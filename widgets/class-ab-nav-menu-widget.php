<?php
/**
 * AB Navigation Menu Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Nav_Menu_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_nav_menu';
	}

	public function get_title() {
		return esc_html__( 'AB Navigation Menu', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-nav-menu';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'menu', 'navigation', 'header', 'links', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Menu Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$menus = wp_get_nav_menus();
		$menu_options = array();
		foreach ( $menus as $menu ) {
			$menu_options[ $menu->slug ] = $menu->name;
		}

		$this->add_control(
			'menu_slug',
			array(
				'label'   => esc_html__( 'Select Menu', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $menu_options,
				'default' => ! empty( $menu_options ) ? array_key_first( $menu_options ) : '',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( ! empty( $settings['menu_slug'] ) ) {
			wp_nav_menu( array(
				'menu'       => $settings['menu_slug'],
				'container'  => 'nav',
				'menu_class' => 'ab-navigation-menu',
			) );
		} else {
			wp_nav_menu( array(
				'container'  => 'nav',
				'menu_class' => 'ab-navigation-menu',
			) );
		}
	}
}
