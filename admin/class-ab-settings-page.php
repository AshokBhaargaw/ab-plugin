<?php
/**
 * AB Settings Admin Page
 *
 * Provides a WordPress admin panel page (under Settings > AB Settings) where
 * users can create, edit, and delete Popup configurations. Each popup is stored
 * as a WordPress option and rendered on the frontend via the [ab_popup id="..."]
 * shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Settings_Page {

	/** Option key that holds the array of all popups. */
	const OPTION_KEY = 'ab_popups';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	// =========================================================================
	// MENU
	// =========================================================================

	public function register_menu() {
		add_menu_page(
			__( 'AB Settings', 'ab-addon' ),
			__( 'AB Settings', 'ab-addon' ),
			'manage_options',
			'ab-settings',
			array( $this, 'render_page' ),
			'dashicons-lightbulb',
			58
		);
	}

	// =========================================================================
	// ASSETS
	// =========================================================================

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== 'toplevel_page_ab-settings' ) {
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
	}

	// =========================================================================
	// FORM HANDLER
	// =========================================================================

	public function handle_form_submission() {
		if ( ! isset( $_POST['ab_popup_nonce'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'ab-addon' ) );
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_popup_nonce'] ) ), 'ab_popup_save' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ab-addon' ) );
		}

		$action   = isset( $_POST['ab_action'] ) ? sanitize_key( wp_unslash( $_POST['ab_action'] ) ) : '';
		$popup_id = isset( $_POST['ab_popup_id'] ) ? sanitize_key( wp_unslash( $_POST['ab_popup_id'] ) ) : '';
		$popups   = $this->get_popups();

		if ( $action === 'save' ) {
			$label = isset( $_POST['ab_label'] ) ? sanitize_text_field( wp_unslash( $_POST['ab_label'] ) ) : '';
			if ( empty( $label ) ) {
				$label = __( 'Untitled Popup', 'ab-addon' );
			}
			$id = ! empty( $popup_id ) ? $popup_id : 'popup_' . uniqid();

			// Extract fields that need pre-processing.
			$trigger_type     = sanitize_key( wp_unslash( $_POST['ab_trigger_type'] ?? 'page_load' ) );
			$trigger_selector = sanitize_text_field( wp_unslash( $_POST['ab_trigger_selector'] ?? '' ) );
			// Auto-generate a unique, name-based CSS selector when the field is left empty.
			if ( $trigger_type === 'click' && empty( $trigger_selector ) ) {
				$trigger_selector = $this->generate_unique_selector( $label, $id );
			}

			$popups[ $id ] = array(
				'id'                    => $id,
				'label'                 => $label,
				'popup_type'            => sanitize_key( wp_unslash( $_POST['ab_popup_type'] ?? 'modal' ) ),
				'notification_position' => sanitize_key( wp_unslash( $_POST['ab_notification_position'] ?? 'bottom-right' ) ),
				'trigger_type'          => $trigger_type,
				'trigger_delay'         => floatval( wp_unslash( $_POST['ab_trigger_delay'] ?? 2 ) ),
				'trigger_selector'      => $trigger_selector,
				'trigger_scroll'        => intval( wp_unslash( $_POST['ab_trigger_scroll'] ?? 50 ) ),
				'show_once'             => isset( $_POST['ab_show_once'] ) ? 'yes' : 'no',
				'target_display'        => sanitize_key( wp_unslash( $_POST['ab_target_display'] ?? 'sitewide' ) ),
				'content_type'          => sanitize_key( wp_unslash( $_POST['ab_content_type'] ?? 'text_only' ) ),
				'popup_title'           => sanitize_text_field( wp_unslash( $_POST['ab_popup_title'] ?? '' ) ),
				'popup_description'     => wp_kses_post( wp_unslash( $_POST['ab_popup_description'] ?? '' ) ),
				'popup_image_url'       => esc_url_raw( wp_unslash( $_POST['ab_popup_image_url'] ?? '' ) ),
				'image_position'        => sanitize_key( wp_unslash( $_POST['ab_image_position'] ?? 'top' ) ),
				'cta_text'              => sanitize_text_field( wp_unslash( $_POST['ab_cta_text'] ?? '' ) ),
				'cta_url'               => esc_url_raw( wp_unslash( $_POST['ab_cta_url'] ?? '' ) ),
				'cta_target'            => isset( $_POST['ab_cta_target'] ) ? '_blank' : '_self',
				'custom_html'           => wp_kses_post( wp_unslash( $_POST['ab_custom_html'] ?? '' ) ),
				'popup_shortcode'       => sanitize_text_field( wp_unslash( $_POST['ab_popup_shortcode'] ?? '' ) ),
				'elementor_template_id' => intval( wp_unslash( $_POST['ab_elementor_template_id'] ?? 0 ) ),
				'show_close_button'     => isset( $_POST['ab_show_close'] ) ? 'yes' : 'no',
				'close_on_overlay'      => isset( $_POST['ab_close_overlay'] ) ? 'yes' : 'no',
				'overlay_color'         => sanitize_hex_color( wp_unslash( $_POST['ab_overlay_color'] ?? '' ) ) ?: 'rgba(0,0,0,0.65)',
				'bg_color'              => sanitize_hex_color( wp_unslash( $_POST['ab_bg_color'] ?? '' ) ) ?: '#ffffff',
			);

			update_option( self::OPTION_KEY, $popups );
			wp_safe_redirect( admin_url( 'admin.php?page=ab-settings&saved=1' ) );
			exit;
		}

		if ( $action === 'delete' && ! empty( $popup_id ) ) {
			unset( $popups[ $popup_id ] );
			update_option( self::OPTION_KEY, $popups );
			wp_safe_redirect( admin_url( 'admin.php?page=ab-settings&deleted=1' ) );
			exit;
		}
	}

	// =========================================================================
	// HELPERS
	// =========================================================================

	public static function get_popups() {
		return (array) get_option( self::OPTION_KEY, array() );
	}

	public static function get_popup( $id ) {
		$popups = self::get_popups();
		return isset( $popups[ $id ] ) ? $popups[ $id ] : null;
	}

	/**
	 * Generate a unique CSS class selector derived from the popup label.
	 *
	 * e.g. "Taxi Booking Popup" → ".open-taxi-booking-popup"
	 * If that selector is already used by another popup, a numeric suffix is appended:
	 * ".open-taxi-booking-popup-2", ".open-taxi-booking-popup-3", …
	 *
	 * @param string $label          The popup name.
	 * @param string $current_id     The ID of the popup being saved (excluded from collision check).
	 * @return string                A unique CSS class selector.
	 */
	private function generate_unique_selector( $label, $current_id = '' ) {
		$slug          = sanitize_title( $label ); // lowercase, hyphens, no special chars
		$base_selector = '.open-' . $slug;

		// Collect all selectors already in use by other popups.
		$existing = array();
		foreach ( self::get_popups() as $id => $popup ) {
			if ( $id === $current_id ) {
				continue; // skip the popup currently being saved
			}
			if ( ! empty( $popup['trigger_selector'] ) ) {
				$existing[] = $popup['trigger_selector'];
			}
		}

		$selector = $base_selector;
		$counter  = 2;
		while ( in_array( $selector, $existing, true ) ) {
			$selector = $base_selector . '-' . $counter;
			$counter++;
		}

		return $selector;
	}

	public static function get_elementor_templates() {
		$templates = array();
		$posts = get_posts( array(
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $p ) {
				$templates[ $p->ID ] = $p->post_title;
			}
		}
		return $templates;
	}

	// =========================================================================
	// RENDER PAGE
	// =========================================================================

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action   = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		$popup_id = isset( $_GET['popup_id'] ) ? sanitize_key( $_GET['popup_id'] ) : '';
		$editing  = ( $action === 'edit' && ! empty( $popup_id ) );
		$adding   = ( $action === 'new' );
		$popup    = ( $editing ) ? $this->get_popup( $popup_id ) : null;

		// If trying to edit a non-existent popup, fall back to list.
		if ( $editing && ! $popup ) {
			$action  = 'list';
			$editing = false;
		}

		?>
		<div class="wrap ab-settings-wrap" style="padding-top: 20px;">
			<!-- <div class="ab-settings-header">
				<div class="ab-settings-header__logo">
					<span class="dashicons dashicons-lightbulb"></span>
					<h1><?php esc_html_e( 'AB Settings', 'ab-addon' ); ?></h1>
				</div>
				<p class="ab-settings-header__desc"><?php esc_html_e( 'Manage your site popups. Use the shortcode below each popup to display it anywhere on your site.', 'ab-addon' ); ?></p>
			</div> -->

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '✅ Popup saved successfully!', 'ab-addon' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( '🗑️ Popup deleted.', 'ab-addon' ); ?></p></div>
			<?php endif; ?>
			<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'no_label' ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( '⚠️ Please provide a Name for the popup.', 'ab-addon' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $action === 'list' ) : ?>
				<?php $this->render_list_view(); ?>
			<?php elseif ( $adding || $editing ) : ?>
				<?php $this->render_edit_form( $popup ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	// =========================================================================
	// LIST VIEW
	// =========================================================================

	private function render_list_view() {
		$popups = $this->get_popups();
		?>
		<div class="ab-settings-toolbar">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings&action=new' ) ); ?>" class="button button-primary ab-btn-new">
				<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add New Popup', 'ab-addon' ); ?>
			</a>
		</div>

		<?php if ( empty( $popups ) ) : ?>
			<div class="ab-empty-state">
				<span class="dashicons dashicons-megaphone ab-empty-icon"></span>
				<h2><?php esc_html_e( 'No popups yet', 'ab-addon' ); ?></h2>
				<p><?php esc_html_e( 'Create your first popup and set "Display On" to show it automatically across your site.', 'ab-addon' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings&action=new' ) ); ?>" class="button button-primary">
					<?php esc_html_e( '+ Create First Popup', 'ab-addon' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="ab-popup-cards">
				<?php foreach ( $popups as $popup ) : ?>
					<div class="ab-popup-card">
						<div class="ab-popup-card__header">
							<span class="ab-popup-card__icon"><?php echo $this->get_type_icon( $popup['popup_type'] ?? 'modal' ); ?></span>
							<div>
								<h3 class="ab-popup-card__title"><?php echo esc_html( $popup['label'] ?: $popup['id'] ); ?></h3>
								<span class="ab-popup-card__type"><?php echo esc_html( $this->get_type_label( $popup['popup_type'] ?? 'modal' ) ); ?></span>
							</div>
						</div>

						<div class="ab-popup-card__meta">
							<span>🎯 <?php echo esc_html( $this->get_trigger_label( $popup['trigger_type'] ?? 'page_load' ) ); ?></span>
							<span>📄 <?php echo esc_html( $this->get_content_label( $popup['content_type'] ?? 'text_only' ) ); ?></span>
						</div>

						<div class="ab-popup-card__actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings&action=edit&popup_id=' . $popup['id'] ) ); ?>" class="button button-secondary">
								<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit', 'ab-addon' ); ?>
							</a>
							<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this popup?', 'ab-addon' ); ?>');">
								<?php wp_nonce_field( 'ab_popup_save', 'ab_popup_nonce' ); ?>
								<input type="hidden" name="ab_action" value="delete">
								<input type="hidden" name="ab_popup_id" value="<?php echo esc_attr( $popup['id'] ); ?>">
								<button type="submit" class="button ab-btn-delete">
									<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'ab-addon' ); ?>
								</button>
							</form>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	// =========================================================================
	// EDIT / CREATE FORM
	// =========================================================================

	private function render_edit_form( $popup = null ) {
		$is_new = ( $popup === null );
		$d      = $popup ?: array(); // defaults container

		$def = array(
			'id'                    => '',
			'label'                 => '',
			'popup_type'            => 'modal',
			'notification_position' => 'bottom-right',
			'trigger_type'          => 'page_load',
			'trigger_delay'         => 2,
			'trigger_selector'      => '',
			'trigger_scroll'        => 50,
			'show_once'             => 'yes',
			'target_display'        => 'sitewide',
			'content_type'          => 'text_only',
			'popup_title'           => __( 'Welcome!', 'ab-addon' ),
			'popup_description'     => __( 'This is your popup message. Customize it as you like.', 'ab-addon' ),
			'popup_image_url'       => '',
			'image_position'        => 'top',
			'cta_text'              => __( 'Learn More', 'ab-addon' ),
			'cta_url'               => '',
			'cta_target'            => '_self',
			'custom_html'           => '<p>Enter your <strong>custom HTML</strong> here.</p>',
			'popup_shortcode'       => '',
			'elementor_template_id' => 0,
			'show_close_button'     => 'yes',
			'close_on_overlay'      => 'yes',
			'overlay_color'         => '#000000',
			'bg_color'              => '#ffffff',
		);

		// Merge saved values over defaults
		$v = array_merge( $def, $d );

		$page_title = $is_new ? __( 'Add New Popup', 'ab-addon' ) : __( 'Edit Popup', 'ab-addon' );
		?>
		<div class="ab-form-header">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="ab-back-link">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'All Popups', 'ab-addon' ); ?>
			</a>
			<h2><?php echo esc_html( $page_title ); ?></h2>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="ab-popup-form" novalidate>
			<?php wp_nonce_field( 'ab_popup_save', 'ab_popup_nonce' ); ?>
			<input type="hidden" name="ab_action" value="save">
			<input type="hidden" name="ab_popup_id" value="<?php echo esc_attr( $v['id'] ); ?>">

			<div class="ab-form-grid">

				<!-- ======================================================= -->
				<!-- LEFT COLUMN: Settings                                     -->
				<!-- ======================================================= -->
				<div class="ab-form-col ab-form-col--settings">

					<!-- Basic -->
					<div class="ab-form-section">
						<h3 class="ab-form-section__title"><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Popup Name', 'ab-addon' ); ?></h3>
						<table class="form-table ab-form-table">
							<tr>
								<th><label for="ab_label"><?php esc_html_e( 'Name', 'ab-addon' ); ?></label></th>
								<td>
									<input type="text" id="ab_label" name="ab_label" value="<?php echo esc_attr( $v['label'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Newsletter Signup', 'ab-addon' ); ?>">
									<p class="description"><?php esc_html_e( 'An internal name for this popup (not shown on the frontend).', 'ab-addon' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<!-- Popup Type & Trigger -->
					<div class="ab-form-section">
						<h3 class="ab-form-section__title"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Popup Type & Trigger', 'ab-addon' ); ?></h3>
						<table class="form-table ab-form-table">

							<tr>
								<th><label for="ab_popup_type"><?php esc_html_e( 'Popup Type', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_popup_type" name="ab_popup_type" class="ab-select ab-trigger-toggle">
										<option value="modal"        <?php selected( $v['popup_type'], 'modal' ); ?>><?php esc_html_e( 'Modal (Centered Overlay)', 'ab-addon' ); ?></option>
										<option value="notification" <?php selected( $v['popup_type'], 'notification' ); ?>><?php esc_html_e( 'Notification / Banner', 'ab-addon' ); ?></option>
										<option value="exit_intent"  <?php selected( $v['popup_type'], 'exit_intent' ); ?>><?php esc_html_e( 'Exit Intent', 'ab-addon' ); ?></option>
									</select>
								</td>
							</tr>

							<tr class="ab-row-notif-position" <?php echo $v['popup_type'] !== 'notification' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_notification_position"><?php esc_html_e( 'Banner Position', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_notification_position" name="ab_notification_position" class="ab-select">
										<?php
										$positions = array(
											'top-left'      => __( 'Top Left', 'ab-addon' ),
											'top-center'    => __( 'Top Center', 'ab-addon' ),
											'top-right'     => __( 'Top Right', 'ab-addon' ),
											'bottom-left'   => __( 'Bottom Left', 'ab-addon' ),
											'bottom-center' => __( 'Bottom Center', 'ab-addon' ),
											'bottom-right'  => __( 'Bottom Right', 'ab-addon' ),
										);
										foreach ( $positions as $key => $label ) {
											printf(
												'<option value="%s" %s>%s</option>',
												esc_attr( $key ),
												selected( $v['notification_position'], $key, false ),
												esc_html( $label )
											);
										}
										?>
									</select>
								</td>
							</tr>

							<tr class="ab-row-trigger" <?php echo $v['popup_type'] === 'exit_intent' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_trigger_type"><?php esc_html_e( 'Trigger', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_trigger_type" name="ab_trigger_type" class="ab-select ab-trigger-toggle">
										<option value="page_load" <?php selected( $v['trigger_type'], 'page_load' ); ?>><?php esc_html_e( 'Page Load', 'ab-addon' ); ?></option>
										<option value="click"     <?php selected( $v['trigger_type'], 'click' ); ?>><?php esc_html_e( 'Button / Element Click', 'ab-addon' ); ?></option>
										<option value="scroll"    <?php selected( $v['trigger_type'], 'scroll' ); ?>><?php esc_html_e( 'Scroll Percentage', 'ab-addon' ); ?></option>
									</select>
								</td>
							</tr>

							<tr class="ab-row-delay" <?php echo $v['trigger_type'] === 'click' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_trigger_delay"><?php esc_html_e( 'Delay (seconds)', 'ab-addon' ); ?></label></th>
								<td>
									<input type="number" id="ab_trigger_delay" name="ab_trigger_delay" value="<?php echo esc_attr( $v['trigger_delay'] ); ?>" min="0" max="60" step="0.5" class="small-text">
									<p class="description"><?php esc_html_e( 'Seconds to wait before showing the popup after the trigger fires.', 'ab-addon' ); ?></p>
								</td>
							</tr>

							<tr class="ab-row-selector" <?php echo $v['trigger_type'] !== 'click' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_trigger_selector"><?php esc_html_e( 'CSS Selector', 'ab-addon' ); ?></label></th>
								<td>
									<input type="text" id="ab_trigger_selector" name="ab_trigger_selector" value="<?php echo esc_attr( $v['trigger_selector'] ); ?>" class="regular-text" placeholder=".open-popup or #open-popup">
									<p class="description"><?php esc_html_e( 'CSS selector of the element that will open the popup on click. Use a class (e.g. .open-popup) or an ID (e.g. #open-popup).', 'ab-addon' ); ?></p>
								</td>
							</tr>

							<tr class="ab-row-scroll" <?php echo $v['trigger_type'] !== 'scroll' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_trigger_scroll"><?php esc_html_e( 'Scroll Percentage (%)', 'ab-addon' ); ?></label></th>
								<td>
									<input type="number" id="ab_trigger_scroll" name="ab_trigger_scroll" value="<?php echo esc_attr( $v['trigger_scroll'] ); ?>" min="5" max="100" class="small-text">
									<span>%</span>
								</td>
							</tr>

							<tr>
								<th><?php esc_html_e( 'Show Once Per Session', 'ab-addon' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="ab_show_once" value="yes" <?php checked( $v['show_once'], 'yes' ); ?>>
										<?php esc_html_e( 'Yes — show this popup only once per browser session', 'ab-addon' ); ?>
									</label>
								</td>
							</tr>

							<tr>
								<th><label for="ab_target_display"><?php esc_html_e( 'Display On', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_target_display" name="ab_target_display" class="ab-select">
										<option value="sitewide"  <?php selected( $v['target_display'] ?? 'sitewide', 'sitewide' ); ?>><?php esc_html_e( '🌐 Entire Site (All Pages)', 'ab-addon' ); ?></option>
										<option value="homepage"  <?php selected( $v['target_display'] ?? 'sitewide', 'homepage' ); ?>><?php esc_html_e( '🏠 Homepage Only', 'ab-addon' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Choose whether this popup should appear across your entire site or only on the homepage.', 'ab-addon' ); ?></p>
								</td>
							</tr>

						</table>
					</div>

					<!-- Content -->
					<div class="ab-form-section">
						<h3 class="ab-form-section__title"><span class="dashicons dashicons-editor-ul"></span> <?php esc_html_e( 'Popup Content', 'ab-addon' ); ?></h3>
						<table class="form-table ab-form-table">

							<tr>
								<th><label for="ab_content_type"><?php esc_html_e( 'Content Type', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_content_type" name="ab_content_type" class="ab-select ab-trigger-toggle">
										<option value="text_only"          <?php selected( $v['content_type'], 'text_only' ); ?>><?php esc_html_e( 'Text Only', 'ab-addon' ); ?></option>
										<option value="text_image"         <?php selected( $v['content_type'], 'text_image' ); ?>><?php esc_html_e( 'Text & Image', 'ab-addon' ); ?></option>
										<option value="text_cta"           <?php selected( $v['content_type'], 'text_cta' ); ?>><?php esc_html_e( 'Text, Image & Button', 'ab-addon' ); ?></option>
										<option value="elementor_template" <?php selected( $v['content_type'], 'elementor_template' ); ?>><?php esc_html_e( '🎨 Elementor Template', 'ab-addon' ); ?></option>
										<option value="shortcode"          <?php selected( $v['content_type'], 'shortcode' ); ?>><?php esc_html_e( 'Shortcode', 'ab-addon' ); ?></option>
										<option value="custom_html"        <?php selected( $v['content_type'], 'custom_html' ); ?>><?php esc_html_e( 'Custom HTML', 'ab-addon' ); ?></option>
									</select>
								</td>
							</tr>

							<tr class="ab-row-shortcode-input" <?php echo $v['content_type'] !== 'shortcode' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_popup_shortcode"><?php esc_html_e( 'Shortcode', 'ab-addon' ); ?></label></th>
								<td>
									<textarea id="ab_popup_shortcode" name="ab_popup_shortcode" rows="3" class="large-text code" placeholder='[contact-form-7 id="123" title="Contact form"]'><?php echo esc_textarea( $v['popup_shortcode'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Enter any WordPress shortcode (e.g. form, booking, etc.).', 'ab-addon' ); ?></p>
								</td>
							</tr>

							<tr class="ab-row-elementor-template" <?php echo $v['content_type'] !== 'elementor_template' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_elementor_template_id"><?php esc_html_e( 'Elementor Template', 'ab-addon' ); ?></label></th>
								<td>
									<?php
									$el_templates = self::get_elementor_templates();
									if ( ! empty( $el_templates ) ) :
									?>
										<select id="ab_elementor_template_id" name="ab_elementor_template_id" class="ab-select">
											<option value=""><?php esc_html_e( '— Select a Saved Template —', 'ab-addon' ); ?></option>
											<?php foreach ( $el_templates as $tid => $tname ) : ?>
												<option value="<?php echo esc_attr( $tid ); ?>" <?php selected( (int) ( $v['elementor_template_id'] ?? 0 ), $tid ); ?>>
													<?php echo esc_html( $tname ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php else : ?>
										<p style="color:#666;margin:0 0 8px;">
											<?php esc_html_e( 'No saved Elementor templates found yet.', 'ab-addon' ); ?>
										</p>
									<?php endif; ?>
									<div style="margin-top:10px;">
										<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=elementor_library' ) ); ?>" target="_blank" class="button button-secondary" style="display:inline-flex;align-items:center;gap:5px;">
											<span class="dashicons dashicons-external"></span>
											<?php esc_html_e( 'Create / Manage Elementor Templates ↗', 'ab-addon' ); ?>
										</a>
									</div>
									<p class="description">
										<?php esc_html_e( 'Build any layout in Elementor (Templates > Saved Templates) and select it here to render inside your popup.', 'ab-addon' ); ?>
									</p>
								</td>
							</tr>

							<tr class="ab-row-html-input" <?php echo $v['content_type'] !== 'custom_html' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_custom_html"><?php esc_html_e( 'Custom HTML', 'ab-addon' ); ?></label></th>
								<td>
									<textarea id="ab_custom_html" name="ab_custom_html" rows="6" class="large-text code"><?php echo esc_textarea( $v['custom_html'] ); ?></textarea>
								</td>
							</tr>

							<tr class="ab-row-text-fields" <?php echo ! in_array( $v['content_type'], array( 'text_only', 'text_image', 'text_cta' ) ) ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_popup_title"><?php esc_html_e( 'Title', 'ab-addon' ); ?></label></th>
								<td>
									<input type="text" id="ab_popup_title" name="ab_popup_title" value="<?php echo esc_attr( $v['popup_title'] ); ?>" class="regular-text">
								</td>
							</tr>

							<tr class="ab-row-text-fields" <?php echo ! in_array( $v['content_type'], array( 'text_only', 'text_image', 'text_cta' ) ) ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_popup_description"><?php esc_html_e( 'Description', 'ab-addon' ); ?></label></th>
								<td>
									<textarea id="ab_popup_description" name="ab_popup_description" rows="4" class="large-text"><?php echo esc_textarea( $v['popup_description'] ); ?></textarea>
								</td>
							</tr>

							<tr class="ab-row-image" <?php echo ! in_array( $v['content_type'], array( 'text_image', 'text_cta' ) ) ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_popup_image_url"><?php esc_html_e( 'Image URL', 'ab-addon' ); ?></label></th>
								<td>
									<div class="ab-media-field">
										<input type="text" id="ab_popup_image_url" name="ab_popup_image_url" value="<?php echo esc_attr( $v['popup_image_url'] ); ?>" class="regular-text" placeholder="https://...">
										<button type="button" class="button ab-media-upload-btn" data-target="#ab_popup_image_url" data-preview="#ab-img-preview">
											<span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Choose Image', 'ab-addon' ); ?>
										</button>
									</div>
									<?php if ( ! empty( $v['popup_image_url'] ) ) : ?>
										<img id="ab-img-preview" src="<?php echo esc_url( $v['popup_image_url'] ); ?>" style="max-width:200px;margin-top:8px;border-radius:6px;display:block;">
									<?php else : ?>
										<img id="ab-img-preview" src="" style="max-width:200px;margin-top:8px;border-radius:6px;display:none;">
									<?php endif; ?>
								</td>
							</tr>

							<tr class="ab-row-image-pos" <?php echo ! in_array( $v['content_type'], array( 'text_image', 'text_cta' ) ) ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_image_position"><?php esc_html_e( 'Image Position', 'ab-addon' ); ?></label></th>
								<td>
									<select id="ab_image_position" name="ab_image_position" class="ab-select">
										<option value="top"   <?php selected( $v['image_position'], 'top' ); ?>><?php esc_html_e( 'Top', 'ab-addon' ); ?></option>
										<option value="left"  <?php selected( $v['image_position'], 'left' ); ?>><?php esc_html_e( 'Left', 'ab-addon' ); ?></option>
										<option value="right" <?php selected( $v['image_position'], 'right' ); ?>><?php esc_html_e( 'Right', 'ab-addon' ); ?></option>
									</select>
								</td>
							</tr>

							<tr class="ab-row-cta" <?php echo $v['content_type'] !== 'text_cta' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_cta_text"><?php esc_html_e( 'Button Text', 'ab-addon' ); ?></label></th>
								<td>
									<input type="text" id="ab_cta_text" name="ab_cta_text" value="<?php echo esc_attr( $v['cta_text'] ); ?>" class="regular-text">
								</td>
							</tr>

							<tr class="ab-row-cta" <?php echo $v['content_type'] !== 'text_cta' ? 'style="display:none;"' : ''; ?>>
								<th><label for="ab_cta_url"><?php esc_html_e( 'Button URL', 'ab-addon' ); ?></label></th>
								<td>
									<input type="text" id="ab_cta_url" name="ab_cta_url" value="<?php echo esc_attr( $v['cta_url'] ); ?>" class="regular-text" placeholder="https:// or #">
									<label style="display:block;margin-top:6px;">
										<input type="checkbox" name="ab_cta_target" value="1" <?php checked( $v['cta_target'], '_blank' ); ?>>
										<?php esc_html_e( 'Open in new tab', 'ab-addon' ); ?>
									</label>
								</td>
							</tr>

						</table>
					</div>

					<!-- Behaviour -->
					<div class="ab-form-section">
						<h3 class="ab-form-section__title"><span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Behaviour', 'ab-addon' ); ?></h3>
						<table class="form-table ab-form-table">

							<tr>
								<th><?php esc_html_e( 'Show Close Button', 'ab-addon' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="ab_show_close" value="yes" <?php checked( $v['show_close_button'], 'yes' ); ?>>
										<?php esc_html_e( 'Show an ✕ button to close the popup', 'ab-addon' ); ?>
									</label>
								</td>
							</tr>

							<tr>
								<th><?php esc_html_e( 'Close on Overlay Click', 'ab-addon' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="ab_close_overlay" value="yes" <?php checked( $v['close_on_overlay'], 'yes' ); ?>>
										<?php esc_html_e( 'Close the popup when clicking the dark overlay (modals only)', 'ab-addon' ); ?>
									</label>
								</td>
							</tr>

						</table>
					</div>

					<!-- Style -->
					<div class="ab-form-section">
						<h3 class="ab-form-section__title"><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'Style', 'ab-addon' ); ?></h3>
						<table class="form-table ab-form-table">

							<tr>
								<th><label for="ab_bg_color"><?php esc_html_e( 'Popup Background', 'ab-addon' ); ?></label></th>
								<td>
									<input type="color" id="ab_bg_color" name="ab_bg_color" value="<?php echo esc_attr( $v['bg_color'] ); ?>">
								</td>
							</tr>

							<tr>
								<th><label for="ab_overlay_color"><?php esc_html_e( 'Overlay Color', 'ab-addon' ); ?></label></th>
								<td>
									<input type="color" id="ab_overlay_color" name="ab_overlay_color" value="<?php echo esc_attr( $v['overlay_color'] ); ?>">
									<p class="description"><?php esc_html_e( 'Used for modal and exit-intent popups.', 'ab-addon' ); ?></p>
								</td>
							</tr>

						</table>
					</div>

				</div><!-- .ab-form-col--settings -->

				<!-- ======================================================= -->
				<!-- RIGHT COLUMN: Info panel                                 -->
				<!-- ======================================================= -->
				<!-- RIGHT COLUMN: Save / Update Panel                       -->
				<!-- ======================================================= -->
				<div class="ab-form-col ab-form-col--info">

					<!-- Save / Update box -->
					<div class="ab-info-box ab-save-box">
						<h3>
							<span class="dashicons <?php echo $is_new ? 'dashicons-plus-alt2' : 'dashicons-edit'; ?>"></span>
							<?php echo $is_new ? esc_html__( 'Publish Popup', 'ab-addon' ) : esc_html__( 'Update Popup', 'ab-addon' ); ?>
						</h3>

						<!-- Status badge -->
						<div class="ab-save-status">
							<span class="ab-status-dot"></span>
							<span class="ab-status-label">
								<?php echo $is_new ? esc_html__( 'Draft — not saved yet', 'ab-addon' ) : esc_html__( 'Published', 'ab-addon' ); ?>
							</span>
						</div>


						<!-- Primary action -->
						<button type="submit" class="ab-save-btn">
							<span class="dashicons <?php echo $is_new ? 'dashicons-cloud-upload' : 'dashicons-saved'; ?>"></span>
							<?php echo $is_new ? esc_html__( 'Save & Publish', 'ab-addon' ) : esc_html__( 'Update Popup', 'ab-addon' ); ?>
						</button>

						<!-- Secondary action -->
						<div class="ab-save-cancel-wrap">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="ab-save-cancel">
								<span class="dashicons dashicons-arrow-left-alt"></span>
								<?php esc_html_e( 'Discard & go back', 'ab-addon' ); ?>
							</a>
						</div>
					</div>

					<!-- Quick tips box -->
					<div class="ab-info-box ab-info-box--tips">
						<h3><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Quick Tips', 'ab-addon' ); ?></h3>
						<ul>
							<li>🌐 <?php esc_html_e( 'Set "Display On" → "Entire Site" to show the popup on every page automatically.', 'ab-addon' ); ?></li>
							<li>🏠 <?php esc_html_e( 'Set "Display On" → "Homepage Only" to restrict it to the front page.', 'ab-addon' ); ?></li>
							<li>🎯 <?php esc_html_e( 'For click trigger, enter the CSS selector of your button (e.g. #my-btn).', 'ab-addon' ); ?></li>
							<li>🔁 <?php esc_html_e( '"Show Once" hides the popup after the first view per session.', 'ab-addon' ); ?></li>
						</ul>
					</div>

				</div><!-- .ab-form-col--info -->

			</div><!-- .ab-form-grid -->

		</form>

		<script>
		(function($) {
			// -----------------------------------------------------------------
			// Conditional row visibility
			// -----------------------------------------------------------------
			function updateVisibility() {
				var popupType   = $('#ab_popup_type').val();
				var triggerType = $('#ab_trigger_type').val();
				var contentType = $('#ab_content_type').val();

				// Notification position
				$('.ab-row-notif-position').toggle( popupType === 'notification' );

				// Trigger row (hidden for exit_intent)
				$('.ab-row-trigger').toggle( popupType !== 'exit_intent' );

				// Delay row (hidden for click trigger — popup fires immediately on click)
				$('.ab-row-delay').toggle( triggerType !== 'click' && popupType !== 'exit_intent' );

				// Selector row
				$('.ab-row-selector').toggle( triggerType === 'click' && popupType !== 'exit_intent' );

				// Scroll row
				$('.ab-row-scroll').toggle( triggerType === 'scroll' && popupType !== 'exit_intent' );

				// Text fields
				var textTypes = ['text_only','text_image','text_cta'];
				$('.ab-row-text-fields').toggle( textTypes.indexOf(contentType) !== -1 );

				// Image
				var imgTypes = ['text_image','text_cta'];
				$('.ab-row-image').toggle( imgTypes.indexOf(contentType) !== -1 );
				$('.ab-row-image-pos').toggle( imgTypes.indexOf(contentType) !== -1 );

				// CTA
				$('.ab-row-cta').toggle( contentType === 'text_cta' );

				// Shortcode input
				$('.ab-row-shortcode-input').toggle( contentType === 'shortcode' );

				// Elementor template input
				$('.ab-row-elementor-template').toggle( contentType === 'elementor_template' );

				// HTML input
				$('.ab-row-html-input').toggle( contentType === 'custom_html' );
			}

			$('#ab_popup_type, #ab_trigger_type, #ab_content_type').on('change', updateVisibility);
			updateVisibility();

			// -----------------------------------------------------------------
			// Auto-generate CSS selector from popup name
			// Only active on new popups (or when selector is still empty).
			// Once the user manually edits the selector field, auto-fill stops.
			// -----------------------------------------------------------------
			var selectorManuallyEdited = <?php echo ( ! $is_new && ! empty( $v['trigger_selector'] ) ) ? 'true' : 'false'; ?>;

			function labelToSelector( label ) {
				return '.open-' + label
					.toLowerCase()
					.trim()
					.replace( /[^a-z0-9\s-]/g, '' )
					.replace( /\s+/g, '-' )
					.replace( /-+/g, '-' )
					.replace( /^-|-$/g, '' );
			}

			// Auto-fill selector as label is typed.
			$('#ab_label').on('input', function() {
				if ( selectorManuallyEdited ) return;
				var generated = labelToSelector( $(this).val() );
				$('#ab_trigger_selector').val( generated.length > 6 ? generated : '' );
			});

			// Mark as manually edited when user directly changes the selector field.
			$('#ab_trigger_selector').on('input', function() {
				selectorManuallyEdited = true;
			});

			// -----------------------------------------------------------------
			// WP Media uploader
			// -----------------------------------------------------------------
			$('.ab-media-upload-btn').on('click', function() {
				var $btn     = $(this);
				var target   = $btn.data('target');
				var preview  = $btn.data('preview');
				var frame = wp.media({ title: 'Select Image', button: { text: 'Use Image' }, multiple: false });
				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$(target).val(attachment.url);
					$(preview).attr('src', attachment.url).show();
				});
				frame.open();
			});

			// -----------------------------------------------------------------
			// Copy shortcode
			// -----------------------------------------------------------------
			$(document).on('click', '.ab-copy-btn', function() {
				var text = $(this).data('clipboard-text');
				if (navigator.clipboard) {
					navigator.clipboard.writeText(text);
				} else {
					var $tmp = $('<input>');
					$('body').append($tmp);
					$tmp.val(text).select();
					document.execCommand('copy');
					$tmp.remove();
				}
				var $icon = $(this).find('.dashicons');
				$icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
				setTimeout(function(){ $icon.removeClass('dashicons-yes').addClass('dashicons-clipboard'); }, 1500);
			});

		}(jQuery));
		</script>
		<?php
	}

	// =========================================================================
	// UTILITY LABELS
	// =========================================================================

	private function get_type_icon( $type ) {
		$icons = array(
			'modal'        => '🪟',
			'notification' => '🔔',
			'exit_intent'  => '🚪',
		);
		return $icons[ $type ] ?? '🪟';
	}

	private function get_type_label( $type ) {
		$labels = array(
			'modal'        => __( 'Modal Overlay', 'ab-addon' ),
			'notification' => __( 'Notification Banner', 'ab-addon' ),
			'exit_intent'  => __( 'Exit Intent', 'ab-addon' ),
		);
		return $labels[ $type ] ?? $type;
	}

	private function get_trigger_label( $trigger ) {
		$labels = array(
			'page_load'   => __( 'Page Load', 'ab-addon' ),
			'click'       => __( 'Click', 'ab-addon' ),
			'scroll'      => __( 'Scroll', 'ab-addon' ),
			'exit_intent' => __( 'Exit Intent', 'ab-addon' ),
		);
		return $labels[ $trigger ] ?? $trigger;
	}

	private function get_content_label( $type ) {
		$labels = array(
			'text_only'          => __( 'Text Only', 'ab-addon' ),
			'text_image'         => __( 'Text & Image', 'ab-addon' ),
			'text_cta'           => __( 'Text, Image & Button', 'ab-addon' ),
			'elementor_template' => __( 'Elementor Template', 'ab-addon' ),
			'shortcode'          => __( 'Shortcode', 'ab-addon' ),
			'custom_html'        => __( 'Custom HTML', 'ab-addon' ),
		);
		return $labels[ $type ] ?? $type;
	}
}
