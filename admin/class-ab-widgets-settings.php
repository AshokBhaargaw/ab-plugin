<?php
/**
 * AB Widgets Admin Settings Page
 *
 * Dedicated control center under AB Settings > Widgets where administrators
 * can toggle, search, filter, and batch-activate/deactivate all AB Addon widgets.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/class-ab-widgets-manager.php';

class AB_Widgets_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 15 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX Handlers
		add_action( 'wp_ajax_ab_toggle_widget', array( $this, 'ajax_toggle_widget' ) );
		add_action( 'wp_ajax_ab_batch_toggle_widgets', array( $this, 'ajax_batch_toggle_widgets' ) );
	}

	/**
	 * Register submenu under AB Settings.
	 */
	public function register_submenu() {
		add_submenu_page(
			'ab-settings',
			__( 'Widgets', 'ab-addon' ),
			__( 'Widgets', 'ab-addon' ),
			'manage_options',
			'ab-widgets',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts & styles for Widgets settings page.
	 *
	 * @param string $hook Page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ab-widgets' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'ab-admin-settings',
			AB_ADDON_URL . 'assets/css/admin-settings.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-admin-widgets-js',
			AB_ADDON_URL . 'assets/js/admin-widgets.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);

		wp_localize_script(
			'ab-admin-widgets-js',
			'ab_widgets_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ab_widgets_nonce' ),
				'strings'  => array(
					'saved'         => __( 'Widget setting saved!', 'ab-addon' ),
					'activated_all' => __( 'All widgets activated!', 'ab-addon' ),
					'deactivated'   => __( 'All non-core widgets deactivated!', 'ab-addon' ),
					'error'         => __( 'Failed to save settings. Please try again.', 'ab-addon' ),
				),
			)
		);
	}

	/**
	 * AJAX Handler for single widget toggle.
	 */
	public function ajax_toggle_widget() {
		check_ajax_referer( 'ab_widgets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ab-addon' ) ) );
		}

		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_key( wp_unslash( $_POST['widget_id'] ) ) : '';
		$status    = isset( $_POST['status'] ) && '1' === $_POST['status'];

		if ( empty( $widget_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid widget ID.', 'ab-addon' ) ) );
		}

		$manager = AB_Widgets_Manager::instance();
		$success = $manager->set_widget_status( $widget_id, $status );

		$active_count = count( $manager->get_active_widgets() );
		$total_count  = count( $manager->get_catalog() );

		wp_send_json_success( array(
			'widget_id'    => $widget_id,
			'status'       => $status,
			'active_count' => $active_count,
			'total_count'  => $total_count,
			'message'      => $status ? __( 'Widget activated', 'ab-addon' ) : __( 'Widget deactivated', 'ab-addon' ),
		) );
	}

	/**
	 * AJAX Handler for batch activate/deactivate.
	 */
	public function ajax_batch_toggle_widgets() {
		check_ajax_referer( 'ab_widgets_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ab-addon' ) ) );
		}

		$action = isset( $_POST['batch_action'] ) ? sanitize_key( wp_unslash( $_POST['batch_action'] ) ) : '';
		$manager = AB_Widgets_Manager::instance();

		if ( 'activate_all' === $action ) {
			$manager->activate_all();
		} elseif ( 'deactivate_all' === $action ) {
			$manager->deactivate_all();
		}

		$active_count = count( $manager->get_active_widgets() );
		$total_count  = count( $manager->get_catalog() );

		wp_send_json_success( array(
			'action'       => $action,
			'active_count' => $active_count,
			'total_count'  => $total_count,
			'message'      => ( 'activate_all' === $action ) ? __( 'All widgets activated!', 'ab-addon' ) : __( 'All widgets deactivated!', 'ab-addon' ),
		) );
	}

	/**
	 * Render the Widgets admin dashboard page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$manager        = AB_Widgets_Manager::instance();
		$catalog        = $manager->get_catalog();
		$active_widgets = $manager->get_active_widgets();
		$active_count   = count( $active_widgets );
		$total_count    = count( $catalog );

		$created_count = 0;
		foreach ( $catalog as $w ) {
			if ( ! empty( $w['is_created'] ) ) {
				$created_count++;
			}
		}
		?>
		<div class="wrap ab-settings-wrap ab-widgets-page">

			<!-- Navigation Tabs -->
			<nav class="nav-tab-wrapper ab-admin-nav-tabs" style="margin: 20px 0 24px 0;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-megaphone"></span> <?php esc_html_e( 'Popups', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-widgets' ) ); ?>" class="nav-tab nav-tab-active">
					<span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e( 'Widgets', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-archive-templates' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-layout"></span> <?php esc_html_e( 'Archive Templates', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Sliders', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-weather-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Ramdevra Weather', 'ab-addon' ); ?>
				</a>
			</nav>

			<!-- Toast Notification -->
			<div id="ab-toast" class="ab-toast" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<span class="ab-toast-text"><?php esc_html_e( 'Settings updated!', 'ab-addon' ); ?></span>
			</div>

			<!-- Main Container with 2-column layout -->
			<div class="ab-widgets-layout" style="display: flex; gap: 24px; align-items: flex-start;">

				<!-- Left Column: Widgets Grid & Controls -->
				<div class="ab-widgets-main" style="flex: 1; min-width: 0;">

					<!-- Header & Actions Bar -->
					<div class="ab-widgets-header-bar" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 24px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
						
						<div style="display: flex; align-items: center; gap: 14px;">
							<h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">
								<?php esc_html_e( 'Widgets / Features', 'ab-addon' ); ?>
							</h1>
							<span class="ab-badge ab-badge-counter" id="ab-stats-counter" style="background: #f1f5f9; color: #475569; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 13px;">
								<span id="ab-active-num"><?php echo esc_html( $active_count ); ?></span> / <?php echo esc_html( $total_count ); ?> <?php esc_html_e( 'Active', 'ab-addon' ); ?>
							</span>
						</div>

						<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
							<!-- Search Input -->
							<div class="ab-search-box" style="position: relative;">
								<span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></span>
								<input type="text" id="ab-widget-search" placeholder="<?php esc_attr_e( 'Search widgets...', 'ab-addon' ); ?>" style="padding-left: 36px; padding-right: 12px; height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; width: 220px; font-size: 13px; outline: none;" />
							</div>

							<!-- Activate / Deactivate Buttons -->
							<button type="button" class="button button-secondary ab-batch-btn" data-action="activate_all" style="height: 38px; padding: 0 14px; font-size: 13px; font-weight: 600; border-radius: 8px;">
								<?php esc_html_e( 'Activate All', 'ab-addon' ); ?>
							</button>

							<button type="button" class="button button-secondary ab-batch-btn" data-action="deactivate_all" style="height: 38px; padding: 0 14px; font-size: 13px; font-weight: 600; border-radius: 8px; color: #64748b;">
								<?php esc_html_e( 'Deactivate Unused', 'ab-addon' ); ?>
							</button>
						</div>

					</div>

					<!-- Category Filter Pills -->
					<div class="ab-category-filters" style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
						<button type="button" class="ab-filter-pill active" data-category="all" style="border: none; background: #6c63ff; color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'All', 'ab-addon' ); ?> (<?php echo esc_html( $total_count ); ?>)
						</button>
						<button type="button" class="ab-filter-pill" data-category="created" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<span style="color: #10b981; margin-right: 4px;">●</span> <?php esc_html_e( 'Already Created', 'ab-addon' ); ?> (<?php echo esc_html( $created_count ); ?>)
						</button>
						<button type="button" class="ab-filter-pill" data-category="content" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'Content', 'ab-addon' ); ?>
						</button>
						<button type="button" class="ab-filter-pill" data-category="creative" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'Creative', 'ab-addon' ); ?>
						</button>
						<button type="button" class="ab-filter-pill" data-category="site" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'Site & SEO', 'ab-addon' ); ?>
						</button>
						<button type="button" class="ab-filter-pill" data-category="woocommerce" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'WooCommerce', 'ab-addon' ); ?>
						</button>
						<button type="button" class="ab-filter-pill" data-category="forms" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'Forms', 'ab-addon' ); ?>
						</button>
						<button type="button" class="ab-filter-pill" data-category="extensions" style="border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
							<?php esc_html_e( 'Extensions', 'ab-addon' ); ?>
						</button>
					</div>

					<!-- Widgets Grid (4 columns matching screenshot) -->
					<div class="ab-widgets-grid" id="ab-widgets-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
						<?php foreach ( $catalog as $id => $widget ) : ?>
							<?php
							$is_active   = ! empty( $active_widgets[ $id ] );
							$is_created  = ! empty( $widget['is_created'] );
							$category    = $widget['category'] ?? 'content';
							$icon_class  = ! empty( $widget['icon'] ) ? $widget['icon'] : 'dashicons-admin-generic';
							$title       = $widget['title'];
							$description = $widget['description'] ?? '';
							?>
							<div class="ab-widget-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?> <?php echo $is_created ? 'is-created' : ''; ?>"
								data-id="<?php echo esc_attr( $id ); ?>"
								data-title="<?php echo esc_attr( strtolower( $title ) ); ?>"
								data-category="<?php echo esc_attr( $category ); ?>"
								data-created="<?php echo $is_created ? '1' : '0'; ?>"
								style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; position: relative; display: flex; flex-direction: column; justify-content: space-between; min-height: 84px; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">

								<!-- Top Row: Icon & Toggle Switch -->
								<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
									
									<div class="ab-widget-icon" style="color: #64748b; font-size: 20px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
										<span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span>
									</div>

									<!-- iOS Switch Toggle -->
									<label class="ab-switch" style="position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; cursor: pointer;">
										<input type="checkbox" class="ab-widget-toggle" data-widget-id="<?php echo esc_attr( $id ); ?>" <?php checked( $is_active, true ); ?> style="opacity: 0; width: 0; height: 0; position: absolute;">
										<span class="ab-slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .25s; border-radius: 20px;"></span>
									</label>

								</div>

								<!-- Widget Title -->
								<div style="margin-top: auto;">
									<div class="ab-widget-name" style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3; margin-bottom: 4px;" title="<?php echo esc_attr( $description ); ?>">
										<?php echo esc_html( $title ); ?>
									</div>

									<!-- Badges row -->
									<div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
										<?php if ( $is_created ) : ?>
											<span class="ab-tag-created" style="display: inline-block; font-size: 10px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">
												<?php esc_html_e( 'Created', 'ab-addon' ); ?>
											</span>
										<?php else : ?>
											<span style="color: #94a3b8; font-size: 11px;">
												<?php echo esc_html( ucfirst( $category ) ); ?>
											</span>
										<?php endif; ?>

										<span class="dashicons dashicons-media-document" style="font-size: 14px; width: 14px; height: 14px; color: #cbd5e1;" title="<?php echo esc_attr( $description ); ?>"></span>
									</div>
								</div>

							</div>
						<?php endforeach; ?>
					</div>

					<!-- No Results Found Box -->
					<div id="ab-no-widgets-found" style="display: none; padding: 40px; text-align: center; background: #ffffff; border: 2px dashed #e2e8f0; border-radius: 12px; margin-top: 16px;">
						<span class="dashicons dashicons-search" style="font-size: 36px; width: 36px; height: 36px; color: #94a3b8; margin-bottom: 12px;"></span>
						<p style="margin: 0; font-size: 15px; font-weight: 600; color: #475569;">
							<?php esc_html_e( 'No matching widgets found.', 'ab-addon' ); ?>
						</p>
					</div>

				</div>

				<!-- Right Sidebar: Quick Access & Stats (matching screenshot) -->
				<div class="ab-widgets-sidebar" style="width: 300px; flex-shrink: 0;">

					<!-- Pro Banner Card (matching screenshot) -->
					<div class="ab-sidebar-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
						
						<div style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius: 8px; padding: 20px 16px; color: #ffffff; margin-bottom: 16px; text-align: center;">
							<div style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #a5b4fc; margin-bottom: 6px;">
								⚡ <?php esc_html_e( 'AB Elementor Suite', 'ab-addon' ); ?>
							</div>
							<h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 800; color: #ffffff;">
								<?php esc_html_e( 'Empower Your Ramdevra Website', 'ab-addon' ); ?>
							</h3>
							<p style="margin: 0; font-size: 12px; line-height: 1.5; color: #c7d2fe;">
								<?php esc_html_e( 'Modular, lightweight, and blazing-fast Elementor controls crafted specially for your portal.', 'ab-addon' ); ?>
							</p>
						</div>

						<h4 style="margin: 0 0 12px 0; font-size: 13px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">
							<?php esc_html_e( 'Included Features', 'ab-addon' ); ?>
						</h4>

						<ul style="margin: 0 0 16px 0; padding: 0; list-style: none; font-size: 13px; color: #475569;">
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( '65+ Elementor Widgets', 'ab-addon' ); ?>
							</li>
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( 'Hotels & Booking Directory', 'ab-addon' ); ?>
							</li>
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( 'Live Ramdevra Weather Cards', 'ab-addon' ); ?>
							</li>
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( 'Hero Banner & Nested Sliders', 'ab-addon' ); ?>
							</li>
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( 'Popup & Banner Notification Modals', 'ab-addon' ); ?>
							</li>
							<li style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
								<span style="color: #6c63ff; font-weight: 700;">✓</span> <?php esc_html_e( '1-Click Duplicator Feature', 'ab-addon' ); ?>
							</li>
						</ul>

					</div>

					<!-- Quick Access Links Card (matching screenshot) -->
					<div class="ab-sidebar-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
						
						<h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 700; color: #1e293b;">
							<?php esc_html_e( 'Quick Access', 'ab-addon' ); ?>
						</h4>

						<div style="display: flex; flex-direction: column; gap: 10px;">
							
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-settings' ) ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-size: 13px; font-weight: 600; transition: all 0.2s;">
								<span class="dashicons dashicons-megaphone" style="color: #6c63ff;"></span>
								<span><?php esc_html_e( 'Popups Manager', 'ab-addon' ); ?></span>
							</a>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-archive-templates' ) ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-size: 13px; font-weight: 600; transition: all 0.2s;">
								<span class="dashicons dashicons-layout" style="color: #6c63ff;"></span>
								<span><?php esc_html_e( 'Archive Templates', 'ab-addon' ); ?></span>
							</a>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-size: 13px; font-weight: 600; transition: all 0.2s;">
								<span class="dashicons dashicons-images-alt2" style="color: #6c63ff;"></span>
								<span><?php esc_html_e( 'Hero Sliders', 'ab-addon' ); ?></span>
							</a>

							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-weather-settings' ) ); ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-size: 13px; font-weight: 600; transition: all 0.2s;">
								<span class="dashicons dashicons-cloud" style="color: #6c63ff;"></span>
								<span><?php esc_html_e( 'Ramdevra Weather Settings', 'ab-addon' ); ?></span>
							</a>

						</div>

					</div>

				</div>

			</div>

		</div>
		<?php
	}
}
