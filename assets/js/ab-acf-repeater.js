/**
 * AB ACF Repeater Input Interactions
 *
 * Handles adding, removing, and reordering repeater rows.
 */

(function($) {
	'use strict';

	/**
	 * Initialize a repeater field container.
	 *
	 * @param {jQuery} $repeater The repeater wrapper.
	 */
	function initRepeater($repeater) {
		if ($repeater.data('ab-repeater-initialized')) {
			return;
		}
		$repeater.data('ab-repeater-initialized', true);

		var min = parseInt($repeater.data('min'), 10) || 0;
		var max = parseInt($repeater.data('max'), 10) || 0;

		/**
		 * Update row numbers and min/max button states.
		 */
		function updateRows() {
			var $container = $repeater.is('table') ? $repeater.find('> tbody') : $repeater.find('> table > tbody, > .acf-repeater-rows');
			var $rows = $container.find('> .acf-row:not(.acf-clone)');
			var count = $rows.length;

			$rows.each(function(index) {
				$(this).find('> .acf-row-handle.order span').text(index + 1);
				$(this).find('> .acf-row-header .acf-row-number').text('Row ' + (index + 1));
			});

			// Check max.
			var $addButtons = $repeater.find('[data-event="add-row"]');
			if (max > 0 && count >= max) {
				$addButtons.addClass('disabled').attr('disabled', 'disabled');
			} else {
				$addButtons.removeClass('disabled').removeAttr('disabled');
			}

			// Check min.
			var $removeButtons = $repeater.find('[data-event="remove-row"]');
			if (min > 0 && count <= min) {
				$removeButtons.addClass('disabled');
			} else {
				$removeButtons.removeClass('disabled');
			}
		}

		/**
		 * Add a new row.
		 */
		$repeater.on('click', '[data-event="add-row"]', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if ($(this).hasClass('disabled')) {
				return;
			}

			var $container = $repeater.find('> table > tbody, > .acf-repeater-rows');
			var $clone = $container.find('> .acf-row.acf-clone');
			if (!$clone.length) {
				return;
			}

			var $clickedRow = $(this).closest('.acf-row');
			var uniqueId = 'row-' + (new Date().getTime()) + '-' + Math.floor(Math.random() * 1000);

			// Clone the template row.
			var $newRow = $clone.clone();
			$newRow.removeClass('acf-clone');
			$newRow.attr('data-id', uniqueId);

			// Enable inputs and update placeholder indices.
			$newRow.find('input, select, textarea').each(function() {
				var $input = $(this);
				$input.removeAttr('disabled').prop('disabled', false);

				var name = $input.attr('name');
				if (name) {
					$input.attr('name', name.replace(/acfcloneindex/g, uniqueId));
				}

				var id = $input.attr('id');
				if (id) {
					$input.attr('id', id.replace(/acfcloneindex/g, uniqueId));
				}
			});

			$newRow.find('label').each(function() {
				var forAttr = $(this).attr('for');
				if (forAttr) {
					$(this).attr('for', forAttr.replace(/acfcloneindex/g, uniqueId));
				}
			});

			$newRow.find('[data-id]').each(function() {
				var dataId = $(this).attr('data-id');
				if (dataId) {
					$(this).attr('data-id', dataId.replace(/acfcloneindex/g, uniqueId));
				}
			});

			// Position the new row: after clicked row or before clone template.
			if ($clickedRow.length && !$clickedRow.hasClass('acf-clone')) {
				$clickedRow.after($newRow);
			} else {
				$clone.before($newRow);
			}

			// Notify ACF to initialize subfields (WYSIWYG, Select2, Image Uploader, etc.).
			if (typeof acf !== 'undefined') {
				acf.doAction('append', $newRow);
				acf.doAction('ready', $newRow);
			}

			updateRows();
		});

		/**
		 * Remove a row.
		 */
		$repeater.on('click', '[data-event="remove-row"]', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if ($(this).hasClass('disabled')) {
				return;
			}

			var $row = $(this).closest('.acf-row');
			if ($row.hasClass('acf-clone')) {
				return;
			}

			if (typeof acf !== 'undefined') {
				acf.doAction('remove', $row);
			}

			$row.css('background-color', '#ffebe8').fadeOut(180, function() {
				$row.remove();
				updateRows();
			});
		});

		/**
		 * Setup drag and drop sortable.
		 */
		var $sortableTarget = $repeater.find('> table > tbody, > .acf-repeater-rows');
		if ($sortableTarget.length && typeof $.fn.sortable !== 'undefined') {
			$sortableTarget.sortable({
				items: '> .acf-row:not(.acf-clone)',
				handle: '.acf-row-handle.order',
				axis: 'y',
				opacity: 0.85,
				placeholder: 'ui-sortable-placeholder',
				forcePlaceholderSize: true,
				stop: function() {
					updateRows();
					if (typeof acf !== 'undefined') {
						acf.doAction('sortstop', $sortableTarget);
					}
				}
			});
		}

		updateRows();
	}

	// Initialize on ACF ready and append actions.
	if (typeof acf !== 'undefined') {
		acf.addAction('ready', function($el) {
			var $context = ($el && $el.length) ? ($el instanceof jQuery ? $el : $($el)) : $(document);
			$context.find('.ab-acf-repeater, .acf-repeater').each(function() {
				initRepeater($(this));
			});
		});

		acf.addAction('append', function($el) {
			var $context = ($el && $el.length) ? ($el instanceof jQuery ? $el : $($el)) : $(document);
			$context.find('.ab-acf-repeater, .acf-repeater').each(function() {
				initRepeater($(this));
			});
		});
	}

	// Fallback DOM ready.
	$(document).ready(function() {
		$('.ab-acf-repeater, .acf-repeater').each(function() {
			initRepeater($(this));
		});
	});

})(jQuery);
