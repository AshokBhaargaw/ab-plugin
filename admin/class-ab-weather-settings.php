<?php
/**
 * AB Weather Admin Settings Page
 *
 * Provides a dedicated settings interface under AB Settings > Ramdevra Weather
 * where administrators can configure the weather provider, Ramdevra coordinates,
 * units, cache duration, licensing attribution, and run live connection tests.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AB_ADDON_PATH . 'includes/weather/class-ab-weather-service.php';

class AB_Weather_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ), 30 );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX Handlers.
		add_action( 'wp_ajax_ab_weather_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_ab_weather_purge_cache', array( $this, 'ajax_purge_cache' ) );
	}

	/**
	 * Register submenu under AB Settings.
	 */
	public function register_submenu() {
		add_submenu_page(
			'ab-settings',
			__( 'Ramdevra Weather', 'ab-addon' ),
			__( 'Ramdevra Weather', 'ab-addon' ),
			'manage_options',
			'ab-weather-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS for the settings page.
	 *
	 * @param string $hook Page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ab-weather-settings' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'ab-admin-settings',
			AB_ADDON_URL . 'assets/css/admin-settings.css',
			array(),
			AB_ADDON_VERSION
		);

		wp_enqueue_script(
			'ab-admin-weather-js',
			AB_ADDON_URL . 'assets/js/admin-weather.js',
			array( 'jquery' ),
			AB_ADDON_VERSION,
			true
		);

		wp_localize_script(
			'ab-admin-weather-js',
			'abWeatherAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ab_weather_admin_nonce' ),
				'testing' => __( 'Testing connection to provider...', 'ab-addon' ),
				'clearing'=> __( 'Purging weather cache...', 'ab-addon' ),
			)
		);
	}

	/**
	 * Handle settings form submission.
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['ab_weather_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'ab-addon' ) );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ab_weather_nonce'] ) ), 'ab_weather_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ab-addon' ) );
		}

		$service  = AB_Weather_Service::instance();
		$settings = array(
			'enabled'          => isset( $_POST['ab_weather_enabled'] ) ? 1 : 0,
			'provider'         => sanitize_key( wp_unslash( $_POST['ab_weather_provider'] ?? 'met_norway' ) ),
			'latitude'         => floatval( wp_unslash( $_POST['ab_weather_latitude'] ?? 27.0200 ) ),
			'longitude'        => floatval( wp_unslash( $_POST['ab_weather_longitude'] ?? 71.9200 ) ),
			'location_name'    => sanitize_text_field( wp_unslash( $_POST['ab_weather_location_name'] ?? 'Ramdevra, Rajasthan, India' ) ),
			'temp_unit'        => sanitize_key( wp_unslash( $_POST['ab_weather_temp_unit'] ?? 'c' ) ),
			'wind_unit'        => sanitize_key( wp_unslash( $_POST['ab_weather_wind_unit'] ?? 'kmh' ) ),
			'forecast_days'    => intval( wp_unslash( $_POST['ab_weather_forecast_days'] ?? 5 ) ),
			'cache_duration'   => intval( wp_unslash( $_POST['ab_weather_cache_duration'] ?? 30 ) ),
			'api_key'          => sanitize_text_field( wp_unslash( $_POST['ab_weather_api_key'] ?? '' ) ),
			'show_attribution' => isset( $_POST['ab_weather_show_attribution'] ) ? 1 : 0,
			'cta_enabled'      => isset( $_POST['ab_weather_cta_enabled'] ) ? 1 : 0,
			'cta_text'         => sanitize_text_field( wp_unslash( $_POST['ab_weather_cta_text'] ?? '' ) ),
			'cta_url'          => esc_url_raw( wp_unslash( $_POST['ab_weather_cta_url'] ?? '' ) ),
		);

		$service->save_settings( $settings );

		// Clear cache if provider or coordinates changed.
		$service->purge_cache();

		wp_safe_redirect( add_query_arg( array( 'page' => 'ab-weather-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * AJAX Handler for Test Connection button.
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'ab_weather_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ab-addon' ) ) );
		}

		$provider_id = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		$api_key     = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		$lat         = isset( $_POST['latitude'] ) ? floatval( wp_unslash( $_POST['latitude'] ) ) : null;
		$lon         = isset( $_POST['longitude'] ) ? floatval( wp_unslash( $_POST['longitude'] ) ) : null;

		$service = AB_Weather_Service::instance();
		$result  = $service->test_connection( $provider_id, $api_key, $lat, $lon );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX Handler for Purge Cache button.
	 */
	public function ajax_purge_cache() {
		check_ajax_referer( 'ab_weather_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ab-addon' ) ) );
		}

		$service = AB_Weather_Service::instance();
		$service->purge_cache();

		wp_send_json_success( array( 'message' => __( 'Weather cache cleared successfully!', 'ab-addon' ) ) );
	}

	/**
	 * Render Settings Page HTML.
	 */
	public function render_page() {
		$service   = AB_Weather_Service::instance();
		$settings  = $service->get_settings();
		$providers = $service->get_providers();

		$current_provider = $service->get_provider( $settings['provider'] ) ?? $providers['met_norway'];
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-slider-settings' ) ); ?>" class="nav-tab">
					<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'Sliders', 'ab-addon' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ab-weather-settings' ) ); ?>" class="nav-tab nav-tab-active">
					<span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Ramdevra Weather', 'ab-addon' ); ?>
				</a>
			</nav>

			<div class="ab-settings-header">
				<div class="ab-settings-title-area">
					<h1><?php esc_html_e( 'AB Weather — Ramdevra Weather', 'ab-addon' ); ?></h1>
					<p class="ab-settings-subtitle">
						<?php esc_html_e( 'Free, lightweight weather feature displaying live conditions and forecasts for Ramdevra, Rajasthan, India.', 'ab-addon' ); ?>
					</p>
				</div>
				<div class="ab-settings-header-actions">
					<button type="button" class="button button-secondary" id="ab-weather-purge-cache-btn">
						<span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
						<?php esc_html_e( 'Clear Weather Cache', 'ab-addon' ); ?>
					</button>
				</div>
			</div>

			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Weather settings saved successfully and cache refreshed.', 'ab-addon' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="ab-settings-body">
				<form method="post" action="" id="ab-weather-settings-form">
					<?php wp_nonce_field( 'ab_weather_save_settings', 'ab_weather_nonce' ); ?>

					<!-- GENERAL CONFIG -->
					<div class="ab-card">
						<h2><?php esc_html_e( 'General Configuration', 'ab-addon' ); ?></h2>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_enabled">
								<input type="checkbox" name="ab_weather_enabled" id="ab_weather_enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?>>
								<strong><?php esc_html_e( 'Enable Weather Feature', 'ab-addon' ); ?></strong>
							</label>
							<p class="description"><?php esc_html_e( 'Toggle all frontend weather cards, Gutenberg blocks, and [ab_weather] shortcodes.', 'ab-addon' ); ?></p>
						</div>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_provider"><?php esc_html_e( 'Weather Provider', 'ab-addon' ); ?></label>
							<select name="ab_weather_provider" id="ab_weather_provider" class="regular-text">
								<?php foreach ( $providers as $p_id => $p_obj ) : ?>
									<option value="<?php echo esc_attr( $p_id ); ?>" <?php selected( $settings['provider'], $p_id ); ?>>
										<?php echo esc_html( $p_obj->get_name() ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="ab-weather-terms-box" style="margin-top: 8px; padding: 10px; background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 4px;">
								<strong id="ab-provider-terms-title"><?php esc_html_e( 'Licensing & Terms Note:', 'ab-addon' ); ?></strong>
								<p id="ab-provider-terms-desc" style="margin: 4px 0 0 0; font-size: 13px;">
									<?php echo esc_html( $current_provider->get_terms_notice() ); ?>
								</p>
							</div>
						</div>

						<div class="ab-field-group" id="ab-weather-api-key-group" style="<?php echo ( 'open_meteo' === $settings['provider'] || 'weatherapi' === $settings['provider'] ) ? '' : 'display:none;'; ?>">
							<label class="ab-label" for="ab_weather_api_key"><?php esc_html_e( 'Provider API Key', 'ab-addon' ); ?></label>
							<input type="password" name="ab_weather_api_key" id="ab_weather_api_key" value="<?php echo esc_attr( $settings['api_key'] ); ?>" class="regular-text" autocomplete="new-password">
							<p class="description">
								<?php esc_html_e( 'Required for WeatherAPI.com (free key available) or Open-Meteo commercial plans. Never exposed to frontend visitors.', 'ab-addon' ); ?>
							</p>
						</div>

						<div class="ab-field-group">
							<button type="button" class="button button-secondary" id="ab-weather-test-btn">
								<span class="dashicons dashicons-admin-plugins" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Test Live Provider Connection', 'ab-addon' ); ?>
							</button>
							<div id="ab-weather-test-result" style="margin-top: 10px; display: none;"></div>
						</div>
					</div>

					<!-- LOCATION & COORDINATES -->
					<div class="ab-card">
						<h2><?php esc_html_e( 'Location Coordinates (Ramdevra)', 'ab-addon' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Fixed coordinates for Ramdevra village (Jaisalmer district, Rajasthan, India). Weather is specifically calculated for Ramdevra, never visitor geolocation.', 'ab-addon' ); ?>
						</p>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_location_name"><?php esc_html_e( 'Display Location Label', 'ab-addon' ); ?></label>
							<input type="text" name="ab_weather_location_name" id="ab_weather_location_name" value="<?php echo esc_attr( $settings['location_name'] ); ?>" class="regular-text">
						</div>

						<div style="display: flex; gap: 20px; flex-wrap: wrap;">
							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_latitude"><?php esc_html_e( 'Latitude', 'ab-addon' ); ?></label>
								<input type="number" step="0.0001" name="ab_weather_latitude" id="ab_weather_latitude" value="<?php echo esc_attr( $settings['latitude'] ); ?>" class="regular-text">
								<small><?php esc_html_e( 'Verified Ramdevra: 27.0200', 'ab-addon' ); ?></small>
							</div>

							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_longitude"><?php esc_html_e( 'Longitude', 'ab-addon' ); ?></label>
								<input type="number" step="0.0001" name="ab_weather_longitude" id="ab_weather_longitude" value="<?php echo esc_attr( $settings['longitude'] ); ?>" class="regular-text">
								<small><?php esc_html_e( 'Verified Ramdevra: 71.9200', 'ab-addon' ); ?></small>
							</div>
						</div>
					</div>

					<!-- DISPLAY UNITS & CACHING -->
					<div class="ab-card">
						<h2><?php esc_html_e( 'Units & Caching', 'ab-addon' ); ?></h2>

						<div style="display: flex; gap: 20px; flex-wrap: wrap;">
							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_temp_unit"><?php esc_html_e( 'Temperature Unit', 'ab-addon' ); ?></label>
								<select name="ab_weather_temp_unit" id="ab_weather_temp_unit" class="regular-text">
									<option value="c" <?php selected( $settings['temp_unit'], 'c' ); ?>><?php esc_html_e( 'Celsius (°C) — Default', 'ab-addon' ); ?></option>
									<option value="f" <?php selected( $settings['temp_unit'], 'f' ); ?>><?php esc_html_e( 'Fahrenheit (°F)', 'ab-addon' ); ?></option>
								</select>
							</div>

							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_wind_unit"><?php esc_html_e( 'Wind Speed Unit', 'ab-addon' ); ?></label>
								<select name="ab_weather_wind_unit" id="ab_weather_wind_unit" class="regular-text">
									<option value="kmh" <?php selected( $settings['wind_unit'], 'kmh' ); ?>><?php esc_html_e( 'km/h — Default', 'ab-addon' ); ?></option>
									<option value="mph" <?php selected( $settings['wind_unit'], 'mph' ); ?>><?php esc_html_e( 'mph', 'ab-addon' ); ?></option>
									<option value="ms" <?php selected( $settings['wind_unit'], 'ms' ); ?>><?php esc_html_e( 'm/s', 'ab-addon' ); ?></option>
								</select>
							</div>
						</div>

						<div style="display: flex; gap: 20px; flex-wrap: wrap;">
							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_forecast_days"><?php esc_html_e( 'Default Forecast Days', 'ab-addon' ); ?></label>
								<input type="number" min="1" max="7" name="ab_weather_forecast_days" id="ab_weather_forecast_days" value="<?php echo esc_attr( $settings['forecast_days'] ); ?>" class="small-text">
								<small><?php esc_html_e( 'Days to show in full card (default 5).', 'ab-addon' ); ?></small>
							</div>

							<div class="ab-field-group" style="flex: 1; min-width: 200px;">
								<label class="ab-label" for="ab_weather_cache_duration"><?php esc_html_e( 'Cache Duration (Minutes)', 'ab-addon' ); ?></label>
								<input type="number" min="5" max="1440" name="ab_weather_cache_duration" id="ab_weather_cache_duration" value="<?php echo esc_attr( $settings['cache_duration'] ); ?>" class="small-text">
								<small><?php esc_html_e( 'Responses cached via WordPress Transients (default 30 min).', 'ab-addon' ); ?></small>
							</div>
						</div>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_show_attribution">
								<input type="checkbox" name="ab_weather_show_attribution" id="ab_weather_show_attribution" value="1" <?php checked( $settings['show_attribution'], 1 ); ?>>
								<?php esc_html_e( 'Display Data Provider Attribution in Footer (Required by MET Norway & WeatherAPI.com licenses)', 'ab-addon' ); ?>
							</label>
						</div>
					</div>

					<!-- TRAVEL TAXI CALLOUT -->
					<div class="ab-card">
						<h2><?php esc_html_e( 'Travel Taxi Callout (CTA)', 'ab-addon' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Promote taxi bookings for pilgrims and travelers visiting Ramdevra directly below the weather widget.', 'ab-addon' ); ?>
						</p>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_cta_enabled">
								<input type="checkbox" name="ab_weather_cta_enabled" id="ab_weather_cta_enabled" value="1" <?php checked( $settings['cta_enabled'], 1 ); ?>>
								<?php esc_html_e( 'Enable Taxi Booking CTA by Default', 'ab-addon' ); ?>
							</label>
						</div>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_cta_text"><?php esc_html_e( 'Callout Message', 'ab-addon' ); ?></label>
							<input type="text" name="ab_weather_cta_text" id="ab_weather_cta_text" value="<?php echo esc_attr( $settings['cta_text'] ); ?>" class="large-text">
						</div>

						<div class="ab-field-group">
							<label class="ab-label" for="ab_weather_cta_url"><?php esc_html_e( 'Booking Page URL', 'ab-addon' ); ?></label>
							<input type="url" name="ab_weather_cta_url" id="ab_weather_cta_url" value="<?php echo esc_attr( $settings['cta_url'] ); ?>" class="large-text">
							<small><?php esc_html_e( 'Default: https://ramdevraa.in/taxi-booking/', 'ab-addon' ); ?></small>
						</div>
					</div>

					<!-- HOW TO USE SHORTCODES -->
					<div class="ab-card">
						<h2><?php esc_html_e( 'Usage & Integration Shortcuts', 'ab-addon' ); ?></h2>
						<p><?php esc_html_e( 'You can display Ramdevra weather anywhere using shortcodes, Gutenberg block, or Elementor:', 'ab-addon' ); ?></p>
						<table class="widefat fixed striped">
							<tbody>
								<tr>
									<td style="width: 250px;"><code>[ab_weather]</code></td>
									<td><?php esc_html_e( 'Displays the full Ramdevra weather station with 5-day forecast.', 'ab-addon' ); ?></td>
								</tr>
								<tr>
									<td><code>[ab_weather mode="compact"]</code></td>
									<td><?php esc_html_e( 'Displays a compact current temperature card for sidebars or headers.', 'ab-addon' ); ?></td>
								</tr>
								<tr>
									<td><code>[ab_weather mode="forecast" days="5"]</code></td>
									<td><?php esc_html_e( 'Displays the 5-day weather outlook card.', 'ab-addon' ); ?></td>
								</tr>
								<tr>
									<td><code>[ab_weather show_cta="no"]</code></td>
									<td><?php esc_html_e( 'Hides the taxi booking banner for this specific placement.', 'ab-addon' ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>

					<p class="submit">
						<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Weather Settings', 'ab-addon' ); ?></button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
}
