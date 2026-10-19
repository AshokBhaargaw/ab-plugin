<?php
/**
 * AB Slider Shortcode
 *
 * Provides the [ab_slider id="..."] shortcode and a backwards-compatible
 * [smartslider3 slider="..."] alias so you can seamlessly replace Smart Slider 3
 * without breaking existing pages or templates.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/slider/class-ab-slider-renderer.php';

class AB_Slider_Shortcode {

	public function __construct() {
		// Primary shortcode
		add_shortcode( 'ab_slider', array( $this, 'render_shortcode' ) );

		// Smart Slider 3 backwards-compatibility alias
		// Only register if Smart Slider 3 plugin is not active or about to be replaced
		if ( ! shortcode_exists( 'smartslider3' ) ) {
			add_shortcode( 'smartslider3', array( $this, 'render_smartslider_compatibility' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register frontend assets so they can be enqueued when the shortcode is used.
	 */
	public function register_assets() {
		// Register Swiper styles if not already registered by Elementor
		if ( ! wp_style_is( 'swiper', 'registered' ) && ! wp_style_is( 'e-swiper', 'registered' ) ) {
			wp_register_style(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
				array(),
				'11.0.0'
			);
		}

		wp_register_style(
			'ab-slider-style',
			AB_ADDON_URL . 'assets/css/slider.css',
			array(),
			AB_ADDON_VERSION
		);

		// Register Swiper script if not already registered by Elementor
		if ( ! wp_script_is( 'swiper', 'registered' ) ) {
			wp_register_script(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
				array(),
				'11.0.0',
				true
			);
		}

		$deps = array( 'jquery' );
		if ( wp_script_is( 'swiper', 'registered' ) ) {
			$deps[] = 'swiper';
		}

		wp_register_script(
			'ab-slider-script',
			AB_ADDON_URL . 'assets/js/slider.js',
			$deps,
			AB_ADDON_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets when shortcode runs.
	 */
	public function enqueue_assets() {
		if ( wp_style_is( 'e-swiper', 'registered' ) ) {
			wp_enqueue_style( 'e-swiper' );
		} elseif ( wp_style_is( 'swiper', 'registered' ) ) {
			wp_enqueue_style( 'swiper' );
		}

		wp_enqueue_style( 'ab-slider-style' );

		if ( wp_script_is( 'swiper', 'registered' ) ) {
			wp_enqueue_script( 'swiper' );
		}

		wp_enqueue_script( 'ab-slider-script' );
	}

	/**
	 * Render [ab_slider id="..."]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'   => '',
				'slug' => '',
				'name' => '',
			),
			$atts,
			'ab_slider'
		);

		$slider_id = ! empty( $atts['id'] ) ? $atts['id'] : ( ! empty( $atts['slug'] ) ? $atts['slug'] : $atts['name'] );

		// If no ID specified, take the first available slider
		if ( empty( $slider_id ) ) {
			$all = AB_Slider_Renderer::get_sliders();
			if ( ! empty( $all ) ) {
				$first = reset( $all );
				$slider_id = $first['id'];
			}
		}

		$this->enqueue_assets();

		return AB_Slider_Renderer::render_by_id( $slider_id );
	}

	/**
	 * Backwards-compatibility handler for [smartslider3 slider="..."]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_smartslider_compatibility( $atts ) {
		$atts = shortcode_atts(
			array(
				'slider' => '',
				'id'     => '',
			),
			$atts,
			'smartslider3'
		);

		$target_id = ! empty( $atts['slider'] ) ? $atts['slider'] : $atts['id'];

		$this->enqueue_assets();

		// If no slider specified or it's a numeric ID, render_by_id handles lookup or fallback
		return AB_Slider_Renderer::render_by_id( $target_id );
	}
}
