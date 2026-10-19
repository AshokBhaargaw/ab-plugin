<?php
/**
 * AB Team Member Elementor Widget
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

class AB_Team_Member_Widget extends Widget_Base {

	public function get_name() {
		return 'ab_team_member';
	}

	public function get_title() {
		return esc_html__( 'AB Team Member', 'ab-addon' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return array( 'ab-addons' );
	}

	public function get_keywords() {
		return array( 'team', 'member', 'person', 'profile', 'staff', 'ab' );
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content',
			array(
				'label' => esc_html__( 'Member Info', 'ab-addon' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'image',
			array(
				'label'   => esc_html__( 'Photo', 'ab-addon' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array(
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				),
			)
		);

		$this->add_control(
			'name',
			array(
				'label'   => esc_html__( 'Name', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Ashok Bhaargaw', 'ab-addon' ),
			)
		);

		$this->add_control(
			'role',
			array(
				'label'   => esc_html__( 'Role / Designation', 'ab-addon' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Founder & Lead Guide', 'ab-addon' ),
			)
		);

		$this->add_control(
			'bio',
			array(
				'label'   => esc_html__( 'Short Bio', 'ab-addon' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Dedicated to facilitating smooth, sacred darshan experiences and reliable hotel stays for Ramdevra yatris.', 'ab-addon' ),
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
			'name_color',
			array(
				'label'     => esc_html__( 'Name Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#0f172a',
				'selectors' => array(
					'{{WRAPPER}} .ab-team-name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'role_color',
			array(
				'label'     => esc_html__( 'Role Color', 'ab-addon' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6c63ff',
				'selectors' => array(
					'{{WRAPPER}} .ab-team-role' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$img_url  = ! empty( $settings['image']['url'] ) ? $settings['image']['url'] : '';
		?>
		<div class="ab-team-card" style="background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
			<?php if ( $img_url ) : ?>
				<div class="ab-team-img-wrap" style="height: 240px; overflow: hidden; background: #f8fafc;">
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $settings['name'] ?? '' ); ?>" style="width: 100%; height: 100%; object-fit: cover;">
				</div>
			<?php endif; ?>
			<div class="ab-team-content" style="padding: 20px;">
				<h3 class="ab-team-name" style="margin: 0 0 4px 0; font-size: 18px; font-weight: 700;"><?php echo esc_html( $settings['name'] ?? '' ); ?></h3>
				<div class="ab-team-role" style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;"><?php echo esc_html( $settings['role'] ?? '' ); ?></div>
				<?php if ( ! empty( $settings['bio'] ) ) : ?>
					<p class="ab-team-bio" style="font-size: 14px; line-height: 1.5; color: #64748b; margin: 0;"><?php echo esc_html( $settings['bio'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
