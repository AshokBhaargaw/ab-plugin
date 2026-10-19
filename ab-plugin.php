<?php
/**
 * Plugin Name: AB Addon
 * Plugin URI:  https://ramdevraa.in
 * Description: Custom Elementor Addon with Blog Grid, Basic Posts widgets, Custom CSS for all elements, and a powerful Popup manager (AB Settings).
 * Version:     1.2.5
 * Author:      Ashok Bhaargaw
 * Text Domain: ab-addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'AB_ADDON_VERSION', '1.2.5' );
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

		// Gutenberg: Elementor Template block.
		require_once AB_ADDON_PATH . 'includes/class-ab-elementor-template-block.php';
		new AB_Elementor_Template_Block();

		// Hotel Helper.
		require_once AB_ADDON_PATH . 'includes/class-ab-hotel-helper.php';

		// Archive Template Manager.
		require_once AB_ADDON_PATH . 'includes/class-ab-archive-template-manager.php';
		new AB_Archive_Template_Manager();

		// Archive Templates Admin Settings.
		require_once AB_ADDON_PATH . 'admin/class-ab-archive-settings.php';
		new AB_Archive_Settings();

		// ACF Repeater (provides Repeater field for free).
		require_once AB_ADDON_PATH . 'includes/class-ab-acf-repeater.php';
		new AB_ACF_Repeater();

		// AB Weather — Ramdevra Weather Service & Renderer.
		require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-service.php';
		require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-renderer.php';

		// Weather Shortcode.
		require_once AB_ADDON_PATH . 'includes/class-ab-weather-shortcode.php';
		new AB_Weather_Shortcode();

		// Weather Gutenberg Block.
		require_once AB_ADDON_PATH . 'includes/class-ab-weather-block.php';
		new AB_Weather_Block();

		// Weather Admin Settings.
		require_once AB_ADDON_PATH . 'admin/class-ab-weather-settings.php';
		new AB_Weather_Settings();

		// Central Widgets Manager & Registry.
		require_once AB_ADDON_PATH . 'includes/class-ab-widgets-manager.php';
		AB_Widgets_Manager::instance();

		// Widgets Admin Settings page (Control Center).
		require_once AB_ADDON_PATH . 'admin/class-ab-widgets-settings.php';
		new AB_Widgets_Settings();

		// AB Slider — Hero Slider Renderer, Shortcode & Admin Settings.
		require_once AB_ADDON_PATH . 'includes/slider/class-ab-slider-renderer.php';
		require_once AB_ADDON_PATH . 'includes/class-ab-slider-shortcode.php';
		new AB_Slider_Shortcode();
		require_once AB_ADDON_PATH . 'admin/class-ab-slider-settings.php';
		new AB_Slider_Settings();
	}

	public function init() {
		// Check if Elementor is installed and active
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_elementor' ) );
			return;
		}

		// Ensure Elementor container and nested-elements features are active for direct widget drop zones.
		if ( 'active' !== get_option( 'elementor_experiment-nested-elements' ) ) {
			update_option( 'elementor_experiment-nested-elements', 'active' );
		}
		if ( 'active' !== get_option( 'elementor_experiment-container' ) ) {
			update_option( 'elementor_experiment-container', 'active' );
		}

		// Register Widget Category
		add_action( 'elementor/elements/categories_registered', array( $this, 'add_elementor_category' ) );

		// Register Widgets
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register Scripts and Styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'enqueue_frontend_assets' ) );

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
		// Register all active widgets controlled from AB Settings > Widgets
		\AB_Widgets_Manager::instance()->register_active_widgets( $widgets_manager );
	}

	public function enqueue_frontend_assets() {
		// Register local Swiper bundle if not already registered
		if ( ! wp_script_is( 'swiper', 'registered' ) ) {
			wp_register_script(
				'swiper',
				AB_ADDON_URL . 'assets/js/swiper-bundle.min.js',
				array(),
				'8.4.7',
				true
			);
		}

		if ( ! wp_style_is( 'swiper', 'registered' ) ) {
			wp_register_style(
				'swiper',
				AB_ADDON_URL . 'assets/css/swiper-bundle.min.css',
				array(),
				'8.4.7'
			);
		}

		// Enqueue Swiper CSS (prefer Elementor e-swiper if registered, otherwise our swiper stylesheet)
		if ( wp_style_is( 'e-swiper', 'registered' ) ) {
			wp_enqueue_style( 'e-swiper' );
		} else {
			wp_enqueue_style( 'swiper' );
		}

		// Always enqueue Swiper script
		wp_enqueue_script( 'swiper' );

		wp_enqueue_style(
			'ab-blog-grid-style',
			AB_ADDON_URL . 'assets/css/blog-grid.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_register_style(
			'ab-hotel-grid-style',
			AB_ADDON_URL . 'assets/css/hotel-grid.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_register_style(
			'ab-weather-style',
			AB_ADDON_URL . 'assets/css/weather.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_register_style(
			'ab-slider-style',
			AB_ADDON_URL . 'assets/css/slider.css',
			array(),
			AB_ADDON_VERSION
		);
		wp_enqueue_style( 'ab-slider-style' );

		// Note: popup CSS/JS are now registered and conditionally enqueued by AB_Popup_Shortcode.

		$script_deps = array( 'jquery', 'swiper' );

		wp_enqueue_script(
			'ab-blog-grid-script',
			AB_ADDON_URL . 'assets/js/blog-grid.js',
			$script_deps,
			AB_ADDON_VERSION,
			true
		);

		wp_register_script(
			'ab-slider-script',
			AB_ADDON_URL . 'assets/js/slider.js',
			$script_deps,
			AB_ADDON_VERSION,
			true
		);
		wp_enqueue_script( 'ab-slider-script' );

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
		// Verify nonce if present, but allow soft pass for cached read-only requests
		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {
			check_ajax_referer( 'ab_blog_grid_nonce', 'nonce', false );
		}

		require_once AB_ADDON_PATH . 'includes/class-ab-card-renderer.php';

		$cat_id      = isset( $_POST['cat_id'] ) ? intval( $_POST['cat_id'] ) : 0;
		$post_type   = isset( $_POST['post_type'] ) && ! empty( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'post';
		$ppp         = isset( $_POST['ppp'] ) ? intval( $_POST['ppp'] ) : 6;
		$orderby     = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'date';
		$order       = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : 'DESC';
		$paged       = isset( $_POST['paged'] ) ? max( 1, intval( $_POST['paged'] ) ) : 1;
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

		// Only apply offset when viewing all posts ($cat_id === 0), not when filtering by a specific category
		if ( 0 === $cat_id && $offset > 0 ) {
			$args['offset'] = $offset + ( ( $paged - 1 ) * $ppp );
		}

		if ( $cat_id > 0 ) {
			// Dynamically determine taxonomy for this term
			$term = get_term( $cat_id );
			$taxonomy = ( $term && ! is_wp_error( $term ) && ! empty( $term->taxonomy ) ) ? $term->taxonomy : 'category';

			$args['tax_query'] = array(
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'term_id',
					'terms'            => array( $cat_id ),
					'include_children' => true,
				),
			);
		} elseif ( ! empty( $clean_settings['categories'] ) ) {
			$selected_cats = array_filter( array_map( 'intval', (array) $clean_settings['categories'] ) );
			if ( ! empty( $selected_cats ) ) {
				$first_cat_id = reset( $selected_cats );
				$first_term   = get_term( $first_cat_id );
				$taxonomy     = ( $first_term && ! is_wp_error( $first_term ) && ! empty( $first_term->taxonomy ) ) ? $first_term->taxonomy : 'category';

				$args['tax_query'] = array(
					array(
						'taxonomy'         => $taxonomy,
						'field'            => 'term_id',
						'terms'            => $selected_cats,
						'include_children' => true,
					),
				);
			}
		}

		$query = new WP_Query( $args );

		ob_start();

		if ( $query->have_posts() ) {
			$is_slider = ( isset( $clean_settings['layout_type'] ) && 'slider' === $clean_settings['layout_type'] );
			while ( $query->have_posts() ) {
				$query->the_post();
				if ( $is_slider ) {
					echo '<div class="swiper-slide">';
				}
				\AB_Card_Renderer::render_blog_card( $clean_settings );
				if ( $is_slider ) {
					echo '</div>';
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
