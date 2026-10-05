/**
 * AB ACF Repeater - Field Group Editor Unlock
 *
 * Removes PRO restrictions from the Repeater field type in ACF's Field Group editor.
 */

(function($) {
	'use strict';

	if (typeof acf === 'undefined') {
		return;
	}

	/**
	 * Unlocks the Repeater field in ACF's internal field type registry.
	 */
	function unlockRepeater() {
		// 1. Remove repeater from PROFieldTypes.
		var proTypes = acf.get('PROFieldTypes');
		if (proTypes && proTypes.repeater) {
			delete proTypes.repeater;
			acf.set('PROFieldTypes', proTypes);
		}

		// 2. Mark repeater as non-pro in fieldTypes.
		var fieldTypes = acf.get('fieldTypes');
		if (fieldTypes && fieldTypes.repeater) {
			fieldTypes.repeater.pro = false;
			fieldTypes.repeater.public = true;
		}

		// 3. Remove lock badge and pro classes from DOM.
		$('.acf-field-type-item[data-type="repeater"], .acf-field-picker-item[data-type="repeater"]').each(function() {
			$(this).removeClass('acf-field-type-pro is-pro');
			$(this).find('.acf-pro-label, .acf-icon-lock').remove();
		});
	}

	/**
	 * When the modal is open and repeater is selected, ensure the "Select Field" button is visible and active.
	 */
	function handleFieldSelection() {
		var $modal = $('.acf-browse-fields-modal, .acf-field-picker');
		if (!$modal.length) {
			return;
		}

		// Check if Repeater is the active item in preview.
		var activeName = $modal.find('.field-type-name').text().trim().toLowerCase();
		var isRepeater = activeName === 'repeater' || $modal.find('.acf-field-type-item.active[data-type="repeater"]').length > 0;

		if (isRepeater) {
			$modal.find('.acf-btn-pro, .field-type-upgrade-to-unlock').hide();
			$modal.find('.acf-select-field').show();
			$modal.find('.acf-insert-field-label').removeAttr('disabled').prop('disabled', false);
		}
	}

	// Run on initial page load and after ACF loads.
	$(document).ready(function() {
		unlockRepeater();
	});

	// Trigger on modal interactions.
	$(document).on('click', '.acf-btn.add-field, [data-name="add-field"], .acf-field-type-picker, .acf-search-field-types', function() {
		setTimeout(unlockRepeater, 50);
		setTimeout(unlockRepeater, 200);
	});

	$(document).on('click', '.acf-field-type-item, .acf-field-picker-item', function() {
		var type = $(this).data('type') || $(this).attr('data-type');
		if (type === 'repeater') {
			setTimeout(function() {
				unlockRepeater();
				handleFieldSelection();
			}, 30);
		}
	});

	// Listen for ACF actions.
	if (typeof acf.addAction !== 'undefined') {
		acf.addAction('ready', unlockRepeater);
		acf.addAction('open_modal', function() {
			unlockRepeater();
			setTimeout(unlockRepeater, 100);
		});
	}

})(jQuery);
