<?php
/**
 * AB Retina Logo Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Retina_Logo_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_retina_logo';
	}

	public function get_title() {
		return esc_html__( 'AB Retina Logo', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-image-bold';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'logo', 'retina logo', 'brand', 'header', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Logo Images', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'logo_default',
			array(
				'label'   => esc_html__( 'Default Logo (1x)', 'ab-addon' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				),
			)
		);

		$this->add_control(
			'logo_retina',
			array(
				'label'       => esc_html__( 'Retina Logo (2x)', 'ab-addon' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Double resolution image for high-DPI retina displays.', 'ab-addon' ),
			)
		);

		$this->add_responsive_control(
			'logo_width',
			array(
				'label'      => esc_html__( 'Width (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 20, 'max' => 500 ),
				),
				'default'    => array( 'size' => 180, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-retina-logo img' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'ab-addon' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'ab-addon' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'ab-addon' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'ab-addon' ), 'icon' => 'eicon-text-align-right' ),
				),
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .ab-retina-logo' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$logo1x   = ! empty( $settings['logo_default']['url'] ) ? $settings['logo_default']['url'] : '';
		$logo2x   = ! empty( $settings['logo_retina']['url'] ) ? $settings['logo_retina']['url'] : $logo1x;
		$site_url = home_url( '/' );
		$site_name= get_bloginfo( 'name' );

		if ( ! $logo1x ) {
			return;
		}

		echo '<div class="ab-retina-logo">';
		echo '<a href="' . esc_url( $site_url ) . '">';
		echo '<img src="' . esc_url( $logo1x ) . '" srcset="' . esc_url( $logo1x ) . ' 1x, ' . esc_url( $logo2x ) . ' 2x" alt="' . esc_attr( $site_name ) . '" style="height: auto; max-width: 100%; display: inline-block;">';
		echo '</a>';
		echo '</div>';
	}
}
