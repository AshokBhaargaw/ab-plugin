<?php
/**
 * AB Weather Service
 *
 * Central weather manager, provider registry, transient caching layer,
 * and lock manager to prevent duplicate concurrent API requests.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-ab-weather-icons.php';
require_once dirname( __FILE__ ) . '/class-ab-weather-provider.php';
require_once dirname( __FILE__ ) . '/class-ab-weather-provider-met-norway.php';
require_once dirname( __FILE__ ) . '/class-ab-weather-provider-open-meteo.php';
require_once dirname( __FILE__ ) . '/class-ab-weather-provider-weatherapi.php';

class AB_Weather_Service {

	const SETTINGS_OPTION_KEY = 'ab_weather_settings';
	const CACHE_PREFIX        = 'ab_weather_';
	const LOCK_PREFIX         = 'ab_weather_lock_';

	private static $_instance = null;

	/**
	 * Registered provider instances.
	 *
	 * @var AB_Weather_Provider_Interface[]
	 */
	private $providers = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->register_providers();
	}

	/**
	 * Register all verified weather providers.
	 */
	private function register_providers() {
		$this->providers['met_norway'] = new AB_Weather_Provider_MET_Norway();
		$this->providers['open_meteo']  = new AB_Weather_Provider_Open_Meteo();
		$this->providers['weatherapi']  = new AB_Weather_Provider_WeatherAPI();
	}

	/**
	 * Get list of registered provider instances.
	 *
	 * @return AB_Weather_Provider_Interface[]
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Get a specific provider by ID.
	 *
	 * @param string $id Provider ID.
	 * @return AB_Weather_Provider_Interface|null
	 */
	public function get_provider( $id ) {
		return $this->providers[ $id ] ?? null;
	}

	/**
	 * Get saved plugin settings with robust defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = array(
			'enabled'          => 1,
			'provider'         => 'met_norway', // Default to genuinely free, commercial-friendly provider.
			'latitude'         => 27.0200,      // Verified Ramdevra coordinates.
			'longitude'        => 71.9200,
			'location_name'    => __( 'Ramdevra, Rajasthan, India', 'ab-addon' ),
			'temp_unit'        => 'c',
			'wind_unit'        => 'kmh',
			'forecast_days'    => 5,
			'cache_duration'   => 30, // in minutes.
			'api_key'          => '',
			'show_attribution' => 1,
			'cta_enabled'      => 1,
			'cta_text'         => __( 'Planning a trip to Ramdevra? Book your taxi.', 'ab-addon' ),
			'cta_url'          => 'https://ramdevraa.in/taxi-booking/',
		);

		$saved = get_option( self::SETTINGS_OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Save updated settings.
	 *
	 * @param array $settings New settings array.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$sanitized = array(
			'enabled'          => ! empty( $settings['enabled'] ) ? 1 : 0,
			'provider'         => sanitize_key( $settings['provider'] ?? 'met_norway' ),
			'latitude'         => round( floatval( $settings['latitude'] ?? 27.0200 ), 4 ),
			'longitude'        => round( floatval( $settings['longitude'] ?? 71.9200 ), 4 ),
			'location_name'    => sanitize_text_field( $settings['location_name'] ?? 'Ramdevra, Rajasthan, India' ),
			'temp_unit'        => ( isset( $settings['temp_unit'] ) && 'f' === $settings['temp_unit'] ) ? 'f' : 'c',
			'wind_unit'        => in_array( $settings['wind_unit'] ?? 'kmh', array( 'kmh', 'mph', 'ms' ), true ) ? $settings['wind_unit'] : 'kmh',
			'forecast_days'    => max( 1, min( 7, intval( $settings['forecast_days'] ?? 5 ) ) ),
			'cache_duration'   => max( 5, min( 1440, intval( $settings['cache_duration'] ?? 30 ) ) ),
			'api_key'          => sanitize_text_field( $settings['api_key'] ?? '' ),
			'show_attribution' => ! empty( $settings['show_attribution'] ) ? 1 : 0,
			'cta_enabled'      => ! empty( $settings['cta_enabled'] ) ? 1 : 0,
			'cta_text'         => sanitize_text_field( $settings['cta_text'] ?? '' ),
			'cta_url'          => esc_url_raw( $settings['cta_url'] ?? '' ),
		);

		return update_option( self::SETTINGS_OPTION_KEY, $sanitized );
	}

	/**
	 * Generate transient cache key.
	 *
	 * @param string $provider_id
	 * @param float  $lat
	 * @param float  $lon
	 * @return string
	 */
	private function get_cache_key( $provider_id, $lat, $lon ) {
		return substr( self::CACHE_PREFIX . md5( $provider_id . '_' . round( $lat, 3 ) . '_' . round( $lon, 3 ) ), 0, 45 );
	}

	/**
	 * Generate lock transient key.
	 *
	 * @param string $provider_id
	 * @return string
	 */
	private function get_lock_key( $provider_id ) {
		return self::LOCK_PREFIX . $provider_id;
	}

	/**
	 * Retrieve weather data, utilizing WordPress transients caching and anti-stampede lock.
	 *
	 * @param bool $force_refresh Force a fresh API call bypass.
	 * @return array|WP_Error
	 */
	public function get_weather( $force_refresh = false ) {
		$settings = $this->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return new WP_Error( 'ab_weather_disabled', __( 'Weather feature is currently disabled in AB Settings.', 'ab-addon' ) );
		}

		$provider_id = $settings['provider'];
		$provider    = $this->get_provider( $provider_id );

		if ( ! $provider ) {
			// Fallback to MET Norway if invalid provider selected.
			$provider_id = 'met_norway';
			$provider    = $this->get_provider( 'met_norway' );
		}

		$lat       = $settings['latitude'];
		$lon       = $settings['longitude'];
		$cache_key = $this->get_cache_key( $provider_id, $lat, $lon );
		$stale_key = $cache_key . '_stale';

		// Check transient cache first.
		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) ) {
				$cached['is_cached']  = true;
				$cached['cache_age']  = time() - ( $cached['fetched_at'] ?? time() );
				$cached['source_tag'] = __( 'Cached', 'ab-addon' );
				return $this->format_weather_display( $cached, $settings );
			}
		}

		// Prevent cache stampede / concurrent requests to provider.
		$lock_key = $this->get_lock_key( $provider_id );
		if ( ! $force_refresh && get_transient( $lock_key ) ) {
			// Another request is actively fetching. Return stale cache if available.
			$stale = get_option( $stale_key );
			if ( ! empty( $stale ) && is_array( $stale ) ) {
				$stale['is_cached']  = true;
				$stale['source_tag'] = __( 'Cached (refreshing...)', 'ab-addon' );
				return $this->format_weather_display( $stale, $settings );
			}
		}

		// Set 20-second mutex lock while request is in flight.
		set_transient( $lock_key, 1, 20 );

		// Fetch fresh data.
		$result = $provider->fetch_weather(
			$lat,
			$lon,
			$settings['temp_unit'] === 'f' ? 'imperial' : 'metric',
			$settings['api_key']
		);

		// Release mutex lock.
		delete_transient( $lock_key );

		if ( is_wp_error( $result ) ) {
			// If API call failed, try to serve stale backup from options.
			$stale = get_option( $stale_key );
			if ( ! empty( $stale ) && is_array( $stale ) ) {
				$stale['is_cached']  = true;
				$stale['source_tag'] = __( 'Cached (Provider Offline)', 'ab-addon' );
				$stale['notice']     = __( 'Live weather update temporarily unavailable. Showing cached report.', 'ab-addon' );
				return $this->format_weather_display( $stale, $settings );
			}
			return $result;
		}

		// Mark as fresh fetch.
		$result['is_cached']  = false;
		$result['source_tag'] = __( 'Live', 'ab-addon' );

		// Cache for configured duration (in seconds).
		$ttl_seconds = max( 300, intval( $settings['cache_duration'] ) * 60 );
		set_transient( $cache_key, $result, $ttl_seconds );

		// Store durable backup copy in case of provider downtime.
		update_option( $stale_key, $result, false );

		return $this->format_weather_display( $result, $settings );
	}

	/**
	 * Purge weather transients for current coordinates.
	 *
	 * @return bool
	 */
	public function purge_cache() {
		$settings = $this->get_settings();
		$lat      = $settings['latitude'];
		$lon      = $settings['longitude'];

		foreach ( array_keys( $this->providers ) as $p_id ) {
			$cache_key = $this->get_cache_key( $p_id, $lat, $lon );
			delete_transient( $cache_key );
			delete_transient( $this->get_lock_key( $p_id ) );
			delete_option( $cache_key . '_stale' );
		}

		return true;
	}

	/**
	 * Format temperatures and units according to site settings.
	 *
	 * @param array $data     Raw weather array.
	 * @param array $settings Plugin settings.
	 * @return array
	 */
	private function format_weather_display( $data, $settings ) {
		$unit_temp = $settings['temp_unit'] ?? 'c';
		$unit_wind = $settings['wind_unit'] ?? 'kmh';

		$tz = new DateTimeZone( 'Asia/Kolkata' );
		$dt = new DateTime( '@' . ( $data['fetched_at'] ?? time() ) );
		$dt->setTimezone( $tz );

		$data['location_display'] = ! empty( $settings['location_name'] ) ? $settings['location_name'] : $data['location'];
		$data['updated_ist_time'] = $dt->format( 'g:i A, d M' );
		$data['updated_time_full'] = $dt->format( 'g:i A' ) . ' IST';
		$data['temp_symbol']      = 'f' === $unit_temp ? '°F' : '°C';

		// Current temp conversion if Fahrenheit chosen.
		if ( 'f' === $unit_temp ) {
			$data['current']['temp_val']       = round( ( $data['current']['temp'] * 9 / 5 ) + 32, 1 );
			$data['current']['feels_like_val'] = round( ( $data['current']['feels_like'] * 9 / 5 ) + 32, 1 );
			$data['current']['temp_min_val']   = round( ( $data['current']['temp_min'] * 9 / 5 ) + 32, 1 );
			$data['current']['temp_max_val']   = round( ( $data['current']['temp_max'] * 9 / 5 ) + 32, 1 );
		} else {
			$data['current']['temp_val']       = round( $data['current']['temp'] );
			$data['current']['feels_like_val'] = round( $data['current']['feels_like'] );
			$data['current']['temp_min_val']   = round( $data['current']['temp_min'] );
			$data['current']['temp_max_val']   = round( $data['current']['temp_max'] );
		}

		// Wind speed formatting.
		if ( 'mph' === $unit_wind ) {
			$data['current']['wind_display'] = round( $data['current']['wind_speed'] * 0.621371, 1 ) . ' mph';
		} elseif ( 'ms' === $unit_wind ) {
			$data['current']['wind_display'] = round( $data['current']['wind_speed'] / 3.6, 1 ) . ' m/s';
		} else {
			$data['current']['wind_display'] = round( $data['current']['wind_speed'] ) . ' km/h';
		}

		// Format forecast days.
		if ( ! empty( $data['forecast'] ) && is_array( $data['forecast'] ) ) {
			foreach ( $data['forecast'] as &$day ) {
				if ( 'f' === $unit_temp ) {
					$day['temp_min_val'] = round( ( $day['temp_min'] * 9 / 5 ) + 32 );
					$day['temp_max_val'] = round( ( $day['temp_max'] * 9 / 5 ) + 32 );
				} else {
					$day['temp_min_val'] = round( $day['temp_min'] );
					$day['temp_max_val'] = round( $day['temp_max'] );
				}
			}
		}

		return $data;
	}

	/**
	 * Run a live connection test for a provider.
	 *
	 * @param string $provider_id Provider ID.
	 * @param string $api_key     Optional API key.
	 * @param float  $lat         Latitude.
	 * @param float  $lon         Longitude.
	 * @return array Result summary with status, message, and sample data.
	 */
	public function test_connection( $provider_id = '', $api_key = '', $lat = null, $lon = null ) {
		$settings = $this->get_settings();

		if ( empty( $provider_id ) ) {
			$provider_id = $settings['provider'];
		}
		if ( empty( $api_key ) ) {
			$api_key = $settings['api_key'];
		}
		if ( null === $lat ) {
			$lat = $settings['latitude'];
		}
		if ( null === $lon ) {
			$lon = $settings['longitude'];
		}

		$provider = $this->get_provider( $provider_id );
		if ( ! $provider ) {
			return array(
				'success' => false,
				'message' => __( 'Selected weather provider is not recognized.', 'ab-addon' ),
			);
		}

		$start_time = microtime( true );
		$result     = $provider->fetch_weather( $lat, $lon, 'metric', $api_key );
		$duration   = round( ( microtime( true ) - $start_time ) * 1000 );

		if ( is_wp_error( $result ) ) {
			return array(
				'success'  => false,
				'message'  => $result->get_error_message(),
				'duration' => $duration . 'ms',
			);
		}

		return array(
			'success'     => true,
			'message'     => sprintf(
				/* translators: %1$s: Provider name, %2$d: Latency in ms, %3$s: Temp in °C */
				__( 'Connected successfully to %1$s in %2$dms! Current Ramdevra weather: %3$s, %4$s°C.', 'ab-addon' ),
				$provider->get_name(),
				$duration,
				$result['current']['condition_text'],
				$result['current']['temp']
			),
			'duration'    => $duration . 'ms',
			'temperature' => $result['current']['temp'] . '°C',
			'condition'   => $result['current']['condition_text'],
			'attribution' => $result['attribution']['text'] ?? '',
			'fetched_at'  => ( new DateTime( 'now', new DateTimeZone( 'Asia/Kolkata' ) ) )->format( 'g:i:s A IST' ),
		);
	}
}
