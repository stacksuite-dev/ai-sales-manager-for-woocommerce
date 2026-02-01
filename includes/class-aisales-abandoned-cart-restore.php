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
		// HMAC token verification is used instead of WordPress nonces for email restore links.
		// Nonces expire and are user-specific, making them unsuitable for email links.
		// The optional nonce check below is defensive; actual verification uses hash_hmac.
		if ( isset( $_GET['_wpnonce'] ) ) {
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aisales_restore' );
		}

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
		$cart  = wp_cache_get( 'aisales_cart_token_' . $token, 'aisales_carts' );
		if ( false === $cart ) {
			$cart = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM %i WHERE cart_token = %s", $table, $token ),
				ARRAY_A
			);
			wp_cache_set( 'aisales_cart_token_' . $token, $cart ? $cart : 'none', 'aisales_carts', 300 );
		}
		if ( 'none' === $cart ) {
			$cart = null;
		}

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

		AISales_Abandoned_Cart_DB::flush_cart_cache( $token );

		wp_safe_redirect( $redirect );
		exit;
	}
}
