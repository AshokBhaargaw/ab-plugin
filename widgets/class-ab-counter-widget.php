<?php
/**
 * AB Counter Elementor Widget
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

class AB_Counter_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_counter';
	}

	public function get_title() {
		return esc_html__( 'AB Counter', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-counter';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'counter', 'number', 'milestone', 'stats', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Counter Settings', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'starting_number',
			array(
				'label'   => esc_html__( 'Starting Number', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);

		$this->add_control(
			'ending_number',
			array(
				'label'   => esc_html__( 'Ending Number', 'ab-addon' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 50000,
			)
		);

		$this->add_control(
			'prefix',
			array(
				'label'       => esc_html__( 'Number Prefix', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( '+', 'ab-addon' ),
				'default'     => '',
			)
		);

		$this->add_control(
			'suffix',
			array(
				'label'       => esc_html__( 'Number Suffix', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( '+', 'ab-addon' ),
				'default'     => '+',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title / Label', 'ab-addon' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Devotees Welcomed', 'ab-addon' ),
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'ab-addon' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-heart',
					'library' => 'fa-solid',
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => esc_html__( 'Alignment', 'ab-addon' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'ab-addon' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'ab-addon' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'ab-addon' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .ab-counter-card' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// Style: Number
		$this->start_controls_section(
			'section_style_number',
			array(
				'label' => esc_html__( 'Number Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'number_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-counter-number' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'number_typography',
				'selector' => '{{WRAPPER}} .ab-counter-number',
			)
		);

		$this->end_controls_section();

		// Style: Title
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Title Styling', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1e293b',
				'selectors' => array(
					'{{WRAPPER}} .ab-counter-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .ab-counter-title',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$start    = intval( $settings['starting_number'] ?? 0 );
		$end      = intval( $settings['ending_number'] ?? 100 );
		$prefix   = esc_html( $settings['prefix'] ?? '' );
		$suffix   = esc_html( $settings['suffix'] ?? '' );
		$title    = esc_html( $settings['title'] ?? '' );

		?>
		<div class="ab-counter-card" style="padding: 24px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); text-align: center;">
			<?php if ( ! empty( $settings['icon']['value'] ) ) : ?>
				<div class="ab-counter-icon" style="font-size: 32px; color: #6c63ff; margin-bottom: 12px;">
					<?php \Elementor\Icons_Manager::render_icon( $settings['icon'], array( 'aria-hidden' => 'true' ) ); ?>
				</div>
			<?php endif; ?>

			<div class="ab-counter-number-wrap" style="font-size: 38px; font-weight: 800; line-height: 1.1; margin-bottom: 6px;">
				<?php if ( $prefix ) : ?><span class="ab-counter-prefix"><?php echo $prefix; ?></span><?php endif; ?>
				<span class="ab-counter-number" data-target="<?php echo esc_attr( $end ); ?>"><?php echo esc_html( number_format_i18n( $end ) ); ?></span>
				<?php if ( $suffix ) : ?><span class="ab-counter-suffix"><?php echo $suffix; ?></span><?php endif; ?>
			</div>

			<?php if ( $title ) : ?>
				<div class="ab-counter-title" style="font-size: 15px; font-weight: 600; color: #64748b;"><?php echo $title; ?></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
