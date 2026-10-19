<?php
/**
 * AB Info Card Elementor Widget
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

class AB_Info_Card_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_info_card';
	}

	public function get_title() {
		return esc_html__( 'AB Info Card', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-info-circle-o';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'info card', 'card', 'feature', 'badge', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Card Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'badge_text',
			array(
				'label'   => esc_html__( 'Badge Text', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'FEATURED', 'ab-addon' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Baba Ramdevji Samadhi Mandir', 'ab-addon' ),
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'ab-addon' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Visit the sacred resting place of Baba Ramdevji. Free morning prasad, vip pass booking, and hotel accommodations available.', 'ab-addon' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="ab-info-card" style="padding: 24px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
			<?php if ( ! empty( $settings['badge_text'] ) ) : ?>
				<span class="ab-info-card-badge" style="display: inline-block; padding: 3px 10px; background: #e0e7ff; color: #4338ca; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
					<?php echo esc_html( $settings['badge_text'] ); ?>
				</span>
			<?php endif; ?>

			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $settings['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $settings['description'] ) ) : ?>
				<p style="margin: 0; font-size: 14px; line-height: 1.6; color: #64748b;"><?php echo esc_html( $settings['description'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
