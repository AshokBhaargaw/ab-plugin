<?php
/**
 * AB Popup Shortcode
 *
 * Renders popups that were configured in the AB Settings admin page.
 * Usage: [ab_popup id="popup_xxxxxxxx"]
 *
 * This class is completely independent of Elementor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Popup_Shortcode {

	public function __construct() {
		add_shortcode( 'ab_popup', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		// Auto-print popups in the footer according to their "Display On" setting.
		add_action( 'wp_footer', array( $this, 'auto_render_popups' ), 5 );
	}

	/** IDs of popups already printed on this request (prevents duplicates). */
	private $rendered = array();

	/**
	 * Print every saved popup in the footer, honouring "Display On".
	 */
	public function auto_render_popups() {
		if ( is_admin() || ! class_exists( 'AB_Settings_Page' ) ) {
			return;
		}
		$popups = AB_Settings_Page::get_popups();
		if ( empty( $popups ) ) {
			return;
		}
		foreach ( $popups as $popup ) {
			$target = $popup['target_display'] ?? 'sitewide';
			if ( 'homepage' === $target && ! is_front_page() ) {
				continue;
			}
			echo do_shortcode( '[ab_popup id="' . esc_attr( $popup['id'] ) . '"]' ); // phpcs:ignore
		}
	}

	/**
	 * Enqueue popup assets on the frontend if any popups exist.
	 */
	public function maybe_enqueue_assets() {
		// Only enqueue if there is at least one popup stored.
		if ( class_exists( 'AB_Settings_Page' ) ) {
			$popups = AB_Settings_Page::get_popups();
			if ( ! empty( $popups ) ) {
				wp_enqueue_style(
					'ab-popup-style',
					AB_ADDON_URL . 'assets/css/popup.css',
					array(),
					AB_ADDON_VERSION
				);
				wp_enqueue_script(
					'ab-popup-script',
					AB_ADDON_URL . 'assets/js/popup.js',
					array( 'jquery' ),
					AB_ADDON_VERSION,
					true
				);
			}
		}
	}

	/**
	 * Shortcode callback.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string      HTML output.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array( 'id' => '' ),
			$atts,
			'ab_popup'
		);

		$popup_id = sanitize_key( $atts['id'] );

		if ( empty( $popup_id ) || ! class_exists( 'AB_Settings_Page' ) ) {
			return '';
		}

		$popup = AB_Settings_Page::get_popup( $popup_id );

		if ( ! $popup ) {
			return '';
		}

		// Never print the same popup twice (manual shortcode + auto footer).
		if ( in_array( $popup_id, $this->rendered, true ) ) {
			return '';
		}
		$this->rendered[] = $popup_id;

		// Ensure assets are loaded (for shortcodes added after initial enqueue).
		if ( ! wp_script_is( 'ab-popup-script', 'enqueued' ) ) {
			wp_enqueue_style( 'ab-popup-style', AB_ADDON_URL . 'assets/css/popup.css', array(), AB_ADDON_VERSION );
			wp_enqueue_script( 'ab-popup-script', AB_ADDON_URL . 'assets/js/popup.js', array( 'jquery' ), AB_ADDON_VERSION, true );
		}

		ob_start();
		$this->render_popup( $popup );
		return ob_get_clean();
	}

	/**
	 * Renders the full popup HTML for the frontend.
	 *
	 * @param array $popup Popup configuration array.
	 */
	private function render_popup( $popup ) {
		$popup_id         = esc_attr( $popup['id'] );
		$popup_type       = $popup['popup_type'] ?? 'modal';
		$trigger_type     = ( $popup_type === 'exit_intent' ) ? 'exit_intent' : ( $popup['trigger_type'] ?? 'page_load' );
		$content_type     = $popup['content_type'] ?? 'text_only';
		$show_close       = ( $popup['show_close_button'] ?? 'yes' ) === 'yes';
		$close_on_overlay = ( $popup['close_on_overlay'] ?? 'yes' ) === 'yes';
		$show_once        = ( $popup['show_once'] ?? 'yes' ) === 'yes';
		$bg_color         = esc_attr( $popup['bg_color'] ?? '#ffffff' );
		$overlay_color    = esc_attr( $popup['overlay_color'] ?? 'rgba(0,0,0,0.65)' );

		$notif_position = '';
		if ( $popup_type === 'notification' && ! empty( $popup['notification_position'] ) ) {
			$notif_position = 'ab-popup-notif--' . esc_attr( $popup['notification_position'] );
		}

		$image_pos_class = '';
		if ( in_array( $popup_type, array( 'modal', 'exit_intent' ), true )
			&& in_array( $content_type, array( 'text_image', 'text_cta' ), true )
		) {
			$image_pos_class = 'ab-popup-layout--' . esc_attr( $popup['image_position'] ?? 'top' );
		}

		$data_attrs = sprintf(
			'data-popup-id="%s" data-popup-type="%s" data-trigger="%s" data-delay="%s" data-selector="%s" data-scroll="%s" data-show-once="%s" data-close-overlay="%s"',
			esc_attr( 'ab-popup-' . $popup_id ),
			esc_attr( $popup_type ),
			esc_attr( $trigger_type ),
			esc_attr( $popup['trigger_delay'] ?? 2 ),
			esc_attr( $popup['trigger_selector'] ?? '' ),
			esc_attr( $popup['trigger_scroll'] ?? 50 ),
			esc_attr( $show_once ? 'yes' : 'no' ),
			esc_attr( $close_on_overlay ? 'yes' : 'no' )
		);
		?>

		<div class="ab-popup-trigger-wrapper" id="<?php echo esc_attr( 'ab-popup-' . $popup_id . '-wrapper' ); ?>" <?php echo $data_attrs; // phpcs:ignore ?>>

			<?php if ( in_array( $popup_type, array( 'modal', 'exit_intent' ), true ) ) : ?>

				<div class="ab-popup-overlay<?php echo $popup_type === 'exit_intent' ? ' ab-popup-exit-intent' : ''; ?>"
				     id="<?php echo esc_attr( 'ab-popup-' . $popup_id ); ?>"
				     role="dialog"
				     aria-modal="true"
				     aria-hidden="true"
				     style="display:none; --ab-overlay-color:<?php echo $overlay_color; ?>;">
					<div class="ab-popup-box <?php echo esc_attr( $image_pos_class ); ?>" role="document" style="background-color:<?php echo $bg_color; ?>;">
						<?php if ( $show_close ) : ?>
							<button class="ab-popup-close" aria-label="<?php esc_attr_e( 'Close popup', 'ab-addon' ); ?>">&#x2715;</button>
						<?php endif; ?>
						<?php $this->render_popup_inner( $popup, $content_type ); ?>
					</div>
				</div>

			<?php elseif ( $popup_type === 'notification' ) : ?>

				<div class="ab-popup-notification <?php echo esc_attr( $notif_position ); ?>"
				     id="<?php echo esc_attr( 'ab-popup-' . $popup_id ); ?>"
				     role="alert"
				     aria-hidden="true"
				     style="display:none;">
					<div class="ab-popup-box" style="background-color:<?php echo $bg_color; ?>;">
						<?php if ( $show_close ) : ?>
							<button class="ab-popup-close" aria-label="<?php esc_attr_e( 'Dismiss', 'ab-addon' ); ?>">&#x2715;</button>
						<?php endif; ?>
						<?php $this->render_popup_inner( $popup, $content_type ); ?>
					</div>
				</div>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Renders the inner content of the popup box.
	 *
	 * @param array  $popup        Popup settings.
	 * @param string $content_type Selected content type.
	 */
	private function render_popup_inner( $popup, $content_type ) {

		if ( $content_type === 'shortcode' ) {
			$sc = $popup['popup_shortcode'] ?? '';
			if ( ! empty( $sc ) ) {
				echo '<div class="ab-popup-shortcode-wrapper">' . do_shortcode( $sc ) . '</div>';
			} else {
				echo '<div class="ab-popup-shortcode-placeholder">' . esc_html__( 'No shortcode entered.', 'ab-addon' ) . '</div>';
			}
			return;
		}

		if ( $content_type === 'elementor_template' ) {
			$template_id = intval( $popup['elementor_template_id'] ?? 0 );
			if ( ! empty( $template_id ) ) {
				if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
					$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
					$css_file->enqueue();
				}
				if ( class_exists( '\Elementor\Plugin' ) ) {
					echo '<div class="ab-popup-elementor-wrapper">';
					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id ); // phpcs:ignore
					echo '</div>';
				} else {
					echo do_shortcode( '[elementor-template id="' . $template_id . '"]' );
				}
			} else {
				echo '<div class="ab-popup-shortcode-placeholder">' . esc_html__( 'Please select an Elementor template in AB Settings.', 'ab-addon' ) . '</div>';
			}
			return;
		}

		if ( $content_type === 'custom_html' ) {
			$html = $popup['custom_html'] ?? '';
			echo '<div class="ab-popup-custom-html-wrapper">' . do_shortcode( wp_kses_post( $html ) ) . '</div>';
			return;
		}

		// text_only | text_image | text_cta
		$has_image = in_array( $content_type, array( 'text_image', 'text_cta' ), true )
		             && ! empty( $popup['popup_image_url'] );
		$has_cta   = $content_type === 'text_cta';

		if ( $has_image ) : ?>
			<div class="ab-popup-image">
				<img src="<?php echo esc_url( $popup['popup_image_url'] ); ?>"
				     alt="<?php echo esc_attr( $popup['popup_title'] ?? '' ); ?>">
			</div>
		<?php endif; ?>

		<div class="ab-popup-body">
			<?php if ( ! empty( $popup['popup_title'] ) ) : ?>
				<h3 class="ab-popup-title"><?php echo wp_kses_post( $popup['popup_title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( ! empty( $popup['popup_description'] ) ) : ?>
				<p class="ab-popup-description"><?php echo wp_kses_post( $popup['popup_description'] ); ?></p>
			<?php endif; ?>

			<?php if ( $has_cta && ! empty( $popup['cta_text'] ) ) :
				$target   = ( $popup['cta_target'] ?? '_self' ) === '_blank' ? '_blank' : '_self';
				$cta_url  = ! empty( $popup['cta_url'] ) ? $popup['cta_url'] : '#';
				?>
				<a class="ab-popup-cta"
				   href="<?php echo esc_url( $cta_url ); ?>"
				   target="<?php echo esc_attr( $target ); ?>"
				   <?php echo $target === '_blank' ? 'rel="noopener noreferrer"' : ''; ?>>
					<?php echo esc_html( $popup['cta_text'] ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
