<?php
/**
 * AB Weather Icons Helper
 *
 * Provides lightweight, crisp, inline vector SVG icons for all weather conditions,
 * metrics, and call-to-actions without loading external icon fonts or image files.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AB_Weather_Icons {

	/**
	 * Get an inline SVG icon by key.
	 *
	 * @param string $key   Icon identifier.
	 * @param int    $size  Width/height in pixels (default 24).
	 * @param string $class Extra CSS class.
	 * @return string Inline SVG markup.
	 */
	public static function get( $key, $size = 24, $class = '' ) {
		$size  = intval( $size );
		$class = esc_attr( trim( 'ab-weather-icon ' . $class ) );

		$icons = self::all_icons();

		if ( isset( $icons[ $key ] ) ) {
			return sprintf(
				'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
				$class,
				$size,
				$size,
				$icons[ $key ]
			);
		}

		// Fallback to sun/cloud icon.
		return sprintf(
			'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
			$class,
			$size,
			$size,
			$icons['clear_day']
		);
	}

	/**
	 * Dictionary of vector paths for all icons.
	 *
	 * @return array
	 */
	private static function all_icons() {
		return array(
			// Sun / Clear Day
			'clear_day'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',

			// Moon / Clear Night
			'clear_night'         => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',

			// Partly Cloudy Day
			'partly_cloudy_day'   => '<path d="M12 2v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="M20 12h2"/><path d="m19.07 4.93-1.41 1.41"/><path d="M15.947 12.65a4 4 0 0 0-5.925-4.13"/><path d="M17.5 21H9a7 7 0 1 1 6.71-9h.79a4.5 4.5 0 1 1 1 8.89Z"/>',

			// Partly Cloudy Night
			'partly_cloudy_night' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/><path d="M17.5 21H9a7 7 0 1 1 6.71-9h.79a4.5 4.5 0 1 1 1 8.89Z"/>',

			// Cloudy / Overcast
			'cloudy'              => '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>',
			'overcast'            => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 20H7a5 5 0 0 1-1.3-9.8"/>',

			// Rain / Showers
			'rain'                => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/>',
			'heavy_rain'          => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m16 14-2 6"/><path d="m8 14-2 6"/><path d="m12 14-2 6"/>',
			'drizzle'             => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M8 19v1"/><path d="M8 14v1"/><path d="M12 19v1"/><path d="M12 14v1"/><path d="M16 19v1"/><path d="M16 14v1"/>',

			// Thunderstorm
			'thunderstorm'        => '<path d="M17.5 17H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/><path d="m13 15-3 5h4l-2 5"/>',

			// Fog / Mist
			'fog'                 => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M4 17h16"/><path d="M7 20h10"/>',

			// Snow
			'snow'                => '<path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M8 15h.01"/><path d="M8 19h.01"/><path d="M12 17h.01"/><path d="M12 21h.01"/><path d="M16 15h.01"/><path d="M16 19h.01"/>',

			// Wind / Breeze
			'wind'                => '<path d="M17.7 7.7a2.5 2.5 0 1 1 1.8 4.3H2"/><path d="M9.6 4.6A2 2 0 1 1 11 8H2"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2"/>',

			// Humidity / Droplet
			'humidity'            => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',

			// Thermometer / Feels Like
			'thermometer'         => '<path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"/>',

			// Rain Probability / Umbrella
			'rain_prob'           => '<path d="M22 12a10.06 10.06 0 0 0-20 0Z"/><path d="M12 12v8a2 2 0 0 0 4 0"/><path d="M12 2v1"/>',

			// Sunrise
			'sunrise'             => '<path d="M12 2v6"/><path d="m4.93 10.93 1.41 1.41"/><path d="M2 18h2"/><path d="M20 18h2"/><path d="m19.07 10.93-1.41 1.41"/><path d="M22 22H2"/><path d="m8 6 4-4 4 4"/><path d="M16 18a4 4 0 0 0-8 0"/>',

			// Sunset
			'sunset'              => '<path d="M12 10V4"/><path d="m4.93 10.93 1.41 1.41"/><path d="M2 18h2"/><path d="M20 18h2"/><path d="m19.07 10.93-1.41 1.41"/><path d="M22 22H2"/><path d="m16 8-4 4-4-4"/><path d="M16 18a4 4 0 0 0-8 0"/>',

			// Location Pin
			'location'            => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',

			// Clock / Updated Time
			'clock'               => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',

			// High / Low arrows
			'arrow_up'            => '<path d="m18 15-6-6-6 6"/>',
			'arrow_down'          => '<path d="m6 9 6 6 6-6"/>',

			// Taxi / Cab Car (for Call-to-Action)
			'taxi'                => '<path d="M14 2H10a2 2 0 0 0-2 2v1H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h1v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-4h2v4a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-4h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3V4a2 2 0 0 0-2-2z"/><circle cx="7.5" cy="10.5" r="1.5"/><circle cx="16.5" cy="10.5" r="1.5"/>',

			// Checkmark
			'check'               => '<polyline points="20 6 9 17 4 12"/>',

			// Alert Triangle
			'alert'               => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
		);
	}
}
