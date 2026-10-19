/**
 * AB Slider Admin Manager Script
 *
 * Handles slide repeater controls, WP Media library image picking,
 * re-ordering, slide collapsing, and clipboard shortcode copying.
 *
 * @package AB_Addon
 */

(function ($) {
	'use strict';

	$(document).ready(function () {

		// 1. Copy Shortcode to Clipboard
		$(document).on('click', '.ab-copy-shortcode-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var code = $btn.data('clipboard') || $btn.text().trim();

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(code).then(function () {
					showCopiedTooltip($btn);
				});
			} else {
				var $temp = $('<input>');
				$('body').append($temp);
				$temp.val(code).select();
				document.execCommand('copy');
				$temp.remove();
				showCopiedTooltip($btn);
			}
		});

		function showCopiedTooltip($el) {
			var origText = $el.html();
			$el.html('<span class="dashicons dashicons-yes"></span> Copied!');
			$el.addClass('ab-copied-success');
			setTimeout(function () {
				$el.html(origText);
				$el.removeClass('ab-copied-success');
			}, 2000);
		}

		// 2. Media Library Image Picker (Background & Mobile Background)
		$(document).on('click', '.ab-upload-image-btn', function (e) {
			e.preventDefault();
			var $btn    = $(this);
			var $row    = $btn.closest('.ab-media-field-wrap');
			var $input  = $row.find('.ab-image-url-input');
			var $preview= $row.find('.ab-image-preview');
			var $remove = $row.find('.ab-remove-image-btn');

			var customUploader = wp.media({
				title: 'Select Slide Image',
				button: {
					text: 'Use this image'
				},
				multiple: false
			}).on('select', function () {
				var attachment = customUploader.state().get('selection').first().toJSON();
				$input.val(attachment.url);
				$preview.html('<img src="' + attachment.url + '" alt="preview" style="max-height: 90px; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" />');
				$remove.show();

				// Update slide header thumbnail if it's the main desktop bg
				if ($input.hasClass('ab-slide-main-bg')) {
					var $slideItem = $btn.closest('.ab-slide-item');
					$slideItem.find('.ab-slide-header-thumb').html('<img src="' + attachment.url + '" />');
				}
			}).open();
		});

		// Remove Image
		$(document).on('click', '.ab-remove-image-btn', function (e) {
			e.preventDefault();
			var $btn    = $(this);
			var $row    = $btn.closest('.ab-media-field-wrap');
			var $input  = $row.find('.ab-image-url-input');
			var $preview= $row.find('.ab-image-preview');

			$input.val('');
			$preview.empty();
			$btn.hide();

			if ($input.hasClass('ab-slide-main-bg')) {
				var $slideItem = $btn.closest('.ab-slide-item');
				$slideItem.find('.ab-slide-header-thumb').html('<span class="dashicons dashicons-format-image"></span>');
			}
		});

		// 3. Slide Accordion Collapse/Expand
		$(document).on('click', '.ab-slide-header-bar', function (e) {
			// Don't trigger if clicking buttons inside header
			if ($(e.target).closest('.ab-slide-header-actions').length) {
				return;
			}
			var $item = $(this).closest('.ab-slide-item');
			$item.toggleClass('ab-collapsed');
			var $body = $item.find('.ab-slide-body');
			$body.slideToggle(200);
		});

		// 4. Move Slide Up
		$(document).on('click', '.ab-slide-move-up', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.ab-slide-item');
			var $prev = $item.prev('.ab-slide-item');
			if ($prev.length) {
				$item.insertBefore($prev);
				reindexSlides();
			}
		});

		// 5. Move Slide Down
		$(document).on('click', '.ab-slide-move-down', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.ab-slide-item');
			var $next = $item.next('.ab-slide-item');
			if ($next.length) {
				$item.insertAfter($next);
				reindexSlides();
			}
		});

		// 6. Delete Slide
		$(document).on('click', '.ab-slide-delete', function (e) {
			e.preventDefault();
			var $list = $('#ab-slides-list');
			if ($list.find('.ab-slide-item').length <= 1) {
				alert('A slider must have at least one slide.');
				return;
			}
			if (confirm('Are you sure you want to delete this slide?')) {
				var $item = $(this).closest('.ab-slide-item');
				$item.fadeOut(200, function () {
					$(this).remove();
					reindexSlides();
				});
			}
		});

		// 7. Duplicate Slide
		$(document).on('click', '.ab-slide-duplicate', function (e) {
			e.preventDefault();
			var $item = $(this).closest('.ab-slide-item');
			var $clone = $item.clone();

			// Generate new ID for cloned slide
			var newId = 'slide_' + Date.now();
			$clone.attr('data-slide-id', newId);
			$clone.find('input[name*="[id]"]').val(newId);

			$clone.insertAfter($item);
			reindexSlides();
		});

		// 8. Add New Slide
		$('#ab-add-slide-btn').on('click', function (e) {
			e.preventDefault();
			var $list = $('#ab-slides-list');
			var $first = $list.find('.ab-slide-item').first();
			var newIndex = $list.find('.ab-slide-item').length;
			var newId = 'slide_' + Date.now();

			var $newSlide = $first.clone();
			$newSlide.removeClass('ab-collapsed');
			$newSlide.find('.ab-slide-body').show();
			$newSlide.attr('data-slide-id', newId);

			// Reset input values
			$newSlide.find('input[type="text"]').val('');
			$newSlide.find('textarea').val('');
			$newSlide.find('input[name*="[id]"]').val(newId);
			$newSlide.find('input[name*="[overlay_color]"]').val('rgba(15, 23, 42, 0.55)');
			$newSlide.find('.ab-image-url-input').val('');
			$newSlide.find('.ab-image-preview').empty();
			$newSlide.find('.ab-remove-image-btn').hide();
			$newSlide.find('.ab-slide-header-thumb').html('<span class="dashicons dashicons-format-image"></span>');
			$newSlide.find('.ab-slide-title-display').text('New Slide');

			$list.append($newSlide);
			reindexSlides();

			// Scroll smoothly to newly added slide
			$('html, body').animate({
				scrollTop: $newSlide.offset().top - 100
			}, 300);
		});

		// Live update slide title display in header bar
		$(document).on('input', '.ab-slide-title-input', function () {
			var val = $(this).val().trim();
			var $headerText = $(this).closest('.ab-slide-item').find('.ab-slide-title-display');
			$headerText.text(val || 'Untitled Slide');
		});

		// Reindex names and badges
		function reindexSlides() {
			var $items = $('#ab-slides-list').find('.ab-slide-item');
			$items.each(function (idx) {
				var $item = $(this);
				$item.find('.ab-slide-number-badge').text('#' + (idx + 1));

				// Update input name attributes e.g. slides[0][title] -> slides[1][title]
				$item.find('input, select, textarea').each(function () {
					var name = $(this).attr('name');
					if (name) {
						var updatedName = name.replace(/slides\[\d+\]/, 'slides[' + idx + ']');
						$(this).attr('name', updatedName);
					}
				});
			});
			$('#ab-slide-count-badge').text($items.length + ' slides');
		}

	});

})(jQuery);
