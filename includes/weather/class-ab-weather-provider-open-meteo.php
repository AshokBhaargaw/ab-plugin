<?php
/**
 * Open-Meteo Weather Provider
 *
 * Provides weather forecasts using Open-Meteo API.
 * Note: Open-Meteo's free tier is strictly for non-commercial use. Commercial use
 * (including websites offering services or promotional activities like taxi bookings)
 * requires a paid commercial API key under Open-Meteo Terms of Use.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-ab-weather-provider.php';

class AB_Weather_Provider_Open_Meteo extends AB_Weather_Provider_Base {

	public function get_id() {
		return 'open_meteo';
	}

	public function get_name() {
		return __( 'Open-Meteo (Requires Commercial API Key for Taxi/Commercial Sites)', 'ab-addon' );
	}

	public function requires_api_key() {
		return false; // Can operate in non-commercial mode without key, but key required for commercial use.
	}

	public function get_terms_notice() {
		return __( 'Open-Meteo free tier is restricted to non-commercial projects. Because Ramdevraa.in promotes commercial taxi booking services, an Open-Meteo commercial subscription / API key is required for compliant production use. If you do not have an API key, use MET Norway instead.', 'ab-addon' );
	}

	public function get_attribution() {
		return array(
			'text' => __( 'Weather data by Open-Meteo.com', 'ab-addon' ),
			'url'  => 'https://open-meteo.com/',
		);
	}

	public function fetch_weather( $lat, $lon, $units = 'metric', $api_key = '' ) {
		$lat = round( floatval( $lat ), 4 );
		$lon = round( floatval( $lon ), 4 );

		$base_url = ! empty( $api_key )
			? 'https://customer-api.open-meteo.com/v1/forecast'
			: 'https://api.open-meteo.com/v1/forecast';

		$query_args = array(
			'latitude'         => $lat,
			'longitude'        => $lon,
			'current'          => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m',
			'daily'            => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,sunrise,sunset',
			'timezone'         => 'Asia/Kolkata',
			'forecast_days'    => 6,
		);

		if ( ! empty( $api_key ) ) {
			$query_args['apikey'] = sanitize_text_field( $api_key );
		}

		$endpoint = add_query_arg( $query_args, $base_url );

		$data = $this->safe_remote_get( $endpoint, array(), 10 );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$current = $data['current'] ?? array();
		$daily   = $data['daily'] ?? array();

		if ( empty( $current ) || ! isset( $current['temperature_2m'] ) ) {
			return new WP_Error( 'ab_weather_empty', __( 'Invalid or empty response from Open-Meteo.', 'ab-addon' ) );
		}

		$cur_temp       = floatval( $current['temperature_2m'] );
		$feels_like     = isset( $current['apparent_temperature'] ) ? floatval( $current['apparent_temperature'] ) : $cur_temp;
		$humidity       = isset( $current['relative_humidity_2m'] ) ? intval( $current['relative_humidity_2m'] ) : 0;
		$wind_kmh       = isset( $current['wind_speed_10m'] ) ? floatval( $current['wind_speed_10m'] ) : 0.0;
		$wmo_code       = isset( $current['weather_code'] ) ? intval( $current['weather_code'] ) : 0;
		$cond_info      = $this->map_wmo_code( $wmo_code );

		// Today's high and low from daily[0].
		$today_max = isset( $daily['temperature_2m_max'][0] ) ? floatval( $daily['temperature_2m_max'][0] ) : $cur_temp;
		$today_min = isset( $daily['temperature_2m_min'][0] ) ? floatval( $daily['temperature_2m_min'][0] ) : $cur_temp;
		$today_pop = isset( $daily['precipitation_probability_max'][0] ) ? intval( $daily['precipitation_probability_max'][0] ) : 0;

		// Format sunrise and sunset for today.
		$sunrise_raw = $daily['sunrise'][0] ?? '';
		$sunset_raw  = $daily['sunset'][0] ?? '';

		$sunrise_formatted = ! empty( $sunrise_raw ) ? ( new DateTime( $sunrise_raw ) )->format( 'g:i A' ) : '';
		$sunset_formatted  = ! empty( $sunset_raw ) ? ( new DateTime( $sunset_raw ) )->format( 'g:i A' ) : '';

		// If missing, calculate via date_sun_info.
		if ( empty( $sunrise_formatted ) || empty( $sunset_formatted ) ) {
			$sun_calc = $this->calculate_sun_info( $lat, $lon, time() );
			$sunrise_formatted = $sun_calc['sunrise'];
			$sunset_formatted  = $sun_calc['sunset'];
		}

		// Next 5 days forecast (index 1 to 5).
		$forecast = array();
		$times    = $daily['time'] ?? array();
		$tz       = new DateTimeZone( 'Asia/Kolkata' );

		for ( $i = 1; $i < count( $times ); $i++ ) {
			if ( $i > 5 ) {
				break;
			}
			$date_str = $times[ $i ];
			$day_dt   = new DateTime( $date_str, $tz );
			$day_wmo  = isset( $daily['weather_code'][ $i ] ) ? intval( $daily['weather_code'][ $i ] ) : 0;
			$day_cond = $this->map_wmo_code( $day_wmo );

			$forecast[] = array(
				'date'               => $date_str,
				'day_name'           => $day_dt->format( 'D' ),
				'day_date'           => $day_dt->format( 'M j' ),
				'temp_min'           => isset( $daily['temperature_2m_min'][ $i ] ) ? floatval( $daily['temperature_2m_min'][ $i ] ) : 0.0,
				'temp_max'           => isset( $daily['temperature_2m_max'][ $i ] ) ? floatval( $daily['temperature_2m_max'][ $i ] ) : 0.0,
				'condition_icon'     => $day_cond['icon'],
				'condition_text'     => $day_cond['text'],
				'precipitation_prob' => isset( $daily['precipitation_probability_max'][ $i ] ) ? intval( $daily['precipitation_probability_max'][ $i ] ) : 0,
			);
		}

		return array(
			'provider'      => 'open_meteo',
			'provider_name' => 'Open-Meteo',
			'location'      => __( 'Ramdevra, Rajasthan, India', 'ab-addon' ),
			'latitude'      => $lat,
			'longitude'     => $lon,
			'timezone'      => 'Asia/Kolkata',
			'fetched_at'    => time(),
			'current'       => array(
				'temp'               => $cur_temp,
				'feels_like'         => $feels_like,
				'humidity'           => $humidity,
				'wind_speed'         => $wind_kmh,
				'temp_min'           => $today_min,
				'temp_max'           => $today_max,
				'precipitation_prob' => $today_pop,
				'condition_icon'     => $cond_info['icon'],
				'condition_text'     => $cond_info['text'],
				'sunrise'            => $sunrise_formatted,
				'sunset'             => $sunset_formatted,
			),
			'forecast'      => $forecast,
			'attribution'   => $this->get_attribution(),
		);
	}

	/**
	 * Map WMO weather codes to standard icon and label.
	 *
	 * @param int $code WMO code.
	 * @return array
	 */
	private function map_wmo_code( $code ) {
		switch ( $code ) {
			case 0:
				return array( 'icon' => 'clear_day', 'text' => __( 'Clear Sky', 'ab-addon' ) );
			case 1:
				return array( 'icon' => 'clear_day', 'text' => __( 'Mainly Clear', 'ab-addon' ) );
			case 2:
				return array( 'icon' => 'partly_cloudy_day', 'text' => __( 'Partly Cloudy', 'ab-addon' ) );
			case 3:
				return array( 'icon' => 'overcast', 'text' => __( 'Overcast', 'ab-addon' ) );
			case 45:
			case 48:
				return array( 'icon' => 'fog', 'text' => __( 'Fog / Mist', 'ab-addon' ) );
			case 51:
			case 53:
			case 55:
				return array( 'icon' => 'drizzle', 'text' => __( 'Drizzle', 'ab-addon' ) );
			case 61:
			case 63:
				return array( 'icon' => 'rain', 'text' => __( 'Rain Showers', 'ab-addon' ) );
			case 65:
				return array( 'icon' => 'heavy_rain', 'text' => __( 'Heavy Rain', 'ab-addon' ) );
			case 71:
			case 73:
			case 75:
			case 77:
				return array( 'icon' => 'snow', 'text' => __( 'Snow', 'ab-addon' ) );
			case 80:
			case 81:
			case 82:
				return array( 'icon' => 'rain', 'text' => __( 'Rain Showers', 'ab-addon' ) );
			case 95:
			case 96:
			case 99:
				return array( 'icon' => 'thunderstorm', 'text' => __( 'Thunderstorm', 'ab-addon' ) );
			default:
				return array( 'icon' => 'clear_day', 'text' => __( 'Clear', 'ab-addon' ) );
		}
	}
}
