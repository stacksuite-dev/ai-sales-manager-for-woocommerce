<?php
/**
 * Abandoned Cart Emails
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Emails {
	/**
	 * Templates option key.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'aisales_abandoned_cart_email_templates';

	/**
	 * Send recovery email.
	 *
	 * @param array $cart Cart record.
	 * @param int   $step Email step.
	 * @return bool
	 */
	public function send_recovery_email( $cart, $step ) {
		$email = isset( $cart['email'] ) ? $cart['email'] : '';
		if ( empty( $email ) ) {
			return false;
		}

		$templates = $this->get_templates();
		if ( empty( $templates[ $step ] ) ) {
			return false;
		}

		$restore_link = $this->get_restore_link( $cart );
		$cart_items   = $this->format_cart_items( $cart );
		$cart_total   = $this->format_total( $cart );
		$first_name   = $this->extract_first_name( $email );

		$replacements = array(
			'{customer_name}'  => $first_name,
			'{cart_items}'     => $cart_items,
			'{cart_total}'     => $cart_total,
			'{restore_link}'   => $restore_link,
			'{store_name}'     => get_bloginfo( 'name' ),
			'{store_url}'      => home_url(),
		);

		$subject = strtr( $templates[ $step ]['subject'], $replacements );
		$body    = strtr( $templates[ $step ]['body'], $replacements );

		$body = wpautop( $body );

		add_filter( 'wp_mail_content_type', array( $this, 'set_html_email' ) );
		$sent = wp_mail( $email, $subject, $body );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_email' ) );

		return $sent;
	}

	/**
	 * Set HTML email content type.
	 *
	 * @return string
	 */
	public function set_html_email() {
		return 'text/html';
	}

	/**
	 * Get email templates.
	 *
	 * @return array
	 */
	public function get_templates() {
		$templates = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		return array_replace_recursive( $this->get_default_templates(), $templates );
	}

	/**
	 * Get default templates.
	 *
	 * @return array
	 */
	private function get_default_templates() {
		return array(
			1 => array(
				'subject' => __( 'You left items in your cart', 'ai-sales-manager-for-woocommerce' ),
				'body'    => __( "Hi {customer_name},\n\nYou left these items in your cart:\n{cart_items}\n\nTotal: {cart_total}\n\nComplete your purchase here: {restore_link}\n\nThanks,\n{store_name}", 'ai-sales-manager-for-woocommerce' ),
			),
			2 => array(
				'subject' => __( 'Still thinking it over?', 'ai-sales-manager-for-woocommerce' ),
				'body'    => __( "Hi {customer_name},\n\nYour cart is still waiting:\n{cart_items}\n\nTotal: {cart_total}\n\nResume checkout: {restore_link}\n\n{store_name}", 'ai-sales-manager-for-woocommerce' ),
			),
			3 => array(
				'subject' => __( 'Last chance to complete your order', 'ai-sales-manager-for-woocommerce' ),
				'body'    => __( "Hi {customer_name},\n\nYour cart is about to expire:\n{cart_items}\n\nTotal: {cart_total}\n\nFinish now: {restore_link}\n\nThanks,\n{store_name}", 'ai-sales-manager-for-woocommerce' ),
			),
		);
	}

	/**
	 * Build restore link.
	 *
	 * @param array $cart Cart record.
	 * @return string
	 */
	private function get_restore_link( $cart ) {
		$token = isset( $cart['cart_token'] ) ? $cart['cart_token'] : '';
		$key   = isset( $cart['restore_key'] ) ? $cart['restore_key'] : '';

		return add_query_arg(
			array(
				'action' => 'aisales_restore_cart',
				'token'  => $token,
				'key'    => $key,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Format cart items as HTML list.
	 *
	 * @param array $cart Cart record.
	 * @return string
	 */
	private function format_cart_items( $cart ) {
		$items = array();
		$decoded = isset( $cart['cart_items'] ) ? wp_json_decode( $cart['cart_items'], true ) : array();

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $item ) {
				$name = isset( $item['name'] ) ? $item['name'] : '';
				$qty  = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
				if ( $name ) {
					$items[] = sprintf( '%dx %s', $qty, esc_html( $name ) );
				}
			}
		}

		if ( empty( $items ) ) {
			return esc_html__( 'Your cart items', 'ai-sales-manager-for-woocommerce' );
		}

		return '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
	}

	/**
	 * Format cart total.
	 *
	 * @param array $cart Cart record.
	 * @return string
	 */
	private function format_total( $cart ) {
		$total    = isset( $cart['total'] ) ? (float) $cart['total'] : 0;
		$currency = isset( $cart['currency'] ) ? $cart['currency'] : get_woocommerce_currency();

		return wc_price( $total, array( 'currency' => $currency ) );
	}

	/**
	 * Extract first name from email.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private function extract_first_name( $email ) {
		$parts = explode( '@', $email );
		$name  = isset( $parts[0] ) ? $parts[0] : '';
		$name  = trim( preg_replace( '/[^a-zA-Z]+/', ' ', $name ) );
		$name  = $name ? ucwords( $name ) : __( 'there', 'ai-sales-manager-for-woocommerce' );

		return $name;
	}
}
