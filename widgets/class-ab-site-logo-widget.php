<?php
/**
 * AB Site Logo Elementor Widget
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

class AB_Site_Logo_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_site_logo';
	}

	public function get_title() {
		return esc_html__( 'AB Site Logo', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-site-logo';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'site logo', 'logo', 'header', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Logo Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
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
					'{{WRAPPER}} .ab-site-logo img' => 'width: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .ab-site-logo' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		$site_url       = home_url( '/' );
		$site_name      = get_bloginfo( 'name' );

		echo '<div class="ab-site-logo">';
		if ( $custom_logo_id ) {
			$logo_src = wp_get_attachment_image_src( $custom_logo_id, 'full' );
			if ( ! empty( $logo_src[0] ) ) {
				echo '<a href="' . esc_url( $site_url ) . '">';
				echo '<img src="' . esc_url( $logo_src[0] ) . '" alt="' . esc_attr( $site_name ) . '" style="height: auto; max-width: 100%; display: inline-block;">';
				echo '</a>';
			}
		} else {
			echo '<a href="' . esc_url( $site_url ) . '" style="text-decoration: none; font-size: 22px; font-weight: 800; color: #0f172a;">' . esc_html( $site_name ) . '</a>';
		}
		echo '</div>';
	}
}
