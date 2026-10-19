<?php
/**
 * AB Copyright Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Copyright_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_copyright';
	}

	public function get_title() {
		return esc_html__( 'AB Copyright', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'copyright', 'year', 'footer', 'site', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Copyright Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'custom_text',
			array(
				'label'       => esc_html__( 'Custom Text', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'All rights reserved.',
				'placeholder' => 'All rights reserved.',
			)
		);

		$this->add_control(
			'include_year',
			array(
				'label'        => esc_html__( 'Include Auto Current Year', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'include_site_title',
			array(
				'label'        => esc_html__( 'Include Site Title Link', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'ab-addon' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => esc_html__( 'Left', 'ab-addon' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => esc_html__( 'Center', 'ab-addon' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => esc_html__( 'Right', 'ab-addon' ), 'icon' => 'eicon-text-align-right' ),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .ab-copyright' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-copyright' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'copyright_typography',
				'selector' => '{{WRAPPER}} .ab-copyright',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$year     = date( 'Y' );
		$site_url = home_url( '/' );
		$site_name= get_bloginfo( 'name' );

		echo '<div class="ab-copyright" style="font-size: 13px; line-height: 1.5;">';
		echo '&copy; ';
		if ( 'yes' === ( $settings['include_year'] ?? '' ) ) {
			echo esc_html( $year ) . ' ';
		}
		if ( 'yes' === ( $settings['include_site_title'] ?? '' ) ) {
			echo '<a href="' . esc_url( $site_url ) . '" style="color: inherit; text-decoration: none; font-weight: 600;">' . esc_html( $site_name ) . '</a>. ';
		}
		if ( ! empty( $settings['custom_text'] ) ) {
			echo esc_html( $settings['custom_text'] );
		}
		echo '</div>';
	}
}
