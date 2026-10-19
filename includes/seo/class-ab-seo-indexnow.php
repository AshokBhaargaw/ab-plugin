<?php
/**
 * AB SEO IndexNow Integration
 *
 * Implements the IndexNow instant indexing protocol for Bing, Yandex, and other search engines.
 * Whenever content is published or updated, IndexNow instantly notifies search engines
 * to crawl and index it within minutes.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_IndexNow {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Key verification file handler
		add_action( 'init', array( $this, 'serve_key_verification' ), 1 );

		// Auto submit upon publishing or updating
		add_action( 'publish_post', array( $this, 'auto_submit_post' ), 10, 2 );
		add_action( 'publish_page', array( $this, 'auto_submit_post' ), 10, 2 );

		// Manual test submission AJAX
		add_action( 'wp_ajax_ab_seo_submit_indexnow', array( $this, 'ajax_submit_indexnow' ) );
		add_action( 'wp_ajax_ab_seo_generate_indexnow_key', array( $this, 'ajax_generate_key' ) );
	}

	/**
	 * Get or generate IndexNow API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		$options = AB_SEO::get_options();
		$key = $options['indexnow_api_key'] ?? '';

		if ( empty( $key ) ) {
			$key = wp_generate_password( 32, false, false );
			$options['indexnow_api_key'] = $key;
			update_option( 'ab_seo_settings', $options );
		}

		return $key;
	}

	/**
	 * Serve key verification txt file (e.g., /{key}.txt returns {key})
	 */
	public function serve_key_verification() {
		$key = self::get_api_key();
		if ( empty( $key ) ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = trim( parse_url( $request_uri, PHP_URL_PATH ), '/' );

		if ( $path === $key . '.txt' ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $key );
			exit;
		}
	}

	/**
	 * Automatically submit post URL to IndexNow when published.
	 */
	public function auto_submit_post( $post_id, $post ) {
		$options = AB_SEO::get_options();
		if ( empty( $options['indexnow_enable'] ) || empty( $options['indexnow_auto_submit'] ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$permalink = get_permalink( $post_id );
		if ( $permalink ) {
			self::submit_url( $permalink );
		}
	}

	/**
	 * Submit a URL or array of URLs to IndexNow API.
	 *
	 * @param string|array $urls
	 * @return array Response
	 */
	public static function submit_url( $urls ) {
		$key = self::get_api_key();
		if ( empty( $key ) ) {
			return array( 'success' => false, 'message' => __( 'No IndexNow key configured.', 'ab-addon' ) );
		}

		$urls_list = is_array( $urls ) ? $urls : array( $urls );
		$host      = wp_parse_url( home_url(), PHP_URL_HOST );
		$key_location = home_url( '/' . $key . '.txt' );

		$payload = array(
			'host'        => $host,
			'key'         => $key,
			'keyLocation' => $key_location,
			'urlList'     => array_values( array_unique( $urls_list ) ),
		);

		$response = wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );

		// 200 = OK, 202 = Accepted
		if ( in_array( $status, array( 200, 202 ), true ) ) {
			update_option( 'ab_seo_last_indexnow_submit', current_time( 'mysql' ) );
			return array(
				'success' => true,
				'message' => sprintf( __( 'Successfully submitted %d URL(s) to IndexNow (Status %d)!', 'ab-addon' ), count( $urls_list ), $status ),
			);
		}

		return array(
			'success' => false,
			'message' => sprintf( __( 'IndexNow returned HTTP %d response.', 'ab-addon' ), $status ),
		);
	}

	/**
	 * AJAX: Manual submission.
	 */
	public function ajax_submit_indexnow() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : home_url( '/' );
		$result = self::submit_url( $url );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Regenerate key.
	 */
	public function ajax_generate_key() {
		check_ajax_referer( 'ab_seo_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$new_key = wp_generate_password( 32, false, false );
		$options = AB_SEO::get_options();
		$options['indexnow_api_key'] = $new_key;
		update_option( 'ab_seo_settings', $options );

		wp_send_json_success( array(
			'key'          => $new_key,
			'key_location' => home_url( '/' . $new_key . '.txt' ),
			'message'      => __( 'New IndexNow Key generated successfully!', 'ab-addon' ),
		) );
	}
}

AB_SEO_IndexNow::instance();
