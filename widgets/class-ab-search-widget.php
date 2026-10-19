<?php
/**
 * AB Search Elementor Widget
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

class AB_Search_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_search';
	}

	public function get_title() {
		return esc_html__( 'AB Search', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'search', 'form', 'query', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Search Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'   => esc_html__( 'Placeholder', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Search hotels, guides, articles...', 'ab-addon' ),
			)
		);

		$this->add_control(
			'btn_text',
			array(
				'label'   => esc_html__( 'Button Text', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Search', 'ab-addon' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings    = $this->get_settings_for_display();
		$placeholder = ! empty( $settings['placeholder'] ) ? $settings['placeholder'] : __( 'Search...', 'ab-addon' );
		$btn_text    = ! empty( $settings['btn_text'] ) ? $settings['btn_text'] : __( 'Search', 'ab-addon' );
		?>
		<form role="search" method="get" class="ab-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="display: flex; gap: 8px; max-width: 500px; width: 100%;">
			<input type="search" class="ab-search-input" placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo get_search_query(); ?>" name="s" style="flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;" />
			<button type="submit" class="ab-search-submit" style="padding: 10px 20px; background: #6c63ff; color: #ffffff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;"><?php echo esc_html( $btn_text ); ?></button>
		</form>
		<?php
	}
}
