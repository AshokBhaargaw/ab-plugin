<?php
/**
 * AB Elementor Template Block for Gutenberg
 *
 * Allows Gutenberg users to insert saved Elementor templates directly into
 * Gutenberg post and page content while storing only the template ID.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AB_Elementor_Template_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register block category.
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );

		// Register block and assets.
		add_action( 'init', array( $this, 'register_block' ) );

		// Register REST API endpoint for template discovery.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register the "AB Addon" Gutenberg block category if not already present.
	 *
	 * @param array                   $categories List of block categories.
	 * @param WP_Block_Editor_Context $context    Current block editor context.
	 * @return array
	 */
	public function register_block_category( $categories, $context = null ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'ab-addon' === $category['slug'] ) {
				return $categories;
			}
		}

		$categories[] = array(
			'slug'  => 'ab-addon',
			'title' => esc_html__( 'AB Addon', 'ab-addon' ),
			'icon'  => null,
		);

		return $categories;
	}

	/**
	 * Register Gutenberg block type and editor assets.
	 */
	public function register_block() {
		// Register editor script.
		wp_register_script(
			'ab-elementor-template-block-editor',
			AB_ADDON_URL . 'blocks/elementor-template/editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-components',
				'wp-block-editor',
				'wp-i18n',
			),
			AB_ADDON_VERSION,
			true
		);

		// Localize data for editor (Elementor status, templates list, admin URLs).
		wp_localize_script(
			'ab-elementor-template-block-editor',
			'abElementorBlockData',
			array(
				'isElementorActive' => self::is_elementor_active(),
				'templates'         => self::get_elementor_templates(),
				'adminTemplatesUrl' => admin_url( 'edit.php?post_type=elementor_library' ),
				'restUrl'           => esc_url_raw( rest_url( 'ab-addon/v1/elementor-templates' ) ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
			)
		);

		// Register editor styles.
		wp_register_style(
			'ab-elementor-template-block-editor-style',
			AB_ADDON_URL . 'blocks/elementor-template/editor.css',
			array( 'wp-edit-blocks' ),
			AB_ADDON_VERSION
		);

		// Register the dynamic block via block.json metadata.
		register_block_type(
			AB_ADDON_PATH . 'blocks/elementor-template',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Register REST API route for searching and listing Elementor templates.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'ab-addon/v1',
			'/elementor-templates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_templates' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST API callback to return published Elementor templates.
	 *
	 * @param WP_REST_Request $request REST request object.
	 * @return WP_REST_Response
	 */
	public function rest_get_templates( $request ) {
		$templates = self::get_elementor_templates();
		return rest_ensure_response( $templates );
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
	 * Retrieve all published Elementor templates with type metadata.
	 *
	 * Supports Container, Section, Flexbox, Grid, Div Block, Page, and other
	 * modern Elementor template formats.
	 *
	 * @return array List of template objects.
	 */
	public static function get_elementor_templates() {
		$templates = array();

		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		if ( empty( $posts ) || ! is_array( $posts ) ) {
			return $templates;
		}

		foreach ( $posts as $post ) {
			$type_label = '';

			// 1. Try Elementor library taxonomy term first.
			$terms = wp_get_post_terms( $post->ID, 'elementor_library_type', array( 'fields' => 'names' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$type_label = $terms[0];
			}

			// 2. If empty, check post meta.
			if ( empty( $type_label ) ) {
				$meta_type = get_post_meta( $post->ID, '_elementor_template_type', true );
				if ( ! empty( $meta_type ) ) {
					$type_label = $meta_type;
				}
			}

			// Format friendly label.
			$formatted_type = self::format_template_type( $type_label );

			// Safe edit with Elementor link.
			$edit_url = '';
			if ( current_user_can( 'edit_post', $post->ID ) ) {
				$edit_url = admin_url( 'post.php?post=' . $post->ID . '&action=elementor' );
			}

			$templates[] = array(
				'id'      => (int) $post->ID,
				'title'   => html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, 'UTF-8' ),
				'type'    => $formatted_type,
				'editUrl' => $edit_url,
			);
		}

		return $templates;
	}

	/**
	 * Convert technical template types to clean display labels.
	 *
	 * @param string $type Raw template type slug.
	 * @return string
	 */
	private static function format_template_type( $type ) {
		if ( empty( $type ) ) {
			return '';
		}

		$map = array(
			'container' => esc_html__( 'Container', 'ab-addon' ),
			'section'   => esc_html__( 'Section', 'ab-addon' ),
			'page'      => esc_html__( 'Page', 'ab-addon' ),
			'div-block' => esc_html__( 'Div Block', 'ab-addon' ),
			'div'       => esc_html__( 'Div Block', 'ab-addon' ),
			'header'    => esc_html__( 'Header', 'ab-addon' ),
			'footer'    => esc_html__( 'Footer', 'ab-addon' ),
			'single'    => esc_html__( 'Single', 'ab-addon' ),
			'archive'   => esc_html__( 'Archive', 'ab-addon' ),
			'popup'     => esc_html__( 'Popup', 'ab-addon' ),
			'widget'    => esc_html__( 'Widget', 'ab-addon' ),
		);

		$key = strtolower( trim( $type ) );
		return isset( $map[ $key ] ) ? $map[ $key ] : ucfirst( $key );
	}

	/**
	 * Dynamic block render callback.
	 *
	 * Fetches the requested Elementor template ID, verifies that it is valid and published,
	 * enqueues necessary Elementor CSS assets, and dynamically renders the content.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block inner content.
	 * @return string Rendered HTML.
	 */
	public function render_block( $attributes, $content = '' ) {
		$template_id = ! empty( $attributes['templateId'] ) ? absint( $attributes['templateId'] ) : 0;

		// If no template selected, output nothing.
		if ( empty( $template_id ) ) {
			return '';
		}

		// Verify Elementor is available.
		if ( ! self::is_elementor_active() ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return sprintf(
					'<div class="ab-elementor-template-notice ab-elementor-template-notice--warning" style="padding:12px 16px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:4px;margin:15px 0;font-size:14px;"><strong>%s:</strong> %s</div>',
					esc_html__( 'AB Elementor Template', 'ab-addon' ),
					esc_html__( 'Elementor is required to use the AB Elementor Template block.', 'ab-addon' )
				);
			}
			return '';
		}

		// Verify that the post exists, is published, and is an elementor_library post type.
		$post = get_post( $template_id );
		if ( ! $post || 'publish' !== $post->post_status || 'elementor_library' !== $post->post_type ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return sprintf(
					'<div class="ab-elementor-template-notice ab-elementor-template-notice--error" style="padding:12px 16px;background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;border-radius:4px;margin:15px 0;font-size:14px;"><strong>%s:</strong> %s</div>',
					esc_html__( 'AB Elementor Template', 'ab-addon' ),
					esc_html__( 'Selected Elementor template is no longer available.', 'ab-addon' )
				);
			}
			return '';
		}

		// Enqueue the specific Elementor template CSS file (contains all container, flex, grid, colors, fonts).
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
			$css_file->enqueue();
		}

		// Render the Elementor template content using Elementor frontend API.
		$template_content = '';
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->frontend ) ) {
			// Passing true ensures inline styles and dependencies are also loaded.
			$template_content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );
		} else {
			$template_content = do_shortcode( '[elementor-template id="' . $template_id . '"]' );
		}

		if ( empty( $template_content ) ) {
			return '';
		}

		// Clean, minimal container wrapper with no hardcoded dimensions, padding, or display constraints.
		return sprintf(
			'<div class="ab-elementor-template-render" data-template-id="%d">%s</div>',
			esc_attr( $template_id ),
			$template_content
		);
	}
}
