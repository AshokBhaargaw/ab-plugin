<?php
/**
 * MET Norway Weather Provider (Locationforecast 2.0)
 *
 * Provides completely free, open weather data funded by Norwegian taxpayers under
 * the Norwegian Licence for Open Government Data (NLOD 2.0) and CC BY 4.0 International.
 * Commercial use is permitted at no cost without an API key.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-ab-weather-provider.php';

class AB_Weather_Provider_MET_Norway extends AB_Weather_Provider_Base {

	public function get_id() {
		return 'met_norway';
	}

	public function get_name() {
		return __( 'MET Norway (Genuinely Free & Commercial Friendly — No Key Needed)', 'ab-addon' );
	}

	public function requires_api_key() {
		return false;
	}

	public function get_terms_notice() {
		return __( 'Official public data from The Norwegian Meteorological Institute under NLOD 2.0 & CC BY 4.0. Free for both commercial and non-commercial websites with no API key required.', 'ab-addon' );
	}

	public function get_attribution() {
		return array(
			'text' => __( 'Weather data from MET Norway', 'ab-addon' ),
			'url'  => 'https://www.met.no/en',
		);
	}

	public function fetch_weather( $lat, $lon, $units = 'metric', $api_key = '' ) {
		$lat = round( floatval( $lat ), 4 );
		$lon = round( floatval( $lon ), 4 );

		$endpoint = sprintf(
			'https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=%s&lon=%s',
			rawurlencode( (string) $lat ),
			rawurlencode( (string) $lon )
		);

		// MET Norway requires a unique User-Agent identifying the site.
		$site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $site_domain ) ) {
			$site_domain = 'ramdevraa.in';
		}
		$admin_email = get_option( 'admin_email', 'info@ramdevraa.in' );

		$headers = array(
			'User-Agent' => sprintf( 'RamdevraaWeatherPlugin/1.0 (%s; %s)', $site_domain, $admin_email ),
		);

		$data = $this->safe_remote_get( $endpoint, $headers, 10 );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$timeseries = $data['properties']['timeseries'] ?? array();
		if ( empty( $timeseries ) || ! is_array( $timeseries ) ) {
			return new WP_Error( 'ab_weather_empty', __( 'Empty timeseries from MET Norway API.', 'ab-addon' ) );
		}

		$tz = new DateTimeZone( 'Asia/Kolkata' );

		// Parse the current reading from the first entry.
		$current_point   = $timeseries[0];
		$current_details = $current_point['data']['instant']['details'] ?? array();
		$current_symbol  = $current_point['data']['next_1_hours']['summary']['symbol_code']
			?? $current_point['data']['next_6_hours']['summary']['symbol_code']
			?? 'clearsky_day';

		$cur_temp_c   = isset( $current_details['air_temperature'] ) ? floatval( $current_details['air_temperature'] ) : 0.0;
		$cur_humidity = isset( $current_details['relative_humidity'] ) ? round( floatval( $current_details['relative_humidity'] ) ) : 0;
		$wind_mps     = isset( $current_details['wind_speed'] ) ? floatval( $current_details['wind_speed'] ) : 0.0;
		$wind_kmh     = round( $wind_mps * 3.6, 1 );

		// Simple Australian Bureau of Meteorology Apparent Temperature calculation.
		$vapor_pressure = ( $cur_humidity / 100 ) * 6.105 * exp( ( 17.27 * $cur_temp_c ) / ( 237.7 + $cur_temp_c ) );
		$feels_like_c   = round( $cur_temp_c + ( 0.33 * $vapor_pressure ) - ( 0.70 * $wind_mps ) - 4.00, 1 );

		// Sun info for today.
		$sun_today = $this->calculate_sun_info( $lat, $lon, time() );

		// Group timeseries into days (IST) for 5-day forecast.
		$daily_buckets = array();
		$today_str     = ( new DateTime( 'now', $tz ) )->format( 'Y-m-d' );

		foreach ( $timeseries as $entry ) {
			$time_utc = $entry['time'] ?? '';
			if ( empty( $time_utc ) ) {
				continue;
			}
			$entry_dt = new DateTime( $time_utc );
			$entry_dt->setTimezone( $tz );
			$day_key = $entry_dt->format( 'Y-m-d' );

			if ( ! isset( $daily_buckets[ $day_key ] ) ) {
				$daily_buckets[ $day_key ] = array(
					'date'         => $day_key,
					'timestamp'    => $entry_dt->getTimestamp(),
					'temps'        => array(),
					'symbols'      => array(),
					'precip_probs' => array(),
				);
			}

			if ( isset( $entry['data']['instant']['details']['air_temperature'] ) ) {
				$daily_buckets[ $day_key ]['temps'][] = floatval( $entry['data']['instant']['details']['air_temperature'] );
			}

			$sym = $entry['data']['next_6_hours']['summary']['symbol_code']
				?? $entry['data']['next_1_hours']['summary']['symbol_code']
				?? $entry['data']['next_12_hours']['summary']['symbol_code']
				?? '';
			if ( ! empty( $sym ) ) {
				$daily_buckets[ $day_key ]['symbols'][] = $sym;
			}

			$prob = $entry['data']['next_6_hours']['details']['probability_of_precipitation']
				?? $entry['data']['next_1_hours']['details']['probability_of_precipitation']
				?? null;
			if ( null !== $prob ) {
				$daily_buckets[ $day_key ]['precip_probs'][] = floatval( $prob );
			}
		}

		// Calculate today's high and low.
		$today_temps = $daily_buckets[ $today_str ]['temps'] ?? array( $cur_temp_c );
		$today_high  = ! empty( $today_temps ) ? round( max( $today_temps ), 1 ) : $cur_temp_c;
		$today_low   = ! empty( $today_temps ) ? round( min( $today_temps ), 1 ) : $cur_temp_c;

		$today_precip_prob = ! empty( $daily_buckets[ $today_str ]['precip_probs'] )
			? round( max( $daily_buckets[ $today_str ]['precip_probs'] ) )
			: 0;

		// Build forecast days (skip today, take next 5 days).
		$forecast = array();
		$count    = 0;
		foreach ( $daily_buckets as $day_key => $bucket ) {
			if ( $day_key === $today_str ) {
				continue;
			}
			if ( empty( $bucket['temps'] ) ) {
				continue;
			}
			$count++;
			if ( $count > 6 ) {
				break;
			}

			$day_dt       = new DateTime( $day_key . ' 12:00:00', $tz );
			$rep_symbol   = ! empty( $bucket['symbols'] ) ? $bucket['symbols'][0] : 'clearsky_day';
			$precip_max   = ! empty( $bucket['precip_probs'] ) ? round( max( $bucket['precip_probs'] ) ) : 0;
			$cond_info    = $this->map_symbol( $rep_symbol );

			$forecast[] = array(
				'date'               => $day_key,
				'day_name'           => $day_dt->format( 'D' ),
				'day_date'           => $day_dt->format( 'M j' ),
				'temp_min'           => round( min( $bucket['temps'] ), 1 ),
				'temp_max'           => round( max( $bucket['temps'] ), 1 ),
				'condition_icon'     => $cond_info['icon'],
				'condition_text'     => $cond_info['text'],
				'precipitation_prob' => $precip_max,
			);
		}

		$current_cond = $this->map_symbol( $current_symbol );

		return array(
			'provider'     => 'met_norway',
			'provider_name'=> 'MET Norway',
			'location'     => __( 'Ramdevra, Rajasthan, India', 'ab-addon' ),
			'latitude'     => $lat,
			'longitude'    => $lon,
			'timezone'     => 'Asia/Kolkata',
			'fetched_at'   => time(),
			'current'      => array(
				'temp'               => round( $cur_temp_c, 1 ),
				'feels_like'         => $feels_like_c,
				'humidity'           => $cur_humidity,
				'wind_speed'         => $wind_kmh,
				'temp_min'           => $today_low,
				'temp_max'           => $today_high,
				'precipitation_prob' => $today_precip_prob,
				'condition_icon'     => $current_cond['icon'],
				'condition_text'     => $current_cond['text'],
				'sunrise'            => $sun_today['sunrise'],
				'sunset'             => $sun_today['sunset'],
			),
			'forecast'     => $forecast,
			'attribution'  => $this->get_attribution(),
		);
	}

	/**
	 * Map MET Norway symbol code to standard icon key and condition text.
	 *
	 * @param string $symbol MET Norway symbol code.
	 * @return array
	 */
	private function map_symbol( $symbol ) {
		$symbol = strtolower( trim( $symbol ) );

		if ( strpos( $symbol, 'clearsky' ) !== false || strpos( $symbol, 'fair' ) !== false ) {
			if ( strpos( $symbol, 'night' ) !== false ) {
				return array( 'icon' => 'clear_night', 'text' => __( 'Clear Night', 'ab-addon' ) );
			}
			return array( 'icon' => 'clear_day', 'text' => __( 'Clear & Sunny', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'partlycloudy' ) !== false ) {
			if ( strpos( $symbol, 'night' ) !== false ) {
				return array( 'icon' => 'partly_cloudy_night', 'text' => __( 'Partly Cloudy', 'ab-addon' ) );
			}
			return array( 'icon' => 'partly_cloudy_day', 'text' => __( 'Partly Cloudy', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'cloudy' ) !== false ) {
			return array( 'icon' => 'cloudy', 'text' => __( 'Cloudy', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'thunder' ) !== false ) {
			return array( 'icon' => 'thunderstorm', 'text' => __( 'Thunderstorm', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'heavyrain' ) !== false ) {
			return array( 'icon' => 'heavy_rain', 'text' => __( 'Heavy Rain', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'rain' ) !== false ) {
			return array( 'icon' => 'rain', 'text' => __( 'Rain Showers', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'fog' ) !== false ) {
			return array( 'icon' => 'fog', 'text' => __( 'Foggy / Haze', 'ab-addon' ) );
		}

		if ( strpos( $symbol, 'snow' ) !== false || strpos( $symbol, 'sleet' ) !== false ) {
			return array( 'icon' => 'snow', 'text' => __( 'Snow', 'ab-addon' ) );
		}

		return array( 'icon' => 'clear_day', 'text' => __( 'Clear Sky', 'ab-addon' ) );
	}
}
