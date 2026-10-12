/**
 * AB Weather Admin Settings JavaScript
 *
 * Handles Test Connection AJAX call, Cache Purge, and provider terms guidance.
 */

(function ($) {
	'use strict';

	$(function () {
		var $providerSelect = $('#ab_weather_provider');
		var $apiKeyGroup = $('#ab-weather-api-key-group');
		var $termsBox = $('.ab-weather-terms-box');
		var $termsDesc = $('#ab-provider-terms-desc');
		var $testBtn = $('#ab-weather-test-btn');
		var $testResult = $('#ab-weather-test-result');
		var $purgeBtn = $('#ab-weather-purge-cache-btn');

		var providerNotes = {
			met_norway: {
				note: 'Official public data from The Norwegian Meteorological Institute under NLOD 2.0 & CC BY 4.0. Free for both commercial and non-commercial websites with no API key required.',
				border: '#16a34a',
				bg: '#f0fdf4',
				needsKey: false
			},
			open_meteo: {
				note: 'Open-Meteo free tier is restricted to non-commercial projects. Because Ramdevraa.in promotes commercial taxi booking services, an Open-Meteo commercial subscription / API key is required for compliant production use. If you do not have an API key, use MET Norway instead.',
				border: '#ea580c',
				bg: '#fff7ed',
				needsKey: true
			},
			weatherapi: {
				note: 'WeatherAPI.com explicitly allows commercial use on its free tier (up to 100,000 calls/month). Requires a free API key from weatherapi.com and attribution.',
				border: '#0284c7',
				bg: '#f0f9ff',
				needsKey: true
			}
		};

		// Update UI when provider select changes
		$providerSelect.on('change', function () {
			var pid = $(this).val();
			var pInfo = providerNotes[pid];
			if (pInfo) {
				$termsDesc.text(pInfo.note);
				$termsBox.css({
					'border-left-color': pInfo.border,
					'background-color': pInfo.bg
				});
				if (pInfo.needsKey) {
					$apiKeyGroup.slideDown(200);
				} else {
					$apiKeyGroup.slideUp(200);
				}
			}
		});

		// Test Connection button
		$testBtn.on('click', function (e) {
			e.preventDefault();

			var provider = $providerSelect.val();
			var apiKey = $('#ab_weather_api_key').val();
			var lat = $('#ab_weather_latitude').val();
			var lon = $('#ab_weather_longitude').val();

			$testBtn.prop('disabled', true).addClass('updating-message');
			$testResult.show().html('<div class="notice notice-info inline"><p>' + (abWeatherAdmin.testing || 'Testing connection...') + '</p></div>');

			$.ajax({
				url: abWeatherAdmin.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'ab_weather_test_connection',
					nonce: abWeatherAdmin.nonce,
					provider: provider,
					api_key: apiKey,
					latitude: lat,
					longitude: lon
				},
				success: function (res) {
					$testBtn.prop('disabled', false).removeClass('updating-message');
					if (res.success && res.data) {
						var d = res.data;
						$testResult.html(
							'<div class="notice notice-success inline" style="padding: 10px 14px;">' +
								'<p style="margin: 0 0 4px 0; font-weight: 600; color: #16a34a;">' +
									'<span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span> ' +
									d.message +
								'</p>' +
								'<div style="font-size: 12px; color: #475569; display: flex; gap: 15px; margin-top: 6px;">' +
									'<span><strong>Latency:</strong> ' + d.duration + '</span>' +
									'<span><strong>Condition:</strong> ' + d.condition + '</span>' +
									'<span><strong>Temp:</strong> ' + d.temperature + '</span>' +
									'<span><strong>Time:</strong> ' + d.fetched_at + '</span>' +
								'</div>' +
							'</div>'
						);
					} else {
						var errMsg = (res.data && res.data.message) ? res.data.message : 'Unknown connection error';
						$testResult.html(
							'<div class="notice notice-error inline" style="padding: 10px 14px;">' +
								'<p style="margin: 0; font-weight: 600; color: #dc2626;">' +
									'<span class="dashicons dashicons-warning" style="vertical-align: middle;"></span> ' +
									'Connection Failed: ' + errMsg +
								'</p>' +
							'</div>'
						);
					}
				},
				error: function (xhr, status, error) {
					$testBtn.prop('disabled', false).removeClass('updating-message');
					$testResult.html(
						'<div class="notice notice-error inline"><p>AJAX Error: ' + error + '</p></div>'
					);
				}
			});
		});

		// Purge Cache button
		$purgeBtn.on('click', function (e) {
			e.preventDefault();
			$purgeBtn.prop('disabled', true);

			$.ajax({
				url: abWeatherAdmin.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'ab_weather_purge_cache',
					nonce: abWeatherAdmin.nonce
				},
				success: function (res) {
					$purgeBtn.prop('disabled', false);
					alert(res.data ? res.data.message : 'Weather cache cleared!');
				},
				error: function () {
					$purgeBtn.prop('disabled', false);
					alert('Failed to clear weather cache.');
				}
			});
		});
	});
})(jQuery);
