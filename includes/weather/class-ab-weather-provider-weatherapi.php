<?php
/**
 * WeatherAPI.com Weather Provider
 *
 * Provides weather forecasts using WeatherAPI.com.
 * WeatherAPI.com's Free plan explicitly permits commercial use up to 100,000 calls/month
 * and requires a free API key and attribution link.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-ab-weather-provider.php';

class AB_Weather_Provider_WeatherAPI extends AB_Weather_Provider_Base {

	public function get_id() {
		return 'weatherapi';
	}

	public function get_name() {
		return __( 'WeatherAPI.com (Free Tier Allows Commercial Use — Requires Free Key)', 'ab-addon' );
	}

	public function requires_api_key() {
		return true;
	}

	public function get_terms_notice() {
		return __( 'WeatherAPI.com explicitly allows commercial use on its free tier (up to 100,000 calls/month). Requires a free API key from weatherapi.com and attribution.', 'ab-addon' );
	}

	public function get_attribution() {
		return array(
			'text' => __( 'Powered by WeatherAPI.com', 'ab-addon' ),
			'url'  => 'https://www.weatherapi.com/',
		);
	}

	public function fetch_weather( $lat, $lon, $units = 'metric', $api_key = '' ) {
		if ( empty( $api_key ) ) {
			return new WP_Error(
				'ab_weather_missing_key',
				__( 'WeatherAPI.com requires a free API key. Please enter your API key in the AB Weather settings.', 'ab-addon' )
			);
		}

		$lat = round( floatval( $lat ), 4 );
		$lon = round( floatval( $lon ), 4 );

		$endpoint = sprintf(
			'https://api.weatherapi.com/v1/forecast.json?key=%s&q=%s,%s&days=6&aqi=no&alerts=no',
			rawurlencode( sanitize_text_field( $api_key ) ),
			rawurlencode( (string) $lat ),
			rawurlencode( (string) $lon )
		);

		$data = $this->safe_remote_get( $endpoint, array(), 10 );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( isset( $data['error'] ) ) {
			return new WP_Error(
				'ab_weather_api_error',
				$data['error']['message'] ?? __( 'WeatherAPI returned an error.', 'ab-addon' )
			);
		}

		$current     = $data['current'] ?? array();
		$forecastday = $data['forecast']['forecastday'] ?? array();

		if ( empty( $current ) ) {
			return new WP_Error( 'ab_weather_empty', __( 'Empty weather response from WeatherAPI.', 'ab-addon' ) );
		}

		$cur_temp   = floatval( $current['temp_c'] ?? 0 );
		$feels_like = floatval( $current['feelslike_c'] ?? $cur_temp );
		$humidity   = intval( $current['humidity'] ?? 0 );
		$wind_kmh   = floatval( $current['wind_kph'] ?? 0 );
		$cond_text  = $current['condition']['text'] ?? 'Clear';
		$cond_icon  = $this->map_condition_text( $cond_text );

		// Today's high and low from forecastday[0].
		$today_max = isset( $forecastday[0]['day']['maxtemp_c'] ) ? floatval( $forecastday[0]['day']['maxtemp_c'] ) : $cur_temp;
		$today_min = isset( $forecastday[0]['day']['mintemp_c'] ) ? floatval( $forecastday[0]['day']['mintemp_c'] ) : $cur_temp;
		$today_pop = isset( $forecastday[0]['day']['daily_chance_of_rain'] ) ? intval( $forecastday[0]['day']['daily_chance_of_rain'] ) : 0;
		$sunrise   = $forecastday[0]['astro']['sunrise'] ?? '';
		$sunset    = $forecastday[0]['astro']['sunset'] ?? '';

		if ( empty( $sunrise ) || empty( $sunset ) ) {
			$sun_calc = $this->calculate_sun_info( $lat, $lon, time() );
			$sunrise  = $sun_calc['sunrise'];
			$sunset   = $sun_calc['sunset'];
		}

		// 5-day forecast.
		$forecast = array();
		$tz       = new DateTimeZone( 'Asia/Kolkata' );

		for ( $i = 1; $i < count( $forecastday ); $i++ ) {
			if ( $i > 5 ) {
				break;
			}
			$day_data = $forecastday[ $i ];
			$date_str = $day_data['date'] ?? '';
			$day_dt   = new DateTime( $date_str, $tz );
			$f_text   = $day_data['day']['condition']['text'] ?? 'Clear';

			$forecast[] = array(
				'date'               => $date_str,
				'day_name'           => $day_dt->format( 'D' ),
				'day_date'           => $day_dt->format( 'M j' ),
				'temp_min'           => floatval( $day_data['day']['mintemp_c'] ?? 0 ),
				'temp_max'           => floatval( $day_data['day']['maxtemp_c'] ?? 0 ),
				'condition_icon'     => $this->map_condition_text( $f_text ),
				'condition_text'     => $f_text,
				'precipitation_prob' => intval( $day_data['day']['daily_chance_of_rain'] ?? 0 ),
			);
		}

		return array(
			'provider'      => 'weatherapi',
			'provider_name' => 'WeatherAPI.com',
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
				'condition_icon'     => $cond_icon,
				'condition_text'     => $cond_text,
				'sunrise'            => $sunrise,
				'sunset'             => $sunset,
			),
			'forecast'      => $forecast,
			'attribution'   => $this->get_attribution(),
		);
	}

	private function map_condition_text( $text ) {
		$text = strtolower( trim( $text ) );

		if ( strpos( $text, 'sun' ) !== false || strpos( $text, 'clear' ) !== false ) {
			return 'clear_day';
		}
		if ( strpos( $text, 'partly' ) !== false ) {
			return 'partly_cloudy_day';
		}
		if ( strpos( $text, 'overcast' ) !== false ) {
			return 'overcast';
		}
		if ( strpos( $text, 'cloud' ) !== false ) {
			return 'cloudy';
		}
		if ( strpos( $text, 'thunder' ) !== false ) {
			return 'thunderstorm';
		}
		if ( strpos( $text, 'heavy rain' ) !== false ) {
			return 'heavy_rain';
		}
		if ( strpos( $text, 'drizzle' ) !== false ) {
			return 'drizzle';
		}
		if ( strpos( $text, 'rain' ) !== false ) {
			return 'rain';
		}
		if ( strpos( $text, 'fog' ) !== false || strpos( $text, 'mist' ) !== false || strpos( $text, 'haze' ) !== false ) {
			return 'fog';
		}
		if ( strpos( $text, 'snow' ) !== false ) {
			return 'snow';
		}

		return 'clear_day';
	}
}
