(function ($) {
	'use strict';

	var ABBlogGrid = function ($scope) {
		var $wrapper    = $scope.find('.ab-blog-grid-wrapper');
		if (!$wrapper.length) {
			return;
		}

		var $grid       = $wrapper.find('.ab-blog-grid');
		var $loader     = $wrapper.find('.ab-grid-loader');
		var $filterBtns = $wrapper.find('.ab-filter-btn');
		var $loadMoreBtn= $wrapper.find('.ab-load-more-btn');
		var rawSettings = $wrapper.data('settings');

		var activeCatId = 0;
		var currentPage = 1;

		// --- Category Filter Click ---
		$filterBtns.on('click', function (e) {
			e.preventDefault();
			var $btn = $(this);

			if ($btn.hasClass('active')) {
				return;
			}

			$filterBtns.removeClass('active');
			$btn.addClass('active');

			activeCatId = parseInt($btn.data('cat-id'), 10) || 0;
			currentPage = 1;

			fetchPosts(false);
		});

		// --- Load More Click ---
		$loadMoreBtn.on('click', function (e) {
			e.preventDefault();
			currentPage++;
			fetchPosts(true);
		});

		function fetchPosts(isAppend) {
			if (!rawSettings) {
				return;
			}

			$loader.fadeIn(200);
			if (!isAppend) {
				$grid.css('opacity', '0.4');
			}

			var data = {
				action: 'ab_blog_grid_filter',
				nonce: ab_addon_ajax.nonce,
				cat_id: activeCatId,
				paged: currentPage,
				post_type: rawSettings.post_type || 'post',
				ppp: rawSettings.posts_per_page || 6,
				orderby: rawSettings.orderby || 'date',
				order: rawSettings.order || 'DESC',
				offset: rawSettings.offset || 0,
				card_style: rawSettings.card_style || 'modern',
				settings: rawSettings
			};

			$.ajax({
				url: ab_addon_ajax.ajax_url,
				type: 'POST',
				data: data,
				success: function (response) {
					$loader.fadeOut(200);
					$grid.css('opacity', '1');

					if (response.success) {
						if (isAppend) {
							$grid.append(response.data.html);
						} else {
							$grid.html(response.data.html);
						}

						// Update Load More Button state
						if ($loadMoreBtn.length) {
							if (currentPage >= response.data.max_pages) {
								$loadMoreBtn.parent().hide();
							} else {
								$loadMoreBtn.parent().show();
								$loadMoreBtn.data('paged', currentPage);
							}
						}
					}
				},
				error: function () {
					$loader.fadeOut(200);
					$grid.css('opacity', '1');
				}
			});
		}
	};

	$(window).on('elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction('frontend/element_ready/ab_blog_grid.default', ABBlogGrid);
		elementorFrontend.hooks.addAction('frontend/element_ready/ab_basic_posts.default', ABBlogGrid);
	});

	// Fallback for non-elementor pages or standard jQuery ready
	$(document).ready(function () {
		$('.elementor-widget-ab_blog_grid, .elementor-widget-ab_basic_posts').each(function () {
			ABBlogGrid($(this));
		});
	});

})(jQuery);
