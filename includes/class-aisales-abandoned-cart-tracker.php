<?php
/**
 * Abandoned Cart Tracker
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Tracker {
	/**
	 * Single instance
	 *
	 * @var AISales_Abandoned_Cart_Tracker
	 */
	private static $instance = null;

	/**
	 * Cookie name for cart token.
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'aisales_cart_token';

	/**
	 * Get instance.
	 *
	 * @return AISales_Abandoned_Cart_Tracker
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
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_cart_change' ) );
		add_action( 'woocommerce_cart_updated', array( $this, 'handle_cart_change' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_checkout_update' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'handle_order_completed' ), 10, 1 );
		add_action( 'init', array( $this, 'maybe_capture_checkout_email' ) );
	}

	/**
	 * Handle cart change events.
	 */
	public function handle_cart_change() {
		if ( is_admin() ) {
			return;
		}

		$cart = $this->get_cart_instance();
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		$this->upsert_cart_record();
	}

	/**
	 * Handle checkout update.
	 *
	 * @param string $post_data Raw checkout data.
	 */
	public function handle_checkout_update( $post_data ) {
		if ( ! $this->get_cart_instance() ) {
			return;
		}

		parse_str( $post_data, $data );
		$email = isset( $data['billing_email'] ) ? sanitize_email( $data['billing_email'] ) : '';

		$this->upsert_cart_record( array( 'email' => $email ) );
	}

	/**
	 * Capture checkout email from POST if available.
	 */
	public function maybe_capture_checkout_email() {
		if ( empty( $_POST ) ) {
			return;
		}

		// Verify WooCommerce checkout nonce before reading POST data.
		$wc_nonce = isset( $_POST['woocommerce-process-checkout-nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) )
			: '';
		if ( ! wp_verify_nonce( $wc_nonce, 'woocommerce-process_checkout' ) ) {
			return;
		}

		if ( ! isset( $_POST['billing_email'] ) ) {
			return;
		}

		$email = sanitize_email( wp_unslash( $_POST['billing_email'] ) );
		if ( empty( $email ) ) {
			return;
		}

		$this->upsert_cart_record( array( 'email' => $email ) );
	}

	/**
	 * Mark cart as recovered when order is completed.
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_order_completed( $order_id ) {
		$token = $this->get_cart_token();
		if ( ! $token ) {
			return;
		}

		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$wpdb->update(
			$table,
			array(
				'status'       => 'recovered',
				'recovered_at' => current_time( 'mysql' ),
				'order_id'     => absint( $order_id ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'cart_token' => $token ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%s' )
		);

		AISales_Abandoned_Cart_DB::flush_cart_cache( $token );

		update_post_meta( $order_id, '_aisales_abandoned_cart_token', $token );
	}

	/**
	 * Upsert cart record.
	 *
	 * @param array $extra Extra data to store.
	 */
	private function upsert_cart_record( $extra = array() ) {
		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$token      = $this->get_cart_token( true );
		$cart_data  = $this->get_cart_snapshot();
		$user_id    = get_current_user_id();
		$email      = isset( $extra['email'] ) ? sanitize_email( $extra['email'] ) : '';
		$now        = current_time( 'mysql' );
		$restore_key = $this->get_restore_key( $token );

		$existing = wp_cache_get( 'aisales_cart_token_' . $token, 'aisales_carts' );
		if ( false === $existing || 'none' === $existing ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, email FROM %i WHERE cart_token = %s", $table, $token ),
				ARRAY_A
			);
			wp_cache_set( 'aisales_cart_token_' . $token, $existing ? $existing : 'none', 'aisales_carts', 300 );
		}
		if ( 'none' === $existing ) {
			$existing = null;
		}

		if ( empty( $email ) && ! empty( $existing['email'] ) ) {
			$email = $existing['email'];
		} elseif ( empty( $email ) && $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user && ! empty( $user->user_email ) ) {
				$email = $user->user_email;
			}
		}

		$data = array(
			'cart_token'       => $token,
			'restore_key'      => $restore_key,
			'user_id'          => $user_id ? $user_id : null,
			'email'            => $email ? $email : null,
			'cart_items'       => wp_json_encode( $cart_data['items'] ),
			'currency'         => $cart_data['currency'],
			'subtotal'         => $cart_data['subtotal'],
			'total'            => $cart_data['total'],
			'status'           => 'active',
			'last_activity_at' => $now,
			'updated_at'       => $now,
		);

		$formats = array( '%s', '%s', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s' );

		if ( $existing ) {
			$wpdb->update(
				$table,
				$data,
				array( 'id' => $existing['id'] ),
				$formats,
				array( '%d' )
			);
		} else {
			$data['created_at'] = $now;
			$formats[]          = '%s';

			$wpdb->insert( $table, $data, $formats );
		}

		AISales_Abandoned_Cart_DB::flush_cart_cache( $token );
	}

	/**
	 * Get cart snapshot.
	 *
	 * @return array
	 */
	private function get_cart_snapshot() {
		$items = array();
		$cart  = $this->get_cart_instance();
		if ( ! $cart ) {
			return array(
				'items'    => array(),
				'currency' => get_woocommerce_currency(),
				'subtotal' => 0,
				'total'    => 0,
			);
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$items[] = array(
				'product_id' => $product->get_id(),
				'name'       => $product->get_name(),
				'quantity'   => $cart_item['quantity'],
				'price'      => wc_get_price_to_display( $product ),
			);
		}

		return array(
			'items'    => $items,
			'currency' => get_woocommerce_currency(),
			'subtotal' => (float) $cart->get_subtotal(),
			'total'    => (float) $cart->get_total( 'edit' ),
		);
	}

	/**
	 * Get or create cart token.
	 *
	 * @param bool $create Create if missing.
	 * @return string|null
	 */
	private function get_cart_token( $create = false ) {
		$token = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';

		if ( $token ) {
			return $token;
		}

		if ( ! $create ) {
			return null;
		}

		$token = wp_generate_password( 32, false, false );
		wc_setcookie( self::COOKIE_NAME, $token, time() + DAY_IN_SECONDS * 30 );
		return $token;
	}

	/**
	 * Get WooCommerce cart instance safely.
	 *
	 * @return WC_Cart|null
	 */
	private function get_cart_instance() {
		if ( ! function_exists( 'WC' ) ) {
			return null;
		}

		$cart = WC()->cart;
		return $cart ? $cart : null;
	}

	/**
	 * Get restore key from token.
	 *
	 * @param string $token Cart token.
	 * @return string
	 */
	private function get_restore_key( $token ) {
		$secret = wp_salt( 'nonce' );
		return hash_hmac( 'sha256', $token, $secret );
	}
}
