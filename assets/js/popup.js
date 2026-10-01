/**
 * AB Popup Widget — Frontend Script
 *
 * Handles:
 *  - Page-load trigger (with delay)
 *  - Click-based trigger (CSS selector)
 *  - Scroll percentage trigger
 *  - Exit-intent trigger (mouse leave viewport)
 *  - Session-once guard (sessionStorage)
 *  - Close via button, overlay click, or Escape key
 *  - Body scroll lock for modal types
 */
(function ($) {
	'use strict';

	var SESSION_KEY_PREFIX = 'ab_popup_seen_';

	/**
	 * Opens a popup element with the CSS animation class.
	 *
	 * @param {jQuery} $popup  The .ab-popup-overlay or .ab-popup-notification element.
	 * @param {string} popupId Unique popup ID (for session storage).
	 * @param {string} type    'modal', 'exit_intent', or 'notification'.
	 * @param {string} showOnce 'yes' or 'no'.
	 */
	function openPopup( $popup, popupId, type, showOnce ) {
		if ( showOnce === 'yes' && sessionStorage.getItem( SESSION_KEY_PREFIX + popupId ) ) {
			return;
		}

		$popup.css( 'display', 'flex' );

		// Force reflow so transitions run
		void $popup[0].offsetWidth;

		$popup.addClass( 'ab-popup--visible' );
		$popup.attr( 'aria-hidden', 'false' );

		if ( type === 'modal' || type === 'exit_intent' ) {
			$( 'body' ).addClass( 'ab-popup-open' );
		}

		if ( showOnce === 'yes' ) {
			sessionStorage.setItem( SESSION_KEY_PREFIX + popupId, '1' );
		}

		// Trap focus inside modal
		if ( type === 'modal' || type === 'exit_intent' ) {
			trapFocus( $popup );
		}
	}

	/**
	 * Closes a popup element.
	 *
	 * @param {jQuery} $popup The popup element.
	 * @param {string} type   'modal', 'exit_intent', or 'notification'.
	 */
	function closePopup( $popup, type ) {
		$popup.removeClass( 'ab-popup--visible' );
		$popup.attr( 'aria-hidden', 'true' );

		if ( type === 'modal' || type === 'exit_intent' ) {
			$( 'body' ).removeClass( 'ab-popup-open' );
		}

		// Hide after transition ends
		$popup.one( 'transitionend', function () {
			if ( ! $popup.hasClass( 'ab-popup--visible' ) ) {
				$popup.css( 'display', 'none' );
			}
		} );

		// Fallback in case transitionend doesn't fire
		setTimeout( function () {
			if ( ! $popup.hasClass( 'ab-popup--visible' ) ) {
				$popup.css( 'display', 'none' );
			}
		}, 400 );
	}

	/**
	 * Trap keyboard focus inside a modal popup.
	 *
	 * @param {jQuery} $popup
	 */
	function trapFocus( $popup ) {
		var focusable = $popup.find(
			'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
		).filter( ':visible' );

		if ( focusable.length ) {
			focusable.first().trigger( 'focus' );
		}
	}

	/**
	 * Initialises a single popup wrapper element.
	 *
	 * @param {HTMLElement} wrapper The .ab-popup-trigger-wrapper element.
	 */
	function initPopup( wrapper ) {
		var $wrapper     = $( wrapper );
		var popupId      = $wrapper.data( 'popup-id' );
		var popupType    = $wrapper.data( 'popup-type' );
		var trigger      = $wrapper.data( 'trigger' );
		var delay        = parseFloat( $wrapper.data( 'delay' ) ) || 0;
		var selector     = $wrapper.data( 'selector' ) || '';
		var scrollPct    = parseFloat( $wrapper.data( 'scroll' ) ) || 50;
		var showOnce     = $wrapper.data( 'show-once' ) || 'yes';
		var closeOverlay = $wrapper.data( 'close-overlay' ) || 'yes';

		var $popup = $( '#' + popupId );

		if ( ! $popup.length ) {
			return;
		}

		// ---- Close handlers ----

		// Close button
		$popup.on( 'click', '.ab-popup-close', function () {
			closePopup( $popup, popupType );
		} );

		// Overlay click
		if ( ( popupType === 'modal' || popupType === 'exit_intent' ) && closeOverlay === 'yes' ) {
			$popup.on( 'click', function ( e ) {
				if ( $( e.target ).is( '.ab-popup-overlay' ) ) {
					closePopup( $popup, popupType );
				}
			} );
		}

		// Escape key
		$( document ).on( 'keydown.ab-popup-' + popupId, function ( e ) {
			if ( e.key === 'Escape' && $popup.hasClass( 'ab-popup--visible' ) ) {
				closePopup( $popup, popupType );
			}
		} );

		// ---- Trigger handlers ----

		if ( trigger === 'exit_intent' ) {
			// Exit-intent: listen for mouse leaving the top of the viewport
			var exitFired = false;
			$( document ).on( 'mouseleave.ab-exit-' + popupId, function ( e ) {
				if ( exitFired || e.clientY > 10 ) {
					return;
				}
				exitFired = true;
				// Remove listener so it only fires once per page load
				$( document ).off( 'mouseleave.ab-exit-' + popupId );

				if ( delay > 0 ) {
					setTimeout( function () {
						openPopup( $popup, popupId, popupType, showOnce );
					}, delay * 1000 );
				} else {
					openPopup( $popup, popupId, popupType, showOnce );
				}
			} );

		} else if ( trigger === 'page_load' ) {
			setTimeout( function () {
				openPopup( $popup, popupId, popupType, showOnce );
			}, delay * 1000 );

		} else if ( trigger === 'click' ) {
			if ( selector ) {
				$( document ).on( 'click', selector, function ( e ) {
					e.preventDefault();
					openPopup( $popup, popupId, popupType, showOnce );
				} );
			}

		} else if ( trigger === 'scroll' ) {
			var scrollFired = false;
			$( window ).on( 'scroll.ab-scroll-' + popupId, function () {
				if ( scrollFired ) {
					return;
				}
				var scrolled  = $( window ).scrollTop();
				var docHeight = $( document ).height() - $( window ).height();
				var pct       = docHeight > 0 ? ( scrolled / docHeight ) * 100 : 0;

				if ( pct >= scrollPct ) {
					scrollFired = true;
					$( window ).off( 'scroll.ab-scroll-' + popupId );
					openPopup( $popup, popupId, popupType, showOnce );
				}
			} );
		}
	}

	// ---- Bootstrap ----
	$( document ).ready( function () {
		$( '.ab-popup-trigger-wrapper' ).each( function () {
			initPopup( this );
		} );
	} );

	// Elementor editor live preview
	if ( window.elementorFrontend ) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/ab_popup.default',
			function ( $scope ) {
				$scope.find( '.ab-popup-trigger-wrapper' ).each( function () {
					initPopup( this );
				} );
			}
		);
	}

}( jQuery ));
