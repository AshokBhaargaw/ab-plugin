<?php
/**
 * AB Hotel Grid Elementor Widget
 *
 * Displays a dynamic, customizable grid of Hotel Custom Post Type posts.
 * Provides controls for query, responsive columns, card elements, pagination,
 * and comprehensive Elementor styling options.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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

class AB_Hotel_Grid_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_hotel_grid';
	}

	public function get_title() {
		return esc_html__( 'AB Hotel Grid', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'hotel', 'grid', 'hotels', 'ramdevra', 'stay', 'rooms', 'ab', 'archive' );
	}

	public function get_style_depends() {
		return array( 'ab-hotel-grid-style' );
	}

	/**
	 * Retrieve registered public post types for selection dropdown.
	 *
	 * @return array Post type options.
	 */
	protected function get_post_types_options() {
		$options = array(
			'auto'  => esc_html__( 'Auto Detect', 'ab-addon' ),
			'hotel' => esc_html__( 'Hotel (hotel)', 'ab-addon' ),
		);

		$cpts = get_post_types( array( 'public' => true ), 'objects' );
		$exclude = array( 'post', 'page', 'attachment', 'elementor_library', 'hotel' );

		foreach ( $cpts as $slug => $cpt ) {
			if ( in_array( $slug, $exclude, true ) ) {
				continue;
			}
			$options[ $slug ] = $cpt->label . ' (' . $slug . ')';
		}

		return $options;
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {

		// ==========================================
		// SECTION: QUERY & CONTEXT
		// ==========================================
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Query & Source', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'       => esc_html__( 'Post Type', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => $this->get_post_types_options(),
				'description' => esc_html__( 'Auto Detect defaults to "hotel" when placed on the Hotel archive or general pages.', 'ab-addon' ),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => esc_html__( 'Number of Hotels', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 100,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => esc_html__( 'Date', 'ab-addon' ),
					'title'      => esc_html__( 'Title', 'ab-addon' ),
					'modified'   => esc_html__( 'Modified Date', 'ab-addon' ),
					'menu_order' => esc_html__( 'Menu Order', 'ab-addon' ),
					'rand'       => esc_html__( 'Random', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => esc_html__( 'Descending (Z-A, Newest first)', 'ab-addon' ),
					'ASC'  => esc_html__( 'Ascending (A-Z, Oldest first)', 'ab-addon' ),
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: LAYOUT
		// ==========================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout & Columns', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'ab-addon' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array(
					'1' => esc_html__( '1 Column', 'ab-addon' ),
					'2' => esc_html__( '2 Columns', 'ab-addon' ),
					'3' => esc_html__( '3 Columns', 'ab-addon' ),
					'4' => esc_html__( '4 Columns', 'ab-addon' ),
				),
				'selectors'      => array(
					'{{WRAPPER}} .ab-hotel-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr)) !important;',
				),
			)
		);

		$this->add_responsive_control(
			'column_gap',
			array(
				'label'      => esc_html__( 'Columns Gap', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'size' => 28,
					'unit' => 'px',
				),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-grid' => 'column-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'row_gap',
			array(
				'label'      => esc_html__( 'Rows Gap', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'size' => 28,
					'unit' => 'px',
				),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-grid' => 'row-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: CARD ELEMENTS
		// ==========================================
		$this->start_controls_section(
			'section_card_elements',
			array(
				'label' => esc_html__( 'Card Elements', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => esc_html__( 'Featured Image', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'      => esc_html__( 'Image Height', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'default'    => array(
					'size' => 230,
					'unit' => 'px',
				),
				'range'      => array(
					'px' => array(
						'min' => 120,
						'max' => 450,
					),
				),
				'condition'  => array(
					'show_image' => 'yes',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-image-wrapper' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Badge / Tag', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Badge Text', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Ramdevra Hotel', 'ab-addon' ),
				'condition'   => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_location',
			array(
				'label'        => esc_html__( 'Location', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => esc_html__( 'Hotel Title', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML Tag', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'p'  => 'p',
				),
				'condition' => array(
					'show_title' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Description / Excerpt', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'     => esc_html__( 'Excerpt Words', 'ab-addon' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 20,
				'min'       => 5,
				'max'       => 100,
				'condition' => array(
					'show_excerpt' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_button',
			array(
				'label'        => esc_html__( 'Details Button', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'     => esc_html__( 'Button Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'View Hotel Details', 'ab-addon' ),
				'condition' => array(
					'show_button' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: PAGINATION
		// ==========================================
		$this->start_controls_section(
			'section_pagination',
			array(
				'label' => esc_html__( 'Pagination', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_pagination',
			array(
				'label'        => esc_html__( 'Enable Pagination', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: CARD
		// ==========================================
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Hotel Card', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-card' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .ab-hotel-card',
			)
		);

		$this->add_responsive_control(
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
					'{{WRAPPER}} .ab-hotel-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_content_padding',
			array(
				'label'      => esc_html__( 'Content Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => 22,
					'right'    => 22,
					'bottom'   => 22,
					'left'     => 22,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .ab-hotel-card',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: BADGE
		// ==========================================
		$this->start_controls_section(
			'section_style_badge',
			array(
				'label'     => esc_html__( 'Badge / Tag', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_badge' => 'yes',
				),
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-badge' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d97706',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-badge' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .ab-hotel-badge',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: TITLE & LOCATION
		// ==========================================
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Title & Location', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_title',
			array(
				'label' => esc_html__( 'Hotel Title', 'ab-addon' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1f2937',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-title, {{WRAPPER}} .ab-hotel-title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => esc_html__( 'Hover Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d97706',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-hotel-title',
			)
		);

		$this->add_control(
			'heading_location',
			array(
				'label'     => esc_html__( 'Location', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'show_location' => 'yes',
				),
			)
		);

		$this->add_control(
			'location_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7280',
				'condition' => array(
					'show_location' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-location' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'location_typography',
				'condition' => array(
					'show_location' => 'yes',
				),
				'selector'  => '{{WRAPPER}} .ab-hotel-location',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: EXCERPT
		// ==========================================
		$this->start_controls_section(
			'section_style_excerpt',
			array(
				'label'     => esc_html__( 'Description / Excerpt', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_excerpt' => 'yes',
				),
			)
		);

		$this->add_control(
			'excerpt_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6b7280',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-excerpt' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'excerpt_typography',
				'selector' => '{{WRAPPER}} .ab-hotel-excerpt',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: BUTTON
		// ==========================================
		$this->start_controls_section(
			'section_style_button',
			array(
				'label'     => esc_html__( 'Details Button', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'show_button' => 'yes',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_button_style' );

		// Normal State
		$this->start_controls_tab(
			'tab_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'ab-addon' ),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-btn' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'button_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1f2937',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Hover State
		$this->start_controls_tab(
			'tab_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'ab-addon' ),
			)
		);

		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-btn:hover' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'button_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d97706',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'button_typography',
				'separator' => 'before',
				'selector'  => '{{WRAPPER}} .ab-hotel-btn',
			)
		);

		$this->add_responsive_control(
			'button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'default'    => array(
					'top'      => 8,
					'right'    => 8,
					'bottom'   => 8,
					'left'     => 8,
					'unit'     => 'px',
					'isLinked' => true,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'default'    => array(
					'top'      => 12,
					'right'    => 18,
					'bottom'   => 12,
					'left'     => 18,
					'unit'     => 'px',
					'isLinked' => false,
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-hotel-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: PAGINATION
		// ==========================================
		$this->start_controls_section(
			'section_style_pagination',
			array(
				'label'     => esc_html__( 'Pagination', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_pagination' => 'yes',
				),
			)
		);

		$this->add_control(
			'pagination_color',
			array(
				'label'     => esc_html__( 'Link Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#374151',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-pagination .page-numbers' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pagination_active_bg_color',
			array(
				'label'     => esc_html__( 'Active Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#d97706',
				'selectors' => array(
					'{{WRAPPER}} .ab-hotel-pagination .page-numbers.current, {{WRAPPER}} .ab-hotel-pagination .page-numbers:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}}; color: #ffffff;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Determine target post type.
		$selected_post_type = ! empty( $settings['post_type'] ) ? $settings['post_type'] : 'auto';

		if ( 'auto' === $selected_post_type ) {
			if ( is_post_type_archive() ) {
				$archive_pt = get_query_var( 'post_type' );
				if ( is_array( $archive_pt ) ) {
					$archive_pt = reset( $archive_pt );
				}
				$post_type = ! empty( $archive_pt ) ? sanitize_key( $archive_pt ) : 'hotel';
			} else {
				$post_type = 'hotel';
			}
		} else {
			$post_type = sanitize_key( $selected_post_type );
		}

		// Pagination calculation.
		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$paged = get_query_var( 'page' );
		} else {
			$paged = 1;
		}

		$posts_per_page = ! empty( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 6;
		$orderby        = ! empty( $settings['orderby'] ) ? sanitize_key( $settings['orderby'] ) : 'date';
		$order          = ! empty( $settings['order'] ) ? sanitize_key( $settings['order'] ) : 'DESC';

		$query_args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		);

		$query = new \WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			echo '<div class="ab-hotel-no-posts" style="padding: 40px 20px; text-align: center; color: #6b7280;">';
			echo '<p>' . esc_html__( 'No hotel listings found.', 'ab-addon' ) . '</p>';
			echo '</div>';
			return;
		}

		// Card Element Settings.
		$show_image     = 'yes' === ( $settings['show_image'] ?? 'yes' );
		$show_badge     = 'yes' === ( $settings['show_badge'] ?? 'yes' );
		$badge_text     = ! empty( $settings['badge_text'] ) ? $settings['badge_text'] : esc_html__( 'Ramdevra Hotel', 'ab-addon' );
		$show_location  = 'yes' === ( $settings['show_location'] ?? 'yes' );
		$show_title     = 'yes' === ( $settings['show_title'] ?? 'yes' );
		$title_tag      = ! empty( $settings['title_tag'] ) ? sanitize_key( $settings['title_tag'] ) : 'h3';
		$show_excerpt   = 'yes' === ( $settings['show_excerpt'] ?? 'yes' );
		$excerpt_words  = ! empty( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 20;
		$show_button    = 'yes' === ( $settings['show_button'] ?? 'yes' );
		$button_text    = ! empty( $settings['button_text'] ) ? $settings['button_text'] : esc_html__( 'View Hotel Details', 'ab-addon' );
		$has_pagination = 'yes' === ( $settings['enable_pagination'] ?? 'yes' );
		?>

		<div class="ab-hotel-grid-wrapper" data-post-type="<?php echo esc_attr( $post_type ); ?>">
			<div class="ab-hotel-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$hotel_id   = get_the_ID();
					$permalink  = get_permalink( $hotel_id );
					$title      = get_the_title( $hotel_id );
					$location   = class_exists( 'AB_Hotel_Helper' ) ? AB_Hotel_Helper::get_hotel_location( $hotel_id ) : 'Ramdevra, Rajasthan';
					$excerpt    = class_exists( 'AB_Hotel_Helper' ) ? AB_Hotel_Helper::get_hotel_excerpt( $hotel_id, $excerpt_words ) : wp_trim_words( get_the_excerpt( $hotel_id ), $excerpt_words );
					?>
					<article id="ab-hotel-<?php echo esc_attr( $hotel_id ); ?>" class="ab-hotel-card post-<?php echo esc_attr( $hotel_id ); ?>">

						<?php if ( $show_image ) : ?>
							<div class="ab-hotel-image-wrapper">
								<a href="<?php echo esc_url( $permalink ); ?>" class="ab-hotel-image-link" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'ab-addon' ), $title ) ); ?>">
									<?php if ( has_post_thumbnail( $hotel_id ) ) : ?>
										<?php
										the_post_thumbnail(
											'large',
											array(
												'class' => 'ab-hotel-img',
												'alt'   => esc_attr( $title ),
											)
										);
										?>
									<?php else : ?>
										<div class="ab-hotel-placeholder" aria-hidden="true">
											<span class="dashicons dashicons-building"></span>
										</div>
									<?php endif; ?>
								</a>

								<?php if ( $show_badge && ! empty( $badge_text ) ) : ?>
									<span class="ab-hotel-badge"><?php echo esc_html( $badge_text ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="ab-hotel-content">

							<?php if ( $show_location && ! empty( $location ) ) : ?>
								<p class="ab-hotel-location">
									<span aria-hidden="true">📍</span> <?php echo esc_html( $location ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $show_title ) : ?>
								<<?php echo esc_attr( $title_tag ); ?> class="ab-hotel-title">
									<a href="<?php echo esc_url( $permalink ); ?>">
										<?php echo esc_html( $title ); ?>
									</a>
								</<?php echo esc_attr( $title_tag ); ?>>
							<?php endif; ?>

							<?php if ( $show_excerpt && ! empty( $excerpt ) ) : ?>
								<div class="ab-hotel-excerpt">
									<p><?php echo esc_html( $excerpt ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( $show_button ) : ?>
								<div class="ab-hotel-footer">
									<a href="<?php echo esc_url( $permalink ); ?>" class="ab-hotel-btn">
										<span><?php echo esc_html( $button_text ); ?></span>
										<span class="ab-hotel-btn-arrow" aria-hidden="true">→</span>
									</a>
								</div>
							<?php endif; ?>

						</div>

					</article>
				<?php endwhile; ?>
			</div>

			<?php
			if ( $has_pagination && $query->max_num_pages > 1 ) :
				$big = 999999999;
				$pagination_links = paginate_links(
					array(
						'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
						'format'    => '?paged=%#%',
						'current'   => max( 1, $paged ),
						'total'     => $query->max_num_pages,
						'type'      => 'list',
						'prev_text' => '← ' . esc_html__( 'Previous', 'ab-addon' ),
						'next_text' => esc_html__( 'Next', 'ab-addon' ) . ' →',
					)
				);
				if ( ! empty( $pagination_links ) ) :
					?>
					<nav class="ab-hotel-pagination" aria-label="<?php esc_attr_e( 'Hotel listings pagination', 'ab-addon' ); ?>">
						<div class="ab-hotel-pagination-inner">
							<?php echo wp_kses_post( $pagination_links ); ?>
						</div>
					</nav>
					<?php
				endif;
			endif;

			wp_reset_postdata();
			?>
		</div>
		<?php
	}
}
