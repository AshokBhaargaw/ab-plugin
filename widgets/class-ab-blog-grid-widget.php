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
use Elementor\Group_Control_Css_Filter;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Blog_Grid_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_blog_grid';
	}

	public function get_title() {
		return esc_html__( 'AB Loop Grid', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-posts-grid';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'blog', 'loop', 'grid', 'cards', 'posts', 'category', 'filter', 'ab' );
	}

	public function get_style_depends() {
		return array( 'ab-blog-grid-style' );
	}

	public function get_script_depends() {
		return array( 'ab-blog-grid-script' );
	}

	protected function get_categories_dropdown_options() {
		$categories = get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
		) );

		$options = array(
			'0' => esc_html__( 'All Categories', 'ab-addon' ),
		);
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$options[ (string) $category->term_id ] = $category->name . ' (' . $category->count . ')';
			}
		}
		return $options;
	}

	protected function get_categories_options() {
		$categories = get_categories( array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
		) );

		$options = array();
		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$options[ $category->term_id ] = $category->name . ' (' . $category->count . ')';
			}
		}
		return $options;
	}

	protected function get_post_types_options() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$options    = array();
		foreach ( $post_types as $type ) {
			if ( in_array( $type->name, array( 'attachment', 'elementor_library' ), true ) ) {
				continue;
			}
			$options[ $type->name ] = $type->label;
		}
		return $options;
	}

	protected function register_controls() {

		// ==========================================
		// SECTION: QUERY & CATEGORIES
		// ==========================================
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Query & Categories', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'post_type',
			array(
				'label'   => esc_html__( 'Post Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'post',
				'options' => $this->get_post_types_options(),
			)
		);

		$this->add_control(
			'category_select_mode',
			array(
				'label'   => esc_html__( 'Category Filter Mode', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'      => esc_html__( 'All Categories', 'ab-addon' ),
					'single'   => esc_html__( 'Single Category Dropdown', 'ab-addon' ),
					'multiple' => esc_html__( 'Multiple Categories Multi-select', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'single_category',
			array(
				'label'       => esc_html__( 'Select Category', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '0',
				'options'     => $this->get_categories_dropdown_options(),
				'description' => esc_html__( 'Select a specific category to show posts from.', 'ab-addon' ),
				'condition'   => array(
					'category_select_mode' => 'single',
				),
			)
		);

		$this->add_control(
			'categories',
			array(
				'label'       => esc_html__( 'Select Categories', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_categories_options(),
				'description' => esc_html__( 'Select multiple categories to display.', 'ab-addon' ),
				'condition'   => array(
					'category_select_mode' => 'multiple',
				),
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => esc_html__( 'Posts Per Page', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 50,
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'          => esc_html__( 'Date', 'ab-addon' ),
					'title'         => esc_html__( 'Title', 'ab-addon' ),
					'modified'      => esc_html__( 'Last Modified', 'ab-addon' ),
					'rand'          => esc_html__( 'Random', 'ab-addon' ),
					'comment_count' => esc_html__( 'Comment Count', 'ab-addon' ),
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
					'DESC' => esc_html__( 'Descending (Z-A / Newest)', 'ab-addon' ),
					'ASC'  => esc_html__( 'Ascending (A-Z / Oldest)', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'offset',
			array(
				'label'   => esc_html__( 'Offset (Skip Posts)', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
			)
		);

		$this->add_control(
			'exclude_current',
			array(
				'label'        => esc_html__( 'Exclude Current Post', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: CATEGORY FILTER BAR
		// ==========================================
		$this->start_controls_section(
			'section_filter_bar',
			array(
				'label'     => esc_html__( 'Category Filter Tabs', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'category_select_mode!' => 'single',
				),
			)
		);

		$this->add_control(
			'enable_filter_bar',
			array(
				'label'        => esc_html__( 'Enable Filter Tabs', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'ab-addon' ),
				'label_off'    => esc_html__( 'Hide', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'filter_all_label',
			array(
				'label'     => esc_html__( '"All" Tab Label', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'All Posts', 'ab-addon' ),
				'condition' => array( 'enable_filter_bar' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: LAYOUT & CARDS
		// ==========================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Card Layout & Elements', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'card_style',
			array(
				'label'   => esc_html__( 'Card Design Style', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modern',
				'options' => array(
					'modern'  => esc_html__( 'Modern (Rounded Shadow Card)', 'ab-addon' ),
					'classic' => esc_html__( 'Classic (Bordered Clean Card)', 'ab-addon' ),
					'overlay' => esc_html__( 'Overlay (Image Background Card)', 'ab-addon' ),
					'minimal' => esc_html__( 'Minimal (Flat Compact Card)', 'ab-addon' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array( 
				'label'          => esc_html__( 'Columns Grid', 'ab-addon' ),
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
					'{{WRAPPER}} .ab-blog-grid' => 'display: grid !important; grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
				),
			)
		);

		$this->add_control(
			'image_aspect_ratio',
			array(
				'label'   => esc_html__( 'Image Ratio', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ratio-16-9',
				'options' => array(
					'ratio-16-9' => '16:9 Landscape',
					'ratio-4-3'  => '4:3 Standard',
					'ratio-1-1'  => '1:1 Square',
					'ratio-3-4'  => '3:4 Portrait',
					'ratio-auto' => 'Auto Original',
				),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => esc_html__( 'Featured Image', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Category Badge', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'        => esc_html__( 'Post Title', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML Tag', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'div'  => 'div',
				),
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Excerpt', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'excerpt_length',
			array(
				'label'     => esc_html__( 'Excerpt Words', 'ab-addon' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 18,
				'min'       => 5,
				'max'       => 100,
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'show_meta',
			array(
				'label'        => esc_html__( 'Show Post Meta', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label'     => esc_html__( 'Meta: Author', 'ab-addon' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'     => esc_html__( 'Meta: Date', 'ab-addon' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		$this->add_control(
			'show_read_more',
			array(
				'label'        => esc_html__( 'Read More Button', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label'     => esc_html__( 'Read More Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read Story', 'ab-addon' ),
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'   => esc_html__( 'Pagination / Navigation', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'           => esc_html__( 'None', 'ab-addon' ),
					'numeric'        => esc_html__( 'Numeric Page Numbers', 'ab-addon' ),
					'ajax_load_more' => esc_html__( 'AJAX Load More Button', 'ab-addon' ),
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: FILTER TABS
		// ==========================================
		$this->start_controls_section(
			'section_style_filters',
			array(
				'label'     => esc_html__( 'Filter Tabs Style', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_filter_bar'     => 'yes',
					'category_select_mode!' => 'single',
				),
			)
		);

		$this->add_responsive_control(
			'filter_align',
			array(
				'label'     => esc_html__( 'Alignment', 'ab-addon' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => esc_html__( 'Left', 'ab-addon' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'ab-addon' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'Right', 'ab-addon' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .ab-filter-bar' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'filter_typography',
				'selector' => '{{WRAPPER}} .ab-filter-btn',
			)
		);

		$this->start_controls_tabs( 'tabs_filter_style' );

		// Normal State
		$this->start_controls_tab(
			'tab_filter_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'filter_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#555555',
				'selectors' => array(
					'{{WRAPPER}} .ab-filter-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'filter_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f3f4f6',
				'selectors' => array(
					'{{WRAPPER}} .ab-filter-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		// Active / Hover State
		$this->start_controls_tab(
			'tab_filter_active',
			array( 'label' => esc_html__( 'Active / Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'filter_active_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-filter-btn:hover, {{WRAPPER}} .ab-filter-btn.active' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'filter_active_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ff4500',
				'selectors' => array(
					'{{WRAPPER}} .ab-filter-btn:hover, {{WRAPPER}} .ab-filter-btn.active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'filter_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-filter-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'filter_margin_bottom',
			array(
				'label'      => esc_html__( 'Spacing Below Filter', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 30 ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-filter-bar' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: CARD BOX
		// ==========================================
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card Container', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Grid Spacing Gap', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-blog-grid' => 'gap: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .ab-card-inner' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Content Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .ab-card-inner',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .ab-card-inner',
			)
		);

		$this->add_control(
			'card_hover_lift',
			array(
				'label'        => esc_html__( 'Hover Lift Effect', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: FEATURED IMAGE
		// ==========================================
		$this->start_controls_section(
			'section_style_image',
			array(
				'label'     => esc_html__( 'Featured Image', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'       => esc_html__( 'Image Height', 'ab-addon' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh' ),
				'range'       => array(
					'px' => array( 'min' => 80, 'max' => 600 ),
					'vh' => array( 'min' => 10, 'max' => 60 ),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => 190,
				),
				'selectors'   => array(
					'{{WRAPPER}} .ab-card-media' => 'height: {{SIZE}}{{UNIT}} !important; min-height: {{SIZE}}{{UNIT}} !important; max-height: {{SIZE}}{{UNIT}} !important; aspect-ratio: unset !important;',
					'{{WRAPPER}} .ab-card-media .ab-card-image-link' => 'position: absolute !important; inset: 0 !important; width: 100% !important; height: 100% !important; display: block !important;',
					'{{WRAPPER}} .ab-card-media img, {{WRAPPER}} .ab-card-img' => 'width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; display: block !important;',
				),
			)
		);

		$this->add_control(
			'image_object_fit',
			array(
				'label'     => esc_html__( 'Object Fit', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'      => esc_html__( 'Cover', 'ab-addon' ),
					'contain'    => esc_html__( 'Contain', 'ab-addon' ),
					'fill'       => esc_html__( 'Fill', 'ab-addon' ),
					'scale-down' => esc_html__( 'Scale Down', 'ab-addon' ),
					'none'       => esc_html__( 'None', 'ab-addon' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-img, {{WRAPPER}} .ab-card-media img' => 'object-fit: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'image_box_bg',
			array(
				'label'     => esc_html__( 'Image Box Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#f3f4f6',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-media' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_object_position',
			array(
				'label'     => esc_html__( 'Object Position', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => array(
					'center center' => esc_html__( 'Center Center', 'ab-addon' ),
					'center top'    => esc_html__( 'Center Top', 'ab-addon' ),
					'center bottom' => esc_html__( 'Center Bottom', 'ab-addon' ),
					'left center'   => esc_html__( 'Left Center', 'ab-addon' ),
					'right center'  => esc_html__( 'Right Center', 'ab-addon' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-img, {{WRAPPER}} .ab-card-media img' => 'object-position: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'image_scale',
			array(
				'label'      => esc_html__( 'Image Zoom / Crop', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 1, 'max' => 2, 'step' => 0.05 ),
				),
				'default'    => array( 'size' => 1 ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-img' => 'transform: scale({{SIZE}}); transform-origin: center center;',
				),
			)
		);

		$this->add_responsive_control(
			'image_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ab-card-img'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'image_margin',
			array(
				'label'      => esc_html__( 'Spacing / Margin', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-media' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'image_border',
				'selector' => '{{WRAPPER}} .ab-card-media',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'image_box_shadow',
				'selector' => '{{WRAPPER}} .ab-card-media',
			)
		);

		$this->start_controls_tabs( 'tabs_image_style' );

		// Normal Tab
		$this->start_controls_tab(
			'tab_image_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'image_opacity',
			array(
				'label'     => esc_html__( 'Opacity', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-img' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'image_css_filters',
				'selector' => '{{WRAPPER}} .ab-card-img',
			)
		);

		$this->end_controls_tab();

		// Hover Tab
		$this->start_controls_tab(
			'tab_image_hover',
			array( 'label' => esc_html__( 'Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'image_hover_opacity',
			array(
				'label'     => esc_html__( 'Opacity', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 0.1, 'max' => 1, 'step' => 0.05 ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-item:hover .ab-card-img' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'image_hover_css_filters',
				'selector' => '{{WRAPPER}} .ab-card-item:hover .ab-card-img',
			)
		);

		$this->add_control(
			'image_hover_scale',
			array(
				'label'     => esc_html__( 'Hover Zoom Scale', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 1, 'max' => 1.4, 'step' => 0.02 ),
				),
				'default'   => array( 'size' => 1.06 ),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-item:hover .ab-card-img' => 'transform: scale({{SIZE}});',
				),
			)
		);

		$this->add_control(
			'image_hover_transition',
			array(
				'label'     => esc_html__( 'Transition Duration (s)', 'ab-addon' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array( 'min' => 0.1, 'max' => 2, 'step' => 0.1 ),
				),
				'default'   => array( 'size' => 0.5 ),
				'selectors' => array(
					'{{WRAPPER}} .ab-card-img' => 'transition: transform {{SIZE}}s ease, opacity {{SIZE}}s ease, filter {{SIZE}}s ease;',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: BADGE
		// ==========================================
		$this->start_controls_section(
			'section_style_badge',
			array(
				'label'     => esc_html__( 'Category Badge', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_badge' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} .ab-card-badge a',
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-badge a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ff4500',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-badge a' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top' => '4', 'right' => '4', 'bottom' => '4', 'left' => '4', 'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-badge a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: TYPOGRAPHY & COLORS
		// ==========================================
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Typography & Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// TITLE
		$this->add_control(
			'heading_title_style',
			array(
				'label'     => esc_html__( 'Post Title', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-card-title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => esc_html__( 'Title Hover Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ff4500',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		// EXCERPT
		$this->add_control(
			'heading_excerpt_style',
			array(
				'label'     => esc_html__( 'Excerpt', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'excerpt_typography',
				'selector'  => '{{WRAPPER}} .ab-card-excerpt',
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		$this->add_control(
			'excerpt_color',
			array(
				'label'     => esc_html__( 'Excerpt Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#666666',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-excerpt' => 'color: {{VALUE}};',
				),
				'condition' => array( 'show_excerpt' => 'yes' ),
			)
		);

		// READ MORE BUTTON
		$this->add_control(
			'heading_btn_style',
			array(
				'label'     => esc_html__( 'Read More Button', 'ab-addon' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'btn_typography',
				'selector'  => '{{WRAPPER}} .ab-card-btn',
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->start_controls_tabs( 'tabs_btn_style' );

		$this->start_controls_tab(
			'tab_btn_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ff4500',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_btn_hover',
			array( 'label' => esc_html__( 'Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'btn_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#cc3700',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		// Build WP Query
		$post_type = ! empty( $settings['post_type'] ) ? $settings['post_type'] : 'post';
		$ppp       = ! empty( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : 6;
		$orderby   = ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'date';
		$order     = ! empty( $settings['order'] ) ? $settings['order'] : 'DESC';
		$offset    = ! empty( $settings['offset'] ) ? intval( $settings['offset'] ) : 0;
		$paged     = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $ppp,
			'paged'          => $paged,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'publish',
		);

		if ( $offset > 0 ) {
			$args['offset'] = $offset;
		}

		if ( 'yes' === $settings['exclude_current'] && is_singular() ) {
			$args['post__not_in'] = array( get_the_ID() );
		}

		// Selected categories filter logic
		$cat_mode      = ! empty( $settings['category_select_mode'] ) ? $settings['category_select_mode'] : 'all';
		$selected_cats = array();

		if ( 'single' === $cat_mode && ! empty( $settings['single_category'] ) && '0' !== (string) $settings['single_category'] ) {
			$selected_cats = array( intval( $settings['single_category'] ) );
		} elseif ( 'multiple' === $cat_mode && ! empty( $settings['categories'] ) ) {
			$selected_cats = array_map( 'intval', (array) $settings['categories'] );
		}

		if ( ! empty( $selected_cats ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $selected_cats,
				),
			);
		}

		$query = new WP_Query( $args );

		// Grid wrapper classes
		$grid_classes = array(
			'ab-blog-grid',
			'ab-grid-cols-' . ( isset( $settings['columns'] ) ? $settings['columns'] : '3' ),
			'ab-style-' . ( ! empty( $settings['card_style'] ) ? $settings['card_style'] : 'modern' ),
			'ab-ratio-' . ( ! empty( $settings['image_aspect_ratio'] ) ? str_replace( 'ratio-', '', $settings['image_aspect_ratio'] ) : '16-9' ),
		);

		if ( 'yes' === $settings['card_hover_lift'] ) {
			$grid_classes[] = 'has-hover-lift';
		}

		$widget_id = $this->get_id();

		// Encode settings for JS / AJAX
		$json_settings = esc_attr( wp_json_encode( array(
			'post_type'            => $post_type,
			'posts_per_page'       => $ppp,
			'orderby'              => $orderby,
			'order'                => $order,
			'offset'               => $offset,
			'columns'              => isset( $settings['columns'] ) ? $settings['columns'] : '3',
			'card_style'           => $settings['card_style'],
			'image_aspect_ratio'   => $settings['image_aspect_ratio'],
			'show_image'           => $settings['show_image'],
			'show_badge'           => $settings['show_badge'],
			'show_title'           => $settings['show_title'],
			'title_tag'            => $settings['title_tag'],
			'show_excerpt'         => $settings['show_excerpt'],
			'excerpt_length'       => $settings['excerpt_length'],
			'show_meta'            => $settings['show_meta'],
			'show_author'          => $settings['show_author'],
			'show_date'            => $settings['show_date'],
			'show_read_more'       => $settings['show_read_more'],
			'read_more_text'       => $settings['read_more_text'],
			'category_select_mode' => $cat_mode,
			'single_category'      => ! empty( $settings['single_category'] ) ? $settings['single_category'] : '0',
			'categories'           => $selected_cats,
			'enable_filter_bar'    => ( isset( $settings['enable_filter_bar'] ) && 'single' !== $cat_mode ) ? $settings['enable_filter_bar'] : 'no',
			'filter_all_label'     => ! empty( $settings['filter_all_label'] ) ? $settings['filter_all_label'] : esc_html__( 'All Posts', 'ab-addon' ),
		) ) );
		?>

		<div class="ab-blog-grid-wrapper" id="ab-grid-<?php echo esc_attr( $widget_id ); ?>" data-settings="<?php echo $json_settings; ?>">

			<?php
			// Render Filter Tabs only if enabled AND not in single category mode
			$show_filter_bar = ( 'yes' === $settings['enable_filter_bar'] && 'single' !== $cat_mode );

			if ( $show_filter_bar ) :
				$filter_cats = array();

				if ( ! empty( $selected_cats ) ) {
					foreach ( $selected_cats as $cat_id ) {
						$term = get_term( $cat_id, 'category' );
						if ( $term && ! is_wp_error( $term ) ) {
							$filter_cats[] = $term;
						}
					}
				} else {
					$filter_cats = get_terms( array(
						'taxonomy'   => 'category',
						'hide_empty' => true,
					) );
				}

				// Only render filter tabs if there are at least 2 categories to filter between
				if ( ! empty( $filter_cats ) && ! is_wp_error( $filter_cats ) && count( $filter_cats ) > 1 ) :
					$all_label = ! empty( $settings['filter_all_label'] ) ? $settings['filter_all_label'] : esc_html__( 'All Posts', 'ab-addon' );
					?>
					<div class="ab-filter-bar">
						<button type="button" class="ab-filter-btn active" data-cat-id="0">
							<?php echo esc_html( $all_label ); ?>
						</button>
						<?php foreach ( $filter_cats as $term ) : ?>
							<button type="button" class="ab-filter-btn" data-cat-id="<?php echo esc_attr( $term->term_id ); ?>">
								<?php echo esc_html( $term->name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif;
			endif;
			?>

			<!-- Grid Container -->
			<div class="<?php echo esc_attr( implode( ' ', $grid_classes ) ); ?>">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						self::render_single_card( $settings );
					endwhile;
					wp_reset_postdata();
				else :
					echo '<div class="ab-grid-no-posts"><p>' . esc_html__( 'No blog posts found.', 'ab-addon' ) . '</p></div>';
				endif;
				?>
			</div>

			<!-- Loader Overlay -->
			<div class="ab-grid-loader" style="display:none;">
				<div class="ab-spinner"></div>
			</div>

			<?php
			// Render Pagination or Load More
			if ( $query->max_num_pages > 1 ) :
				if ( 'numeric' === $settings['pagination_type'] ) :
					?>
					<div class="ab-grid-pagination">
						<?php
						echo paginate_links( array(
							'total'     => $query->max_num_pages,
							'current'   => $paged,
							'prev_text' => '&laquo;',
							'next_text' => '&raquo;',
						) );
						?>
					</div>
				<?php elseif ( 'ajax_load_more' === $settings['pagination_type'] ) : ?>
					<div class="ab-grid-load-more-wrap">
						<button type="button" class="ab-load-more-btn" data-paged="<?php echo esc_attr( $paged ); ?>" data-max-pages="<?php echo esc_attr( $query->max_num_pages ); ?>">
							<span><?php esc_html_e( 'Load More Articles', 'ab-addon' ); ?></span>
						</button>
					</div>
					<?php
				endif;
			endif;
			?>

		</div>

		<?php
	}

	public static function render_single_card( $settings ) {
		$post_id        = get_the_ID();
		$permalink      = get_permalink( $post_id );
		$title          = get_the_title( $post_id );
		$title_tag      = ! empty( $settings['title_tag'] ) ? $settings['title_tag'] : 'h3';
		$card_style     = ! empty( $settings['card_style'] ) ? $settings['card_style'] : ( ! empty( $settings['card_design'] ) ? $settings['card_design'] : 'modern' );
		$ratio_class    = ! empty( $settings['image_aspect_ratio'] ) ? $settings['image_aspect_ratio'] : 'ratio-16-9';

		$show_image     = isset( $settings['show_image'] ) ? $settings['show_image'] : 'yes';
		$show_badge     = isset( $settings['show_badge'] ) ? $settings['show_badge'] : 'yes';
		$show_title     = isset( $settings['show_title'] ) ? $settings['show_title'] : 'yes';
		$show_excerpt   = isset( $settings['show_excerpt'] ) ? $settings['show_excerpt'] : 'yes';
		$excerpt_len    = ! empty( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 18;
		$show_meta      = isset( $settings['show_meta'] ) ? $settings['show_meta'] : 'yes';
		$show_author    = isset( $settings['show_author'] ) ? $settings['show_author'] : 'yes';
		$show_date      = isset( $settings['show_date'] ) ? $settings['show_date'] : 'yes';
		$show_read_more = isset( $settings['show_read_more'] ) ? $settings['show_read_more'] : 'yes';
		$read_more_text = ! empty( $settings['read_more_text'] ) ? $settings['read_more_text'] : esc_html__( 'Read Story', 'ab-addon' );

		// Categories
		$categories = get_the_category( $post_id );
		$primary_cat = ! empty( $categories ) ? $categories[0] : null;

		// Author Name
		$author_id   = get_post_field( 'post_author', $post_id );
		$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
		?>
		<article class="ab-card-item ab-card-<?php echo esc_attr( $card_style ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
			<div class="ab-card-inner">

				<?php if ( 'yes' === $show_image && has_post_thumbnail( $post_id ) ) : ?>
					<div class="ab-card-media <?php echo esc_attr( $ratio_class ); ?>">
						<a href="<?php echo esc_url( $permalink ); ?>" class="ab-card-image-link">
							<?php the_post_thumbnail( 'large', array( 'class' => 'ab-card-img', 'alt' => esc_attr( $title ) ) ); ?>
						</a>

						<?php if ( 'yes' === $show_badge && $primary_cat ) : ?>
							<div class="ab-card-badge">
								<a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>">
									<?php echo esc_html( $primary_cat->name ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php elseif ( 'yes' === $show_badge && $primary_cat && 'overlay' !== $card_style ) : ?>
					<div class="ab-card-badge-inline">
						<a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>">
							<?php echo esc_html( $primary_cat->name ); ?>
						</a>
					</div>
				<?php endif; ?>

				<div class="ab-card-content">

					<?php if ( 'yes' === $show_meta ) : ?>
						<div class="ab-card-meta">
							<?php if ( 'yes' === $show_author && ! empty( $author_name ) ) : ?>
								<span class="ab-meta-item ab-meta-author">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
									<?php echo esc_html( $author_name ); ?>
								</span>
							<?php endif; ?>

							<?php if ( 'yes' === $show_date ) : ?>
								<span class="ab-meta-item ab-meta-date">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
									<?php echo esc_html( get_the_date( 'M j, Y', $post_id ) ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $show_title ) : ?>
						<<?php echo esc_attr( $title_tag ); ?> class="ab-card-title">
							<a href="<?php echo esc_url( $permalink ); ?>">
								<?php echo esc_html( $title ); ?>
							</a>
						</<?php echo esc_attr( $title_tag ); ?>>
					<?php endif; ?>

					<?php if ( 'yes' === $show_excerpt ) : ?>
						<div class="ab-card-excerpt">
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), $excerpt_len, '...' ) ); ?></p>
						</div>
					<?php endif; ?>

					<?php if ( 'yes' === $show_read_more ) : ?>
						<div class="ab-card-footer">
							<a href="<?php echo esc_url( $permalink ); ?>" class="ab-card-btn">
								<span><?php echo esc_html( $read_more_text ); ?></span>
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
							</a>
						</div>
					<?php endif; ?>

				</div>
			</div>
		</article>
		<?php
	}
}
