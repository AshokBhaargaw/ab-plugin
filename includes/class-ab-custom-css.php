<?php
/**
 * AB Custom CSS for Elementor
 *
 * Adds a Custom CSS section and code editor under the "Advanced" tab
 * for all Elementor elements (widgets, sections, containers, columns, and page settings).
 * Includes separate workspaces for Desktop, Tablet, and Mobile responsive views,
 * and an interactive Element Classes Scanner to assist with CSS selectors.
 *
 * @package   AB_Addon
 * @author    Ashok Bhaargaw
 * @version   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

final class AB_Custom_CSS {

	/**
	 * Single instance of the class.
	 *
	 * @var AB_Custom_CSS|null
	 */
	private static $_instance = null;

	/**
	 * Track elements that have already been parsed in post CSS to prevent duplicate output.
	 *
	 * @var array<string, bool>
	 */
	private static $parsed_elements = array();

	/**
	 * Get the single instance.
	 *
	 * @return AB_Custom_CSS
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register Custom CSS controls in Elementor elements (widgets, sections, containers, columns, page).
		add_action( 'elementor/element/after_section_end', array( $this, 'register_controls' ), 10, 3 );

		// Parse and inject CSS into Elementor generated stylesheets for frontend & preview.
		add_action( 'elementor/element/parse_css', array( $this, 'add_element_custom_css' ), 10, 2 );

		// Parse and inject Page-level Custom CSS into post stylesheet.
		add_action( 'elementor/css-file/post/parse', array( $this, 'add_page_custom_css' ) );

		// Fallback for dynamic/standalone element rendering on frontend.
		add_action( 'elementor/frontend/after_render', array( $this, 'frontend_css_fallback' ) );

		// Enqueue editor assets (live preview script & styles).
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register the Custom CSS control section under the "Advanced" tab.
	 *
	 * Targets 'section_custom_css_pro' (the Elementor Free promo section) and replaces it.
	 *
	 * @param \Elementor\Controls_Stack $element    The Elementor element instance.
	 * @param string                    $section_id Section ID that just ended.
	 * @param array                     $args       Section arguments.
	 */
	public function register_controls( $element, $section_id, $args ) {
		// Prevent registering multiple times on the same element.
		if ( $element->get_controls( 'ab_custom_css' ) ) {
			return;
		}

		// If Elementor Pro is active, Elementor Pro natively provides its own custom CSS module.
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return;
		}

		// In Elementor Free, 'section_custom_css_pro' is registered at the end of TAB_ADVANCED.
		if ( 'section_custom_css_pro' === $section_id ) {
			// Remove the Pro promotion upsell section & notice.
			$element->remove_control( 'section_custom_css_pro' );
			$element->remove_control( 'custom_css_pro' );

			$this->add_custom_css_section( $element );
		}
	}

	/**
	 * Add Custom CSS section, Classes Helper, and Responsive Tabs to the given Elementor element.
	 *
	 * @param \Elementor\Controls_Stack $element The Elementor element.
	 */
	public function add_custom_css_section( $element ) {
		$element->start_controls_section(
			'ab_section_custom_css',
			array(
				'label' => esc_html__( 'Custom CSS', 'ab-addon' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			)
		);

		// 1. Responsive Tabs (Desktop, Tablet, Mobile) at the VERY TOP
		$element->start_controls_tabs( 'ab_custom_css_device_tabs' );

		// 1.1 Desktop Tab
		$element->start_controls_tab(
			'ab_custom_css_desktop_tab',
			array(
				'label' => esc_html__( 'Desktop', 'ab-addon' ),
			)
		);

		$element->add_control(
			'ab_custom_css',
			array(
				'type'        => \Elementor\Controls_Manager::CODE,
				'label'       => esc_html__( 'Desktop CSS', 'ab-addon' ),
				'show_label'  => false,
				'language'    => 'css',
				'rows'        => 14,
				'render_type' => 'ui',
				'placeholder' => "/* " . esc_html__( 'Desktop / All Devices CSS', 'ab-addon' ) . " */\nselector {\n\t\n}",
				'description' => sprintf(
					/* translators: %s: selector tag */
					esc_html__( 'Applies to all screen sizes. Use %s to target this element.', 'ab-addon' ),
					'<code>selector</code>'
				),
			)
		);

		$element->end_controls_tab();

		// 1.2 Tablet Tab
		$element->start_controls_tab(
			'ab_custom_css_tablet_tab',
			array(
				'label' => esc_html__( 'Tablet', 'ab-addon' ),
			)
		);

		$element->add_control(
			'ab_custom_css_tablet',
			array(
				'type'        => \Elementor\Controls_Manager::CODE,
				'label'       => esc_html__( 'Tablet CSS', 'ab-addon' ),
				'show_label'  => false,
				'language'    => 'css',
				'rows'        => 14,
				'render_type' => 'ui',
				'placeholder' => "/* " . esc_html__( 'Tablet CSS (max-width: 1024px)', 'ab-addon' ) . " */\nselector {\n\t\n}",
				'description' => esc_html__( 'Automatically wrapped in @media (max-width: 1024px).', 'ab-addon' ),
			)
		);

		$element->end_controls_tab();

		// 1.3 Mobile Tab
		$element->start_controls_tab(
			'ab_custom_css_mobile_tab',
			array(
				'label' => esc_html__( 'Mobile', 'ab-addon' ),
			)
		);

		$element->add_control(
			'ab_custom_css_mobile',
			array(
				'type'        => \Elementor\Controls_Manager::CODE,
				'label'       => esc_html__( 'Mobile CSS', 'ab-addon' ),
				'show_label'  => false,
				'language'    => 'css',
				'rows'        => 14,
				'render_type' => 'ui',
				'placeholder' => "/* " . esc_html__( 'Mobile CSS (max-width: 767px)', 'ab-addon' ) . " */\nselector {\n\t\n}",
				'description' => esc_html__( 'Automatically wrapped in @media (max-width: 767px).', 'ab-addon' ),
			)
		);

		$element->end_controls_tab();

		$element->end_controls_tabs();

		// 2. Interactive Classes & Selectors Helper Box (Below the code editor)
		$element->add_control(
			'ab_custom_css_classes_helper',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<div class="ab-custom-css-classes-box">'
					. '<div class="ab-classes-header">'
					. '<div class="ab-view-switcher">'
					. '<button type="button" class="ab-view-btn active" data-view="tree"><i class="eicon-tree-view"></i> ' . esc_html__( 'Tree View', 'ab-addon' ) . '</button>'
					. '<button type="button" class="ab-view-btn" data-view="badges"><i class="eicon-tags"></i> ' . esc_html__( 'All Tags & Classes', 'ab-addon' ) . '</button>'
					. '</div>'
					. '<button type="button" class="ab-classes-refresh-btn" title="' . esc_attr__( 'Rescan element structure', 'ab-addon' ) . '"><i class="eicon-sync"></i> ' . esc_html__( 'Rescan', 'ab-addon' ) . '</button>'
					. '</div>'
					. '<div class="ab-classes-hint">' . esc_html__( 'Click any tag, class, or Insert button to write CSS:', 'ab-addon' ) . '</div>'
					. '<div class="ab-classes-content"><div class="ab-classes-loading"><i class="eicon-loading eicon-animation-spin"></i> ' . esc_html__( 'Scanning element structure...', 'ab-addon' ) . '</div></div>'
					. '</div>',
				'content_classes' => 'ab-custom-css-classes-wrapper',
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Replace the 'selector' placeholder in the CSS string with the element's unique CSS selector.
	 * Uses regex lookaround to ensure partial class names (like .my-selector or selector-box) are NOT accidentally replaced.
	 *
	 * @param string $css             The raw CSS code.
	 * @param string $unique_selector The unique CSS selector for the element (e.g. .elementor-element.elementor-element-xxxx).
	 * @return string Processed CSS.
	 */
	public static function parse_selector_keyword( $css, $unique_selector ) {
		if ( empty( $css ) || ! is_string( $css ) ) {
			return '';
		}

		return preg_replace( '/(?<![a-zA-Z0-9_-])selector(?![a-zA-Z0-9_-])/', $unique_selector, $css );
	}

	/**
	 * Retrieve and sanitize custom CSS for a given key.
	 *
	 * @param array  $settings Element settings.
	 * @param string $key      Setting key.
	 * @return string Sanitized CSS.
	 */
	private function get_setting_css( $settings, $key = 'ab_custom_css' ) {
		$css = ! empty( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		$css = trim( $css );

		if ( ! empty( $css ) && ! current_user_can( 'unfiltered_html' ) ) {
			$css = wp_strip_all_tags( $css );
		}

		return $css;
	}

	/**
	 * Compile custom CSS for Desktop, Tablet, and Mobile views.
	 *
	 * @param array  $settings        Element settings.
	 * @param string $unique_selector The unique selector for the element.
	 * @param string $name            Element name.
	 * @param string $id              Element ID.
	 * @return string Compiled CSS.
	 */
	public function compile_element_css( $settings, $unique_selector, $name = '', $id = '' ) {
		$desktop_css = $this->get_setting_css( $settings, 'ab_custom_css' );
		$tablet_css  = $this->get_setting_css( $settings, 'ab_custom_css_tablet' );
		$mobile_css  = $this->get_setting_css( $settings, 'ab_custom_css_mobile' );

		// Fallback: if ab_custom_css is empty, check custom_css from Elementor Pro templates
		if ( empty( $desktop_css ) && ! defined( 'ELEMENTOR_PRO_VERSION' ) && ! empty( $settings['custom_css'] ) ) {
			$desktop_css = trim( (string) $settings['custom_css'] );
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				$desktop_css = wp_strip_all_tags( $desktop_css );
			}
		}

		if ( empty( $desktop_css ) && empty( $tablet_css ) && empty( $mobile_css ) ) {
			return '';
		}

		// Retrieve active breakpoints from Elementor if available
		$tablet_bp = 1024;
		$mobile_bp = 767;

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->breakpoints ) ) {
			$breakpoints = \Elementor\Plugin::$instance->breakpoints->get_breakpoints();
			if ( ! empty( $breakpoints['tablet'] ) ) {
				$tablet_bp = $breakpoints['tablet']->get_value();
			}
			if ( ! empty( $breakpoints['mobile'] ) ) {
				$mobile_bp = $breakpoints['mobile']->get_value();
			}
		}

		$compiled = '';

		// 1. Desktop / All Devices CSS
		if ( ! empty( $desktop_css ) ) {
			$processed = self::parse_selector_keyword( $desktop_css, $unique_selector );
			$compiled .= "/* Desktop / All Devices */\n" . $processed . "\n";
		}

		// 2. Tablet CSS (max-width: $tablet_bp px)
		if ( ! empty( $tablet_css ) ) {
			$processed = self::parse_selector_keyword( $tablet_css, $unique_selector );
			$compiled .= sprintf(
				"/* Tablet View */\n@media (max-width: %dpx) {\n%s\n}\n",
				$tablet_bp,
				$processed
			);
		}

		// 3. Mobile CSS (max-width: $mobile_bp px)
		if ( ! empty( $mobile_css ) ) {
			$processed = self::parse_selector_keyword( $mobile_css, $unique_selector );
			$compiled .= sprintf(
				"/* Mobile View */\n@media (max-width: %dpx) {\n%s\n}\n",
				$mobile_bp,
				$processed
			);
		}

		if ( empty( $compiled ) ) {
			return '';
		}

		return sprintf(
			"/* Start AB Custom CSS for %s (%s) */\n%s/* End AB Custom CSS */\n",
			esc_html( $name ),
			esc_html( $id ),
			$compiled
		);
	}

	/**
	 * Parse and inject element custom CSS into Elementor's generated stylesheet.
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css The Post CSS file instance.
	 * @param \Elementor\Element_Base        $element  The Elementor element instance.
	 */
	public function add_element_custom_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		$unique_selector = method_exists( $element, 'get_unique_selector' )
			? $element->get_unique_selector()
			: '.elementor-element-' . $element->get_id();

		$compiled_css = $this->compile_element_css(
			$element->get_settings(),
			$unique_selector,
			$element->get_name(),
			$element->get_id()
		);

		if ( empty( $compiled_css ) ) {
			return;
		}

		$post_css->get_stylesheet()->add_raw_css( $compiled_css );

		// Mark as parsed so fallback doesn't output duplicate <style> tag.
		self::$parsed_elements[ $element->get_id() ] = true;
	}

	/**
	 * Parse and inject page-level custom CSS into Elementor's post stylesheet.
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css The Post CSS file instance.
	 */
	public function add_page_custom_css( $post_css ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->documents ) ) {
			return;
		}

		$post_id  = $post_css->get_post_id();
		$document = \Elementor\Plugin::$instance->documents->get( $post_id );
		if ( ! $document ) {
			return;
		}

		$page_selector = '.elementor-' . $post_id;
		$compiled_css  = $this->compile_element_css(
			$document->get_settings(),
			$page_selector,
			'Page ' . $post_id,
			(string) $post_id
		);

		if ( empty( $compiled_css ) ) {
			return;
		}

		$post_css->get_stylesheet()->add_raw_css( $compiled_css );
	}

	/**
	 * Frontend fallback for elements rendered dynamically or outside of standard post CSS compilation.
	 *
	 * @param \Elementor\Element_Base $element The Elementor element instance.
	 */
	public function frontend_css_fallback( $element ) {
		// Skip in editor mode (editor JS handles live updates in canvas).
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		$element_id = $element->get_id();

		// If already compiled into the main stylesheet by add_element_custom_css, do nothing.
		if ( ! empty( self::$parsed_elements[ $element_id ] ) ) {
			return;
		}

		$unique_selector = method_exists( $element, 'get_unique_selector' )
			? $element->get_unique_selector()
			: '.elementor-element-' . $element_id;

		$compiled_css = $this->compile_element_css(
			$element->get_settings(),
			$unique_selector,
			$element->get_name(),
			$element_id
		);

		if ( empty( $compiled_css ) ) {
			return;
		}

		printf(
			'<style id="ab-custom-css-%s">%s</style>',
			esc_attr( $element_id ),
			wp_strip_all_tags( $compiled_css )
		);

		self::$parsed_elements[ $element_id ] = true;
	}

	/**
	 * Enqueue assets for the Elementor Editor panel and live preview canvas.
	 */
	public function enqueue_editor_assets() {
		$ver = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : AB_ADDON_VERSION;

		wp_enqueue_style(
			'ab-custom-css-editor',
			AB_ADDON_URL . 'assets/css/ab-custom-css-editor.css',
			array(),
			$ver
		);

		wp_enqueue_script(
			'ab-custom-css-editor',
			AB_ADDON_URL . 'assets/js/ab-custom-css-editor.js',
			array( 'jquery' ),
			$ver,
			true
		);
	}
}
