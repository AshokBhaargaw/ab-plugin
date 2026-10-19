<?php
/**
 * AB Dual Color Heading Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Dual_Color_Heading_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_dual_color_heading';
	}

	public function get_title() {
		return esc_html__( 'AB Dual Color Heading', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-heading';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'heading', 'title', 'dual color', 'gradient', 'text', 'ab' );
	}

	protected function register_controls() {

		// Content Section
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Heading Text', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'first_text',
			array(
				'label'       => esc_html__( 'First Text (Prefix)', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Discover', 'ab-addon' ),
				'placeholder' => esc_html__( 'Prefix text', 'ab-addon' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'highlight_text',
			array(
				'label'       => esc_html__( 'Highlighted Text', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Ramdevra', 'ab-addon' ),
				'placeholder' => esc_html__( 'Highlighted text', 'ab-addon' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'last_text',
			array(
				'label'       => esc_html__( 'Last Text (Suffix)', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Pilgrimage', 'ab-addon' ),
				'placeholder' => esc_html__( 'Suffix text', 'ab-addon' ),
				'dynamic'     => array( 'active' => true ),
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
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				),
				'default' => 'h2',
			)
		);

		$this->add_responsive_control(
			'align',
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
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .ab-dual-heading' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'link',
			array(
				'label'       => esc_html__( 'Link', 'ab-addon' ),
				'type'        => Controls_Manager::URL,
				'dynamic'     => array( 'active' => true ),
				'placeholder' => esc_html__( 'https://your-link.com', 'ab-addon' ),
			)
		);

		$this->end_controls_section();

		// Style: General
		$this->start_controls_section(
			'section_style_general',
			array(
				'label' => esc_html__( 'General Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'general_typography',
				'selector' => '{{WRAPPER}} .ab-dual-heading',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'text_shadow',
				'selector' => '{{WRAPPER}} .ab-dual-heading',
			)
		);

		$this->end_controls_section();

		// Style: Normal Text
		$this->start_controls_section(
			'section_style_normal',
			array(
				'label' => esc_html__( 'Prefix & Suffix Text', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'normal_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e293b',
				'selectors' => array(
					'{{WRAPPER}} .ab-dual-heading .ab-text-normal' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style: Highlighted Text
		$this->start_controls_section(
			'section_style_highlight',
			array(
				'label' => esc_html__( 'Highlighted Text', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'highlight_color',
			array(
				'label'     => esc_html__( 'Highlight Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-dual-heading .ab-text-highlight' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'use_gradient',
			array(
				'label'        => esc_html__( 'Use Gradient Effect', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'gradient_color',
			array(
				'label'     => esc_html__( 'Gradient Second Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f97316',
				'condition' => array(
					'use_gradient' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-dual-heading .ab-text-highlight.ab-has-gradient' => 'background: linear-gradient(135deg, {{highlight_color.VALUE}}, {{VALUE}}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'highlight_typography',
				'selector' => '{{WRAPPER}} .ab-dual-heading .ab-text-highlight',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$tag       = ! empty( $settings['html_tag'] ) ? esc_attr( $settings['html_tag'] ) : 'h2';
		$first     = esc_html( $settings['first_text'] ?? '' );
		$highlight = esc_html( $settings['highlight_text'] ?? '' );
		$last      = esc_html( $settings['last_text'] ?? '' );
		$is_grad   = ( 'yes' === ( $settings['use_gradient'] ?? '' ) ) ? ' ab-has-gradient' : '';

		$has_link = ! empty( $settings['link']['url'] );
		if ( $has_link ) {
			$this->add_link_attributes( 'heading_link', $settings['link'] );
		}

		echo '<div class="ab-dual-heading-wrap">';
		if ( $has_link ) {
			echo '<a ' . $this->get_render_attribute_string( 'heading_link' ) . ' style="text-decoration: none; color: inherit;">';
		}
		echo '<' . $tag . ' class="ab-dual-heading">';
		if ( $first ) {
			echo '<span class="ab-text-normal ab-text-first">' . $first . ' </span>';
		}
		if ( $highlight ) {
			echo '<span class="ab-text-highlight' . esc_attr( $is_grad ) . '">' . $highlight . '</span>';
		}
		if ( $last ) {
			echo '<span class="ab-text-normal ab-text-last"> ' . $last . '</span>';
		}
		echo '</' . $tag . '>';
		if ( $has_link ) {
			echo '</a>';
		}
		echo '</div>';
	}
}
