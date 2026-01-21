/**
 * AI Sales Manager - Frontend Shortcodes JavaScript
 *
 * Handles interactive functionality for shortcode widgets.
 */

(function($) {
	'use strict';

	/**
	 * AISales Frontend Widgets
	 */
	var AISalesWidgets = {
		
		/**
		 * Initialize
		 */
		init: function() {
			this.initCountdowns();
			this.initCartUrgency();
			this.initRecentPurchases();
			this.initLiveViewers();
			this.initAnimatedCounts();
		},

		/**
		 * Initialize live viewers widget
		 */
		initLiveViewers: function() {
			$('.aisales-live-viewers').each(function() {
				var $widget = $(this);
				var min = parseInt($widget.data('min'), 10) || 1;
				var max = parseInt($widget.data('max'), 10) || 25;
				var interval = parseInt($widget.data('interval'), 10) || 12;
				var $text = $widget.find('.aisales-live-viewers__text');
				var template = $widget.data('format') || $text.text();

				if ($text.length === 0) {
					return;
				}

				setInterval(function() {
					var count = Math.floor(Math.random() * (max - min + 1)) + min;
					var text = template.replace('{count}', count);
					$text.text(text);
				}, interval * 1000);
			});
		},

		/**
		 * Initialize recent purchase popups
		 */
		initRecentPurchases: function() {
			$('.aisales-recent-purchase').each(function() {
				var $widget = $(this);
				var $items = $widget.find('.aisales-recent-purchase__item');
				var interval = parseInt($widget.data('interval'), 10) || 8;
				var duration = parseInt($widget.data('duration'), 10) || 5;

				if ($items.length === 0) {
					return;
				}

				var index = 0;
				var cycleTime = Math.max(interval, duration + 1);

				$items.hide().eq(0).addClass('is-active').show();

				var cycle = function() {
					var $current = $items.eq(index);
					$current.addClass('is-active').fadeIn(200);

					setTimeout(function() {
						$current.removeClass('is-active').fadeOut(200, function() {
							index = (index + 1) % $items.length;
						});
					}, duration * 1000);

					setTimeout(cycle, cycleTime * 1000);
				};

				setTimeout(cycle, cycleTime * 1000);
			});
		},

		/**
		 * Initialize cart urgency timers
		 */
		initCartUrgency: function() {
			var self = this;

			$('.aisales-cart-urgency__timer').each(function() {
				self.startCountdown($(this), 'inline');
			});
		},

		/**
		 * Initialize all countdown timers
		 */
		initCountdowns: function() {
			var self = this;

			// Inline style countdowns
			$('.aisales-countdown__timer').each(function() {
				self.startCountdown($(this), 'inline');
			});

			// Box style countdowns
			$('.aisales-countdown__boxes').each(function() {
				self.startCountdown($(this), 'boxes');
			});
		},

		/**
		 * Start a countdown timer
		 * 
		 * @param {jQuery} $el Timer element
		 * @param {string} style 'inline' or 'boxes'
		 */
		startCountdown: function($el, style) {
			var self = this;
			var endDate = new Date($el.data('end')).getTime();

			// Get display options
			var showDays = $el.data('show-days') !== 0 && $el.data('show-days') !== undefined;
			var showHours = $el.data('show-hours') !== 0 && $el.data('show-hours') !== undefined;
			var showMinutes = $el.data('show-minutes') !== 0 && $el.data('show-minutes') !== undefined;
			var showSeconds = $el.data('show-seconds') !== 0 && $el.data('show-seconds') !== undefined;

			// Update immediately
			self.updateCountdown($el, endDate, style, showDays, showHours, showMinutes, showSeconds);

			// Then update every second
			var interval = setInterval(function() {
				var remaining = self.updateCountdown($el, endDate, style, showDays, showHours, showMinutes, showSeconds);
				
				if (remaining <= 0) {
					clearInterval(interval);
					self.handleCountdownEnd($el);
				}
			}, 1000);

			// Store interval for cleanup
			$el.data('countdown-interval', interval);
		},

		/**
		 * Update countdown display
		 * 
		 * @param {jQuery} $el Timer element
		 * @param {number} endDate End timestamp
		 * @param {string} style Display style
		 * @param {boolean} showDays Show days
		 * @param {boolean} showHours Show hours
		 * @param {boolean} showMinutes Show minutes
		 * @param {boolean} showSeconds Show seconds
		 * @return {number} Remaining milliseconds
		 */
		updateCountdown: function($el, endDate, style, showDays, showHours, showMinutes, showSeconds) {
			var now = new Date().getTime();
			var remaining = endDate - now;

			if (remaining <= 0) {
				remaining = 0;
			}

			// Calculate time units
			var days = Math.floor(remaining / (1000 * 60 * 60 * 24));
			var hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
			var minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
			var seconds = Math.floor((remaining % (1000 * 60)) / 1000);

			// If not showing days, add days to hours
			if (!showDays) {
				hours += days * 24;
				days = 0;
			}

			// If not showing hours, add hours to minutes
			if (!showHours) {
				minutes += hours * 60;
				hours = 0;
			}

			if (style === 'boxes') {
				// Update box-style countdown
				$el.find('[data-unit="days"]').text(this.padZero(days));
				$el.find('[data-unit="hours"]').text(this.padZero(hours));
				$el.find('[data-unit="minutes"]').text(this.padZero(minutes));
				$el.find('[data-unit="seconds"]').text(this.padZero(seconds));
			} else {
				// Update inline-style countdown
				var parts = [];
				
				if (showDays && days > 0) {
					parts.push(days + 'd');
				}
				if (showHours) {
					parts.push(this.padZero(hours));
				}
				if (showMinutes) {
					parts.push(this.padZero(minutes));
				}
				if (showSeconds) {
					parts.push(this.padZero(seconds));
				}

				// Join with colons, but days gets special treatment
				var timerText;
				if (showDays && days > 0) {
					timerText = days + 'd ' + parts.slice(1).join(':');
				} else {
					timerText = parts.join(':');
				}

				$el.text(timerText);
			}

			return remaining;
		},

		/**
		 * Handle countdown end
		 * 
		 * @param {jQuery} $el Timer element
		 */
		handleCountdownEnd: function($el) {
			var $widget = $el.closest('.aisales-countdown');
			
			// Add ended class
			$widget.addClass('aisales-countdown--ended');

			// Optionally hide the widget (default behavior)
			// The PHP already handles this for page loads, but this handles
			// countdowns that end while the user is on the page
			setTimeout(function() {
				$widget.fadeOut(300);
			}, 1000);
		},

		/**
		 * Pad number with leading zero
		 * 
		 * @param {number} num Number to pad
		 * @return {string} Padded string
		 */
		padZero: function(num) {
			return num < 10 ? '0' + num : String(num);
		},

		/**
		 * Initialize animated count-up for total sold widgets
		 */
		initAnimatedCounts: function() {
			$('.aisales-total-sold__text[data-animate="true"]').each(function() {
				var $el = $(this);
				var targetCount = parseInt($el.data('count'), 10);
				var text = $el.text();
				
				if (!targetCount || targetCount <= 0) {
					return;
				}

				// Extract the format by replacing the number
				var formattedTarget = targetCount.toLocaleString();
				
				// Animate from 0 to target
				var duration = 1500; // 1.5 seconds
				var startTime = null;
				var startCount = 0;

				function animate(currentTime) {
					if (!startTime) startTime = currentTime;
					var elapsed = currentTime - startTime;
					var progress = Math.min(elapsed / duration, 1);
					
					// Easing function (ease-out)
					progress = 1 - Math.pow(1 - progress, 3);
					
					var currentCount = Math.floor(startCount + (targetCount - startCount) * progress);
					var displayText = text.replace(formattedTarget, currentCount.toLocaleString());
					$el.text(displayText);
					
					if (progress < 1) {
						requestAnimationFrame(animate);
					} else {
						// Ensure final value is exact
						$el.text(text);
					}
				}

				// Use Intersection Observer to trigger animation when visible
				if ('IntersectionObserver' in window) {
					var observer = new IntersectionObserver(function(entries) {
						entries.forEach(function(entry) {
							if (entry.isIntersecting) {
								requestAnimationFrame(animate);
								observer.unobserve(entry.target);
							}
						});
					}, { threshold: 0.5 });
					
					observer.observe($el[0]);
				} else {
					// Fallback for older browsers
					requestAnimationFrame(animate);
				}
			});
		}
	};

	// Initialize on DOM ready
	$(document).ready(function() {
		AISalesWidgets.init();
	});

})(jQuery);
