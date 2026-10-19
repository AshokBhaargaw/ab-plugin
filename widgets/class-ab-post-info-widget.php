<?php
/**
 * AB Post Info Elementor Widget
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

class AB_Post_Info_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_post_info';
	}

	public function get_title() {
		return esc_html__( 'AB Post Info', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-post-info';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'post info', 'author', 'date', 'meta', 'comments', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Metadata Items', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_author',
			array(
				'label'        => esc_html__( 'Author', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_date',
			array(
				'label'        => esc_html__( 'Date', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_comments',
			array(
				'label'        => esc_html__( 'Comments Count', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
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
			'color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-post-info' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="ab-post-info" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; font-size: 13px; font-weight: 500;">
			<?php if ( 'yes' === ( $settings['show_author'] ?? '' ) ) : ?>
				<span class="ab-meta-item ab-meta-author" style="display: inline-flex; align-items: center; gap: 4px;">
					<span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
					<?php echo esc_html( get_the_author() ); ?>
				</span>
			<?php endif; ?>

			<?php if ( 'yes' === ( $settings['show_date'] ?? '' ) ) : ?>
				<span class="ab-meta-item ab-meta-date" style="display: inline-flex; align-items: center; gap: 4px;">
					<span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
					<?php echo esc_html( get_the_date() ); ?>
				</span>
			<?php endif; ?>

			<?php if ( 'yes' === ( $settings['show_comments'] ?? '' ) ) : ?>
				<span class="ab-meta-item ab-meta-comments" style="display: inline-flex; align-items: center; gap: 4px;">
					<span class="dashicons dashicons-format-chat" style="font-size: 16px; width: 16px; height: 16px;"></span>
					<?php comments_number( '0 Comments', '1 Comment', '% Comments' ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php
	}
}
