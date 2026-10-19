<?php
/**
 * AB Site Title Elementor Widget
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

class AB_Site_Title_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_site_title';
	}

	public function get_title() {
		return esc_html__( 'AB Site Title', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-site-title';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'site title', 'logo', 'header', 'name', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Site Title', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'custom_title',
			array(
				'label'       => esc_html__( 'Custom Title (Leave empty for WordPress Site Title)', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_bloginfo( 'name' ),
			)
		);

		$this->add_control(
			'html_tag',
			array(
				'label'   => esc_html__( 'HTML Tag', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'span' => 'span',
					'div'  => 'div',
					'p'    => 'p',
				),
				'default' => 'h1',
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
					'{{WRAPPER}} .ab-site-title-wrap' => 'text-align: {{VALUE}};',
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
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0f172a',
				'selectors' => array(
					'{{WRAPPER}} .ab-site-title-wrap a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-site-title-wrap',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$title    = ! empty( $settings['custom_title'] ) ? $settings['custom_title'] : get_bloginfo( 'name' );
		$tag      = ! empty( $settings['html_tag'] ) ? esc_attr( $settings['html_tag'] ) : 'h1';

		echo '<div class="ab-site-title-wrap">';
		echo '<' . $tag . ' class="ab-site-title" style="margin: 0; line-height: 1.2;">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '" style="text-decoration: none; color: inherit;">' . esc_html( $title ) . '</a>';
		echo '</' . $tag . '>';
		echo '</div>';
	}
}
