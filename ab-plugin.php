<?php
/**
 * Plugin Name: AB Addon
 * Plugin URI:  https://ramdevraa.in
 * Description: Custom Elementor Addon with Blog Grid, Basic Posts widgets, Custom CSS for all elements, and a powerful Popup manager (AB Settings).
 * Version:     1.2.0
 * Author:      Ashok Bhaargaw
 * Text Domain: ab-addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'AB_ADDON_VERSION', '1.2.2' );
define( 'AB_ADDON_PATH', plugin_dir_path( __FILE__ ) );
define( 'AB_ADDON_URL', plugin_dir_url( __FILE__ ) );

final class AB_Addon_Elementor {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Core (Elementor-independent) features — always initialise.
		$this->init_core();

		// Elementor-dependent features.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialise features that do NOT require Elementor.
	 * Called from __construct so they are always available.
	 */
	private function init_core() {
		// Admin Settings page (popup manager).
		require_once AB_ADDON_PATH . 'admin/class-ab-settings-page.php';
		new AB_Settings_Page();

		// Standalone shortcode renderer.
		require_once AB_ADDON_PATH . 'includes/class-ab-popup-shortcode.php';
		new AB_Popup_Shortcode();
	}

	public function init() {
		// Check if Elementor is installed and active
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_elementor' ) );
			return;
		}

		// Register Widget Category
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_category' ) );

		// Register Widgets
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register Scripts and Styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// AJAX Handler for Category Filter & Pagination
		add_action( 'wp_ajax_ab_blog_grid_filter', array( $this, 'ajax_filter_posts' ) );
		add_action( 'wp_ajax_nopriv_ab_blog_grid_filter', array( $this, 'ajax_filter_posts' ) );

		// Custom CSS for Elementor Elements (Advanced Tab)
		require_once AB_ADDON_PATH . 'includes/class-ab-custom-css.php';
		\AB_Custom_CSS::instance();
	}

	public function admin_notice_missing_elementor() {
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'ab-addon' ),
			'<strong>' . esc_html__( 'AB Addon', 'ab-addon' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'ab-addon' ) . '</strong>'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	public function add_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'ab-addons',
			array(
				'title' => esc_html__( 'AB Addons', 'ab-addon' ),
				'icon'  => 'eicon-code',
			)
		);
	}

	public function register_widgets( $widgets_manager ) {
		require_once AB_ADDON_PATH . 'widgets/class-ab-blog-grid-widget.php';
		require_once AB_ADDON_PATH . 'widgets/class-ab-basic-posts-widget.php';
		// Note: Popup is now managed via AB Settings admin page and [ab_popup] shortcode.
		// The Elementor popup widget (class-ab-popup-widget.php) is intentionally not loaded here.

		$widgets_manager->register( new \AB_Blog_Grid_Widget() );
		$widgets_manager->register( new \AB_Basic_Posts_Widget() );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'ab-blog-grid-style',
			AB_ADDON_URL . 'assets/css/blog-grid.css',
			array(),
			AB_ADDON_VERSION
		);

		// Note: popup CSS/JS are now registered and conditionally enqueued by AB_Popup_Shortcode.

		wp_enqueue_script(
			'ab-blog-grid-script',
			AB_ADDON_URL . 'assets/js/blog-grid.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);

		wp_localize_script(
			'ab-blog-grid-script',
			'ab_addon_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ab_blog_grid_nonce' ),
			)
		);
	}

	public function ajax_filter_posts() {
		check_ajax_referer( 'ab_blog_grid_nonce', 'nonce' );

		$cat_id      = isset( $_POST['cat_id'] ) ? intval( $_POST['cat_id'] ) : 0;
		$post_type   = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'post';
		$ppp         = isset( $_POST['ppp'] ) ? intval( $_POST['ppp'] ) : 6;
		$orderby     = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'date';
		$order       = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : 'DESC';
		$paged       = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
		$offset      = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;

		$settings = isset( $_POST['settings'] ) ? (array) $_POST['settings'] : array();

		// Sanitize settings array values
		$clean_settings = array();
		foreach ( $settings as $k => $v ) {
			$clean_settings[ sanitize_key( $k ) ] = is_array( $v ) ? array_map( 'sanitize_text_field', $v ) : sanitize_text_field( $v );
		}

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $ppp,
			'paged'          => $paged,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		);

		if ( $offset > 0 ) {
			$args['offset'] = $offset + ( ( $paged - 1 ) * $ppp );
		}

		if ( $cat_id > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => ( $post_type === 'post' ) ? 'category' : 'category',
					'field'    => 'term_id',
					'terms'    => array( $cat_id ),
				),
			);
		} elseif ( ! empty( $clean_settings['categories'] ) ) {
			$selected_cats = (array) $clean_settings['categories'];
			$args['tax_query'] = array(
				array(
					'taxonomy' => ( $post_type === 'post' ) ? 'category' : 'category',
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', $selected_cats ),
				),
			);
		}

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( class_exists( '\AB_Basic_Posts_Widget' ) ) {
					\AB_Basic_Posts_Widget::render_single_card( $clean_settings );
				} else {
					\AB_Blog_Grid_Widget::render_single_card( $clean_settings );
				}
			}
			wp_reset_postdata();
		} else {
			echo '<div class="ab-grid-no-posts"><p>' . esc_html__( 'No posts found in this category.', 'ab-addon' ) . '</p></div>';
		}

		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'        => $html,
			'max_pages'   => $query->max_num_pages,
			'found_posts' => $query->found_posts,
			'paged'       => $paged,
		) );
	}
}

AB_Addon_Elementor::instance();
