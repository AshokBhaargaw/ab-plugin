<?php
/**
 * AB Archive Template Manager
 *
 * Intercepts Custom Post Type archive requests and renders an assigned Elementor
 * template instead of the default theme archive template, while preserving
 * standard headers, footers, Yoast SEO tags, and fallback compatibility.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AB_Archive_Template_Manager {

	/**
	 * Option key storing archive template mappings [ post_type => template_id ].
	 */
	const OPTION_KEY = 'ab_archive_templates';

	/**
	 * Flag to prevent infinite loops during template rendering.
	 *
	 * @var bool
	 */
	private static $is_rendering = false;

	/**
	 * Cached current archive post type.
	 *
	 * @var string|null
	 */
	private static $current_archive_post_type = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook early into template redirection / asset enqueueing.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_archive_template_assets' ), 20 );

		// Filter the archive template.
		add_filter( 'template_include', array( $this, 'filter_archive_template' ), 99 );

		// Astra layout filter: allow full container flexibility for Elementor archives.
		add_filter( 'astra_content_layout', array( $this, 'filter_astra_content_layout' ) );

		// Add body class for styling context.
		add_filter( 'body_class', array( $this, 'add_archive_body_class' ) );
	}

	/**
	 * Check if Elementor is installed and loaded.
	 *
	 * @return bool
	 */
	public static function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Retrieve all configured archive template mappings.
	 *
	 * @return array Array of [ post_type => template_id ].
	 */
	public static function get_archive_templates() {
		$templates = get_option( self::OPTION_KEY, array() );
		return is_array( $templates ) ? $templates : array();
	}

	/**
	 * Get assigned Elementor template ID for a given post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return int Template ID or 0 if none assigned.
	 */
	public static function get_template_id_for_post_type( $post_type ) {
		if ( empty( $post_type ) ) {
			return 0;
		}

		$templates = self::get_archive_templates();
		return isset( $templates[ $post_type ] ) ? absint( $templates[ $post_type ] ) : 0;
	}

	/**
	 * Retrieve list of all public custom post types that support archives.
	 *
	 * Supports Hotel, Travel, Vehicle, Taxi, and any future CPTs dynamically.
	 *
	 * @return array Array of WP_Post_Type objects keyed by post type name.
	 */
	public static function get_supported_post_types() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$supported = array();
		$excluded  = array( 'post', 'page', 'attachment', 'elementor_library' );

		foreach ( $post_types as $slug => $cpt ) {
			if ( in_array( $slug, $excluded, true ) ) {
				continue;
			}

			// Include if post type has an archive enabled.
			if ( ! empty( $cpt->has_archive ) ) {
				$supported[ $slug ] = $cpt;
			}
		}

		/**
		 * Filter the supported post types for Elementor archive templates.
		 *
		 * @param array $supported Array of WP_Post_Type objects.
		 */
		return apply_filters( 'ab_archive_template_supported_post_types', $supported );
	}

	/**
	 * Detect current archive post type reliably.
	 *
	 * @return string|false Post type slug if on CPT archive, false otherwise.
	 */
	public static function get_current_archive_post_type() {
		if ( ! is_null( self::$current_archive_post_type ) ) {
			return self::$current_archive_post_type;
		}

		if ( ! is_post_type_archive() ) {
			self::$current_archive_post_type = false;
			return false;
		}

		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		if ( empty( $post_type ) ) {
			$queried_obj = get_queried_object();
			if ( $queried_obj instanceof WP_Post_Type ) {
				$post_type = $queried_obj->name;
			}
		}

		self::$current_archive_post_type = ! empty( $post_type ) ? sanitize_key( $post_type ) : false;
		return self::$current_archive_post_type;
	}

	/**
	 * Check whether the current request is for a CPT archive that has an active Elementor template.
	 *
	 * @return int Template ID if active and valid, 0 otherwise.
	 */
	public static function get_active_archive_template_id() {
		if ( ! self::is_elementor_active() ) {
			return 0;
		}

		$post_type = self::get_current_archive_post_type();
		if ( ! $post_type ) {
			return 0;
		}

		$template_id = self::get_template_id_for_post_type( $post_type );
		if ( ! $template_id ) {
			return 0;
		}

		// Ensure template exists and is published.
		$post = get_post( $template_id );
		if ( ! $post || 'publish' !== $post->post_status || 'elementor_library' !== $post->post_type ) {
			return 0;
		}

		return $template_id;
	}

	/**
	 * Enqueue Elementor CSS, Kit CSS, and atomic styles early in <head> for the active archive template.
	 */
	public function enqueue_archive_template_assets() {
		$template_id = self::get_active_archive_template_id();
		if ( ! $template_id ) {
			return;
		}

		// Enqueue Elementor base frontend CSS and active Kit CSS.
		self::enqueue_elementor_base_assets();

		// Enqueue template-specific generated CSS.
		self::enqueue_template_css( $template_id );

		// Fire atomic styles for Elementor 4 containers.
		self::register_atomic_styles( $template_id );

		// Enqueue widget styles.
		wp_enqueue_style(
			'ab-hotel-grid-style',
			AB_ADDON_URL . 'assets/css/hotel-grid.css',
			array(),
			AB_ADDON_VERSION
		);
	}

	/**
	 * Filter template_include to load the AB archive wrapper if a template is assigned.
	 *
	 * @param string $template Path to standard template.
	 * @return string Modified template path or original.
	 */
	public function filter_archive_template( $template ) {
		$template_id = self::get_active_archive_template_id();

		if ( ! $template_id ) {
			// Fall back to theme's archive-hotel.php or default archive.
			return $template;
		}

		$wrapper = AB_ADDON_PATH . 'templates/archive-elementor.php';
		if ( file_exists( $wrapper ) ) {
			return $wrapper;
		}

		return $template;
	}

	/**
	 * Filter Astra layout to 'page-builder' for Elementor archive templates so sections stretch cleanly.
	 *
	 * @param string $layout Astra content layout.
	 * @return string
	 */
	public function filter_astra_content_layout( $layout ) {
		if ( self::get_active_archive_template_id() ) {
			return 'page-builder';
		}
		return $layout;
	}

	/**
	 * Add contextual body classes when an Elementor archive template is active.
	 *
	 * @param array $classes Array of body classes.
	 * @return array
	 */
	public function add_archive_body_class( $classes ) {
		$template_id = self::get_active_archive_template_id();
		if ( $template_id ) {
			$post_type = self::get_current_archive_post_type();
			$classes[] = 'ab-elementor-archive';
			$classes[] = 'ab-elementor-archive-' . sanitize_html_class( $post_type );
			$classes[] = 'elementor-page';
			$classes[] = 'elementor-page-' . $template_id;
		}
		return $classes;
	}

	/**
	 * Render the active Elementor archive template.
	 *
	 * Called inside templates/archive-elementor.php. Prevents recursion and loops.
	 *
	 * @return string Rendered HTML.
	 */
	public static function render_active_archive_template() {
		if ( self::$is_rendering ) {
			return '';
		}

		$template_id = self::get_active_archive_template_id();
		if ( ! $template_id ) {
			return '';
		}

		self::$is_rendering = true;

		$content = '';

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
		} else {
			$content = do_shortcode( '[elementor-template id="' . $template_id . '"]' );
		}

		self::$is_rendering = false;

		return $content;
	}

	/**
	 * Elementor base frontend CSS + active Kit CSS.
	 */
	private static function enqueue_elementor_base_assets() {
		static $done = false;
		if ( $done || ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->frontend ) ) {
			return;
		}
		$done = true;

		\Elementor\Plugin::$instance->frontend->enqueue_styles();

		if ( isset( \Elementor\Plugin::$instance->kits_manager ) ) {
			$kit_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();
			if ( $kit_id ) {
				self::enqueue_template_css( $kit_id );
			}
		}
	}

	/**
	 * Enqueue an Elementor post's generated CSS file.
	 *
	 * @param int $post_id Elementor template ID.
	 */
	private static function enqueue_template_css( $post_id ) {
		if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			return;
		}
		$css = \Elementor\Core\Files\CSS\Post::create( $post_id );
		if ( $css ) {
			$css->enqueue();
		}
	}

	/**
	 * Elementor 4 atomic element styles trigger.
	 *
	 * @param int $template_id Elementor template ID.
	 */
	private static function register_atomic_styles( $template_id ) {
		static $done = array();

		$template_id = absint( $template_id );
		if ( ! $template_id || isset( $done[ $template_id ] ) ) {
			return;
		}
		$done[ $template_id ] = true;

		do_action( 'elementor/post/render', $template_id );
	}
}
