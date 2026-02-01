<?php
/**
 * Abandoned Cart Restore Handler
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Restore {
	/**
	 * Single instance.
	 *
	 * @var AISales_Abandoned_Cart_Restore
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AISales_Abandoned_Cart_Restore
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_ajax_aisales_restore_cart', array( $this, 'handle_restore' ) );
		add_action( 'wp_ajax_nopriv_aisales_restore_cart', array( $this, 'handle_restore' ) );
	}

	/**
	 * Handle restore action.
	 */
	public function handle_restore() {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( empty( $token ) || empty( $key ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		$expected_key = hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) );
		if ( ! hash_equals( $expected_key, $key ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table lookup by token.
		$cart  = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE cart_token = %s", $token ),
			ARRAY_A
		);

		if ( empty( $cart ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		WC()->cart->empty_cart();
		$items = wp_json_decode( $cart['cart_items'], true );
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
				$quantity   = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
				if ( $product_id ) {
					WC()->cart->add_to_cart( $product_id, $quantity );
				}
			}
		}

		$settings = AISales_Abandoned_Cart_Settings::get_settings();
		$redirect = wc_get_cart_url();
		if ( isset( $settings['restore_redirect'] ) && 'checkout' === $settings['restore_redirect'] ) {
			$redirect = wc_get_checkout_url();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table update.
		$wpdb->update(
			$table,
			array(
				'status'     => 'active',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $cart['id'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
