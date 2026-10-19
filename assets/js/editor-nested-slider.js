/**
 * AB Nested Slider Editor Bridge
 *
 * Connects AB Sliding Container with Elementor's Backbone/Marionette NestedView
 * runtime so that live container drop-zones work seamlessly in the visual editor.
 *
 * @package AB_Addon
 */

(function ($) {
	'use strict';

	function registerABNestedSlider() {
		if (typeof window.elementor === 'undefined' || typeof window.$e === 'undefined') {
			return false;
		}

		try {
			// Avoid double registration
			if (elementor.elementsManager && typeof elementor.elementsManager.getElementType === 'function' && elementor.elementsManager.getElementType('ab_nested_slider')) {
				return true;
			}

			var nestedComponent = $e.components ? $e.components.get('nested-elements') : null;
			if (!nestedComponent || !nestedComponent.exports || !nestedComponent.exports.NestedView) {
				return false;
			}

			var NestedElementBase = elementor.modules && elementor.modules.elements && elementor.modules.elements.types
				? elementor.modules.elements.types.NestedElementBase
				: null;

			if (!NestedElementBase) {
				return false;
			}

			var NestedView = nestedComponent.exports.NestedView;

			// Define Marionette child view (supports both Backbone .extend and ES6 classes)
			var ABNestedSliderView;
			if (typeof NestedView.extend === 'function') {
				ABNestedSliderView = NestedView.extend({
					filter: function (child, index) {
						child.attributes.dataIndex = index + 1;
						return true;
					},
					onAddChild: function (childView) {
						if (childView && childView.$el) {
							childView.$el.addClass('swiper-slide ab-hero-slide ab-nested-slide');
						}
					}
				});
			} else {
				class CustomNestedView extends NestedView {
					filter(child, index) {
						child.attributes.dataIndex = index + 1;
						return true;
					}
					onAddChild(childView) {
						if (childView && childView.$el) {
							childView.$el.addClass('swiper-slide ab-hero-slide ab-nested-slide');
						}
					}
				}
				ABNestedSliderView = CustomNestedView;
			}

			// Define element type descriptor matching get_name() = 'ab_nested_slider'
			if (typeof NestedElementBase.extend === 'function') {
				var ABNestedSliderElementType = NestedElementBase.extend({
					getType: function () {
						return 'ab_nested_slider';
					},
					getView: function () {
						return ABNestedSliderView;
					}
				});
				elementor.elementsManager.registerElementType(new ABNestedSliderElementType());
			} else {
				class CustomNestedElementType extends NestedElementBase {
					getType() {
						return 'ab_nested_slider';
					}
					getView() {
						return ABNestedSliderView;
					}
				}
				elementor.elementsManager.registerElementType(new CustomNestedElementType());
			}

			return true;
		} catch (e) {
			console.warn('[AB Addon] Failed to register ab_nested_slider editor element type:', e);
			return false;
		}
	}

	// 1. Try immediately if elementor runtime is ready
	if (!registerABNestedSlider()) {

		// 2. Listen to Elementor nested load event
		if (window.elementorCommon && window.elementorCommon.elements && window.elementorCommon.elements.$window) {
			elementorCommon.elements.$window.on('elementor/nested-element-type-loaded', function () {
				registerABNestedSlider();
			});
		}

		// 3. Listen on window elementor events
		$(window).on('elementor:init elementor/frontend/init', function () {
			registerABNestedSlider();
		});

		// 4. Polling safeguard during editor boot
		var attempts = 0;
		var timer = setInterval(function () {
			attempts++;
			if (registerABNestedSlider() || attempts > 50) {
				clearInterval(timer);
			}
		}, 100);
	}

})(jQuery);
