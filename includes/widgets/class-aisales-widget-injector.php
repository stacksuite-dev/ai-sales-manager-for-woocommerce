<?php
/**
 * Widget Injector
 *
 * Automatically injects enabled widgets into WooCommerce pages
 * at the positions configured by the user.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget Injector class
 */
class AISales_Widget_Injector {

	/**
	 * Single instance
	 *
	 * @var AISales_Widget_Injector
	 */
	private static $instance = null;

	/**
	 * Widget settings
	 *
	 * @var array
	 */
	private $settings = null;

	/**
	 * Position to hook mapping
	 *
	 * @var array
	 */
	private $position_hooks = array(
		'below_price'       => array(
			'hook'     => 'woocommerce_single_product_summary',
			'priority' => 15,
		),
		'above_add_to_cart' => array(
			'hook'     => 'woocommerce_single_product_summary',
			'priority' => 25,
		),
		'below_add_to_cart' => array(
			'hook'     => 'woocommerce_single_product_summary',
			'priority' => 35,
		),
		'product_meta'      => array(
			'hook'     => 'woocommerce_product_meta_end',
			'priority' => 10,
		),
	);

	/**
	 * Get instance
	 *
	 * @return AISales_Widget_Injector
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
		// Only run on frontend, not in admin.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Hook into template_redirect to set up injections after we know what page we're on.
		add_action( 'template_redirect', array( $this, 'setup_injections' ) );
	}

	/**
	 * Get widget settings
	 *
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( 'aisales_widgets_settings', array() );
		}
		return $this->settings;
	}

	/**
	 * Check if a widget is enabled
	 *
	 * @param string $widget_key Widget key.
	 * @return bool
	 */
	private function is_widget_enabled( $widget_key ) {
		$settings = $this->get_settings();
		return isset( $settings['enabled_widgets'] ) && in_array( $widget_key, $settings['enabled_widgets'], true );
	}

	/**
	 * Get widget position
	 *
	 * @param string $widget_key Widget key.
	 * @param string $default    Default position.
	 * @return string
	 */
	private function get_widget_position( $widget_key, $default = 'below_price' ) {
		$settings = $this->get_settings();
		if ( isset( $settings['widget_positions'][ $widget_key ] ) ) {
			return $settings['widget_positions'][ $widget_key ];
		}
		return $default;
	}

	/**
	 * Set up widget injections based on current page
	 */
	public function setup_injections() {
		$this->maybe_enable_debug();

		if ( is_product() ) {
			$this->setup_product_injections();
		}

		if ( is_cart() || is_checkout() ) {
			$this->setup_cart_injections();
		}
	}

	/**
	 * Enable debug output
	 */
	private function maybe_enable_debug() {
		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		if ( ! $debug ) {
			return;
		}

		$settings = $this->get_settings();
		add_action( 'wp_footer', function() use ( $settings ) {
			echo "\n<!-- AISALES DEBUG:\n";
			echo 'enabled_widgets: ' . esc_html( print_r( isset( $settings['enabled_widgets'] ) ? $settings['enabled_widgets'] : 'NOT SET', true ) ) . "\n";
			echo 'widget_positions: ' . esc_html( print_r( isset( $settings['widget_positions'] ) ? $settings['widget_positions'] : 'NOT SET', true ) ) . "\n";
			global $product;
			if ( $product ) {
				echo 'product_id: ' . esc_html( $product->get_id() ) . "\n";
				echo 'total_sales: ' . esc_html( $product->get_total_sales() ) . "\n";
			}
			echo "-->\n";
		} );
	}

	/**
	 * Set up product page injections
	 */
	private function setup_product_injections() {
		// Collect widgets to inject at each position.
		$injections = array();

		// Check each injectable widget.
		$injectable_widgets = array(
			'total_sold'     => 'below_price',
			'stock_urgency'  => 'above_add_to_cart',
			'review_summary' => 'below_price',
			'price_drop'     => 'below_price',
			'live_viewers'   => 'below_price',
		);

		foreach ( $injectable_widgets as $widget_key => $default_position ) {
			if ( ! $this->is_widget_enabled( $widget_key ) ) {
				continue;
			}

			$position = $this->get_widget_position( $widget_key, $default_position );

			if ( ! isset( $injections[ $position ] ) ) {
				$injections[ $position ] = array();
			}
			$injections[ $position ][] = $widget_key;
		}

		// Register hooks for each position that has widgets.
		foreach ( $injections as $position => $widgets ) {
			if ( ! isset( $this->position_hooks[ $position ] ) ) {
				continue;
			}

			$hook_data = $this->position_hooks[ $position ];

			// Create a closure to render widgets for this position.
			$render_callback = function() use ( $widgets ) {
				$this->render_widgets( $widgets );
			};

			add_action( $hook_data['hook'], $render_callback, $hook_data['priority'] );
		}
	}

	/**
	 * Set up cart/checkout injections
	 */
	private function setup_cart_injections() {
		if ( ! $this->is_widget_enabled( 'shipping_bar' ) ) {
			return;
		}

		add_action( 'woocommerce_before_cart', function() {
			$this->render_widget( 'shipping_bar' );
		}, 5 );

		add_action( 'woocommerce_before_checkout_form', function() {
			$this->render_widget( 'shipping_bar' );
		}, 5 );
	}

	/**
	 * Render a list of widgets
	 *
	 * @param array $widget_keys Widget keys to render.
	 */
	private function render_widgets( $widget_keys ) {
		foreach ( $widget_keys as $widget_key ) {
			$this->render_widget( $widget_key );
		}
	}

	/**
	 * Render a single widget using its shortcode
	 *
	 * @param string $widget_key Widget key.
	 */
	private function render_widget( $widget_key ) {
		// Map widget key to shortcode.
		$shortcode_map = array(
			'total_sold'     => 'aisales_total_sold',
			'stock_urgency'  => 'aisales_stock_urgency',
			'review_summary' => 'aisales_review_summary',
			'price_drop'     => 'aisales_price_drop',
			'live_viewers'   => 'aisales_live_viewers',
			'shipping_bar'   => 'aisales_shipping_bar',
		);

		if ( ! isset( $shortcode_map[ $widget_key ] ) ) {
			return;
		}

		$shortcode = $shortcode_map[ $widget_key ];

		// Render the shortcode.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo do_shortcode( '[' . $shortcode . ']' );
	}
}
