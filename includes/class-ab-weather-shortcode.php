<?php
/**
 * AB Weather Shortcode
 *
 * Registers the [ab_weather] shortcode to display the Ramdevra weather card
 * anywhere in WordPress content, Elementor shortcode widgets, or Gutenberg shortcode blocks.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-renderer.php';

class AB_Weather_Shortcode {

	public function __construct() {
		add_shortcode( 'ab_weather', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render [ab_weather] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'mode'          => 'full', // 'compact', 'current', 'full', 'forecast'
				'days'          => '5',
				'title'         => '',
				'show_cta'      => '',     // 'yes', 'no', 'true', 'false', ''
				'cta_text'      => '',
				'cta_url'       => '',
				'max_width'     => '',
				'show_meta'     => 'yes',
				'class'         => '',
			),
			$atts,
			'ab_weather'
		);

		// Map 'current' to 'compact'.
		$mode = sanitize_key( $atts['mode'] );
		if ( 'current' === $mode ) {
			$mode = 'compact';
		}
		if ( ! in_array( $mode, array( 'compact', 'full', 'forecast' ), true ) ) {
			$mode = 'full';
		}

		$days = max( 1, min( 7, intval( $atts['days'] ) ) );

		$args = array(
			'mode'          => $mode,
			'forecast_days' => $days,
			'extra_class'   => sanitize_html_class( $atts['class'] ),
		);

		if ( ! empty( $atts['max_width'] ) ) {
			$args['max_width'] = sanitize_text_field( $atts['max_width'] );
		}

		if ( ! empty( $atts['title'] ) ) {
			$args['title'] = sanitize_text_field( $atts['title'] );
		}

		// CTA override if explicitly supplied.
		if ( '' !== $atts['show_cta'] ) {
			$args['show_cta'] = in_array( strtolower( $atts['show_cta'] ), array( 'yes', 'true', '1' ), true );
		}

		if ( ! empty( $atts['cta_text'] ) ) {
			$args['cta_text'] = sanitize_text_field( $atts['cta_text'] );
		}

		if ( ! empty( $atts['cta_url'] ) ) {
			$args['cta_url'] = esc_url_raw( $atts['cta_url'] );
		}

		if ( '' !== $atts['show_meta'] ) {
			$args['show_updated_time'] = in_array( strtolower( $atts['show_meta'] ), array( 'yes', 'true', '1' ), true );
		}

		return AB_Weather_Renderer::render( $args );
	}
}
