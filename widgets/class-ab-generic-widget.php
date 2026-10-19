<?php
/**
 * AB Generic / Modular Elementor Widget
 *
 * Provides a graceful, functional fallback for any catalog widget toggled on
 * before a dedicated template class is registered.
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

class AB_Generic_Widget extends Widget_Base {

	private $_widget_id;
	private $_widget_title;
	private $_widget_icon;

	public function __construct( $data = array(), $args = null ) {
		$this->_widget_id    = $args['id'] ?? 'ab_generic';
		$this->_widget_title = $args['title'] ?? 'AB Widget';
		$this->_widget_icon  = $args['icon'] ?? 'eicon-code';
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return $this->_widget_id;
	}

	public function get_title() {
		return 'AB ' . $this->_widget_title;
	}

	public function get_icon() {
		return $this->_widget_icon;
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Widget Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'custom_notice',
			array(
				'label'   => esc_html__( 'Heading / Notice', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => $this->_widget_title,
			)
		);

		$this->add_control(
			'custom_description',
			array(
				'label'   => esc_html__( 'Description', 'ab-addon' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => sprintf( esc_html__( '%s is enabled in AB Settings.', 'ab-addon' ), $this->_widget_title ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="ab-generic-widget-card" style="padding: 24px; border: 1px dashed #6c63ff; border-radius: 12px; background: #fdfdff; text-align: center;">
			<h4 style="margin: 0 0 6px 0; font-size: 17px; color: #6c63ff;"><?php echo esc_html( $settings['custom_notice'] ); ?></h4>
			<p style="margin: 0; font-size: 13px; color: #64748b;"><?php echo esc_html( $settings['custom_description'] ); ?></p>
		</div>
		<?php
	}
}
