/**
 * AB Addon - Admin Widgets Settings Script
 */
(function ($) {
	'use strict';

	$(document).ready(function () {

		var $grid        = $('#ab-widgets-grid');
		var $cards       = $('.ab-widget-card');
		var $searchInput = $('#ab-widget-search');
		var $filterPills = $('.ab-filter-pill');
		var $activeNum   = $('#ab-active-num');
		var $toast       = $('#ab-toast');
		var toastTimer   = null;

		function showToast(msg, isError) {
			if (toastTimer) {
				clearTimeout(toastTimer);
			}
			$toast.find('.ab-toast-text').text(msg);
			if (isError) {
				$toast.addClass('is-error');
			} else {
				$toast.removeClass('is-error');
			}
			$toast.fadeIn(200);
			toastTimer = setTimeout(function () {
				$toast.fadeOut(300);
			}, 2500);
		}

		// -------------------------------------------------------------
		// 1. Single Widget Toggle
		// -------------------------------------------------------------
		$(document).on('change', '.ab-widget-toggle', function () {
			var $toggle  = $(this);
			var widgetId = $toggle.data('widget-id');
			var status   = $toggle.is(':checked') ? 1 : 0;
			var $card    = $toggle.closest('.ab-widget-card');

			$toggle.prop('disabled', true);

			$.ajax({
				url: ab_widgets_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'ab_toggle_widget',
					nonce: ab_widgets_ajax.nonce,
					widget_id: widgetId,
					status: status
				},
				success: function (res) {
					$toggle.prop('disabled', false);
					if (res.success) {
						if (status) {
							$card.removeClass('is-inactive').addClass('is-active');
						} else {
							$card.removeClass('is-active').addClass('is-inactive');
						}
						$activeNum.text(res.data.active_count);
						showToast(res.data.message);
					} else {
						// Revert toggle
						$toggle.prop('checked', !status);
						showToast(res.data && res.data.message ? res.data.message : ab_widgets_ajax.strings.error, true);
					}
				},
				error: function () {
					$toggle.prop('disabled', false);
					$toggle.prop('checked', !status);
					showToast(ab_widgets_ajax.strings.error, true);
				}
			});
		});

		// -------------------------------------------------------------
		// 2. Batch Buttons (Activate All / Deactivate All)
		// -------------------------------------------------------------
		$('.ab-batch-btn').on('click', function (e) {
			e.preventDefault();
			var $btn    = $(this);
			var action  = $btn.data('action');

			if (action === 'deactivate_all' && !confirm('Are you sure you want to deactivate all non-core widgets?')) {
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: ab_widgets_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'ab_batch_toggle_widgets',
					nonce: ab_widgets_ajax.nonce,
					batch_action: action
				},
				success: function (res) {
					$btn.prop('disabled', false);
					if (res.success) {
						var activate = (action === 'activate_all');
						$('.ab-widget-toggle').prop('checked', activate);
						if (activate) {
							$cards.removeClass('is-inactive').addClass('is-active');
						} else {
							$cards.removeClass('is-active').addClass('is-inactive');
						}
						$activeNum.text(res.data.active_count);
						showToast(res.data.message);
					} else {
						showToast(ab_widgets_ajax.strings.error, true);
					}
				},
				error: function () {
					$btn.prop('disabled', false);
					showToast(ab_widgets_ajax.strings.error, true);
				}
			});
		});

		// -------------------------------------------------------------
		// 3. Search and Category Filter Logic
		// -------------------------------------------------------------
		function filterWidgets() {
			var query    = $.trim($searchInput.val()).toLowerCase();
			var category = $('.ab-filter-pill.active').data('category') || 'all';
			var visibleCount = 0;

			$cards.each(function () {
				var $card     = $(this);
				var cardTitle = $card.data('title') || '';
				var cardCat   = $card.data('category') || '';
				var isCreated = $card.data('created') == '1';

				var matchesSearch = (!query || cardTitle.indexOf(query) !== -1);
				var matchesCat    = false;

				if (category === 'all') {
					matchesCat = true;
				} else if (category === 'created') {
					matchesCat = isCreated;
				} else if (category === cardCat) {
					matchesCat = true;
				}

				if (matchesSearch && matchesCat) {
					$card.show();
					visibleCount++;
				} else {
					$card.hide();
				}
			});

			if (visibleCount === 0) {
				$('#ab-no-widgets-found').show();
			} else {
				$('#ab-no-widgets-found').hide();
			}
		}

		$searchInput.on('input', function () {
			filterWidgets();
		});

		$filterPills.on('click', function (e) {
			e.preventDefault();
			$filterPills.removeClass('active').css({
				'background': '#fff',
				'color': '#475569',
				'border': '1px solid #e2e8f0'
			});
			$(this).addClass('active').css({
				'background': '#6c63ff',
				'color': '#fff',
				'border': 'none'
			});
			filterWidgets();
		});

	});

})(jQuery);
