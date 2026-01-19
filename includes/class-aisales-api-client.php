<?php
/**
 * API Client for AI Sales Manager SaaS
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * API Client class
 */
class AISales_API_Client {

	/**
	 * Single instance
	 *
	 * @var AISales_API_Client
	 */
	private static $instance = null;

	/**
	 * API base URL
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * Use mock data flag
	 *
	 * @var bool
	 */
	private $use_mock = false;

	/**
	 * Get instance
	 *
	 * @return AISales_API_Client
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
		$this->api_url = apply_filters( 'aisales_api_url', AISALES_API_URL );

		// Mock mode: disabled by default, can be enabled via filter or constant
		// Use: define('AISALES_MOCK_MODE', true) in wp-config.php for testing
		$this->use_mock = defined( 'AISALES_MOCK_MODE' ) && AISALES_MOCK_MODE;
		$this->use_mock = apply_filters( 'aisales_use_mock', $this->use_mock );
	}

	/**
	 * Get stored API key
	 *
	 * @return string|false
	 */
	public function get_api_key() {
		return get_option( 'aisales_api_key', false );
	}

	/**
	 * Check if connected (has API key)
	 *
	 * @return bool
	 */
	public function is_connected() {
		$api_key = $this->get_api_key();
		return ! empty( $api_key );
	}

	/**
	 * Make API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method   HTTP method.
	 * @param array  $body     Request body.
	 * @return array|WP_Error
	 */
	private function request( $endpoint, $method = 'GET', $body = array() ) {
		$api_key = $this->get_api_key();

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-API-Key'    => $api_key,
			),
			'timeout' => 30,
			// Allow SSL verification to be disabled for local development
			'sslverify' => apply_filters( 'aisales_api_sslverify', true ),
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$url = $this->api_url . $endpoint;
		
		// Log request in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( 'AISales API request: %s %s', $method, $url ) );
		}
		
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			// Log connection errors for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AISales API connection error to %s: %s', $url, $response->get_error_message() ) );
			}
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code >= 400 ) {
			// Log API errors for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AISales API error %d from %s: %s', $status_code, $url, $body ) );
			}
			// Try to get error message from response, with fallback showing status code
			$error_message = __( 'API request failed', 'ai-sales-manager-for-woocommerce' );
			if ( isset( $data['message'] ) ) {
				$error_message = $data['message'];
			} elseif ( isset( $data['error'] ) ) {
				$error_message = $data['error'];
			} elseif ( ! empty( $body ) && strlen( $body ) < 200 ) {
				$error_message = sprintf( __( 'API error %d: %s', 'ai-sales-manager-for-woocommerce' ), $status_code, $body );
			} else {
				$error_message = sprintf( __( 'API error %d', 'ai-sales-manager-for-woocommerce' ), $status_code );
			}
			return new WP_Error( 'api_error', $error_message, array( 'status' => $status_code, 'body' => $body ) );
		}

		return $data;
	}

	/**
	 * Register new account
	 *
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @return array|WP_Error
	 */
	public function register( $email, $password ) {
		if ( $this->use_mock ) {
			return $this->mock_register( $email );
		}

		return $this->request( '/auth/register', 'POST', array(
			'email'    => $email,
			'password' => $password,
		) );
	}

	/**
	 * Login to account
	 *
	 * @param string $email    User email.
	 * @param string $password User password.
	 * @return array|WP_Error
	 */
	public function login( $email, $password ) {
		if ( $this->use_mock ) {
			return $this->mock_login( $email );
		}

		return $this->request( '/auth/login', 'POST', array(
			'email'    => $email,
			'password' => $password,
		) );
	}

	/**
	 * Connect WordPress site (domain-based authentication)
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array|WP_Error
	 */
	public function connect( $email, $domain ) {
		if ( $this->use_mock ) {
			return $this->mock_connect( $email, $domain );
		}

		return $this->request( '/auth/connect', 'POST', array(
			'email'  => $email,
			'domain' => $domain,
		) );
	}

	/**
	 * Get account info
	 *
	 * @return array|WP_Error
	 */
	public function get_account() {
		if ( $this->use_mock ) {
			return $this->mock_get_account();
		}

		return $this->request( '/account' );
	}

	/**
	 * Get usage history
	 *
	 * @param int $limit  Number of records.
	 * @param int $offset Offset for pagination.
	 * @return array|WP_Error
	 */
	public function get_usage( $limit = 10, $offset = 0 ) {
		if ( $this->use_mock ) {
			return $this->mock_get_usage( $limit, $offset );
		}

		return $this->request( "/account/usage?limit={$limit}&offset={$offset}" );
	}

	/**
	 * Get transaction history
	 *
	 * @param int $limit  Number of records.
	 * @param int $offset Offset for pagination.
	 * @return array|WP_Error
	 */
	public function get_transactions( $limit = 10, $offset = 0 ) {
		if ( $this->use_mock ) {
			return $this->mock_get_transactions( $limit, $offset );
		}

		return $this->request( "/account/transactions?limit={$limit}&offset={$offset}" );
	}

	/**
	 * Create checkout session for top-up
	 *
	 * @param string $plan Plan identifier.
	 * @return array|WP_Error
	 */
	public function create_checkout( $plan = '10k' ) {
		if ( $this->use_mock ) {
			return $this->mock_create_checkout();
		}

		$admin_url = admin_url( 'admin.php?page=ai-sales-manager' );

		return $this->request( '/billing/checkout', 'POST', array(
			'plan'        => $plan,
			'success_url' => add_query_arg( 'topup', 'success', $admin_url ),
			'cancel_url'  => add_query_arg( 'topup', 'cancelled', $admin_url ),
		) );
	}

	/**
	 * Generate content for product
	 *
	 * @param array $product_data Product data.
	 * @return array|WP_Error
	 */
	public function generate_content( $product_data ) {
		if ( $this->use_mock ) {
			return $this->mock_generate_content( $product_data );
		}

		return $this->request( '/ai/content', 'POST', $product_data );
	}

	/**
	 * Suggest taxonomy for product
	 *
	 * @param array $product_data Product data.
	 * @return array|WP_Error
	 */
	public function suggest_taxonomy( $product_data ) {
		if ( $this->use_mock ) {
			return $this->mock_suggest_taxonomy( $product_data );
		}

		return $this->request( '/ai/taxonomy', 'POST', $product_data );
	}

	/**
	 * Generate product image
	 *
	 * @param array $product_data Product data.
	 * @return array|WP_Error
	 */
	public function generate_image( $product_data ) {
		if ( $this->use_mock ) {
			return $this->mock_generate_image( $product_data );
		}

		return $this->request( '/ai/image/generate', 'POST', $product_data );
	}

	/**
	 * Improve product image
	 *
	 * @param array $image_data Image data.
	 * @return array|WP_Error
	 */
	public function improve_image( $image_data ) {
		if ( $this->use_mock ) {
			return $this->mock_improve_image( $image_data );
		}

		return $this->request( '/ai/image/improve', 'POST', $image_data );
	}

	/**
	 * Generate content for WooCommerce category
	 *
	 * @param array $category_data Category data.
	 * @return array|WP_Error
	 */
	public function generate_category_content( $category_data ) {
		if ( $this->use_mock ) {
			return $this->mock_generate_category_content( $category_data );
		}

		return $this->request( '/ai/category/content', 'POST', $category_data );
	}

	/**
	 * Suggest subcategories for WooCommerce category
	 *
	 * @param array $category_data Category data.
	 * @return array|WP_Error
	 */
	public function suggest_subcategories( $category_data ) {
		if ( $this->use_mock ) {
			return $this->mock_suggest_subcategories( $category_data );
		}

		return $this->request( '/ai/category/subcategories', 'POST', $category_data );
	}

	// =========================================================================
	// BILLING & AUTO TOP-UP METHODS
	// =========================================================================

	/**
	 * Get auto top-up settings
	 *
	 * @return array|WP_Error
	 */
	public function get_auto_topup_settings() {
		if ( $this->use_mock ) {
			return $this->mock_get_auto_topup_settings();
		}

		return $this->request( '/billing/auto-topup' );
	}

	/**
	 * Update auto top-up settings
	 *
	 * @param array $settings Settings to update (enabled, threshold, product_slug).
	 * @return array|WP_Error
	 */
	public function update_auto_topup_settings( $settings ) {
		if ( $this->use_mock ) {
			return $this->mock_update_auto_topup_settings( $settings );
		}

		return $this->request( '/billing/auto-topup', 'PUT', $settings );
	}

	/**
	 * Get saved payment method details
	 *
	 * @return array|WP_Error
	 */
	public function get_payment_method() {
		if ( $this->use_mock ) {
			return $this->mock_get_payment_method();
		}

		return $this->request( '/billing/payment-method' );
	}

	/**
	 * Create setup session for adding payment method
	 *
	 * @param string $success_url URL to redirect on success.
	 * @param string $cancel_url  URL to redirect on cancel.
	 * @return array|WP_Error
	 */
	public function setup_payment_method( $success_url, $cancel_url ) {
		if ( $this->use_mock ) {
			return $this->mock_setup_payment_method( $success_url );
		}

		return $this->request( '/billing/setup-payment-method', 'POST', array(
			'success_url' => $success_url,
			'cancel_url'  => $cancel_url,
		) );
	}

	/**
	 * Remove saved payment method
	 *
	 * @return array|WP_Error
	 */
	public function remove_payment_method() {
		if ( $this->use_mock ) {
			return $this->mock_remove_payment_method();
		}

		return $this->request( '/billing/payment-method', 'DELETE' );
	}

	/**
	 * Get purchase history (top-ups only)
	 *
	 * @param int $limit  Number of records.
	 * @param int $offset Offset for pagination.
	 * @return array|WP_Error
	 */
	public function get_purchases( $limit = 20, $offset = 0 ) {
		if ( $this->use_mock ) {
			return $this->mock_get_purchases( $limit, $offset );
		}

		return $this->request( "/billing/purchases?limit={$limit}&offset={$offset}" );
	}

	// =========================================================================
	// MOCK DATA METHODS (for development/testing)
	// =========================================================================

	/**
	 * Mock register response
	 *
	 * @param string $email User email.
	 * @return array
	 */
	private function mock_register( $email ) {
		$api_key = 'wai_mock_' . wp_generate_password( 32, false );
		update_option( 'aisales_api_key', $api_key );
		update_option( 'aisales_user_email', $email );
		update_option( 'aisales_balance', 1000 ); // Start with 1000 tokens for testing

		return array(
			'message'        => 'Account created successfully',
			'user_id'        => 1,
			'email'          => $email,
			'api_key'        => $api_key,
			'balance_tokens' => 1000,
		);
	}

	/**
	 * Mock login response
	 *
	 * @param string $email User email.
	 * @return array
	 */
	private function mock_login( $email ) {
		$api_key = get_option( 'aisales_api_key' );
		if ( ! $api_key ) {
			$api_key = 'wai_mock_' . wp_generate_password( 32, false );
			update_option( 'aisales_api_key', $api_key );
		}
		update_option( 'aisales_user_email', $email );

		$balance = get_option( 'aisales_balance', 7432 );

		return array(
			'message'        => 'Login successful',
			'user_id'        => 1,
			'email'          => $email,
			'api_key'        => $api_key,
			'balance_tokens' => $balance,
		);
	}

	/**
	 * Mock connect response (domain-based auth)
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array
	 */
	private function mock_connect( $email, $domain ) {
		$api_key = get_option( 'aisales_api_key' );
		$is_new  = false;

		if ( ! $api_key ) {
			$api_key = 'wai_mock_' . wp_generate_password( 32, false );
			$is_new  = true;
			update_option( 'aisales_balance', 1000 );
		}

		update_option( 'aisales_api_key', $api_key );
		update_option( 'aisales_user_email', $email );
		update_option( 'aisales_domain', $domain );

		$balance = get_option( 'aisales_balance', 1000 );

		return array(
			'message'        => $is_new ? 'Account created successfully' : 'Connected successfully',
			'user_id'        => 1,
			'email'          => $email,
			'domain'         => $domain,
			'api_key'        => $api_key,
			'balance_tokens' => $balance,
			'is_new'         => $is_new,
		);
	}

	/**
	 * Mock get account response
	 *
	 * @return array
	 */
	private function mock_get_account() {
		return array(
			'user_id'        => 1,
			'email'          => get_option( 'aisales_user_email', 'demo@example.com' ),
			'balance_tokens' => get_option( 'aisales_balance', 7432 ),
		);
	}

	/**
	 * Mock get usage response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	private function mock_get_usage( $limit, $offset ) {
		$mock_logs = array(
			array(
				'id'            => 1,
				'operation'     => 'content',
				'input_tokens'  => 150,
				'output_tokens' => 95,
				'total_tokens'  => 245,
				'product_id'    => '123',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			),
			array(
				'id'            => 2,
				'operation'     => 'image_generate',
				'input_tokens'  => 200,
				'output_tokens' => 1003,
				'total_tokens'  => 1203,
				'product_id'    => '456',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
			),
			array(
				'id'            => 3,
				'operation'     => 'taxonomy',
				'input_tokens'  => 50,
				'output_tokens' => 39,
				'total_tokens'  => 89,
				'product_id'    => '789',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			),
			array(
				'id'            => 4,
				'operation'     => 'content',
				'input_tokens'  => 180,
				'output_tokens' => 120,
				'total_tokens'  => 300,
				'product_id'    => '101',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
			),
			array(
				'id'            => 5,
				'operation'     => 'image_improve',
				'input_tokens'  => 500,
				'output_tokens' => 450,
				'total_tokens'  => 950,
				'product_id'    => '102',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			),
		);

		return array(
			'logs'       => array_slice( $mock_logs, $offset, $limit ),
			'pagination' => array(
				'total'    => count( $mock_logs ),
				'limit'    => $limit,
				'offset'   => $offset,
				'has_more' => ( $offset + $limit ) < count( $mock_logs ),
			),
		);
	}

	/**
	 * Mock get transactions response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	private function mock_get_transactions( $limit, $offset ) {
		$mock_transactions = array(
			array(
				'id'            => 1,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 10000,
				'description'   => 'Token top-up',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
			),
			array(
				'id'            => 2,
				'type'          => 'usage',
				'amount_tokens' => -245,
				'amount_usd'    => null,
				'balance_after' => 9755,
				'description'   => 'Content generation',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			),
			array(
				'id'            => 3,
				'type'          => 'usage',
				'amount_tokens' => -1203,
				'amount_usd'    => null,
				'balance_after' => 8552,
				'description'   => 'Image generation',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
			),
		);

		return array(
			'transactions' => array_slice( $mock_transactions, $offset, $limit ),
			'pagination'   => array(
				'total'    => count( $mock_transactions ),
				'limit'    => $limit,
				'offset'   => $offset,
				'has_more' => ( $offset + $limit ) < count( $mock_transactions ),
			),
		);
	}

	/**
	 * Mock create checkout response
	 *
	 * @return array
	 */
	private function mock_create_checkout() {
		// In mock mode, just add tokens directly
		$current_balance = get_option( 'aisales_balance', 0 );
		update_option( 'aisales_balance', $current_balance + 10000 );

		return array(
			'checkout_url' => admin_url( 'admin.php?page=ai-sales-manager&topup=success' ),
			'session_id'   => 'mock_session_' . time(),
		);
	}

	/**
	 * Mock generate content response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	private function mock_generate_content( $product_data ) {
		$title = isset( $product_data['product_title'] ) ? $product_data['product_title'] : 'Product';

		// Deduct mock tokens
		$this->deduct_mock_tokens( 245 );

		return array(
			'result'      => array(
				'title'            => $title . ' - Premium Quality',
				'description'      => "Discover the exceptional quality of our {$title}. Crafted with meticulous attention to detail, this product combines style with functionality. Perfect for those who appreciate the finer things in life.\n\nKey Features:\n• Premium materials for lasting durability\n• Modern design that complements any setting\n• Carefully crafted for optimal performance\n• Backed by our quality guarantee",
				'short_description' => "Premium {$title} crafted with exceptional quality and modern design. Perfect for discerning customers.",
				'meta_description' => "Shop our premium {$title}. High-quality materials, modern design, and exceptional value. Free shipping available.",
			),
			'tokens_used' => array(
				'input'  => 150,
				'output' => 95,
				'total'  => 245,
			),
		);
	}

	/**
	 * Mock suggest taxonomy response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	private function mock_suggest_taxonomy( $product_data ) {
		// Deduct mock tokens
		$this->deduct_mock_tokens( 89 );

		return array(
			'suggested_categories' => array(
				'Clothing',
				'Men\'s Fashion',
				'Casual Wear',
			),
			'suggested_tags'       => array(
				'premium',
				'comfortable',
				'stylish',
				'everyday',
				'bestseller',
			),
			'suggested_attributes' => array(
				array(
					'name'   => 'Material',
					'values' => array( 'Cotton', 'Polyester Blend' ),
				),
				array(
					'name'   => 'Fit',
					'values' => array( 'Regular', 'Slim', 'Relaxed' ),
				),
			),
			'tokens_used'          => array(
				'input'  => 50,
				'output' => 39,
				'total'  => 89,
			),
		);
	}

	/**
	 * Mock generate image response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	private function mock_generate_image( $product_data ) {
		// Deduct mock tokens
		$this->deduct_mock_tokens( 1203 );

		// Return a placeholder image URL
		return array(
			'image_url'   => 'https://via.placeholder.com/800x800/3498db/ffffff?text=AI+Generated+Image',
			'tokens_used' => array(
				'input'  => 200,
				'output' => 1003,
				'total'  => 1203,
			),
		);
	}

	/**
	 * Mock improve image response
	 *
	 * @param array $image_data Image data.
	 * @return array
	 */
	private function mock_improve_image( $image_data ) {
		// Deduct mock tokens
		$this->deduct_mock_tokens( 950 );

		return array(
			'image_url'   => 'https://via.placeholder.com/800x800/2ecc71/ffffff?text=Improved+Image',
			'tokens_used' => array(
				'input'  => 500,
				'output' => 450,
				'total'  => 950,
			),
		);
	}

	/**
	 * Deduct mock tokens from balance
	 *
	 * @param int $tokens Number of tokens to deduct.
	 */
	private function deduct_mock_tokens( $tokens ) {
		$balance = get_option( 'aisales_balance', 7432 );
		$new_balance = max( 0, $balance - $tokens );
		update_option( 'aisales_balance', $new_balance );
	}

	// =========================================================================
	// MOCK BILLING & AUTO TOP-UP METHODS
	// =========================================================================

	/**
	 * Mock get auto top-up settings response
	 *
	 * @return array
	 */
	private function mock_get_auto_topup_settings() {
		return array(
			'enabled'      => get_option( 'aisales_auto_topup_enabled', false ),
			'threshold'    => get_option( 'aisales_auto_topup_threshold', 1000 ),
			'product_slug' => get_option( 'aisales_auto_topup_product', 'standard_plan' ),
		);
	}

	/**
	 * Mock update auto top-up settings response
	 *
	 * @param array $settings Settings to update.
	 * @return array
	 */
	private function mock_update_auto_topup_settings( $settings ) {
		if ( isset( $settings['enabled'] ) ) {
			update_option( 'aisales_auto_topup_enabled', $settings['enabled'] );
		}
		if ( isset( $settings['threshold'] ) ) {
			update_option( 'aisales_auto_topup_threshold', $settings['threshold'] );
		}
		if ( isset( $settings['product_slug'] ) ) {
			update_option( 'aisales_auto_topup_product', $settings['product_slug'] );
		}

		return $this->mock_get_auto_topup_settings();
	}

	/**
	 * Mock get payment method response
	 *
	 * @return array
	 */
	private function mock_get_payment_method() {
		$has_method = get_option( 'aisales_mock_payment_method', false );

		if ( ! $has_method ) {
			return array( 'payment_method' => null );
		}

		return array(
			'payment_method' => array(
				'id'        => 'pm_mock_1234567890',
				'brand'     => 'visa',
				'last4'     => '4242',
				'exp_month' => 12,
				'exp_year'  => 2027,
			),
		);
	}

	/**
	 * Mock setup payment method response
	 *
	 * @param string $success_url Success URL.
	 * @return array
	 */
	private function mock_setup_payment_method( $success_url ) {
		// In mock mode, just save a mock payment method
		update_option( 'aisales_mock_payment_method', true );

		return array(
			'checkout_url' => add_query_arg( 'payment_setup', 'success', $success_url ),
			'session_id'   => 'mock_setup_session_' . time(),
		);
	}

	/**
	 * Mock remove payment method response
	 *
	 * @return array
	 */
	private function mock_remove_payment_method() {
		delete_option( 'aisales_mock_payment_method' );
		update_option( 'aisales_auto_topup_enabled', false );

		return array( 'removed' => true );
	}

	/**
	 * Mock get purchases response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	private function mock_get_purchases( $limit, $offset ) {
		$mock_purchases = array(
			array(
				'id'            => 1,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 10000,
				'description'   => 'Token top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
			),
			array(
				'id'            => 2,
				'type'          => 'auto_topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 12500,
				'description'   => 'Auto top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			),
			array(
				'id'            => 3,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 17432,
				'description'   => 'Token top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			),
		);

		// Sort by date descending
		usort( $mock_purchases, function( $a, $b ) {
			return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
		});

		return array(
			'purchases' => array_slice( $mock_purchases, $offset, $limit ),
			'total'     => count( $mock_purchases ),
			'limit'     => $limit,
			'offset'    => $offset,
		);
	}

	/**
	 * Mock generate category content response
	 *
	 * @param array $category_data Category data.
	 * @return array
	 */
	private function mock_generate_category_content( $category_data ) {
		$name = isset( $category_data['name'] ) ? $category_data['name'] : 'Category';

		$this->deduct_mock_tokens( 200 );

		return array(
			'result'      => array(
				'description' => "Discover our exceptional {$name} collection. Carefully curated to bring you the finest products in this category.",
			),
			'tokens_used' => array(
				'input'  => 100,
				'output' => 100,
				'total'  => 200,
			),
		);
	}

	/**
	 * Mock suggest subcategories response
	 *
	 * @param array $category_data Category data.
	 * @return array
	 */
	private function mock_suggest_subcategories( $category_data ) {
		$this->deduct_mock_tokens( 150 );

		return array(
			'subcategories' => array(
				'Featured Items',
				'New Arrivals',
				'Best Sellers',
				'Sale Items',
			),
			'tokens_used'   => array(
				'input'  => 75,
				'output' => 75,
				'total'  => 150,
			),
		);
	}
}
