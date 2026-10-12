<?php
/**
 * AB Weather Block Registration for Gutenberg
 *
 * Registers the native Gutenberg block "ab-addon/weather" (AB Weather).
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-renderer.php';

class AB_Weather_Block {

	public function __construct() {
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Ensure the "AB Addon" block category exists.
	 *
	 * @param array                   $categories
	 * @param WP_Block_Editor_Context $context
	 * @return array
	 */
	public function register_block_category( $categories, $context = null ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'ab-addon' === $category['slug'] ) {
				return $categories;
			}
		}

		$categories[] = array(
			'slug'  => 'ab-addon',
			'title' => esc_html__( 'AB Addon', 'ab-addon' ),
			'icon'  => null,
		);

		return $categories;
	}

	/**
	 * Register Gutenberg block type, editor scripts and editor styles.
	 */
	public function register_block() {
		wp_register_script(
			'ab-weather-block-editor',
			AB_ADDON_URL . 'blocks/weather/editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-block-editor',
				'wp-i18n',
				'wp-server-side-render',
			),
			AB_ADDON_VERSION,
			true
		);

		wp_register_style(
			'ab-weather-block-editor-style',
			AB_ADDON_URL . 'blocks/weather/editor.css',
			array( 'wp-edit-blocks' ),
			AB_ADDON_VERSION
		);

		// Also register the frontend style so ServerSideRender in editor can display the exact styles!
		wp_register_style(
			'ab-weather-style',
			AB_ADDON_URL . 'assets/css/weather.css',
			array(),
			AB_ADDON_VERSION
		);

		register_block_type(
			AB_ADDON_PATH . 'blocks/weather',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Server-side render callback for ab-addon/weather block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block inner content.
	 * @return string HTML output.
	 */
	public function render_block( $attributes, $content = '' ) {
		$args = array(
			'mode'              => $attributes['mode'] ?? 'full',
			'title'             => $attributes['title'] ?? '',
			'forecast_days'     => intval( $attributes['forecastDays'] ?? 5 ),
			'show_feels_like'   => ! isset( $attributes['showFeelsLike'] ) || ! empty( $attributes['showFeelsLike'] ),
			'show_high_low'     => ! isset( $attributes['showHighLow'] ) || ! empty( $attributes['showHighLow'] ),
			'show_humidity'     => ! isset( $attributes['showHumidity'] ) || ! empty( $attributes['showHumidity'] ),
			'show_wind'         => ! isset( $attributes['showWind'] ) || ! empty( $attributes['showWind'] ),
			'show_rain_prob'    => ! isset( $attributes['showRainProb'] ) || ! empty( $attributes['showRainProb'] ),
			'show_sun_times'    => ! isset( $attributes['showSunTimes'] ) || ! empty( $attributes['showSunTimes'] ),
			'show_updated_time' => ! isset( $attributes['showUpdatedTime'] ) || ! empty( $attributes['showUpdatedTime'] ),
			'show_cta'          => ! isset( $attributes['showCta'] ) || ! empty( $attributes['showCta'] ),
			'cta_text'          => $attributes['ctaText'] ?? '',
			'cta_url'           => $attributes['ctaUrl'] ?? '',
		);

		return AB_Weather_Renderer::render( $args );
	}
}
