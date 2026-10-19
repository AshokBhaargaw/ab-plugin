<?php
/**
 * AB Advanced Heading Elementor Widget
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

class AB_Advanced_Heading_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_advanced_heading';
	}

	public function get_title() {
		return esc_html__( 'AB Advanced Heading', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-t-letter';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'advanced heading', 'title', 'subtitle', 'separator', 'description', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Heading Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'       => esc_html__( 'Sub Title / Eyebrow', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'WELCOME TO RAMDEVRA', 'ab-addon' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Main Title', 'ab-addon' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'Experience Divine Peace & Sacred Heritage', 'ab-addon' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_separator',
			array(
				'label'        => esc_html__( 'Show Separator Line', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'description',
			array(
				'label'       => esc_html__( 'Description', 'ab-addon' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => esc_html__( 'Plan your sacred pilgrimage with verified dharamsala bookings, local guides, and live weather updates.', 'ab-addon' ),
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
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .ab-adv-heading' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .ab-adv-sep'     => 'margin-left: {{VALUE}} === "center" ? auto : ({{VALUE}} === "right" ? auto : 0); margin-right: {{VALUE}} === "center" ? auto : ({{VALUE}} === "left" ? auto : 0);',
				),
			)
		);

		$this->end_controls_section();

		// Style: Subtitle
		$this->start_controls_section(
			'section_style_subtitle',
			array(
				'label' => esc_html__( 'Sub Title Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'subtitle_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-adv-subtitle' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'selector' => '{{WRAPPER}} .ab-adv-subtitle',
			)
		);

		$this->end_controls_section();

		// Style: Main Title
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Main Title Styling', 'ab-addon' ),
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
					'{{WRAPPER}} .ab-adv-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-adv-title',
			)
		);

		$this->end_controls_section();

		// Style: Separator
		$this->start_controls_section(
			'section_style_separator',
			array(
				'label'     => esc_html__( 'Separator Styling', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_separator' => 'yes' ),
			)
		);

		$this->add_control(
			'sep_color',
			array(
				'label'     => esc_html__( 'Line Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f97316',
				'selectors' => array(
					'{{WRAPPER}} .ab-adv-sep' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'sep_width',
			array(
				'label'      => esc_html__( 'Width (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 20, 'max' => 300 ),
				),
				'default'    => array( 'size' => 60, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-adv-sep' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'sep_height',
			array(
				'label'      => esc_html__( 'Height / Thickness (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 1, 'max' => 10 ),
				),
				'default'    => array( 'size' => 3, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-adv-sep' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Style: Description
		$this->start_controls_section(
			'section_style_description',
			array(
				'label' => esc_html__( 'Description Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-adv-desc' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .ab-adv-desc',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		echo '<div class="ab-adv-heading" style="margin: 0 0 20px 0;">';
		if ( ! empty( $settings['subtitle'] ) ) {
			echo '<div class="ab-adv-subtitle" style="font-size: 13px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 8px;">' . esc_html( $settings['subtitle'] ) . '</div>';
		}
		if ( ! empty( $settings['title'] ) ) {
			echo '<h2 class="ab-adv-title" style="margin: 0 0 12px 0; font-weight: 800; line-height: 1.25;">' . esc_html( $settings['title'] ) . '</h2>';
		}
		if ( 'yes' === ( $settings['show_separator'] ?? '' ) ) {
			echo '<div class="ab-adv-sep" style="border-radius: 4px; margin: 12px auto 16px auto;"></div>';
		}
		if ( ! empty( $settings['description'] ) ) {
			echo '<div class="ab-adv-desc" style="font-size: 15px; line-height: 1.6; max-width: 720px; margin: 0 auto;">' . wp_kses_post( $settings['description'] ) . '</div>';
		}
		echo '</div>';
	}
}
