<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Utils;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

/**
 * AB Popup Widget
 *
 * Elementor widget for configurable popups:
 * - Types   : Modal, Notification/Banner, Exit-Intent
 * - Triggers: Page Load (delay), Element Click, Scroll percentage
 * - Content : Text, Image, CTA Button, Custom HTML
 */
class AB_Popup_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_popup';
	}

	public function get_title() {
		return esc_html__( 'AB Popup', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-lightbox';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'popup', 'modal', 'notification', 'exit intent', 'overlay', 'banner', 'ab' );
	}

	public function get_style_depends() {
		return array( 'ab-popup-style' );
	}

	public function get_script_depends() {
		return array( 'ab-popup-script' );
	}

	// =========================================================================
	// CONTROLS
	// =========================================================================
	protected function register_controls() {

		// -----------------------------------------------------------------
		// SECTION: Popup Type & Trigger
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_popup_settings',
			array(
				'label' => esc_html__( 'Popup Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'preview_in_editor',
			array(
				'label'        => esc_html__( 'Preview in Editor', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Show the live popup directly in the Elementor canvas for styling.', 'ab-addon' ),
			)
		);

		$this->add_control(
			'popup_type',
			array(
				'label'   => esc_html__( 'Popup Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modal',
				'options' => array(
					'modal'        => esc_html__( 'Modal (Centered Overlay)', 'ab-addon' ),
					'notification' => esc_html__( 'Notification / Banner', 'ab-addon' ),
					'exit_intent'  => esc_html__( 'Exit Intent', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'notification_position',
			array(
				'label'     => esc_html__( 'Banner Position', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'bottom-right',
				'options'   => array(
					'top-left'      => esc_html__( 'Top Left', 'ab-addon' ),
					'top-center'    => esc_html__( 'Top Center', 'ab-addon' ),
					'top-right'     => esc_html__( 'Top Right', 'ab-addon' ),
					'bottom-left'   => esc_html__( 'Bottom Left', 'ab-addon' ),
					'bottom-center' => esc_html__( 'Bottom Center', 'ab-addon' ),
					'bottom-right'  => esc_html__( 'Bottom Right', 'ab-addon' ),
				),
				'condition' => array(
					'popup_type' => 'notification',
				),
			)
		);

		$this->add_control(
			'trigger_type',
			array(
				'label'     => esc_html__( 'Trigger', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'page_load',
				'options'   => array(
					'page_load' => esc_html__( 'Page Load', 'ab-addon' ),
					'click'     => esc_html__( 'Button / Element Click', 'ab-addon' ),
					'scroll'    => esc_html__( 'Scroll Percentage', 'ab-addon' ),
				),
				'condition' => array(
					'popup_type!' => 'exit_intent',
				),
			)
		);

		$this->add_control(
			'trigger_delay',
			array(
				'label'      => esc_html__( 'Delay (seconds)', 'ab-addon' ),
				'type'       => Controls_Manager::NUMBER,
				'default'    => 2,
				'min'        => 0,
				'max'        => 60,
				'step'       => 0.5,
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'trigger_type',
							'operator' => '==',
							'value'    => 'page_load',
						),
						array(
							'name'     => 'popup_type',
							'operator' => '==',
							'value'    => 'exit_intent',
						),
					),
				),
			)
		);

		$this->add_control(
			'trigger_selector',
			array(
				'label'       => esc_html__( 'CSS Selector to Click', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '#open-popup',
				'placeholder' => '#my-button, .open-popup',
				'description' => esc_html__( 'CSS selector of the element that will open the popup on click.', 'ab-addon' ),
				'condition'   => array(
					'trigger_type' => 'click',
				),
			)
		);

		$this->add_control(
			'trigger_scroll_percent',
			array(
				'label'     => esc_html__( 'Scroll Percentage (%)', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 50 ),
				'range'     => array(
					'%' => array(
						'min' => 5,
						'max' => 100,
					),
				),
				'condition' => array(
					'trigger_type' => 'scroll',
				),
			)
		);

		$this->add_control(
			'show_once',
			array(
				'label'        => esc_html__( 'Show Only Once Per Session', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// SECTION: Content
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_popup_content',
			array(
				'label' => esc_html__( 'Popup Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'content_type',
			array(
				'label'   => esc_html__( 'Content Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'text_image',
				'options' => array(
					'text_only'   => esc_html__( 'Text Only', 'ab-addon' ),
					'text_image'  => esc_html__( 'Text & Image', 'ab-addon' ),
					'text_cta'    => esc_html__( 'Text, Image & Button', 'ab-addon' ),
					'shortcode'   => esc_html__( 'Shortcode', 'ab-addon' ),
					'custom_html' => esc_html__( 'Custom HTML', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'popup_shortcode',
			array(
				'label'       => esc_html__( 'Shortcode', 'ab-addon' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => '[contact-form-7 id="123" title="Contact form"]',
				'default'     => '',
				'rows'        => 3,
				'description' => esc_html__( 'Enter any WordPress shortcode (e.g. form, booking, etc.).', 'ab-addon' ),
				'condition'   => array(
					'content_type' => 'shortcode',
				),
			)
		);

		$this->add_control(
			'popup_title',
			array(
				'label'       => esc_html__( 'Title', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Welcome!', 'ab-addon' ),
				'label_block' => true,
				'condition'   => array(
					'content_type' => array( 'text_only', 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_control(
			'popup_description',
			array(
				'label'     => esc_html__( 'Description', 'ab-addon' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => esc_html__( 'This is your popup message. Customize it as you like.', 'ab-addon' ),
				'rows'      => 4,
				'condition' => array(
					'content_type' => array( 'text_only', 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_control(
			'popup_image',
			array(
				'label'     => esc_html__( 'Image', 'ab-addon' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array( 'url' => Utils::get_placeholder_image_src() ),
				'condition' => array(
					'content_type' => array( 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_control(
			'image_position',
			array(
				'label'     => esc_html__( 'Image Position', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'top',
				'options'   => array(
					'top'   => esc_html__( 'Top', 'ab-addon' ),
					'left'  => esc_html__( 'Left', 'ab-addon' ),
					'right' => esc_html__( 'Right', 'ab-addon' ),
				),
				'condition' => array(
					'popup_type'   => 'modal',
					'content_type' => array( 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_control(
			'cta_text',
			array(
				'label'     => esc_html__( 'Button Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Learn More', 'ab-addon' ),
				'condition' => array(
					'content_type' => 'text_cta',
				),
			)
		);

		$this->add_control(
			'cta_url',
			array(
				'label'         => esc_html__( 'Button URL', 'ab-addon' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => 'https://your-link.com',
				'show_external' => true,
				'default'       => array(
					'url'         => '#',
					'is_external' => false,
					'nofollow'    => false,
				),
				'condition'     => array(
					'content_type' => 'text_cta',
				),
			)
		);

		$this->add_control(
			'custom_html',
			array(
				'label'     => esc_html__( 'Custom HTML', 'ab-addon' ),
				'type'      => Controls_Manager::CODE,
				'language'  => 'html',
				'default'   => '<p>Enter your <strong>custom HTML</strong> here.</p>',
				'condition' => array(
					'content_type' => 'custom_html',
				),
			)
		);

		$this->add_control(
			'show_close_button',
			array(
				'label'        => esc_html__( 'Show Close Button', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'close_on_overlay',
			array(
				'label'        => esc_html__( 'Close on Overlay Click', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'popup_type' => array( 'modal', 'exit_intent' ),
				),
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Overlay
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_overlay',
			array(
				'label'     => esc_html__( 'Overlay', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'popup_type' => array( 'modal', 'exit_intent' ),
				),
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'     => esc_html__( 'Overlay Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.65)',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-overlay' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Popup Box
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_popup',
			array(
				'label' => esc_html__( 'Popup Box', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'popup_background',
				'label'    => esc_html__( 'Background', 'ab-addon' ),
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .ab-popup-box',
				'fields_options' => array(
					'background' => array( 'default' => 'classic' ),
					'color'      => array( 'default' => '#ffffff' ),
				),
			)
		);

		$this->add_responsive_control(
			'popup_width',
			array(
				'label'      => esc_html__( 'Width', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 200, 'max' => 1400 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'vw' => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array( 'size' => 560, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-box' => 'max-width: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'popup_type' => array( 'modal', 'exit_intent' ),
				),
			)
		);

		$this->add_responsive_control(
			'popup_padding',
			array(
				'label'      => esc_html__( 'Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'    => '36',
					'right'  => '36',
					'bottom' => '36',
					'left'   => '36',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'popup_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'    => '16',
					'right'  => '16',
					'bottom' => '16',
					'left'   => '16',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'popup_box_shadow',
				'selector' => '{{WRAPPER}} .ab-popup-box',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'popup_border',
				'selector' => '{{WRAPPER}} .ab-popup-box',
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Title
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_title',
			array(
				'label'     => esc_html__( 'Title', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'content_type' => array( 'text_only', 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-popup-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a2e',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'title_margin',
			array(
				'label'      => esc_html__( 'Margin', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Description
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_description',
			array(
				'label'     => esc_html__( 'Description', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'content_type' => array( 'text_only', 'text_image', 'text_cta' ),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .ab-popup-description',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#555555',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-description' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Button / CTA
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_button',
			array(
				'label'     => esc_html__( 'Button', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'content_type' => 'text_cta',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typography',
				'selector' => '{{WRAPPER}} .ab-popup-cta',
			)
		);

		$this->start_controls_tabs( 'btn_style_tabs' );

		$this->start_controls_tab(
			'btn_tab_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-cta' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-cta' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'btn_tab_hover',
			array( 'label' => esc_html__( 'Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'btn_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-cta:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#574fd6',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-cta:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'    => '12',
					'right'  => '28',
					'bottom' => '12',
					'left'   => '28',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'    => '8',
					'right'  => '8',
					'bottom' => '8',
					'left'   => '8',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-popup-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// -----------------------------------------------------------------
		// STYLE: Close Button
		// -----------------------------------------------------------------
		$this->start_controls_section(
			'section_style_close',
			array(
				'label'     => esc_html__( 'Close Button', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_close_button' => 'yes',
				),
			)
		);

		$this->add_control(
			'close_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#888888',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-close' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'close_hover_color',
			array(
				'label'     => esc_html__( 'Hover Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#222222',
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-close:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'close_size',
			array(
				'label'     => esc_html__( 'Icon Size (px)', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 18 ),
				'range'     => array(
					'px' => array( 'min' => 10, 'max' => 48 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-popup-close' => 'font-size: {{SIZE}}px;',
				),
			)
		);

		$this->end_controls_section();
	}

	// =========================================================================
	// RENDER
	// =========================================================================
	protected function render() {
		$settings = $this->get_settings_for_display();

		$popup_id         = 'ab-popup-' . $this->get_id();
		$popup_type       = $settings['popup_type'];
		$trigger_type     = ( $popup_type === 'exit_intent' ) ? 'exit_intent' : ( $settings['trigger_type'] ?? 'page_load' );
		$content_type     = $settings['content_type'];
		$show_close       = isset( $settings['show_close_button'] ) && $settings['show_close_button'] === 'yes';
		$close_on_overlay = isset( $settings['close_on_overlay'] ) && $settings['close_on_overlay'] === 'yes';
		$show_once        = isset( $settings['show_once'] ) && $settings['show_once'] === 'yes';
		$preview_editor   = isset( $settings['preview_in_editor'] ) && $settings['preview_in_editor'] === 'yes';
		$is_editor        = \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();

		$notif_position = '';
		if ( $popup_type === 'notification' && ! empty( $settings['notification_position'] ) ) {
			$notif_position = 'ab-popup-notif--' . esc_attr( $settings['notification_position'] );
		}

		$scroll_percent = isset( $settings['trigger_scroll_percent']['size'] ) ? (float) $settings['trigger_scroll_percent']['size'] : 50;

		$image_pos_class = '';
		if ( in_array( $popup_type, array( 'modal', 'exit_intent' ), true ) && in_array( $content_type, array( 'text_image', 'text_cta' ), true ) ) {
			$pos = $settings['image_position'] ?? 'top';
			$image_pos_class = 'ab-popup-layout--' . esc_attr( $pos );
		}

		// In Elementor editor: render live preview directly in the canvas
		if ( $is_editor ) {
			if ( $preview_editor ) {
				?>
				<div class="ab-popup-editor-preview">
					<div class="ab-popup-editor-badge">
						<span>👁️ <strong><?php esc_html_e( 'AB Popup Live Preview', 'ab-addon' ); ?></strong> &mdash; <?php echo esc_html( ucfirst( str_replace( '_', ' ', $popup_type ) ) ); ?></span>
						<span class="ab-popup-badge-hint"><?php esc_html_e( 'Turn off "Preview in Editor" to collapse', 'ab-addon' ); ?></span>
					</div>
					<div class="ab-popup-box <?php echo esc_attr( $image_pos_class ); ?>" role="document">
						<?php if ( $show_close ) : ?>
						<button class="ab-popup-close" aria-label="<?php esc_attr_e( 'Close popup', 'ab-addon' ); ?>">&#x2715;</button>
						<?php endif; ?>
						<?php $this->render_popup_content( $settings, $content_type ); ?>
					</div>
				</div>
				<?php
			} else {
				$this->render_editor_notice( $popup_type );
			}
			return;
		}

		// Frontend output: trigger wrapper and hidden popup modal / notification
		$data_attrs = sprintf(
			'data-popup-id="%s" data-popup-type="%s" data-trigger="%s" data-delay="%s" data-selector="%s" data-scroll="%s" data-show-once="%s" data-close-overlay="%s"',
			esc_attr( $popup_id ),
			esc_attr( $popup_type ),
			esc_attr( $trigger_type ),
			esc_attr( $settings['trigger_delay'] ?? 2 ),
			esc_attr( $settings['trigger_selector'] ?? '' ),
			esc_attr( $scroll_percent ),
			esc_attr( $show_once ? 'yes' : 'no' ),
			esc_attr( $close_on_overlay ? 'yes' : 'no' )
		);
		?>

		<div class="ab-popup-trigger-wrapper" id="<?php echo esc_attr( $popup_id . '-wrapper' ); ?>" <?php echo $data_attrs; // phpcs:ignore ?>>

			<?php if ( in_array( $popup_type, array( 'modal', 'exit_intent' ), true ) ) : ?>

			<div class="ab-popup-overlay<?php echo $popup_type === 'exit_intent' ? ' ab-popup-exit-intent' : ''; ?>"
			     id="<?php echo esc_attr( $popup_id ); ?>"
			     role="dialog"
			     aria-modal="true"
			     aria-hidden="true"
			     style="display:none;">
				<div class="ab-popup-box <?php echo esc_attr( $image_pos_class ); ?>" role="document">
					<?php if ( $show_close ) : ?>
					<button class="ab-popup-close" aria-label="<?php esc_attr_e( 'Close popup', 'ab-addon' ); ?>">&#x2715;</button>
					<?php endif; ?>
					<?php $this->render_popup_content( $settings, $content_type ); ?>
				</div>
			</div>

			<?php elseif ( $popup_type === 'notification' ) : ?>

			<div class="ab-popup-notification <?php echo esc_attr( $notif_position ); ?>"
			     id="<?php echo esc_attr( $popup_id ); ?>"
			     role="alert"
			     aria-hidden="true"
			     style="display:none;">
				<div class="ab-popup-box">
					<?php if ( $show_close ) : ?>
					<button class="ab-popup-close" aria-label="<?php esc_attr_e( 'Dismiss', 'ab-addon' ); ?>">&#x2715;</button>
					<?php endif; ?>
					<?php $this->render_popup_content( $settings, $content_type ); ?>
				</div>
			</div>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Renders a visible editor placeholder when preview is toggled off in the Elementor canvas.
	 */
	protected function render_editor_notice( $popup_type ) {
		$labels = array(
			'modal'        => '🪟 Modal Popup',
			'notification' => '🔔 Notification Banner',
			'exit_intent'  => '🚪 Exit Intent Popup',
		);
		$label = isset( $labels[ $popup_type ] ) ? $labels[ $popup_type ] : '🪟 Popup';
		echo '<div style="display:flex;align-items:center;gap:10px;padding:14px 18px;background:#f0f0ff;border:2px dashed #6c63ff;border-radius:10px;font-family:sans-serif;">';
		echo '<span style="font-size:22px;">🪟</span>';
		echo '<div><strong style="color:#6c63ff;font-size:13px;">' . esc_html( $label ) . ' &mdash; ' . esc_html__( 'Preview Hidden', 'ab-addon' ) . '</strong><br>';
		echo '<span style="color:#888;font-size:11px;">' . esc_html__( 'Switch "Preview in Editor" to YES in widget settings to design.', 'ab-addon' ) . '</span></div>';
		echo '</div>';
	}

	/**
	 * Renders the inner content of the popup box.
	 *
	 * @param array  $settings     Widget settings.
	 * @param string $content_type Selected content type.
	 */
	protected function render_popup_content( $settings, $content_type ) {
		if ( $content_type === 'shortcode' ) {
			$shortcode = $settings['popup_shortcode'] ?? '';
			if ( ! empty( $shortcode ) ) {
				echo '<div class="ab-popup-shortcode-wrapper">';
				echo do_shortcode( $shortcode );
				echo '</div>';
			} else {
				echo '<div class="ab-popup-shortcode-placeholder">';
				echo esc_html__( 'Please enter your shortcode in the widget settings.', 'ab-addon' );
				echo '</div>';
			}
			return;
		}

		if ( $content_type === 'custom_html' ) {
			$custom_html = $settings['custom_html'] ?? '';
			echo '<div class="ab-popup-custom-html-wrapper">';
			echo do_shortcode( wp_kses_post( $custom_html ) );
			echo '</div>';
			return;
		}

		$has_image = in_array( $content_type, array( 'text_image', 'text_cta' ), true )
		             && ! empty( $settings['popup_image']['url'] );
		$has_cta   = $content_type === 'text_cta';
		?>

		<?php if ( $has_image ) : ?>
		<div class="ab-popup-image">
			<img src="<?php echo esc_url( $settings['popup_image']['url'] ); ?>"
			     alt="<?php echo esc_attr( $settings['popup_title'] ?? '' ); ?>" />
		</div>
		<?php endif; ?>

		<div class="ab-popup-body">
			<?php if ( ! empty( $settings['popup_title'] ) ) : ?>
			<h3 class="ab-popup-title"><?php echo wp_kses_post( $settings['popup_title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $settings['popup_description'] ) ) : ?>
			<p class="ab-popup-description"><?php echo wp_kses_post( $settings['popup_description'] ); ?></p>
			<?php endif; ?>

			<?php if ( $has_cta && ! empty( $settings['cta_text'] ) ) :
				$target   = ! empty( $settings['cta_url']['is_external'] ) ? '_blank' : '_self';
				$nofollow = ! empty( $settings['cta_url']['nofollow'] ) ? ' rel="nofollow"' : '';
				?>
			<a class="ab-popup-cta"
			   href="<?php echo esc_url( $settings['cta_url']['url'] ?? '#' ); ?>"
			   target="<?php echo esc_attr( $target ); ?>"
			   <?php echo $nofollow; // phpcs:ignore ?>>
				<?php echo esc_html( $settings['cta_text'] ); ?>
			</a>
			<?php endif; ?>
		</div>

		<?php
	}
}
