<?php
/**
 * AB Hotel Helper
 *
 * Provides clean, reusable helper functions for retrieving Hotel CPT data.
 * Designed to be future-proof so that custom fields (e.g., location, distance to Mandir/Station,
 * price range, amenities, phone, booking URL) can be added without modifying widget code.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class AB_Hotel_Helper {

	/**
	 * Retrieve hotel location.
	 *
	 * Checks for post meta / custom field first. Falls back to default 'Ramdevra, Rajasthan'.
	 *
	 * @param int $post_id Post ID.
	 * @return string Location string.
	 */
	public static function get_hotel_location( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$location = get_post_meta( $post_id, 'hotel_location', true );
		if ( empty( $location ) ) {
			$location = get_post_meta( $post_id, 'location', true );
		}

		if ( empty( $location ) ) {
			$location = 'Ramdevra, Rajasthan';
		}

		/**
		 * Filter the hotel location.
		 *
		 * @param string $location Formatted location.
		 * @param int    $post_id  Hotel post ID.
		 */
		return apply_filters( 'ab_hotel_location', $location, $post_id );
	}

	/**
	 * Retrieve clean hotel excerpt with fallback to content.
	 *
	 * @param int $post_id Hotel post ID.
	 * @param int $words   Number of words to trim.
	 * @return string Sanitized excerpt.
	 */
	public static function get_hotel_excerpt( $post_id = 0, $words = 20 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$excerpt = get_the_excerpt( $post_id );

		if ( empty( trim( $excerpt ) ) ) {
			$post = get_post( $post_id );
			if ( $post && ! empty( $post->post_content ) ) {
				$excerpt = wp_trim_words(
					wp_strip_all_tags( strip_shortcodes( $post->post_content ) ),
					$words,
					'…'
				);
			}
		} else {
			$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), $words, '…' );
		}

		return apply_filters( 'ab_hotel_excerpt', $excerpt, $post_id, $words );
	}

	/**
	 * Extensible helper for future hotel metadata.
	 *
	 * Future fields:
	 * - distance_mandir
	 * - distance_station
	 * - price_range
	 * - hotel_type
	 * - facilities
	 * - phone
	 * - booking_url
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field   Field key.
	 * @param mixed  $default Default value.
	 * @return mixed Field value.
	 */
	public static function get_hotel_meta( $post_id = 0, $field = '', $default = '' ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$value = get_post_meta( $post_id, $field, true );
		if ( empty( $value ) ) {
			$value = $default;
		}

		return apply_filters( 'ab_hotel_meta_' . $field, $value, $post_id );
	}
}
