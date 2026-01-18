<?php
/**
 * API Client for WooAI SaaS
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * API Client class
 */
class WooAI_API_Client {

	/**
	 * Single instance
	 *
	 * @var WooAI_API_Client
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
	 * @return WooAI_API_Client
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
		$this->api_url = apply_filters( 'wooai_api_url', WOOAI_API_URL );

		// Mock mode: disabled by default, can be enabled via filter or constant
		// Use: define('WOOAI_MOCK_MODE', true) in wp-config.php for testing
		$this->use_mock = defined( 'WOOAI_MOCK_MODE' ) && WOOAI_MOCK_MODE;
		$this->use_mock = apply_filters( 'wooai_use_mock', $this->use_mock );
	}

	/**
	 * Get stored API key
	 *
	 * @return string|false
	 */
	public function get_api_key() {
		return get_option( 'wooai_api_key', false );
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
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->api_url . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code >= 400 ) {
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'API request failed', 'woo-ai-sales-manager' );
			return new WP_Error( 'api_error', $error_message, array( 'status' => $status_code ) );
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

		$admin_url = admin_url( 'admin.php?page=woo-ai-manager' );

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
		update_option( 'wooai_api_key', $api_key );
		update_option( 'wooai_user_email', $email );
		update_option( 'wooai_balance', 1000 ); // Start with 1000 tokens for testing

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
		$api_key = get_option( 'wooai_api_key' );
		if ( ! $api_key ) {
			$api_key = 'wai_mock_' . wp_generate_password( 32, false );
			update_option( 'wooai_api_key', $api_key );
		}
		update_option( 'wooai_user_email', $email );

		$balance = get_option( 'wooai_balance', 7432 );

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
		$api_key = get_option( 'wooai_api_key' );
		$is_new  = false;

		if ( ! $api_key ) {
			$api_key = 'wai_mock_' . wp_generate_password( 32, false );
			$is_new  = true;
			update_option( 'wooai_balance', 1000 );
		}

		update_option( 'wooai_api_key', $api_key );
		update_option( 'wooai_user_email', $email );
		update_option( 'wooai_domain', $domain );

		$balance = get_option( 'wooai_balance', 1000 );

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
			'email'          => get_option( 'wooai_user_email', 'demo@example.com' ),
			'balance_tokens' => get_option( 'wooai_balance', 7432 ),
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
		$current_balance = get_option( 'wooai_balance', 0 );
		update_option( 'wooai_balance', $current_balance + 10000 );

		return array(
			'checkout_url' => admin_url( 'admin.php?page=woo-ai-manager&topup=success' ),
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
		$balance = get_option( 'wooai_balance', 7432 );
		$new_balance = max( 0, $balance - $tokens );
		update_option( 'wooai_balance', $new_balance );
	}
}
