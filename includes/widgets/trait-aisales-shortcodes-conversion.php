<?php
/**
 * Conversion Shortcodes Trait
 *
 * Contains render methods for conversion widgets:
 * - Countdown
 * - Price Drop
 * - Bundle Savings
 * - Cart Urgency
 * - Shipping Bar
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Conversion Shortcodes trait
 */
trait AISales_Shortcodes_Conversion {

	/**
	 * Render [aisales_countdown] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_countdown( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'countdown' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'end_date'   => '', // ISO 8601 format or relative like '+2 days'.
				'style'      => isset( $config['style'] ) ? $config['style'] : 'inline',
			),
			$atts,
			'aisales_countdown'
		);

		$product_id = $this->get_product_id( $atts );
		$end_date   = '';

		// Try to get end date from product sale schedule if on a product page.
		if ( $product_id && empty( $atts['end_date'] ) ) {
			$product = $this->get_product( $product_id );
			if ( $product && $product->is_on_sale() ) {
				$sale_end = $product->get_date_on_sale_to();
				if ( $sale_end ) {
					$end_date = $sale_end->date( 'c' ); // ISO 8601 format.
				}
			}
		}

		// Use explicit end_date attribute if provided.
		if ( ! empty( $atts['end_date'] ) ) {
			$parsed = strtotime( $atts['end_date'] );
			if ( $parsed ) {
				$end_date = gmdate( 'c', $parsed );
			}
		}

		// If no end date found, don't render.
		if ( empty( $end_date ) ) {
			return '';
		}

		// Check if countdown has already ended.
		$end_timestamp = strtotime( $end_date );
		if ( $end_timestamp <= time() ) {
			$end_action = isset( $config['end_action'] ) ? $config['end_action'] : 'hide';
			if ( 'hide' === $end_action ) {
				return '';
			}
			// Otherwise show "Sale ended" message.
		}

		// Check exclusions.
		if ( $product_id ) {
			$exclude_products = isset( $config['exclude_products'] ) ? $config['exclude_products'] : '';
			if ( ! empty( $exclude_products ) ) {
				$excluded_ids = array_map( 'absint', array_filter( explode( ',', $exclude_products ) ) );
				if ( in_array( $product_id, $excluded_ids, true ) ) {
					return '';
				}
			}
		}

		$style       = sanitize_key( $atts['style'] );
		$show_days   = isset( $config['show_days'] ) ? $config['show_days'] : true;
		$show_hours  = isset( $config['show_hours'] ) ? $config['show_hours'] : true;
		$show_mins   = isset( $config['show_minutes'] ) ? $config['show_minutes'] : true;
		$show_secs   = isset( $config['show_seconds'] ) ? $config['show_seconds'] : true;
		$format      = isset( $config['format'] ) ? $config['format'] : 'Sale ends in {timer}';

		// Build timer HTML based on style.
		$content = '';

		if ( 'boxes' === $style ) {
			// Box style with individual units.
			$content .= '<div class="aisales-countdown__boxes" data-end="' . esc_attr( $end_date ) . '">';

			if ( $show_days ) {
				$content .= '<div class="aisales-countdown__unit">';
				$content .= '<span class="aisales-countdown__value" data-unit="days">00</span>';
				$content .= '<span class="aisales-countdown__label">' . esc_html__( 'Days', 'ai-sales-manager-for-woocommerce' ) . '</span>';
				$content .= '</div>';
			}
			if ( $show_hours ) {
				$content .= '<div class="aisales-countdown__unit">';
				$content .= '<span class="aisales-countdown__value" data-unit="hours">00</span>';
				$content .= '<span class="aisales-countdown__label">' . esc_html__( 'Hours', 'ai-sales-manager-for-woocommerce' ) . '</span>';
				$content .= '</div>';
			}
			if ( $show_mins ) {
				$content .= '<div class="aisales-countdown__unit">';
				$content .= '<span class="aisales-countdown__value" data-unit="minutes">00</span>';
				$content .= '<span class="aisales-countdown__label">' . esc_html__( 'Min', 'ai-sales-manager-for-woocommerce' ) . '</span>';
				$content .= '</div>';
			}
			if ( $show_secs ) {
				$content .= '<div class="aisales-countdown__unit">';
				$content .= '<span class="aisales-countdown__value" data-unit="seconds">00</span>';
				$content .= '<span class="aisales-countdown__label">' . esc_html__( 'Sec', 'ai-sales-manager-for-woocommerce' ) . '</span>';
				$content .= '</div>';
			}

			$content .= '</div>';
		} else {
			// Inline style with formatted text.
			$content .= '<span class="aisales-countdown__icon">';
			$content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/><path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';
			$content .= '</span>';

			// Timer placeholder that JS will update.
			$timer_html = '<span class="aisales-countdown__timer" data-end="' . esc_attr( $end_date ) . '" ';
			$timer_html .= 'data-show-days="' . ( $show_days ? '1' : '0' ) . '" ';
			$timer_html .= 'data-show-hours="' . ( $show_hours ? '1' : '0' ) . '" ';
			$timer_html .= 'data-show-minutes="' . ( $show_mins ? '1' : '0' ) . '" ';
			$timer_html .= 'data-show-seconds="' . ( $show_secs ? '1' : '0' ) . '">';
			$timer_html .= '00:00:00'; // Placeholder, JS will update.
			$timer_html .= '</span>';

			// Replace {timer} in format string.
			$text = str_replace( '{timer}', $timer_html, $format );
			$content .= '<span class="aisales-countdown__text">' . wp_kses_post( $text ) . '</span>';
		}

		// Build classes.
		$classes   = array( 'aisales-countdown--' . $style );
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$inline_style = $this->build_margin_style( $config );

		// Apply custom colors.
		if ( ! empty( $config['text_color'] ) ) {
			$inline_style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}
		if ( ! empty( $config['bg_color'] ) ) {
			$inline_style .= 'background-color:' . esc_attr( $config['bg_color'] ) . ';';
		}
		if ( ! empty( $config['accent_color'] ) ) {
			$inline_style .= '--aisales-countdown-accent:' . esc_attr( $config['accent_color'] ) . ';';
		}

		return $this->wrap_output( 'countdown', $content, $classes, $inline_style );
	}

	/**
	 * Render [aisales_price_drop] shortcode
	 *
	 * Displays a sale badge with percentage/amount saved for products on sale.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_price_drop( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'price_drop' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id'      => '',
				'show_percentage' => isset( $config['show_percentage'] ) ? ( $config['show_percentage'] ? 'true' : 'false' ) : 'true',
				'show_amount'     => isset( $config['show_amount'] ) ? ( $config['show_amount'] ? 'true' : 'false' ) : 'false',
				'style'           => isset( $config['style'] ) ? $config['style'] : 'badge',
			),
			$atts,
			'aisales_price_drop'
		);

		$product_id = $this->get_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		// Check exclusions.
		$exclude_products = isset( $config['exclude_products'] ) ? $config['exclude_products'] : '';
		if ( ! empty( $exclude_products ) ) {
			$excluded_ids = array_map( 'absint', array_filter( explode( ',', $exclude_products ) ) );
			if ( in_array( $product_id, $excluded_ids, true ) ) {
				return '';
			}
		}

		$product = $this->get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		// Only show for products on sale.
		if ( ! $product->is_on_sale() ) {
			return '';
		}

		// Get prices.
		$regular_price = (float) $product->get_regular_price();
		$sale_price    = (float) $product->get_sale_price();

		// Handle variable products - get min prices.
		if ( $product->is_type( 'variable' ) ) {
			$regular_price = (float) $product->get_variation_regular_price( 'min' );
			$sale_price    = (float) $product->get_variation_sale_price( 'min' );
		}

		// Validate prices.
		if ( $regular_price <= 0 || $sale_price <= 0 || $sale_price >= $regular_price ) {
			return '';
		}

		// Calculate savings.
		$savings_amount  = $regular_price - $sale_price;
		$savings_percent = round( ( $savings_amount / $regular_price ) * 100 );

		// Check minimum discount threshold.
		$min_discount = isset( $config['min_discount'] ) ? absint( $config['min_discount'] ) : 5;
		if ( $savings_percent < $min_discount ) {
			return '';
		}

		$show_percentage = filter_var( $atts['show_percentage'], FILTER_VALIDATE_BOOLEAN );
		$show_amount     = filter_var( $atts['show_amount'], FILTER_VALIDATE_BOOLEAN );
		$style           = sanitize_key( $atts['style'] );

		// Build display text.
		$format = isset( $config['format'] ) ? $config['format'] : 'Save {percent}!';
		$text   = str_replace(
			array( '{percent}', '{amount}' ),
			array( $savings_percent . '%', wc_price( $savings_amount ) ),
			$format
		);

		// Build content based on style.
		$content = '';

		if ( 'ribbon' === $style ) {
			// Ribbon style - percentage only, compact.
			$content = '<span class="aisales-price-drop__ribbon">-' . $savings_percent . '%</span>';
		} elseif ( 'inline' === $style ) {
			// Inline style - text with both values.
			$parts = array();
			if ( $show_percentage ) {
				$parts[] = '<span class="aisales-price-drop__percent">-' . $savings_percent . '%</span>';
			}
			if ( $show_amount ) {
				/* translators: %s: savings amount */
				$parts[] = '<span class="aisales-price-drop__amount">' . sprintf( esc_html__( 'Save %s', 'ai-sales-manager-for-woocommerce' ), wp_strip_all_tags( wc_price( $savings_amount ) ) ) . '</span>';
			}
			$content = implode( ' ', $parts );
		} else {
			// Badge style (default) - uses format string.
			$content = '<span class="aisales-price-drop__icon">';
			$content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>';
			$content .= '</span>';
			$content .= '<span class="aisales-price-drop__text">' . wp_kses_post( $text ) . '</span>';
		}

		// Build classes.
		$classes   = array( 'aisales-price-drop--' . $style );
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$inline_style = $this->build_margin_style( $config );

		// Apply custom colors.
		if ( ! empty( $config['text_color'] ) ) {
			$inline_style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}
		if ( ! empty( $config['bg_color'] ) ) {
			$inline_style .= 'background-color:' . esc_attr( $config['bg_color'] ) . ';';
		}

		return $this->wrap_output( 'price_drop', $content, $classes, $inline_style );
	}

	/**
	 * Render [aisales_bundle_savings] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_bundle_savings( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'bundle_savings' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'format'     => isset( $config['format'] ) ? $config['format'] : 'Buy {qty}, save {amount} ({percent}%)',
			),
			$atts,
			'aisales_bundle_savings'
		);

		$product_id = $this->get_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		// Check exclusions.
		$exclude_products = isset( $config['exclude_products'] ) ? $config['exclude_products'] : '';
		if ( ! empty( $exclude_products ) ) {
			$excluded_ids = array_map( 'absint', array_filter( explode( ',', $exclude_products ) ) );
			if ( in_array( $product_id, $excluded_ids, true ) ) {
				return '';
			}
		}

		$product = $this->get_product( $product_id );
		if ( ! $product ) {
			return '';
		}

		$price = (float) $product->get_price();
		if ( $price <= 0 ) {
			return '';
		}

		// Parse discount tiers from config (format: qty:percent per line).
		$tiers_raw = isset( $config['tiers'] ) ? $config['tiers'] : "2:5\n3:10\n4:15";
		$tiers     = $this->parse_bundle_tiers( $tiers_raw );

		if ( empty( $tiers ) ) {
			return '';
		}

		// Build savings list.
		$list_items = '';
		foreach ( $tiers as $qty => $percent ) {
			$savings_amount = ( $price * $qty ) * ( $percent / 100 );
			$line_text      = str_replace(
				array( '{qty}', '{amount}', '{percent}' ),
				array( $qty, wc_price( $savings_amount ), $percent ),
				$atts['format']
			);

			$list_items .= '<li class="aisales-bundle-savings__item">' . wp_kses_post( $line_text ) . '</li>';
		}

		$content = '<ul class="aisales-bundle-savings__list">' . $list_items . '</ul>';

		// Build classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$inline_style = $this->build_margin_style( $config );
		if ( ! empty( $config['text_color'] ) ) {
			$inline_style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}
		if ( ! empty( $config['bg_color'] ) ) {
			$inline_style .= 'background-color:' . esc_attr( $config['bg_color'] ) . ';';
		}

		return $this->wrap_output( 'bundle_savings', $content, $classes, $inline_style );
	}

	/**
	 * Render [aisales_cart_urgency] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_cart_urgency( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'cart_urgency' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'duration' => isset( $config['duration'] ) ? $config['duration'] : 15,
				'format'   => isset( $config['format'] ) ? $config['format'] : 'Items reserved for {time}',
			),
			$atts,
			'aisales_cart_urgency'
		);

		$duration_minutes = absint( $atts['duration'] );
		if ( $duration_minutes <= 0 ) {
			return '';
		}

		// Determine cart expiration timestamp using session/cookie.
		$expires_at = $this->get_cart_urgency_expiry( $duration_minutes );
		if ( ! $expires_at ) {
			return '';
		}

		// If already expired, follow end_action.
		if ( $expires_at <= time() ) {
			$end_action = isset( $config['end_action'] ) ? $config['end_action'] : 'hide';
			if ( 'hide' === $end_action ) {
				return '';
			}
		}

		$show_hours   = isset( $config['show_hours'] ) ? $config['show_hours'] : true;
		$show_minutes = isset( $config['show_minutes'] ) ? $config['show_minutes'] : true;
		$show_seconds = isset( $config['show_seconds'] ) ? $config['show_seconds'] : true;

		// Build timer placeholder.
		$timer_html  = '<span class="aisales-cart-urgency__timer" data-end="' . esc_attr( gmdate( 'c', $expires_at ) ) . '" ';
		$timer_html .= 'data-show-hours="' . ( $show_hours ? '1' : '0' ) . '" ';
		$timer_html .= 'data-show-minutes="' . ( $show_minutes ? '1' : '0' ) . '" ';
		$timer_html .= 'data-show-seconds="' . ( $show_seconds ? '1' : '0' ) . '">';
		$timer_html .= '00:00';
		$timer_html .= '</span>';

		// Replace placeholder.
		$text = str_replace( '{time}', $timer_html, $atts['format'] );

		$content  = '<span class="aisales-cart-urgency__icon">';
		$content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 6v6l4 2 .75-1.23-3.25-1.52V6z"/><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16z"/></svg>';
		$content .= '</span>';
		$content .= '<span class="aisales-cart-urgency__text">' . wp_kses_post( $text ) . '</span>';

		// Build classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$inline_style = $this->build_margin_style( $config );
		if ( ! empty( $config['text_color'] ) ) {
			$inline_style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}
		if ( ! empty( $config['bg_color'] ) ) {
			$inline_style .= 'background-color:' . esc_attr( $config['bg_color'] ) . ';';
		}
		if ( ! empty( $config['accent_color'] ) ) {
			$inline_style .= '--aisales-cart-urgency-accent:' . esc_attr( $config['accent_color'] ) . ';';
		}

		return $this->wrap_output( 'cart_urgency', $content, $classes, $inline_style );
	}

	/**
	 * Render [aisales_shipping_bar] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shipping_bar( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'shipping_bar' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'threshold' => isset( $config['threshold'] ) ? $config['threshold'] : 0,
				'message'   => isset( $config['message'] ) ? $config['message'] : 'Add {amount} more for FREE shipping!',
				'success'   => isset( $config['success'] ) ? $config['success'] : 'You qualify for FREE shipping!',
			),
			$atts,
			'aisales_shipping_bar'
		);

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$cart_total = (float) WC()->cart->get_subtotal();
		$threshold  = (float) $atts['threshold'];

		// If threshold not set, try to get from WooCommerce free shipping method.
		if ( $threshold <= 0 ) {
			$threshold = $this->get_free_shipping_threshold();
		}

		if ( $threshold <= 0 ) {
			return '';
		}

		$remaining = max( 0, $threshold - $cart_total );
		$progress  = min( 100, ( $cart_total / $threshold ) * 100 );

		if ( $remaining <= 0 ) {
			$message = $atts['success'];
		} else {
			$message = str_replace( '{amount}', wc_price( $remaining ), $atts['message'] );
		}

		$content  = '<div class="aisales-shipping-bar__message">' . wp_kses_post( $message ) . '</div>';
		$content .= '<div class="aisales-shipping-bar__track">';
		$content .= '<div class="aisales-shipping-bar__fill" style="width:' . esc_attr( round( $progress ) ) . '%"></div>';
		$content .= '</div>';

		// Build classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$inline_style = $this->build_margin_style( $config );
		if ( ! empty( $config['text_color'] ) ) {
			$inline_style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}
		if ( ! empty( $config['bg_color'] ) ) {
			$inline_style .= '--aisales-shipping-bar-bg:' . esc_attr( $config['bg_color'] ) . ';';
		}
		if ( ! empty( $config['bar_color'] ) ) {
			$inline_style .= '--aisales-shipping-bar-fill:' . esc_attr( $config['bar_color'] ) . ';';
		}

		return $this->wrap_output( 'shipping_bar', $content, $classes, $inline_style );
	}

	/**
	 * Parse bundle tier configuration
	 *
	 * Format: "2:5\n3:10" (qty:percent)
	 *
	 * @param string $tiers_raw Raw tiers string.
	 * @return array Parsed tiers sorted by quantity.
	 */
	private function parse_bundle_tiers( $tiers_raw ) {
		$tiers = array();

		$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $tiers_raw ) );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) || false === strpos( $line, ':' ) ) {
				continue;
			}
			list( $qty, $percent ) = array_map( 'trim', explode( ':', $line, 2 ) );
			$qty     = absint( $qty );
			$percent = absint( $percent );
			if ( $qty > 0 && $percent > 0 ) {
				$tiers[ $qty ] = $percent;
			}
		}

		if ( empty( $tiers ) ) {
			return array();
		}

		ksort( $tiers );
		return $tiers;
	}

	/**
	 * Get or set cart urgency expiry timestamp
	 *
	 * @param int $duration_minutes Duration in minutes.
	 * @return int|false Unix timestamp or false.
	 */
	private function get_cart_urgency_expiry( $duration_minutes ) {
		$duration_minutes = absint( $duration_minutes );
		if ( $duration_minutes <= 0 ) {
			return false;
		}

		$cookie_name = 'aisales_cart_urgency_expiry';
		$expiry      = 0;

		if ( ! empty( $_COOKIE[ $cookie_name ] ) ) {
			$expiry = absint( sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) );
		}

		if ( $expiry <= 0 || $expiry < time() ) {
			$expiry = time() + ( $duration_minutes * 60 );
			setcookie( $cookie_name, (string) $expiry, $expiry, COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE[ $cookie_name ] = $expiry;
		}

		return $expiry;
	}

	/**
	 * Get free shipping threshold from WooCommerce settings
	 *
	 * @return float Threshold amount.
	 */
	private function get_free_shipping_threshold() {
		$zones = WC_Shipping_Zones::get_zones();
		$zones[] = array( 'shipping_methods' => WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods() );

		foreach ( $zones as $zone ) {
			if ( empty( $zone['shipping_methods'] ) ) {
				continue;
			}

			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'free_shipping' === $method->id && $method->enabled ) {
					$min_amount = isset( $method->min_amount ) ? (float) $method->min_amount : 0;
					if ( $min_amount > 0 ) {
						return $min_amount;
					}
				}
			}
		}

		return 0;
	}
}
