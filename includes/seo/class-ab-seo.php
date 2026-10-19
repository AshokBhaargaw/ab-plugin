<?php
/**
 * AB SEO Main Coordinator
 *
 * Core loader for all SEO features in AB Plugin, delivering all Yoast SEO Premium features:
 * - Multiple Focus Keywords & Content Analysis
 * - Google Snippet Simulation (Desktop & Mobile)
 * - Social Previews (Facebook OpenGraph & Twitter Cards)
 * - Advanced Schema.org JSON-LD Graph Framework
 * - Automatic & Custom Redirect Manager (301, 302, 307, 410, 451, Regex)
 * - 404 Error Monitor & Instant Redirector
 * - Internal Linking Suggestions & Link Counter
 * - Orphaned Content Detector & Filter
 * - Cornerstone Content Tagging & Stricter Scoring
 * - XML Sitemaps (with Images)
 * - IndexNow Instant Indexing Protocol
 * - Configurable Breadcrumbs System
 * - Robots Meta & Canonical URL Controls
 * - AI / Smart Title & Meta Description Generator
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load all SEO submodules.
	 */
	private function includes() {
		// Helpers & Breadcrumbs
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-breadcrumbs.php';

		// Redirects Manager & 404 Monitor
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-redirects.php';

		// Internal Linking & Orphaned Content
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-internal-linking.php';

		// XML Sitemap Generator
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-sitemap.php';

		// IndexNow Instant Indexing
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-indexnow.php';

		// Frontend Output Engine (Title, Meta, OpenGraph, Twitter, Schema)
		require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-frontend.php';

		// Post/Page Editor Meta Box (Real-time analysis, Previews, Multiple Keywords)
		if ( is_admin() ) {
			require_once AB_ADDON_PATH . 'includes/seo/class-ab-seo-meta-box.php';
			require_once AB_ADDON_PATH . 'admin/class-ab-seo-settings.php';
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_post_meta_fields' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Register meta fields for Gutenberg REST API compatibility.
	 */
	public function register_post_meta_fields() {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				'_ab_seo_meta',
				array(
					'show_in_rest' => array(
						'schema' => array(
							'type'       => 'object',
							'properties' => array(
								'focus_keywords'   => array( 'type' => 'array' ),
								'seo_title'        => array( 'type' => 'string' ),
								'meta_description' => array( 'type' => 'string' ),
								'canonical_url'    => array( 'type' => 'string' ),
								'noindex'          => array( 'type' => 'boolean' ),
								'nofollow'         => array( 'type' => 'boolean' ),
								'is_cornerstone'   => array( 'type' => 'boolean' ),
							),
						),
					),
					'single'       => true,
					'type'         => 'object',
					'auth_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * Optional frontend styling for breadcrumbs.
	 */
	public function enqueue_frontend_assets() {
		wp_register_style(
			'ab-seo-breadcrumbs',
			AB_ADDON_URL . 'assets/css/ab-seo-frontend.css',
			array(),
			AB_ADDON_VERSION
		);
	}

	/**
	 * Retrieve saved SEO options with defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$defaults = array(
			// General & Knowledge Graph
			'site_type'             => 'Organization',
			'org_name'              => get_bloginfo( 'name' ),
			'org_alternate_name'    => '',
			'org_logo'              => '',
			'person_name'           => '',
			'person_description'    => '',
			'social_facebook'       => '',
			'social_twitter'        => '',
			'social_instagram'      => '',
			'social_linkedin'       => '',
			'social_youtube'        => '',
			'social_pinterest'      => '',

			// Title & Meta Defaults
			'title_separator'       => '—',
			'home_title'            => '%%sitename%% %%sep%% %%tagline%%',
			'home_desc'             => get_bloginfo( 'description' ),
			'post_title_tpl'        => '%%title%% %%sep%% %%sitename%%',
			'post_desc_tpl'         => '%%excerpt%%',
			'page_title_tpl'        => '%%title%% %%sep%% %%sitename%%',
			'page_desc_tpl'         => '%%excerpt%%',

			// OpenGraph & Twitter Defaults
			'og_enable'             => '1',
			'default_share_image'   => '',
			'twitter_enable'        => '1',
			'twitter_card_type'     => 'summary_large_image',

			// Sitemap Settings
			'sitemap_enable'        => '1',
			'sitemap_post_types'    => array( 'post', 'page' ),
			'sitemap_taxonomies'    => array( 'category', 'post_tag' ),
			'sitemap_include_images'=> '1',
			'sitemap_ping_google'   => '1',

			// Redirects & 404
			'auto_redirect_slug'    => '1',
			'auto_redirect_trash'   => '1',
			'monitor_404'           => '1',

			// Breadcrumbs
			'breadcrumbs_enable'    => '1',
			'breadcrumbs_sep'       => '›',
			'breadcrumbs_home'      => 'Home',
			'breadcrumbs_prefix'    => '',

			// IndexNow
			'indexnow_enable'       => '1',
			'indexnow_api_key'      => '',
			'indexnow_auto_submit'  => '1',

			// AI / Smart Generator
			'ai_provider'           => 'builtin',
			'ai_api_key'            => '',
		);

		$options = get_option( 'ab_seo_settings', array() );
		return wp_parse_args( $options, $defaults );
	}

	/**
	 * Helper: replace template variables in title or description.
	 *
	 * @param string  $text
	 * @param WP_Post $post
	 * @return string
	 */
	public static function replace_template_tags( $text, $post = null ) {
		if ( empty( $text ) ) {
			return '';
		}

		$options = self::get_options();
		$sep     = $options['title_separator'] ?? '—';

		$sitename = get_bloginfo( 'name' );
		$tagline  = get_bloginfo( 'description' );

		$title = '';
		$excerpt = '';
		$date = '';
		$category = '';

		if ( $post instanceof WP_Post ) {
			$title = get_the_title( $post );
			$excerpt = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 25 );
			$date = get_the_date( '', $post );
			$cats = get_the_category( $post->ID );
			if ( ! empty( $cats ) ) {
				$category = $cats[0]->name;
			}
		}

		$replacements = array(
			'%%sitename%%' => $sitename,
			'%%tagline%%'  => $tagline,
			'%%sep%%'      => $sep,
			'%%title%%'    => $title,
			'%%excerpt%%'  => $excerpt,
			'%%date%%'     => $date,
			'%%category%%' => $category,
			'%%year%%'     => date_i18n( 'Y' ),
			'%%month%%'    => date_i18n( 'F' ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
	}
}

// Global helper function for Breadcrumbs
if ( ! function_exists( 'ab_breadcrumbs' ) ) {
	function ab_breadcrumbs( $args = array() ) {
		return AB_SEO_Breadcrumbs::render( $args );
	}
}
