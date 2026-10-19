/**
 * AB Blog Grid & Basic Posts Script
 *
 * Handles AJAX Category Filtering, Load More Pagination, and
 * Swiper Slider / Carousel layout mode for Blog Grid.
 *
 * @package AB_Addon
 */

(function ($) {
	'use strict';

	var ABBlogGrid = function ($scope) {
		if (!$scope || !$scope.length) {
			return;
		}

		var $wrapper = $scope.hasClass('ab-blog-grid-wrapper') ? $scope : $scope.find('.ab-blog-grid-wrapper');
		if (!$wrapper.length) {
			$wrapper = $scope.closest('.ab-blog-grid-wrapper');
		}
		if (!$wrapper.length) {
			return;
		}

		var $grid       = $wrapper.find('.ab-blog-grid');
		var $sliderWrap = $wrapper.find('.ab-blog-slider-wrap');
		var $loader     = $wrapper.find('.ab-grid-loader');
		var $filterBtns = $wrapper.find('.ab-filter-btn');
		var $loadMoreBtn= $wrapper.find('.ab-load-more-btn');

		var rawSettings = $wrapper.data('settings');
		if (typeof rawSettings === 'string') {
			try {
				rawSettings = JSON.parse(rawSettings);
			} catch (e) {
				rawSettings = {};
			}
		} else if (typeof rawSettings !== 'object' || rawSettings === null) {
			rawSettings = {};
		}

		var activeCatId = 0;
		var currentPage = 1;

		// --- Swiper Slider Initialization ---
		function initSlider() {
			if (!$sliderWrap.length) {
				return;
			}

			var $slider = $sliderWrap.find('.ab-blog-slider');
			if (!$slider.length) {
				return;
			}

			// Clean up any existing Swiper instance on this container
			var existingInstance = $slider.data('ab-swiper');
			if (existingInstance && typeof existingInstance.destroy === 'function') {
				try {
					existingInstance.destroy(true, true);
				} catch (e) {}
			}
			if ($slider[0] && $slider[0].swiper && typeof $slider[0].swiper.destroy === 'function') {
				try {
					$slider[0].swiper.destroy(true, true);
				} catch (e) {}
			}

			var slideCount = $slider.find('.swiper-slide').length;
			if (slideCount === 0) {
				return;
			}

			var rawSliderConfig = $sliderWrap.data('slider-config');
			var config = {};
			if (typeof rawSliderConfig === 'object' && rawSliderConfig !== null) {
				config = rawSliderConfig;
			} else if (typeof rawSliderConfig === 'string') {
				try {
					config = JSON.parse(rawSliderConfig);
				} catch (e) {
					config = {};
				}
			}

			var slidesPerView       = parseInt(config.slidesPerView, 10) || 3;
			var slidesPerViewTablet = parseInt(config.slidesPerViewTablet, 10) || (slidesPerView > 2 ? 2 : slidesPerView);
			var slidesPerViewMobile = parseInt(config.slidesPerViewMobile, 10) || 1;

			var spaceBetween        = typeof config.spaceBetween !== 'undefined' ? parseInt(config.spaceBetween, 10) : 24;
			var spaceBetweenTablet  = typeof config.spaceBetweenTablet !== 'undefined' ? parseInt(config.spaceBetweenTablet, 10) : Math.min(20, spaceBetween);
			var spaceBetweenMobile  = typeof config.spaceBetweenMobile !== 'undefined' ? parseInt(config.spaceBetweenMobile, 10) : Math.min(16, spaceBetween);

			var isLoop     = (config.loop === true || config.loop === 'yes') && (slideCount > slidesPerView);
			var isAutoplay = (config.autoplay === true || config.autoplay === 'yes') && slideCount > 1;
			var autoplaySpeed = parseInt(config.autoplaySpeed || config.autoplay_speed, 10) || 3000;

			var showArrows = config.arrows !== false && config.arrows !== 'no' && slideCount > 1;
			var showDots   = config.dots !== false && config.dots !== 'no' && slideCount > 1;

			var swiperOptions = {
				slidesPerView: slidesPerViewMobile,
				spaceBetween: spaceBetweenMobile,
				loop: isLoop,
				speed: 500,
				watchOverflow: true,
				observer: true,
				observeParents: true,
				watchSlidesProgress: true,
				breakpoints: {
					320: {
						slidesPerView: slidesPerViewMobile,
						spaceBetween: spaceBetweenMobile,
					},
					768: {
						slidesPerView: slidesPerViewTablet,
						spaceBetween: spaceBetweenTablet,
					},
					1025: {
						slidesPerView: slidesPerView,
						spaceBetween: spaceBetween,
					},
				},
			};

			if (isAutoplay) {
				swiperOptions.autoplay = {
					delay: autoplaySpeed,
					disableOnInteraction: false,
					pauseOnMouseEnter: true,
				};
			} else {
				swiperOptions.autoplay = false;
			}

			var $prev = $sliderWrap.find('.ab-slider-prev');
			var $next = $sliderWrap.find('.ab-slider-next');
			if (showArrows && $prev.length && $next.length) {
				$prev.show();
				$next.show();
				swiperOptions.navigation = {
					prevEl: $prev[0],
					nextEl: $next[0],
				};
			} else {
				$prev.hide();
				$next.hide();
			}

			var $pagination = $sliderWrap.find('.ab-slider-pagination');
			if (showDots && $pagination.length) {
				$pagination.show();
				swiperOptions.pagination = {
					el: $pagination[0],
					type: 'bullets',
					clickable: true,
				};
			} else {
				$pagination.hide();
			}

			function onInstReady(instance) {
				if (!instance) {
					return;
				}
				$slider.data('ab-swiper', instance);
				setTimeout(function () {
					if (instance && typeof instance.update === 'function') {
						instance.update();
					}
				}, 100);
			}

			function createSwiper() {
				// First try window.Swiper
				if (typeof window.Swiper !== 'undefined') {
					try {
						var instance = new window.Swiper($slider[0], swiperOptions);
						onInstReady(instance);
						return;
					} catch (e) {
						console.warn('Direct Swiper init error in blog-grid:', e);
					}
				}

				// Fallback: Elementor utils.swiper
				if (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function') {
					try {
						var res = new elementorFrontend.utils.swiper($slider[0], swiperOptions);
						if (res && typeof res.then === 'function') {
							res.then(onInstReady).catch(function () {
								if (typeof window.Swiper !== 'undefined') {
									var fallbackInst = new window.Swiper($slider[0], swiperOptions);
									onInstReady(fallbackInst);
								}
							});
						} else if (res) {
							onInstReady(res);
						}
					} catch (err) {
						if (typeof window.Swiper !== 'undefined') {
							var fallbackInst = new window.Swiper($slider[0], swiperOptions);
							onInstReady(fallbackInst);
						}
					}
				}
			}

			if (typeof window.Swiper !== 'undefined' || (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function')) {
				createSwiper();
			} else {
				var retries = 0;
				var timer = setInterval(function () {
					retries++;
					if (typeof window.Swiper !== 'undefined' || (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function')) {
						clearInterval(timer);
						createSwiper();
					} else if (retries >= 50) {
						clearInterval(timer);
					}
				}, 100);
			}
		}

		// Run slider initialization
		initSlider();

		var currentXHR = null;

		// --- Category Filter Click (Event Delegation) ---
		$wrapper.off('click.abFilter', '.ab-filter-btn').on('click.abFilter', '.ab-filter-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);

			if ($btn.hasClass('active')) {
				return;
			}

			$wrapper.find('.ab-filter-btn').removeClass('active');
			$btn.addClass('active');

			var catAttr = $btn.attr('data-cat-id');
			activeCatId = (typeof catAttr !== 'undefined' && catAttr !== false) ? (parseInt(catAttr, 10) || 0) : (parseInt($btn.data('cat-id'), 10) || 0);
			currentPage = 1;

			fetchPosts(false);
		});

		// --- Load More Click ---
		$wrapper.off('click.abLoadMore', '.ab-load-more-btn').on('click.abLoadMore', '.ab-load-more-btn', function (e) {
			e.preventDefault();
			currentPage++;
			fetchPosts(true);
		});

		function fetchPosts(isAppend) {
			if (!rawSettings) {
				return;
			}

			// Abort any ongoing request before starting a new one
			if (currentXHR && typeof currentXHR.abort === 'function') {
				currentXHR.abort();
				currentXHR = null;
			}

			$loader.fadeIn(200);
			if (!isAppend) {
				if ($grid.length) {
					$grid.css('opacity', '0.4');
				}
				if ($sliderWrap.length) {
					$sliderWrap.css('opacity', '0.4');
				}
			}

			var ajaxUrl = (typeof ab_addon_ajax !== 'undefined' && ab_addon_ajax.ajax_url) ? ab_addon_ajax.ajax_url : '/wp-admin/admin-ajax.php';
			var nonce   = (typeof ab_addon_ajax !== 'undefined' && ab_addon_ajax.nonce) ? ab_addon_ajax.nonce : '';

			var data = {
				action: 'ab_blog_grid_filter',
				nonce: nonce,
				cat_id: activeCatId,
				paged: currentPage,
				post_type: rawSettings.post_type || 'post',
				ppp: rawSettings.posts_per_page || 6,
				orderby: rawSettings.orderby || 'date',
				order: rawSettings.order || 'DESC',
				offset: rawSettings.offset || 0,
				card_style: rawSettings.card_style || 'modern',
				layout_type: rawSettings.layout_type || 'grid',
				settings: rawSettings,
			};

			currentXHR = $.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					currentXHR = null;
					$loader.fadeOut(200);
					if ($grid.length) {
						$grid.css('opacity', '1');
					}
					if ($sliderWrap.length) {
						$sliderWrap.css('opacity', '1');
					}

					if (response && response.success) {
						var htmlContent = (response.data && typeof response.data.html === 'string' && response.data.html.trim() !== '') 
							? response.data.html 
							: '<div class="ab-grid-no-posts"><p>No posts found in this category.</p></div>';

						if ($sliderWrap.length) {
							var $swiperWrapper = $sliderWrap.find('.swiper-wrapper');
							$swiperWrapper.html(htmlContent);
							initSlider();
						} else if ($grid.length) {
							if (isAppend) {
								$grid.append(htmlContent);
							} else {
								$grid.html(htmlContent);
							}
						}

						// Trigger window resize for layout refresh
						$(window).trigger('resize');

						// Update Load More Button state
						var $currentLoadMore = $wrapper.find('.ab-load-more-btn');
						if ($currentLoadMore.length && response.data) {
							if (currentPage >= response.data.max_pages) {
								$currentLoadMore.parent().hide();
							} else {
								$currentLoadMore.parent().show();
								$currentLoadMore.data('paged', currentPage);
							}
						}
					}
				},
				error: function (xhr, status) {
					if (status === 'abort') {
						return; // Ignore aborted requests
					}
					currentXHR = null;
					$loader.fadeOut(200);
					if ($grid.length) {
						$grid.css('opacity', '1');
					}
					if ($sliderWrap.length) {
						$sliderWrap.css('opacity', '1');
					}
				},
			});
		}
	};

	function hookElementor() {
		if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/ab_blog_grid.default', ABBlogGrid);
			elementorFrontend.hooks.addAction('frontend/element_ready/ab_basic_posts.default', ABBlogGrid);
		}
	}

	if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
		hookElementor();
	} else {
		$(window).on('elementor/frontend/init', hookElementor);
	}

	// Fallback for non-elementor pages or standard jQuery ready
	$(document).ready(function () {
		$('.ab-blog-grid-wrapper').each(function () {
			ABBlogGrid($(this));
		});
	});

	$(window).on('load', function () {
		$('.ab-blog-slider').each(function () {
			var inst = $(this).data('ab-swiper');
			if (inst && typeof inst.update === 'function') {
				inst.update();
			}
		});
	});

})(jQuery);
