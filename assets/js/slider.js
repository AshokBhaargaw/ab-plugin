/**
 * AB Hero Slider Script
 *
 * Front-end Swiper initialization for AB Hero Slider & Nested Sliding Container.
 * Handles responsive options, autoplay, pause-on-hover, fade/slide effects,
 * mobile responsive backgrounds, and Elementor frontend editor live preview compatibility.
 *
 * @package AB_Addon
 */

(function ($) {
	'use strict';

	/**
	 * Initialize a single AB Slider container.
	 *
	 * @param {jQuery} $wrapper
	 */
	function initABSlider($wrapper) {
		if (!$wrapper || !$wrapper.length) {
			return;
		}

		var $wrap = $wrapper.hasClass('ab-hero-slider-wrap') ? $wrapper : $wrapper.find('.ab-hero-slider-wrap');
		if (!$wrap.length) {
			$wrap = $wrapper.closest('.ab-hero-slider-wrap');
		}
		if (!$wrap.length) {
			return;
		}

		var $slider = $wrap.find('.ab-hero-slider');
		if (!$slider.length) {
			return;
		}

		// Handle responsive mobile background images if defined
		function checkMobileBgs() {
			var isMobile = $(window).width() <= 767;
			$slider.find('.ab-slide-bg').each(function () {
				var $bg = $(this);
				var mobUrl = $bg.data('mob-bg');
				if (mobUrl) {
					if (isMobile) {
						$bg.css('background-image', 'url(' + mobUrl + ')');
					} else {
						var deskUrl = $bg.data('desk-bg');
						if (deskUrl) {
							$bg.css('background-image', 'url(' + deskUrl + ')');
						} else {
							var origStyle = $bg.attr('style') || '';
							if (!origStyle.match(/url\(/)) {
								$bg.css('background-image', '');
							}
						}
					}
				}
			});
		}

		// Cache desktop background image for reliable desktop/mobile switching
		$slider.find('.ab-slide-bg').each(function () {
			var $bg = $(this);
			if (!$bg.data('desk-bg')) {
				var bgImg = $bg.css('background-image');
				if (bgImg && bgImg !== 'none') {
					var match = bgImg.match(/^url\(["']?(.+?)["']?\)$/);
					if (match && match[1]) {
						$bg.data('desk-bg', match[1]);
					}
				}
			}
		});

		checkMobileBgs();

		// Cleanup any existing Swiper instance on this container
		var oldInstance = $slider.data('ab-swiper');
		if (oldInstance && typeof oldInstance.destroy === 'function') {
			try {
				oldInstance.destroy(true, true);
			} catch (err) {}
		}
		if ($slider[0] && $slider[0].swiper && typeof $slider[0].swiper.destroy === 'function') {
			try {
				$slider[0].swiper.destroy(true, true);
			} catch (err) {}
		}

		var slideCount = $slider.find('.swiper-slide').length;
		if (slideCount === 0) {
			return;
		}

		var rawConfig = $wrap.data('slider-config');
		var config = {};
		if (typeof rawConfig === 'object' && rawConfig !== null) {
			config = rawConfig;
		} else if (typeof rawConfig === 'string') {
			try {
				config = JSON.parse(rawConfig);
			} catch (e) {
				config = {};
			}
		}

		var isEditMode = (typeof elementorFrontend !== 'undefined' && typeof elementorFrontend.isEditMode === 'function' && elementorFrontend.isEditMode()) || $('body').hasClass('elementor-editor-active');

		var slidesPerView       = parseInt(config.slidesPerView, 10) || 1;
		var slidesPerViewTablet = parseInt(config.slidesPerViewTablet, 10) || (slidesPerView > 2 ? 2 : 1);
		var slidesPerViewMobile = parseInt(config.slidesPerViewMobile, 10) || 1;
		var spaceBetween        = typeof config.spaceBetween !== 'undefined' ? parseInt(config.spaceBetween, 10) : 0;

		var slidesToScroll       = parseInt(config.slidesToScroll, 10) || 1;
		var slidesToScrollTablet = parseInt(config.slidesToScrollTablet, 10) || 1;
		var slidesToScrollMobile = parseInt(config.slidesToScrollMobile, 10) || 1;

		var centeredSlides     = !!config.centeredSlides;
		var pauseOnInteraction = !!config.pauseOnInteraction;

		var effect = config.effect || 'fade';
		if (slidesPerView > 1 || slidesPerViewTablet > 1 || slidesPerViewMobile > 1) {
			effect = 'slide';
		}
		var speed        = parseInt(config.speed, 10) || 800;
		var isLoop       = (config.loop === true || config.loop === 'yes') && slideCount > 1;
		var isAutoplay   = (config.autoplay === true || config.autoplay === 'yes') && slideCount > 1;
		var autoplayDelay= parseInt(config.delay || config.autoplay_delay, 10) || 5000;
		var pauseOnHover = config.pauseOnHover === true || config.pauseOnHover === 'yes' || (config.pauseOnHover !== false && config.pause_on_hover !== 'no');

		var showArrows = config.arrows !== false && config.arrows !== 'no' && config.navigation !== 'dots' && config.navigation !== 'none' && slideCount > 1;
		var showDots   = config.dots !== false && config.dots !== 'no' && config.navigation !== 'arrows' && config.navigation !== 'none' && slideCount > 1;

		var $prevBtn = $wrap.find('.ab-slider-prev');
		var $nextBtn = $wrap.find('.ab-slider-next');
		var $pagination = $wrap.find('.ab-slider-pagination');

		var swiperOptions = {
			effect: effect,
			speed: speed,
			loop: isLoop,
			grabCursor: true,
			centeredSlides: centeredSlides,
			spaceBetween: spaceBetween,
			observer: true,
			observeParents: true,
			watchSlidesProgress: true,
			keyboard: {
				enabled: true,
				onlyInViewport: true,
			},
		};

		if (effect === 'fade') {
			swiperOptions.slidesPerView = 1;
			swiperOptions.slidesPerGroup = 1;
			swiperOptions.fadeEffect = {
				crossFade: true,
			};
		} else {
			swiperOptions.slidesPerView = slidesPerViewMobile;
			swiperOptions.slidesPerGroup = slidesToScrollMobile;
			swiperOptions.breakpoints = {
				768: {
					slidesPerView: slidesPerViewTablet,
					slidesPerGroup: slidesToScrollTablet,
					spaceBetween: spaceBetween,
				},
				1025: {
					slidesPerView: slidesPerView,
					slidesPerGroup: slidesToScroll,
					spaceBetween: spaceBetween,
				},
			};
		}

		if (isAutoplay && !isEditMode) {
			swiperOptions.autoplay = {
				delay: autoplayDelay,
				disableOnInteraction: pauseOnInteraction,
				pauseOnMouseEnter: pauseOnHover,
			};
		} else {
			swiperOptions.autoplay = false;
		}

		if (showArrows && $prevBtn.length && $nextBtn.length) {
			$prevBtn.show();
			$nextBtn.show();
			swiperOptions.navigation = {
				prevEl: $prevBtn[0],
				nextEl: $nextBtn[0],
			};
		} else {
			$prevBtn.hide();
			$nextBtn.hide();
		}

		if (showDots && $pagination.length) {
			$pagination.show();
			swiperOptions.pagination = {
				el: $pagination[0],
				clickable: true,
			};
		} else {
			$pagination.hide();
		}

		/**
		 * Callback when Swiper instance is created.
		 */
		function onInstReady(inst) {
			if (!inst) {
				return;
			}
			$slider.data('ab-swiper', inst);

			// Trigger update after layout settles
			setTimeout(function () {
				if (inst && typeof inst.update === 'function') {
					inst.update();
				}
			}, 100);

			if (isEditMode) {
				// In editor mode, allow clicking on slides or navigation to switch active slide
				$slider.off('click.abNestedNav').on('click.abNestedNav', '.swiper-slide', function (e) {
					var slideIdx = $(this).data('slide-index');
					if (typeof slideIdx !== 'undefined' && inst && typeof inst.slideTo === 'function') {
						var targetIdx = parseInt(slideIdx, 10);
						if (inst.activeIndex !== targetIdx) {
							inst.slideTo(targetIdx);
						}
					}
				});
			}
		}

		/**
		 * Instantiate Swiper using global window.Swiper or Elementor utils wrapper.
		 */
		function startSwiper() {
			// First try window.Swiper (direct synchronous initialization)
			if (typeof window.Swiper !== 'undefined') {
				try {
					var inst = new window.Swiper($slider[0], swiperOptions);
					onInstReady(inst);
					return;
				} catch (err) {
					console.warn('Direct window.Swiper init error:', err);
				}
			}

			// Fallback: Elementor utils.swiper
			if (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function') {
				try {
					var res = new elementorFrontend.utils.swiper($slider[0], swiperOptions);
					if (res && typeof res.then === 'function') {
						res.then(onInstReady).catch(function (err) {
							console.error('Elementor swiper init error:', err);
						});
					} else if (res) {
						onInstReady(res);
					}
				} catch (e) {
					console.error('Elementor swiper exception:', e);
				}
			}
		}

		if (typeof window.Swiper !== 'undefined' || (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function')) {
			startSwiper();
		} else {
			var attempts = 0;
			var poll = setInterval(function () {
				attempts++;
				if (typeof window.Swiper !== 'undefined' || (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && typeof elementorFrontend.utils.swiper === 'function')) {
					clearInterval(poll);
					startSwiper();
				}
				if (attempts > 50) {
					clearInterval(poll);
				}
			}, 100);
		}
	}

	// Elementor widget hooks
	function hookElementor() {
		if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/ab_slider.default', function ($scope) {
				initABSlider($scope);
			});

			elementorFrontend.hooks.addAction('frontend/element_ready/ab_nested_slider.default', function ($scope) {
				initABSlider($scope);
			});
		}
	}

	if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
		hookElementor();
	} else {
		$(window).on('elementor/frontend/init', hookElementor);
	}

	// Standard document ready & window load for shortcodes and direct pages
	$(document).ready(function () {
		$('.ab-hero-slider-wrap').each(function () {
			initABSlider($(this));
		});
	});

	$(window).on('load', function () {
		$('.ab-hero-slider-wrap').each(function () {
			var inst = $(this).find('.ab-hero-slider').data('ab-swiper');
			if (inst && typeof inst.update === 'function') {
				inst.update();
			}
		});
	});

	$(window).on('resize', function () {
		clearTimeout(window.__abSliderResizeTimeout);
		window.__abSliderResizeTimeout = setTimeout(function () {
			$('.ab-hero-slider-wrap').each(function () {
				var $wrap = $(this);
				var isMobile = $(window).width() <= 767;
				$wrap.find('.ab-slide-bg').each(function () {
					var $bg = $(this);
					var mobUrl = $bg.data('mob-bg');
					if (mobUrl) {
						if (isMobile) {
							$bg.css('background-image', 'url(' + mobUrl + ')');
						} else {
							var deskUrl = $bg.data('desk-bg');
							if (deskUrl) {
								$bg.css('background-image', 'url(' + deskUrl + ')');
							}
						}
					}
				});
				var inst = $wrap.find('.ab-hero-slider').data('ab-swiper');
				if (inst && typeof inst.update === 'function') {
					inst.update();
				}
			});
		}, 150);
	});

})(jQuery);
