<?php
/**
 * AB SEO Post Meta Box
 *
 * Provides the interactive SEO editor on all public post types:
 * - Multiple Focus Keywords & Synonyms (up to 5 keyphrases)
 * - Real-time SEO & Readability Content Analysis
 * - Google Snippet Simulation (Desktop & Mobile)
 * - Social Previews (Facebook OpenGraph & Twitter/X Cards)
 * - Advanced Schema.org Controls & Dynamic FAQ Builder
 * - Advanced Robots Meta & Canonical URL
 * - Cornerstone Content Toggle
 * - Live Internal Linking Suggestions Panel
 * - Smart AI Generator for Meta Titles & Descriptions
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_SEO_Meta_Box {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX for Smart AI / Template meta generator
		add_action( 'wp_ajax_ab_seo_generate_smart_meta', array( $this, 'ajax_generate_smart_meta' ) );
	}

	/**
	 * Register SEO Meta Box on all public post types.
	 */
	public function register_meta_box() {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'ab-seo-meta-box',
				__( 'AB SEO (Yoast Premium Features)', 'ab-addon' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue styles and scripts for post editor.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
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
			'ab-seo-metabox-js',
			AB_ADDON_URL . 'assets/js/ab-seo-metabox.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);

		wp_localize_script(
			'ab-seo-metabox-js',
			'ab_seo_metabox_data',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'ab_seo_metabox_nonce' ),
				'site_name'    => get_bloginfo( 'name' ),
				'home_url'     => home_url( '/' ),
				'default_sep'  => '—',
				'i18n'         => array(
					'copied'   => __( 'Copied to clipboard!', 'ab-addon' ),
					'good'     => __( 'Good', 'ab-addon' ),
					'ok'       => __( 'OK', 'ab-addon' ),
					'bad'      => __( 'Needs Improvement', 'ab-addon' ),
				),
			)
		);
	}

	/**
	 * Render Meta Box HTML.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'ab_seo_save_meta', 'ab_seo_meta_nonce' );

		$meta = get_post_meta( $post->ID, '_ab_seo_meta', true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$focus_keywords = ! empty( $meta['focus_keywords'] ) && is_array( $meta['focus_keywords'] ) ? $meta['focus_keywords'] : array(
			array( 'keyword' => '', 'synonyms' => '' ),
		);

		$seo_title        = $meta['seo_title'] ?? '';
		$meta_description = $meta['meta_description'] ?? '';
		$canonical_url    = $meta['canonical_url'] ?? '';
		$noindex          = ! empty( $meta['noindex'] );
		$nofollow         = ! empty( $meta['nofollow'] );
		$noarchive        = ! empty( $meta['noarchive'] );
		$nosnippet        = ! empty( $meta['nosnippet'] );
		$exclude_sitemap  = ! empty( $meta['exclude_sitemap'] );
		$is_cornerstone   = ! empty( $meta['is_cornerstone'] );

		// Social
		$og_title         = $meta['og_title'] ?? '';
		$og_description   = $meta['og_description'] ?? '';
		$og_image         = $meta['og_image'] ?? '';
		$twitter_title    = $meta['twitter_title'] ?? '';
		$twitter_desc     = $meta['twitter_description'] ?? '';
		$twitter_image    = $meta['twitter_image'] ?? '';

		// Schema
		$schema_page_type    = $meta['schema_page_type'] ?? 'WebPage';
		$schema_article_type = $meta['schema_article_type'] ?? ( ( 'post' === $post->post_type ) ? 'BlogPosting' : 'None' );
		$faq_items           = ! empty( $meta['faq_items'] ) && is_array( $meta['faq_items'] ) ? $meta['faq_items'] : array();

		// Overall score
		$score = get_post_meta( $post->ID, '_ab_seo_score', true ) ?: 'bad';
		$read  = get_post_meta( $post->ID, '_ab_seo_readability_score', true ) ?: 'ok';
		?>
		<div class="ab-seo-wrap">
			<!-- Top Overview Header -->
			<div class="ab-seo-top-bar">
				<div class="ab-seo-brand">
					<span class="dashicons dashicons-chart-area"></span>
					<strong>AB SEO</strong>
					<span class="ab-seo-badge-pro"><?php esc_html_e( 'PREMIUM SUITE', 'ab-addon' ); ?></span>
				</div>
				<div class="ab-seo-scores-summary">
					<div class="ab-seo-score-item">
						<span><?php esc_html_e( 'SEO Score:', 'ab-addon' ); ?></span>
						<span id="ab-seo-overall-pill" class="ab-seo-pill ab-seo-pill-<?php echo esc_attr( $score ); ?>">
							<?php echo ( 'good' === $score ) ? esc_html__( 'Good', 'ab-addon' ) : ( ( 'ok' === $score ) ? esc_html__( 'OK', 'ab-addon' ) : esc_html__( 'Needs Improvement', 'ab-addon' ) ); ?>
						</span>
					</div>
					<div class="ab-seo-score-item">
						<span><?php esc_html_e( 'Readability:', 'ab-addon' ); ?></span>
						<span id="ab-seo-read-pill" class="ab-seo-pill ab-seo-pill-<?php echo esc_attr( $read ); ?>">
							<?php echo ( 'good' === $read ) ? esc_html__( 'Good', 'ab-addon' ) : ( ( 'ok' === $read ) ? esc_html__( 'OK', 'ab-addon' ) : esc_html__( 'Needs Improvement', 'ab-addon' ) ); ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Tab Navigation -->
			<div class="ab-seo-tabs-nav">
				<button type="button" class="ab-seo-tab-btn active" data-tab="tab-content-seo">
					<span class="dashicons dashicons-search"></span> <?php esc_html_e( 'SEO Content', 'ab-addon' ); ?>
				</button>
				<button type="button" class="ab-seo-tab-btn" data-tab="tab-social">
					<span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Social Previews', 'ab-addon' ); ?>
				</button>
				<button type="button" class="ab-seo-tab-btn" data-tab="tab-schema">
					<span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Schema.org', 'ab-addon' ); ?>
				</button>
				<button type="button" class="ab-seo-tab-btn" data-tab="tab-internal-links">
					<span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Internal Linking', 'ab-addon' ); ?>
				</button>
				<button type="button" class="ab-seo-tab-btn" data-tab="tab-advanced">
					<span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Advanced / Robots', 'ab-addon' ); ?>
				</button>
			</div>

			<!-- TAB 1: SEO CONTENT & MULTIPLE KEYWORDS -->
			<div id="tab-content-seo" class="ab-seo-tab-content active">
				
				<!-- Multiple Focus Keywords Selector (Yoast Premium Feature) -->
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Focus Keyphrases & Synonyms (Up to 5 Keywords)', 'ab-addon' ); ?></h3>
						<span class="ab-badge-premium"><?php esc_html_e( 'Yoast Premium Exclusive', 'ab-addon' ); ?></span>
					</div>
					<p class="ab-seo-desc"><?php esc_html_e( 'Optimize this post for multiple related keywords, synonyms, and variations simultaneously.', 'ab-addon' ); ?></p>

					<!-- Keyword Tabs List -->
					<div class="ab-seo-keyword-tabs" id="ab-seo-keyword-tabs">
						<?php for ( $i = 0; $i < 5; $i++ ) :
							$kw_data = $focus_keywords[ $i ] ?? array( 'keyword' => '', 'synonyms' => '' );
							$kw_text = ! empty( $kw_data['keyword'] ) ? $kw_data['keyword'] : ( 0 === $i ? __( 'Primary Keyphrase', 'ab-addon' ) : sprintf( __( '+ Add Keyphrase %d', 'ab-addon' ), $i + 1 ) );
							$has_val = ! empty( $kw_data['keyword'] );
						?>
							<button type="button" class="ab-seo-kw-tab-btn <?php echo 0 === $i ? 'active' : ''; ?> <?php echo $has_val ? 'has-kw' : ''; ?>" data-kw-index="<?php echo esc_attr( $i ); ?>">
								<span class="ab-seo-kw-status-dot"></span>
								<span class="ab-seo-kw-label"><?php echo esc_html( $kw_text ); ?></span>
							</button>
						<?php endfor; ?>
					</div>

					<!-- Keyword Input Panels -->
					<div id="ab-seo-keyword-panels">
						<?php for ( $i = 0; $i < 5; $i++ ) :
							$kw_data = $focus_keywords[ $i ] ?? array( 'keyword' => '', 'synonyms' => '' );
						?>
							<div class="ab-seo-kw-panel <?php echo 0 === $i ? 'active' : ''; ?>" data-kw-panel="<?php echo esc_attr( $i ); ?>">
								<div class="ab-seo-field-row">
									<div class="ab-seo-field-col">
										<label><strong><?php echo 0 === $i ? esc_html__( 'Focus Keyphrase (Primary):', 'ab-addon' ) : sprintf( esc_html__( 'Additional Keyphrase %d:', 'ab-addon' ), $i + 1 ); ?></strong></label>
										<input type="text" name="ab_seo[focus_keywords][<?php echo esc_attr( $i ); ?>][keyword]" class="ab-seo-kw-input regular-text" value="<?php echo esc_attr( $kw_data['keyword'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. ramdevra temple timings', 'ab-addon' ); ?>" />
									</div>
									<div class="ab-seo-field-col">
										<label><strong><?php esc_html_e( 'Synonyms (comma separated):', 'ab-addon' ); ?></strong></label>
										<input type="text" name="ab_seo[focus_keywords][<?php echo esc_attr( $i ); ?>][synonyms]" class="ab-seo-synonyms-input regular-text" value="<?php echo esc_attr( $kw_data['synonyms'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. ramdevji darshan time, ramdevra schedule', 'ab-addon' ); ?>" />
									</div>
								</div>
							</div>
						<?php endfor; ?>
					</div>
				</div>

				<!-- Google Snippet Preview Simulation -->
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Google Snippet Preview', 'ab-addon' ); ?></h3>
						<div class="ab-seo-device-toggle">
							<button type="button" class="ab-device-btn active" data-device="mobile">
								<span class="dashicons dashicons-smartphone"></span> <?php esc_html_e( 'Mobile', 'ab-addon' ); ?>
							</button>
							<button type="button" class="ab-device-btn" data-device="desktop">
								<span class="dashicons dashicons-desktop"></span> <?php esc_html_e( 'Desktop', 'ab-addon' ); ?>
							</button>
						</div>
					</div>

					<!-- Visual Google Preview Box -->
					<div class="ab-google-preview-box mobile-view" id="ab-google-preview">
						<div class="ab-google-site-line">
							<span class="ab-google-favicon"><span class="dashicons dashicons-admin-site-alt3"></span></span>
							<div class="ab-google-url-wrap">
								<span class="ab-google-sitename"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
								<span class="ab-google-url" id="ab-google-preview-url"><?php echo esc_html( get_permalink( $post ) ); ?></span>
							</div>
						</div>
						<div class="ab-google-title" id="ab-google-preview-title"><?php echo esc_html( ! empty( $seo_title ) ? $seo_title : get_the_title( $post ) ); ?></div>
						<div class="ab-google-desc" id="ab-google-preview-desc"><?php echo esc_html( ! empty( $meta_description ) ? $meta_description : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 ) ); ?></div>
					</div>

					<!-- Smart AI / Template Generation Bar -->
					<div class="ab-seo-ai-bar">
						<button type="button" class="button button-secondary" id="ab-seo-smart-gen-btn">
							<span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'AI / Smart Generate Title & Description', 'ab-addon' ); ?>
						</button>
						<span class="ab-seo-spinner spinner"></span>
						<span class="description"><?php esc_html_e( 'One-click generate high-converting, optimal length titles and descriptions.', 'ab-addon' ); ?></span>
					</div>

					<!-- SEO Title Input -->
					<div class="ab-seo-field">
						<label for="ab_seo_title">
							<strong><?php esc_html_e( 'SEO Title:', 'ab-addon' ); ?></strong>
							<span class="ab-meter-label" id="ab-title-meter-label">0 / 60</span>
						</label>
						<div class="ab-meter-bar-wrap"><div class="ab-meter-bar" id="ab-title-meter-bar"></div></div>
						<input type="text" id="ab_seo_title" name="ab_seo[seo_title]" class="large-text" value="<?php echo esc_attr( $seo_title ); ?>" placeholder="<?php esc_attr_e( '%%title%% — %%sitename%%', 'ab-addon' ); ?>" />
						<div class="ab-snippet-vars">
							<span class="ab-tag-chip" data-tag="%%title%%">%%title%%</span>
							<span class="ab-tag-chip" data-tag="%%sep%%">%%sep%%</span>
							<span class="ab-tag-chip" data-tag="%%sitename%%">%%sitename%%</span>
							<span class="ab-tag-chip" data-tag="%%category%%">%%category%%</span>
							<span class="ab-tag-chip" data-tag="%%date%%">%%date%%</span>
						</div>
					</div>

					<!-- Meta Description Input -->
					<div class="ab-seo-field">
						<label for="ab_seo_meta_desc">
							<strong><?php esc_html_e( 'Meta Description:', 'ab-addon' ); ?></strong>
							<span class="ab-meter-label" id="ab-desc-meter-label">0 / 160</span>
						</label>
						<div class="ab-meter-bar-wrap"><div class="ab-meter-bar" id="ab-desc-meter-bar"></div></div>
						<textarea id="ab_seo_meta_desc" name="ab_seo[meta_description]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Enter a compelling summary of this post...', 'ab-addon' ); ?>"><?php echo esc_textarea( $meta_description ); ?></textarea>
					</div>
				</div>

				<!-- Live Real-Time SEO & Readability Analysis Results -->
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'SEO Content Analysis', 'ab-addon' ); ?></h3>
						<span class="description" id="ab-active-kw-display"><?php esc_html_e( 'Analyzing Primary Keyphrase', 'ab-addon' ); ?></span>
					</div>

					<div class="ab-seo-audit-list" id="ab-seo-audit-checklist">
						<!-- Dynamic Checklist items loaded by JS -->
						<div class="ab-seo-audit-item"><span class="ab-dot bad"></span> <span class="ab-text"><?php esc_html_e( 'Checking content analysis...', 'ab-addon' ); ?></span></div>
					</div>

					<h4 class="ab-section-subhead"><?php esc_html_e( 'Readability Analysis (Flesch Score & Flow)', 'ab-addon' ); ?></h4>
					<div class="ab-seo-audit-list" id="ab-readability-checklist">
						<div class="ab-seo-audit-item"><span class="ab-dot ok"></span> <span class="ab-text"><?php esc_html_e( 'Evaluating readability...', 'ab-addon' ); ?></span></div>
					</div>
				</div>

			</div><!-- /TAB 1 -->

			<!-- TAB 2: SOCIAL PREVIEWS (Facebook & Twitter) -->
			<div id="tab-social" class="ab-seo-tab-content">
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><span class="dashicons dashicons-facebook"></span> <?php esc_html_e( 'Facebook (OpenGraph) Preview & Customization', 'ab-addon' ); ?></h3>
					</div>

					<div class="ab-social-preview-card fb-card">
						<div class="ab-fb-img-box" id="ab-fb-img-preview" style="<?php echo ! empty( $og_image ) ? 'background-image:url(' . esc_url( $og_image ) . ');' : ''; ?>">
							<?php if ( empty( $og_image ) ) : ?>
								<span class="dashicons dashicons-format-image"></span>
							<?php endif; ?>
						</div>
						<div class="ab-fb-content">
							<div class="ab-fb-domain"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
							<div class="ab-fb-title" id="ab-fb-preview-title"><?php echo esc_html( ! empty( $og_title ) ? $og_title : get_the_title( $post ) ); ?></div>
							<div class="ab-fb-desc" id="ab-fb-preview-desc"><?php echo esc_html( ! empty( $og_description ) ? $og_description : wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 ) ); ?></div>
						</div>
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Facebook Title:', 'ab-addon' ); ?></strong></label>
						<input type="text" name="ab_seo[og_title]" id="ab_og_title" class="large-text" value="<?php echo esc_attr( $og_title ); ?>" placeholder="<?php esc_attr_e( 'Defaults to SEO Title', 'ab-addon' ); ?>" />
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Facebook Description:', 'ab-addon' ); ?></strong></label>
						<textarea name="ab_seo[og_description]" id="ab_og_desc" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Defaults to Meta Description', 'ab-addon' ); ?>"><?php echo esc_textarea( $og_description ); ?></textarea>
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Facebook Image:', 'ab-addon' ); ?></strong></label>
						<div class="ab-media-picker-row">
							<input type="text" name="ab_seo[og_image]" id="ab_og_image" class="regular-text" value="<?php echo esc_attr( $og_image ); ?>" />
							<button type="button" class="button ab-upload-media-btn" data-target="#ab_og_image" data-preview="#ab-fb-img-preview"><?php esc_html_e( 'Select Image', 'ab-addon' ); ?></button>
							<button type="button" class="button ab-remove-media-btn" data-target="#ab_og_image" data-preview="#ab-fb-img-preview"><?php esc_html_e( 'Remove', 'ab-addon' ); ?></button>
						</div>
					</div>
				</div>

				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><span class="dashicons dashicons-twitter"></span> <?php esc_html_e( 'Twitter / X Card Preview & Customization', 'ab-addon' ); ?></h3>
					</div>

					<div class="ab-social-preview-card twitter-card">
						<div class="ab-tw-img-box" id="ab-tw-img-preview" style="<?php echo ! empty( $twitter_image ) ? 'background-image:url(' . esc_url( $twitter_image ) . ');' : ( ! empty( $og_image ) ? 'background-image:url(' . esc_url( $og_image ) . ');' : '' ); ?>">
							<?php if ( empty( $twitter_image ) && empty( $og_image ) ) : ?>
								<span class="dashicons dashicons-twitter"></span>
							<?php endif; ?>
						</div>
						<div class="ab-tw-content">
							<div class="ab-tw-domain"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
							<div class="ab-tw-title" id="ab-tw-preview-title"><?php echo esc_html( ! empty( $twitter_title ) ? $twitter_title : get_the_title( $post ) ); ?></div>
							<div class="ab-tw-desc" id="ab-tw-preview-desc"><?php echo esc_html( ! empty( $twitter_desc ) ? $twitter_desc : wp_trim_words( wp_strip_all_tags( $post->post_content ), 18 ) ); ?></div>
						</div>
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Twitter Title:', 'ab-addon' ); ?></strong></label>
						<input type="text" name="ab_seo[twitter_title]" id="ab_tw_title" class="large-text" value="<?php echo esc_attr( $twitter_title ); ?>" placeholder="<?php esc_attr_e( 'Defaults to Facebook / SEO Title', 'ab-addon' ); ?>" />
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Twitter Description:', 'ab-addon' ); ?></strong></label>
						<textarea name="ab_seo[twitter_description]" id="ab_tw_desc" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Defaults to Facebook / Meta Description', 'ab-addon' ); ?>"><?php echo esc_textarea( $twitter_desc ); ?></textarea>
					</div>

					<div class="ab-seo-field">
						<label><strong><?php esc_html_e( 'Twitter Image:', 'ab-addon' ); ?></strong></label>
						<div class="ab-media-picker-row">
							<input type="text" name="ab_seo[twitter_image]" id="ab_tw_image" class="regular-text" value="<?php echo esc_attr( $twitter_image ); ?>" />
							<button type="button" class="button ab-upload-media-btn" data-target="#ab_tw_image" data-preview="#ab-tw-img-preview"><?php esc_html_e( 'Select Image', 'ab-addon' ); ?></button>
							<button type="button" class="button ab-remove-media-btn" data-target="#ab_tw_image" data-preview="#ab-tw-img-preview"><?php esc_html_e( 'Remove', 'ab-addon' ); ?></button>
						</div>
					</div>
				</div>
			</div><!-- /TAB 2 -->

			<!-- TAB 3: SCHEMA.ORG & FAQ BUILDER -->
			<div id="tab-schema" class="ab-seo-tab-content">
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Structured Data (Schema.org) Graph Settings', 'ab-addon' ); ?></h3>
					</div>

					<div class="ab-seo-field-row">
						<div class="ab-seo-field-col">
							<label><strong><?php esc_html_e( 'Page Type:', 'ab-addon' ); ?></strong></label>
							<select name="ab_seo[schema_page_type]" class="regular-text">
								<?php
								$page_types = array( 'WebPage', 'AboutPage', 'ContactPage', 'FAQPage', 'CheckoutPage', 'ProfilePage', 'CollectionPage' );
								foreach ( $page_types as $pt ) :
								?>
									<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $schema_page_type, $pt ); ?>><?php echo esc_html( $pt ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ab-seo-field-col">
							<label><strong><?php esc_html_e( 'Article Type:', 'ab-addon' ); ?></strong></label>
							<select name="ab_seo[schema_article_type]" class="regular-text">
								<?php
								$article_types = array( 'None' => __( 'None (Standard Page)', 'ab-addon' ), 'Article' => 'Article', 'BlogPosting' => 'BlogPosting', 'NewsArticle' => 'NewsArticle', 'TechArticle' => 'TechArticle' );
								foreach ( $article_types as $val => $lbl ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $schema_article_type, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>

				<!-- Dynamic FAQ Schema Builder (Google FAQ Rich Snippet) -->
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Dynamic FAQ Schema Builder', 'ab-addon' ); ?></h3>
						<span class="ab-badge-premium"><?php esc_html_e( 'Google Rich Snippet', 'ab-addon' ); ?></span>
					</div>
					<p class="ab-seo-desc"><?php esc_html_e( 'Add Frequently Asked Questions and answers to automatically generate Schema.org FAQPage structured data that appears directly in Google search results with expandable accordion rich snippets!', 'ab-addon' ); ?></p>

					<div id="ab-faq-items-wrap">
						<?php if ( ! empty( $faq_items ) ) : ?>
							<?php foreach ( $faq_items as $index => $item ) : ?>
								<div class="ab-faq-item-row">
									<div class="ab-faq-head">
										<strong><?php printf( esc_html__( 'FAQ Question #%d', 'ab-addon' ), $index + 1 ); ?></strong>
										<button type="button" class="button button-link-delete ab-remove-faq-btn"><?php esc_html_e( 'Remove', 'ab-addon' ); ?></button>
									</div>
									<input type="text" name="ab_seo[faq_items][<?php echo esc_attr( $index ); ?>][q]" class="large-text" value="<?php echo esc_attr( $item['q'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Question (e.g. What is Ramdevra Temple timing?)', 'ab-addon' ); ?>" />
									<textarea name="ab_seo[faq_items][<?php echo esc_attr( $index ); ?>][a]" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Answer...', 'ab-addon' ); ?>"><?php echo esc_textarea( $item['a'] ?? '' ); ?></textarea>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<button type="button" class="button button-primary" id="ab-add-faq-btn">
						<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( '+ Add FAQ Item', 'ab-addon' ); ?>
					</button>
				</div>
			</div><!-- /TAB 3 -->

			<!-- TAB 4: INTERNAL LINKING SUGGESTIONS -->
			<div id="tab-internal-links" class="ab-seo-tab-content">
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Live Internal Linking Suggestions', 'ab-addon' ); ?></h3>
						<span class="ab-badge-premium"><?php esc_html_e( 'Yoast Premium Exclusive', 'ab-addon' ); ?></span>
					</div>
					<p class="ab-seo-desc"><?php esc_html_e( 'Search engines love strong internal linking architecture. AB SEO scans your site and suggests relevant articles to link to right now with 1-click copy link.', 'ab-addon' ); ?></p>

					<div class="ab-seo-suggestions-filter">
						<input type="text" id="ab-linking-search-input" class="regular-text" placeholder="<?php esc_attr_e( 'Filter suggestions by keyword...', 'ab-addon' ); ?>" />
						<button type="button" class="button button-secondary" id="ab-refresh-suggestions-btn">
							<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh Suggestions', 'ab-addon' ); ?>
						</button>
					</div>

					<div id="ab-internal-linking-results" class="ab-linking-cards-grid">
						<p class="description"><?php esc_html_e( 'Loading internal link recommendations...', 'ab-addon' ); ?></p>
					</div>
				</div>
			</div><!-- /TAB 4 -->

			<!-- TAB 5: ADVANCED / ROBOTS & CORNERSTONE -->
			<div id="tab-advanced" class="ab-seo-tab-content">
				<div class="ab-seo-card">
					<div class="ab-seo-card-header">
						<h3><?php esc_html_e( 'Robots Meta & Indexing Controls', 'ab-addon' ); ?></h3>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[is_cornerstone]" value="1" <?php checked( $is_cornerstone ); ?> />
							<strong><?php esc_html_e( 'Mark as Cornerstone Content', 'ab-addon' ); ?></strong>
						</label>
						<p class="description"><?php esc_html_e( 'Cornerstone articles are the most authoritative, complete pieces on your site. They have stricter content evaluation and priority filtering in the admin post list.', 'ab-addon' ); ?></p>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[noindex]" value="1" <?php checked( $noindex ); ?> />
							<strong><?php esc_html_e( 'Noindex: Do not allow search engines to show this post in search results', 'ab-addon' ); ?></strong>
						</label>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[nofollow]" value="1" <?php checked( $nofollow ); ?> />
							<strong><?php esc_html_e( 'Nofollow: Do not follow links on this post', 'ab-addon' ); ?></strong>
						</label>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[noarchive]" value="1" <?php checked( $noarchive ); ?> />
							<strong><?php esc_html_e( 'Noarchive: Prevent search engines from showing a cached link for this page', 'ab-addon' ); ?></strong>
						</label>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[nosnippet]" value="1" <?php checked( $nosnippet ); ?> />
							<strong><?php esc_html_e( 'Nosnippet: Do not show a text snippet or video preview in the search results', 'ab-addon' ); ?></strong>
						</label>
					</div>

					<div class="ab-seo-field">
						<label class="ab-checkbox-label">
							<input type="checkbox" name="ab_seo[exclude_sitemap]" value="1" <?php checked( $exclude_sitemap ); ?> />
							<strong><?php esc_html_e( 'Exclude this post from XML Sitemap', 'ab-addon' ); ?></strong>
						</label>
					</div>

					<div class="ab-seo-field">
						<label for="ab_canonical_url"><strong><?php esc_html_e( 'Canonical URL Override:', 'ab-addon' ); ?></strong></label>
						<input type="url" name="ab_seo[canonical_url]" id="ab_canonical_url" class="large-text" value="<?php echo esc_url( $canonical_url ); ?>" placeholder="<?php echo esc_url( get_permalink( $post ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Leave empty to automatically use the standard permalink. Provide a custom URL if this post is syndicating or duplicating content from another source.', 'ab-addon' ); ?></p>
					</div>
				</div>
			</div><!-- /TAB 5 -->

		</div><!-- /ab-seo-wrap -->
		<?php
	}

	/**
	 * Save Meta Box data.
	 */
	public function save_meta_box_data( $post_id, $post ) {
		if ( ! isset( $_POST['ab_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_seo_meta_nonce'] ) ), 'ab_seo_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['ab_seo'] ) || ! is_array( $_POST['ab_seo'] ) ) {
			return;
		}

		$raw = (array) $_POST['ab_seo'];

		// Clean keywords
		$clean_keywords = array();
		if ( ! empty( $raw['focus_keywords'] ) && is_array( $raw['focus_keywords'] ) ) {
			foreach ( $raw['focus_keywords'] as $kw_row ) {
				$clean_keywords[] = array(
					'keyword'  => sanitize_text_field( $kw_row['keyword'] ?? '' ),
					'synonyms' => sanitize_text_field( $kw_row['synonyms'] ?? '' ),
				);
			}
		}

		// Clean FAQ items
		$clean_faq = array();
		if ( ! empty( $raw['faq_items'] ) && is_array( $raw['faq_items'] ) ) {
			foreach ( $raw['faq_items'] as $item ) {
				$q = sanitize_text_field( $item['q'] ?? '' );
				$a = wp_kses_post( $item['a'] ?? '' );
				if ( ! empty( $q ) && ! empty( $a ) ) {
					$clean_faq[] = array( 'q' => $q, 'a' => $a );
				}
			}
		}

		$clean = array(
			'focus_keywords'      => $clean_keywords,
			'seo_title'           => sanitize_text_field( $raw['seo_title'] ?? '' ),
			'meta_description'    => sanitize_textarea_field( $raw['meta_description'] ?? '' ),
			'canonical_url'       => esc_url_raw( $raw['canonical_url'] ?? '' ),
			'noindex'             => ! empty( $raw['noindex'] ) ? 1 : 0,
			'nofollow'            => ! empty( $raw['nofollow'] ) ? 1 : 0,
			'noarchive'           => ! empty( $raw['noarchive'] ) ? 1 : 0,
			'nosnippet'           => ! empty( $raw['nosnippet'] ) ? 1 : 0,
			'exclude_sitemap'     => ! empty( $raw['exclude_sitemap'] ) ? 1 : 0,
			'is_cornerstone'      => ! empty( $raw['is_cornerstone'] ) ? 1 : 0,

			'og_title'            => sanitize_text_field( $raw['og_title'] ?? '' ),
			'og_description'      => sanitize_textarea_field( $raw['og_description'] ?? '' ),
			'og_image'            => esc_url_raw( $raw['og_image'] ?? '' ),
			'twitter_title'       => sanitize_text_field( $raw['twitter_title'] ?? '' ),
			'twitter_description' => sanitize_textarea_field( $raw['twitter_description'] ?? '' ),
			'twitter_image'       => esc_url_raw( $raw['twitter_image'] ?? '' ),

			'schema_page_type'    => sanitize_text_field( $raw['schema_page_type'] ?? 'WebPage' ),
			'schema_article_type' => sanitize_text_field( $raw['schema_article_type'] ?? 'None' ),
			'faq_items'           => $clean_faq,
		);

		update_post_meta( $post_id, '_ab_seo_meta', $clean );
		update_post_meta( $post_id, '_ab_seo_is_cornerstone', $clean['is_cornerstone'] );

		// Calculate & save overall SEO score for post list columns
		$primary_kw = $clean_keywords[0]['keyword'] ?? '';
		$score = 'bad';
		$read  = 'ok';

		if ( ! empty( $primary_kw ) && ! empty( $clean['seo_title'] ) && ! empty( $clean['meta_description'] ) ) {
			$score = 'good';
		} elseif ( ! empty( $primary_kw ) ) {
			$score = 'ok';
		}

		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
		if ( $word_count > 300 ) {
			$read = 'good';
		}

		update_post_meta( $post_id, '_ab_seo_score', $score );
		update_post_meta( $post_id, '_ab_seo_readability_score', $read );
	}

	/**
	 * AJAX: Smart AI Generator for Meta Title & Description.
	 */
	public function ajax_generate_smart_meta() {
		check_ajax_referer( 'ab_seo_metabox_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ab-addon' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content = isset( $_POST['content'] ) ? wp_strip_all_tags( wp_unslash( $_POST['content'] ) ) : '';
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		$sitename = get_bloginfo( 'name' );

		// Generate optimal SEO Title (under 60 chars)
		if ( ! empty( $keyword ) ) {
			$clean_kw = ucwords( strtolower( $keyword ) );
			$gen_title = $clean_kw . ' — ' . $sitename;
			if ( strlen( $gen_title ) < 45 && ! empty( $title ) && strcasecmp( $title, $clean_kw ) !== 0 ) {
				$gen_title = $title . ' | ' . $clean_kw . ' — ' . $sitename;
			}
		} else {
			$gen_title = ( ! empty( $title ) ? $title : __( 'Welcome', 'ab-addon' ) ) . ' — ' . $sitename;
		}

		if ( mb_strlen( $gen_title ) > 60 ) {
			$gen_title = mb_substr( $gen_title, 0, 57 ) . '...';
		}

		// Generate optimal Meta Description (120-155 chars)
		$gen_desc = '';
		if ( ! empty( $content ) ) {
			$sentences = preg_split( '/(?<=[.?!])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( $sentences as $s ) {
				$s = trim( $s );
				if ( ! empty( $keyword ) && stripos( $s, $keyword ) !== false && strlen( $s ) >= 60 && strlen( $s ) <= 155 ) {
					$gen_desc = $s;
					break;
				}
			}

			if ( empty( $gen_desc ) && ! empty( $sentences[0] ) ) {
				$gen_desc = $sentences[0];
				if ( strlen( $gen_desc ) < 100 && isset( $sentences[1] ) ) {
					$gen_desc .= ' ' . $sentences[1];
				}
			}
		}

		if ( empty( $gen_desc ) ) {
			$kw_phrase = ! empty( $keyword ) ? $keyword : ( ! empty( $title ) ? $title : get_bloginfo( 'name' ) );
			$gen_desc = sprintf( __( 'Explore complete details, guides, and up-to-date information about %s on %s.', 'ab-addon' ), $kw_phrase, $sitename );
		}

		if ( mb_strlen( $gen_desc ) > 155 ) {
			$gen_desc = mb_substr( $gen_desc, 0, 152 ) . '...';
		}

		wp_send_json_success( array(
			'title'       => $gen_title,
			'description' => $gen_desc,
			'message'     => __( 'Generated high-impact SEO title and description!', 'ab-addon' ),
		) );
	}
}

AB_SEO_Meta_Box::instance();
