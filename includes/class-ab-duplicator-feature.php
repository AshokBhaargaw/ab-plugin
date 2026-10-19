<?php
/**
 * AB Duplicator Feature
 *
 * Provides a 1-click duplicate action link in post and page row actions.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Duplicator_Feature {

	public static function init() {
		add_filter( 'post_row_actions', array( __CLASS__, 'add_duplicate_link' ), 10, 2 );
		add_filter( 'page_row_actions', array( __CLASS__, 'add_duplicate_link' ), 10, 2 );
		add_action( 'admin_action_ab_duplicate_post', array( __CLASS__, 'handle_duplicate_action' ) );
	}

	public static function add_duplicate_link( $actions, $post ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin.php?action=ab_duplicate_post&post=' . $post->ID ),
			'ab_duplicate_post_' . $post->ID
		);

		$actions['ab_duplicate'] = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate this item', 'ab-addon' ),
			esc_html__( 'Duplicate', 'ab-addon' )
		);

		return $actions;
	}

	public static function handle_duplicate_action() {
		if ( empty( $_GET['post'] ) ) {
			wp_die( esc_html__( 'No post to duplicate.', 'ab-addon' ) );
		}

		$post_id = intval( $_GET['post'] );
		check_admin_referer( 'ab_duplicate_post_' . $post_id );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ab-addon' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'ab-addon' ) );
		}

		$current_user = wp_get_current_user();

		$new_post_args = array(
			'post_author'    => $current_user->ID,
			'post_content'   => $post->post_content,
			'post_title'     => $post->post_title . ' (Copy)',
			'post_excerpt'   => $post->post_excerpt,
			'post_status'    => 'draft',
			'post_type'      => $post->post_type,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_password'  => $post->post_password,
			'to_ping'        => $post->to_ping,
			'pinged'         => $post->pinged,
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
		);

		$new_post_id = wp_insert_post( $new_post_args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		// Copy post meta
		$post_meta = get_post_custom( $post_id );
		if ( ! empty( $post_meta ) ) {
			foreach ( $post_meta as $key => $values ) {
				foreach ( $values as $value ) {
					add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
				}
			}
		}

		// Copy taxonomies
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
		}

		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}
}
