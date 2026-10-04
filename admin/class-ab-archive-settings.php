<?php
/**
 * AB Archive Templates Admin Settings Page
 *
 * Provides an interface under AB Settings > Archive Templates where administrators
 * can assign Elementor templates to Custom Post Type archives.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Archive_Settings {

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
			__( 'Archive Templates', 'ab-addon' ),
			__( 'Archive Templates', 'ab-addon' ),
			'manage_options',
			'ab-archive-templates',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ab-archive-templates' ) === false && strpos( $hook, 'ab-settings' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'ab-admin-settings',
			AB_ADDON_URL . 'assets/css/admin-settings.css',
			array(),
			AB_ADDON_VERSION
		);
	}

	/**
	 * Handle settings form submission.
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['ab_archive_templates_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit these settings.', 'ab-addon' ) );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_archive_templates_nonce'] ) ), 'ab_save_archive_templates' ) ) {
			wp_die( esc_html__( 'Security check failed. Please reload and try again.', 'ab-addon' ) );
		}

		$raw_templates = isset( $_POST['ab_archive_templates'] ) ? (array) $_POST['ab_archive_templates'] : array();
		$clean_templates = array();

		foreach ( $raw_templates as $cpt_slug => $template_id ) {
			$clean_cpt = sanitize_key( $cpt_slug );
			$clean_id  = absint( $template_id );
			$clean_templates[ $clean_cpt ] = $clean_id;
		}

		update_option( AB_Archive_Template_Manager::OPTION_KEY, $clean_templates );

		// Clear Elementor CSS cache if Elementor is loaded.
		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=ab-archive-templates&saved=1' ) );
		exit;
	}

	/**
	 * Retrieve all published Elementor templates.
	 *
	 * @return array Array of [ template_id => template_title ].
	 */
	public static function get_elementor_templates() {
		$templates = array();

		$posts = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $p ) {
				$templates[ $p->ID ] = $p->post_title;
			}
		}

		return $templates;
	}

	/**
	 * Render the Archive Templates management page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$is_elementor_active = AB_Archive_Template_Manager::is_elementor_active();
		$elementor_templates = $is_elementor_active ? self::get_elementor_templates() : array();
		$assigned_templates  = AB_Archive_Template_Manager::get_archive_templates();
		$supported_cpts      = AB_Archive_Template_Manager::get_supported_post_types();
		?>
		<div class="wrap ab-settings-wrap" style="padding-top: 20px;">

			<!-- Navigation Tabs -->
			<nav class="nav-tab-wrapper ab-admin-nav-tabs" style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Popups', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-archive-templates' ) ); ?>" class="nav-tab nav-tab-active">
					<span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Archive Templates', 'ab-addon' ); ?>
				</a>
			</nav>

			<!-- Header Banner -->
			<div class="ab-settings-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
				<div class="ab-settings-header__logo">
					<span class="dashicons dashicons-layout" style="font-size: 32px; width: 32px; height: 32px; color: #fff;"></span>
					<div>
						<h1 style="color: #fff; margin: 0; font-size: 22px; font-weight: 700;"><?php esc_html_e( 'Archive Templates', 'ab-addon' ); ?></h1>
						<p class="ab-settings-header__desc" style="color: rgba(255,255,255,0.9); margin-top: 4px;">
							<?php esc_html_e( 'Select an Elementor template to use as the archive layout for each Custom Post Type. If no template is selected, WordPress uses the theme\'s default archive template.', 'ab-addon' ); ?>
						</p>
					</div>
				</div>
			</div>

			<!-- Feedback Notice -->
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( '✅ Archive templates configuration saved successfully!', 'ab-addon' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Requirement notice -->
			<?php if ( ! $is_elementor_active ) : ?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'Notice:', 'ab-addon' ); ?></strong>
						<?php esc_html_e( 'Elementor is not active. Install and activate Elementor to use Elementor Archive Templates.', 'ab-addon' ); ?>
					</p>
				</div>
			<?php else : ?>
				<div class="notice notice-info" style="border-left-color: #10b981;">
					<p>
						<span class="dashicons dashicons-info" style="color: #10b981; vertical-align: text-top;"></span>
						<?php esc_html_e( 'Requires Elementor Free or later. Design your archive visually using Elementor templates, then assign them below.', 'ab-addon' ); ?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=elementor_library' ) ); ?>" class="button button-small" style="margin-left: 10px;" target="_blank">
							<?php esc_html_e( 'Manage Elementor Templates ↗', 'ab-addon' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=elementor_library' ) ); ?>" class="button button-small button-secondary" style="margin-left: 5px;" target="_blank">
							<?php esc_html_e( '+ Add New Template', 'ab-addon' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<!-- Archive Templates Form -->
			<form method="post" action="" style="margin-top: 20px;">
				<?php wp_nonce_field( 'ab_save_archive_templates', 'ab_archive_templates_nonce' ); ?>

				<div class="ab-archive-templates-table-wrapper" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
					<table class="widefat striped" style="border: none;">
						<thead>
							<tr style="background: #f8fafc;">
								<th style="padding: 16px; font-weight: 700; width: 25%;"><?php esc_html_e( 'Post Type', 'ab-addon' ); ?></th>
								<th style="padding: 16px; font-weight: 700; width: 30%;"><?php esc_html_e( 'Archive URL', 'ab-addon' ); ?></th>
								<th style="padding: 16px; font-weight: 700; width: 45%;"><?php esc_html_e( 'Elementor Archive Template', 'ab-addon' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $supported_cpts ) ) : ?>
								<tr>
									<td colspan="3" style="padding: 30px; text-align: center; color: #64748b;">
										<?php esc_html_e( 'No public Custom Post Types with archives found.', 'ab-addon' ); ?>
									</td>
								</tr>
							<?php else : ?>
								<?php
								foreach ( $supported_cpts as $slug => $cpt_object ) :
									$archive_url = AB_Archive_Template_Manager::get_archive_url_for_post_type( $slug );
									$selected_id = isset( $assigned_templates[ $slug ] ) ? absint( $assigned_templates[ $slug ] ) : 0;
									if ( 'post' === $slug ) {
										$label = __( 'Blog (Posts)', 'ab-addon' );
									} else {
										$label = ! empty( $cpt_object->labels->name ) ? $cpt_object->labels->name : ucfirst( $slug );
									}
									$edit_url    = $selected_id ? admin_url( 'post.php?post=' . $selected_id . '&action=elementor' ) : '';
									?>
									<tr>
										<td style="padding: 18px 16px; vertical-align: middle;">
											<div style="font-weight: 700; font-size: 15px; color: #1e293b;">
												<?php echo esc_html( $label ); ?>
											</div>
											<code style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
												post_type = <?php echo esc_html( $slug ); ?>
											</code>
										</td>
										<td style="padding: 18px 16px; vertical-align: middle;">
											<?php if ( $archive_url ) : ?>
												<a href="<?php echo esc_url( $archive_url ); ?>" target="_blank" style="font-weight: 600; text-decoration: none; color: #2563eb; display: inline-flex; align-items: center; gap: 4px;">
													<?php echo esc_html( wp_make_link_relative( $archive_url ) ); ?>
													<span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px;"></span>
												</a>
											<?php else : ?>
												<span style="color: #94a3b8;"><?php esc_html_e( 'No archive URL', 'ab-addon' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="padding: 18px 16px; vertical-align: middle;">
											<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
												<select
													name="ab_archive_templates[<?php echo esc_attr( $slug ); ?>]"
													id="ab_template_<?php echo esc_attr( $slug ); ?>"
													style="min-width: 260px; height: 38px; border-radius: 6px; border-color: #cbd5e1;"
													<?php disabled( ! $is_elementor_active ); ?>
												>
													<option value="0"><?php esc_html_e( '— Theme Default (No Template) —', 'ab-addon' ); ?></option>
													<?php if ( ! empty( $elementor_templates ) ) : ?>
														<optgroup label="<?php esc_attr_e( 'Saved Elementor Templates', 'ab-addon' ); ?>">
															<?php foreach ( $elementor_templates as $tid => $ttitle ) : ?>
																<option value="<?php echo esc_attr( $tid ); ?>" <?php selected( $selected_id, $tid ); ?>>
																	<?php echo esc_html( $ttitle ? $ttitle : '#' . $tid ); ?>
																</option>
															<?php endforeach; ?>
														</optgroup>
													<?php endif; ?>
												</select>

												<?php if ( $selected_id && $edit_url ) : ?>
													<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank" class="button button-secondary" style="height: 38px; display: inline-flex; align-items: center; gap: 4px;">
														<span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'Edit in Elementor', 'ab-addon' ); ?>
													</a>
												<?php endif; ?>

												<?php if ( $selected_id > 0 ) : ?>
													<span class="ab-template-status" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 600;">
														<span class="dashicons dashicons-yes-alt" style="font-size: 15px; width: 15px; height: 15px;"></span> <?php esc_html_e( 'Custom Archive Active', 'ab-addon' ); ?>
													</span>
												<?php else : ?>
													<span class="ab-template-status" style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 12px; font-weight: 600;">
														<?php esc_html_e( 'Theme Default Fallback', 'ab-addon' ); ?>
													</span>
												<?php endif; ?>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<p class="submit" style="margin-top: 24px;">
					<button type="submit" class="button button-primary button-hero" style="font-weight: 700; padding: 0 28px; height: 44px; display: inline-flex; align-items: center; gap: 8px;">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save Archive Templates', 'ab-addon' ); ?>
					</button>
				</p>
			</form>

		</div>
		<?php
	}
}
