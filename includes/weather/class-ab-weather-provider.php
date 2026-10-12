<?php
/**
 * AB Weather Provider Interface and Base Class
 *
 * Defines the contract for weather data providers in Ramdevra Weather widget.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Weather Provider Interface.
 */
interface AB_Weather_Provider_Interface {

	/**
	 * Unique identifier of the provider.
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Human-readable name of the provider.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Whether this provider requires an API key.
	 *
	 * @return bool
	 */
	public function requires_api_key();

	/**
	 * Human-readable licensing and commercial usage terms note.
	 *
	 * @return string
	 */
	public function get_terms_notice();

	/**
	 * Provider attribution details (text and URL).
	 *
	 * @return array Array with 'text' and 'url' keys.
	 */
	public function get_attribution();

	/**
	 * Fetch weather data for given coordinates.
	 *
	 * @param float  $lat     Latitude.
	 * @param float  $lon     Longitude.
	 * @param string $units   Unit system ('metric' for Celsius, 'imperial' for Fahrenheit).
	 * @param string $api_key Optional API key.
	 * @return array|WP_Error Normalized weather data or WP_Error on failure.
	 */
	public function fetch_weather( $lat, $lon, $units = 'metric', $api_key = '' );
}

/**
 * Base Abstract Weather Provider.
 */
abstract class AB_Weather_Provider_Base implements AB_Weather_Provider_Interface {

	/**
	 * List of allowed hostnames for SSRF protection.
	 *
	 * @var array
	 */
	protected $allowed_hosts = array(
		'api.met.no',
		'api.open-meteo.com',
		'customer-api.open-meteo.com',
		'api.weatherapi.com',
	);

	/**
	 * Perform a safe remote GET request with SSRF protection and error handling.
	 *
	 * @param string $url     Endpoint URL.
	 * @param array  $headers Additional headers.
	 * @param int    $timeout Request timeout in seconds.
	 * @return array|WP_Error Decoded JSON associative array or WP_Error.
	 */
	protected function safe_remote_get( $url, $headers = array(), $timeout = 9 ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! in_array( $host, $this->allowed_hosts, true ) ) {
			return new WP_Error(
				'ab_weather_ssrf_blocked',
				sprintf(
					/* translators: %s: Host name */
					__( 'Host %s is not in the approved weather provider whitelist.', 'ab-addon' ),
					esc_html( $host )
				)
			);
		}

		$default_headers = array(
			'Accept'     => 'application/json',
			'User-Agent' => 'RamdevraaWeatherWidget/1.0 (' . home_url() . '; ' . get_option( 'admin_email' ) . ')',
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 2,
				'sslverify'   => true,
				'headers'     => wp_parse_args( $headers, $default_headers ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ab_weather_http_error',
				__( 'Failed to connect to weather service: ', 'ab-addon' ) . $response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( 200 !== $status_code ) {
			return new WP_Error(
				'ab_weather_api_error',
				sprintf(
					/* translators: %1$d: HTTP status code, %2$s: Response message */
					__( 'Weather provider returned HTTP status %1$d: %2$s', 'ab-addon' ),
					$status_code,
					substr( wp_strip_all_tags( $body ), 0, 150 )
				)
			);
		}

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'ab_weather_json_error',
				__( 'Malformed response received from weather provider.', 'ab-addon' )
			);
		}

		return $data;
	}

	/**
	 * Calculate sunrise and sunset for coordinates using PHP date_sun_info.
	 * Ensures sunrise and sunset are always available in Asia/Kolkata timezone.
	 *
	 * @param float  $lat       Latitude.
	 * @param float  $lon       Longitude.
	 * @param int    $timestamp Unix timestamp for the date.
	 * @return array Array with formatted sunrise and sunset strings (e.g., '06:40 AM', '06:17 PM').
	 */
	protected function calculate_sun_info( $lat, $lon, $timestamp = 0 ) {
		if ( empty( $timestamp ) ) {
			$timestamp = time();
		}

		$sun_info = date_sun_info( $timestamp, $lat, $lon );

		$tz = new DateTimeZone( 'Asia/Kolkata' );

		$sunrise_dt = new DateTime( '@' . $sun_info['sunrise'] );
		$sunrise_dt->setTimezone( $tz );

		$sunset_dt = new DateTime( '@' . $sun_info['sunset'] );
		$sunset_dt->setTimezone( $tz );

		return array(
			'sunrise' => $sunrise_dt->format( 'g:i A' ),
			'sunset'  => $sunset_dt->format( 'g:i A' ),
		);
	}
}
