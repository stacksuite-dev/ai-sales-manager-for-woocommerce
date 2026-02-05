<?php
/**
 * AJAX Handler Base Class
 *
 * Abstract base class for all AJAX handlers providing common
 * security checks, validation methods, and response utilities.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract AJAX Handler Base Class
 */
abstract class AISales_Ajax_Base {

	/**
	 * Nonce action name
	 *
	 * @var string
	 */
	protected $nonce_action = 'aisales_nonce';

	/**
	 * Nonce field name
	 *
	 * @var string
	 */
	protected $nonce_field = 'nonce';

	/**
	 * Required capability for handlers
	 *
	 * @var string
	 */
	protected $required_capability = 'manage_woocommerce';

	/**
	 * Constructor - registers AJAX actions
	 */
	public function __construct() {
		$this->register_actions();
	}

	/**
	 * Register AJAX actions
	 *
	 * Child classes must implement this method to register their specific AJAX actions.
	 *
	 * @return void
	 */
	abstract protected function register_actions();

	/**
	 * Verify nonce and check permissions
	 *
	 * Sends JSON error and exits if verification fails.
	 *
	 * @param string|null $capability Optional. Override default capability check.
	 * @return void
	 */
	protected function verify_request( $capability = null ) {
		check_ajax_referer( $this->nonce_action, $this->nonce_field );

		$cap = $capability ?? $this->required_capability;
		if ( ! current_user_can( $cap ) ) {
			wp_send_json_error( array(
				'message' => __( 'Permission denied.', 'stacksuite-sales-manager-for-woocommerce' ),
			) );
		}
	}

	/**
	 * Verify nonce only (for public endpoints)
	 *
	 * @return void
	 */
	protected function verify_nonce() {
		check_ajax_referer( $this->nonce_action, $this->nonce_field );
	}

	/**
	 * Get sanitized POST value
	 *
	 * @param string $key     POST key.
	 * @param string $type    Sanitization type: 'text', 'email', 'int', 'bool', 'key', 'textarea', 'url', 'raw'.
	 * @param mixed  $default Default value if not set.
	 * @return mixed Sanitized value.
	 */
	protected function get_post( $key, $type = 'text', $default = '' ) {
		$nonce_value = isset( $_POST[ $this->nonce_field ] )
			? sanitize_text_field( wp_unslash( $_POST[ $this->nonce_field ] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce_value, $this->nonce_action ) ) {
			return $default;
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		switch ( $type ) {
			case 'text':
				return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			case 'email':
				return sanitize_email( wp_unslash( $_POST[ $key ] ) );
			case 'int':
				return absint( wp_unslash( $_POST[ $key ] ) );
			case 'bool':
				return filter_var( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), FILTER_VALIDATE_BOOLEAN );
			case 'key':
				return sanitize_key( wp_unslash( $_POST[ $key ] ) );
			case 'textarea':
				return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
			case 'url':
				return esc_url_raw( wp_unslash( $_POST[ $key ] ) );
			case 'raw':
				return wp_kses_post( wp_unslash( $_POST[ $key ] ) );
			default:
				return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		}
	}

	/**
	 * Get and validate required POST value
	 *
	 * Sends JSON error and exits if value is empty.
	 *
	 * @param string $key     POST key.
	 * @param string $type    Sanitization type.
	 * @param string $message Error message if empty.
	 * @return mixed Sanitized value.
	 */
	protected function require_post( $key, $type = 'text', $message = '' ) {
		$value = $this->get_post( $key, $type );

		if ( empty( $value ) && 0 !== $value && '0' !== $value ) {
			$error_message = $message ?: sprintf(
				/* translators: %s: field name */
				__( '%s is required.', 'stacksuite-sales-manager-for-woocommerce' ),
				ucfirst( str_replace( '_', ' ', $key ) )
			);
			wp_send_json_error( array( 'message' => $error_message ) );
		}

		return $value;
	}

	/**
	 * Get JSON decoded POST value
	 *
	 * Decodes JSON first, then sanitizes HTML content within the decoded data.
	 * This prevents wp_kses_post from corrupting JSON structure (escaped quotes, etc).
	 *
	 * @param string $key      POST key.
	 * @param bool   $assoc    Return associative array.
	 * @param mixed  $default  Default value if not set or invalid JSON.
	 * @param bool   $sanitize Whether to sanitize HTML content in decoded values.
	 * @return mixed Decoded JSON value.
	 */
	protected function get_json_post( $key, $assoc = true, $default = array(), $sanitize = true ) {
		$nonce_value = isset( $_POST[ $this->nonce_field ] )
			? sanitize_text_field( wp_unslash( $_POST[ $this->nonce_field ] ) )
			: '';
		if ( ! wp_verify_nonce( $nonce_value, $this->nonce_action ) ) {
			return $default;
		}

		if ( ! isset( $_POST[ $key ] ) ) {
			return $default;
		}

		// Get raw value without wp_kses_post to preserve JSON structure.
		$raw = wp_unslash( $_POST[ $key ] );

		if ( empty( $raw ) ) {
			return $default;
		}

		$decoded = json_decode( $raw, $assoc );

		if ( null === $decoded ) {
			return $default;
		}

		// Sanitize HTML content after JSON decoding.
		if ( $sanitize ) {
			$decoded = $this->sanitize_json_values( $decoded );
		}

		return $decoded;
	}

	/**
	 * Recursively sanitize HTML content in decoded JSON values
	 *
	 * @param mixed $data Decoded JSON data.
	 * @return mixed Sanitized data.
	 */
	private function sanitize_json_values( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->sanitize_json_values( $value );
			}
			return $data;
		}

		if ( is_string( $data ) ) {
			return wp_kses_post( $data );
		}

		return $data;
	}

	/**
	 * Get API client instance
	 *
	 * @return AISales_API_Client
	 */
	protected function api() {
		return AISales_API_Client::instance();
	}

	/**
	 * Send success response with optional data
	 *
	 * @param mixed $data Response data.
	 * @return void
	 */
	protected function success( $data = array() ) {
		wp_send_json_success( $data );
	}

	/**
	 * Send error response
	 *
	 * @param string $message Error message.
	 * @param array  $data    Optional additional data.
	 * @return void
	 */
	protected function error( $message, $data = array() ) {
		$response = array_merge( array( 'message' => $message ), $data );
		wp_send_json_error( $response );
	}

	/**
	 * Handle WP_Error from API calls
	 *
	 * @param WP_Error|mixed $result API result.
	 * @return mixed Returns result if not an error, sends JSON error otherwise.
	 */
	protected function handle_api_result( $result ) {
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = isset( $error_data['status'] ) ? $error_data['status'] : 'unknown';

			$this->error( $result->get_error_message(), array( 'status' => $status ) );
		}

		return $result;
	}

	/**
	 * Get and validate a WooCommerce product
	 *
	 * @param int $product_id Product ID.
	 * @return WC_Product Product object.
	 */
	protected function get_validated_product( $product_id ) {
		if ( ! $product_id ) {
			$this->error( __( 'Invalid product.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			$this->error( __( 'Product not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		return $product;
	}

	/**
	 * Get and validate a WooCommerce category term
	 *
	 * @param int $term_id Term ID.
	 * @return WP_Term Term object.
	 */
	protected function get_validated_category( $term_id ) {
		if ( ! $term_id ) {
			$this->error( __( 'Invalid category.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$term = get_term( $term_id, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			$this->error( __( 'Category not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		return $term;
	}

	/**
	 * Register a single AJAX action
	 *
	 * @param string $action  Action name (without 'aisales_' prefix).
	 * @param string $method  Handler method name.
	 * @param bool   $nopriv  Also register for non-logged-in users.
	 * @return void
	 */
	protected function add_action( $action, $method, $nopriv = false ) {
		add_action( 'wp_ajax_aisales_' . $action, array( $this, $method ) );

		if ( $nopriv ) {
			add_action( 'wp_ajax_nopriv_aisales_' . $action, array( $this, $method ) );
		}
	}
}
