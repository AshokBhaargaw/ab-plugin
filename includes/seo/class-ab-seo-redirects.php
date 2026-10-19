<?php
/**
 * AB SEO Redirects Manager & 404 Monitor
 *
 * Full-featured replacement for Yoast SEO Premium Redirect Manager:
 * - Automatic 301 redirect on post slug / URL change
 * - Automatic handling on post deletion / trash (410 or redirect)
 * - Custom Plain & Regex redirects (301, 302, 307, 410, 451)
 * - Real-time 404 error monitor with 1-click redirect creator
 * - Hit counter & last-hit timestamp tracking
 * - CSV Import & Export
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Redirects {

	const OPTION_REDIRECTS = 'ab_seo_redirects';
	const OPTION_404_LOGS  = 'ab_seo_404_logs';

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Listen for URL / slug changes
		add_action( 'post_updated', array( $this, 'detect_slug_change' ), 10, 3 );
		add_action( 'wp_trash_post', array( $this, 'detect_post_trash' ) );

		// Execute redirects & monitor 404
		add_action( 'template_redirect', array( $this, 'handle_frontend_redirect' ), 1 );

		// AJAX handlers for redirect CRUD & 404 actions
		add_action( 'wp_ajax_ab_seo_save_redirect', array( $this, 'ajax_save_redirect' ) );
		add_action( 'wp_ajax_ab_seo_delete_redirect', array( $this, 'ajax_delete_redirect' ) );
		add_action( 'wp_ajax_ab_seo_clear_404_logs', array( $this, 'ajax_clear_404_logs' ) );
		add_action( 'wp_ajax_ab_seo_convert_404', array( $this, 'ajax_convert_404' ) );
		add_action( 'wp_ajax_ab_seo_export_redirects', array( $this, 'ajax_export_redirects' ) );
		add_action( 'wp_ajax_ab_seo_import_redirects', array( $this, 'ajax_import_redirects' ) );
	}

	/**
	 * Get all active redirects.
	 *
	 * @return array
	 */
	public static function get_redirects() {
		$redirects = get_option( self::OPTION_REDIRECTS, array() );
		return is_array( $redirects ) ? $redirects : array();
	}

	/**
	 * Get all 404 logs.
	 *
	 * @return array
	 */
	public static function get_404_logs() {
		$logs = get_option( self::OPTION_404_LOGS, array() );
		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * Save redirects array.
	 *
	 * @param array $redirects
	 */
	public static function save_redirects( $redirects ) {
		update_option( self::OPTION_REDIRECTS, $redirects );
	}

	/**
	 * Save 404 logs array.
	 *
	 * @param array $logs
	 */
	public static function save_404_logs( $logs ) {
		update_option( self::OPTION_404_LOGS, $logs );
	}

	/**
	 * Detect when a post's slug changes, and auto-create a 301 redirect.
	 * Matches Yoast SEO Premium Automatic Redirect Manager.
	 */
	public function detect_slug_change( $post_id, $post_after, $post_before ) {
		$options = AB_SEO::get_options();
		if ( empty( $options['auto_redirect_slug'] ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post_before->post_status || 'publish' !== $post_after->post_status ) {
			return;
		}

		if ( $post_before->post_name !== $post_after->post_name ) {
			$old_permalink = get_permalink( $post_before );
			$new_permalink = get_permalink( $post_after );

			$old_path = wp_make_link_relative( $old_permalink );
			$new_path = wp_make_link_relative( $new_permalink );

			if ( ! empty( $old_path ) && ! empty( $new_path ) && $old_path !== $new_path ) {
				$this->add_redirect( array(
					'id'          => 'auto_' . uniqid(),
					'source_url'  => $old_path,
					'target_url'  => $new_path,
					'type'        => '301',
					'is_regex'    => 0,
					'hits'        => 0,
					'created_at'  => current_time( 'mysql' ),
					'note'        => sprintf( __( 'Auto-created: slug changed for "%s"', 'ab-addon' ), get_the_title( $post_after ) ),
				) );
			}
		}
	}

	/**
	 * Detect when a published post is sent to Trash.
	 */
	public function detect_post_trash( $post_id ) {
		$options = AB_SEO::get_options();
		if ( empty( $options['auto_redirect_trash'] ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		$permalink = get_permalink( $post_id );
		$rel_path  = wp_make_link_relative( $permalink );

		if ( ! empty( $rel_path ) ) {
			// Auto create 410 Gone redirect to prevent SEO crawl waste
			$this->add_redirect( array(
				'id'          => 'trash_' . uniqid(),
				'source_url'  => $rel_path,
				'target_url'  => '',
				'type'        => '410',
				'is_regex'    => 0,
				'hits'        => 0,
				'created_at'  => current_time( 'mysql' ),
				'note'        => sprintf( __( 'Auto-created: post trashed "%s"', 'ab-addon' ), get_the_title( $post ) ),
			) );
		}
	}

	/**
	 * Add or update a redirect.
	 *
	 * @param array $data
	 */
	public function add_redirect( $data ) {
		$redirects = self::get_redirects();

		$source = trim( $data['source_url'] );
		$source = '/' . ltrim( $source, '/' );

		// Check if source already exists, update if so
		$found = false;
		foreach ( $redirects as $k => $item ) {
			if ( $item['source_url'] === $source ) {
				$redirects[ $k ] = array_merge( $item, $data );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			$data['source_url'] = $source;
			$redirects[] = $data;
		}

		self::save_redirects( $redirects );
	}

	/**
	 * Intercept incoming requests on template_redirect.
	 */
	public function handle_frontend_redirect() {
		if ( is_admin() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$path        = parse_url( $request_uri, PHP_URL_PATH );
		$path        = '/' . ltrim( $path, '/' );

		$redirects = self::get_redirects();
		$matched   = null;
		$target    = '';
		$status    = 301;

		foreach ( $redirects as $index => $red ) {
			$source = $red['source_url'];

			if ( ! empty( $red['is_regex'] ) ) {
				// Regex match
				$pattern = '#' . str_replace( '#', '\#', $source ) . '#i';
				if ( @preg_match( $pattern, $path ) ) {
					$matched = $index;
					$target  = preg_replace( $pattern, $red['target_url'], $path );
					$status  = intval( $red['type'] );
					break;
				}
			} else {
				// Exact match (ignore trailing slash difference)
				if ( rtrim( $source, '/' ) === rtrim( $path, '/' ) ) {
					$matched = $index;
					$target  = $red['target_url'];
					$status  = intval( $red['type'] );
					break;
				}
			}
		}

		if ( null !== $matched ) {
			// Update hit counter
			$redirects[ $matched ]['hits']      = ( $redirects[ $matched ]['hits'] ?? 0 ) + 1;
			$redirects[ $matched ]['last_used'] = current_time( 'mysql' );
			self::save_redirects( $redirects );

			if ( 410 === $status ) {
				status_header( 410 );
				nocache_headers();
				wp_die(
					'<h1>' . esc_html__( '410 Gone - Content Deleted', 'ab-addon' ) . '</h1><p>' . esc_html__( 'The requested content has been permanently removed by the author.', 'ab-addon' ) . '</p>',
					esc_html__( 'Content Deleted', 'ab-addon' ),
					array( 'response' => 410 )
				);
			}

			if ( 451 === $status ) {
				status_header( 451 );
				nocache_headers();
				wp_die(
					'<h1>' . esc_html__( '451 Unavailable For Legal Reasons', 'ab-addon' ) . '</h1>',
					esc_html__( 'Unavailable', 'ab-addon' ),
					array( 'response' => 451 )
				);
			}

			if ( ! empty( $target ) ) {
				// If target is relative, make full url
				if ( 0 === strpos( $target, '/' ) ) {
					$target = home_url( $target );
				}

				wp_redirect( $target, in_array( $status, array( 301, 302, 307 ), true ) ? $status : 301 );
				exit;
			}
		}

		// 404 Monitor logic
		if ( is_404() ) {
			$options = AB_SEO::get_options();
			if ( ! empty( $options['monitor_404'] ) ) {
				$this->log_404_error( $path );
			}
		}
	}

	/**
	 * Log 404 errors for analysis & easy 1-click redirects.
	 */
	private function log_404_error( $path ) {
		// Ignore common static asset extensions to prevent bloat
		$ext = pathinfo( $path, PATHINFO_EXTENSION );
		if ( in_array( strtolower( $ext ), array( 'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'woff', 'woff2', 'ico', 'map' ), true ) ) {
			return;
		}

		$logs = self::get_404_logs();
		$hash = md5( $path );

		$referer    = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( isset( $logs[ $hash ] ) ) {
			$logs[ $hash ]['hits']++;
			$logs[ $hash ]['last_seen'] = current_time( 'mysql' );
			if ( ! empty( $referer ) ) {
				$logs[ $hash ]['referer'] = $referer;
			}
		} else {
			// Limit total logs to 250 items to keep DB clean
			if ( count( $logs ) > 250 ) {
				array_shift( $logs );
			}

			$logs[ $hash ] = array(
				'url'        => $path,
				'hits'       => 1,
				'first_seen' => current_time( 'mysql' ),
				'last_seen'  => current_time( 'mysql' ),
				'referer'    => $referer,
				'user_agent' => substr( $user_agent, 0, 150 ),
			);
		}

		self::save_404_logs( $logs );
	}

	/**
	 * AJAX: Save redirect (add or update)
	 */
	public function ajax_save_redirect() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$id         = ! empty( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : 'red_' . uniqid();
		$source_url = isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '';
		$target_url = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';
		$type       = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '301';
		$is_regex   = ! empty( $_POST['is_regex'] ) ? 1 : 0;
		$note       = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( empty( $source_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Source URL is required.', 'ab-addon' ) ) );
		}

		$redirects = self::get_redirects();

		$data = array(
			'id'         => $id,
			'source_url' => '/' . ltrim( trim( $source_url ), '/' ),
			'target_url' => trim( $target_url ),
			'type'       => $type,
			'is_regex'   => $is_regex,
			'note'       => $note,
			'created_at' => current_time( 'mysql' ),
			'hits'       => 0,
		);

		$updated = false;
		foreach ( $redirects as $k => $item ) {
			if ( $item['id'] === $id ) {
				$data['hits'] = $item['hits'] ?? 0;
				$redirects[ $k ] = array_merge( $item, $data );
				$updated = true;
				break;
			}
		}

		if ( ! $updated ) {
			$redirects[] = $data;
		}

		self::save_redirects( $redirects );

		wp_send_json_success( array( 'message' => __( 'Redirect saved successfully!', 'ab-addon' ) ) );
	}

	/**
	 * AJAX: Delete redirect
	 */
	public function ajax_delete_redirect() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$id = isset( $_POST['id'] ) ? sanitize_key( $_POST['id'] ) : '';
		$redirects = self::get_redirects();

		$filtered = array();
		foreach ( $redirects as $item ) {
			if ( $item['id'] !== $id ) {
				$filtered[] = $item;
			}
		}

		self::save_redirects( $filtered );
		wp_send_json_success( array( 'message' => __( 'Redirect deleted.', 'ab-addon' ) ) );
	}

	/**
	 * AJAX: Clear 404 Logs
	 */
	public function ajax_clear_404_logs() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		self::save_404_logs( array() );
		wp_send_json_success( array( 'message' => __( '404 logs cleared.', 'ab-addon' ) ) );
	}

	/**
	 * AJAX: Convert 404 into 301 Redirect
	 */
	public function ajax_convert_404() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$url    = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '/';

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid 404 URL.', 'ab-addon' ) ) );
		}

		// Add 301 redirect
		$this->add_redirect( array(
			'id'         => 'from404_' . uniqid(),
			'source_url' => $url,
			'target_url' => $target,
			'type'       => '301',
			'is_regex'   => 0,
			'hits'       => 0,
			'created_at' => current_time( 'mysql' ),
			'note'       => __( 'Resolved from 404 Monitor', 'ab-addon' ),
		) );

		// Remove from 404 log
		$logs = self::get_404_logs();
		$hash = md5( $url );
		if ( isset( $logs[ $hash ] ) ) {
			unset( $logs[ $hash ] );
			self::save_404_logs( $logs );
		}

		wp_send_json_success( array( 'message' => __( '301 Redirect created and 404 entry resolved!', 'ab-addon' ) ) );
	}

	/**
	 * AJAX: Export Redirects
	 */
	public function ajax_export_redirects() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission denied.' );
		}

		$redirects = self::get_redirects();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ab-seo-redirects-' . date( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Source URL', 'Target URL', 'Type', 'Is Regex', 'Note', 'Hits', 'Created At' ) );

		foreach ( $redirects as $row ) {
			fputcsv( $output, array(
				$row['source_url'] ?? '',
				$row['target_url'] ?? '',
				$row['type'] ?? '301',
				! empty( $row['is_regex'] ) ? '1' : '0',
				$row['note'] ?? '',
				$row['hits'] ?? 0,
				$row['created_at'] ?? '',
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX: Import Redirects (CSV)
	 */
	public function ajax_import_redirects() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		if ( empty( $_FILES['file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'ab-addon' ) ) );
		}

		$handle = fopen( $_FILES['file']['tmp_name'], 'r' );
		if ( false === $handle ) {
			wp_send_json_error( array( 'message' => __( 'Cannot read CSV file.', 'ab-addon' ) ) );
		}

		$header = fgetcsv( $handle );
		$imported = 0;

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			if ( empty( $data[0] ) ) {
				continue;
			}
			$this->add_redirect( array(
				'id'         => 'imp_' . uniqid(),
				'source_url' => sanitize_text_field( $data[0] ),
				'target_url' => isset( $data[1] ) ? sanitize_text_field( $data[1] ) : '',
				'type'       => isset( $data[2] ) ? sanitize_text_field( $data[2] ) : '301',
				'is_regex'   => ! empty( $data[3] ) ? 1 : 0,
				'note'       => isset( $data[4] ) ? sanitize_text_field( $data[4] ) : __( 'Imported from CSV', 'ab-addon' ),
				'hits'       => 0,
				'created_at' => current_time( 'mysql' ),
			) );
			$imported++;
		}

		fclose( $handle );

		wp_send_json_success( array( 'message' => sprintf( __( 'Successfully imported %d redirects!', 'ab-addon' ), $imported ) ) );
	}
}

AB_SEO_Redirects::instance();
