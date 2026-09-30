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

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Basic_Posts_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_basic_posts';
	}

	public function get_title() {
		return esc_html__( 'Basic Posts', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-grid';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'basic posts', 'posts', 'grid', 'blog', 'cards', 'loop', 'uae', 'ab' );
	}

	public function get_style_depends() {
		return array( 'ab-blog-grid-style' );
	}

	public function get_script_depends() {
		return array( 'ab-blog-grid-script' );
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
		// SECTION: LAYOUT
		// ==========================================
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => esc_html__( 'Layout', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'layout_style',
			array(
				'label'   => esc_html__( 'Layout Style', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'    => esc_html__( 'Grid', 'ab-addon' ),
					'masonry' => esc_html__( 'Masonry', 'ab-addon' ),
					'list'    => esc_html__( 'List View', 'ab-addon' ),
				),
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
					'5' => esc_html__( '5 Columns', 'ab-addon' ),
					'6' => esc_html__( '6 Columns', 'ab-addon' ),
				),
				'condition'      => array(
					'layout_style!' => 'list',
				),
				'selectors'      => array(
					'{{WRAPPER}} .ab-basic-posts-grid' => 'display: grid !important; grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
					'{{WRAPPER}} .ab-blog-grid'        => 'display: grid !important; grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
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
				'max'     => 100,
			)
		);

		$this->add_control(
			'card_design',
			array(
				'label'   => esc_html__( 'Card Design Preset', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modern',
				'options' => array(
					'modern'  => esc_html__( 'Modern Shadow Card', 'ab-addon' ),
					'classic' => esc_html__( 'Classic Bordered Card', 'ab-addon' ),
					'overlay' => esc_html__( 'Image Overlay Card', 'ab-addon' ),
					'minimal' => esc_html__( 'Minimal Flat Card', 'ab-addon' ),
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: QUERY
		// ==========================================
		$this->start_controls_section(
			'section_query',
			array(
				'label' => esc_html__( 'Query', 'ab-addon' ),
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
			'categories',
			array(
				'label'       => esc_html__( 'Categories', 'ab-addon' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_categories_options(),
				'description' => esc_html__( 'Filter by specific categories. Leave blank to show all.', 'ab-addon' ),
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
					'menu_order'    => esc_html__( 'Menu Order', 'ab-addon' ),
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
				'label'   => esc_html__( 'Offset', 'ab-addon' ),
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
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: POST STRUCTURE / ELEMENTS
		// ==========================================
		$this->start_controls_section(
			'section_elements',
			array(
				'label' => esc_html__( 'Post Structure & Elements', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		// Featured Image
		$this->add_control(
			'show_image',
			array(
				'label'        => esc_html__( 'Show Featured Image', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'image_aspect_ratio',
			array(
				'label'     => esc_html__( 'Image Aspect Ratio', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ratio-16-9',
				'options'   => array(
					'ratio-16-9' => '16:9 Landscape',
					'ratio-4-3'  => '4:3 Standard',
					'ratio-1-1'  => '1:1 Square',
					'ratio-3-4'  => '3:4 Portrait',
					'ratio-auto' => 'Auto Original',
				),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		$this->add_control(
			'image_hover_effect',
			array(
				'label'     => esc_html__( 'Image Hover Animation', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'zoom-in',
				'options'   => array(
					'none'     => esc_html__( 'None', 'ab-addon' ),
					'zoom-in'  => esc_html__( 'Zoom In', 'ab-addon' ),
					'zoom-out' => esc_html__( 'Zoom Out', 'ab-addon' ),
					'overlay'  => esc_html__( 'Fade Overlay', 'ab-addon' ),
				),
				'condition' => array( 'show_image' => 'yes' ),
			)
		);

		// Badge
		$this->add_control(
			'show_badge',
			array(
				'label'        => esc_html__( 'Show Category Badge', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		// Title
		$this->add_control(
			'show_title',
			array(
				'label'        => esc_html__( 'Show Title', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'title_tag',
			array(
				'label'     => esc_html__( 'Title HTML Tag', 'ab-addon' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'h3',
				'options'   => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'p'    => 'p',
				),
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		// Meta
		$this->add_control(
			'show_meta',
			array(
				'label'        => esc_html__( 'Show Post Meta', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
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
			'show_comments',
			array(
				'label'     => esc_html__( 'Meta: Comments', 'ab-addon' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'no',
				'condition' => array( 'show_meta' => 'yes' ),
			)
		);

		// Excerpt
		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Show Excerpt', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
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

		// Read More Button
		$this->add_control(
			'show_read_more',
			array(
				'label'        => esc_html__( 'Show Read More Button', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'read_more_text',
			array(
				'label'     => esc_html__( 'Read More Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read Article', 'ab-addon' ),
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: FILTER TABS
		// ==========================================
		$this->start_controls_section(
			'section_filter_tabs',
			array(
				'label' => esc_html__( 'Category Filter Tabs', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_filter_tabs',
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
				'condition' => array( 'enable_filter_tabs' => 'yes' ),
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
			'pagination_type',
			array(
				'label'   => esc_html__( 'Pagination Type', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => array(
					'none'           => esc_html__( 'None', 'ab-addon' ),
					'numeric'        => esc_html__( 'Numeric Page Numbers', 'ab-addon' ),
					'ajax_load_more' => esc_html__( 'AJAX Load More Button', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'load_more_text',
			array(
				'label'     => esc_html__( 'Load More Button Text', 'ab-addon' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Load More Posts', 'ab-addon' ),
				'condition' => array( 'pagination_type' => 'ajax_load_more' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: CARD BOX & LAYOUT
		// ==========================================
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => esc_html__( 'Card & Layout', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => esc_html__( 'Grid Gap (Columns & Rows)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-basic-posts-grid' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'tabs_card_style' );

		// Normal Card State
		$this->start_controls_tab(
			'tab_card_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'card_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-item' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .ab-card-item',
			)
		);

		$this->end_controls_tab();

		// Hover Card State
		$this->start_controls_tab(
			'tab_card_hover',
			array( 'label' => esc_html__( 'Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'card_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-card-item:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_hover_box_shadow',
				'selector' => '{{WRAPPER}} .ab-card-item:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'card_border',
				'selector'  => '{{WRAPPER}} .ab-card-item',
				'separator' => 'before',
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => esc_html__( 'Content Padding', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: TITLE
		// ==========================================
		$this->start_controls_section(
			'section_style_title',
			array(
				'label'     => esc_html__( 'Title', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-card-title',
			)
		);

		$this->start_controls_tabs( 'tabs_title_style' );

		$this->start_controls_tab(
			'tab_title_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a202c',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-title, {{WRAPPER}} .ab-card-title a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_hover',
			array( 'label' => esc_html__( 'Hover', 'ab-addon' ) )
		);

		$this->add_control(
			'title_hover_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-title a:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'title_margin_bottom',
			array(
				'label'      => esc_html__( 'Margin Bottom', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 12 ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: BADGE / CATEGORY
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
				'selector' => '{{WRAPPER}} .ab-card-badge a, {{WRAPPER}} .ab-card-badge-inline a',
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-badge a, {{WRAPPER}} .ab-card-badge-inline a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-badge, {{WRAPPER}} .ab-card-badge-inline a' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'ab-addon' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .ab-card-badge, {{WRAPPER}} .ab-card-badge-inline a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: READ MORE BUTTON
		// ==========================================
		$this->start_controls_section(
			'section_style_read_more',
			array(
				'label'     => esc_html__( 'Read More Button', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_read_more' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'read_more_typography',
				'selector' => '{{WRAPPER}} .ab-card-btn',
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
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn' => 'background-color: {{VALUE}};',
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
				'default'   => '#1d4ed8',
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_hover_bg_color',
			array(
				'label'     => esc_html__( 'Background Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-card-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: FILTER TABS
		// ==========================================
		$this->start_controls_section(
			'section_style_filter',
			array(
				'label'     => esc_html__( 'Filter Tabs Style', 'ab-addon' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'enable_filter_tabs' => 'yes' ),
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

		$this->start_controls_tab(
			'tab_filter_normal',
			array( 'label' => esc_html__( 'Normal', 'ab-addon' ) )
		);

		$this->add_control(
			'filter_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#4b5563',
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
				'default'   => '#2563eb',
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

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

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
			$args['offset'] = $offset + ( ( $paged - 1 ) * $ppp );
		}

		if ( 'yes' === $settings['exclude_current'] && is_singular() ) {
			$args['post__not_in'] = array( get_the_ID() );
		}

		if ( ! empty( $settings['categories'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => array_map( 'intval', (array) $settings['categories'] ),
				),
			);
		}

		$query = new WP_Query( $args );

		$grid_classes = array(
			'ab-blog-grid',
			'ab-basic-posts-grid',
			'ab-grid-cols-' . ( isset( $settings['columns'] ) ? $settings['columns'] : '3' ),
			'ab-style-' . ( ! empty( $settings['card_design'] ) ? $settings['card_design'] : 'modern' ),
		);
		if ( ! empty( $settings['layout_style'] ) && 'list' === $settings['layout_style'] ) {
			$grid_classes[] = 'ab-basic-posts-list';
		}

		// Wrapper data settings for AJAX
		$json_settings = array(
			'post_type'      => $post_type,
			'posts_per_page' => $ppp,
			'orderby'        => $orderby,
			'order'          => $order,
			'offset'         => $offset,
			'card_style'     => ! empty( $settings['card_design'] ) ? $settings['card_design'] : 'modern',
			'categories'     => ! empty( $settings['categories'] ) ? (array) $settings['categories'] : array(),
			'show_image'     => isset( $settings['show_image'] ) ? $settings['show_image'] : 'yes',
			'show_badge'     => isset( $settings['show_badge'] ) ? $settings['show_badge'] : 'yes',
			'show_title'     => isset( $settings['show_title'] ) ? $settings['show_title'] : 'yes',
			'title_tag'      => ! empty( $settings['title_tag'] ) ? $settings['title_tag'] : 'h3',
			'show_excerpt'   => isset( $settings['show_excerpt'] ) ? $settings['show_excerpt'] : 'yes',
			'excerpt_length' => ! empty( $settings['excerpt_length'] ) ? intval( $settings['excerpt_length'] ) : 18,
			'show_meta'      => isset( $settings['show_meta'] ) ? $settings['show_meta'] : 'yes',
			'show_author'    => isset( $settings['show_author'] ) ? $settings['show_author'] : 'yes',
			'show_date'      => isset( $settings['show_date'] ) ? $settings['show_date'] : 'yes',
			'show_read_more' => isset( $settings['show_read_more'] ) ? $settings['show_read_more'] : 'yes',
			'read_more_text' => ! empty( $settings['read_more_text'] ) ? $settings['read_more_text'] : esc_html__( 'Read Article', 'ab-addon' ),
			'image_aspect_ratio' => ! empty( $settings['image_aspect_ratio'] ) ? $settings['image_aspect_ratio'] : 'ratio-16-9',
		);
		?>

		<div class="ab-blog-grid-wrapper ab-basic-posts-wrapper" data-settings="<?php echo esc_attr( wp_json_encode( $json_settings ) ); ?>">

			<?php
			// Filter Tabs Header
			if ( 'yes' === $settings['enable_filter_tabs'] ) :
				$selected_cats = ! empty( $settings['categories'] ) ? (array) $settings['categories'] : array();
				$filter_cats   = array();

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

				if ( ! empty( $filter_cats ) && ! is_wp_error( $filter_cats ) ) :
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
						self::render_single_card( $json_settings );
					endwhile;
					wp_reset_postdata();
				else :
					echo '<div class="ab-grid-no-posts"><p>' . esc_html__( 'No posts found.', 'ab-addon' ) . '</p></div>';
				endif;
				?>
			</div>

			<!-- Loader Overlay -->
			<div class="ab-grid-loader" style="display:none;">
				<div class="ab-spinner"></div>
			</div>

			<?php
			// Pagination
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
							<span><?php echo esc_html( ! empty( $settings['load_more_text'] ) ? $settings['load_more_text'] : esc_html__( 'Load More Posts', 'ab-addon' ) ); ?></span>
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
		$read_more_text = ! empty( $settings['read_more_text'] ) ? $settings['read_more_text'] : esc_html__( 'Read Article', 'ab-addon' );

		// Category
		$categories  = get_the_category( $post_id );
		$primary_cat = ! empty( $categories ) ? $categories[0] : null;

		// Author
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
