<?php
/**
 * Auth AJAX Handlers
 *
 * Handles all authentication and account-related AJAX actions including
 * connection, balance retrieval, and API key recovery.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Auth AJAX Handlers class
 */
class AISales_Ajax_Auth extends AISales_Ajax_Base {

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		// Connection
		$this->add_action( 'connect', 'handle_connect' );

		// Balance
		$this->add_action( 'get_balance', 'handle_get_balance' );

		// API key recovery (also available to non-logged-in users for recovery page)
		$this->add_action( 'request_api_key_recovery', 'handle_request_api_key_recovery', true );
		$this->add_action( 'validate_recovery_token', 'handle_validate_recovery_token', true );
	}

	/**
	 * Handle connect (domain-based auth)
	 */
	public function handle_connect() {
		$this->verify_request();

		$email  = $this->require_post( 'email', 'email', __( 'Email is required.', 'ai-sales-manager-for-woocommerce' ) );
		$domain = $this->require_post( 'domain', 'text', __( 'Domain is required.', 'ai-sales-manager-for-woocommerce' ) );

		$result = $this->api()->connect( $email, $domain );

		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = isset( $error_data['status'] ) ? $error_data['status'] : 'unknown';

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'AISales connect failed - Status: %s, Message: %s', $status, $result->get_error_message() ) );
			}

			$this->error( $result->get_error_message(), array( 'status' => $status ) );
		}

		// Store credentials
		update_option( 'aisales_api_key', $result['api_key'] );
		update_option( 'aisales_user_email', $result['email'] );
		update_option( 'aisales_balance', $result['balance_tokens'] );
		update_option( 'aisales_domain', $result['domain'] );

		$is_new        = isset( $result['is_new'] ) && $result['is_new'];
		$welcome_bonus = isset( $result['welcome_bonus'] ) ? intval( $result['welcome_bonus'] ) : 0;

		// Build success message
		if ( $is_new && $welcome_bonus > 0 ) {
			$message = sprintf(
				/* translators: %s: number of bonus tokens */
				__( 'Welcome! Your account has been created and you received %s bonus tokens to get started.', 'ai-sales-manager-for-woocommerce' ),
				number_format_i18n( $welcome_bonus )
			);
		} elseif ( $is_new ) {
			$message = __( 'Welcome! Your account has been created successfully.', 'ai-sales-manager-for-woocommerce' );
		} else {
			$message = __( 'Successfully connected to your existing account.', 'ai-sales-manager-for-woocommerce' );
		}

		$this->success( array(
			'message'       => $message,
			'is_new'        => $is_new,
			'welcome_bonus' => $welcome_bonus,
			'balance'       => $result['balance_tokens'],
			'email'         => $result['email'],
		) );
	}

	/**
	 * Handle get balance
	 */
	public function handle_get_balance() {
		$this->verify_request();

		$result = $this->handle_api_result( $this->api()->get_balance() );

		// Update local balance
		if ( isset( $result['balance'] ) ) {
			update_option( 'aisales_balance', $result['balance'] );
		}

		$this->success( array(
			'balance'         => isset( $result['balance'] ) ? $result['balance'] : 0,
			'auto_topup'      => isset( $result['auto_topup'] ) ? $result['auto_topup'] : array(),
			'payment_method'  => isset( $result['payment_method'] ) ? $result['payment_method'] : null,
			'usage_this_month' => isset( $result['usage_this_month'] ) ? $result['usage_this_month'] : 0,
		) );
	}

	/**
	 * Handle API key recovery request
	 */
	public function handle_request_api_key_recovery() {
		$this->verify_nonce();

		$email  = $this->get_post( 'email', 'email' );
		$domain = $this->get_site_domain();

		if ( empty( $email ) || ! is_email( $email ) ) {
			$this->error( __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) );
		}

		if ( empty( $domain ) ) {
			$this->error( __( 'Could not determine site domain.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = $this->api()->create_recovery_token( $email, $domain );

		// Generic success message for security (don't reveal if email/domain exists)
		$generic_message = __( 'If an account exists with this email for this site, you will receive a recovery link shortly.', 'ai-sales-manager-for-woocommerce' );

		if ( is_wp_error( $result ) ) {
			$this->success( array( 'message' => $generic_message ) );
			return;
		}

		// Check if account was found
		if ( ! isset( $result['has_account'] ) || ! $result['has_account'] ) {
			$this->success( array( 'message' => $generic_message ) );
			return;
		}

		// Build and send recovery email
		$recovery_url = add_query_arg( array(
			'page'           => 'ai-sales-manager',
			'recovery_token' => $result['token'],
		), admin_url( 'admin.php' ) );

		$sent = $this->send_recovery_email( $email, $domain, $recovery_url );

		if ( $sent ) {
			$this->success( array( 'message' => $generic_message ) );
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'AISales: Failed to send recovery email to ' . $email );
			}
			$this->error( __( 'Failed to send recovery email. Please try again later or contact support.', 'ai-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Handle recovery token validation
	 */
	public function handle_validate_recovery_token() {
		$this->verify_nonce();

		$token = $this->require_post( 'token', 'text', __( 'Invalid recovery token.', 'ai-sales-manager-for-woocommerce' ) );

		$result = $this->handle_api_result( $this->api()->validate_recovery_token( $token ) );

		if ( isset( $result['api_key'] ) ) {
			update_option( 'aisales_api_key', $result['api_key'] );

			if ( isset( $result['email'] ) ) {
				update_option( 'aisales_user_email', $result['email'] );
			}

			$this->success( array(
				'message'  => __( 'API key recovered successfully! You are now connected.', 'ai-sales-manager-for-woocommerce' ),
				'redirect' => admin_url( 'admin.php?page=ai-sales-manager' ),
			) );
		}

		$this->error( __( 'Invalid response from server.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Get site domain
	 *
	 * @return string Site domain without protocol.
	 */
	private function get_site_domain() {
		$site_url = get_site_url();
		$parsed   = wp_parse_url( $site_url );
		return isset( $parsed['host'] ) ? $parsed['host'] : '';
	}

	/**
	 * Send recovery email
	 *
	 * @param string $email        Recipient email.
	 * @param string $domain       Site domain.
	 * @param string $recovery_url Recovery URL.
	 * @return bool Whether email was sent.
	 */
	private function send_recovery_email( $email, $domain, $recovery_url ) {
		$store_name = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: %s: store name */
			__( '[%s] API Key Recovery', 'ai-sales-manager-for-woocommerce' ),
			$store_name
		);

		$message = sprintf(
			/* translators: 1: store name, 2: site domain, 3: recovery URL */
			__(
				"Hello,\n\nYou (or someone else) requested to recover your AI Sales Manager API key for %1\$s (%2\$s).\n\nClick the link below to recover your API key:\n%3\$s\n\nThis link will expire in 1 hour.\n\nIf you did not request this recovery, you can safely ignore this email.\n\nThanks,\n%1\$s",
				'ai-sales-manager-for-woocommerce'
			),
			$store_name,
			$domain,
			$recovery_url
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $email, $subject, $message, $headers );
	}
}
