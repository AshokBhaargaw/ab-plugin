<?php
/**
 * AB SEO Admin Settings Page
 *
 * Dedicated Control Center under AB Settings > SEO.
 * Provides configuration for all Yoast SEO Premium features:
 * - Knowledge Graph & Social Profiles
 * - Titles & Meta Templates
 * - Redirects Manager (301, 302, 307, 410, 451, Regex, CSV Export/Import)
 * - 404 Error Monitor & Instant Redirect Resolver
 * - Internal Linking & Orphaned Content Re-indexer
 * - XML Sitemap Settings & Ping Controls
 * - IndexNow Instant Indexing Protocol
 * - Breadcrumbs Configuration
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 25 );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register submenu under AB Settings.
	 */
	public function register_submenu() {
		add_submenu_page(
			'ab-settings',
			__( 'SEO (Yoast Premium)', 'ab-addon' ),
			__( 'SEO Suite', 'ab-addon' ),
			'manage_options',
			'ab-seo',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts & styles.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ab-seo' ) === false ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'ab-seo-admin-css',
			AB_ADDON_URL . 'assets/css/ab-seo-admin.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-seo-admin-js',
			AB_ADDON_URL . 'assets/js/ab-seo-admin.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);

		wp_localize_script(
			'ab-seo-admin-js',
			'ab_seo_admin_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ab_seo_admin_nonce' ),
				'i18n'     => array(
					'confirm_delete' => __( 'Are you sure you want to delete this redirect?', 'ab-addon' ),
					'confirm_clear'  => __( 'Are you sure you want to clear all 404 logs?', 'ab-addon' ),
				),
			)
		);
	}

	/**
	 * Handle main settings save form.
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['ab_seo_settings_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ab-addon' ) );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_seo_settings_nonce'] ) ), 'ab_seo_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ab-addon' ) );
		}

		$current = AB_SEO::get_options();
		$input   = isset( $_POST['ab_seo'] ) && is_array( $_POST['ab_seo'] ) ? wp_unslash( $_POST['ab_seo'] ) : array();

		// Sanitize values
		$clean = array(
			// Knowledge Graph
			'site_type'             => sanitize_text_field( $input['site_type'] ?? 'Organization' ),
			'org_name'              => sanitize_text_field( $input['org_name'] ?? '' ),
			'org_alternate_name'    => sanitize_text_field( $input['org_alternate_name'] ?? '' ),
			'org_logo'              => esc_url_raw( $input['org_logo'] ?? '' ),
			'person_name'           => sanitize_text_field( $input['person_name'] ?? '' ),
			'social_facebook'       => esc_url_raw( $input['social_facebook'] ?? '' ),
			'social_twitter'        => esc_url_raw( $input['social_twitter'] ?? '' ),
			'social_instagram'      => esc_url_raw( $input['social_instagram'] ?? '' ),
			'social_linkedin'       => esc_url_raw( $input['social_linkedin'] ?? '' ),
			'social_youtube'        => esc_url_raw( $input['social_youtube'] ?? '' ),
			'social_pinterest'      => esc_url_raw( $input['social_pinterest'] ?? '' ),

			// Titles & Meta Defaults
			'title_separator'       => sanitize_text_field( $input['title_separator'] ?? '—' ),
			'home_title'            => sanitize_text_field( $input['home_title'] ?? '' ),
			'home_desc'             => sanitize_textarea_field( $input['home_desc'] ?? '' ),
			'post_title_tpl'        => sanitize_text_field( $input['post_title_tpl'] ?? '' ),
			'post_desc_tpl'         => sanitize_text_field( $input['post_desc_tpl'] ?? '' ),
			'page_title_tpl'        => sanitize_text_field( $input['page_title_tpl'] ?? '' ),
			'page_desc_tpl'         => sanitize_text_field( $input['page_desc_tpl'] ?? '' ),

			// Social Defaults
			'og_enable'             => ! empty( $input['og_enable'] ) ? '1' : '0',
			'default_share_image'   => esc_url_raw( $input['default_share_image'] ?? '' ),
			'twitter_enable'        => ! empty( $input['twitter_enable'] ) ? '1' : '0',
			'twitter_card_type'     => sanitize_text_field( $input['twitter_card_type'] ?? 'summary_large_image' ),

			// XML Sitemap
			'sitemap_enable'        => ! empty( $input['sitemap_enable'] ) ? '1' : '0',
			'sitemap_post_types'    => ! empty( $input['sitemap_post_types'] ) ? array_map( 'sanitize_text_field', (array) $input['sitemap_post_types'] ) : array( 'post', 'page' ),
			'sitemap_taxonomies'    => ! empty( $input['sitemap_taxonomies'] ) ? array_map( 'sanitize_text_field', (array) $input['sitemap_taxonomies'] ) : array( 'category', 'post_tag' ),
			'sitemap_include_images'=> ! empty( $input['sitemap_include_images'] ) ? '1' : '0',
			'sitemap_ping_google'   => ! empty( $input['sitemap_ping_google'] ) ? '1' : '0',

			// Redirect Automation
			'auto_redirect_slug'    => ! empty( $input['auto_redirect_slug'] ) ? '1' : '0',
			'auto_redirect_trash'   => ! empty( $input['auto_redirect_trash'] ) ? '1' : '0',
			'monitor_404'           => ! empty( $input['monitor_404'] ) ? '1' : '0',

			// Breadcrumbs
			'breadcrumbs_enable'    => ! empty( $input['breadcrumbs_enable'] ) ? '1' : '0',
			'breadcrumbs_sep'       => sanitize_text_field( $input['breadcrumbs_sep'] ?? '›' ),
			'breadcrumbs_home'      => sanitize_text_field( $input['breadcrumbs_home'] ?? 'Home' ),
			'breadcrumbs_prefix'    => sanitize_text_field( $input['breadcrumbs_prefix'] ?? '' ),

			// IndexNow
			'indexnow_enable'       => ! empty( $input['indexnow_enable'] ) ? '1' : '0',
			'indexnow_api_key'      => sanitize_text_field( $input['indexnow_api_key'] ?? '' ),
			'indexnow_auto_submit'  => ! empty( $input['indexnow_auto_submit'] ) ? '1' : '0',
		);

		update_option( 'ab_seo_settings', array_merge( $current, $clean ) );

		// Flush rewrites if sitemap status changed
		flush_rewrite_rules();

		wp_safe_redirect( add_query_arg( array( 'page' => 'ab-seo', 'saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Render settings page.
	 */
	public function render_page() {
		$options   = AB_SEO::get_options();
		$redirects = AB_SEO_Redirects::get_redirects();
		$logs_404  = AB_SEO_Redirects::get_404_logs();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap ab-settings-wrap ab-seo-admin-wrap">
			<!-- Header -->
			<div class="ab-settings-header ab-seo-hero-header">
				<div class="ab-settings-header__logo">
					<span class="dashicons dashicons-search"></span>
					<div>
						<h1><?php esc_html_e( 'AB SEO Suite — Yoast Premium Edition', 'ab-addon' ); ?></h1>
						<p class="ab-settings-header__desc"><?php esc_html_e( 'Enterprise-grade SEO tools: Multiple Focus Keyphrases, Redirects Manager, 404 Monitor, Internal Linking, XML Sitemaps, and Schema Graphs.', 'ab-addon' ); ?></p>
					</div>
				</div>
				<div class="ab-seo-header-status">
					<span class="ab-status-badge active"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'All Premium Features Active', 'ab-addon' ); ?></span>
				</div>
			</div>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e( 'SEO Settings saved successfully!', 'ab-addon' ); ?></strong></p>
				</div>
			<?php endif; ?>

			<!-- Main Navigation Tabs -->
			<nav class="nav-tab-wrapper ab-seo-nav-tabs">
				<a href="?page=ab-seo&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General & Knowledge Graph', 'ab-addon' ); ?>
				</a>
				<a href="?page=ab-seo&tab=titles" class="nav-tab <?php echo 'titles' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-heading"></span> <?php esc_html_e( 'Titles & Meta', 'ab-addon' ); ?>
				</a>
				<a href="?page=ab-seo&tab=redirects" class="nav-tab <?php echo 'redirects' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Redirects Manager', 'ab-addon' ); ?>
					<span class="ab-tab-count"><?php echo count( $redirects ); ?></span>
				</a>
				<a href="?page=ab-seo&tab=monitor404" class="nav-tab <?php echo 'monitor404' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-warning"></span> <?php esc_html_e( '404 Monitor', 'ab-addon' ); ?>
					<?php if ( ! empty( $logs_404 ) ) : ?>
						<span class="ab-tab-count ab-count-warn"><?php echo count( $logs_404 ); ?></span>
					<?php endif; ?>
				</a>
				<a href="?page=ab-seo&tab=internallinks" class="nav-tab <?php echo 'internallinks' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Internal Links & Orphaned', 'ab-addon' ); ?>
				</a>
				<a href="?page=ab-seo&tab=sitemap" class="nav-tab <?php echo 'sitemap' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'XML Sitemap', 'ab-addon' ); ?>
				</a>
				<a href="?page=ab-seo&tab=indexnow" class="nav-tab <?php echo 'indexnow' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'IndexNow (Instant)', 'ab-addon' ); ?>
				</a>
				<a href="?page=ab-seo&tab=breadcrumbs" class="nav-tab <?php echo 'breadcrumbs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-arrow-right-alt"></span> <?php esc_html_e( 'Breadcrumbs', 'ab-addon' ); ?>
				</a>
			</nav>

			<!-- TAB CONTENT WRAPPER -->
			<div class="ab-seo-tab-panel">

				<!-- 1. GENERAL & KNOWLEDGE GRAPH -->
				<?php if ( 'general' === $active_tab ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'ab_seo_save_settings', 'ab_seo_settings_nonce' ); ?>
						<div class="ab-seo-admin-card">
							<h3><?php esc_html_e( 'Knowledge Graph & Site Identity', 'ab-addon' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Search engines use structured Knowledge Graph data to display rich knowledge panels about your brand or person.', 'ab-addon' ); ?></p>

							<table class="form-table">
								<tr>
									<th scope="row"><label><?php esc_html_e( 'This site represents:', 'ab-addon' ); ?></label></th>
									<td>
										<select name="ab_seo[site_type]">
											<option value="Organization" <?php selected( $options['site_type'], 'Organization' ); ?>><?php esc_html_e( 'Organization / Company / Brand', 'ab-addon' ); ?></option>
											<option value="Person" <?php selected( $options['site_type'], 'Person' ); ?>><?php esc_html_e( 'Person / Individual', 'ab-addon' ); ?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Organization / Brand Name:', 'ab-addon' ); ?></label></th>
									<td>
										<input type="text" name="ab_seo[org_name]" class="regular-text" value="<?php echo esc_attr( $options['org_name'] ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Alternate Name / Acronym:', 'ab-addon' ); ?></label></th>
									<td>
										<input type="text" name="ab_seo[org_alternate_name]" class="regular-text" value="<?php echo esc_attr( $options['org_alternate_name'] ); ?>" placeholder="e.g. Ramdevra Mandir" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Logo (Image URL):', 'ab-addon' ); ?></label></th>
									<td>
										<div class="ab-media-picker-row">
											<input type="text" name="ab_seo[org_logo]" id="ab_org_logo" class="regular-text" value="<?php echo esc_attr( $options['org_logo'] ); ?>" />
											<button type="button" class="button ab-upload-media-btn" data-target="#ab_org_logo"><?php esc_html_e( 'Upload / Select Logo', 'ab-addon' ); ?></button>
										</div>
									</td>
								</tr>
							</table>

							<h4 class="ab-form-section-title"><?php esc_html_e( 'Social Media Profiles (sameAs Schema)', 'ab-addon' ); ?></h4>
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Facebook Page URL:', 'ab-addon' ); ?></th>
									<td><input type="url" name="ab_seo[social_facebook]" class="regular-text" value="<?php echo esc_attr( $options['social_facebook'] ); ?>" /></td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Twitter / X Profile URL:', 'ab-addon' ); ?></th>
									<td><input type="url" name="ab_seo[social_twitter]" class="regular-text" value="<?php echo esc_attr( $options['social_twitter'] ); ?>" /></td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Instagram Profile URL:', 'ab-addon' ); ?></th>
									<td><input type="url" name="ab_seo[social_instagram]" class="regular-text" value="<?php echo esc_attr( $options['social_instagram'] ); ?>" /></td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'YouTube Channel URL:', 'ab-addon' ); ?></th>
									<td><input type="url" name="ab_seo[social_youtube]" class="regular-text" value="<?php echo esc_attr( $options['social_youtube'] ); ?>" /></td>
								</tr>
							</table>
						</div>
						<?php submit_button( __( 'Save Changes', 'ab-addon' ) ); ?>
					</form>
				<?php endif; ?>

				<!-- 2. TITLES & META -->
				<?php if ( 'titles' === $active_tab ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'ab_seo_save_settings', 'ab_seo_settings_nonce' ); ?>
						<div class="ab-seo-admin-card">
							<h3><?php esc_html_e( 'Title & Meta Defaults', 'ab-addon' ); ?></h3>
							<table class="form-table">
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Title Separator:', 'ab-addon' ); ?></label></th>
									<td>
										<div class="ab-sep-picker">
											<?php
											$separators = array( '—', '-', '|', '•', '›', '»', '~', '/' );
											foreach ( $separators as $sep ) :
											?>
												<label class="ab-sep-option">
													<input type="radio" name="ab_seo[title_separator]" value="<?php echo esc_attr( $sep ); ?>" <?php checked( $options['title_separator'], $sep ); ?> />
													<span class="ab-sep-char"><?php echo esc_html( $sep ); ?></span>
												</label>
											<?php endforeach; ?>
										</div>
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Homepage Title Template:', 'ab-addon' ); ?></label></th>
									<td>
										<input type="text" name="ab_seo[home_title]" class="large-text" value="<?php echo esc_attr( $options['home_title'] ); ?>" />
										<p class="description"><?php esc_html_e( 'Variables: %%sitename%%, %%tagline%%, %%sep%%', 'ab-addon' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Homepage Meta Description:', 'ab-addon' ); ?></label></th>
									<td>
										<textarea name="ab_seo[home_desc]" rows="3" class="large-text"><?php echo esc_textarea( $options['home_desc'] ); ?></textarea>
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Post Title Template:', 'ab-addon' ); ?></label></th>
									<td>
										<input type="text" name="ab_seo[post_title_tpl]" class="large-text" value="<?php echo esc_attr( $options['post_title_tpl'] ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Page Title Template:', 'ab-addon' ); ?></label></th>
									<td>
										<input type="text" name="ab_seo[page_title_tpl]" class="large-text" value="<?php echo esc_attr( $options['page_title_tpl'] ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label><?php esc_html_e( 'Default Social Share Image:', 'ab-addon' ); ?></label></th>
									<td>
										<div class="ab-media-picker-row">
											<input type="text" name="ab_seo[default_share_image]" id="ab_default_share_image" class="regular-text" value="<?php echo esc_attr( $options['default_share_image'] ); ?>" />
											<button type="button" class="button ab-upload-media-btn" data-target="#ab_default_share_image"><?php esc_html_e( 'Upload Image', 'ab-addon' ); ?></button>
										</div>
									</td>
								</tr>
							</table>
						</div>
						<?php submit_button( __( 'Save Changes', 'ab-addon' ) ); ?>
					</form>
				<?php endif; ?>

				<!-- 3. REDIRECTS MANAGER (Yoast Premium Feature) -->
				<?php if ( 'redirects' === $active_tab ) : ?>
					<div class="ab-seo-admin-card">
						<div class="ab-card-header-flex">
							<div>
								<h3><?php esc_html_e( 'Redirects Manager (301, 302, 307, 410, 451)', 'ab-addon' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Prevent 404 dead links and preserve search engine rank automatically when changing URLs or deleting content.', 'ab-addon' ); ?></p>
							</div>
							<div class="ab-btn-group">
								<button type="button" class="button button-primary" id="ab-btn-open-redirect-modal">
									<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( '+ Add New Redirect', 'ab-addon' ); ?>
								</button>
								<a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=ab_seo_export_redirects' ) ); ?>" class="button button-secondary">
									<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ab-addon' ); ?>
								</a>
							</div>
						</div>

						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th style="width: 70px;"><?php esc_html_e( 'Type', 'ab-addon' ); ?></th>
									<th><?php esc_html_e( 'Old URL (Source)', 'ab-addon' ); ?></th>
									<th><?php esc_html_e( 'Redirect To (Target)', 'ab-addon' ); ?></th>
									<th style="width: 70px; text-align: center;"><?php esc_html_e( 'Hits', 'ab-addon' ); ?></th>
									<th style="width: 140px;"><?php esc_html_e( 'Note', 'ab-addon' ); ?></th>
									<th style="width: 90px; text-align: right;"><?php esc_html_e( 'Actions', 'ab-addon' ); ?></th>
								</tr>
							</thead>
							<tbody id="ab-redirects-table-body">
								<?php if ( empty( $redirects ) ) : ?>
									<tr>
										<td colspan="6" class="ab-empty-table-cell"><?php esc_html_e( 'No redirects configured yet. Click "+ Add New Redirect" above.', 'ab-addon' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( array_reverse( $redirects ) as $red ) : ?>
										<tr data-id="<?php echo esc_attr( $red['id'] ); ?>">
											<td><span class="ab-redirect-type-tag type-<?php echo esc_attr( $red['type'] ); ?>"><?php echo esc_html( $red['type'] ); ?></span></td>
											<td>
												<code><?php echo esc_html( $red['source_url'] ); ?></code>
												<?php if ( ! empty( $red['is_regex'] ) ) : ?>
													<span class="ab-badge-regex"><?php esc_html_e( 'REGEX', 'ab-addon' ); ?></span>
												<?php endif; ?>
											</td>
											<td>
												<?php if ( in_array( $red['type'], array( '410', '451' ), true ) ) : ?>
													<em><?php echo ( '410' === $red['type'] ) ? esc_html__( 'Content Deleted (410)', 'ab-addon' ) : esc_html__( 'Unavailable for Legal Reasons (451)', 'ab-addon' ); ?></em>
												<?php else : ?>
													<code><?php echo esc_html( $red['target_url'] ); ?></code>
												<?php endif; ?>
											</td>
											<td style="text-align: center;"><strong><?php echo intval( $red['hits'] ?? 0 ); ?></strong></td>
											<td><small><?php echo esc_html( $red['note'] ?? '' ); ?></small></td>
											<td style="text-align: right;">
												<button type="button" class="button-link-delete ab-del-redirect-btn" data-id="<?php echo esc_attr( $red['id'] ); ?>" title="<?php esc_attr_e( 'Delete redirect', 'ab-addon' ); ?>">
													<span class="dashicons dashicons-trash"></span>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<!-- Redirect Modal Form -->
					<div id="ab-redirect-modal" class="ab-modal-overlay" style="display: none;">
						<div class="ab-modal-box">
							<div class="ab-modal-header">
								<h3><?php esc_html_e( 'Create Redirect', 'ab-addon' ); ?></h3>
								<button type="button" class="ab-modal-close">&times;</button>
							</div>
							<div class="ab-modal-body">
								<div class="ab-form-group">
									<label><strong><?php esc_html_e( 'Redirect Type:', 'ab-addon' ); ?></strong></label>
									<select id="modal_red_type" class="large-text">
										<option value="301"><?php esc_html_e( '301 Moved Permanently (Recommended)', 'ab-addon' ); ?></option>
										<option value="302"><?php esc_html_e( '302 Found / Temporary', 'ab-addon' ); ?></option>
										<option value="307"><?php esc_html_e( '307 Temporary Redirect', 'ab-addon' ); ?></option>
										<option value="410"><?php esc_html_e( '410 Content Deleted', 'ab-addon' ); ?></option>
										<option value="451"><?php esc_html_e( '451 Unavailable for Legal Reasons', 'ab-addon' ); ?></option>
									</select>
								</div>
								<div class="ab-form-group">
									<label><strong><?php esc_html_e( 'Old URL (Relative path or pattern):', 'ab-addon' ); ?></strong></label>
									<input type="text" id="modal_red_source" class="large-text" placeholder="/old-page-slug" />
								</div>
								<div class="ab-form-group" id="modal_red_target_group">
									<label><strong><?php esc_html_e( 'Target URL (Destination):', 'ab-addon' ); ?></strong></label>
									<input type="text" id="modal_red_target" class="large-text" placeholder="/new-destination or https://..." />
								</div>
								<div class="ab-form-group">
									<label class="ab-checkbox-label">
										<input type="checkbox" id="modal_red_regex" value="1" />
										<strong><?php esc_html_e( 'Regular Expression (Regex)', 'ab-addon' ); ?></strong>
									</label>
								</div>
								<div class="ab-form-group">
									<label><strong><?php esc_html_e( 'Note / Reason:', 'ab-addon' ); ?></strong></label>
									<input type="text" id="modal_red_note" class="large-text" placeholder="e.g. Renamed post" />
								</div>
							</div>
							<div class="ab-modal-footer">
								<button type="button" class="button ab-modal-close"><?php esc_html_e( 'Cancel', 'ab-addon' ); ?></button>
								<button type="button" class="button button-primary" id="ab-save-redirect-btn"><?php esc_html_e( 'Save Redirect', 'ab-addon' ); ?></button>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- 4. 404 MONITOR -->
				<?php if ( 'monitor404' === $active_tab ) : ?>
					<div class="ab-seo-admin-card">
						<div class="ab-card-header-flex">
							<div>
								<h3><?php esc_html_e( '404 Error Monitor', 'ab-addon' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Logs 404 errors encountered by real visitors and search engine bots. Instantly redirect broken URLs with 1-click!', 'ab-addon' ); ?></p>
							</div>
							<?php if ( ! empty( $logs_404 ) ) : ?>
								<button type="button" class="button button-secondary" id="ab-clear-404-btn">
									<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Clear All 404 Logs', 'ab-addon' ); ?>
								</button>
							<?php endif; ?>
						</div>

						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Broken URL (404 Path)', 'ab-addon' ); ?></th>
									<th style="width: 70px; text-align: center;"><?php esc_html_e( 'Hits', 'ab-addon' ); ?></th>
									<th style="width: 150px;"><?php esc_html_e( 'Last Seen', 'ab-addon' ); ?></th>
									<th><?php esc_html_e( 'Referrer', 'ab-addon' ); ?></th>
									<th style="width: 160px; text-align: right;"><?php esc_html_e( 'Action', 'ab-addon' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $logs_404 ) ) : ?>
									<tr>
										<td colspan="5" class="ab-empty-table-cell">
											<span class="dashicons dashicons-yes-alt ab-icon-good"></span>
											<p><?php esc_html_e( 'Awesome! Zero 404 errors recorded.', 'ab-addon' ); ?></p>
										</td>
									</tr>
								<?php else : ?>
									<?php foreach ( array_reverse( $logs_404 ) as $item ) : ?>
										<tr>
											<td><code><?php echo esc_html( $item['url'] ); ?></code></td>
											<td style="text-align: center;"><span class="ab-count-badge"><?php echo intval( $item['hits'] ); ?></span></td>
											<td><small><?php echo esc_html( $item['last_seen'] ); ?></small></td>
											<td><small><?php echo ! empty( $item['referer'] ) ? esc_html( $item['referer'] ) : '<em>Direct / Search</em>'; ?></small></td>
											<td style="text-align: right;">
												<button type="button" class="button button-small button-primary ab-resolve-404-btn" data-url="<?php echo esc_attr( $item['url'] ); ?>">
													<span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Redirect', 'ab-addon' ); ?>
												</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- 5. INTERNAL LINKING & ORPHANED CONTENT -->
				<?php if ( 'internallinks' === $active_tab ) : ?>
					<div class="ab-seo-admin-card">
						<div class="ab-card-header-flex">
							<div>
								<h3><?php esc_html_e( 'Internal Linking & Orphaned Content Architecture', 'ab-addon' ); ?></h3>
								<p class="description"><?php esc_html_e( 'Internal links tell search engines what content is most important. Orphaned posts are articles with 0 incoming internal links.', 'ab-addon' ); ?></p>
							</div>
							<button type="button" class="button button-primary" id="ab-reindex-links-btn">
								<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Re-index Internal Links Now', 'ab-addon' ); ?>
							</button>
						</div>

						<div class="ab-seo-info-banner">
							<span class="dashicons dashicons-info"></span>
							<div>
								<strong><?php esc_html_e( 'How Orphaned Content Works in AB SEO:', 'ab-addon' ); ?></strong>
								<p><?php esc_html_e( 'Go to Posts > All Posts or Pages > All Pages. You will notice new columns showing exact incoming and outgoing link counts, plus a new filter view "Orphaned Content" at the top of the table. Click it anytime to see and fix all orphaned articles!', 'ab-addon' ); ?></p>
							</div>
						</div>

						<div class="ab-seo-stat-boxes">
							<div class="ab-stat-box">
								<div class="ab-stat-num">
									<?php
									$cs_count = count( get_posts( array( 'post_type' => 'post', 'meta_key' => '_ab_seo_is_cornerstone', 'meta_value' => '1', 'posts_per_page' => -1, 'fields' => 'ids' ) ) );
									echo intval( $cs_count );
									?>
								</div>
								<div class="ab-stat-label"><?php esc_html_e( 'Cornerstone Articles', 'ab-addon' ); ?></div>
							</div>
							<div class="ab-stat-box">
								<div class="ab-stat-num">
									<?php
									$orphaned_count = count( get_posts( array(
										'post_type' => 'post',
										'post_status' => 'publish',
										'posts_per_page' => -1,
										'fields' => 'ids',
										'meta_query' => array(
											'relation' => 'OR',
											array( 'key' => '_ab_seo_incoming_links_count', 'value' => '0', 'compare' => '=' ),
											array( 'key' => '_ab_seo_incoming_links_count', 'compare' => 'NOT EXISTS' ),
										),
									) ) );
									echo intval( $orphaned_count );
									?>
								</div>
								<div class="ab-stat-label"><?php esc_html_e( 'Orphaned Posts (0 Links)', 'ab-addon' ); ?></div>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- 6. XML SITEMAP -->
				<?php if ( 'sitemap' === $active_tab ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'ab_seo_save_settings', 'ab_seo_settings_nonce' ); ?>
						<div class="ab-seo-admin-card">
							<h3><?php esc_html_e( 'XML Sitemap Generator', 'ab-addon' ); ?></h3>
							<p class="description">
								<?php esc_html_e( 'Your XML Sitemap is automatically generated and available at:', 'ab-addon' ); ?>
								<a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" class="ab-sitemap-link">
									<code><?php echo esc_html( home_url( '/sitemap.xml' ) ); ?></code> <span class="dashicons dashicons-external"></span>
								</a>
							</p>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Enable XML Sitemap:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[sitemap_enable]" value="1" <?php checked( $options['sitemap_enable'], '1' ); ?> />
											<strong><?php esc_html_e( 'Generate XML Sitemap automatically', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Include Post Types:', 'ab-addon' ); ?></th>
									<td>
										<?php
										$all_pts = get_post_types( array( 'public' => true ), 'objects' );
										$selected_pts = (array) $options['sitemap_post_types'];
										foreach ( $all_pts as $pt_key => $pt_obj ) :
										?>
											<label style="display:inline-block; margin-right: 15px;">
												<input type="checkbox" name="ab_seo[sitemap_post_types][]" value="<?php echo esc_attr( $pt_key ); ?>" <?php checked( in_array( $pt_key, $selected_pts, true ) ); ?> />
												<?php echo esc_html( $pt_obj->labels->name ); ?> (<code><?php echo esc_html( $pt_key ); ?></code>)
											</label>
										<?php endforeach; ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Include Images:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[sitemap_include_images]" value="1" <?php checked( $options['sitemap_include_images'], '1' ); ?> />
											<strong><?php esc_html_e( 'Include featured and content images in XML sitemap', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Auto-Ping Search Engines:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[sitemap_ping_google]" value="1" <?php checked( $options['sitemap_ping_google'], '1' ); ?> />
											<strong><?php esc_html_e( 'Automatically ping Google and Bing whenever new content is published', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
							</table>
						</div>
						<?php submit_button( __( 'Save Changes', 'ab-addon' ) ); ?>
					</form>
				<?php endif; ?>

				<!-- 7. INDEXNOW -->
				<?php if ( 'indexnow' === $active_tab ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'ab_seo_save_settings', 'ab_seo_settings_nonce' ); ?>
						<div class="ab-seo-admin-card">
							<div class="ab-card-header-flex">
								<div>
									<h3><?php esc_html_e( 'IndexNow Instant Search Engine Indexing', 'ab-addon' ); ?></h3>
									<p class="description"><?php esc_html_e( 'The IndexNow protocol instantly tells Microsoft Bing, Yandex, and other search engines when your content changes. Pages get indexed in minutes!', 'ab-addon' ); ?></p>
								</div>
								<span class="ab-badge-premium"><?php esc_html_e( 'Instant Discovery', 'ab-addon' ); ?></span>
							</div>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'IndexNow Status:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[indexnow_enable]" value="1" <?php checked( $options['indexnow_enable'], '1' ); ?> />
											<strong><?php esc_html_e( 'Enable IndexNow Protocol', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Auto-submit on Publish:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[indexnow_auto_submit]" value="1" <?php checked( $options['indexnow_auto_submit'], '1' ); ?> />
											<strong><?php esc_html_e( 'Automatically ping IndexNow whenever a post or page is published/updated', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'IndexNow API Key:', 'ab-addon' ); ?></th>
									<td>
										<div class="ab-api-key-row">
											<input type="text" name="ab_seo[indexnow_api_key]" id="ab_indexnow_key" class="regular-text" value="<?php echo esc_attr( AB_SEO_IndexNow::get_api_key() ); ?>" readonly />
											<button type="button" class="button button-secondary" id="ab-gen-indexnow-key-btn"><?php esc_html_e( 'Regenerate Key', 'ab-addon' ); ?></button>
										</div>
										<p class="description">
											<?php esc_html_e( 'Key Verification URL:', 'ab-addon' ); ?>
											<a href="<?php echo esc_url( home_url( '/' . AB_SEO_IndexNow::get_api_key() . '.txt' ) ); ?>" target="_blank">
												<code><?php echo esc_html( home_url( '/' . AB_SEO_IndexNow::get_api_key() . '.txt' ) ); ?></code>
											</a>
										</p>
									</td>
								</tr>
							</table>

							<h4 class="ab-form-section-title"><?php esc_html_e( 'Manual Instant Submission Test', 'ab-addon' ); ?></h4>
							<div class="ab-indexnow-test-row">
								<input type="url" id="ab-indexnow-test-url" class="regular-text" value="<?php echo esc_url( home_url( '/' ) ); ?>" placeholder="https://..." />
								<button type="button" class="button button-primary" id="ab-test-indexnow-btn">
									<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Submit URL to IndexNow', 'ab-addon' ); ?>
								</button>
								<span id="ab-indexnow-status-msg"></span>
							</div>
						</div>
						<?php submit_button( __( 'Save Changes', 'ab-addon' ) ); ?>
					</form>
				<?php endif; ?>

				<!-- 8. BREADCRUMBS -->
				<?php if ( 'breadcrumbs' === $active_tab ) : ?>
					<form method="post" action="">
						<?php wp_nonce_field( 'ab_seo_save_settings', 'ab_seo_settings_nonce' ); ?>
						<div class="ab-seo-admin-card">
							<h3><?php esc_html_e( 'Breadcrumbs System', 'ab-addon' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Use breadcrumbs to improve site navigation for users and generate Schema.org BreadcrumbList rich snippets for search engines.', 'ab-addon' ); ?></p>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Enable Breadcrumbs:', 'ab-addon' ); ?></th>
									<td>
										<label class="ab-checkbox-label">
											<input type="checkbox" name="ab_seo[breadcrumbs_enable]" value="1" <?php checked( $options['breadcrumbs_enable'], '1' ); ?> />
											<strong><?php esc_html_e( 'Enable Breadcrumbs feature', 'ab-addon' ); ?></strong>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Breadcrumbs Separator:', 'ab-addon' ); ?></th>
									<td>
										<input type="text" name="ab_seo[breadcrumbs_sep]" class="small-text" value="<?php echo esc_attr( $options['breadcrumbs_sep'] ); ?>" />
										<span class="description"><?php esc_html_e( 'e.g. ›, », /, •', 'ab-addon' ); ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Homepage Anchor Text:', 'ab-addon' ); ?></th>
									<td>
										<input type="text" name="ab_seo[breadcrumbs_home]" class="regular-text" value="<?php echo esc_attr( $options['breadcrumbs_home'] ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Usage / Embedding:', 'ab-addon' ); ?></th>
									<td>
										<p><?php esc_html_e( 'Embed anywhere using shortcode:', 'ab-addon' ); ?> <code>[ab_breadcrumbs]</code></p>
										<p><?php esc_html_e( 'Or in PHP template files:', 'ab-addon' ); ?> <code>&lt;?php if ( function_exists( 'ab_breadcrumbs' ) ) { ab_breadcrumbs( array( 'echo' => true ) ); } ?&gt;</code></p>
									</td>
								</tr>
							</table>
						</div>
						<?php submit_button( __( 'Save Changes', 'ab-addon' ) ); ?>
					</form>
				<?php endif; ?>

			</div><!-- /ab-seo-tab-panel -->
		</div><!-- /wrap -->
		<?php
	}
}

new AB_SEO_Settings();
