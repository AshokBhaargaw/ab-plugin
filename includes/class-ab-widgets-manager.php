<?php
/**
 * AB Widgets Manager
 *
 * Central registry and controller for all AB Addon Elementor widgets and features.
 * Manages activation state, provides the complete widget catalog, and dynamically
 * registers active widgets in Elementor.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Widgets_Manager {

	/**
	 * Option key for active widgets array in wp_options.
	 */
	const OPTION_KEY = 'ab_active_widgets';

	/**
	 * Singleton instance.
	 *
	 * @var AB_Widgets_Manager|null
	 */
	private static $_instance = null;

	/**
	 * Cached widget definitions.
	 *
	 * @var array|null
	 */
	private $catalog = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		// Ensure options exist upon first run
		$this->ensure_default_settings();
	}

	/**
	 * Default active widgets:
	 * Matches the screenshot toggled-on items + all already created core AB widgets.
	 */
	public function get_default_active_widgets() {
		return array(
			// Core already created widgets
			'ab_basic_posts'        => true,
			'ab_blog_grid'          => true,
			'ab_hotel_grid'         => true,
			'ab_weather'            => true,
			'ab_slider'             => true,
			'ab_nested_slider'      => true,
			'ab_popup'              => true,

			// Screenshot enabled widgets
			'ab_retina_logo'        => true,
			'ab_page_title'         => true,
			'ab_site_tagline'       => true,
			'ab_site_title'         => true,
			'ab_post_info'          => true,
			'ab_breadcrumbs'        => true,
			'ab_scroll_to_top'      => true,
			'ab_cart'               => true,
			'ab_copyright'          => true,
			'ab_reading_progress'   => true,
			'ab_nav_menu'           => true,
			'ab_site_logo'          => true,
			'ab_info_card'          => true,
			'ab_search'             => true,
			'ab_woo_products_grid'  => true,
			'ab_counter'            => true,
			'ab_duplicator'         => true,
		);
	}

	/**
	 * Ensure option is set with defaults on initial activation.
	 */
	private function ensure_default_settings() {
		$active = get_option( self::OPTION_KEY, null );
		if ( null === $active ) {
			update_option( self::OPTION_KEY, $this->get_default_active_widgets() );
		}
	}

	/**
	 * Master catalog of all widgets/features from the reference dashboard.
	 *
	 * @return array
	 */
	public function get_catalog() {
		if ( ! is_null( $this->catalog ) ) {
			return $this->catalog;
		}

		$this->catalog = array(
			// ==========================================
			// 1. ALREADY CREATED / CORE AB WIDGETS
			// ==========================================
			'ab_basic_posts' => array(
				'id'          => 'ab_basic_posts',
				'title'       => __( 'Basic Posts', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-grid-view',
				'description' => __( 'Flexible post display grid with customizable layout, filters, meta, and responsive styling.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Basic_Posts_Widget',
				'file'        => 'class-ab-basic-posts-widget.php',
			),
			'ab_blog_grid' => array(
				'id'          => 'ab_blog_grid',
				'title'       => __( 'Blog Grid', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-excerpt-view',
				'description' => __( 'Interactive dynamic blog grid with live AJAX category filters, pagination, and slider view.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Blog_Grid_Widget',
				'file'        => 'class-ab-blog-grid-widget.php',
			),
			'ab_hotel_grid' => array(
				'id'          => 'ab_hotel_grid',
				'title'       => __( 'Hotel Grid', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-building',
				'description' => __( 'Dedicated Ramdevra hotel showcase grid with pricing, amenities, ratings, and booking inquiry.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Hotel_Grid_Widget',
				'file'        => 'class-ab-hotel-grid-widget.php',
			),
			'ab_weather' => array(
				'id'          => 'ab_weather',
				'title'       => __( 'Weather', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-cloud',
				'description' => __( 'Real-time Ramdevra weather conditions, hourly temperatures, and multi-day forecast widget.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Weather_Widget',
				'file'        => 'class-ab-weather-widget.php',
			),
			'ab_slider' => array(
				'id'          => 'ab_slider',
				'title'       => __( 'Hero Slider', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-images-alt2',
				'description' => __( 'High-performance banner hero slider with animated badges, dual CTAs, and mobile optimization.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Slider_Widget',
				'file'        => 'class-ab-slider-widget.php',
			),
			'ab_nested_slider' => array(
				'id'          => 'ab_nested_slider',
				'title'       => __( 'Nested Slider', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-slides',
				'description' => __( 'Container-based slider allowing direct drag & drop of any Elementor widgets into individual slides.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Nested_Slider_Widget',
				'file'        => 'class-ab-nested-slider-widget.php',
			),
			'ab_popup' => array(
				'id'          => 'ab_popup',
				'title'       => __( 'Modal Popup', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-external',
				'description' => __( 'Triggerable popup modals, notification bars, exit-intent overlays, and Elementor template popups.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Popup_Widget',
				'file'        => 'class-ab-popup-widget.php',
			),

			// ==========================================
			// 2. SITE & BRANDING WIDGETS
			// ==========================================
			'ab_retina_logo' => array(
				'id'          => 'ab_retina_logo',
				'title'       => __( 'Retina Logo', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-format-image',
				'description' => __( 'Crisp high-DPI logo support with custom 1x, 2x, and 3x retina resolutions and links.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Retina_Logo_Widget',
				'file'        => 'class-ab-retina-logo-widget.php',
			),
			'ab_site_logo' => array(
				'id'          => 'ab_site_logo',
				'title'       => __( 'Site Logo', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-admin-customizer',
				'description' => __( 'Dynamic WordPress site custom logo with fallback text, sizing, and alignment controls.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Site_Logo_Widget',
				'file'        => 'class-ab-site-logo-widget.php',
			),
			'ab_site_title' => array(
				'id'          => 'ab_site_title',
				'title'       => __( 'Site Title', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-admin-site-alt3',
				'description' => __( 'Dynamic site title pulled straight from WordPress settings with customizable typography and tag.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Site_Title_Widget',
				'file'        => 'class-ab-site-title-widget.php',
			),
			'ab_site_tagline' => array(
				'id'          => 'ab_site_tagline',
				'title'       => __( 'Site Tagline', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-editor-quote',
				'description' => __( 'Dynamic site description/tagline with customizable styling, typography, and placement.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Site_Tagline_Widget',
				'file'        => 'class-ab-site-tagline-widget.php',
			),
			'ab_page_title' => array(
				'id'          => 'ab_page_title',
				'title'       => __( 'Page Title', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-media-text',
				'description' => __( 'Dynamic page or post title with prefix, suffix, HTML tag choice, and typography styling.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Page_Title_Widget',
				'file'        => 'class-ab-page-title-widget.php',
			),
			'ab_post_info' => array(
				'id'          => 'ab_post_info',
				'title'       => __( 'Post Info', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-info',
				'description' => __( 'Post metadata bar showing author, publish date, read time, categories, and comment count.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Post_Info_Widget',
				'file'        => 'class-ab-post-info-widget.php',
			),
			'ab_breadcrumbs' => array(
				'id'          => 'ab_breadcrumbs',
				'title'       => __( 'Breadcrumbs', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-arrow-right-alt2',
				'description' => __( 'SEO-optimized breadcrumb navigation trail with schema markup, custom separators, and icons.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Breadcrumbs_Widget',
				'file'        => 'class-ab-breadcrumbs-widget.php',
			),
			'ab_scroll_to_top' => array(
				'id'          => 'ab_scroll_to_top',
				'title'       => __( 'Scroll to Top', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-arrow-up-alt2',
				'description' => __( 'Smooth animated scroll-to-top button with custom scroll offset threshold, position, and icons.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Scroll_To_Top_Widget',
				'file'        => 'class-ab-scroll-to-top-widget.php',
			),
			'ab_copyright' => array(
				'id'          => 'ab_copyright',
				'title'       => __( 'Copyright', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-lock',
				'description' => __( 'Dynamic copyright notice that automatically updates the current year and links to your site.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Copyright_Widget',
				'file'        => 'class-ab-copyright-widget.php',
			),
			'ab_reading_progress' => array(
				'id'          => 'ab_reading_progress',
				'title'       => __( 'Reading Progress Bar', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-leftright',
				'description' => __( 'Sticky animated progress indicator tracking reader progress along blog posts or long pages.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Reading_Progress_Widget',
				'file'        => 'class-ab-reading-progress-widget.php',
			),
			'ab_nav_menu' => array(
				'id'          => 'ab_nav_menu',
				'title'       => __( 'Navigation Menu', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-menu',
				'description' => __( 'Responsive header navigation menu with dropdown indicators, mobile drawer, and animations.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Nav_Menu_Widget',
				'file'        => 'class-ab-nav-menu-widget.php',
			),
			'ab_search' => array(
				'id'          => 'ab_search',
				'title'       => __( 'Search', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-search',
				'description' => __( 'Clean modern search bar widget with live input styling, button icons, and responsive layouts.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Search_Widget',
				'file'        => 'class-ab-search-widget.php',
			),

			// ==========================================
			// 3. CONTENT & TYPOGRAPHY WIDGETS
			// ==========================================
			'ab_dual_color_heading' => array(
				'id'          => 'ab_dual_color_heading',
				'title'       => __( 'Dual Color Heading', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-editor-textcolor',
				'description' => __( 'Stylish dual-color headline with custom gradient highlights, prefixes, suffixes, and typography.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Dual_Color_Heading_Widget',
				'file'        => 'class-ab-dual-color-heading-widget.php',
			),
			'ab_advanced_heading' => array(
				'id'          => 'ab_advanced_heading',
				'title'       => __( 'Advanced Heading', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-heading',
				'description' => __( 'Multi-part heading with subtitle, main title, decorative separator icon/line, and description.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Advanced_Heading_Widget',
				'file'        => 'class-ab-advanced-heading-widget.php',
			),
			'ab_fancy_heading' => array(
				'id'          => 'ab_fancy_heading',
				'title'       => __( 'Fancy Heading', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-art',
				'description' => __( 'Eye-catching animated typing, rotating, and highlighted text effects for hero headlines.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_counter' => array(
				'id'          => 'ab_counter',
				'title'       => __( 'Counter', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-clock',
				'description' => __( 'Animated milestone numbers with prefix, suffix, title, icons, and duration controls.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Counter_Widget',
				'file'        => 'class-ab-counter-widget.php',
			),
			'ab_info_box' => array(
				'id'          => 'ab_info_box',
				'title'       => __( 'Info Box', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-tablet',
				'description' => __( 'Feature box with icon or image, title, body text, button CTA, and smooth hover animation.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Info_Box_Widget',
				'file'        => 'class-ab-info-box-widget.php',
			),
			'ab_info_card' => array(
				'id'          => 'ab_info_card',
				'title'       => __( 'Info Card', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-id',
				'description' => __( 'Compact info card with badge, icon, title, description, and interactive action buttons.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Info_Card_Widget',
				'file'        => 'class-ab-info-card-widget.php',
			),
			'ab_team_member' => array(
				'id'          => 'ab_team_member',
				'title'       => __( 'Team Member', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-groups',
				'description' => __( 'Professional staff profile with photo, name, designation, biography, and social links.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Team_Member_Widget',
				'file'        => 'class-ab-team-member-widget.php',
			),
			'ab_posts' => array(
				'id'          => 'ab_posts',
				'title'       => __( 'Posts', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-admin-post',
				'description' => __( 'Standard post listings supporting query builders, pagination, and multi-column card layouts.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Basic_Posts_Widget', // Handled by basic posts
				'file'        => 'class-ab-basic-posts-widget.php',
			),
			'ab_content_toggle' => array(
				'id'          => 'ab_content_toggle',
				'title'       => __( 'Content Toggle', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-randomize',
				'description' => __( 'Interactive switch between monthly/annual pricing, templates, or comparison views.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_before_after_slider' => array(
				'id'          => 'ab_before_after_slider',
				'title'       => __( 'Before After Slider', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-image-flip-horizontal',
				'description' => __( 'Interactive split slider to compare before and after photographs with draggable handle.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_business_hours' => array(
				'id'          => 'ab_business_hours',
				'title'       => __( 'Business Hours', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-clock',
				'description' => __( 'Clean weekly timetable display highlighting current day and closed statuses.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_business_reviews' => array(
				'id'          => 'ab_business_reviews',
				'title'       => __( 'Business Reviews', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-star-filled',
				'description' => __( 'Showcase client testimonials, star ratings, and Google review cards.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_countdown_timer' => array(
				'id'          => 'ab_countdown_timer',
				'title'       => __( 'Countdown Timer', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-backup',
				'description' => __( 'Live ticking urgency counter for flash sales, event launches, and festivals.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_faq_schema' => array(
				'id'          => 'ab_faq_schema',
				'title'       => __( 'FAQ Schema', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-editor-help',
				'description' => __( 'Accordion FAQ with automatic Google FAQPage JSON-LD rich snippet generation.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_how_to_schema' => array(
				'id'          => 'ab_how_to_schema',
				'title'       => __( 'How-to Schema', 'ab-addon' ),
				'category'    => 'site',
				'icon'        => 'dashicons-list-view',
				'description' => __( 'Step-by-step instructions with images, duration, and Google HowTo rich snippet schema.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_google_map' => array(
				'id'          => 'ab_google_map',
				'title'       => __( 'Google Map', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-location',
				'description' => __( 'Interactive maps with custom marker pins, responsive zooms, and style themes.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_hotspot' => array(
				'id'          => 'ab_hotspot',
				'title'       => __( 'Hotspot', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-marker',
				'description' => __( 'Place interactive tooltips and pulse markers on any promotional image or diagram.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_image_gallery' => array(
				'id'          => 'ab_image_gallery',
				'title'       => __( 'Image Gallery', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-format-gallery',
				'description' => __( 'Masonry and justified image galleries with lightbox and filtering.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_instagram_feed' => array(
				'id'          => 'ab_instagram_feed',
				'title'       => __( 'Instagram Feed', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-camera',
				'description' => __( 'Display curated Instagram photos and reels in a responsive social grid.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_login_form' => array(
				'id'          => 'ab_login_form',
				'title'       => __( 'Login Form', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-admin-users',
				'description' => __( 'Custom styled WordPress frontend login and password reset form.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_marketing_button' => array(
				'id'          => 'ab_marketing_button',
				'title'       => __( 'Marketing Button', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-button',
				'description' => __( 'High-converting call to action button with primary headline and sub-text.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_multi_buttons' => array(
				'id'          => 'ab_multi_buttons',
				'title'       => __( 'Multi Buttons', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-columns',
				'description' => __( 'Group dual or triple CTA buttons with connecting text or separators.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_off_canvas' => array(
				'id'          => 'ab_off_canvas',
				'title'       => __( 'Off - Canvas', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-align-left',
				'description' => __( 'Slide-out sidebar drawer for mobile navigation, filters, or cart panels.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_price_box' => array(
				'id'          => 'ab_price_box',
				'title'       => __( 'Price Box', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-tag',
				'description' => __( 'Modern pricing table card with feature checklists, ribbons, and CTA buttons.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_price_list' => array(
				'id'          => 'ab_price_list',
				'title'       => __( 'Price List', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-editor-ul',
				'description' => __( 'Restaurant menu or hotel service price lists with dotted leader lines.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_retina_image' => array(
				'id'          => 'ab_retina_image',
				'title'       => __( 'Retina Image', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-format-image',
				'description' => __( 'Serve razor sharp high resolution images automatically on Apple retina screens.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_social_share' => array(
				'id'          => 'ab_social_share',
				'title'       => __( 'Social Share', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-share',
				'description' => __( 'One-click social sharing buttons for WhatsApp, Facebook, X, and Pinterest.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_table' => array(
				'id'          => 'ab_table',
				'title'       => __( 'Table', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-editor-table',
				'description' => __( 'Sortable, searchable responsive data comparison tables.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_table_of_contents' => array(
				'id'          => 'ab_table_of_contents',
				'title'       => __( 'Table of Contents', 'ab-addon' ),
				'category'    => 'content',
				'icon'        => 'dashicons-index-card',
				'description' => __( 'Auto-generated clickable table of contents parsed from document headings.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_timeline' => array(
				'id'          => 'ab_timeline',
				'title'       => __( 'Timeline', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-ellipsis',
				'description' => __( 'Vertical or horizontal history and milestone story timeline.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_twitter_feed' => array(
				'id'          => 'ab_twitter_feed',
				'title'       => __( 'Twitter Feed', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-twitter',
				'description' => __( 'Live embedded timeline tweets, hashtags, and profile mentions.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_user_registration' => array(
				'id'          => 'ab_user_registration',
				'title'       => __( 'User Registration Form', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-id-alt',
				'description' => __( 'Frontend user registration and account creation forms.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_video' => array(
				'id'          => 'ab_video',
				'title'       => __( 'Video', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-video-alt3',
				'description' => __( 'Optimized lazy-loading video player with custom play icon and poster.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_video_gallery' => array(
				'id'          => 'ab_video_gallery',
				'title'       => __( 'Video Gallery', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-format-video',
				'description' => __( 'Grid and playlist gallery for YouTube, Vimeo, and self-hosted videos.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_welcome_music' => array(
				'id'          => 'ab_welcome_music',
				'title'       => __( 'Welcome Music', 'ab-addon' ),
				'category'    => 'creative',
				'icon'        => 'dashicons-format-audio',
				'description' => __( 'Atmospheric background music player with play/mute toggle button.', 'ab-addon' ),
				'is_created'  => false,
			),

			// ==========================================
			// 4. WOOCOMMERCE WIDGETS
			// ==========================================
			'ab_cart' => array(
				'id'          => 'ab_cart',
				'title'       => __( 'Cart', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-cart',
				'description' => __( 'Header mini cart button showing item count, total price, and flyout dropdown.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_products_grid' => array(
				'id'          => 'ab_woo_products_grid',
				'title'       => __( 'Woo Products Grid', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-products',
				'description' => __( 'Showcase WooCommerce shop products in a customizable grid with instant cart action.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_add_to_cart' => array(
				'id'          => 'ab_woo_add_to_cart',
				'title'       => __( 'Woo - Add To Cart', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-plus-alt',
				'description' => __( 'Custom styled Add to Cart button with quantity stepper and variation selector.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_categories' => array(
				'id'          => 'ab_woo_categories',
				'title'       => __( 'Woo - Categories', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-category',
				'description' => __( 'Product category cards with thumbnails, product counts, and hover effects.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_checkout' => array(
				'id'          => 'ab_woo_checkout',
				'title'       => __( 'Woo - Checkout', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-money-alt',
				'description' => __( 'Streamlined conversion-focused WooCommerce checkout layout.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_mini_cart' => array(
				'id'          => 'ab_woo_mini_cart',
				'title'       => __( 'Woo - Mini Cart', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-archive',
				'description' => __( 'Sliding off-canvas shopping bag drawer with subtotal and checkout link.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_woo_products' => array(
				'id'          => 'ab_woo_products',
				'title'       => __( 'Woo - Products', 'ab-addon' ),
				'category'    => 'woocommerce',
				'icon'        => 'dashicons-store',
				'description' => __( 'Flexible product carousels, featured product lists, and sale badges.', 'ab-addon' ),
				'is_created'  => false,
			),

			// ==========================================
			// 5. FORM STYLERS
			// ==========================================
			'ab_cf7_styler' => array(
				'id'          => 'ab_cf7_styler',
				'title'       => __( 'Contact Form 7 Styler', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-email-alt',
				'description' => __( 'Design modern Contact Form 7 inputs, labels, radio buttons, and submit buttons.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_gravity_form_styler' => array(
				'id'          => 'ab_gravity_form_styler',
				'title'       => __( 'Gravity Form Styler', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-feedback',
				'description' => __( 'Full visual design control over Gravity Forms without writing custom CSS.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_wp_fluent_forms' => array(
				'id'          => 'ab_wp_fluent_forms',
				'title'       => __( 'WP Fluent Forms Styler', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-forms',
				'description' => __( 'Custom styling for Fluent Forms with borders, focus effects, and typography.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_wpforms_styler' => array(
				'id'          => 'ab_wpforms_styler',
				'title'       => __( 'WPForms Styler', 'ab-addon' ),
				'category'    => 'forms',
				'icon'        => 'dashicons-format-aside',
				'description' => __( 'Easily style WPForms inputs, checkboxes, buttons, and validation notices.', 'ab-addon' ),
				'is_created'  => false,
			),

			// ==========================================
			// 6. EXTENSIONS & UTILITIES
			// ==========================================
			'ab_duplicator' => array(
				'id'          => 'ab_duplicator',
				'title'       => __( 'Duplicator', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-admin-page',
				'description' => __( 'One-click post, page, and hotel duplication tool directly from WordPress admin.', 'ab-addon' ),
				'is_created'  => true,
				'class_name'  => 'AB_Duplicator_Feature',
				'file'        => null, // System feature
			),
			'ab_display_conditions' => array(
				'id'          => 'ab_display_conditions',
				'title'       => __( 'Display Conditions', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-visibility',
				'description' => __( 'Show or hide any Elementor element by user role, operating system, date, or login state.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_particle_backgrounds' => array(
				'id'          => 'ab_particle_backgrounds',
				'title'       => __( 'Particle Backgrounds', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-share-alt2',
				'description' => __( 'Lightweight interactive canvas particles and floating geometric animations.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_party_propz' => array(
				'id'          => 'ab_party_propz',
				'title'       => __( 'Party Propz', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-awards',
				'description' => __( 'Festive decorations and confetti animations for festivals and celebrations.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_shape_divider' => array(
				'id'          => 'ab_shape_divider',
				'title'       => __( 'Shape Divider', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-art',
				'description' => __( 'Curved, zigzag, and wave SVG dividers between sections with invert controls.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_cross_site_copy' => array(
				'id'          => 'ab_cross_site_copy',
				'title'       => __( 'Cross-Site Copy Paste', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-admin-post',
				'description' => __( 'Copy any Elementor section or widget from one domain and paste it into another.', 'ab-addon' ),
				'is_created'  => false,
			),
			'ab_presets' => array(
				'id'          => 'ab_presets',
				'title'       => __( 'Presets', 'ab-addon' ),
				'category'    => 'extensions',
				'icon'        => 'dashicons-screenoptions',
				'description' => __( 'Pre-designed style presets for 1-click aesthetic styling of any widget.', 'ab-addon' ),
				'is_created'  => false,
			),
		);

		return $this->catalog;
	}

	/**
	 * Get array of active widget IDs.
	 *
	 * @return array Map of [ widget_id => bool ]
	 */
	public function get_active_widgets() {
		$active = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $active ) ) {
			return $this->get_default_active_widgets();
		}
		return $active;
	}

	/**
	 * Check if a widget is active.
	 *
	 * @param string $id
	 * @return bool
	 */
	public function is_widget_active( $id ) {
		$active = $this->get_active_widgets();
		return ! empty( $active[ $id ] );
	}

	/**
	 * Update single widget status.
	 *
	 * @param string $id
	 * @param bool $status
	 * @return bool
	 */
	public function set_widget_status( $id, $status ) {
		$active = $this->get_active_widgets();
		if ( $status ) {
			$active[ $id ] = true;
		} else {
			unset( $active[ $id ] );
		}
		return update_option( self::OPTION_KEY, $active );
	}

	/**
	 * Activate all widgets in catalog.
	 */
	public function activate_all() {
		$catalog = $this->get_catalog();
		$active  = array();
		foreach ( $catalog as $id => $data ) {
			$active[ $id ] = true;
		}
		return update_option( self::OPTION_KEY, $active );
	}

	/**
	 * Deactivate all widgets except core essentials.
	 */
	public function deactivate_all() {
		return update_option( self::OPTION_KEY, array() );
	}

	/**
	 * Register active widgets inside Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_active_widgets( $widgets_manager ) {
		$catalog = $this->get_catalog();
		$active  = $this->get_active_widgets();

		// If duplicator is active, initialize it
		if ( ! empty( $active['ab_duplicator'] ) ) {
			require_once AB_ADDON_PATH . 'includes/class-ab-duplicator-feature.php';
			\AB_Duplicator_Feature::init();
		}

		foreach ( $catalog as $id => $widget_data ) {
			// Skip if not active
			if ( empty( $active[ $id ] ) ) {
				continue;
			}

			// Skip feature-only items that are not Elementor widgets
			if ( in_array( $id, array( 'ab_duplicator', 'ab_display_conditions', 'ab_particle_backgrounds', 'ab_cross_site_copy', 'ab_presets' ), true ) ) {
				continue;
			}

			// If widget has a dedicated class file
			if ( ! empty( $widget_data['file'] ) && ! empty( $widget_data['class_name'] ) ) {
				$file_path = AB_ADDON_PATH . 'widgets/' . $widget_data['file'];
				if ( file_exists( $file_path ) ) {
					require_once $file_path;
					$class_name = $widget_data['class_name'];
					if ( class_exists( $class_name ) ) {
						// Special check for nested elements base class
						if ( 'AB_Nested_Slider_Widget' === $class_name && ! class_exists( '\Elementor\Modules\NestedElements\Base\Widget_Nested_Base' ) ) {
							continue;
						}
						$widgets_manager->register( new $class_name() );
						continue;
					}
				}
			}

			// Graceful fallback for newly enabled catalog items
			require_once AB_ADDON_PATH . 'widgets/class-ab-generic-widget.php';
			if ( class_exists( '\AB_Generic_Widget' ) ) {
				$widgets_manager->register( new \AB_Generic_Widget( array(), array(
					'id'    => $id,
					'title' => $widget_data['title'] ?? 'Widget',
					'icon'  => $widget_data['icon'] ?? 'eicon-code',
				) ) );
			}
		}
	}
}
