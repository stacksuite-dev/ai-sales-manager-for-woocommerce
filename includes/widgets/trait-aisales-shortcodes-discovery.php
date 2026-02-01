<?php
/**
 * Discovery shortcodes trait
 *
 * Contains shortcode render methods for product discovery widgets:
 * - Bestsellers
 * - Trending Products
 * - Recently Viewed
 * - Frequently Bought Together
 * - New Arrivals
 *
 * @package AISales_Sales_Manager
 * @since 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trait AISales_Shortcodes_Discovery
 *
 * Provides discovery-related shortcode render methods.
 * Used by AISales_Shortcodes class.
 */
trait AISales_Shortcodes_Discovery {

	/**
	 * Render [aisales_bestsellers] shortcode
	 *
	 * Displays top-selling products in a grid layout.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_bestsellers( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'bestsellers' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'limit'    => isset( $config['limit'] ) ? $config['limit'] : 4,
				'columns'  => isset( $config['columns'] ) ? $config['columns'] : 4,
				'category' => isset( $config['category'] ) ? $config['category'] : '',
				'layout'   => isset( $config['layout'] ) ? $config['layout'] : 'grid',
			),
			$atts,
			'aisales_bestsellers'
		);

		// Query args for best sellers.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'meta_key'       => 'total_sales',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		);

		// Filter by category if specified.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		// Only show visible products.
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		return $this->render_product_grid( 'bestsellers', $query_args, $atts, $config );
	}

	/**
	 * Render [aisales_trending] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_trending( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'trending' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'limit'    => isset( $config['limit'] ) ? $config['limit'] : 4,
				'columns'  => isset( $config['columns'] ) ? $config['columns'] : 4,
				'period'   => isset( $config['period'] ) ? $config['period'] : '7days',
				'category' => isset( $config['category'] ) ? $config['category'] : '',
				'layout'   => isset( $config['layout'] ) ? $config['layout'] : 'grid',
			),
			$atts,
			'aisales_trending'
		);

		// Calculate date range based on period.
		$period = sanitize_key( $atts['period'] );
		switch ( $period ) {
			case '24hours':
				$date_after = '-1 day';
				break;
			case '30days':
				$date_after = '-30 days';
				break;
			case '7days':
			default:
				$date_after = '-7 days';
				break;
		}

		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) );

		// Query for products with recent orders.
		// We'll get products that have been ordered recently, sorted by order count.
		global $wpdb;

		// Get product IDs from recent orders, ordered by frequency.
		$cache_key          = 'aisales_trending_' . md5( $date_threshold . '_' . absint( $atts['limit'] ) );
		$recent_product_ids = wp_cache_get( $cache_key, 'aisales_carts' );
		if ( false === $recent_product_ids ) {
			$recent_product_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT order_item_meta.meta_value as product_id
					FROM {$wpdb->prefix}woocommerce_order_items as order_items
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
						ON order_items.order_item_id = order_item_meta.order_item_id
					INNER JOIN {$wpdb->posts} as posts
						ON order_items.order_id = posts.ID
					WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ('wc-completed', 'wc-processing')
					AND posts.post_date >= %s
					AND order_item_meta.meta_key = '_product_id'
					GROUP BY order_item_meta.meta_value
					ORDER BY COUNT(*) DESC
					LIMIT %d",
					$date_threshold,
					absint( $atts['limit'] ) * 2
				)
			);
			wp_cache_set( $cache_key, $recent_product_ids, 'aisales_carts', 300 );
		}

		if ( empty( $recent_product_ids ) ) {
			// Fallback to bestsellers if no recent orders.
			return $this->render_bestsellers( $atts );
		}

		// Query args for trending products.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'post__in'       => array_map( 'absint', $recent_product_ids ),
			'orderby'        => 'post__in',
		);

		// Filter by category if specified.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		// Only show visible products.
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		return $this->render_product_grid( 'trending', $query_args, $atts, $config );
	}

	/**
	 * Render [aisales_recently_viewed] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_recently_viewed( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'recently_viewed' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'limit'           => isset( $config['limit'] ) ? $config['limit'] : 4,
				'columns'         => isset( $config['columns'] ) ? $config['columns'] : 4,
				'exclude_current' => isset( $config['exclude_current'] ) ? ( $config['exclude_current'] ? 'true' : 'false' ) : 'true',
				'layout'          => isset( $config['layout'] ) ? $config['layout'] : 'grid',
			),
			$atts,
			'aisales_recently_viewed'
		);

		// Get recently viewed products from WooCommerce cookie.
		$viewed_products = array();
		if ( ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ) {
			$viewed_products = explode( '|', sanitize_text_field( wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) );
		}

		$viewed_products = array_map( 'absint', array_filter( $viewed_products ) );

		if ( empty( $viewed_products ) ) {
			return '';
		}

		// Reverse to show most recent first.
		$viewed_products = array_reverse( $viewed_products );

		// Optionally exclude current product.
		$exclude_current = filter_var( $atts['exclude_current'], FILTER_VALIDATE_BOOLEAN );
		if ( $exclude_current ) {
			$current_id = $this->get_product_id( $atts );
			if ( $current_id ) {
				$viewed_products = array_diff( $viewed_products, array( $current_id ) );
			}
		}

		$viewed_products = array_values( array_unique( $viewed_products ) );
		$viewed_products = array_slice( $viewed_products, 0, absint( $atts['limit'] ) );

		if ( empty( $viewed_products ) ) {
			return '';
		}

		// Query args for recently viewed products.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'post__in'       => $viewed_products,
			'orderby'        => 'post__in',
		);

		// Only show visible products.
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		return $this->render_product_grid( 'recently_viewed', $query_args, $atts, $config );
	}

	/**
	 * Render [aisales_bought_together] shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_bought_together( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'bought_together' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'product_id'      => '',
				'limit'           => isset( $config['limit'] ) ? $config['limit'] : 4,
				'columns'         => isset( $config['columns'] ) ? $config['columns'] : 4,
				'exclude_current' => isset( $config['exclude_current'] ) ? ( $config['exclude_current'] ? 'true' : 'false' ) : 'true',
				'layout'          => isset( $config['layout'] ) ? $config['layout'] : 'grid',
			),
			$atts,
			'aisales_bought_together'
		);

		$product_id = $this->get_product_id( $atts );
		if ( ! $product_id ) {
			return '';
		}

		$exclude_current = filter_var( $atts['exclude_current'], FILTER_VALIDATE_BOOLEAN );

		global $wpdb;

		// Find products that were purchased together with the current product.
		$bt_cache_key = 'aisales_bought_together_' . $product_id . '_' . absint( $atts['limit'] );
		$related_ids  = wp_cache_get( $bt_cache_key, 'aisales_carts' );
		if ( false === $related_ids ) {
			$related_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT order_item_meta.meta_value as product_id
					FROM {$wpdb->prefix}woocommerce_order_items as order_items
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
						ON order_items.order_item_id = order_item_meta.order_item_id
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta2
						ON order_items.order_item_id = order_item_meta2.order_item_id
					INNER JOIN {$wpdb->posts} as posts
						ON order_items.order_id = posts.ID
					WHERE posts.post_type = 'shop_order'
					AND posts.post_status IN ('wc-completed', 'wc-processing')
					AND order_item_meta.meta_key = '_product_id'
					AND order_item_meta2.meta_key = '_product_id'
					AND order_item_meta2.meta_value = %d
					AND order_item_meta.meta_value != %d
					GROUP BY order_item_meta.meta_value
					ORDER BY COUNT(*) DESC
					LIMIT %d",
					$product_id,
					$product_id,
					absint( $atts['limit'] ) * 2
				)
			);
			wp_cache_set( $bt_cache_key, $related_ids, 'aisales_carts', 300 );
		}

		if ( empty( $related_ids ) ) {
			return '';
		}

		$related_ids = array_map( 'absint', $related_ids );

		if ( $exclude_current ) {
			$related_ids = array_diff( $related_ids, array( $product_id ) );
		}

		$related_ids = array_values( array_unique( $related_ids ) );
		$related_ids = array_slice( $related_ids, 0, absint( $atts['limit'] ) );

		if ( empty( $related_ids ) ) {
			return '';
		}

		// Query args for related products.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'post__in'       => $related_ids,
			'orderby'        => 'post__in',
		);

		// Only show visible products.
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		return $this->render_product_grid( 'bought_together', $query_args, $atts, $config );
	}

	/**
	 * Render [aisales_new_arrivals] shortcode
	 *
	 * Displays recently added products in a grid layout.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_new_arrivals( $atts ) {
		// Get saved widget configuration.
		$config = $this->get_widget_config( 'new_arrivals' );

		// Shortcode attributes with config defaults.
		$atts = shortcode_atts(
			array(
				'limit'    => isset( $config['limit'] ) ? $config['limit'] : 4,
				'columns'  => isset( $config['columns'] ) ? $config['columns'] : 4,
				'days'     => isset( $config['days'] ) ? $config['days'] : 30,
				'category' => isset( $config['category'] ) ? $config['category'] : '',
				'layout'   => isset( $config['layout'] ) ? $config['layout'] : 'grid',
			),
			$atts,
			'aisales_new_arrivals'
		);

		// Calculate date threshold.
		$days           = absint( $atts['days'] );
		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// Query args for new arrivals.
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'date_query'     => array(
				array(
					'after' => $date_threshold,
				),
			),
		);

		// Filter by category if specified.
		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $atts['category'] ),
				),
			);
		}

		// Only show visible products.
		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		);

		return $this->render_product_grid( 'new_arrivals', $query_args, $atts, $config );
	}

	/* =========================================================================
	   PRODUCT GRID HELPER
	   ========================================================================= */

	/**
	 * Render a product grid/list
	 *
	 * Shared helper for discovery widgets (bestsellers, new_arrivals, trending, etc.)
	 *
	 * @param string $widget_key Widget key (e.g., 'bestsellers', 'new_arrivals').
	 * @param array  $query_args WP_Query arguments.
	 * @param array  $atts       Shortcode attributes.
	 * @param array  $config     Widget configuration.
	 * @return string HTML output.
	 */
	private function render_product_grid( $widget_key, $query_args, $atts, $config ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$products = new WP_Query( $query_args );

		if ( ! $products->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		$this->enqueue_assets();

		// Get display options from config.
		$show_title         = isset( $config['show_title'] ) ? $config['show_title'] : true;
		$show_price         = isset( $config['show_price'] ) ? $config['show_price'] : true;
		$show_rating        = isset( $config['show_rating'] ) ? $config['show_rating'] : true;
		$show_badge         = isset( $config['show_badge'] ) ? $config['show_badge'] : true;
		$badge_text         = isset( $config['badge_text'] ) ? $config['badge_text'] : '';
		$title_text         = isset( $config['title_text'] ) ? $config['title_text'] : '';
		$show_section_title = isset( $config['show_section_title'] ) ? $config['show_section_title'] : true;
		$columns            = isset( $atts['columns'] ) ? absint( $atts['columns'] ) : 4;
		$layout             = isset( $atts['layout'] ) ? sanitize_key( $atts['layout'] ) : 'grid';

		// Build output.
		$output = '';

		// Section title.
		if ( $show_section_title && ! empty( $title_text ) ) {
			$output .= '<h3 class="aisales-product-grid__title">' . esc_html( $title_text ) . '</h3>';
		}

		// Grid wrapper.
		$grid_class  = 'aisales-product-grid__items';
		$grid_class .= ' aisales-product-grid--' . $layout;
		$grid_class .= ' aisales-product-grid--cols-' . $columns;

		$output .= '<div class="' . esc_attr( $grid_class ) . '">';

		while ( $products->have_posts() ) {
			$products->the_post();
			$product = wc_get_product( get_the_ID() );

			if ( ! $product ) {
				continue;
			}

			$output .= '<div class="aisales-product-grid__item">';

			// Product link wrapper.
			$output .= '<a href="' . esc_url( $product->get_permalink() ) . '" class="aisales-product-grid__link">';

			// Image container with badge.
			$output .= '<div class="aisales-product-grid__image-wrap">';

			// Product image.
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				$output .= wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'aisales-product-grid__image' ) );
			} else {
				$output .= wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'aisales-product-grid__image' ) );
			}

			// Badge.
			if ( $show_badge && ! empty( $badge_text ) ) {
				$output .= '<span class="aisales-product-grid__badge">' . esc_html( $badge_text ) . '</span>';
			}

			// Sale badge (if on sale).
			if ( $product->is_on_sale() ) {
				$output .= '<span class="aisales-product-grid__sale-badge">' . esc_html__( 'Sale', 'ai-sales-manager-for-woocommerce' ) . '</span>';
			}

			$output .= '</div>'; // End image-wrap.

			// Product info.
			$output .= '<div class="aisales-product-grid__info">';

			// Title.
			if ( $show_title ) {
				$output .= '<h4 class="aisales-product-grid__product-title">' . esc_html( $product->get_name() ) . '</h4>';
			}

			// Rating.
			if ( $show_rating && $product->get_average_rating() > 0 ) {
				$rating  = $product->get_average_rating();
				$output .= '<div class="aisales-product-grid__rating">';
				$output .= $this->render_star_rating( $rating );
				$output .= '</div>';
			}

			// Price.
			if ( $show_price ) {
				$output .= '<div class="aisales-product-grid__price">' . $product->get_price_html() . '</div>';
			}

			$output .= '</div>'; // End info.
			$output .= '</a>'; // End link.
			$output .= '</div>'; // End item.
		}

		$output .= '</div>'; // End items.

		wp_reset_postdata();

		// Build wrapper classes.
		$classes      = array();
		$custom_class = isset( $config['custom_class'] ) ? $config['custom_class'] : '';
		if ( ! empty( $custom_class ) ) {
			$classes[] = sanitize_html_class( $custom_class );
		}

		// Build styles.
		$style = $this->build_margin_style( $config );

		// Use wrap_output but with block display.
		$base_class = 'aisales-widget aisales-product-grid aisales-' . str_replace( '_', '-', $widget_key );
		if ( ! empty( $classes ) ) {
			$base_class .= ' ' . implode( ' ', $classes );
		}

		$style_attr = ! empty( $style ) ? sprintf( ' style="%s"', esc_attr( $style ) ) : '';

		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $base_class ),
			$style_attr,
			$output
		);
	}

	/**
	 * Render star rating HTML (compact version for grids)
	 *
	 * @param float $rating Rating value (0-5).
	 * @return string HTML output.
	 */
	private function render_star_rating( $rating ) {
		$rating     = (float) $rating;
		$full_stars = floor( $rating );
		$half_star  = ( $rating - $full_stars ) >= 0.5;

		$html = '<span class="aisales-stars">';

		// Full stars.
		for ( $i = 0; $i < $full_stars; $i++ ) {
			$html .= '<svg class="aisales-star aisales-star--full" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		// Half star.
		if ( $half_star ) {
			$html .= '<svg class="aisales-star aisales-star--half" viewBox="0 0 24 24" width="14" height="14"><defs><linearGradient id="half-grad-grid"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs><path fill="url(#half-grad-grid)" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		// Empty stars to fill to 5.
		$empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
		for ( $i = 0; $i < $empty_stars; $i++ ) {
			$html .= '<svg class="aisales-star aisales-star--empty" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
		}

		$html .= '</span>';

		return $html;
	}
}
