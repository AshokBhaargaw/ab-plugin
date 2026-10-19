<?php
/**
 * AB Info Box Elementor Widget
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! class_exists( 'Elementor\Widget_Base' ) ) {
	return;
}

class AB_Info_Box_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_info_box';
	}

	public function get_title() {
		return esc_html__( 'AB Info Box', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-info-box';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'info box', 'feature', 'icon box', 'services', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Info Box Content', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'ab-addon' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-map-marker-alt',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Ramdevra Darshan Guide', 'ab-addon' ),
				'dynamic' => array( 'active' => true ),
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'ab-addon' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Complete itinerary, temple darshan timings, aarti schedule, and verified travel directions for pilgrims.', 'ab-addon' ),
			)
		);

		$this->add_control(
			'btn_text',
			array(
				'label'   => esc_html__( 'Button Text', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Learn More', 'ab-addon' ),
			)
		);

		$this->add_control(
			'btn_link',
			array(
				'label'       => esc_html__( 'Button Link', 'ab-addon' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://ramdevraa.in',
			)
		);

		$this->end_controls_section();

		// Style Section
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Icon Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-infobox-icon' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Title Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0f172a',
				'selectors' => array(
					'{{WRAPPER}} .ab-infobox-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'desc_color',
			array(
				'label'     => esc_html__( 'Description Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#64748b',
				'selectors' => array(
					'{{WRAPPER}} .ab-infobox-desc' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
		<div class="ab-infobox-card" style="padding: 28px; background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; transition: transform 0.25s, box-shadow 0.25s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
			<?php if ( ! empty( $settings['icon']['value'] ) ) : ?>
				<div class="ab-infobox-icon" style="font-size: 32px; margin-bottom: 16px;">
					<?php \Elementor\Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $settings['title'] ) ) : ?>
				<h3 class="ab-infobox-title" style="font-size: 19px; font-weight: 700; margin: 0 0 10px 0;"><?php echo esc_html( $settings['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $settings['description'] ) ) : ?>
				<p class="ab-infobox-desc" style="font-size: 14px; line-height: 1.6; margin: 0 0 18px 0;"><?php echo esc_html( $settings['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $settings['btn_text'] ) && ! empty( $settings['btn_link']['url'] ) ) : ?>
				<a href="<?php echo esc_url( $settings['btn_link']['url'] ); ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: 14px; color: #6c63ff; text-decoration: none;">
					<?php echo esc_html( $settings['btn_text'] ); ?> &rarr;
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
