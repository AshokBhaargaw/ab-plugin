<?php
/**
 * AB Nested Slider Widget (Sliding Container)
 *
 * Implements Elementor's native Nested Elements architecture (Widget_Nested_Base)
 * to provide a drag-and-drop Sliding Container where users can insert ANY Elementor
 * widgets directly into each slide right on the visual canvas.
 *
 * No templates or separate pages required.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;

if ( ! class_exists( '\Elementor\Modules\NestedElements\Base\Widget_Nested_Base' ) ) {
	return;
}

class AB_Nested_Slider_Widget extends Widget_Nested_Base {

	public function get_name() {
		return 'ab_nested_slider';
	}

	public function get_title() {
		return esc_html__( 'AB Sliding Container', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-nested-carousel';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'slider', 'carousel', 'nested', 'container', 'slides', 'pro', 'ab' );
	}

	public function get_style_depends() {
		return array( 'swiper', 'e-swiper', 'ab-slider-style' );
	}

	public function get_script_depends() {
		return array( 'swiper', 'ab-slider-script' );
	}

	public function show_in_panel(): bool {
		return true;
	}

	/**
	 * Default child containers created when dropped on canvas.
	 *
	 * @return array
	 */
	protected function get_default_children_elements() {
		return array(
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => esc_html__( 'Slide #1', 'ab-addon' ),
				),
			),
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => esc_html__( 'Slide #2', 'ab-addon' ),
				),
			),
			array(
				'elType'   => 'container',
				'settings' => array(
					'_title' => esc_html__( 'Slide #3', 'ab-addon' ),
				),
			),
		);
	}

	/**
	 * Repeater item setting key that represents the slide title.
	 *
	 * @return string
	 */
	protected function get_default_repeater_title_setting_key() {
		return 'slide_title';
	}

	/**
	 * Title template for new child items.
	 *
	 * @return string
	 */
	protected function get_default_children_title() {
		return esc_html__( 'Slide #%d', 'ab-addon' );
	}

	/**
	 * Selector where child elements are rendered in DOM.
	 *
	 * @return string
	 */
	protected function get_default_children_placeholder_selector() {
		return '.swiper-wrapper';
	}

	/**
	 * Append nested carousel wrapper classes.
	 *
	 * @return string
	 */
	protected function get_html_wrapper_class() {
		return parent::get_html_wrapper_class() . ' elementor-widget-' . $this->get_name();
	}

	protected function register_controls() {

		// ==========================================
		// SECTION: SLIDES (NESTED REPEATER)
		// ==========================================
		$this->start_controls_section(
			'section_slides',
			array(
				'label' => esc_html__( 'Slides', 'ab-addon' ),
			)
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'slide_title',
			array(
				'label'       => esc_html__( 'Slide Name', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Slide', 'ab-addon' ),
				'label_block' => true,
				'dynamic'     => array(
					'active' => true,
				),
			)
		);

		$repeater->add_control(
			'slide_bg_image',
			array(
				'label'   => esc_html__( 'Slide Background Image (Optional)', 'ab-addon' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => '',
				),
			)
		);

		$repeater->add_control(
			'slide_overlay_color',
			array(
				'label'   => esc_html__( 'Overlay Color (Optional)', 'ab-addon' ),
				'type'    => Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$repeater_type = class_exists( '\Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater' )
			? Control_Nested_Repeater::CONTROL_TYPE
			: 'nested-elements-repeater';

		$this->add_control(
			'slides',
			array(
				'label'       => esc_html__( 'Slides List', 'ab-addon' ),
				'type'        => $repeater_type,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'slide_title' => esc_html__( 'Slide #1', 'ab-addon' ),
					),
					array(
						'slide_title' => esc_html__( 'Slide #2', 'ab-addon' ),
					),
					array(
						'slide_title' => esc_html__( 'Slide #3', 'ab-addon' ),
					),
				),
				'title_field' => '{{{ slide_title }}}',
				'button_text' => esc_html__( 'Add New Slide', 'ab-addon' ),
			)
		);

		$this->end_controls_section();

		// ==========================================
		// SECTION: SLIDER SETTINGS
		// ==========================================
		$this->start_controls_section(
			'section_slider_settings',
			array(
				'label' => esc_html__( 'Slider Settings', 'ab-addon' ),
			)
		);

		$this->add_responsive_control(
			'slider_height',
			array(
				'label'      => esc_html__( 'Slider Height (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh', 'auto' ),
				'range'      => array(
					'px' => array(
						'min'  => 150,
						'max'  => 1200,
						'step' => 10,
					),
					'vh' => array(
						'min' => 20,
						'max' => 100,
					),
				),
				'desktop_default' => array(
					'size' => 520,
					'unit' => 'px',
				),
				'tablet_default'  => array(
					'size' => 440,
					'unit' => 'px',
				),
				'mobile_default'  => array(
					'size' => 380,
					'unit' => 'px',
				),
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider-wrap' => '--ab-slide-h: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ab-hero-slider'      => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'slides_per_view',
			array(
				'label'          => esc_html__( 'Slides per View', 'ab-addon' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '1',
				'tablet_default' => '1',
				'mobile_default' => '1',
				'options'        => array(
					'1' => '1 (Full Width Slide)',
					'2' => '2 Slides',
					'3' => '3 Slides',
					'4' => '4 Slides',
				),
			)
		);

		$this->add_responsive_control(
			'space_between',
			array(
				'label'      => esc_html__( 'Space Between Slides (px)', 'ab-addon' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 2,
					),
				),
				'default'    => array(
					'size' => 0,
					'unit' => 'px',
				),
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'   => esc_html__( 'Transition Effect', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => array(
					'slide' => esc_html__( 'Slide (Horizontal)', 'ab-addon' ),
					'fade'  => esc_html__( 'Fade (Crossfade - 1 slide only)', 'ab-addon' ),
				),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Autoplay', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'autoplay_delay',
			array(
				'label'     => esc_html__( 'Autoplay Delay (ms)', 'ab-addon' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'step'      => 500,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'pause_on_hover',
			array(
				'label'        => esc_html__( 'Pause on Hover', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Infinite Loop', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => esc_html__( 'Transition Speed (ms)', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 600,
				'step'    => 50,
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'        => esc_html__( 'Navigation Arrows', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'dots',
			array(
				'label'        => esc_html__( 'Pagination Dots', 'ab-addon' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'ab-addon' ),
				'label_off'    => esc_html__( 'No', 'ab-addon' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ==========================================
		// STYLE TAB: NAVIGATION
		// ==========================================
		$this->start_controls_section(
			'section_style_navigation',
			array(
				'label' => esc_html__( 'Arrows & Dots', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => esc_html__( 'Arrow Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slider-nav' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => esc_html__( 'Arrow Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-slider-nav' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => esc_html__( 'Dot Inactive Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => esc_html__( 'Dot Active Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .ab-hero-slider .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides   = ! empty( $settings['slides'] ) ? $settings['slides'] : array();

		if ( empty( $slides ) ) {
			return;
		}

		$instance_id = 'ab-nested-slider-' . $this->get_id();

		$h_desktop = ! empty( $settings['slider_height']['size'] ) ? intval( $settings['slider_height']['size'] ) : 520;
		$h_tablet  = ! empty( $settings['slider_height_tablet']['size'] ) ? intval( $settings['slider_height_tablet']['size'] ) : 440;
		$h_mobile  = ! empty( $settings['slider_height_mobile']['size'] ) ? intval( $settings['slider_height_mobile']['size'] ) : 380;

		$slides_per_view        = ! empty( $settings['slides_per_view'] ) ? intval( $settings['slides_per_view'] ) : 1;
		$slides_per_view_tablet = ! empty( $settings['slides_per_view_tablet'] ) ? intval( $settings['slides_per_view_tablet'] ) : 1;
		$slides_per_view_mobile = ! empty( $settings['slides_per_view_mobile'] ) ? intval( $settings['slides_per_view_mobile'] ) : 1;
		$space_between          = ! empty( $settings['space_between']['size'] ) ? intval( $settings['space_between']['size'] ) : 0;

		$effect         = ! empty( $settings['effect'] ) ? $settings['effect'] : 'slide';
		$autoplay       = ( isset( $settings['autoplay'] ) && 'yes' === $settings['autoplay'] );
		$autoplay_delay = ! empty( $settings['autoplay_delay'] ) ? intval( $settings['autoplay_delay'] ) : 5000;
		$pause_on_hover = ( ! isset( $settings['pause_on_hover'] ) || 'yes' === $settings['pause_on_hover'] );
		$speed          = ! empty( $settings['speed'] ) ? intval( $settings['speed'] ) : 600;
		$loop           = ( isset( $settings['loop'] ) && 'yes' === $settings['loop'] );
		$arrows         = ( ! isset( $settings['arrows'] ) || 'yes' === $settings['arrows'] );
		$dots           = ( ! isset( $settings['dots'] ) || 'yes' === $settings['dots'] );

		$slider_js_config = array(
			'effect'              => $effect,
			'speed'               => $speed,
			'loop'                => $loop && ( count( $slides ) > 1 ),
			'autoplay'            => $autoplay,
			'delay'               => $autoplay_delay,
			'pauseOnHover'        => $pause_on_hover,
			'arrows'              => $arrows,
			'dots'                => $dots,
			'slidesPerView'       => $slides_per_view,
			'slidesPerViewTablet' => $slides_per_view_tablet,
			'slidesPerViewMobile' => $slides_per_view_mobile,
			'spaceBetween'        => $space_between,
		);

		$style_vars = sprintf(
			'--ab-slide-h-desk: %dpx; --ab-slide-h-tab: %dpx; --ab-slide-h-mob: %dpx;',
			$h_desktop,
			$h_tablet,
			$h_mobile
		);
		$children     = $this->get_children();
		$is_edit_mode = \Elementor\Plugin::$instance->editor->is_edit_mode();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="ab-hero-slider-wrap ab-nested-slider-wrap" style="<?php echo esc_attr( $style_vars ); ?>" data-slider-config="<?php echo esc_attr( wp_json_encode( $slider_js_config ) ); ?>">
			<div class="ab-hero-slider swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $slides as $index => $item ) : ?>
						<?php
						$bg_url  = ! empty( $item['slide_bg_image']['url'] ) ? esc_url( $item['slide_bg_image']['url'] ) : '';
						$overlay = ! empty( $item['slide_overlay_color'] ) ? esc_attr( $item['slide_overlay_color'] ) : '';
						?>
						<div class="swiper-slide ab-hero-slide ab-nested-slide" data-slide-index="<?php echo esc_attr( $index ); ?>">
							<?php if ( $bg_url ) : ?>
								<div class="ab-slide-bg" style="background-image: url('<?php echo $bg_url; ?>');"></div>
							<?php endif; ?>

							<?php if ( $overlay ) : ?>
								<div class="ab-slide-overlay" style="background: <?php echo $overlay; ?>;"></div>
							<?php endif; ?>

							<div class="ab-nested-slide-content">
								<?php $this->print_child( $index ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $arrows && count( $slides ) > 1 ) : ?>
					<button type="button" class="ab-slider-nav ab-slider-prev" aria-label="<?php esc_attr_e( 'Previous', 'ab-addon' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button type="button" class="ab-slider-nav ab-slider-next" aria-label="<?php esc_attr_e( 'Next', 'ab-addon' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				<?php endif; ?>

				<?php if ( $dots && count( $slides ) > 1 ) : ?>
					<div class="ab-slider-pagination swiper-pagination"></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
