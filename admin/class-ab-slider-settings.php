<?php
/**
 * AB Slider Admin Settings Page
 *
 * Dedicated slider management dashboard under AB Settings > Sliders.
 * Allows creating, editing, re-ordering, duplicating, and deleting Hero Banner Sliders.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/slider/class-ab-slider-renderer.php';

class AB_Slider_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register submenu under AB Settings.
	 */
	public function register_submenu() {
		add_submenu_page(
			'ab-settings',
			__( 'Sliders', 'ab-addon' ),
			__( 'Sliders', 'ab-addon' ),
			'manage_options',
			'ab-slider-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts & styles.
	 *
	 * @param string $hook Page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ab-slider-settings' ) === false ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style(
			'ab-admin-settings',
			AB_ADDON_URL . 'assets/css/admin-settings.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-admin-slider-js',
			AB_ADDON_URL . 'assets/js/admin-slider.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);
	}

	/**
	 * Handle save, duplicate, and delete actions.
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['ab_slider_nonce'] ) && ! isset( $_GET['ab_slider_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage sliders.', 'ab-addon' ) );
		}

		// Handle GET actions (Delete / Duplicate)
		if ( isset( $_GET['ab_slider_action'] ) ) {
			$action    = sanitize_key( $_GET['ab_slider_action'] );
			$slider_id = sanitize_key( $_GET['slider_id'] ?? '' );
			$nonce     = sanitize_text_field( $_GET['_wpnonce'] ?? '' );

			if ( ! wp_verify_nonce( $nonce, 'ab_slider_action_' . $slider_id ) ) {
				wp_die( esc_html__( 'Security check failed.', 'ab-addon' ) );
			}

			$sliders = AB_Slider_Renderer::get_sliders();

			if ( 'delete' === $action && isset( $sliders[ $slider_id ] ) ) {
				unset( $sliders[ $slider_id ] );
				update_option( AB_Slider_Renderer::OPTION_KEY, $sliders );
				wp_safe_redirect( add_query_arg( array( 'page' => 'ab-slider-settings', 'ab_msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			if ( 'duplicate' === $action && isset( $sliders[ $slider_id ] ) ) {
				$copy = $sliders[ $slider_id ];
				$new_id = 'slider_' . substr( md5( uniqid() ), 0, 8 );
				$copy['id'] = $new_id;
				$copy['name'] = $copy['name'] . ' (Copy)';
				$sliders[ $new_id ] = $copy;
				update_option( AB_Slider_Renderer::OPTION_KEY, $sliders );
				wp_safe_redirect( add_query_arg( array( 'page' => 'ab-slider-settings', 'ab_msg' => 'duplicated' ), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// Handle POST action (Save Slider)
		if ( isset( $_POST['ab_slider_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_slider_nonce'] ) ), 'ab_slider_save' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'ab-addon' ) );
			}

			$slider_id = sanitize_key( wp_unslash( $_POST['slider_id'] ?? '' ) );
			if ( empty( $slider_id ) ) {
				$slider_id = 'slider_' . substr( md5( uniqid() ), 0, 8 );
			}

			$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? 'Hero Slider' ) );
			$slug = sanitize_title( wp_unslash( $_POST['slug'] ?? $name ) );

			// Parse slides
			$raw_slides = isset( $_POST['slides'] ) && is_array( $_POST['slides'] ) ? wp_unslash( $_POST['slides'] ) : array();
			$clean_slides = array();

			foreach ( $raw_slides as $slide ) {
				$clean_slides[] = array(
					'id'                   => sanitize_key( $slide['id'] ?? uniqid() ),
					'bg_image_url'         => esc_url_raw( $slide['bg_image_url'] ?? '' ),
					'bg_mobile_url'        => esc_url_raw( $slide['bg_mobile_url'] ?? '' ),
					'overlay_color'        => sanitize_text_field( $slide['overlay_color'] ?? 'rgba(15, 23, 42, 0.55)' ),
					'badge'                => sanitize_text_field( $slide['badge'] ?? '' ),
					'title'                => wp_kses_post( $slide['title'] ?? '' ),
					'description'          => wp_kses_post( $slide['description'] ?? '' ),
					'btn_primary_text'     => sanitize_text_field( $slide['btn_primary_text'] ?? '' ),
					'btn_primary_url'      => esc_url_raw( $slide['btn_primary_url'] ?? '' ),
					'btn_primary_target'   => sanitize_key( $slide['btn_primary_target'] ?? '_self' ),
					'btn_secondary_text'   => sanitize_text_field( $slide['btn_secondary_text'] ?? '' ),
					'btn_secondary_url'    => esc_url_raw( $slide['btn_secondary_url'] ?? '' ),
					'btn_secondary_target' => sanitize_key( $slide['btn_secondary_target'] ?? '_self' ),
					'slide_link'           => esc_url_raw( $slide['slide_link'] ?? '' ),
				);
			}

			$slider_data = array(
				'id'              => $slider_id,
				'name'            => $name,
				'slug'            => $slug,
				'height_desktop'  => intval( $_POST['height_desktop'] ?? 560 ),
				'height_tablet'   => intval( $_POST['height_tablet'] ?? 460 ),
				'height_mobile'   => intval( $_POST['height_mobile'] ?? 360 ),
				'effect'          => sanitize_key( $_POST['effect'] ?? 'fade' ),
				'autoplay'        => isset( $_POST['autoplay'] ) && 'yes' === $_POST['autoplay'] ? 'yes' : 'no',
				'autoplay_delay'  => intval( $_POST['autoplay_delay'] ?? 5000 ),
				'pause_on_hover'  => isset( $_POST['pause_on_hover'] ) && 'yes' === $_POST['pause_on_hover'] ? 'yes' : 'no',
				'speed'           => intval( $_POST['speed'] ?? 800 ),
				'loop'            => isset( $_POST['loop'] ) && 'yes' === $_POST['loop'] ? 'yes' : 'no',
				'arrows'          => isset( $_POST['arrows'] ) && 'yes' === $_POST['arrows'] ? 'yes' : 'no',
				'dots'            => isset( $_POST['dots'] ) && 'yes' === $_POST['dots'] ? 'yes' : 'no',
				'content_align'   => sanitize_key( $_POST['content_align'] ?? 'center' ),
				'content_max_w'   => intval( $_POST['content_max_w'] ?? 820 ),
				'slides'          => $clean_slides,
			);

			$sliders = AB_Slider_Renderer::get_sliders();
			$sliders[ $slider_id ] = $slider_data;
			update_option( AB_Slider_Renderer::OPTION_KEY, $sliders );

			wp_safe_redirect( add_query_arg( array( 'page' => 'ab-slider-settings', 'action' => 'edit', 'slider_id' => $slider_id, 'ab_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Main admin page renderer.
	 */
	public function render_page() {
		$action    = sanitize_key( $_GET['action'] ?? 'list' );
		$slider_id = sanitize_key( $_GET['slider_id'] ?? '' );

		if ( 'new' === $action || ( 'edit' === $action && ! empty( $slider_id ) ) ) {
			$this->render_edit_view( $slider_id );
		} else {
			$this->render_list_view();
		}
	}

	/**
	 * Render the sliders list view.
	 */
	private function render_list_view() {
		$sliders = AB_Slider_Renderer::get_sliders();
		$msg = sanitize_key( $_GET['ab_msg'] ?? '' );
		?>
		<div class="wrap ab-settings-wrap" style="padding-top: 20px;">
			<!-- Navigation Tabs -->
			<nav class="nav-tab-wrapper ab-admin-nav-tabs" style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Popups', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-widgets' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Widgets', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-archive-templates' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Archive Templates', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" class="nav-tab nav-tab-active">
					<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Sliders', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-weather-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Ramdevra Weather', 'ab-addon' ); ?>
				</a>
			</nav>

			<!-- Header -->
			<div class="ab-settings-header" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
				<div class="ab-settings-header__logo">
					<span class="dashicons dashicons-slides" style="font-size: 36px; width: 36px; height: 36px;"></span>
				</div>
				<div>
					<h1><?php esc_html_e( 'AB Hero Sliders', 'ab-addon' ); ?></h1>
					<p class="ab-settings-header__desc"><?php esc_html_e( 'Fast, lightweight & beautiful full-width hero banner sliders. Replace Smart Slider 3 with zero bloat.', 'ab-addon' ); ?></p>
				</div>
			</div>

			<!-- Notices -->
			<?php if ( 'saved' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slider saved successfully!', 'ab-addon' ); ?></p></div>
			<?php elseif ( 'deleted' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slider deleted successfully.', 'ab-addon' ); ?></p></div>
			<?php elseif ( 'duplicated' === $msg ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slider duplicated successfully.', 'ab-addon' ); ?></p></div>
			<?php endif; ?>

			<!-- Toolbar -->
			<div class="ab-settings-toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings&action=new' ) ); ?>" class="button button-primary button-hero ab-btn-new" style="background: #ea580c; border-color: #c2410c;">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Create New Slider', 'ab-addon' ); ?>
				</a>
				<div style="color: #64748b; font-size: 13.5px;">
					<strong>Tip:</strong> You can also insert any slider using <code>[ab_slider id="..."]</code> or via the Elementor AB Slider widget.
				</div>
			</div>

			<?php if ( empty( $sliders ) ) : ?>
				<div class="ab-empty-state">
					<span class="dashicons dashicons-slides ab-empty-icon" style="color: #fdba74;"></span>
					<h2><?php esc_html_e( 'No Sliders Created Yet', 'ab-addon' ); ?></h2>
					<p><?php esc_html_e( 'Click the button below to create your first Hero Banner Slider.', 'ab-addon' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings&action=new' ) ); ?>" class="button button-primary" style="background: #ea580c; border-color: #c2410c; margin-top: 12px;">
						<?php esc_html_e( 'Create Slider', 'ab-addon' ); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="ab-popup-cards" style="grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));">
					<?php foreach ( $sliders as $slider ) : ?>
						<?php
						$s_id    = esc_attr( $slider['id'] );
						$s_name  = esc_html( $slider['name'] );
						$slides  = ! empty( $slider['slides'] ) ? $slider['slides'] : array();
						$count   = count( $slides );
						$first_bg= ! empty( $slides[0]['bg_image_url'] ) ? $slides[0]['bg_image_url'] : '';
						?>
						<div class="ab-popup-card" style="border-top: 4px solid #f97316;">
							<div class="ab-popup-card__header" style="position: relative;">
								<?php if ( $first_bg ) : ?>
									<div style="height: 120px; border-radius: 8px 8px 0 0; background: url('<?php echo esc_url( $first_bg ); ?>') center/cover no-repeat; margin: -16px -16px 12px -16px; position: relative; overflow: hidden;">
										<div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,23,42,0.8), transparent);"></div>
										<span style="position: absolute; bottom: 8px; left: 12px; color: #fff; font-weight: 700; font-size: 15px; text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
											<?php echo $s_name; ?>
										</span>
									</div>
								<?php else : ?>
									<h2 class="ab-popup-card__title" style="margin-top: 0; font-size: 17px;"><?php echo $s_name; ?></h2>
								<?php endif; ?>
							</div>

							<div class="ab-popup-card__body" style="padding-top: 4px;">
								<div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
									<span class="ab-badge ab-badge--blue" style="background: #ffedd5; color: #9a3412;">
										<?php printf( esc_html__( '%d Slides', 'ab-addon' ), $count ); ?>
									</span>
									<span class="ab-badge ab-badge--grey">
										<?php echo esc_html( ucfirst( $slider['effect'] ?? 'fade' ) ); ?> Effect
									</span>
									<span class="ab-badge ab-badge--grey">
										<?php echo intval( $slider['height_desktop'] ?? 560 ); ?>px Height
									</span>
								</div>

								<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
									<div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px;">Shortcode</div>
									<div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
										<code style="font-size: 13px; color: #0f172a; font-weight: 600;">[ab_slider id="<?php echo $s_id; ?>"]</code>
										<button type="button" class="button button-small ab-copy-shortcode-btn" data-clipboard='[ab_slider id="<?php echo $s_id; ?>"]' title="Copy Shortcode">
											<span class="dashicons dashicons-admin-page"></span> Copy
										</button>
									</div>
								</div>
							</div>

							<div class="ab-popup-card__actions" style="border-top: 1px solid #e2e8f0; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings&action=edit&slider_id=' . $s_id ) ); ?>" class="button button-primary" style="background: #ea580c; border-color: #c2410c;">
									<span class="dashicons dashicons-edit" style="margin-top: 3px;"></span> <?php esc_html_e( 'Edit Slider', 'ab-addon' ); ?>
								</a>
								<div style="display: flex; gap: 6px;">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ab-slider-settings&ab_slider_action=duplicate&slider_id=' . $s_id ), 'ab_slider_action_' . $s_id ) ); ?>" class="button button-secondary" title="<?php esc_attr_e( 'Duplicate', 'ab-addon' ); ?>">
										<span class="dashicons dashicons-admin-page" style="margin-top: 3px;"></span>
									</a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ab-slider-settings&ab_slider_action=delete&slider_id=' . $s_id ), 'ab_slider_action_' . $s_id ) ); ?>" class="button button-link-delete" style="color: #ef4444; border: 1px solid #fee2e2; border-radius: 4px; padding: 0 8px; line-height: 28px;" onclick="return confirm('<?php esc_attr_e( 'Delete this slider? This cannot be undone.', 'ab-addon' ); ?>');">
										<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the slider create/edit view.
	 *
	 * @param string $slider_id
	 */
	private function render_edit_view( $slider_id ) {
		$is_new = empty( $slider_id );
		$slider = null;

		if ( ! $is_new ) {
			$slider = AB_Slider_Renderer::get_slider( $slider_id );
		}

		if ( ! $slider ) {
			$slider = AB_Slider_Renderer::get_default_seed_slider();
			if ( $is_new ) {
				$slider['id']   = 'slider_' . substr( md5( uniqid() ), 0, 8 );
				$slider['name'] = 'New Hero Slider';
			}
		}

		$slides = ! empty( $slider['slides'] ) ? $slider['slides'] : array();
		?>
		<div class="wrap ab-settings-wrap">
			<!-- Breadcrumb / Back -->
			<div style="margin: 16px 0 12px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-arrow-left-alt" style="margin-top: 3px;"></span> <?php esc_html_e( 'Back to Sliders', 'ab-addon' ); ?>
				</a>
			</div>

			<!-- Header -->
			<div class="ab-settings-header" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); margin-top: 0;">
				<div class="ab-settings-header__logo">
					<span class="dashicons dashicons-slides" style="font-size: 32px; width: 32px; height: 32px;"></span>
				</div>
				<div>
					<h1><?php echo $is_new ? esc_html__( 'Create New Hero Slider', 'ab-addon' ) : sprintf( esc_html__( 'Edit: %s', 'ab-addon' ), esc_html( $slider['name'] ) ); ?></h1>
					<p class="ab-settings-header__desc"><?php esc_html_e( 'Configure slider dimensions, autoplay, transitions, and slide content.', 'ab-addon' ); ?></p>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" id="ab-slider-form">
				<?php wp_nonce_field( 'ab_slider_save', 'ab_slider_nonce' ); ?>
				<input type="hidden" name="slider_id" value="<?php echo esc_attr( $slider['id'] ); ?>" />

				<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">

					<!-- LEFT COLUMN: Slides Repeater -->
					<div>
						<!-- Slider Name & Shortcode Card -->
						<div class="postbox" style="padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; background: #fff;">
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
								<div>
									<label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 6px;">
										<?php esc_html_e( 'Slider Name', 'ab-addon' ); ?> *
									</label>
									<input type="text" name="name" value="<?php echo esc_attr( $slider['name'] ); ?>" class="regular-text" style="width: 100%; font-size: 15px; padding: 6px 10px;" required />
								</div>
								<div>
									<label style="font-weight: 700; font-size: 14px; display: block; margin-bottom: 6px;">
										<?php esc_html_e( 'Shortcode Preview', 'ab-addon' ); ?>
									</label>
									<div style="display: flex; gap: 6px; align-items: center;">
										<input type="text" readonly value='[ab_slider id="<?php echo esc_attr( $slider['id'] ); ?>"]' class="regular-text" style="width: 100%; background: #f8fafc; font-family: monospace; font-size: 13px;" />
										<button type="button" class="button ab-copy-shortcode-btn" data-clipboard='[ab_slider id="<?php echo esc_attr( $slider['id'] ); ?>"]' title="Copy">
											<span class="dashicons dashicons-admin-page"></span>
										</button>
									</div>
								</div>
							</div>
						</div>

						<!-- Slides Section -->
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
							<h2 style="font-size: 18px; font-weight: 700; margin: 0; color: #1e293b;">
								<?php esc_html_e( 'Slides', 'ab-addon' ); ?>
								<span id="ab-slide-count-badge" class="ab-badge ab-badge--blue" style="background: #ffedd5; color: #9a3412; margin-left: 8px;">
									<?php echo count( $slides ); ?> slides
								</span>
							</h2>
							<button type="button" id="ab-add-slide-btn" class="button button-primary" style="background: #ea580c; border-color: #c2410c;">
								<span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span> <?php esc_html_e( 'Add New Slide', 'ab-addon' ); ?>
							</button>
						</div>

						<!-- Slides List -->
						<div id="ab-slides-list" style="display: flex; flex-direction: column; gap: 16px;">
							<?php foreach ( $slides as $idx => $slide ) : ?>
								<?php $this->render_slide_item( $idx, $slide ); ?>
							<?php endforeach; ?>
						</div>

						<div style="margin-top: 20px; text-align: center;">
							<button type="button" class="button button-secondary" onclick="document.getElementById('ab-add-slide-btn').click();" style="padding: 6px 18px;">
								<span class="dashicons dashicons-plus-alt2" style="margin-top: 3px;"></span> <?php esc_html_e( '+ Add Another Slide', 'ab-addon' ); ?>
							</button>
						</div>
					</div>

					<!-- RIGHT COLUMN: Settings & Publishing -->
					<div>
						<!-- Publish Card -->
						<div class="postbox" style="border-radius: 8px; overflow: hidden; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
							<div class="postbox-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px;">
								<h2 style="font-size: 15px; font-weight: 700; margin: 0;"><?php esc_html_e( 'Publish Settings', 'ab-addon' ); ?></h2>
							</div>
							<div class="inside" style="padding: 16px;">
								<p style="margin-top: 0; color: #64748b; font-size: 13px;">
									Changes will take effect immediately wherever this slider is placed.
								</p>
								<button type="submit" class="button button-primary button-large" style="width: 100%; background: #ea580c; border-color: #c2410c; font-size: 15px; height: 42px; font-weight: 700;">
									<span class="dashicons dashicons-saved" style="margin-top: 6px;"></span> <?php esc_html_e( 'Save Slider', 'ab-addon' ); ?>
								</button>
							</div>
						</div>

						<!-- Slider Behavior Options -->
						<div class="postbox" style="border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
							<div class="postbox-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px;">
								<h2 style="font-size: 15px; font-weight: 700; margin: 0;"><?php esc_html_e( 'Dimensions & Heights', 'ab-addon' ); ?></h2>
							</div>
							<div class="inside" style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Desktop Height (px)</label>
									<input type="number" name="height_desktop" value="<?php echo intval( $slider['height_desktop'] ?? 560 ); ?>" min="200" max="1200" style="width: 100%;" />
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Tablet Height (px)</label>
									<input type="number" name="height_tablet" value="<?php echo intval( $slider['height_tablet'] ?? 460 ); ?>" min="200" max="1000" style="width: 100%;" />
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Mobile Height (px)</label>
									<input type="number" name="height_mobile" value="<?php echo intval( $slider['height_mobile'] ?? 360 ); ?>" min="180" max="800" style="width: 100%;" />
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Max Content Width (px)</label>
									<input type="number" name="content_max_w" value="<?php echo intval( $slider['content_max_w'] ?? 820 ); ?>" min="400" max="1400" style="width: 100%;" />
								</div>
							</div>
						</div>

						<!-- Animation & Controls Card -->
						<div class="postbox" style="border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
							<div class="postbox-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 16px;">
								<h2 style="font-size: 15px; font-weight: 700; margin: 0;"><?php esc_html_e( 'Animation & Controls', 'ab-addon' ); ?></h2>
							</div>
							<div class="inside" style="padding: 16px; display: flex; flex-direction: column; gap: 14px;">
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Transition Effect</label>
									<select name="effect" style="width: 100%;">
										<option value="fade" <?php selected( $slider['effect'] ?? 'fade', 'fade' ); ?>>Fade (Smooth Crossfade)</option>
										<option value="slide" <?php selected( $slider['effect'] ?? 'fade', 'slide' ); ?>>Slide (Horizontal Slide)</option>
									</select>
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Transition Speed (ms)</label>
									<input type="number" name="speed" value="<?php echo intval( $slider['speed'] ?? 800 ); ?>" step="50" min="200" max="3000" style="width: 100%;" />
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Text Alignment</label>
									<select name="content_align" style="width: 100%;">
										<option value="center" <?php selected( $slider['content_align'] ?? 'center', 'center' ); ?>>Center</option>
										<option value="left" <?php selected( $slider['content_align'] ?? 'center', 'left' ); ?>>Left</option>
										<option value="right" <?php selected( $slider['content_align'] ?? 'center', 'right' ); ?>>Right</option>
									</select>
								</div>
								<hr style="margin: 4px 0; border: none; border-top: 1px solid #e2e8f0;" />
								<div>
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
										<input type="checkbox" name="autoplay" value="yes" <?php checked( $slider['autoplay'] ?? 'yes', 'yes' ); ?> />
										Enable Autoplay
									</label>
								</div>
								<div>
									<label style="font-weight: 600; display: block; font-size: 13px; margin-bottom: 4px;">Autoplay Delay (ms)</label>
									<input type="number" name="autoplay_delay" value="<?php echo intval( $slider['autoplay_delay'] ?? 5000 ); ?>" step="500" min="1000" max="20000" style="width: 100%;" />
								</div>
								<div>
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
										<input type="checkbox" name="pause_on_hover" value="yes" <?php checked( $slider['pause_on_hover'] ?? 'yes', 'yes' ); ?> />
										Pause on Hover
									</label>
								</div>
								<div>
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
										<input type="checkbox" name="loop" value="yes" <?php checked( $slider['loop'] ?? 'yes', 'yes' ); ?> />
										Infinite Loop
									</label>
								</div>
								<div>
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
										<input type="checkbox" name="arrows" value="yes" <?php checked( $slider['arrows'] ?? 'yes', 'yes' ); ?> />
										Navigation Chevrons (Arrows)
									</label>
								</div>
								<div>
									<label style="font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer;">
										<input type="checkbox" name="dots" value="yes" <?php checked( $slider['dots'] ?? 'yes', 'yes' ); ?> />
										Pagination Bullets (Dots)
									</label>
								</div>
							</div>
						</div>
					</div>

				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a single repeater slide item HTML in the admin form.
	 *
	 * @param int   $idx
	 * @param array $slide
	 */
	private function render_slide_item( $idx, $slide ) {
		$slide_id      = esc_attr( $slide['id'] ?? uniqid() );
		$bg_url        = esc_url( $slide['bg_image_url'] ?? '' );
		$bg_mob_url    = esc_url( $slide['bg_mobile_url'] ?? '' );
		$overlay_color = esc_attr( $slide['overlay_color'] ?? 'rgba(15, 23, 42, 0.55)' );
		$badge         = esc_attr( $slide['badge'] ?? '' );
		$title         = esc_attr( $slide['title'] ?? '' );
		$desc          = esc_textarea( $slide['description'] ?? '' );
		$btn1_text     = esc_attr( $slide['btn_primary_text'] ?? '' );
		$btn1_url      = esc_url( $slide['btn_primary_url'] ?? '' );
		$btn1_target   = esc_attr( $slide['btn_primary_target'] ?? '_self' );
		$btn2_text     = esc_attr( $slide['btn_secondary_text'] ?? '' );
		$btn2_url      = esc_url( $slide['btn_secondary_url'] ?? '' );
		$btn2_target   = esc_attr( $slide['btn_secondary_target'] ?? '_self' );
		$slide_link    = esc_url( $slide['slide_link'] ?? '' );
		?>
		<div class="ab-slide-item postbox" data-slide-id="<?php echo $slide_id; ?>" style="border-radius: 8px; overflow: hidden; margin-bottom: 0; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
			<input type="hidden" name="slides[<?php echo $idx; ?>][id]" value="<?php echo $slide_id; ?>" />

			<!-- Slide Header Bar (collapsible) -->
			<div class="ab-slide-header-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; cursor: pointer; user-select: none;">
				<div style="display: flex; align-items: center; gap: 12px;">
					<span class="ab-slide-number-badge" style="background: #e2e8f0; color: #334155; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
						#<?php echo ( $idx + 1 ); ?>
					</span>
					<div class="ab-slide-header-thumb" style="width: 38px; height: 26px; border-radius: 4px; background: #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden;">
						<?php if ( $bg_url ) : ?>
							<img src="<?php echo $bg_url; ?>" style="width: 100%; height: 100%; object-fit: cover;" />
						<?php else : ?>
							<span class="dashicons dashicons-format-image" style="color: #64748b; font-size: 16px; width: 16px; height: 16px;"></span>
						<?php endif; ?>
					</div>
					<strong class="ab-slide-title-display" style="font-size: 14px; color: #1e293b;">
						<?php echo ! empty( $title ) ? $title : 'Untitled Slide'; ?>
					</strong>
				</div>

				<div class="ab-slide-header-actions" style="display: flex; align-items: center; gap: 4px;">
					<button type="button" class="button button-small ab-slide-move-up" title="Move Up"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
					<button type="button" class="button button-small ab-slide-move-down" title="Move Down"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
					<button type="button" class="button button-small ab-slide-duplicate" title="Duplicate Slide"><span class="dashicons dashicons-admin-page"></span></button>
					<button type="button" class="button button-small ab-slide-delete" style="color: #ef4444;" title="Delete Slide"><span class="dashicons dashicons-trash"></span></button>
				</div>
			</div>

			<!-- Slide Body Form Fields -->
			<div class="ab-slide-body inside" style="padding: 20px;">
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
					<!-- Desktop Background Image -->
					<div class="ab-media-field-wrap">
						<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
							<?php esc_html_e( 'Desktop Background Image', 'ab-addon' ); ?> *
						</label>
						<div style="display: flex; gap: 10px; align-items: flex-start;">
							<div class="ab-image-preview">
								<?php if ( $bg_url ) : ?>
									<img src="<?php echo $bg_url; ?>" style="max-height: 80px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" />
								<?php endif; ?>
							</div>
							<div style="flex-grow: 1;">
								<input type="text" name="slides[<?php echo $idx; ?>][bg_image_url]" value="<?php echo $bg_url; ?>" class="ab-image-url-input ab-slide-main-bg" placeholder="Image URL or choose from library..." style="width: 100%; margin-bottom: 6px;" />
								<div style="display: flex; gap: 6px;">
									<button type="button" class="button ab-upload-image-btn"><span class="dashicons dashicons-upload"></span> Select Image</button>
									<button type="button" class="button ab-remove-image-btn" style="<?php echo empty( $bg_url ) ? 'display:none;' : ''; ?>">Remove</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Mobile Background Image (Optional) -->
					<div class="ab-media-field-wrap">
						<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
							<?php esc_html_e( 'Mobile Background Image (Optional)', 'ab-addon' ); ?>
						</label>
						<div style="display: flex; gap: 10px; align-items: flex-start;">
							<div class="ab-image-preview">
								<?php if ( $bg_mob_url && $bg_mob_url !== $bg_url ) : ?>
									<img src="<?php echo $bg_mob_url; ?>" style="max-height: 80px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" />
								<?php endif; ?>
							</div>
							<div style="flex-grow: 1;">
								<input type="text" name="slides[<?php echo $idx; ?>][bg_mobile_url]" value="<?php echo $bg_mob_url; ?>" class="ab-image-url-input" placeholder="Leave blank to use desktop image..." style="width: 100%; margin-bottom: 6px;" />
								<div style="display: flex; gap: 6px;">
									<button type="button" class="button ab-upload-image-btn"><span class="dashicons dashicons-upload"></span> Select Image</button>
									<button type="button" class="button ab-remove-image-btn" style="<?php echo empty( $bg_mob_url ) ? 'display:none;' : ''; ?>">Remove</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Overlay & Badge -->
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px;">
					<div>
						<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
							<?php esc_html_e( 'Dark / Gradient Overlay Color', 'ab-addon' ); ?>
						</label>
						<input type="text" name="slides[<?php echo $idx; ?>][overlay_color]" value="<?php echo $overlay_color; ?>" placeholder="rgba(15, 23, 42, 0.55)" style="width: 100%;" />
						<p class="description" style="margin-top: 4px;">e.g. <code>rgba(15, 23, 42, 0.55)</code> or <code>linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.3))</code></p>
					</div>
					<div>
						<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
							<?php esc_html_e( 'Eyebrow / Badge Text', 'ab-addon' ); ?>
						</label>
						<input type="text" name="slides[<?php echo $idx; ?>][badge]" value="<?php echo $badge; ?>" placeholder="e.g. Welcome to Ramdevra" style="width: 100%;" />
					</div>
				</div>

				<!-- Title & Description -->
				<div style="margin-bottom: 16px;">
					<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
						<?php esc_html_e( 'Main Heading / Title', 'ab-addon' ); ?>
					</label>
					<input type="text" name="slides[<?php echo $idx; ?>][title]" value="<?php echo $title; ?>" class="ab-slide-title-input" placeholder="e.g. श्री रामदेवरा धाम" style="width: 100%; font-size: 15px;" />
				</div>

				<div style="margin-bottom: 16px;">
					<label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 13px;">
						<?php esc_html_e( 'Description / Subtext', 'ab-addon' ); ?>
					</label>
					<textarea name="slides[<?php echo $idx; ?>][description]" rows="2" style="width: 100%;"><?php echo $desc; ?></textarea>
				</div>

				<!-- Buttons Row -->
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; background: #f8fafc; padding: 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
					<!-- Primary Button -->
					<div>
						<strong style="display: block; margin-bottom: 8px; color: #ea580c;"><?php esc_html_e( 'Primary Button', 'ab-addon' ); ?></strong>
						<input type="text" name="slides[<?php echo $idx; ?>][btn_primary_text]" value="<?php echo $btn1_text; ?>" placeholder="Button Text (e.g. आरती समय)" style="width: 100%; margin-bottom: 6px;" />
						<input type="text" name="slides[<?php echo $idx; ?>][btn_primary_url]" value="<?php echo $btn1_url; ?>" placeholder="Link URL (e.g. https://...)" style="width: 100%; margin-bottom: 6px;" />
						<label style="font-size: 12px;">
							<input type="checkbox" name="slides[<?php echo $idx; ?>][btn_primary_target]" value="_blank" <?php checked( $btn1_target, '_blank' ); ?> />
							Open in new tab
						</label>
					</div>

					<!-- Secondary Button -->
					<div>
						<strong style="display: block; margin-bottom: 8px; color: #475569;"><?php esc_html_e( 'Secondary Button (Optional)', 'ab-addon' ); ?></strong>
						<input type="text" name="slides[<?php echo $idx; ?>][btn_secondary_text]" value="<?php echo $btn2_text; ?>" placeholder="Button Text (e.g. होटल देखें)" style="width: 100%; margin-bottom: 6px;" />
						<input type="text" name="slides[<?php echo $idx; ?>][btn_secondary_url]" value="<?php echo $btn2_url; ?>" placeholder="Link URL" style="width: 100%; margin-bottom: 6px;" />
						<label style="font-size: 12px;">
							<input type="checkbox" name="slides[<?php echo $idx; ?>][btn_secondary_target]" value="_blank" <?php checked( $btn2_target, '_blank' ); ?> />
							Open in new tab
						</label>
					</div>
				</div>

				<!-- Full Slide Link -->
				<div>
					<label style="font-weight: 600; display: block; margin-bottom: 4px; font-size: 13px;">
						<?php esc_html_e( 'Full Slide Click URL (Optional)', 'ab-addon' ); ?>
					</label>
					<input type="text" name="slides[<?php echo $idx; ?>][slide_link]" value="<?php echo $slide_link; ?>" placeholder="Make entire slide clickable..." style="width: 100%;" />
				</div>
			</div>
		</div>
		<?php
	}
}
