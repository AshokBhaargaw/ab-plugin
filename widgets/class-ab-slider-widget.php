<?php
/**
 * AB Slider Elementor Widget
 *
 * Provides a flexible, high-performance Hero Banner Slider widget in Elementor.
 * Allows using either a pre-built slider from AB Settings > Sliders or creating
 * custom repeater slides directly inside Elementor.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

require_once AB_ADDON_PATH . 'includes/slider/class-ab-slider-renderer.php';

class AB_Slider_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_slider';
	}

	public function get_title() {
		return esc_html__( 'AB Hero Slider', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-slides';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'slider', 'slideshow', 'hero', 'banner', 'carousel', 'smart slider', 'ab' );
	}

	public function get_style_depends() {
		return array( 'swiper', 'e-swiper', 'ab-slider-style' );
	}

	public function get_script_depends() {
		return array( 'swiper', 'ab-slider-script' );
	}

	protected function register_controls() {

		// ==========================================
		// SECTION: SLIDER SOURCE
		// ==========================================
		$this->start_controls_section(
			'section_source',
			array(
				'label' => esc_html__( 'Slider Source', 'ab-addon' ),
			)
		);

		$this->add_control(
			'source_type',
			array(
				'label'   => esc_html__( 'Source Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => array(
					'custom' => esc_html__( 'Visual Slides (Direct in Elementor)', 'ab-addon' ),
					'saved'  => esc_html__( 'Saved Slider (from AB Settings)', 'ab-addon' ),
				),
				'description' => esc_html__( 'With "Visual Slides", you can edit images, text, and styles directly with live visual preview.', 'ab-addon' ),
			)
		);

		// Saved Sliders Dropdown
		$saved_sliders = AB_Slider_Renderer::get_sliders();
		$slider_options = array();
		foreach ( $saved_sliders as $id => $s ) {
			$slider_options[ $id ] = ! empty( $s['name'] ) ? $s['name'] : $id;
		}

		$this->add_control(
			'saved_slider_id',
			array(
				'label'       => esc_html__( 'Select Slider', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $slider_options,
				'default'     => ! empty( $slider_options ) ? array_key_first( $slider_options ) : '',
				'condition'   => array(
					'source_type' => 'saved',
				),
				'description' => esc_html__( 'Manage and add sliders in WordPress Admin > AB Settings > Sliders.', 'ab-addon' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: CUSTOM SLIDES REPEATER
		// ==========================================
		$this->start_controls_section(
			'section_slides',
			array(
				'label'     => esc_html__( 'Slides', 'ab-addon' ),
				'condition' => array(
					'source_type' => 'custom',
				),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'slide_content_type',
			array(
				'label'   => esc_html__( 'Slide Content Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'content',
				'options' => array(
					'content'  => esc_html__( 'Hero Content (Heading, Text, Buttons)', 'ab-addon' ),
					'template' => esc_html__( 'Elementor Template / Container (Any Widgets)', 'ab-addon' ),
				),
			)
		);

		$repeater->add_control(
			'template_id',
			array(
				'label'       => esc_html__( 'Select Elementor Template', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_elementor_templates_list(),
				'condition'   => array(
					'slide_content_type' => 'template',
				),
				'description' => esc_html__( 'Design any Container or Section with any widgets in Templates > Saved Templates, and select it here.', 'ab-addon' ),
			)
		);

		$repeater->add_control(
			'bg_image',
			array(
				'label'   => esc_html__( 'Desktop Background Image', 'ab-addon' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => '',
				),
			)
		);

		$repeater->add_control(
			'bg_mobile',
			array(
				'label'       => esc_html__( 'Mobile Background (Optional)', 'ab-addon' ),
				'type'        => Controls_Manager::MEDIA,
				'description' => esc_html__( 'Leave empty to automatically use desktop image.', 'ab-addon' ),
			)
		);

		$repeater->add_control(
			'overlay_color',
			array(
				'label'   => esc_html__( 'Overlay Color', 'ab-addon' ),
				'type'    => Controls_Manager::COLOR,
				'default' => 'rgba(15, 23, 42, 0.55)',
			)
		);

		$repeater->add_control(
			'badge',
			array(
				'label'       => esc_html__( 'Eyebrow / Badge Text', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'पवित्र तीर्थ स्थल', 'ab-addon' ),
				'label_block' => true,
				'condition'   => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title / Heading', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'श्री रामदेवरा धाम', 'ab-addon' ),
				'label_block' => true,
				'condition'   => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'     => esc_html__( 'Description', 'ab-addon' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => esc_html__( 'रामदेवरा (रुणिचा) में आपका स्वागत है। बाबा के दर्शन एवं संपूर्ण जानकारी।', 'ab-addon' ),
				'rows'      => 3,
				'condition' => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_primary_heading',
			array(
				'label'     => esc_html__( 'Primary Button', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_primary_text',
			array(
				'label'     => esc_html__( 'Button 1 Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'आरती समय देखें', 'ab-addon' ),
				'condition' => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_primary_url',
			array(
				'label'         => esc_html__( 'Button 1 URL', 'ab-addon' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => 'https://ramdevraa.in',
				'show_external' => true,
				'default'       => array(
					'url'         => '#',
					'is_external' => false,
				),
				'condition'     => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_secondary_heading',
			array(
				'label'     => esc_html__( 'Secondary Button', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_secondary_text',
			array(
				'label'     => esc_html__( 'Button 2 Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'होटल व धर्मशाला', 'ab-addon' ),
				'condition' => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'btn_secondary_url',
			array(
				'label'         => esc_html__( 'Button 2 URL', 'ab-addon' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => 'https://ramdevraa.in/hotels',
				'show_external' => true,
				'default'       => array(
					'url'         => '',
					'is_external' => false,
				),
				'condition'     => array(
					'slide_content_type' => 'content',
				),
			)
		);

		$repeater->add_control(
			'slide_link',
			array(
				'label'         => esc_html__( 'Full Slide Link (Optional)', 'ab-addon' ),
				'type'          => Controls_Manager::URL,
				'separator'     => 'before',
				'description'   => esc_html__( 'Makes entire slide clickable.', 'ab-addon' ),
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => esc_html__( 'Slides List', 'ab-addon' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'title'       => 'बाबा रामदेव जी समाधि मंदिर',
						'badge'       => 'पवित्र तीर्थ स्थल',
						'description' => 'रामदेवरा (रुणिचा) में आपका हार्दिक स्वागत है। बाबा के दर्शन, आरती समय, होटल एवं यात्रा की सम्पूर्ण जानकारी।',
						'btn_primary_text' => 'आरती का समय देखें',
					),
					array(
						'title'       => 'रामदेवरा यात्रा गाइड एवं सेवाएँ',
						'badge'       => 'सुगम यात्रा एवं दर्शन',
						'description' => 'रेलवे स्टेशन, बस स्टैंड, टैक्सी सेवा और स्थानीय दर्शनीय स्थलों की विस्तृत जानकारी।',
						'btn_primary_text' => 'यात्रा गाइड पढ़ें',
					),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: SLIDER SETTINGS
		// ==========================================
		$this->start_controls_section(
			'section_slider_settings',
			array(
				'label'     => esc_html__( 'Slider Settings', 'ab-addon' ),
				'condition' => array(
					'source_type' => 'custom',
				),
			)
		);

		// Height Desktop / Tablet / Mobile
		$this->add_responsive_control(
			'slider_height',
			array(
				'label'      => esc_html__( 'Slider Height (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min'  => 200,
						'max'  => 1200,
						'step' => 10,
					),
					'vh' => array(
						'min'  => 30,
						'max'  => 100,
					),
				),
				'desktop_default' => array(
					'size' => 560,
					'unit' => 'px',
				),
				'tablet_default' => array(
					'size' => 460,
					'unit' => 'px',
				),
				'mobile_default' => array(
					'size' => 380,
					'unit' => 'px',
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider-wrap' => '--ab-slide-h: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ab-hero-slider'      => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'slides_per_view',
			array(
				'label'          => esc_html__( 'Slides per View (Sliding Container)', 'ab-addon' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1 (Full Banner / Single Container)',
					'2' => '2 Slides',
					'3' => '3 Slides',
					'4' => '4 Slides',
				),
			)
		);

		$this->add_responsive_control(
			'space_between',
			array(
				'label'      => esc_html__( 'Space Between Slides (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 2,
					),
				),
				'default'    => array(
					'size' => 0,
					'unit' => 'px',
				),
			)
		);

		$this->add_responsive_control(
			'content_alignment',
			array(
				'label'     => esc_html__( 'Content Alignment', 'ab-addon' ),
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
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => esc_html__( 'Transition Effect', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade',
				'options' => array(
					'fade'  => esc_html__( 'Fade (Crossfade)', 'ab-addon' ),
					'slide' => esc_html__( 'Slide (Horizontal)', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Autoplay', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => esc_html__( 'Autoplay Delay (ms)', 'ab-addon' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'step'      => 500,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => esc_html__( 'Pause on Hover', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Infinite Loop', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => esc_html__( 'Transition Speed (ms)', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 800,
				'step'    => 50,
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'        => esc_html__( 'Navigation Arrows', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'dots',
			array(
				'label'        => esc_html__( 'Pagination Dots', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: TITLE & CONTENT
		// ==========================================
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Title & Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-slide-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-slide-title',
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Description Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255, 255, 255, 0.92)',
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .ab-slide-desc' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'desc_typography',
				'selector' => '{{WRAPPER}} .ab-slide-desc',
			)
		);

		$this->add_control(
			'badge_heading',
			array(
				'label'     => esc_html__( 'Badge / Eyebrow', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'badge_bg',
			array(
				'label'     => esc_html__( 'Badge Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slide-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Badge Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slide-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: BUTTONS
		// ==========================================
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => esc_html__( 'Buttons', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'primary_btn_bg',
			array(
				'label'     => esc_html__( 'Primary Button Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-btn-primary' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'primary_btn_color',
			array(
				'label'     => esc_html__( 'Primary Button Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-btn-primary' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'btn_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: ARROWS & DOTS
		// ==========================================
		$this->start_controls_section(
			'section_style_nav',
			array(
				'label' => esc_html__( 'Arrows & Dots', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => esc_html__( 'Arrow Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slider-nav' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => esc_html__( 'Arrow Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slider-nav' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => esc_html__( 'Dot Inactive Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => esc_html__( 'Dot Active Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( 'saved' === $settings['source_type'] ) {
			$slider_id = ! empty( $settings['saved_slider_id'] ) ? $settings['saved_slider_id'] : '';
			if ( empty( $slider_id ) ) {
				$all = AB_Slider_Renderer::get_sliders();
				if ( ! empty( $all ) ) {
					$first = reset( $all );
					$slider_id = $first['id'];
				}
			}

			echo AB_Slider_Renderer::render_by_id( $slider_id ); // phpcs:ignore
			return;
		}

		// Custom Slides Mode
		$raw_slides = ! empty( $settings['slides'] ) ? $settings['slides'] : array();
		if ( empty( $raw_slides ) ) {
			$default_seed = AB_Slider_Renderer::get_default_seed_slider();
			$raw_slides   = ! empty( $default_seed['slides'] ) ? $default_seed['slides'] : array();
		}
		$clean_slides = array();

		foreach ( $raw_slides as $slide ) {
			$clean_slides[] = array(
				'id'                   => $slide['_id'] ?? uniqid(),
				'slide_content_type'   => ! empty( $slide['slide_content_type'] ) ? $slide['slide_content_type'] : 'content',
				'template_id'          => ! empty( $slide['template_id'] ) ? intval( $slide['template_id'] ) : 0,
				'bg_image_url'         => ! empty( $slide['bg_image']['url'] ) ? $slide['bg_image']['url'] : '',
				'bg_mobile_url'        => ! empty( $slide['bg_mobile']['url'] ) ? $slide['bg_mobile']['url'] : '',
				'overlay_color'        => ! empty( $slide['overlay_color'] ) ? $slide['overlay_color'] : 'rgba(15, 23, 42, 0.55)',
				'badge'                => ! empty( $slide['badge'] ) ? $slide['badge'] : '',
				'title'                => ! empty( $slide['title'] ) ? $slide['title'] : '',
				'description'          => ! empty( $slide['description'] ) ? $slide['description'] : '',
				'btn_primary_text'     => ! empty( $slide['btn_primary_text'] ) ? $slide['btn_primary_text'] : '',
				'btn_primary_url'      => ! empty( $slide['btn_primary_url']['url'] ) ? $slide['btn_primary_url']['url'] : '',
				'btn_primary_target'   => ! empty( $slide['btn_primary_url']['is_external'] ) ? '_blank' : '_self',
				'btn_secondary_text'   => ! empty( $slide['btn_secondary_text'] ) ? $slide['btn_secondary_text'] : '',
				'btn_secondary_url'    => ! empty( $slide['btn_secondary_url']['url'] ) ? $slide['btn_secondary_url']['url'] : '',
				'btn_secondary_target' => ! empty( $slide['btn_secondary_url']['is_external'] ) ? '_blank' : '_self',
				'slide_link'           => ! empty( $slide['slide_link']['url'] ) ? $slide['slide_link']['url'] : '',
			);
		}

		$config = array(
			'id'                     => 'elem_' . $this->get_id(),
			'height_desktop'         => ! empty( $settings['slider_height']['size'] ) ? intval( $settings['slider_height']['size'] ) : 560,
			'height_tablet'          => ! empty( $settings['slider_height_tablet']['size'] ) ? intval( $settings['slider_height_tablet']['size'] ) : 460,
			'height_mobile'          => ! empty( $settings['slider_height_mobile']['size'] ) ? intval( $settings['slider_height_mobile']['size'] ) : 380,
			'slides_per_view'        => ! empty( $settings['slides_per_view'] ) ? intval( $settings['slides_per_view'] ) : 1,
			'slides_per_view_tablet' => ! empty( $settings['slides_per_view_tablet'] ) ? intval( $settings['slides_per_view_tablet'] ) : 1,
			'slides_per_view_mobile' => ! empty( $settings['slides_per_view_mobile'] ) ? intval( $settings['slides_per_view_mobile'] ) : 1,
			'space_between'          => ! empty( $settings['space_between']['size'] ) ? intval( $settings['space_between']['size'] ) : 0,
			'effect'                 => ! empty( $settings['effect'] ) ? $settings['effect'] : 'fade',
			'autoplay'               => ! empty( $settings['autoplay'] ) ? $settings['autoplay'] : 'yes',
			'autoplay_delay'         => ! empty( $settings['autoplay_delay'] ) ? intval( $settings['autoplay_delay'] ) : 5000,
			'pause_on_hover'         => ! empty( $settings['pause_on_hover'] ) ? $settings['pause_on_hover'] : 'yes',
			'speed'                  => ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 800,
			'loop'                   => ! empty( $settings['loop'] ) ? $settings['loop'] : 'yes',
			'arrows'                 => ! empty( $settings['arrows'] ) ? $settings['arrows'] : 'yes',
			'dots'                   => ! empty( $settings['dots'] ) ? $settings['dots'] : 'yes',
			'content_align'          => ! empty( $settings['content_alignment'] ) ? $settings['content_alignment'] : 'center',
			'content_max_w'          => 820,
			'slides'                 => $clean_slides,
		);

		echo AB_Slider_Renderer::render( $config ); // phpcs:ignore
	}

	/**
	 * Retrieve saved Elementor templates for dropdown.
	 *
	 * @return array
	 */
	protected function get_elementor_templates_list() {
		$options = array( '' => esc_html__( '— Select a Saved Template —', 'ab-addon' ) );
		$templates = get_posts( array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		if ( ! empty( $templates ) && ! is_wp_error( $templates ) ) {
			foreach ( $templates as $t ) {
				$type = get_post_meta( $t->ID, '_elementor_template_type', true );
				$label = $t->post_title . ( $type ? ' (' . ucfirst( $type ) . ')' : '' );
				$options[ $t->ID ] = $label;
			}
		}

		return $options;
	}
}
