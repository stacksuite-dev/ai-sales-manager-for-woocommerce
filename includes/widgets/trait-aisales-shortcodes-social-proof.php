<?php
/**
 * Social Proof Shortcodes Trait
 *
 * Contains render methods for social proof widgets:
 * - Total Sold
 * - Stock Urgency
 * - Recent Purchase
 * - Live Viewers
 * - Review Summary
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Social Proof Shortcodes trait
 */
trait AISales_Shortcodes_Social_Proof {

	/**
	 * Render [aisales_total_sold] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_total_sold( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'total_sold' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'format'     => isset( $config['format'] ) ? $config['format'] : '{count} sold',
				'show_icon'  => isset( $config['show_icon'] ) ? ( $config['show_icon'] ? 'true' : 'false' ) : 'true',
			),
			$atts,
			'aisales_total_sold'
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

		// Check category exclusions.
		$exclude_categories = isset( $config['exclude_categories'] ) ? $config['exclude_categories'] : '';
		if ( ! empty( $exclude_categories ) ) {
			$excluded_cats  = array_map( 'trim', explode( ',', $exclude_categories ) );
			$product_cats   = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
			$product_cats   = is_array( $product_cats ) ? $product_cats : array();
			$intersect      = array_intersect( $excluded_cats, $product_cats );
			if ( ! empty( $intersect ) ) {
				return '';
			}
		}

		// Get total sales count.
		$total_sold = $product->get_total_sales();

		// Check minimum to show threshold.
		$min_to_show = isset( $config['min_to_show'] ) ? absint( $config['min_to_show'] ) : 1;
		if ( $total_sold < $min_to_show ) {
			return '';
		}

		// Format the number based on config.
		$number_format   = isset( $config['number_format'] ) ? $config['number_format'] : 'formatted';
		$formatted_count = $this->format_number( $total_sold, $number_format );
		$text            = str_replace( '{count}', $formatted_count, $atts['format'] );

		$show_icon = filter_var( $atts['show_icon'], FILTER_VALIDATE_BOOLEAN );

		// Get icon style.
		$icon_style = isset( $config['icon_style'] ) ? $config['icon_style'] : 'cart';
		$icons      = array(
			'cart'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>',
			'bag'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-6-2c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm6 16H6V8h2v2c0 .55.45 1 1 1s1-.45 1-1V8h4v2c0 .55.45 1 1 1s1-.45 1-1V8h2v12z"/></svg>',
			'chart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M5 9.2h3V19H5V9.2zM10.6 5h2.8v14h-2.8V5zm5.6 8H19v6h-2.8v-6z"/></svg>',
			'fire'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/></svg>',
		);

		$content = '';
		if ( $show_icon && isset( $icons[ $icon_style ] ) ) {
			$content .= '<span class="aisales-total-sold__icon">' . $icons[ $icon_style ] . '</span>';
		}

		// Add animation data attribute if enabled.
		$animate = isset( $config['animate'] ) && $config['animate'];
		if ( $animate ) {
			$content .= '<span class="aisales-total-sold__text" data-animate="true" data-count="' . esc_attr( $total_sold ) . '">' . esc_html( $text ) . '</span>';
		} else {
			$content .= '<span class="aisales-total-sold__text">' . esc_html( $text ) . '</span>';
		}

		// Build classes.
		$classes = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles (margins + colors).
		$style = $this->build_margin_style( $config );

		// Add color styles - check if keys exist in saved config to determine if user has customized.
		$all_config   = get_option( 'aisales_widget_config', array() );
		$saved_config = isset( $all_config['total_sold'] ) ? $all_config['total_sold'] : array();

		// Apply text color if set.
		if ( ! empty( $config['text_color'] ) ) {
			$style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}

		// Apply background color - if key exists in saved config, user has customized it.
		// Empty value means user wants transparent (per help text), so override CSS default.
		if ( array_key_exists( 'bg_color', $saved_config ) ) {
			if ( ! empty( $config['bg_color'] ) ) {
				$style .= 'background-color:' . esc_attr( $config['bg_color'] ) . ';padding:8px 12px;border-radius:6px;';
			} else {
				// User explicitly set empty = transparent background.
				$style .= 'background:transparent;padding:0;';
			}
		}
		// If bg_color not in saved config, CSS defaults apply (no inline override needed).

		// Apply icon color if set.
		if ( ! empty( $config['icon_color'] ) ) {
			$style .= '--aisales-icon-color:' . esc_attr( $config['icon_color'] ) . ';';
		}

		return $this->wrap_output( 'total_sold', $content, $classes, $style );
	}

	/**
	 * Render [aisales_stock_urgency] shortcode
	 *
	 * Displays low stock warning to create urgency.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_stock_urgency( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'stock_urgency' );

		// Thresholds from config.
		$threshold_low      = isset( $config['threshold_low'] ) ? absint( $config['threshold_low'] ) : 10;
		$threshold_warning  = isset( $config['threshold_warning'] ) ? absint( $config['threshold_warning'] ) : 5;
		$threshold_critical = isset( $config['threshold_critical'] ) ? absint( $config['threshold_critical'] ) : 2;

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'threshold'  => $threshold_low,
				'format'     => isset( $config['format_low'] ) ? $config['format_low'] : 'Only {count} left in stock!',
			),
			$atts,
			'aisales_stock_urgency'
		);

		$product_id = $this->get_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		// Check product exclusions.
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

		// Check if product manages stock.
		if ( ! $product->managing_stock() ) {
			return '';
		}

		// Check backorder exclusion.
		$exclude_backorder = isset( $config['exclude_backorder'] ) ? $config['exclude_backorder'] : true;
		if ( $exclude_backorder && $product->backorders_allowed() ) {
			return '';
		}

		$stock_qty = $product->get_stock_quantity();
		$threshold = absint( $atts['threshold'] );

		// Don't show if stock is above threshold or out of stock.
		if ( null === $stock_qty || $stock_qty > $threshold || $stock_qty < 1 ) {
			return '';
		}

		// Determine urgency level and format based on config thresholds.
		$urgency_class = 'aisales-stock-urgency--low';
		$format        = isset( $config['format_low'] ) ? $config['format_low'] : 'Only {count} left in stock';

		if ( $stock_qty <= $threshold_critical ) {
			$urgency_class = 'aisales-stock-urgency--critical';
			$format        = isset( $config['format_critical'] ) ? $config['format_critical'] : 'Almost gone! Only {count} left!';
		} elseif ( $stock_qty <= $threshold_warning ) {
			$urgency_class = 'aisales-stock-urgency--warning';
			$format        = isset( $config['format_warning'] ) ? $config['format_warning'] : 'Hurry! Only {count} left!';
		}

		$text = str_replace( '{count}', $this->format_number( $stock_qty ), $format );

		// Build content.
		$show_icon = isset( $config['show_icon'] ) ? $config['show_icon'] : true;
		$animation = isset( $config['animation'] ) ? $config['animation'] : true;

		$content = '';
		if ( $show_icon ) {
			$content .= '<span class="aisales-stock-urgency__icon">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
					<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
				</svg>
			</span>';
		}
		$content .= '<span class="aisales-stock-urgency__text">' . esc_html( $text ) . '</span>';

		// Build classes.
		$classes = array( $urgency_class );
		if ( $animation && $stock_qty <= $threshold_critical ) {
			$classes[] = 'aisales-stock-urgency--pulse';
		}
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build margin styles.
		$style = $this->build_margin_style( $config );

		return $this->wrap_output( 'stock_urgency', $content, $classes, $style );
	}

	/**
	 * Render [aisales_recent_purchase] shortcode
	 *
	 * Displays recent purchase notifications as popups or inline.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_recent_purchase( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'recent_purchase' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'limit'         => isset( $config['limit'] ) ? $config['limit'] : 5,
				'display'       => 'popup',
				'show_location' => isset( $config['show_location'] ) ? ( $config['show_location'] ? 'true' : 'false' ) : 'true',
			),
			$atts,
			'aisales_recent_purchase'
		);

		$recent_orders = $this->get_recent_purchases( absint( $atts['limit'] ) );
		if ( empty( $recent_orders ) ) {
			return '';
		}

		$show_location = filter_var( $atts['show_location'], FILTER_VALIDATE_BOOLEAN );
		$privacy_level = isset( $config['privacy_level'] ) ? $config['privacy_level'] : 'first_city';
		$show_image    = isset( $config['show_image'] ) ? $config['show_image'] : true;
		$position      = isset( $config['position'] ) ? $config['position'] : 'bottom-left';
		$animation     = isset( $config['animation'] ) ? $config['animation'] : 'slide';

		// Build notification items.
		$items_html = '';
		foreach ( $recent_orders as $order_data ) {
			$customer_name = $this->format_customer_name( $order_data['name'], $privacy_level );
			$location      = $show_location ? $order_data['location'] : '';

			$text = sprintf(
				/* translators: 1: customer name, 2: product name */
				__( '%1$s purchased %2$s', 'ai-sales-manager-for-woocommerce' ),
				esc_html( $customer_name ),
				esc_html( $order_data['product_name'] )
			);

			if ( ! empty( $location ) ) {
				$text .= ' ' . sprintf(
					/* translators: %s: customer location */
					__( 'from %s', 'ai-sales-manager-for-woocommerce' ),
					esc_html( $location )
				);
			}

			$items_html .= '<div class="aisales-recent-purchase__item" data-timestamp="' . esc_attr( $order_data['timestamp'] ) . '">';
			if ( $show_image && ! empty( $order_data['image'] ) ) {
				$items_html .= '<div class="aisales-recent-purchase__image">' . $order_data['image'] . '</div>';
			}
			$items_html .= '<div class="aisales-recent-purchase__content">';
			$items_html .= '<div class="aisales-recent-purchase__text">' . esc_html( $text ) . '</div>';
			$items_html .= '<div class="aisales-recent-purchase__time">' . esc_html( $order_data['time_ago'] ) . '</div>';
			$items_html .= '</div>';
			$items_html .= '</div>';
		}

		$content = '<div class="aisales-recent-purchase__list">' . $items_html . '</div>';

		// Build classes.
		$classes      = array( 'aisales-recent-purchase--' . $position, 'aisales-recent-purchase--' . $animation );
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$style = '';

		// Data attributes for JS rotation.
		$interval = isset( $config['interval'] ) ? absint( $config['interval'] ) : 8;
		$duration = isset( $config['duration'] ) ? absint( $config['duration'] ) : 5;

		return $this->wrap_recent_purchase_output( $content, $classes, $style, $interval, $duration );
	}

	/**
	 * Render [aisales_live_viewers] shortcode
	 *
	 * Displays live viewer count with randomized refresh.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_live_viewers( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'live_viewers' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id' => '',
				'format'     => isset( $config['format'] ) ? $config['format'] : '{count} people are viewing this product',
			),
			$atts,
			'aisales_live_viewers'
		);

		$product_id = $this->get_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$min_viewers     = isset( $config['min_to_show'] ) ? absint( $config['min_to_show'] ) : 1;
		$max_viewers     = isset( $config['max_viewers'] ) ? absint( $config['max_viewers'] ) : 25;
		$update_interval = isset( $config['refresh_interval'] ) ? absint( $config['refresh_interval'] ) : 12;
		$show_pulse      = isset( $config['show_pulse'] ) ? (bool) $config['show_pulse'] : false;

		// Backward-compatible aliases (if older config keys exist).
		if ( isset( $config['min_viewers'] ) && ! isset( $config['min_to_show'] ) ) {
			$min_viewers = absint( $config['min_viewers'] );
		}
		if ( isset( $config['update_interval'] ) && ! isset( $config['refresh_interval'] ) ) {
			$update_interval = absint( $config['update_interval'] );
		}

		if ( $max_viewers < $min_viewers ) {
			$max_viewers = $min_viewers;
		}

		$count = $this->get_live_viewer_count( $product_id, $min_viewers, $max_viewers );

		if ( $count < $min_viewers ) {
			return '';
		}

		$text = str_replace( '{count}', $count, $atts['format'] );

		$content  = '<span class="aisales-live-viewers__icon">';
		$content .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 6a9.77 9.77 0 019.26 6 9.77 9.77 0 01-18.52 0A9.77 9.77 0 0112 6zm0-2C6 4 1.73 7.11 0 12c1.73 4.89 6 8 12 8s10.27-3.11 12-8c-1.73-4.89-6-8-12-8zm0 5a3 3 0 100 6 3 3 0 000-6z"/></svg>';
		$content .= '</span>';
		$content .= '<span class="aisales-live-viewers__text">' . esc_html( $text ) . '</span>';

		// Build classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}
		if ( $show_pulse ) {
			$classes[] = 'aisales-live-viewers--pulse';
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
			$inline_style .= '--aisales-live-viewers-accent:' . esc_attr( $config['accent_color'] ) . ';';
		}

		// Wrap with data attributes for JS refresh.
		return sprintf(
			'<div class="%s" style="%s" data-product="%d" data-min="%d" data-max="%d" data-interval="%d" data-format="%s">%s</div>',
			esc_attr( 'aisales-widget aisales-live-viewers' . ( ! empty( $classes ) ? ' ' . implode( ' ', $classes ) : '' ) ),
			esc_attr( $inline_style ),
			$product_id,
			$min_viewers,
			$max_viewers,
			$update_interval,
			esc_attr( $atts['format'] ),
			$content
		);
	}

	/**
	 * Render [aisales_review_summary] shortcode
	 *
	 * Displays star rating and review count for a product.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_review_summary( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'review_summary' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id'     => '',
				'show_count'     => isset( $config['show_count'] ) ? ( $config['show_count'] ? 'true' : 'false' ) : 'true',
				'show_breakdown' => isset( $config['show_breakdown'] ) ? ( $config['show_breakdown'] ? 'true' : 'false' ) : 'false',
			),
			$atts,
			'aisales_review_summary'
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

		// Get review data.
		$average_rating = (float) $product->get_average_rating();
		$review_count   = (int) $product->get_review_count();

		// Check minimum reviews threshold.
		$min_reviews = isset( $config['min_reviews'] ) ? absint( $config['min_reviews'] ) : 1;
		if ( $review_count < $min_reviews ) {
			return '';
		}

		$show_count     = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
		$show_breakdown = filter_var( $atts['show_breakdown'], FILTER_VALIDATE_BOOLEAN );

		// Build star rating HTML.
		$full_stars  = floor( $average_rating );
		$half_star   = ( $average_rating - $full_stars ) >= 0.5;
		$empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );

		$stars_html = '<span class="aisales-review-summary__stars">';

		// Full stars.
		for ( $i = 0; $i < $full_stars; $i++ ) {
			$stars_html .= '<svg class="aisales-star aisales-star--full" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		// Half star.
		if ( $half_star ) {
			$stars_html .= '<svg class="aisales-star aisales-star--half" viewBox="0 0 24 24" width="16" height="16"><defs><linearGradient id="half-grad"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs><path fill="url(#half-grad)" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		// Empty stars.
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			$stars_html .= '<svg class="aisales-star aisales-star--empty" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		$stars_html .= '</span>';

		// Build content.
		$content = $stars_html;

		// Rating number.
		$content .= '<span class="aisales-review-summary__rating">' . number_format( $average_rating, 1 ) . '</span>';

		// Review count.
		if ( $show_count ) {
			$count_text = sprintf(
				/* translators: %s: number of reviews */
				_n( '(%s review)', '(%s reviews)', $review_count, 'ai-sales-manager-for-woocommerce' ),
				$this->format_number( $review_count )
			);
			$content .= '<span class="aisales-review-summary__count">' . esc_html( $count_text ) . '</span>';
		}

		// Rating breakdown (optional).
		if ( $show_breakdown ) {
			$breakdown_html = $this->get_rating_breakdown_html( $product_id );
			if ( $breakdown_html ) {
				$content .= $breakdown_html;
			}
		}

		// Build classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$style = $this->build_margin_style( $config );

		// Apply custom colors.
		if ( ! empty( $config['star_color'] ) ) {
			$style .= '--aisales-star-color:' . esc_attr( $config['star_color'] ) . ';';
		}
		if ( ! empty( $config['text_color'] ) ) {
			$style .= 'color:' . esc_attr( $config['text_color'] ) . ';';
		}

		return $this->wrap_output( 'review_summary', $content, $classes, $style );
	}

	/**
	 * Get rating breakdown HTML for a product
	 *
	 * @param int $product_id Product ID.
	 * @return string HTML output or empty string.
	 */
	private function get_rating_breakdown_html( $product_id ) {
		$cache_key = 'aisales_rating_breakdown_' . absint( $product_id );
		$ratings   = wp_cache_get( $cache_key, 'aisales_carts' );
		if ( false === $ratings ) {
			global $wpdb;

			$ratings = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_value as rating, COUNT(*) as count
					FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
					WHERE c.comment_post_ID = %d
					AND c.comment_approved = '1'
					AND c.comment_type = 'review'
					AND cm.meta_key = 'rating'
					GROUP BY meta_value
					ORDER BY meta_value DESC",
					$product_id
				)
			);
			wp_cache_set( $cache_key, $ratings, 'aisales_carts', 300 );
		}

		if ( empty( $ratings ) ) {
			return '';
		}

		// Build distribution array.
		$distribution = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
		$total        = 0;

		foreach ( $ratings as $row ) {
			$star              = absint( $row->rating );
			$count             = absint( $row->count );
			$distribution[ $star ] = $count;
			$total            += $count;
		}

		if ( 0 === $total ) {
			return '';
		}

		$html = '<div class="aisales-review-summary__breakdown">';

		for ( $star = 5; $star >= 1; $star-- ) {
			$count      = $distribution[ $star ];
			$percentage = ( $total > 0 ) ? round( ( $count / $total ) * 100 ) : 0;

			$html .= '<div class="aisales-breakdown__row">';
			$html .= '<span class="aisales-breakdown__label">' . $star . ' star</span>';
			$html .= '<div class="aisales-breakdown__bar"><div class="aisales-breakdown__fill" style="width:' . $percentage . '%"></div></div>';
			$html .= '<span class="aisales-breakdown__count">' . $count . '</span>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get recent purchases for social proof
	 *
	 * @param int $limit Number of purchases.
	 * @return array Recent purchase data.
	 */
	private function get_recent_purchases( $limit ) {
		$limit = absint( $limit );
		if ( $limit <= 0 ) {
			return array();
		}

		$include_guests = true;
		$config         = $this->get_widget_config( 'recent_purchase' );
		if ( isset( $config['include_guests'] ) ) {
			$include_guests = (bool) $config['include_guests'];
		}

		$orders = wc_get_orders(
			array(
				'limit'        => $limit,
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'orderby'      => 'date',
				'order'        => 'DESC',
				'customer_id'  => $include_guests ? '' : 0,
				'return'       => 'objects',
			)
		);

		if ( empty( $orders ) ) {
			return array();
		}

		$results = array();
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$items = $order->get_items();
			if ( empty( $items ) ) {
				continue;
			}

			$first_item = reset( $items );
			$product    = $first_item ? $first_item->get_product() : false;

			if ( ! $product ) {
				continue;
			}

			$image = $product->get_image( 'thumbnail', array( 'class' => 'aisales-recent-purchase__img' ) );

			$results[] = array(
				'name'         => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'location'     => trim( $order->get_billing_city() ),
				'product_name' => $product->get_name(),
				'image'        => $image,
				'timestamp'    => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time(),
				'time_ago'     => human_time_diff( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : time(), time() ) . ' ago',
			);
		}

		return $results;
	}

	/**
	 * Format customer name according to privacy setting
	 *
	 * @param string $name Customer name.
	 * @param string $privacy Privacy level.
	 * @return string Formatted name.
	 */
	private function format_customer_name( $name, $privacy ) {
		$name = trim( (string) $name );
		if ( empty( $name ) ) {
			return __( 'Someone', 'ai-sales-manager-for-woocommerce' );
		}

		if ( 'anonymous' === $privacy ) {
			return __( 'Someone', 'ai-sales-manager-for-woocommerce' );
		}

		if ( 'first_city' === $privacy ) {
			$parts = explode( ' ', $name );
			return $parts[0];
		}

		return $name;
	}

	/**
	 * Wrap recent purchase output with custom attributes
	 *
	 * @param string $content Content HTML.
	 * @param array  $classes Additional classes.
	 * @param string $style Inline style.
	 * @param int    $interval Interval seconds.
	 * @param int    $duration Duration seconds.
	 * @return string HTML output.
	 */
	private function wrap_recent_purchase_output( $content, $classes, $style, $interval, $duration ) {
		$this->enqueue_assets();

		$base_class = 'aisales-widget aisales-recent-purchase';
		if ( ! empty( $classes ) ) {
			$base_class .= ' ' . implode( ' ', $classes );
		}

		$style_attr = '';
		if ( ! empty( $style ) ) {
			$style_attr = sprintf( ' style="%s"', esc_attr( $style ) );
		}

		return sprintf(
			'<div class="%s"%s data-interval="%d" data-duration="%d">%s</div>',
			esc_attr( $base_class ),
			$style_attr,
			$interval,
			$duration,
			$content
		);
	}

	/**
	 * Get live viewer count for a product
	 *
	 * Uses transient to keep counts stable per product for a short period.
	 *
	 * @param int $product_id Product ID.
	 * @param int $min Minimum viewers.
	 * @param int $max Maximum viewers.
	 * @return int Viewer count.
	 */
	private function get_live_viewer_count( $product_id, $min, $max ) {
		$min = absint( $min );
		$max = absint( $max );
		if ( $max < $min ) {
			$max = $min;
		}

		$transient_key = 'aisales_live_viewers_' . absint( $product_id );
		$cached        = get_transient( $transient_key );
		if ( false !== $cached ) {
			return absint( $cached );
		}

		$count = wp_rand( $min, $max );
		set_transient( $transient_key, $count, 60 );

		return $count;
	}
}
