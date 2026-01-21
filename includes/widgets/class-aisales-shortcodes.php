<?php
/**
 * Shortcodes Manager
 *
 * Registers and handles all AI Sales shortcodes for the frontend.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes class
 */
class AISales_Shortcodes {

	/**
	 * Single instance
	 *
	 * @var AISales_Shortcodes
	 */
	private static $instance = null;

	/**
	 * Whether frontend assets have been enqueued
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Get instance
	 *
	 * @return AISales_Shortcodes
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->register_shortcodes();
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
	}

	/**
	 * Register all shortcodes
	 */
	private function register_shortcodes() {
		// Social Proof.
		add_shortcode( 'aisales_total_sold', array( $this, 'render_total_sold' ) );
		add_shortcode( 'aisales_stock_urgency', array( $this, 'render_stock_urgency' ) );
		add_shortcode( 'aisales_review_summary', array( $this, 'render_review_summary' ) );

		// Conversion.
		add_shortcode( 'aisales_countdown', array( $this, 'render_countdown' ) );
		add_shortcode( 'aisales_price_drop', array( $this, 'render_price_drop' ) );
		add_shortcode( 'aisales_bundle_savings', array( $this, 'render_bundle_savings' ) );
		add_shortcode( 'aisales_cart_urgency', array( $this, 'render_cart_urgency' ) );

		// Discovery.
		add_shortcode( 'aisales_bestsellers', array( $this, 'render_bestsellers' ) );
		add_shortcode( 'aisales_trending', array( $this, 'render_trending' ) );
		add_shortcode( 'aisales_recently_viewed', array( $this, 'render_recently_viewed' ) );
		add_shortcode( 'aisales_bought_together', array( $this, 'render_bought_together' ) );
		add_shortcode( 'aisales_new_arrivals', array( $this, 'render_new_arrivals' ) );
	}

	/**
	 * Register frontend assets (loaded only when shortcode is used)
	 */
	public function register_frontend_assets() {
		$css_file    = AISALES_PLUGIN_DIR . 'assets/css/shortcodes.css';
		$css_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $css_file ) )
			? filemtime( $css_file )
			: AISALES_VERSION;

		wp_register_style(
			'aisales-shortcodes',
			AISALES_PLUGIN_URL . 'assets/css/shortcodes.css',
			array(),
			$css_version
		);

		$js_file    = AISALES_PLUGIN_DIR . 'assets/js/shortcodes.js';
		$js_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $js_file ) )
			? filemtime( $js_file )
			: AISALES_VERSION;

		wp_register_script(
			'aisales-shortcodes',
			AISALES_PLUGIN_URL . 'assets/js/shortcodes.js',
			array( 'jquery' ),
			$js_version,
			true
		);
	}

	/**
	 * Enqueue frontend assets (called when a shortcode is rendered)
	 */
	private function enqueue_assets() {
		if ( $this->assets_enqueued ) {
			return;
		}
		wp_enqueue_style( 'aisales-shortcodes' );
		wp_enqueue_script( 'aisales-shortcodes' );
		$this->assets_enqueued = true;
	}

	/**
	 * Get product ID from shortcode attributes or current context
	 *
	 * @param array $atts Shortcode attributes.
	 * @return int|false Product ID or false if not found.
	 */
	private function get_product_id( $atts ) {
		// Explicit product_id attribute.
		if ( ! empty( $atts['product_id'] ) ) {
			return absint( $atts['product_id'] );
		}

		// Auto-detect from current product page.
		global $product;
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			return $product->get_id();
		}

		// Try get_queried_object for single product pages.
		if ( is_singular( 'product' ) ) {
			return get_queried_object_id();
		}

		return false;
	}

	/**
	 * Get product object
	 *
	 * @param int $product_id Product ID.
	 * @return WC_Product|false Product object or false.
	 */
	private function get_product( $product_id ) {
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return false;
		}
		return wc_get_product( $product_id );
	}

	/**
	 * Format number with locale-aware separators
	 *
	 * @param int    $number Number to format.
	 * @param string $format Format type: 'formatted', 'compact', or 'raw'.
	 * @return string Formatted number.
	 */
	private function format_number( $number, $format = 'formatted' ) {
		switch ( $format ) {
			case 'compact':
				if ( $number >= 1000000 ) {
					return round( $number / 1000000, 1 ) . 'M';
				} elseif ( $number >= 1000 ) {
					return round( $number / 1000, 1 ) . 'K';
				}
				return (string) $number;

			case 'raw':
				return (string) $number;

			case 'formatted':
			default:
				return number_format_i18n( $number );
		}
	}

	/**
	 * Get widget configuration from widgets page
	 *
	 * Retrieves saved config and merges with defaults. Works both in admin
	 * (when AISales_Widgets_Page is available) and frontend (direct option read).
	 *
	 * @param string $widget_key Widget key.
	 * @return array Widget config with defaults.
	 */
	private function get_widget_config( $widget_key ) {
		// Define defaults for each widget type.
		$defaults = $this->get_widget_defaults( $widget_key );

		// Get saved config from option.
		$all_config   = get_option( 'aisales_widget_config', array() );
		$saved_config = isset( $all_config[ $widget_key ] ) ? $all_config[ $widget_key ] : array();

		return wp_parse_args( $saved_config, $defaults );
	}

	/**
	 * Get default configuration values for a widget
	 *
	 * @param string $widget_key Widget key.
	 * @return array Default config values.
	 */
	private function get_widget_defaults( $widget_key ) {
		$defaults = array(
			'total_sold'    => array(
				'margin_top'         => 10,
				'margin_bottom'      => 10,
				'show_icon'          => true,
				'icon_style'         => 'cart',
				'custom_class'       => '',
				'text_color'         => '',
				'bg_color'           => '',
				'icon_color'         => '',
				'format'             => '{count} sold',
				'min_to_show'        => 1,
				'number_format'      => 'formatted',
				'animate'            => false,
				'cache_duration'     => 'global',
				'exclude_products'   => '',
				'exclude_categories' => '',
			),
			'stock_urgency'  => array(
				'margin_top'         => 10,
				'margin_bottom'      => 10,
				'show_icon'          => true,
				'animation'          => true,
				'custom_class'       => '',
				'threshold_low'      => 10,
				'threshold_warning'  => 5,
				'threshold_critical' => 2,
				'format_low'         => 'Only {count} left in stock',
				'format_warning'     => 'Hurry! Only {count} left!',
				'format_critical'    => 'Almost gone! Only {count} left!',
				'exclude_backorder'  => true,
				'exclude_products'   => '',
			),
			'review_summary' => array(
				'margin_top'       => 10,
				'margin_bottom'    => 10,
				'show_count'       => true,
				'show_breakdown'   => false,
				'min_reviews'      => 1,
				'star_color'       => '#f59e0b',
				'text_color'       => '',
				'custom_class'     => '',
				'exclude_products' => '',
			),
			'price_drop'     => array(
				'margin_top'       => 10,
				'margin_bottom'    => 10,
				'show_percentage'  => true,
				'show_amount'      => false,
				'style'            => 'badge',
				'format'           => 'Save {percent}!',
				'min_discount'     => 5,
				'text_color'       => '',
				'bg_color'         => '',
				'custom_class'     => '',
				'exclude_products' => '',
			),
			'new_arrivals'   => array(
				'margin_top'         => 20,
				'margin_bottom'      => 20,
				'limit'              => 4,
				'columns'            => 4,
				'days'               => 30,
				'category'           => '',
				'layout'             => 'grid',
				'show_title'         => true,
				'show_price'         => true,
				'show_rating'        => true,
				'show_badge'         => true,
				'badge_text'         => 'New',
				'title_text'         => 'New Arrivals',
				'show_section_title' => true,
				'custom_class'       => '',
			),
			'bestsellers'    => array(
				'margin_top'         => 20,
				'margin_bottom'      => 20,
				'limit'              => 4,
				'columns'            => 4,
				'category'           => '',
				'layout'             => 'grid',
				'show_title'         => true,
				'show_price'         => true,
				'show_rating'        => true,
				'show_badge'         => true,
				'badge_text'         => 'Best Seller',
				'title_text'         => 'Best Sellers',
				'show_section_title' => true,
				'custom_class'       => '',
			),
			'trending'       => array(
				'margin_top'         => 20,
				'margin_bottom'      => 20,
				'limit'              => 4,
				'columns'            => 4,
				'period'             => '7days',
				'category'           => '',
				'layout'             => 'grid',
				'show_title'         => true,
				'show_price'         => true,
				'show_rating'        => true,
				'show_badge'         => true,
				'badge_text'         => 'Trending',
				'title_text'         => 'Trending Now',
				'show_section_title' => true,
				'custom_class'       => '',
			),
			'countdown'      => array(
				'margin_top'       => 10,
				'margin_bottom'    => 10,
				'style'            => 'inline',
				'format'           => 'Sale ends in {timer}',
				'end_action'       => 'hide',
				'show_days'        => true,
				'show_hours'       => true,
				'show_minutes'     => true,
				'show_seconds'     => true,
				'text_color'       => '',
				'bg_color'         => '',
				'accent_color'     => '#dc2626',
				'custom_class'     => '',
				'exclude_products' => '',
			),
			'recently_viewed' => array(
				'margin_top'         => 20,
				'margin_bottom'      => 20,
				'limit'              => 4,
				'columns'            => 4,
				'layout'             => 'grid',
				'exclude_current'    => true,
				'show_title'         => true,
				'show_price'         => true,
				'show_rating'        => true,
				'show_badge'         => true,
				'badge_text'         => 'Viewed',
				'title_text'         => 'Recently Viewed',
				'show_section_title' => true,
				'custom_class'       => '',
			),
			'bought_together' => array(
				'margin_top'         => 20,
				'margin_bottom'      => 20,
				'limit'              => 4,
				'columns'            => 4,
				'layout'             => 'grid',
				'exclude_current'    => true,
				'show_title'         => true,
				'show_price'         => true,
				'show_rating'        => true,
				'show_badge'         => true,
				'badge_text'         => 'Bundle',
				'title_text'         => 'Frequently Bought Together',
				'show_section_title' => true,
				'custom_class'       => '',
			),
			'bundle_savings'  => array(
				'margin_top'       => 10,
				'margin_bottom'    => 10,
				'format'           => 'Buy {qty}, save {amount} ({percent}%)',
				'tiers'            => "2:5\n3:10\n4:15",
				'text_color'       => '',
				'bg_color'         => '',
				'custom_class'     => '',
				'exclude_products' => '',
			),
			'cart_urgency'    => array(
				'margin_top'    => 10,
				'margin_bottom' => 10,
				'duration'      => 15,
				'format'        => 'Items reserved for {time}',
				'end_action'    => 'hide',
				'show_hours'    => true,
				'show_minutes'  => true,
				'show_seconds'  => true,
				'text_color'    => '',
				'bg_color'      => '',
				'accent_color'  => '#e65100',
				'custom_class'  => '',
			),
		);

		return isset( $defaults[ $widget_key ] ) ? $defaults[ $widget_key ] : array();
	}

	/**
	 * Build margin style string from config
	 *
	 * @param array $config Widget config.
	 * @return string CSS style string.
	 */
	private function build_margin_style( $config ) {
		$margin_top    = isset( $config['margin_top'] ) ? absint( $config['margin_top'] ) : 10;
		$margin_bottom = isset( $config['margin_bottom'] ) ? absint( $config['margin_bottom'] ) : 10;

		return sprintf( 'margin-top: %dpx; margin-bottom: %dpx;', $margin_top, $margin_bottom );
	}

	/**
	 * Render wrapper for shortcode output
	 *
	 * @param string $shortcode Shortcode name.
	 * @param string $content   Inner content.
	 * @param array  $classes   Additional CSS classes.
	 * @param string $style     Inline CSS styles.
	 * @return string HTML output.
	 */
	private function wrap_output( $shortcode, $content, $classes = array(), $style = '' ) {
		$this->enqueue_assets();

		$base_class = 'aisales-widget aisales-' . str_replace( '_', '-', $shortcode );
		if ( ! empty( $classes ) ) {
			$base_class .= ' ' . implode( ' ', $classes );
		}

		$style_attr = '';
		if ( ! empty( $style ) ) {
			$style_attr = sprintf( ' style="%s"', esc_attr( $style ) );
		}

		return sprintf(
			'<div class="%s"%s>%s</div>',
			esc_attr( $base_class ),
			$style_attr,
			$content
		);
	}

	/* =========================================================================
	   SOCIAL PROOF SHORTCODES
	   ========================================================================= */

	/**
	 * Render [aisales_total_sold] shortcode
	 *
	 * Displays total units sold for a product.
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

	/* =========================================================================
	   PLACEHOLDER SHORTCODES (to be implemented)
	   ========================================================================= */

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
		global $wpdb;

		// Query to get rating distribution.
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
				$end_date = date( 'c', $parsed );
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
		$timer_html  = '<span class="aisales-cart-urgency__timer" data-end="' . esc_attr( date( 'c', $expires_at ) ) . '" ';
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

		$date_threshold = date( 'Y-m-d H:i:s', strtotime( $date_after ) );

		// Query for products with recent orders.
		// We'll get products that have been ordered recently, sorted by order count.
		global $wpdb;

		// Get product IDs from recent orders, ordered by frequency.
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
				absint( $atts['limit'] ) * 2 // Get extra to account for filtering.
			)
		);

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
			$viewed_products = explode( '|', wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) );
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
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

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
		$grid_class = 'aisales-product-grid__items';
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
				$rating = $product->get_average_rating();
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

	/**
	 * Parse bundle tiers string into an array
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
			$expiry = absint( $_COOKIE[ $cookie_name ] );
		}

		if ( $expiry <= 0 || $expiry < time() ) {
			$expiry = time() + ( $duration_minutes * 60 );
			setcookie( $cookie_name, (string) $expiry, $expiry, COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE[ $cookie_name ] = $expiry;
		}

		return $expiry;
	}
}
