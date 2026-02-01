<?php
/**
 * API Client for AI Sales Manager SaaS
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Load mock data provider for development/testing.
require_once __DIR__ . '/class-aisales-api-mock.php';

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
				/* translators: %1$d: HTTP status code, %2$s: error message */
				$error_message = sprintf( __( 'API error %1$d: %2$s', 'ai-sales-manager-for-woocommerce' ), $status_code, $body );
			} else {
				/* translators: %d: HTTP status code */
				$error_message = sprintf( __( 'API error %d', 'ai-sales-manager-for-woocommerce' ), $status_code );
			}
			return new WP_Error( 'api_error', $error_message, array( 'status' => $status_code, 'body' => $body ) );
		}

		return $data;
	}

	/**
	 * Connect WordPress site (domain-based authentication)
	 * New accounts receive 10,000 welcome bonus tokens
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array|WP_Error
	 */
	public function connect( $email, $domain ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::connect( $email, $domain );
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
			return AISales_API_Mock::get_account();
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
			return AISales_API_Mock::get_usage( $limit, $offset );
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
			return AISales_API_Mock::get_transactions( $limit, $offset );
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
			return AISales_API_Mock::create_checkout( $plan );
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
			return AISales_API_Mock::generate_content( $product_data );
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
			return AISales_API_Mock::suggest_taxonomy( $product_data );
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
			return AISales_API_Mock::generate_image( $product_data );
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
			return AISales_API_Mock::improve_image( $image_data );
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
			return AISales_API_Mock::generate_category_content( $category_data );
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
			return AISales_API_Mock::suggest_subcategories( $category_data );
		}

		return $this->request( '/ai/category/subcategories', 'POST', $category_data );
	}

	/**
	 * Generate email template using AI
	 *
	 * @param array $data Template generation data (template_type, store_context, etc.).
	 * @return array|WP_Error
	 */
	public function generate_email_template( $data ) {
		return $this->request( '/ai/email/generate', 'POST', $data );
	}

	/**
	 * Analyze store brand and suggest settings
	 *
	 * @param array $context Store analysis context data.
	 * @return array|WP_Error
	 */
	public function analyze_brand( $context ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::analyze_brand( $context );
		}

		return $this->request( '/ai/brand/analyze', 'POST', $context );
	}

	/**
	 * Generate SEO-optimized content (titles, meta descriptions, content, keywords)
	 *
	 * @param array $data SEO generation data including:
	 *   - fix_type: 'title', 'meta_description', 'content', or 'keyword'
	 *   - item: Item context (type, id, title, description, etc.)
	 *   - issue: Current SEO issue details
	 *   - store_context: Optional brand/store context
	 *   - requirements: Optional length and quality requirements
	 *   - language: Optional language (default 'English')
	 * @return array|WP_Error
	 */
	public function generate_seo_content( $data ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::generate_seo_content( $data );
		}

		return $this->request( '/ai/seo/generate', 'POST', $data );
	}

	// =========================================================================
	// SUPPORT METHODS
	// =========================================================================

	/**
	 * Create support draft and run AI analysis
	 *
	 * @param array $data Draft payload.
	 * @return array|WP_Error
	 */
	public function create_support_draft( $data ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::create_support_draft( $data );
		}

		return $this->request( '/support/draft', 'POST', $data );
	}

	/**
	 * Clarify a support draft
	 *
	 * @param string $draft_id Draft ID.
	 * @param array  $answers  Clarification answers.
	 * @return array|WP_Error
	 */
	public function clarify_support_draft( $draft_id, $answers ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::clarify_support_draft( $draft_id, $answers );
		}

		return $this->request( '/support/draft/' . $draft_id . '/clarify', 'POST', array(
			'answers' => $answers,
		) );
	}

	/**
	 * Submit support ticket
	 *
	 * @param string $draft_id Draft ID.
	 * @return array|WP_Error
	 */
	public function submit_support_ticket( $draft_id ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::submit_support_ticket( $draft_id );
		}

		return $this->request( '/support/submit/' . $draft_id, 'POST' );
	}

	/**
	 * Get support tickets
	 *
	 * @param array $filters Filters for list.
	 * @return array|WP_Error
	 */
	public function get_support_tickets( $filters = array() ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::get_support_tickets();
		}

		$query = empty( $filters ) ? '' : '?' . http_build_query( $filters );
		return $this->request( '/support/tickets' . $query );
	}

	/**
	 * Get support ticket details
	 *
	 * @param string $ticket_id Ticket ID.
	 * @return array|WP_Error
	 */
	public function get_support_ticket( $ticket_id ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::get_support_ticket( $ticket_id );
		}

		return $this->request( '/support/tickets/' . $ticket_id );
	}

	/**
	 * Reply to a support ticket
	 *
	 * @param string $ticket_id Ticket ID.
	 * @param string $message   Reply message.
	 * @return array|WP_Error
	 */
	public function reply_support_ticket( $ticket_id, $message ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::reply_support_ticket( $ticket_id, $message );
		}

		return $this->request( '/support/tickets/' . $ticket_id . '/reply', 'POST', array(
			'message' => $message,
		) );
	}

	/**
	 * Get support stats
	 *
	 * @return array|WP_Error
	 */
	public function get_support_stats() {
		if ( $this->use_mock ) {
			return AISales_API_Mock::get_support_stats();
		}

		return $this->request( '/support/stats' );
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
			return AISales_API_Mock::get_auto_topup_settings();
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
			return AISales_API_Mock::update_auto_topup_settings( $settings );
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
			return AISales_API_Mock::get_payment_method();
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
			return AISales_API_Mock::setup_payment_method( $success_url );
		}

		return $this->request( '/billing/setup-payment-method', 'POST', array(
			'success_url' => $success_url,
			'cancel_url'  => $cancel_url,
		) );
	}

	/**
	 * Confirm a setup session and save payment method
	 *
	 * @param string $session_id Stripe checkout session ID.
	 * @return array|WP_Error
	 */
	public function confirm_setup( $session_id ) {
		if ( $this->use_mock ) {
			return array( 'confirmed' => true );
		}

		return $this->request( '/billing/confirm-setup', 'POST', array(
			'session_id' => $session_id,
		) );
	}

	/**
	 * Remove saved payment method
	 *
	 * @return array|WP_Error
	 */
	public function remove_payment_method() {
		if ( $this->use_mock ) {
			return AISales_API_Mock::remove_payment_method();
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
			return AISales_API_Mock::get_purchases( $limit, $offset );
		}

		return $this->request( "/billing/purchases?limit={$limit}&offset={$offset}" );
	}

	/**
	 * Get available token plans
	 *
	 * @return array|WP_Error
	 */
	public function get_plans() {
		if ( $this->use_mock ) {
			return AISales_API_Mock::get_plans();
		}

		return $this->request( '/billing/plans' );
	}

	/**
	 * Request API key recovery token
	 * This creates a recovery token that can be sent via email
	 * Requires both email AND domain to match for security.
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array|WP_Error Returns token data or error
	 */
	public function create_recovery_token( $email, $domain ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::create_recovery_token( $email, $domain );
		}

		return $this->request_without_auth( '/auth/create-recovery-token', 'POST', array(
			'email'  => $email,
			'domain' => $domain,
		) );
	}

	/**
	 * Validate recovery token and get new API key
	 *
	 * @param string $token Recovery token.
	 * @return array|WP_Error Returns new API key or error
	 */
	public function validate_recovery_token( $token ) {
		if ( $this->use_mock ) {
			return AISales_API_Mock::validate_recovery_token( $token );
		}

		return $this->request_without_auth( '/auth/validate-recovery-token', 'POST', array(
			'token' => $token,
		) );
	}

	/**
	 * Make API request without authentication
	 * Used for auth endpoints before we have an API key
	 *
	 * @param string $endpoint API endpoint.
	 * @param string $method   HTTP method.
	 * @param array  $body     Request body.
	 * @return array|WP_Error
	 */
	private function request_without_auth( $endpoint, $method = 'GET', $body = array() ) {
		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 30,
			'sslverify' => apply_filters( 'aisales_api_sslverify', true ),
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$url      = $this->api_url . $endpoint;
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body_content = wp_remote_retrieve_body( $response );
		$data         = json_decode( $body_content, true );
		$status_code  = wp_remote_retrieve_response_code( $response );

		if ( $status_code >= 400 ) {
			$message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'API request failed', 'ai-sales-manager-for-woocommerce' );
			return new WP_Error(
				'api_error',
				$message,
				array( 'status' => $status_code )
			);
		}

		return isset( $data['data'] ) ? $data['data'] : $data;
	}
}
