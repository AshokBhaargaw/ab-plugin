<?php
/**
 * ACF Repeater Integration for AB Addon.
 *
 * Unlocks and enables the Repeater field in ACF Free.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AB_ACF_Repeater Class.
 */
class AB_ACF_Repeater {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register field on acf/init.
		add_action( 'acf/init', array( $this, 'register_repeater_field' ), 5 );

		// Enqueue scripts & styles for field group editor (unlocking Repeater).
		add_action( 'acf/field_group/admin_enqueue_scripts', array( $this, 'field_group_admin_enqueue_scripts' ), 20 );

		// Enqueue scripts & styles for post editing (repeater input & interactions).
		add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'input_admin_enqueue_scripts' ) );

		// Frontend enqueue if ACF form is used on frontend.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );
	}

	/**
	 * Register the Repeater field type if not already registered (e.g. by ACF Pro).
	 */
	public function register_repeater_field() {
		// If Repeater is already registered (e.g. ACF Pro is active), do not override.
		if ( function_exists( 'acf_is_field_type' ) && acf_is_field_type( 'repeater' ) ) {
			return;
		}

		require_once AB_ADDON_PATH . 'includes/class-ab-acf-field-repeater.php';

		if ( function_exists( 'acf_register_field_type' ) && class_exists( 'AB_ACF_Field_Repeater' ) ) {
			acf_register_field_type( 'AB_ACF_Field_Repeater' );
		}
	}

	/**
	 * Enqueue scripts and styles for the ACF Field Group editor.
	 * This unblocks the Repeater field from being marked as PRO-only.
	 */
	public function field_group_admin_enqueue_scripts() {
		// Remove 'repeater' from PROFieldTypes localized data in ACF assets.
		if ( function_exists( 'acf' ) && isset( acf()->assets->data['PROFieldTypes']['repeater'] ) ) {
			unset( acf()->assets->data['PROFieldTypes']['repeater'] );
		}

		wp_enqueue_style(
			'ab-acf-repeater-css',
			AB_ADDON_URL . 'assets/css/ab-acf-repeater.css',
			array( 'acf-input', 'acf-field-group' ),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-acf-repeater-field-group',
			AB_ADDON_URL . 'assets/js/ab-acf-repeater-field-group.js',
			array( 'jquery', 'acf-field-group' ),
			AB_ADDON_VERSION,
			true
		);
	}

	/**
	 * Enqueue scripts and styles for post edit screens where ACF inputs appear.
	 */
	public function input_admin_enqueue_scripts() {
		wp_enqueue_style(
			'ab-acf-repeater-css',
			AB_ADDON_URL . 'assets/css/ab-acf-repeater.css',
			array( 'acf-input' ),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-acf-repeater',
			AB_ADDON_URL . 'assets/js/ab-acf-repeater.js',
			array( 'jquery', 'jquery-ui-sortable', 'acf-input' ),
			AB_ADDON_VERSION,
			true
		);
	}

	/**
	 * Frontend enqueue if acf_form is rendered.
	 */
	public function frontend_enqueue_scripts() {
		if ( wp_script_is( 'acf-input' ) ) {
			$this->input_admin_enqueue_scripts();
		}
	}
}
