<?php
/**
 * Widgets Page Controller
 *
 * Admin page for managing and documenting all AI Sales widgets/shortcodes.
 * Provides live previews, shortcode builders, and settings management.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widgets Page class
 */
class AISales_Widgets_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_Widgets_Page
	 */
	private static $instance = null;

	/**
	 * Widget definitions
	 *
	 * @var array
	 */
	private $widgets = array();

	/**
	 * Get instance
	 *
	 * @return AISales_Widgets_Page
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
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_aisales_toggle_widget', array( $this, 'ajax_toggle_widget' ) );
		add_action( 'wp_ajax_aisales_save_widget_settings', array( $this, 'ajax_save_widget_settings' ) );
		add_action( 'wp_ajax_aisales_save_widget_config', array( $this, 'ajax_save_widget_config' ) );
		add_action( 'wp_ajax_aisales_get_widget_config', array( $this, 'ajax_get_widget_config' ) );
	}

	/**
	 * Get widget definitions (lazy-loaded to defer translation calls until init)
	 *
	 * @return array
	 */
	public function get_widgets() {
		if ( empty( $this->widgets ) ) {
			$this->define_widgets();
		}
		return $this->widgets;
	}

	/**
	 * Define all available widgets
	 */
	private function define_widgets() {
		// Define position options for product page widgets.
		$product_positions = array(
			'below_price'       => __( 'Below price', 'stacksuite-sales-manager-for-woocommerce' ),
			'above_add_to_cart' => __( 'Above Add to Cart', 'stacksuite-sales-manager-for-woocommerce' ),
			'below_add_to_cart' => __( 'Below Add to Cart', 'stacksuite-sales-manager-for-woocommerce' ),
			'product_meta'      => __( 'Product meta area', 'stacksuite-sales-manager-for-woocommerce' ),
		);

		$this->widgets = array(
			// Social Proof Widgets.
			'total_sold'       => array(
				'name'        => __( 'Sales Counter', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Display total units sold for any product to build social proof.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_total_sold',
				'category'    => 'social_proof',
				'type'        => 'injectable',
				'positions'   => $product_positions,
				'default_pos' => 'below_price',
				'icon'        => 'dashicons-chart-bar',
				'attributes'  => array(
					'product_id' => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID (auto-detects on product pages)', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'format'     => array(
						'type'        => 'text',
						'default'     => '{count} sold',
						'description' => __( 'Display format. Use {count} as placeholder.', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'show_icon'  => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Show cart icon', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'show_icon'     => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Icon', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Display cart icon before the count', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'icon_style'    => array(
							'type'    => 'icons',
							'label'   => __( 'Icon Style', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'cart',
							'options' => array(
								'cart'  => 'dashicons-cart',
								'bag'   => 'dashicons-products',
								'chart' => 'dashicons-chart-bar',
								'fire'  => 'dashicons-performance',
							),
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Add custom classes for styling', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '#1a1a1a',
						),
						'bg_color'      => array(
							'type'    => 'color',
							'label'   => __( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for transparent', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'icon_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Icon Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '#f59e0b',
						),
					),
					'display'    => array(
						'format'        => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'         => __( 'Customize the message shown', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => '{count} sold',
							'placeholders' => array( '{count}' ),
						),
						'min_to_show'   => array(
							'type'    => 'number',
							'label'   => __( 'Minimum Sales to Display', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Hide widget if sales are below this number', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 1,
							'min'     => 0,
							'max'     => 1000,
						),
						'number_format' => array(
							'type'    => 'select',
							'label'   => __( 'Number Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'formatted',
							'options' => array(
								'formatted' => __( 'Formatted (1,234)', 'stacksuite-sales-manager-for-woocommerce' ),
								'compact'   => __( 'Compact (1.2K)', 'stacksuite-sales-manager-for-woocommerce' ),
								'raw'       => __( 'Raw (1234)', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
					),
					'behavior'   => array(
						'animate'       => array(
							'type'    => 'toggle',
							'label'   => __( 'Animate Count', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Animate number on page load', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => false,
						),
					),
					'advanced'   => array(
						'cache_duration'    => array(
							'type'    => 'select',
							'label'   => __( 'Cache Duration', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'How long to cache the sales count', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'global',
							'options' => array(
								'global' => __( 'Use global setting', 'stacksuite-sales-manager-for-woocommerce' ),
								'900'    => __( '15 minutes', 'stacksuite-sales-manager-for-woocommerce' ),
								'1800'   => __( '30 minutes', 'stacksuite-sales-manager-for-woocommerce' ),
								'3600'   => __( '1 hour', 'stacksuite-sales-manager-for-woocommerce' ),
								'86400'  => __( '24 hours', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'exclude_products'  => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'exclude_categories' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Categories', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated category slugs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-cart',
					'value' => '1,247',
					'label' => 'sold',
				),
			),
			'recent_purchase'  => array(
				'name'        => __( 'Recent Purchase Popups', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Auto-display recent purchase notifications as popups across your store.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_recent_purchase',
				'category'    => 'social_proof',
				'type'        => 'feature',
				'icon'        => 'dashicons-megaphone',
				'attributes'  => array(
					'limit'         => array(
						'type'        => 'number',
						'default'     => 5,
						'description' => __( 'Number of recent purchases to rotate', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'display'       => array(
						'type'        => 'select',
						'default'     => 'popup',
						'options'     => array( 'popup', 'inline' ),
						'description' => __( 'Display as popup toast or inline', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'show_location' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Show buyer location', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'position'      => array(
							'type'    => 'select',
							'label'   => __( 'Popup Position', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'bottom-left',
							'options' => array(
								'bottom-left'  => __( 'Bottom Left', 'stacksuite-sales-manager-for-woocommerce' ),
								'bottom-right' => __( 'Bottom Right', 'stacksuite-sales-manager-for-woocommerce' ),
								'top-left'     => __( 'Top Left', 'stacksuite-sales-manager-for-woocommerce' ),
								'top-right'    => __( 'Top Right', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'show_image'    => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Image', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'privacy_level' => array(
							'type'    => 'select',
							'label'   => __( 'Privacy Level', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'How to display customer information', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'first_city',
							'options' => array(
								'full'       => __( 'Full Name (John Smith)', 'stacksuite-sales-manager-for-woocommerce' ),
								'first_city' => __( 'First + City (John from NYC)', 'stacksuite-sales-manager-for-woocommerce' ),
								'anonymous'  => __( 'Anonymous (Someone from NYC)', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'show_location' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Location', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'limit'         => array(
							'type'    => 'number',
							'label'   => __( 'Purchases to Rotate', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Number of recent purchases to cycle through', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 5,
							'min'     => 1,
							'max'     => 20,
						),
					),
					'behavior'   => array(
						'interval'      => array(
							'type'    => 'range',
							'label'   => __( 'Display Interval', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Seconds between popups', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 8,
							'min'     => 3,
							'max'     => 30,
							'unit'    => 's',
						),
						'duration'      => array(
							'type'    => 'range',
							'label'   => __( 'Popup Duration', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'How long each popup stays visible', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 5,
							'min'     => 2,
							'max'     => 15,
							'unit'    => 's',
						),
						'delay_start'   => array(
							'type'    => 'range',
							'label'   => __( 'Initial Delay', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Wait time before first popup', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 3,
							'min'     => 0,
							'max'     => 30,
							'unit'    => 's',
						),
					),
					'advanced'   => array(
						'pages'            => array(
							'type'    => 'select',
							'label'   => __( 'Show On', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'all',
							'options' => array(
								'all'      => __( 'All Pages', 'stacksuite-sales-manager-for-woocommerce' ),
								'shop'     => __( 'Shop Pages Only', 'stacksuite-sales-manager-for-woocommerce' ),
								'product'  => __( 'Product Pages Only', 'stacksuite-sales-manager-for-woocommerce' ),
								'cart'     => __( 'Cart & Checkout', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'exclude_products' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'    => 'dashicons-admin-users',
					'name'    => 'Sarah',
					'location' => 'New York',
					'time'    => '2 min ago',
				),
			),
			'live_viewers'     => array(
				'name'        => __( 'Live Viewers', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Auto-display real-time visitor count on product pages.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_live_viewers',
				'category'    => 'social_proof',
				'type'        => 'feature',
				'icon'        => 'dashicons-visibility',
				'attributes'  => array(
					'product_id' => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID (auto-detects on product pages)', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'format'     => array(
						'type'        => 'text',
						'default'     => '{count} people viewing',
						'description' => __( 'Display format', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'show_pulse'    => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Pulse Animation', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'format'        => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => '{count} people viewing',
							'placeholders' => array( '{count}' ),
						),
						'min_to_show'   => array(
							'type'    => 'number',
							'label'   => __( 'Minimum Viewers to Display', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Hide widget if viewers are below this', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 1,
							'min'     => 0,
							'max'     => 100,
						),
					),
					'behavior'   => array(
						'refresh_interval' => array(
							'type'    => 'select',
							'label'   => __( 'Refresh Interval', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'How often to update the count', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '30',
							'options' => array(
								'10' => __( '10 seconds', 'stacksuite-sales-manager-for-woocommerce' ),
								'30' => __( '30 seconds', 'stacksuite-sales-manager-for-woocommerce' ),
								'60' => __( '1 minute', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
					),
					'advanced'   => array(),
				),
				'preview'     => array(
					'icon'  => 'dashicons-visibility',
					'value' => '24',
					'label' => 'people viewing',
				),
			),
			'stock_urgency'    => array(
				'name'        => __( 'Stock Urgency', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Display low stock warnings to create purchase urgency.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_stock_urgency',
				'category'    => 'social_proof',
				'type'        => 'injectable',
				'positions'   => $product_positions,
				'default_pos' => 'above_add_to_cart',
				'icon'        => 'dashicons-warning',
				'attributes'  => array(
					'threshold' => array(
						'type'        => 'number',
						'default'     => 10,
						'description' => __( 'Show warning when stock is at or below', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'format'    => array(
						'type'        => 'text',
						'default'     => 'Only {count} left!',
						'description' => __( 'Warning message format', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'show_icon'     => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Warning Icon', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'animation'     => array(
							'type'    => 'toggle',
							'label'   => __( 'Pulse Animation', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Animate for critical stock levels', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'threshold_low'      => array(
							'type'    => 'number',
							'label'   => __( 'Low Stock Threshold', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Show "low" warning at this level', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 1,
							'max'     => 100,
						),
						'threshold_warning'  => array(
							'type'    => 'number',
							'label'   => __( 'Warning Threshold', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Show "warning" at this level', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 5,
							'min'     => 1,
							'max'     => 50,
						),
						'threshold_critical' => array(
							'type'    => 'number',
							'label'   => __( 'Critical Threshold', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Show "critical" at this level', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 2,
							'min'     => 1,
							'max'     => 20,
						),
						'format_low'         => array(
							'type'         => 'text',
							'label'        => __( 'Low Stock Message', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Only {count} left in stock',
							'placeholders' => array( '{count}' ),
						),
						'format_warning'     => array(
							'type'         => 'text',
							'label'        => __( 'Warning Message', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Hurry! Only {count} left!',
							'placeholders' => array( '{count}' ),
						),
						'format_critical'    => array(
							'type'         => 'text',
							'label'        => __( 'Critical Message', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Almost gone! Only {count} left!',
							'placeholders' => array( '{count}' ),
						),
					),
					'behavior'   => array(),
					'advanced'   => array(
						'exclude_backorder' => array(
							'type'    => 'toggle',
							'label'   => __( 'Hide for Backorders', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Don\'t show for backorder products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'exclude_products'  => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'   => 'dashicons-warning',
					'value'  => 'Only 3 left!',
					'urgent' => true,
				),
			),
			'review_summary'   => array(
				'name'        => __( 'Review Summary', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Show aggregated review stats with star ratings and highlights.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_review_summary',
				'category'    => 'social_proof',
				'type'        => 'injectable',
				'positions'   => $product_positions,
				'default_pos' => 'below_price',
				'icon'        => 'dashicons-star-filled',
				'attributes'  => array(
					'product_id'   => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'show_count'   => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Show review count', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'show_breakdown' => array(
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Show rating breakdown', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'star_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Star Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '#f59e0b',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Add custom classes for styling', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'show_count'     => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Review Count', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Display number of reviews next to stars', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_breakdown' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Rating Breakdown', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Display bar chart of rating distribution', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => false,
						),
						'min_reviews'    => array(
							'type'    => 'number',
							'label'   => __( 'Minimum Reviews to Display', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Hide widget if reviews are below this number', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 1,
							'min'     => 0,
							'max'     => 100,
						),
					),
					'behavior'   => array(),
					'advanced'   => array(
						'exclude_products' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'   => 'dashicons-star-filled',
					'rating' => 4.8,
					'count'  => 127,
				),
			),

			// Conversion Widgets.
			'shipping_bar'     => array(
				'name'        => __( 'Free Shipping Bar', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Auto-display progress bar on cart showing distance to free shipping.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_shipping_bar',
				'category'    => 'conversion',
				'type'        => 'feature',
				'icon'        => 'dashicons-car',
				'attributes'  => array(
					'threshold' => array(
						'type'        => 'number',
						'default'     => 0,
						'description' => __( 'Free shipping minimum (0 = use WooCommerce setting)', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'message'   => array(
						'type'        => 'text',
						'default'     => 'Add {amount} more for FREE shipping!',
						'description' => __( 'Message when threshold not met', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'success'   => array(
						'type'        => 'text',
						'default'     => 'You qualify for FREE shipping!',
						'description' => __( 'Message when threshold met', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'preview'     => array(
					'icon'     => 'dashicons-car',
					'progress' => 65,
					'message'  => 'Add $17.50 more for FREE shipping!',
				),
			),
			'countdown'        => array(
				'name'        => __( 'Countdown Timer', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Create urgency with sale or offer expiration countdown.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_countdown',
				'category'    => 'conversion',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-clock',
				'attributes'  => array(
					'product_id' => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID (auto-detects on product pages)', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'end_date'   => array(
						'type'        => 'datetime',
						'default'     => '',
						'description' => __( 'Countdown end date/time', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'style'      => array(
						'type'        => 'select',
						'default'     => 'inline',
						'options'     => array( 'inline', 'boxes' ),
						'description' => __( 'Timer display style', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'style'         => array(
							'type'    => 'select',
							'label'   => __( 'Display Style', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'inline',
							'options' => array(
								'inline' => __( 'Inline', 'stacksuite-sales-manager-for-woocommerce' ),
								'boxes'  => __( 'Boxes', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'bg_color'      => array(
							'type'    => 'color',
							'label'   => __( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'accent_color'  => array(
							'type'    => 'color',
							'label'   => __( 'Accent Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '#dc2626',
						),
					),
					'display'    => array(
						'format'       => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'         => __( 'Use {timer} placeholder for timer output', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Sale ends in {timer}',
							'placeholders' => array( '{timer}' ),
						),
						'show_days'    => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Days', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_hours'   => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Hours', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_minutes' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Minutes', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_seconds' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Seconds', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'end_action'   => array(
							'type'    => 'select',
							'label'   => __( 'When Countdown Ends', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'hide',
							'options' => array(
								'hide' => __( 'Hide widget', 'stacksuite-sales-manager-for-woocommerce' ),
								'show_ended' => __( 'Show ended state', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
					),
					'advanced'   => array(
						'exclude_products' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'    => 'dashicons-clock',
					'hours'   => 23,
					'minutes' => 45,
					'seconds' => 12,
				),
			),
			'price_drop'       => array(
				'name'        => __( 'Price Drop Badge', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Highlight price reductions with eye-catching badges.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_price_drop',
				'category'    => 'conversion',
				'type'        => 'injectable',
				'positions'   => $product_positions,
				'default_pos' => 'below_price',
				'icon'        => 'dashicons-tag',
				'attributes'  => array(
					'product_id'      => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'show_percentage' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Show percentage saved', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'style'           => array(
						'type'        => 'select',
						'default'     => 'badge',
						'options'     => array( 'badge', 'ribbon', 'inline' ),
						'description' => __( 'Badge display style', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'style'         => array(
							'type'    => 'select',
							'label'   => __( 'Display Style', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'badge',
							'options' => array(
								'badge'  => __( 'Badge (icon + text)', 'stacksuite-sales-manager-for-woocommerce' ),
								'ribbon' => __( 'Ribbon (compact %)', 'stacksuite-sales-manager-for-woocommerce' ),
								'inline' => __( 'Inline (text only)', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Add custom classes for styling', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'bg_color'      => array(
							'type'    => 'color',
							'label'   => __( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'format'          => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'         => __( 'Customize the badge message (badge style only)', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Save {percent}!',
							'placeholders' => array( '{percent}', '{amount}' ),
						),
						'show_percentage' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Percentage', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Display discount as percentage', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_amount'     => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Amount', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Display savings amount in currency (inline style)', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => false,
						),
						'min_discount'    => array(
							'type'    => 'number',
							'label'   => __( 'Minimum Discount %', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Only show badge if discount is at least this percentage', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 5,
							'min'     => 0,
							'max'     => 100,
						),
					),
					'advanced'   => array(
						'exclude_products' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'       => 'dashicons-tag',
					'original'   => '$99',
					'sale'       => '$79',
					'percentage' => '20%',
				),
			),
			'bundle_savings'   => array(
				'name'        => __( 'Bundle Savings', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Show multi-buy discounts and bundle savings calculator.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_bundle_savings',
				'category'    => 'conversion',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-products',
				'attributes'  => array(
					'product_id' => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'format'     => array(
						'type'        => 'text',
						'default'     => 'Buy {qty}, save {amount}!',
						'description' => __( 'Savings message format', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'bg_color'      => array(
							'type'    => 'color',
							'label'   => __( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'format'          => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'         => __( 'Use {qty}, {amount}, {percent} placeholders', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Buy {qty}, save {amount} ({percent}%)',
							'placeholders' => array( '{qty}', '{amount}', '{percent}' ),
						),
						'tiers'           => array(
							'type'    => 'textarea',
							'label'   => __( 'Discount Tiers', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Enter one per line: qty:percent (e.g. 2:5)', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => "2:5\n3:10\n4:15",
						),
					),
					'advanced'   => array(
						'exclude_products' => array(
							'type'    => 'text',
							'label'   => __( 'Exclude Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Comma-separated product IDs to exclude', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
				),
				'preview'     => array(
					'icon'    => 'dashicons-products',
					'message' => 'Buy 3, save $15!',
				),
			),
			'cart_urgency'     => array(
				'name'        => __( 'Cart Urgency', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Show cart reservation timer to prevent abandonment.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_cart_urgency',
				'category'    => 'conversion',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-backup',
				'attributes'  => array(
					'duration' => array(
						'type'        => 'number',
						'default'     => 15,
						'description' => __( 'Reservation time in minutes', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'message'  => array(
						'type'        => 'text',
						'default'     => 'Items reserved for {time}',
						'description' => __( 'Urgency message', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'    => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'margin_bottom' => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 10,
							'min'     => 0,
							'max'     => 50,
							'unit'    => 'px',
						),
						'custom_class'  => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'text_color'    => array(
							'type'    => 'color',
							'label'   => __( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'bg_color'      => array(
							'type'    => 'color',
							'label'   => __( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Leave empty for default', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'accent_color'  => array(
							'type'    => 'color',
							'label'   => __( 'Accent Color', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '#e65100',
						),
					),
					'display'    => array(
						'duration'     => array(
							'type'    => 'number',
							'label'   => __( 'Reservation Time (minutes)', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 15,
							'min'     => 1,
							'max'     => 120,
						),
						'format'       => array(
							'type'         => 'text',
							'label'        => __( 'Display Format', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'         => __( 'Use {time} placeholder for timer', 'stacksuite-sales-manager-for-woocommerce' ),
							'default'      => 'Items reserved for {time}',
							'placeholders' => array( '{time}' ),
						),
						'show_hours'   => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Hours', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_minutes' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Minutes', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_seconds' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Seconds', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'end_action'   => array(
							'type'    => 'select',
							'label'   => __( 'When Timer Ends', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'hide',
							'options' => array(
								'hide' => __( 'Hide widget', 'stacksuite-sales-manager-for-woocommerce' ),
								'show_ended' => __( 'Show ended state', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
					),
				),
				'preview'     => array(
					'icon'    => 'dashicons-backup',
					'message' => 'Items reserved for 14:32',
				),
			),

			// Discovery Widgets.
			'bestsellers'      => array(
				'name'        => __( 'Best Sellers', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Display top-selling products to guide purchase decisions.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_bestsellers',
				'category'    => 'discovery',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-awards',
				'attributes'  => array(
					'limit'    => array(
						'type'        => 'number',
						'default'     => 4,
						'description' => __( 'Number of products', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'category' => array(
						'type'        => 'text',
						'default'     => '',
						'description' => __( 'Filter by category slug', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'layout'   => array(
						'type'        => 'select',
						'default'     => 'grid',
						'options'     => array( 'grid', 'carousel', 'list' ),
						'description' => __( 'Display layout', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'         => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'margin_bottom'      => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'columns'            => array(
							'type'    => 'select',
							'label'   => __( 'Columns', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'options' => array(
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							),
						),
						'layout'             => array(
							'type'    => 'select',
							'label'   => __( 'Layout', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'grid',
							'options' => array(
								'grid' => __( 'Grid', 'stacksuite-sales-manager-for-woocommerce' ),
								'list' => __( 'List', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'       => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'limit'              => array(
							'type'    => 'number',
							'label'   => __( 'Number of Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'min'     => 1,
							'max'     => 24,
						),
						'category'           => array(
							'type'    => 'text',
							'label'   => __( 'Category Filter', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Category slug to filter products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'show_section_title' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'title_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Best Sellers',
						),
						'show_title'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Titles', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_price'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Prices', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_rating'        => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Ratings', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_badge'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Badge', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'badge_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Badge Text', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Best Seller',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-awards',
					'type'  => 'products',
					'count' => 4,
				),
			),
			'trending'         => array(
				'name'        => __( 'Trending Now', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Show products with high recent sales velocity.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_trending',
				'category'    => 'discovery',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-chart-line',
				'attributes'  => array(
					'limit'  => array(
						'type'        => 'number',
						'default'     => 4,
						'description' => __( 'Number of products', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'period' => array(
						'type'        => 'select',
						'default'     => '7days',
						'options'     => array( '24hours', '7days', '30days' ),
						'description' => __( 'Trending period', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'         => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'margin_bottom'      => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'columns'            => array(
							'type'    => 'select',
							'label'   => __( 'Columns', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'options' => array(
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							),
						),
						'layout'             => array(
							'type'    => 'select',
							'label'   => __( 'Layout', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'grid',
							'options' => array(
								'grid' => __( 'Grid', 'stacksuite-sales-manager-for-woocommerce' ),
								'list' => __( 'List', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'       => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'limit'              => array(
							'type'    => 'number',
							'label'   => __( 'Number of Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'min'     => 1,
							'max'     => 24,
						),
						'period'             => array(
							'type'    => 'select',
							'label'   => __( 'Trending Period', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Time period to calculate trending products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '7days',
							'options' => array(
								'24hours' => __( 'Last 24 hours', 'stacksuite-sales-manager-for-woocommerce' ),
								'7days'   => __( 'Last 7 days', 'stacksuite-sales-manager-for-woocommerce' ),
								'30days'  => __( 'Last 30 days', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'category'           => array(
							'type'    => 'text',
							'label'   => __( 'Category Filter', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Category slug to filter products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'show_section_title' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'title_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Trending Now',
						),
						'show_title'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Titles', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_price'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Prices', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_rating'        => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Ratings', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_badge'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Badge', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'badge_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Badge Text', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Trending',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-chart-line',
					'type'  => 'products',
					'count' => 4,
					'badge' => 'Trending',
				),
			),
			'recently_viewed'  => array(
				'name'        => __( 'Recently Viewed', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Show products the customer has recently browsed.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_recently_viewed',
				'category'    => 'discovery',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-backup',
				'attributes'  => array(
					'limit'           => array(
						'type'        => 'number',
						'default'     => 4,
						'description' => __( 'Number of products', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'exclude_current' => array(
						'type'        => 'boolean',
						'default'     => true,
						'description' => __( 'Exclude current product', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'         => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'margin_bottom'      => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'columns'            => array(
							'type'    => 'select',
							'label'   => __( 'Columns', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'options' => array(
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							),
						),
						'layout'             => array(
							'type'    => 'select',
							'label'   => __( 'Layout', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'grid',
							'options' => array(
								'grid' => __( 'Grid', 'stacksuite-sales-manager-for-woocommerce' ),
								'list' => __( 'List', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'       => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'limit'              => array(
							'type'    => 'number',
							'label'   => __( 'Number of Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'min'     => 1,
							'max'     => 24,
						),
						'exclude_current'    => array(
							'type'    => 'toggle',
							'label'   => __( 'Exclude Current Product', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_section_title' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'title_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Recently Viewed',
						),
						'show_title'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Titles', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_price'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Prices', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_rating'        => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Ratings', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_badge'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Badge', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'badge_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Badge Text', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Viewed',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-backup',
					'type'  => 'products',
					'count' => 4,
				),
			),
			'bought_together'  => array(
				'name'        => __( 'Bought Together', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Display products frequently purchased together.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_bought_together',
				'category'    => 'discovery',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-networking',
				'attributes'  => array(
					'product_id' => array(
						'type'        => 'number',
						'default'     => '',
						'description' => __( 'Product ID', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'limit'      => array(
						'type'        => 'number',
						'default'     => 3,
						'description' => __( 'Number of suggestions', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'         => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'margin_bottom'      => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'columns'            => array(
							'type'    => 'select',
							'label'   => __( 'Columns', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'options' => array(
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							),
						),
						'layout'             => array(
							'type'    => 'select',
							'label'   => __( 'Layout', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'grid',
							'options' => array(
								'grid' => __( 'Grid', 'stacksuite-sales-manager-for-woocommerce' ),
								'list' => __( 'List', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'       => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'limit'              => array(
							'type'    => 'number',
							'label'   => __( 'Number of Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 3,
							'min'     => 1,
							'max'     => 24,
						),
						'exclude_current'    => array(
							'type'    => 'toggle',
							'label'   => __( 'Exclude Current Product', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_section_title' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'title_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Frequently Bought Together',
						),
						'show_title'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Titles', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_price'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Prices', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_rating'        => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Ratings', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_badge'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Badge', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'badge_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Badge Text', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'Bundle',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-networking',
					'type'  => 'products',
					'count' => 3,
					'label' => 'Frequently bought together',
				),
			),
			'new_arrivals'     => array(
				'name'        => __( 'New Arrivals', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Showcase recently added products to your store.', 'stacksuite-sales-manager-for-woocommerce' ),
				'shortcode'   => 'aisales_new_arrivals',
				'category'    => 'discovery',
				'type'        => 'shortcode',
				'icon'        => 'dashicons-star-empty',
				'attributes'  => array(
					'limit'    => array(
						'type'        => 'number',
						'default'     => 4,
						'description' => __( 'Number of products', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'days'     => array(
						'type'        => 'number',
						'default'     => 30,
						'description' => __( 'Products added within X days', 'stacksuite-sales-manager-for-woocommerce' ),
					),
					'category' => array(
						'type'        => 'text',
						'default'     => '',
						'description' => __( 'Filter by category slug', 'stacksuite-sales-manager-for-woocommerce' ),
					),
				),
				'settings'    => array(
					'appearance' => array(
						'margin_top'         => array(
							'type'    => 'range',
							'label'   => __( 'Margin Top', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'margin_bottom'      => array(
							'type'    => 'range',
							'label'   => __( 'Margin Bottom', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 20,
							'min'     => 0,
							'max'     => 100,
							'unit'    => 'px',
						),
						'columns'            => array(
							'type'    => 'select',
							'label'   => __( 'Columns', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'options' => array(
								2 => '2',
								3 => '3',
								4 => '4',
								5 => '5',
								6 => '6',
							),
						),
						'layout'             => array(
							'type'    => 'select',
							'label'   => __( 'Layout', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'grid',
							'options' => array(
								'grid' => __( 'Grid', 'stacksuite-sales-manager-for-woocommerce' ),
								'list' => __( 'List', 'stacksuite-sales-manager-for-woocommerce' ),
							),
						),
						'custom_class'       => array(
							'type'    => 'text',
							'label'   => __( 'Custom CSS Class', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
					),
					'display'    => array(
						'limit'              => array(
							'type'    => 'number',
							'label'   => __( 'Number of Products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 4,
							'min'     => 1,
							'max'     => 24,
						),
						'days'               => array(
							'type'    => 'number',
							'label'   => __( 'Days Threshold', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Show products added within this many days', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 30,
							'min'     => 1,
							'max'     => 365,
						),
						'category'           => array(
							'type'    => 'text',
							'label'   => __( 'Category Filter', 'stacksuite-sales-manager-for-woocommerce' ),
							'help'    => __( 'Category slug to filter products', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => '',
						),
						'show_section_title' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'title_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Section Title', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'New Arrivals',
						),
						'show_title'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Product Titles', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_price'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Prices', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_rating'        => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Ratings', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'show_badge'         => array(
							'type'    => 'toggle',
							'label'   => __( 'Show Badge', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => true,
						),
						'badge_text'         => array(
							'type'    => 'text',
							'label'   => __( 'Badge Text', 'stacksuite-sales-manager-for-woocommerce' ),
							'default' => 'New',
						),
					),
				),
				'preview'     => array(
					'icon'  => 'dashicons-star-empty',
					'type'  => 'products',
					'count' => 4,
					'badge' => 'New',
				),
			),
		);
	}

	/**
	 * Get widgets by category
	 *
	 * @param string $category Category slug.
	 * @return array
	 */
	public function get_widgets_by_category( $category ) {
		return array_filter(
			$this->get_widgets(),
			function( $widget ) use ( $category ) {
				return $widget['category'] === $category;
			}
		);
	}

	/**
	 * Get widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return array(
			'social_proof' => array(
				'name'        => __( 'Social Proof', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Build trust and create urgency with social proof elements', 'stacksuite-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-groups',
			),
			'conversion'   => array(
				'name'        => __( 'Conversion', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Optimize for sales with conversion-focused widgets', 'stacksuite-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-chart-area',
			),
			'discovery'    => array(
				'name'        => __( 'Discovery', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Help customers discover relevant products', 'stacksuite-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-search',
			),
		);
	}

	/**
	 * Get widget settings
	 *
	 * @return array
	 */
	public function get_settings() {
		// Build default positions from widget definitions.
		$default_positions = array();
		foreach ( $this->get_widgets() as $key => $widget ) {
			if ( 'injectable' === $widget['type'] && ! empty( $widget['default_pos'] ) ) {
				$default_positions[ $key ] = $widget['default_pos'];
			}
		}

		$defaults = array(
			'enabled_widgets'  => array(), // Start with none enabled for injectable widgets.
			'widget_positions' => $default_positions,
			'cache_duration'   => 3600,
			'styling_mode'     => 'inherit',
			'colors'           => array(
				'primary' => '#2271b1',
				'success' => '#00a32a',
				'urgency' => '#d63638',
				'text'    => '#1d2327',
			),
			'social_proof'     => array(
				'privacy_level'  => 'first_city',
				'popup_position' => 'bottom-left',
				'popup_duration' => 5,
			),
			'conversion'       => array(
				'shipping_threshold' => 0,
				'stock_urgency_at'   => 10,
			),
		);

		$saved = get_option( 'aisales_widgets_settings', array() );

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Add submenu page under StackSuite Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'Widgets', 'stacksuite-sales-manager-for-woocommerce' ),
			__( 'Widgets', 'stacksuite-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-widgets',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-widgets' !== $hook ) {
			return;
		}

		// CSS version.
		$css_file    = AISALES_PLUGIN_DIR . 'assets/css/widgets-page.css';
		$css_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $css_file ) )
			? filemtime( $css_file )
			: AISALES_VERSION;

		// Enqueue page styles.
		wp_enqueue_style(
			'aisales-widgets-page',
			AISALES_PLUGIN_URL . 'assets/css/widgets-page.css',
			array( 'aisales-admin' ),
			$css_version
		);

		// JS version.
		$js_file    = AISALES_PLUGIN_DIR . 'assets/js/widgets-page.js';
		$js_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $js_file ) )
			? filemtime( $js_file )
			: AISALES_VERSION;

		// Enqueue page script.
		wp_enqueue_script(
			'aisales-widgets-page',
			AISALES_PLUGIN_URL . 'assets/js/widgets-page.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Localize script.
		wp_localize_script(
			'aisales-widgets-page',
			'aisalesWidgets',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'aisales_nonce' ),
				'widgets'    => $this->get_widgets(),
				'categories' => $this->get_categories(),
				'settings'   => $this->get_settings(),
				'i18n'       => array(
					'copied'         => __( 'Copied!', 'stacksuite-sales-manager-for-woocommerce' ),
					'copyShortcode'  => __( 'Copy Shortcode', 'stacksuite-sales-manager-for-woocommerce' ),
					'openBuilder'    => __( 'Customize', 'stacksuite-sales-manager-for-woocommerce' ),
					'enabled'        => __( 'Enabled', 'stacksuite-sales-manager-for-woocommerce' ),
					'disabled'       => __( 'Disabled', 'stacksuite-sales-manager-for-woocommerce' ),
					'saving'         => __( 'Saving...', 'stacksuite-sales-manager-for-woocommerce' ),
					'saved'          => __( 'Settings saved!', 'stacksuite-sales-manager-for-woocommerce' ),
					'error'          => __( 'Error saving settings', 'stacksuite-sales-manager-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Render the page
	 */
	public function render_page() {
		$aisales_settings   = $this->get_settings();
		$aisales_categories = $this->get_categories();

		include AISALES_PLUGIN_DIR . 'templates/admin-widgets-page.php';
	}

	/**
	 * AJAX handler for toggling widget enabled state
	 */
	public function ajax_toggle_widget() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$widget   = isset( $_POST['widget'] ) ? sanitize_key( $_POST['widget'] ) : '';
		$enabled  = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
		$position = isset( $_POST['position'] ) ? sanitize_key( $_POST['position'] ) : '';

		$widgets = $this->get_widgets();
		if ( empty( $widget ) || ! isset( $widgets[ $widget ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid widget.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$settings = $this->get_settings();

		if ( $enabled ) {
			// Add to enabled widgets if not already there.
			if ( ! in_array( $widget, $settings['enabled_widgets'], true ) ) {
				$settings['enabled_widgets'][] = $widget;
			}
		} else {
			// Remove from enabled widgets.
			$settings['enabled_widgets'] = array_values(
				array_diff( $settings['enabled_widgets'], array( $widget ) )
			);
		}

		// Save position if provided and widget is injectable.
		if ( ! empty( $position ) && 'injectable' === $widgets[ $widget ]['type'] ) {
			// Validate position is allowed for this widget.
			if ( isset( $widgets[ $widget ]['positions'][ $position ] ) ) {
				if ( ! isset( $settings['widget_positions'] ) ) {
					$settings['widget_positions'] = array();
				}
				$settings['widget_positions'][ $widget ] = $position;
			}
		}

		update_option( 'aisales_widgets_settings', $settings );

		// Count enabled injectable/feature widgets for badge.
		$enabled_count = 0;
		foreach ( $settings['enabled_widgets'] as $w ) {
			if ( isset( $widgets[ $w ] ) && in_array( $widgets[ $w ]['type'], array( 'injectable', 'feature' ), true ) ) {
				$enabled_count++;
			}
		}

		wp_send_json_success(
			array(
				'message'       => $enabled
					? __( 'Widget enabled.', 'stacksuite-sales-manager-for-woocommerce' )
					: __( 'Widget disabled.', 'stacksuite-sales-manager-for-woocommerce' ),
				'enabled_count' => $enabled_count,
			)
		);
	}

	/**
	 * AJAX handler for saving widget settings
	 */
	public function ajax_save_widget_settings() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$settings_json = isset( $_POST['settings'] ) ? sanitize_text_field( wp_unslash( $_POST['settings'] ) ) : '';
		$new_settings  = json_decode( $settings_json, true );

		if ( ! is_array( $new_settings ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		// Get current settings to preserve enabled_widgets.
		$current_settings = $this->get_settings();

		// Sanitize and merge settings.
		$sanitized = array(
			'enabled_widgets'  => $current_settings['enabled_widgets'], // Preserve enabled widgets.
			'widget_positions' => isset( $current_settings['widget_positions'] ) ? $current_settings['widget_positions'] : array(), // Preserve widget positions.
			'cache_duration'  => isset( $new_settings['cache_duration'] )
				? absint( $new_settings['cache_duration'] )
				: 3600,
			'styling_mode'    => isset( $new_settings['styling_mode'] ) && in_array( $new_settings['styling_mode'], array( 'inherit', 'custom' ), true )
				? $new_settings['styling_mode']
				: 'inherit',
			'colors'          => array(
				'primary' => isset( $new_settings['colors']['primary'] )
					? sanitize_hex_color( $new_settings['colors']['primary'] )
					: '#2271b1',
				'success' => isset( $new_settings['colors']['success'] )
					? sanitize_hex_color( $new_settings['colors']['success'] )
					: '#00a32a',
				'urgency' => isset( $new_settings['colors']['urgency'] )
					? sanitize_hex_color( $new_settings['colors']['urgency'] )
					: '#d63638',
				'text'    => isset( $new_settings['colors']['text'] )
					? sanitize_hex_color( $new_settings['colors']['text'] )
					: '#1d2327',
			),
			'social_proof'    => array(
				'privacy_level'  => isset( $new_settings['social_proof']['privacy_level'] ) && in_array( $new_settings['social_proof']['privacy_level'], array( 'full', 'first_city', 'city_only', 'anonymous' ), true )
					? $new_settings['social_proof']['privacy_level']
					: 'first_city',
				'popup_position' => isset( $new_settings['social_proof']['popup_position'] ) && in_array( $new_settings['social_proof']['popup_position'], array( 'bottom-left', 'bottom-right', 'top-left', 'top-right' ), true )
					? $new_settings['social_proof']['popup_position']
					: 'bottom-left',
				'popup_duration' => isset( $new_settings['social_proof']['popup_duration'] )
					? min( 15, max( 1, absint( $new_settings['social_proof']['popup_duration'] ) ) )
					: 5,
			),
			'conversion'      => array(
				'shipping_threshold' => isset( $new_settings['conversion']['shipping_threshold'] )
					? floatval( $new_settings['conversion']['shipping_threshold'] )
					: 0,
				'stock_urgency_at'   => isset( $new_settings['conversion']['stock_urgency_at'] )
					? absint( $new_settings['conversion']['stock_urgency_at'] )
					: 10,
			),
		);

		update_option( 'aisales_widgets_settings', $sanitized );

		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully.', 'stacksuite-sales-manager-for-woocommerce' ),
			)
		);
	}

	/**
	 * AJAX handler for getting per-widget configuration
	 */
	public function ajax_get_widget_config() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$widget_key = isset( $_POST['widget'] ) ? sanitize_key( $_POST['widget'] ) : '';
		$widgets    = $this->get_widgets();

		if ( empty( $widget_key ) || ! isset( $widgets[ $widget_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid widget.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$widget = $widgets[ $widget_key ];

		// Get saved config for this widget.
		$all_config   = get_option( 'aisales_widget_config', array() );
		$saved_config = isset( $all_config[ $widget_key ] ) ? $all_config[ $widget_key ] : array();

		// Build defaults from widget settings schema.
		$defaults = array();
		if ( ! empty( $widget['settings'] ) ) {
			foreach ( $widget['settings'] as $panel => $fields ) {
				foreach ( $fields as $field_key => $field ) {
					if ( isset( $field['default'] ) ) {
						$defaults[ $field_key ] = $field['default'];
					}
				}
			}
		}

		// Merge saved with defaults.
		$config = wp_parse_args( $saved_config, $defaults );

		wp_send_json_success(
			array(
				'widget' => $widget_key,
				'config' => $config,
				'schema' => isset( $widget['settings'] ) ? $widget['settings'] : array(),
			)
		);
	}

	/**
	 * AJAX handler for saving per-widget configuration
	 */
	public function ajax_save_widget_config() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$widget_key  = isset( $_POST['widget'] ) ? sanitize_key( $_POST['widget'] ) : '';
		$config_json = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '';
		$new_config  = json_decode( $config_json, true );

		$widgets = $this->get_widgets();

		if ( empty( $widget_key ) || ! isset( $widgets[ $widget_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid widget.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		if ( ! is_array( $new_config ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid configuration data.', 'stacksuite-sales-manager-for-woocommerce' ) ) );
		}

		$widget = $widgets[ $widget_key ];

		// Sanitize config based on schema.
		$sanitized = $this->sanitize_widget_config( $new_config, $widget );

		// Get all widget configs and update this one.
		$all_config               = get_option( 'aisales_widget_config', array() );
		$all_config[ $widget_key ] = $sanitized;

		update_option( 'aisales_widget_config', $all_config );

		wp_send_json_success(
			array(
				'message' => __( 'Widget settings saved.', 'stacksuite-sales-manager-for-woocommerce' ),
				'config'  => $sanitized,
			)
		);
	}

	/**
	 * Sanitize widget configuration based on its settings schema
	 *
	 * @param array $config Raw config data.
	 * @param array $widget Widget definition.
	 * @return array Sanitized config.
	 */
	private function sanitize_widget_config( $config, $widget ) {
		$sanitized = array();

		if ( empty( $widget['settings'] ) ) {
			return $sanitized;
		}

		foreach ( $widget['settings'] as $panel => $fields ) {
			foreach ( $fields as $field_key => $field ) {
				if ( ! isset( $config[ $field_key ] ) ) {
					continue;
				}

				$value = $config[ $field_key ];

				switch ( $field['type'] ) {
					case 'toggle':
						$sanitized[ $field_key ] = (bool) $value;
						break;

					case 'number':
					case 'range':
						$sanitized[ $field_key ] = absint( $value );
						// Clamp to min/max if defined.
						if ( isset( $field['min'] ) && $sanitized[ $field_key ] < $field['min'] ) {
							$sanitized[ $field_key ] = $field['min'];
						}
						if ( isset( $field['max'] ) && $sanitized[ $field_key ] > $field['max'] ) {
							$sanitized[ $field_key ] = $field['max'];
						}
						break;

					case 'select':
						// Validate against allowed options.
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							$allowed = array_keys( $field['options'] );
							if ( in_array( $value, $allowed, true ) ) {
								$sanitized[ $field_key ] = $value;
							} else {
								$sanitized[ $field_key ] = isset( $field['default'] ) ? $field['default'] : '';
							}
						} else {
							$sanitized[ $field_key ] = sanitize_text_field( $value );
						}
						break;

					case 'icons':
						// Validate against allowed icon options.
						if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
							$allowed = array_keys( $field['options'] );
							if ( in_array( $value, $allowed, true ) ) {
								$sanitized[ $field_key ] = $value;
							} else {
								$sanitized[ $field_key ] = isset( $field['default'] ) ? $field['default'] : '';
							}
						}
						break;

					case 'color':
						$sanitized[ $field_key ] = sanitize_hex_color( $value );
						break;

					case 'text':
					case 'textarea':
					default:
						$sanitized[ $field_key ] = sanitize_text_field( $value );
						break;
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Get widget configuration for frontend use
	 *
	 * @param string $widget_key Widget key.
	 * @return array
	 */
	public function get_widget_config( $widget_key ) {
		$widgets = $this->get_widgets();

		if ( ! isset( $widgets[ $widget_key ] ) ) {
			return array();
		}

		$widget = $widgets[ $widget_key ];

		// Build defaults from widget settings schema.
		$defaults = array();
		if ( ! empty( $widget['settings'] ) ) {
			foreach ( $widget['settings'] as $panel => $fields ) {
				foreach ( $fields as $field_key => $field ) {
					if ( isset( $field['default'] ) ) {
						$defaults[ $field_key ] = $field['default'];
					}
				}
			}
		}

		// Get saved config.
		$all_config   = get_option( 'aisales_widget_config', array() );
		$saved_config = isset( $all_config[ $widget_key ] ) ? $all_config[ $widget_key ] : array();

		return wp_parse_args( $saved_config, $defaults );
	}
}
