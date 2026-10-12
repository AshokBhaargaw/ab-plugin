<?php
/**
 * AB Weather Elementor Widget
 *
 * Provides a responsive, customizable Ramdevra Weather Card widget in Elementor Free.
 * Allows administrators to switch between compact and full forecast layouts,
 * customize field visibility, card styles, and taxi booking callout.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-renderer.php';

class AB_Weather_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_weather';
	}

	public function get_title() {
		return esc_html__( 'AB Weather — Ramdevra Weather', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-cloud-sun';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'weather', 'ramdevra', 'forecast', 'temperature', 'climate', 'taxi', 'travel', 'ab' );
	}

	public function get_style_depends() {
		return array( 'ab-weather-style' );
	}

	protected function register_controls() {

		// ==========================================
		// SECTION: LAYOUT & CONTENT
		// ==========================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout & Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => esc_html__( 'Display Mode', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'full',
				'options' => array(
					'full'     => esc_html__( 'Full Station & Forecast', 'ab-addon' ),
					'compact'  => esc_html__( 'Compact Current Weather', 'ab-addon' ),
					'forecast' => esc_html__( 'Forecast Only', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Card Title', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Ramdevra, Rajasthan, India', 'ab-addon' ),
				'description' => esc_html__( 'Leave empty to use the default configured location name.', 'ab-addon' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'forecast_days',
			array(
				'label'     => esc_html__( 'Forecast Days', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 5 ),
				'range'     => array(
					'px' => array(
						'min'  => 1,
						'max'  => 7,
						'step' => 1,
					),
				),
				'condition' => array(
					'mode!' => 'compact',
				),
			)
		);

		$this->add_responsive_control(
			'card_align',
			array(
				'label'     => esc_html__( 'Alignment', 'ab-addon' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'ab-addon' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'ab-addon' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'ab-addon' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors_dictionary' => array(
					'left'   => 'margin-left: 0; margin-right: auto;',
					'center' => 'margin-left: auto; margin-right: auto;',
					'right'  => 'margin-left: auto; margin-right: 0;',
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-weather-container' => '{{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: FIELD VISIBILITY
		// ==========================================
		$this->start_controls_section(
			'section_fields',
			array(
				'label' => esc_html__( 'Field Visibility', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_feels_like',
			array(
				'label'        => esc_html__( 'Feels-Like Temperature', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_high_low',
			array(
				'label'        => esc_html__( 'Today High / Low', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_humidity',
			array(
				'label'        => esc_html__( 'Humidity', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'mode' => 'full',
				),
			)
		);

		$this->add_control(
			'show_wind',
			array(
				'label'        => esc_html__( 'Wind Speed', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'mode' => 'full',
				),
			)
		);

		$this->add_control(
			'show_rain_prob',
			array(
				'label'        => esc_html__( 'Rain Chance', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_sun_times',
			array(
				'label'        => esc_html__( 'Sunrise & Sunset Times', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'mode' => 'full',
				),
			)
		);

		$this->add_control(
			'show_updated_time',
			array(
				'label'        => esc_html__( 'Last Updated Time (IST)', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: TAXI CALLOUT (CTA)
		// ==========================================
		$this->start_controls_section(
			'section_cta',
			array(
				'label' => esc_html__( 'Travel Taxi CTA', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_cta',
			array(
				'label'        => esc_html__( 'Show Taxi Callout Banner', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'       => esc_html__( 'Callout Text', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Planning a trip to Ramdevra? Book your taxi.', 'ab-addon' ),
				'label_block' => true,
				'condition'   => array(
					'show_cta' => 'yes',
				),
			)
		);

		$this->add_control(
			'cta_url',
			array(
				'label'       => esc_html__( 'Booking URL', 'ab-addon' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://ramdevraa.in/taxi-booking/',
				'default'     => array(
					'url'         => 'https://ramdevraa.in/taxi-booking/',
					'is_external' => true,
				),
				'condition'   => array(
					'show_cta' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: STYLE - CARD
		// ==========================================
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card Container', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_max_width',
			array(
				'label'      => esc_html__( 'Max Width', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array(
						'min'  => 320,
						'max'  => 1400,
						'step' => 10,
					),
					'%'  => array(
						'min'  => 20,
						'max'  => 100,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 880,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-weather-container' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-weather-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_text_color',
			array(
				'label'     => esc_html__( 'Primary Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e293b',
				'selectors' => array(
					'{{WRAPPER}} .ab-weather-container' => '--ab-weather-text-primary: {{VALUE}};',
					'{{WRAPPER}} .ab-weather-title'     => 'color: {{VALUE}};',
					'{{WRAPPER}} .ab-weather-temp-num'  => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => 16,
					'right'    => 16,
					'bottom'   => 16,
					'left'     => 16,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-weather-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .ab-weather-card',
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'      => 24,
					'right'    => 24,
					'bottom'   => 24,
					'left'     => 24,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-weather-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: STYLE - ACCENTS & CTA
		// ==========================================
		$this->start_controls_section(
			'section_style_accents',
			array(
				'label' => esc_html__( 'Accents & Button', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => esc_html__( 'Accent Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0284c7',
				'selectors' => array(
					'{{WRAPPER}} .ab-weather-container' => '--ab-weather-accent: {{VALUE}};',
					'{{WRAPPER}} .ab-weather-loc-icon'  => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'cta_btn_color',
			array(
				'label'     => esc_html__( 'Taxi Button Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ea580c',
				'selectors' => array(
					'{{WRAPPER}} .ab-weather-cta-btn' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$cta_url = ! empty( $settings['cta_url']['url'] ) ? $settings['cta_url']['url'] : 'https://ramdevraa.in/taxi-booking/';

		$args = array(
			'mode'              => $settings['mode'] ?? 'full',
			'title'             => $settings['title'] ?? '',
			'forecast_days'     => isset( $settings['forecast_days']['size'] ) ? intval( $settings['forecast_days']['size'] ) : 5,
			'show_feels_like'   => 'yes' === ( $settings['show_feels_like'] ?? 'yes' ),
			'show_high_low'     => 'yes' === ( $settings['show_high_low'] ?? 'yes' ),
			'show_humidity'     => 'yes' === ( $settings['show_humidity'] ?? 'yes' ),
			'show_wind'         => 'yes' === ( $settings['show_wind'] ?? 'yes' ),
			'show_rain_prob'    => 'yes' === ( $settings['show_rain_prob'] ?? 'yes' ),
			'show_sun_times'    => 'yes' === ( $settings['show_sun_times'] ?? 'yes' ),
			'show_updated_time' => 'yes' === ( $settings['show_updated_time'] ?? 'yes' ),
			'show_cta'          => 'yes' === ( $settings['show_cta'] ?? 'yes' ),
			'cta_text'          => $settings['cta_text'] ?? '',
			'cta_url'           => $cta_url,
		);

		echo AB_Weather_Renderer::render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
