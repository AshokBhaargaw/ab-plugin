<?php
/**
 * AB SEO Internal Linking & Orphaned Content Engine
 *
 * Implements Yoast SEO Premium's flagship internal linking features:
 * - Real-time Internal Linking Suggestions in Post Editor
 * - Internal Link Counter (Incoming & Outgoing links per post)
 * - Orphaned Content Detector (Posts with 0 incoming internal links)
 * - Dedicated Admin View Tabs: "Orphaned Content" & "Cornerstone Content"
 * - Post List Table Columns (SEO Score, Readability, Inbound, Outbound Links)
 * - Bulk Link Indexer
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Internal_Linking {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Update link counts on post save
		add_action( 'save_post', array( $this, 'update_post_links_graph' ), 20, 2 );

		// Editor AJAX for live internal linking suggestions
		add_action( 'wp_ajax_ab_seo_get_linking_suggestions', array( $this, 'ajax_get_linking_suggestions' ) );

		// Admin Post & Page list table columns and views
		add_filter( 'manage_posts_columns', array( $this, 'add_seo_columns' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_seo_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_seo_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'render_seo_column' ), 10, 2 );

		// Filter views (Orphaned Content & Cornerstone Content)
		add_filter( 'views_edit-post', array( $this, 'add_post_type_views' ) );
		add_filter( 'views_edit-page', array( $this, 'add_post_type_views' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_posts_by_view' ) );

		// Bulk Re-indexer AJAX
		add_action( 'wp_ajax_ab_seo_reindex_internal_links', array( $this, 'ajax_reindex_internal_links' ) );
	}

	/**
	 * Parse post content on save and update the internal link graph.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function update_post_links_graph( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		$this->analyze_and_store_links( $post_id, $post->post_content );
	}

	/**
	 * Analyze links in content and update inbound/outbound counts.
	 */
	public function analyze_and_store_links( $post_id, $content ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$outgoing_internal_links = array();

		if ( ! empty( $content ) ) {
			// Extract all href URLs from content
			preg_match_all( '/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>/siU', $content, $matches );

			if ( ! empty( $matches[2] ) ) {
				foreach ( $matches[2] as $url ) {
					$url = trim( $url );
					if ( empty( $url ) || 0 === strpos( $url, '#' ) || 0 === strpos( $url, 'mailto:' ) || 0 === strpos( $url, 'tel:' ) ) {
						continue;
					}

					$parsed = wp_parse_url( $url );
					$host   = $parsed['host'] ?? '';

					// Check if internal link
					if ( empty( $host ) || strcasecmp( $host, $home_host ) === 0 ) {
						$target_post_id = url_to_postid( $url );
						if ( $target_post_id && $target_post_id !== $post_id ) {
							$outgoing_internal_links[ $target_post_id ] = true;
						}
					}
				}
			}
		}

		$outgoing_count = count( $outgoing_internal_links );
		update_post_meta( $post_id, '_ab_seo_outgoing_links_count', $outgoing_count );

		// Store target post IDs this post links to
		$linked_targets = array_keys( $outgoing_internal_links );
		update_post_meta( $post_id, '_ab_seo_linked_posts', $linked_targets );

		// Recalculate incoming links for all published posts
		$this->recalculate_all_inbound_counts();
	}

	/**
	 * Recalculate inbound counts for all posts based on stored outbound targets.
	 */
	public function recalculate_all_inbound_counts() {
		global $wpdb;

		// Fetch all linked target arrays
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_ab_seo_linked_posts'"
		);

		$inbound_map = array();

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$targets = maybe_unserialize( $row->meta_value );
				if ( is_array( $targets ) ) {
					foreach ( $targets as $target_id ) {
						$target_id = intval( $target_id );
						if ( $target_id > 0 ) {
							$inbound_map[ $target_id ] = ( $inbound_map[ $target_id ] ?? 0 ) + 1;
						}
					}
				}
			}
		}

		// Update all published posts/pages
		$all_posts = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page')"
		);

		foreach ( $all_posts as $pid ) {
			$inbound_count = $inbound_map[ $pid ] ?? 0;
			update_post_meta( $pid, '_ab_seo_incoming_links_count', $inbound_count );
		}
	}

	/**
	 * AJAX: Provide smart live internal linking suggestions in the editor.
	 */
	public function ajax_get_linking_suggestions() {
		check_ajax_referer( 'ab_seo_metabox_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$post_id   = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$keyword   = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		// Search terms based on keywords or title
		$search_query = ! empty( $keyword ) ? $keyword : $title;
		$search_query = trim( preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $search_query ) );

		$args = array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'post__not_in'   => array( $post_id ),
		);

		if ( ! empty( $search_query ) ) {
			$args['s'] = $search_query;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		$query = new WP_Query( $args );
		$suggestions = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id = get_the_ID();
				$inbound  = (int) get_post_meta( $id, '_ab_seo_incoming_links_count', true );
				$is_cs    = (bool) get_post_meta( $id, '_ab_seo_is_cornerstone', true );

				$suggestions[] = array(
					'id'             => $id,
					'title'          => get_the_title(),
					'url'            => get_permalink(),
					'inbound_links'  => $inbound,
					'is_cornerstone' => $is_cs,
					'date'           => get_the_date(),
					'post_type'      => get_post_type(),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array( 'suggestions' => $suggestions ) );
	}

	/**
	 * Add custom SEO & Link count columns to Post list table.
	 */
	public function add_seo_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'title' === $key ) {
				$new_columns['ab_seo_score']    = '<span class="ab-seo-col-head" title="' . esc_attr__( 'SEO Score', 'ab-addon' ) . '">' . __( 'SEO', 'ab-addon' ) . '</span>';
				$new_columns['ab_seo_read']     = '<span class="ab-seo-col-head" title="' . esc_attr__( 'Readability Score', 'ab-addon' ) . '">' . __( 'Readability', 'ab-addon' ) . '</span>';
				$new_columns['ab_seo_links_in'] = '<span class="ab-seo-col-head" title="' . esc_attr__( 'Incoming Internal Links', 'ab-addon' ) . '"><span class="dashicons dashicons-arrow-down-alt"></span> ' . __( 'In', 'ab-addon' ) . '</span>';
				$new_columns['ab_seo_links_out']= '<span class="ab-seo-col-head" title="' . esc_attr__( 'Outgoing Internal Links', 'ab-addon' ) . '"><span class="dashicons dashicons-arrow-up-alt"></span> ' . __( 'Out', 'ab-addon' ) . '</span>';
			}
		}

		return $new_columns;
	}

	/**
	 * Render column content for SEO indicators.
	 */
	public function render_seo_column( $column, $post_id ) {
		if ( 'ab_seo_score' === $column ) {
			$score = get_post_meta( $post_id, '_ab_seo_score', true );
			$is_cs = get_post_meta( $post_id, '_ab_seo_is_cornerstone', true );

			$class = 'good';
			$label = __( 'Good', 'ab-addon' );
			if ( $score === 'ok' ) {
				$class = 'ok';
				$label = __( 'OK', 'ab-addon' );
			} elseif ( $score === 'bad' || empty( $score ) ) {
				$class = 'bad';
				$label = ! empty( $score ) ? __( 'Needs Improvement', 'ab-addon' ) : __( 'Not analyzed', 'ab-addon' );
			}

			echo '<span class="ab-seo-score-pill ab-seo-score-' . esc_attr( $class ) . '" title="' . esc_attr( $label ) . '"></span>';
			if ( ! empty( $is_cs ) ) {
				echo ' <span class="ab-seo-cornerstone-star" title="' . esc_attr__( 'Cornerstone Content', 'ab-addon' ) . '">★</span>';
			}
		} elseif ( 'ab_seo_read' === $column ) {
			$read = get_post_meta( $post_id, '_ab_seo_readability_score', true );
			$class = ( $read === 'good' ) ? 'good' : ( ( $read === 'ok' ) ? 'ok' : 'bad' );
			echo '<span class="ab-seo-score-pill ab-seo-score-' . esc_attr( $class ) . '"></span>';
		} elseif ( 'ab_seo_links_in' === $column ) {
			$inbound = (int) get_post_meta( $post_id, '_ab_seo_incoming_links_count', true );
			if ( 0 === $inbound ) {
				echo '<span class="ab-seo-badge-zero" title="' . esc_attr__( 'Orphaned content: No incoming links', 'ab-addon' ) . '">0</span>';
			} else {
				echo '<span class="ab-seo-count-badge">' . esc_html( $inbound ) . '</span>';
			}
		} elseif ( 'ab_seo_links_out' === $column ) {
			$outbound = (int) get_post_meta( $post_id, '_ab_seo_outgoing_links_count', true );
			echo '<span class="ab-seo-count-badge">' . esc_html( $outbound ) . '</span>';
		}
	}

	/**
	 * Add Orphaned Content and Cornerstone Content filter views to post list tables.
	 */
	public function add_post_type_views( $views ) {
		global $typenow;

		// Count Orphaned Posts
		$orphaned_count = count( get_posts( array(
			'post_type'      => $typenow,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_ab_seo_incoming_links_count',
					'value'   => '0',
					'compare' => '=',
				),
				array(
					'key'     => '_ab_seo_incoming_links_count',
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		// Count Cornerstone Posts
		$cornerstone_count = count( get_posts( array(
			'post_type'      => $typenow,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_ab_seo_is_cornerstone',
					'value'   => '1',
					'compare' => '=',
				),
			),
		) ) );

		$current_view = isset( $_GET['ab_seo_filter'] ) ? sanitize_key( $_GET['ab_seo_filter'] ) : '';

		$base_url = admin_url( 'edit.php?post_type=' . $typenow );

		$views['ab_orphaned'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'ab_seo_filter', 'orphaned', $base_url ) ),
			( 'orphaned' === $current_view ) ? ' class="current"' : '',
			__( 'Orphaned content', 'ab-addon' ),
			$orphaned_count
		);

		$views['ab_cornerstone'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( add_query_arg( 'ab_seo_filter', 'cornerstone', $base_url ) ),
			( 'cornerstone' === $current_view ) ? ' class="current"' : '',
			__( 'Cornerstone content', 'ab-addon' ),
			$cornerstone_count
		);

		return $views;
	}

	/**
	 * Modify query when an SEO filter view is active.
	 */
	public function filter_posts_by_view( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$filter = isset( $_GET['ab_seo_filter'] ) ? sanitize_key( $_GET['ab_seo_filter'] ) : '';

		if ( 'orphaned' === $filter ) {
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => '_ab_seo_incoming_links_count',
					'value'   => '0',
					'compare' => '=',
				),
				array(
					'key'     => '_ab_seo_incoming_links_count',
					'compare' => 'NOT EXISTS',
				),
			);
			$query->set( 'meta_query', $meta_query );
		} elseif ( 'cornerstone' === $filter ) {
			$meta_query = $query->get( 'meta_query' ) ?: array();
			$meta_query[] = array(
				'key'     => '_ab_seo_is_cornerstone',
				'value'   => '1',
				'compare' => '=',
			);
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * AJAX: Bulk re-index internal link graph for all published posts.
	 */
	public function ajax_reindex_internal_links() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$posts = get_posts( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		foreach ( $posts as $post ) {
			$this->analyze_and_store_links( $post->ID, $post->post_content );
		}

		wp_send_json_success( array(
			'message' => sprintf( __( 'Successfully analyzed and re-indexed internal links for %d posts and pages!', 'ab-addon' ), count( $posts ) ),
		) );
	}
}

AB_SEO_Internal_Linking::instance();
