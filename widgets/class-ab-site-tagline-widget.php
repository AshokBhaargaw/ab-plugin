<?php
/**
 * AB Site Tagline Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Site_Tagline_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_site_tagline';
	}

	public function get_title() {
		return esc_html__( 'AB Site Tagline', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-site-description';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'site tagline', 'description', 'header', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Site Tagline', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'custom_tagline',
			array(
				'label'       => esc_html__( 'Custom Tagline (Leave empty for WordPress Site Tagline)', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_bloginfo( 'description' ),
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
					'{{WRAPPER}} .ab-site-tagline' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-site-tagline' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tagline_typography',
				'selector' => '{{WRAPPER}} .ab-site-tagline',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$tagline  = ! empty( $settings['custom_tagline'] ) ? $settings['custom_tagline'] : get_bloginfo( 'description' );

		echo '<div class="ab-site-tagline" style="font-size: 14px; line-height: 1.5;">' . esc_html( $tagline ) . '</div>';
	}
}
