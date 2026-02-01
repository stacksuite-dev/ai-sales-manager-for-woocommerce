<?php
/**
 * Billing AJAX Handlers
 *
 * Handles all billing-related AJAX actions including top-up,
 * auto top-up settings, and payment method management.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Billing AJAX Handlers class
 */
class AISales_Ajax_Billing extends AISales_Ajax_Base {

	/**
	 * Valid plan IDs for checkout
	 *
	 * @var array
	 */
	private $valid_plans = array( 'starter_plan', 'standard_plan', 'pro_plan', 'business_plan' );

	/**
	 * Allowed auto top-up thresholds
	 *
	 * @var array
	 */
	private $allowed_thresholds = array( 500, 1000, 2000, 5000 );

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		$this->add_action( 'topup', 'handle_topup' );
		$this->add_action( 'quick_topup', 'handle_quick_topup' );
		$this->add_action( 'update_auto_topup', 'handle_update_auto_topup' );
		$this->add_action( 'setup_payment_method', 'handle_setup_payment_method' );
		$this->add_action( 'confirm_setup', 'handle_confirm_setup' );
		$this->add_action( 'remove_payment_method', 'handle_remove_payment_method' );
	}

	/**
	 * Handle top-up request
	 *
	 * Creates a checkout session for purchasing tokens.
	 */
	public function handle_topup() {
		$this->verify_request();

		$result = $this->handle_api_result( $this->api()->create_checkout() );

		$this->success( array(
			'checkout_url' => $result['checkout_url'],
		) );
	}

	/**
	 * Handle quick top-up with specific plan
	 *
	 * Creates a checkout session for a specific plan.
	 */
	public function handle_quick_topup() {
		$this->verify_request();

		$plan_id = $this->require_post( 'plan_id', 'text', __( 'No plan selected.', 'stacksuite-sales-manager-for-woocommerce' ) );

		if ( ! in_array( $plan_id, $this->valid_plans, true ) ) {
			$this->error( __( 'Invalid plan selected.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$result = $this->handle_api_result( $this->api()->create_checkout( $plan_id ) );

		$this->success( array(
			'checkout_url' => $result['checkout_url'],
		) );
	}

	/**
	 * Handle update auto top-up settings
	 *
	 * Updates the auto top-up configuration including threshold and plan.
	 */
	public function handle_update_auto_topup() {
		$this->verify_request();

		$enabled      = $this->get_post( 'enabled', 'bool', false );
		$threshold    = $this->get_post( 'threshold', 'int', 1000 );
		$product_slug = $this->get_post( 'product_slug', 'text', 'standard_plan' );

		// Validate threshold
		if ( ! in_array( $threshold, $this->allowed_thresholds, true ) ) {
			$threshold = 1000;
		}

		$result = $this->handle_api_result(
			$this->api()->update_auto_topup_settings( array(
				'enabled'     => $enabled,
				'threshold'   => $threshold,
				'productSlug' => $product_slug,
			) )
		);

		$this->success( array(
			'message'  => $enabled
				? __( 'Auto top-up enabled.', 'stacksuite-sales-manager-for-woocommerce' )
				: __( 'Auto top-up disabled.', 'stacksuite-sales-manager-for-woocommerce' ),
			'settings' => $result,
		) );
	}

	/**
	 * Handle setup payment method
	 *
	 * Creates a Stripe setup session for adding a payment method.
	 */
	public function handle_setup_payment_method() {
		$this->verify_request();

		$success_url = $this->get_post( 'success_url', 'url' );
		$cancel_url  = $this->get_post( 'cancel_url', 'url' );

		if ( empty( $success_url ) ) {
			$success_url = admin_url( 'admin.php?page=ai-sales-manager&tab=billing&payment_setup=success' );
		}
		if ( empty( $cancel_url ) ) {
			$cancel_url = admin_url( 'admin.php?page=ai-sales-manager&tab=billing&payment_setup=cancelled' );
		}

		$result = $this->handle_api_result( $this->api()->setup_payment_method( $success_url, $cancel_url ) );

		$this->success( array(
			'setup_url'  => $result['setup_url'],
			'session_id' => isset( $result['session_id'] ) ? $result['session_id'] : '',
		) );
	}

	/**
	 * Handle confirm setup session
	 *
	 * Confirms that a Stripe setup session was completed successfully.
	 */
	public function handle_confirm_setup() {
		$this->verify_request();

		$session_id = $this->require_post( 'session_id', 'text', __( 'Session ID required.', 'stacksuite-sales-manager-for-woocommerce' ) );

		$this->handle_api_result( $this->api()->confirm_setup( $session_id ) );

		$this->success( array(
			'confirmed' => true,
		) );
	}

	/**
	 * Handle remove payment method
	 *
	 * Removes the stored payment method from the account.
	 */
	public function handle_remove_payment_method() {
		$this->verify_request();

		$this->handle_api_result( $this->api()->remove_payment_method() );

		$this->success( array(
			'message' => __( 'Payment method removed.', 'stacksuite-sales-manager-for-woocommerce' ),
		) );
	}
}
