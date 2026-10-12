<?php
/**
 * AB Weather Renderer
 *
 * Shared presentation component for Elementor Free widget, Gutenberg block,
 * and [ab_weather] shortcode. Produces clean, accessible, modern semantic markup.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-ab-weather-icons.php';

class AB_Weather_Renderer {

	/**
	 * Enqueue weather frontend CSS conditionally when weather output is rendered.
	 */
	public static function enqueue_assets() {
		if ( ! wp_style_is( 'ab-weather-style', 'enqueued' ) ) {
			wp_enqueue_style( 'ab-weather-style' );
		}
	}

	/**
	 * Render the weather widget/block HTML.
	 *
	 * @param array $args Component configuration arguments.
	 * @return string HTML output.
	 */
	public static function render( $args = array() ) {
		self::enqueue_assets();

		$service  = AB_Weather_Service::instance();
		$settings = $service->get_settings();

		// Default options merged with component instance arguments.
		$defaults = array(
			'mode'               => 'full', // 'compact', 'full', 'forecast'
			'title'              => '',
			'forecast_days'      => intval( $settings['forecast_days'] ?? 5 ),
			'show_feels_like'    => true,
			'show_high_low'      => true,
			'show_humidity'      => true,
			'show_wind'          => true,
			'show_rain_prob'     => true,
			'show_sun_times'     => true,
			'show_updated_time'  => true,
			'show_attribution'   => ! empty( $settings['show_attribution'] ),
			'show_cta'           => ! empty( $settings['cta_enabled'] ),
			'cta_text'           => $settings['cta_text'] ?? __( 'Planning a trip to Ramdevra? Book your taxi.', 'ab-addon' ),
			'cta_url'            => $settings['cta_url'] ?? 'https://ramdevraa.in/taxi-booking/',
			'max_width'          => '',
			'extra_class'        => '',
		);

		$opts = wp_parse_args( $args, $defaults );

		// Fetch weather data from service.
		$weather = $service->get_weather();

		if ( is_wp_error( $weather ) ) {
			return self::render_error_fallback( $weather->get_error_message(), $opts );
		}

		$mode        = sanitize_key( $opts['mode'] );
		$title       = ! empty( $opts['title'] ) ? $opts['title'] : $weather['location_display'];
		$current     = $weather['current'];
		$forecast    = $weather['forecast'];
		$days_count  = max( 1, min( 7, intval( $opts['forecast_days'] ) ) );
		$extra_class = esc_attr( trim( 'ab-weather-container ab-weather-mode-' . $mode . ' ' . $opts['extra_class'] ) );
		$style_attr  = '';
		if ( ! empty( $opts['max_width'] ) ) {
			$style_attr = ' style="max-width:' . esc_attr( $opts['max_width'] ) . ';"';
		}

		ob_start();
		?>
		<div class="<?php echo $extra_class; ?>"<?php echo $style_attr; ?> data-provider="<?php echo esc_attr( $weather['provider'] ); ?>">
			<div class="ab-weather-card">
				
				<!-- CARD HEADER -->
				<div class="ab-weather-header">
					<div class="ab-weather-location">
						<span class="ab-weather-loc-icon" aria-hidden="true">
							<?php echo AB_Weather_Icons::get( 'location', 18 ); ?>
						</span>
						<h3 class="ab-weather-title"><?php echo esc_html( $title ); ?></h3>
					</div>

					<?php if ( ! empty( $opts['show_updated_time'] ) ) : ?>
						<div class="ab-weather-meta" title="<?php echo esc_attr( sprintf( __( 'Data source: %1$s (%2$s)', 'ab-addon' ), $weather['provider_name'], $weather['source_tag'] ) ); ?>">
							<span class="ab-weather-clock-icon" aria-hidden="true">
								<?php echo AB_Weather_Icons::get( 'clock', 14 ); ?>
							</span>
							<time class="ab-weather-time" datetime="<?php echo esc_attr( date( 'c', $weather['fetched_at'] ) ); ?>">
								<?php echo esc_html( $weather['updated_ist_time'] . ' IST' ); ?>
							</time>
							<span class="ab-weather-badge ab-weather-badge-<?php echo $weather['is_cached'] ? 'cached' : 'live'; ?>">
								<?php echo esc_html( $weather['source_tag'] ); ?>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $weather['notice'] ) ) : ?>
					<div class="ab-weather-notice" role="status">
						<?php echo esc_html( $weather['notice'] ); ?>
					</div>
				<?php endif; ?>

				<!-- HERO WEATHER SECTION (Current Conditions) -->
				<?php if ( 'forecast' !== $mode ) : ?>
					<div class="ab-weather-hero">
						<div class="ab-weather-current-main">
							<div class="ab-weather-hero-icon" aria-hidden="true">
								<?php echo AB_Weather_Icons::get( $current['condition_icon'], 64, 'ab-weather-hero-svg' ); ?>
							</div>
							<div class="ab-weather-temp-wrap">
								<span class="ab-weather-temp-num"><?php echo esc_html( $current['temp_val'] ); ?></span>
								<span class="ab-weather-temp-unit"><?php echo esc_html( $weather['temp_symbol'] ); ?></span>
							</div>
						</div>

						<div class="ab-weather-current-desc">
							<span class="ab-weather-condition-text"><?php echo esc_html( $current['condition_text'] ); ?></span>

							<div class="ab-weather-sub-metrics">
								<?php if ( ! empty( $opts['show_feels_like'] ) ) : ?>
									<span class="ab-weather-feels-like">
										<?php
										/* translators: %1$s: Temperature, %2$s: Unit symbol */
										printf( esc_html__( 'Feels like %1$s%2$s', 'ab-addon' ), esc_html( $current['feels_like_val'] ), esc_html( $weather['temp_symbol'] ) );
										?>
									</span>
								<?php endif; ?>

								<?php if ( ! empty( $opts['show_high_low'] ) ) : ?>
									<span class="ab-weather-high-low" aria-label="<?php esc_attr_e( 'Today high and low temperature', 'ab-addon' ); ?>">
										<span class="ab-weather-high">
											<?php echo AB_Weather_Icons::get( 'arrow_up', 12 ); ?>
											<?php echo esc_html( $current['temp_max_val'] . $weather['temp_symbol'] ); ?>
										</span>
										<span class="ab-weather-hl-sep">/</span>
										<span class="ab-weather-low">
											<?php echo AB_Weather_Icons::get( 'arrow_down', 12 ); ?>
											<?php echo esc_html( $current['temp_min_val'] . $weather['temp_symbol'] ); ?>
										</span>
									</span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- METRICS GRID (Details) -->
					<?php if ( 'full' === $mode ) : ?>
						<div class="ab-weather-metrics-grid">
							<?php if ( ! empty( $opts['show_humidity'] ) ) : ?>
								<div class="ab-weather-metric-item" title="<?php esc_attr_e( 'Relative Humidity', 'ab-addon' ); ?>">
									<span class="ab-weather-metric-icon" aria-hidden="true">
										<?php echo AB_Weather_Icons::get( 'humidity', 18 ); ?>
									</span>
									<div class="ab-weather-metric-info">
										<span class="ab-weather-metric-label"><?php esc_html_e( 'Humidity', 'ab-addon' ); ?></span>
										<span class="ab-weather-metric-val"><?php echo esc_html( $current['humidity'] ); ?>%</span>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $opts['show_wind'] ) ) : ?>
								<div class="ab-weather-metric-item" title="<?php esc_attr_e( 'Wind Speed', 'ab-addon' ); ?>">
									<span class="ab-weather-metric-icon" aria-hidden="true">
										<?php echo AB_Weather_Icons::get( 'wind', 18 ); ?>
									</span>
									<div class="ab-weather-metric-info">
										<span class="ab-weather-metric-label"><?php esc_html_e( 'Wind', 'ab-addon' ); ?></span>
										<span class="ab-weather-metric-val"><?php echo esc_html( $current['wind_display'] ); ?></span>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $opts['show_rain_prob'] ) ) : ?>
								<div class="ab-weather-metric-item" title="<?php esc_attr_e( 'Precipitation Chance', 'ab-addon' ); ?>">
									<span class="ab-weather-metric-icon" aria-hidden="true">
										<?php echo AB_Weather_Icons::get( 'rain_prob', 18 ); ?>
									</span>
									<div class="ab-weather-metric-info">
										<span class="ab-weather-metric-label"><?php esc_html_e( 'Rain Chance', 'ab-addon' ); ?></span>
										<span class="ab-weather-metric-val"><?php echo esc_html( $current['precipitation_prob'] ); ?>%</span>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $opts['show_sun_times'] ) && ! empty( $current['sunrise'] ) ) : ?>
								<div class="ab-weather-metric-item" title="<?php esc_attr_e( 'Sunrise & Sunset (Asia/Kolkata)', 'ab-addon' ); ?>">
									<span class="ab-weather-metric-icon" aria-hidden="true">
										<?php echo AB_Weather_Icons::get( 'sunrise', 18 ); ?>
									</span>
									<div class="ab-weather-metric-info">
										<span class="ab-weather-metric-label"><?php esc_html_e( 'Sun', 'ab-addon' ); ?></span>
										<span class="ab-weather-metric-val ab-weather-sun-val">
											<span class="ab-weather-sun-part" title="<?php esc_attr_e( 'Sunrise', 'ab-addon' ); ?>">↑<?php echo esc_html( $current['sunrise'] ); ?></span>
											<span class="ab-weather-sun-part" title="<?php esc_attr_e( 'Sunset', 'ab-addon' ); ?>">↓<?php echo esc_html( $current['sunset'] ); ?></span>
										</span>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<!-- FORECAST SECTION (5-Day Outlook) -->
				<?php if ( ( 'full' === $mode || 'forecast' === $mode ) && ! empty( $forecast ) ) : ?>
					<div class="ab-weather-forecast-wrap">
						<h4 class="ab-weather-forecast-heading">
							<?php
							/* translators: %d: Number of forecast days */
							printf( esc_html__( '%d-Day Weather Forecast', 'ab-addon' ), esc_html( min( $days_count, count( $forecast ) ) ) );
							?>
						</h4>

						<div class="ab-weather-forecast-grid">
							<?php
							$day_index = 0;
							foreach ( $forecast as $f_day ) :
								$day_index++;
								if ( $day_index > $days_count ) {
									break;
								}
								?>
								<div class="ab-weather-forecast-card">
									<div class="ab-weather-f-day"><?php echo esc_html( $f_day['day_name'] ); ?></div>
									<div class="ab-weather-f-date"><?php echo esc_html( $f_day['day_date'] ); ?></div>
									<div class="ab-weather-f-icon" aria-hidden="true">
										<?php echo AB_Weather_Icons::get( $f_day['condition_icon'], 32 ); ?>
									</div>
									<div class="ab-weather-f-cond" title="<?php echo esc_attr( $f_day['condition_text'] ); ?>">
										<?php echo esc_html( $f_day['condition_text'] ); ?>
									</div>
									<div class="ab-weather-f-temps">
										<span class="ab-weather-f-max" title="<?php esc_attr_e( 'Maximum Temperature', 'ab-addon' ); ?>"><?php echo esc_html( $f_day['temp_max_val'] . '°' ); ?></span>
										<span class="ab-weather-f-sep">/</span>
										<span class="ab-weather-f-min" title="<?php esc_attr_e( 'Minimum Temperature', 'ab-addon' ); ?>"><?php echo esc_html( $f_day['temp_min_val'] . '°' ); ?></span>
									</div>
									<?php if ( ! empty( $opts['show_rain_prob'] ) && $f_day['precipitation_prob'] > 0 ) : ?>
										<div class="ab-weather-f-rain" title="<?php esc_attr_e( 'Rain probability', 'ab-addon' ); ?>">
											<span class="ab-weather-rain-icon" aria-hidden="true"><?php echo AB_Weather_Icons::get( 'rain_prob', 12 ); ?></span>
											<span><?php echo esc_html( $f_day['precipitation_prob'] ); ?>%</span>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- TRAVEL / TAXI CALL TO ACTION -->
				<?php if ( ! empty( $opts['show_cta'] ) && ! empty( $opts['cta_url'] ) ) : ?>
					<div class="ab-weather-cta-bar">
						<div class="ab-weather-cta-text">
							<span class="ab-weather-taxi-icon" aria-hidden="true">
								<?php echo AB_Weather_Icons::get( 'taxi', 20 ); ?>
							</span>
							<span><?php echo esc_html( $opts['cta_text'] ); ?></span>
						</div>
						<a href="<?php echo esc_url( $opts['cta_url'] ); ?>" class="ab-weather-cta-btn" target="_blank" rel="noopener">
							<?php esc_html_e( 'Book Taxi', 'ab-addon' ); ?>
						</a>
					</div>
				<?php endif; ?>

				<!-- ATTRIBUTION & LICENSING FOOTER -->
				<?php if ( ! empty( $opts['show_attribution'] ) && ! empty( $weather['attribution'] ) ) : ?>
					<div class="ab-weather-footer">
						<span class="ab-weather-attr">
							<a href="<?php echo esc_url( $weather['attribution']['url'] ); ?>" target="_blank" rel="nofollow noopener">
								<?php echo esc_html( $weather['attribution']['text'] ); ?>
							</a>
						</span>
					</div>
				<?php endif; ?>

			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render graceful fallback UI when weather data is temporarily unavailable.
	 *
	 * @param string $error_message
	 * @param array  $opts
	 * @return string
	 */
	private static function render_error_fallback( $error_message, $opts ) {
		$title = ! empty( $opts['title'] ) ? $opts['title'] : __( 'Ramdevra Weather', 'ab-addon' );

		ob_start();
		?>
		<div class="ab-weather-container ab-weather-fallback <?php echo esc_attr( $opts['extra_class'] ); ?>">
			<div class="ab-weather-card ab-weather-fallback-card">
				<div class="ab-weather-header">
					<div class="ab-weather-location">
						<span class="ab-weather-loc-icon" aria-hidden="true">
							<?php echo AB_Weather_Icons::get( 'location', 18 ); ?>
						</span>
						<h3 class="ab-weather-title"><?php echo esc_html( $title ); ?></h3>
					</div>
				</div>
				<div class="ab-weather-fallback-body">
					<span class="ab-weather-fallback-icon" aria-hidden="true">
						<?php echo AB_Weather_Icons::get( 'alert', 36 ); ?>
					</span>
					<p class="ab-weather-fallback-msg">
						<?php esc_html_e( 'Weather information for Ramdevra is temporarily updating. Please refresh in a few moments.', 'ab-addon' ); ?>
					</p>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<small class="ab-weather-admin-debug">
							<?php
							/* translators: %s: Technical error details */
							printf( esc_html__( 'Admin Notice: %s', 'ab-addon' ), esc_html( $error_message ) );
							?>
						</small>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $opts['show_cta'] ) && ! empty( $opts['cta_url'] ) ) : ?>
					<div class="ab-weather-cta-bar">
						<div class="ab-weather-cta-text">
							<span class="ab-weather-taxi-icon" aria-hidden="true">
								<?php echo AB_Weather_Icons::get( 'taxi', 20 ); ?>
							</span>
							<span><?php echo esc_html( $opts['cta_text'] ); ?></span>
						</div>
						<a href="<?php echo esc_url( $opts['cta_url'] ); ?>" class="ab-weather-cta-btn" target="_blank" rel="noopener">
							<?php esc_html_e( 'Book Taxi', 'ab-addon' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
