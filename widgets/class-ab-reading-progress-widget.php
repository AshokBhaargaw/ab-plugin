<?php
/**
 * AB Reading Progress Bar Elementor Widget
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

class AB_Reading_Progress_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_reading_progress';
	}

	public function get_title() {
		return esc_html__( 'AB Reading Progress Bar', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-navigation-horizontal';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'reading progress', 'scroll progress', 'indicator', 'bar', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Progress Bar Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'position',
			array(
				'label'   => esc_html__( 'Bar Position', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'top'    => esc_html__( 'Top of Viewport', 'ab-addon' ),
					'bottom' => esc_html__( 'Bottom of Viewport', 'ab-addon' ),
				),
				'default' => 'top',
			)
		);

		$this->add_control(
			'bar_height',
			array(
				'label'   => esc_html__( 'Height (px)', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
			)
		);

		$this->add_control(
			'bar_color',
			array(
				'label'     => esc_html__( 'Fill Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$pos      = ( 'bottom' === $settings['position'] ) ? 'bottom: 0;' : 'top: 0;';
		$height   = intval( $settings['bar_height'] ?? 4 );
		$color    = sanitize_text_field( $settings['bar_color'] ?? '#6c63ff' );
		?>
		<div class="ab-reading-progress-track" style="position: fixed; left: 0; width: 100%; <?php echo $pos; ?> height: <?php echo esc_attr( $height ); ?>px; background: rgba(0,0,0,0.05); z-index: 99999; pointer-events: none;">
			<div class="ab-reading-progress-fill" style="height: 100%; width: 0%; background: <?php echo esc_attr( $color ); ?>; transition: width 0.1s linear;"></div>
		</div>
		<script>
		(function(){
			window.addEventListener('scroll', function(){
				var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
				var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
				var scrolled = (height > 0) ? (winScroll / height) * 100 : 0;
				var bar = document.querySelector('.ab-reading-progress-fill');
				if(bar) { bar.style.width = scrolled + '%'; }
			});
		})();
		</script>
		<?php
	}
}
