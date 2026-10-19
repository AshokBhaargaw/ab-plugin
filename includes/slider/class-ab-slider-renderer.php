<?php
/**
 * AB Slider Renderer
 *
 * Core rendering engine for the AB Hero Slider.
 * Used by the [ab_slider] shortcode, the [smartslider3] compatibility alias,
 * the Elementor AB Slider widget, and PHP template tags.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Slider_Renderer {

	/**
	 * WordPress option name where sliders are saved.
	 */
	const OPTION_KEY = 'ab_sliders';

	/**
	 * Retrieve all saved sliders.
	 *
	 * @return array
	 */
	public static function get_sliders() {
		$sliders = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $sliders ) ) {
			$sliders = array();
		}

		// If no sliders exist yet, seed a default Ramdevra hero slider.
		if ( empty( $sliders ) ) {
			$default_slider = self::get_default_seed_slider();
			$sliders[ $default_slider['id'] ] = $default_slider;
			update_option( self::OPTION_KEY, $sliders );
		}

		return $sliders;
	}

	/**
	 * Retrieve a single slider by ID.
	 *
	 * @param string $id Slider identifier.
	 * @return array|null
	 */
	public static function get_slider( $id ) {
		$sliders = self::get_sliders();

		if ( isset( $sliders[ $id ] ) ) {
			return $sliders[ $id ];
		}

		// Also check by numeric index or slug match for compatibility
		foreach ( $sliders as $key => $slider ) {
			if ( (string) $key === (string) $id || ( isset( $slider['slug'] ) && $slider['slug'] === $id ) ) {
				return $slider;
			}
		}

		// Fallback: if numeric id passed (like Smart Slider 3 ID "1"), return first slider
		if ( is_numeric( $id ) && ! empty( $sliders ) ) {
			$values = array_values( $sliders );
			$index  = intval( $id ) - 1;
			if ( isset( $values[ $index ] ) ) {
				return $values[ $index ];
			}
			return reset( $sliders );
		}

		return null;
	}

	/**
	 * Seed data for a default Hero Slider.
	 *
	 * @return array
	 */
	public static function get_default_seed_slider() {
		return array(
			'id'              => 'hero_slider_1',
			'name'            => 'Ramdevra Hero Banner',
			'slug'            => 'hero-banner',
			'height_desktop'  => 560,
			'height_tablet'   => 460,
			'height_mobile'   => 360,
			'effect'          => 'fade', // slide or fade
			'autoplay'        => 'yes',
			'autoplay_delay'  => 5000,
			'pause_on_hover'  => 'yes',
			'speed'           => 800,
			'loop'            => 'yes',
			'arrows'          => 'yes',
			'dots'            => 'yes',
			'content_align'   => 'center',
			'content_max_w'   => 820,
			'slides'          => array(
				array(
					'id'            => 'slide_1',
					'bg_image'      => '',
					'bg_image_url'  => '',
					'bg_mobile_url' => '',
					'overlay_color' => 'rgba(15, 23, 42, 0.55)',
					'badge'         => 'पवित्र तीर्थ स्थल',
					'title'         => 'बाबा रामदेव जी समाधि मंदिर',
					'description'   => 'रामदेवरा (रुणिचा) में आपका हार्दिक स्वागत है। बाबा के दर्शन, आरती समय, होटल एवं यात्रा की सम्पूर्ण जानकारी।',
					'btn_primary_text'   => 'आरती का समय देखें',
					'btn_primary_url'    => '#',
					'btn_primary_target' => '_self',
					'btn_secondary_text'   => 'होटल व धर्मशाला',
					'btn_secondary_url'    => '#',
					'btn_secondary_target' => '_self',
					'slide_link'         => '',
				),
				array(
					'id'            => 'slide_2',
					'bg_image'      => '',
					'bg_image_url'  => '',
					'bg_mobile_url' => '',
					'overlay_color' => 'rgba(15, 23, 42, 0.55)',
					'badge'         => 'सुगम यात्रा एवं दर्शन',
					'title'         => 'रामदेवरा यात्रा गाइड एवं सेवाएँ',
					'description'   => 'रेलवे स्टेशन, बस स्टैंड, टैक्सी सेवा और स्थानीय दर्शनीय स्थलों की विस्तृत जानकारी एक ही स्थान पर।',
					'btn_primary_text'   => 'यात्रा गाइड पढ़ें',
					'btn_primary_url'    => '#',
					'btn_primary_target' => '_self',
					'btn_secondary_text'   => 'टैक्सी बुक करें',
					'btn_secondary_url'    => '#',
					'btn_secondary_target' => '_self',
					'slide_link'         => '',
				),
			),
		);
	}

	/**
	 * Render a slider by ID.
	 *
	 * @param string $slider_id
	 * @return string HTML
	 */
	public static function render_by_id( $slider_id ) {
		$slider = self::get_slider( $slider_id );

		if ( ! $slider ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="ab-slider-error"><p>' . sprintf( esc_html__( 'AB Slider: Slider with ID "%s" not found. Please create or verify it in AB Settings > Sliders.', 'ab-addon' ), esc_html( $slider_id ) ) . '</p></div>';
			}
			return '';
		}

		return self::render( $slider );
	}

	/**
	 * Main renderer method.
	 *
	 * @param array $config Slider configuration array.
	 * @return string HTML
	 */
	public static function render( $config ) {
		if ( empty( $config['slides'] ) || ! is_array( $config['slides'] ) ) {
			return '';
		}

		$instance_id = 'ab-slider-' . ( ! empty( $config['id'] ) ? sanitize_key( $config['id'] ) : uniqid() );

		// Responsive heights
		$h_desktop = ! empty( $config['height_desktop'] ) ? intval( $config['height_desktop'] ) : 560;
		$h_tablet  = ! empty( $config['height_tablet'] ) ? intval( $config['height_tablet'] ) : 460;
		$h_mobile  = ! empty( $config['height_mobile'] ) ? intval( $config['height_mobile'] ) : 360;

		$effect         = ! empty( $config['effect'] ) ? sanitize_key( $config['effect'] ) : 'fade';
		$autoplay       = ( isset( $config['autoplay'] ) && 'yes' === $config['autoplay'] );
		$autoplay_delay = ! empty( $config['autoplay_delay'] ) ? intval( $config['autoplay_delay'] ) : 5000;
		$pause_on_hover = ( ! isset( $config['pause_on_hover'] ) || 'yes' === $config['pause_on_hover'] );
		$speed          = ! empty( $config['speed'] ) ? intval( $config['speed'] ) : 800;
		$loop           = ( ! isset( $config['loop'] ) || 'yes' === $config['loop'] );
		$arrows         = ( ! isset( $config['arrows'] ) || 'yes' === $config['arrows'] );
		$dots           = ( ! isset( $config['dots'] ) || 'yes' === $config['dots'] );
		$content_align  = ! empty( $config['content_align'] ) ? sanitize_key( $config['content_align'] ) : 'center';
		$content_max_w  = ! empty( $config['content_max_w'] ) ? intval( $config['content_max_w'] ) : 820;

		$slides = $config['slides'];
		$slide_count = count( $slides );

		$slides_per_view        = ! empty( $config['slides_per_view'] ) ? intval( $config['slides_per_view'] ) : 1;
		$slides_per_view_tablet = ! empty( $config['slides_per_view_tablet'] ) ? intval( $config['slides_per_view_tablet'] ) : ( $slides_per_view > 2 ? 2 : 1 );
		$slides_per_view_mobile = ! empty( $config['slides_per_view_mobile'] ) ? intval( $config['slides_per_view_mobile'] ) : 1;
		$space_between          = isset( $config['space_between'] ) ? intval( $config['space_between'] ) : 0;

		// Prepare slider JS config object
		$slider_js_config = array(
			'effect'              => $effect,
			'speed'               => $speed,
			'loop'                => $loop && ( $slide_count > 1 ),
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

		// Container CSS custom properties for fluid heights
		$style_vars = sprintf(
			'--ab-slide-h-desk: %dpx; --ab-slide-h-tab: %dpx; --ab-slide-h-mob: %dpx; --ab-slide-max-w: %dpx;',
			$h_desktop,
			$h_tablet,
			$h_mobile,
			$content_max_w
		);

		ob_start();
		?>
		<div id="<?php echo esc_attr( $instance_id ); ?>" class="ab-hero-slider-wrap ab-align-<?php echo esc_attr( $content_align ); ?>" style="<?php echo esc_attr( $style_vars ); ?>" data-slider-config="<?php echo esc_attr( wp_json_encode( $slider_js_config ) ); ?>">
			<div class="ab-hero-slider swiper <?php echo esc_attr( 'ab-effect-' . $effect ); ?>">
				<div class="swiper-wrapper">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<?php
						$content_type  = ! empty( $slide['slide_content_type'] ) ? $slide['slide_content_type'] : 'content';
						$template_id   = ! empty( $slide['template_id'] ) ? intval( $slide['template_id'] ) : 0;

						$bg_url        = ! empty( $slide['bg_image_url'] ) ? esc_url( $slide['bg_image_url'] ) : '';
						$bg_mob_url    = ! empty( $slide['bg_mobile_url'] ) ? esc_url( $slide['bg_mobile_url'] ) : $bg_url;
						$overlay_color = ! empty( $slide['overlay_color'] ) ? esc_attr( $slide['overlay_color'] ) : 'rgba(15, 23, 42, 0.5)';
						$badge         = ! empty( $slide['badge'] ) ? sanitize_text_field( $slide['badge'] ) : '';
						$title         = ! empty( $slide['title'] ) ? wp_kses_post( $slide['title'] ) : '';
						$description   = ! empty( $slide['description'] ) ? wp_kses_post( $slide['description'] ) : '';
						$slide_link    = ! empty( $slide['slide_link'] ) ? esc_url( $slide['slide_link'] ) : '';

						$btn1_text     = ! empty( $slide['btn_primary_text'] ) ? sanitize_text_field( $slide['btn_primary_text'] ) : '';
						$btn1_url      = ! empty( $slide['btn_primary_url'] ) ? esc_url( $slide['btn_primary_url'] ) : '';
						$btn1_target   = ! empty( $slide['btn_primary_target'] ) ? esc_attr( $slide['btn_primary_target'] ) : '_self';

						$btn2_text     = ! empty( $slide['btn_secondary_text'] ) ? sanitize_text_field( $slide['btn_secondary_text'] ) : '';
						$btn2_url      = ! empty( $slide['btn_secondary_url'] ) ? esc_url( $slide['btn_secondary_url'] ) : '';
						$btn2_target   = ! empty( $slide['btn_secondary_target'] ) ? esc_attr( $slide['btn_secondary_target'] ) : '_self';
						?>
						<div class="swiper-slide ab-hero-slide <?php echo ( 'template' === $content_type ) ? 'ab-slide-is-template' : ''; ?>" data-slide-index="<?php echo esc_attr( $index ); ?>">
							<?php if ( ! empty( $slide_link ) ) : ?>
								<a href="<?php echo $slide_link; ?>" class="ab-slide-full-link" aria-label="<?php echo esc_attr( wp_strip_all_tags( $title ) ); ?>"></a>
							<?php endif; ?>

							<!-- Background Layer -->
							<div class="ab-slide-bg" <?php if ( $bg_url ) : ?>style="background-image: url('<?php echo $bg_url; ?>');"<?php endif; ?> <?php if ( $bg_mob_url ) : ?>data-mob-bg="<?php echo $bg_mob_url; ?>"<?php endif; ?>>
								<?php if ( empty( $bg_url ) && 'template' !== $content_type ) : ?>
									<div class="ab-slide-placeholder-gradient"></div>
								<?php endif; ?>
							</div>

							<!-- Dark / Gradient Overlay -->
							<?php if ( ! empty( $bg_url ) || 'content' === $content_type ) : ?>
								<div class="ab-slide-overlay" style="background: <?php echo $overlay_color; ?>;"></div>
							<?php endif; ?>

							<?php if ( 'template' === $content_type ) : ?>
								<!-- Elementor Template Sliding Container -->
								<div class="ab-slide-template-container">
									<?php
									if ( $template_id > 0 && class_exists( '\Elementor\Plugin' ) ) {
										echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id ); // phpcs:ignore
									} else {
										?>
										<div class="ab-slide-template-placeholder">
											<p style="font-size: 16px; margin: 0 0 8px; font-weight: 700;">
												<?php esc_html_e( 'Elementor Template Slide', 'ab-addon' ); ?>
											</p>
											<p style="font-size: 13px; margin: 0; opacity: 0.85;">
												<?php esc_html_e( 'Please select a saved Elementor Template in the slide settings.', 'ab-addon' ); ?>
											</p>
										</div>
										<?php
									}
									?>
								</div>
							<?php else : ?>
								<!-- Standard Hero Content Container -->
								<div class="ab-slide-container">
									<div class="ab-slide-content">
										<?php if ( ! empty( $badge ) ) : ?>
											<div class="ab-slide-badge-wrap">
												<span class="ab-slide-badge"><?php echo esc_html( $badge ); ?></span>
											</div>
										<?php endif; ?>

										<?php if ( ! empty( $title ) ) : ?>
											<h2 class="ab-slide-title"><?php echo $title; ?></h2>
										<?php endif; ?>

										<?php if ( ! empty( $description ) ) : ?>
											<p class="ab-slide-desc"><?php echo $description; ?></p>
										<?php endif; ?>

										<?php if ( ( ! empty( $btn1_text ) && ! empty( $btn1_url ) ) || ( ! empty( $btn2_text ) && ! empty( $btn2_url ) ) ) : ?>
											<div class="ab-slide-buttons">
												<?php if ( ! empty( $btn1_text ) && ! empty( $btn1_url ) ) : ?>
													<a href="<?php echo $btn1_url; ?>" target="<?php echo $btn1_target; ?>" class="ab-btn ab-btn-primary" <?php echo ( '_blank' === $btn1_target ) ? 'rel="noopener noreferrer"' : ''; ?>>
														<span><?php echo esc_html( $btn1_text ); ?></span>
														<svg class="ab-btn-icon" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
													</a>
												<?php endif; ?>

												<?php if ( ! empty( $btn2_text ) && ! empty( $btn2_url ) ) : ?>
													<a href="<?php echo $btn2_url; ?>" target="<?php echo $btn2_target; ?>" class="ab-btn ab-btn-secondary" <?php echo ( '_blank' === $btn2_target ) ? 'rel="noopener noreferrer"' : ''; ?>>
														<span><?php echo esc_html( $btn2_text ); ?></span>
													</a>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $arrows && $slide_count > 1 ) : ?>
					<button type="button" class="ab-slider-nav ab-slider-prev" aria-label="<?php esc_attr_e( 'Previous Slide', 'ab-addon' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
							<polyline points="15 18 9 12 15 6"></polyline>
						</svg>
					</button>
					<button type="button" class="ab-slider-nav ab-slider-next" aria-label="<?php esc_attr_e( 'Next Slide', 'ab-addon' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
							<polyline points="9 18 15 12 9 6"></polyline>
						</svg>
					</button>
				<?php endif; ?>

				<?php if ( $dots && $slide_count > 1 ) : ?>
					<div class="ab-slider-pagination swiper-pagination"></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
