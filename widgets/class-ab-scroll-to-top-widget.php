<?php
/**
 * AB Scroll to Top Elementor Widget
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

class AB_Scroll_To_Top_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_scroll_to_top';
	}

	public function get_title() {
		return esc_html__( 'AB Scroll to Top', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-arrow-up';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'scroll to top', 'back to top', 'scroll', 'floating button', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Button Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'ab-addon' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-chevron-up',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_control(
			'position',
			array(
				'label'   => esc_html__( 'Position', 'ab-addon' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'bottom-right' => esc_html__( 'Bottom Right', 'ab-addon' ),
					'bottom-left'  => esc_html__( 'Bottom Left', 'ab-addon' ),
				),
				'default' => 'bottom-right',
			)
		);

		$this->add_control(
			'scroll_offset',
			array(
				'label'   => esc_html__( 'Show after scrolling (px)', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 300,
			)
		);

		$this->end_controls_section();

		// Style
		$this->start_controls_section(
			'section_style',
			array(
				'label' => esc_html__( 'Style', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => esc_html__( 'Button Background', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-scroll-top-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => esc_html__( 'Icon Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .ab-scroll-top-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$pos      = ( 'bottom-left' === $settings['position'] ) ? 'left: 24px;' : 'right: 24px;';
		$offset   = intval( $settings['scroll_offset'] ?? 300 );
		?>
		<button class="ab-scroll-top-btn" data-offset="<?php echo esc_attr( $offset ); ?>" style="position: fixed; bottom: 24px; <?php echo $pos; ?> width: 44px; height: 44px; border-radius: 50%; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 9999; transition: opacity 0.3s, transform 0.2s;" aria-label="<?php esc_attr_e( 'Scroll to Top', 'ab-addon' ); ?>" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
			<?php \Elementor\Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
		</button>
		<script>
		(function(){
			var btn = document.querySelector('.ab-scroll-top-btn');
			if(!btn) return;
			var offset = parseInt(btn.getAttribute('data-offset')) || 300;
			btn.style.opacity = '0';
			btn.style.pointerEvents = 'none';
			window.addEventListener('scroll', function(){
				if(window.pageYOffset > offset){
					btn.style.opacity = '1';
					btn.style.pointerEvents = 'auto';
				} else {
					btn.style.opacity = '0';
					btn.style.pointerEvents = 'none';
				}
			});
		})();
		</script>
		<?php
	}
}
