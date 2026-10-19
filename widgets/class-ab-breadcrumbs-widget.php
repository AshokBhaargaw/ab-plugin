<?php
/**
 * AB Breadcrumbs Elementor Widget
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

class AB_Breadcrumbs_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_breadcrumbs';
	}

	public function get_title() {
		return esc_html__( 'AB Breadcrumbs', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-navigation-horizontal';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'breadcrumbs', 'navigation', 'seo', 'path', 'trail', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Breadcrumb Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'home_text',
			array(
				'label'   => esc_html__( 'Home Text', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Home', 'ab-addon' ),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => esc_html__( 'Separator', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '/',
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
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .ab-breadcrumbs' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Typography & Colors', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'link_color',
			array(
				'label'     => esc_html__( 'Link Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-breadcrumbs a' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'current_color',
			array(
				'label'     => esc_html__( 'Current Page Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-breadcrumbs .current' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'breadcrumbs_typography',
				'selector' => '{{WRAPPER}} .ab-breadcrumbs',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings  = $this->get_settings_for_display();
		$home      = esc_html( $settings['home_text'] ?? 'Home' );
		$separator = '<span class="ab-sep" style="margin: 0 8px; color: #94a3b8;">' . esc_html( $settings['separator'] ?? '/' ) . '</span>';

		echo '<nav class="ab-breadcrumbs" style="font-size: 14px; line-height: 1.5; font-weight: 500;" aria-label="Breadcrumb">';
		echo '<a href="' . esc_url( home_url( '/' ) ) . '" style="text-decoration: none;">' . $home . '</a>';

		if ( is_singular() ) {
			echo $separator;
			$post_type = get_post_type();
			if ( 'post' === $post_type ) {
				$categories = get_the_category();
				if ( ! empty( $categories ) ) {
					echo '<a href="' . esc_url( get_category_link( $categories[0]->term_id ) ) . '" style="text-decoration: none;">' . esc_html( $categories[0]->name ) . '</a>' . $separator;
				}
			} elseif ( 'hotel' === $post_type ) {
				echo '<a href="' . esc_url( home_url( '/hotel/' ) ) . '" style="text-decoration: none;">' . esc_html__( 'Hotels', 'ab-addon' ) . '</a>' . $separator;
			}
			echo '<span class="current" aria-current="page">' . esc_html( get_the_title() ) . '</span>';
		} elseif ( is_archive() ) {
			echo $separator . '<span class="current" aria-current="page">' . esc_html( get_the_archive_title() ) . '</span>';
		} elseif ( is_search() ) {
			echo $separator . '<span class="current" aria-current="page">' . sprintf( esc_html__( 'Search: %s', 'ab-addon' ), get_search_query() ) . '</span>';
		} elseif ( is_404() ) {
			echo $separator . '<span class="current" aria-current="page">' . esc_html__( '404 Not Found', 'ab-addon' ) . '</span>';
		}
		echo '</nav>';
	}
}
