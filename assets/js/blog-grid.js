(function ($) {
	'use strict';

	var ABBlogGrid = function ($scope) {
		var $wrapper    = $scope.find('.ab-blog-grid-wrapper');
		if (!$wrapper.length) {
			return;
		}

		var $grid       = $wrapper.find('.ab-blog-grid');
		var $sliderWrap = $wrapper.find('.ab-blog-slider-wrap');
		var $loader     = $wrapper.find('.ab-grid-loader');
		var $filterBtns = $wrapper.find('.ab-filter-btn');
		var $loadMoreBtn= $wrapper.find('.ab-load-more-btn');
		var rawSettings = $wrapper.data('settings');

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

			var config = $sliderWrap.data('slider-config') || {};

			var slidesPerView       = parseInt(config.slidesPerView, 10) || 3;
			var slidesPerViewTablet = parseInt(config.slidesPerViewTablet, 10) || (slidesPerView > 2 ? 2 : slidesPerView);
			var slidesPerViewMobile = parseInt(config.slidesPerViewMobile, 10) || 1;

			var spaceBetween        = typeof config.spaceBetween !== 'undefined' ? parseInt(config.spaceBetween, 10) : 24;
			var spaceBetweenTablet  = typeof config.spaceBetweenTablet !== 'undefined' ? parseInt(config.spaceBetweenTablet, 10) : Math.min(20, spaceBetween);
			var spaceBetweenMobile  = typeof config.spaceBetweenMobile !== 'undefined' ? parseInt(config.spaceBetweenMobile, 10) : Math.min(16, spaceBetween);

			var slideCount = $slider.find('.swiper-slide').length;
			var isLoop     = !!config.loop && (slideCount > slidesPerView);

			var swiperOptions = {
				slidesPerView: slidesPerViewMobile,
				spaceBetween: spaceBetweenMobile,
				loop: isLoop,
				speed: 500,
				watchOverflow: true,
				observer: true,
				observeParents: true,
				breakpoints: {
					320: {
						slidesPerView: slidesPerViewMobile,
						spaceBetween: spaceBetweenMobile
					},
					768: {
						slidesPerView: slidesPerViewTablet,
						spaceBetween: spaceBetweenTablet
					},
					1025: {
						slidesPerView: slidesPerView,
						spaceBetween: spaceBetween
					}
				}
			};

			if (config.autoplay) {
				swiperOptions.autoplay = {
					delay: parseInt(config.autoplaySpeed, 10) || 3000,
					disableOnInteraction: false,
					pauseOnMouseEnter: true
				};
			} else {
				swiperOptions.autoplay = false;
			}

			if (config.arrows) {
				var $prev = $sliderWrap.find('.ab-slider-prev');
				var $next = $sliderWrap.find('.ab-slider-next');
				if ($prev.length && $next.length) {
					swiperOptions.navigation = {
						prevEl: $prev[0],
						nextEl: $next[0]
					};
				}
			}

			if (config.dots) {
				var $pagination = $sliderWrap.find('.ab-slider-pagination');
				if ($pagination.length) {
					swiperOptions.pagination = {
						el: $pagination[0],
						type: 'bullets',
						clickable: true
					};
				}
			}

			function createSwiper() {
				if (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function') {
					new elementorFrontend.utils.swiper($slider[0], swiperOptions).then(function (instance) {
						$slider.data('ab-swiper', instance);
					}).catch(function () {
						if (typeof window.Swiper !== 'undefined') {
							var instance = new window.Swiper($slider[0], swiperOptions);
							$slider.data('ab-swiper', instance);
						}
					});
				} else if (typeof window.Swiper !== 'undefined') {
					var instance = new window.Swiper($slider[0], swiperOptions);
					$slider.data('ab-swiper', instance);
				}
			}

			if ((typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function') || typeof window.Swiper !== 'undefined') {
				createSwiper();
			} else {
				var retries = 0;
				var timer = setInterval(function () {
					retries++;
					if ((typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function') || typeof window.Swiper !== 'undefined') {
						clearInterval(timer);
						createSwiper();
					} else if (retries >= 30) {
						clearInterval(timer);
					}
				}, 100);
			}
		}

		// Run slider initialization
		initSlider();

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
				if ($grid.length) {
					$grid.css('opacity', '0.4');
				}
				if ($sliderWrap.length) {
					$sliderWrap.css('opacity', '0.4');
				}
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
				layout_type: rawSettings.layout_type || 'grid',
				settings: rawSettings
			};

			$.ajax({
				url: ab_addon_ajax.ajax_url,
				type: 'POST',
				data: data,
				success: function (response) {
					$loader.fadeOut(200);
					if ($grid.length) {
						$grid.css('opacity', '1');
					}
					if ($sliderWrap.length) {
						$sliderWrap.css('opacity', '1');
					}

					if (response.success) {
						if ($sliderWrap.length) {
							var $swiperWrapper = $sliderWrap.find('.swiper-wrapper');
							$swiperWrapper.html(response.data.html);
							initSlider();
						} else if ($grid.length) {
							if (isAppend) {
								$grid.append(response.data.html);
							} else {
								$grid.html(response.data.html);
							}
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
					if ($grid.length) {
						$grid.css('opacity', '1');
					}
					if ($sliderWrap.length) {
						$sliderWrap.css('opacity', '1');
					}
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
