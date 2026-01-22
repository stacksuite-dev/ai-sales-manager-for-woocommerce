<?php
/**
 * AJAX Handlers
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX Handlers class
 */
class AISales_Ajax_Handlers {

	/**
	 * Single instance
	 *
	 * @var AISales_Ajax_Handlers
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Ajax_Handlers
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
		// Auth actions (domain-based)
		add_action( 'wp_ajax_aisales_connect', array( $this, 'handle_connect' ) );

		// Billing actions
		add_action( 'wp_ajax_aisales_topup', array( $this, 'handle_topup' ) );
		add_action( 'wp_ajax_aisales_quick_topup', array( $this, 'handle_quick_topup' ) );
		add_action( 'wp_ajax_aisales_update_auto_topup', array( $this, 'handle_update_auto_topup' ) );
		add_action( 'wp_ajax_aisales_setup_payment_method', array( $this, 'handle_setup_payment_method' ) );
		add_action( 'wp_ajax_aisales_confirm_setup', array( $this, 'handle_confirm_setup' ) );
		add_action( 'wp_ajax_aisales_remove_payment_method', array( $this, 'handle_remove_payment_method' ) );

		// AI actions
		add_action( 'wp_ajax_aisales_generate_content', array( $this, 'handle_generate_content' ) );
		add_action( 'wp_ajax_aisales_suggest_taxonomy', array( $this, 'handle_suggest_taxonomy' ) );
		add_action( 'wp_ajax_aisales_generate_image', array( $this, 'handle_generate_image' ) );
		add_action( 'wp_ajax_aisales_improve_image', array( $this, 'handle_improve_image' ) );

		// Account actions
		add_action( 'wp_ajax_aisales_get_balance', array( $this, 'handle_get_balance' ) );

		// Chat actions
		add_action( 'wp_ajax_aisales_update_product_field', array( $this, 'handle_update_product_field' ) );
		add_action( 'wp_ajax_aisales_update_category_field', array( $this, 'handle_update_category_field' ) );
		add_action( 'wp_ajax_aisales_get_category', array( $this, 'handle_get_category' ) );

		// Store context actions
		add_action( 'wp_ajax_aisales_save_store_context', array( $this, 'handle_save_store_context' ) );
		add_action( 'wp_ajax_aisales_sync_store_context', array( $this, 'handle_sync_store_context' ) );
		add_action( 'wp_ajax_aisales_mark_chat_visited', array( $this, 'handle_mark_chat_visited' ) );

		// Balance sync action
		add_action( 'wp_ajax_aisales_sync_balance', array( $this, 'handle_sync_balance' ) );

		// Agent mode actions
		add_action( 'wp_ajax_aisales_save_generated_image', array( $this, 'handle_save_generated_image' ) );
		add_action( 'wp_ajax_aisales_get_store_summary', array( $this, 'handle_get_store_summary' ) );
		add_action( 'wp_ajax_aisales_set_product_featured_image', array( $this, 'handle_set_product_featured_image' ) );
		add_action( 'wp_ajax_aisales_set_category_thumbnail', array( $this, 'handle_set_category_thumbnail' ) );

		// Tool calling actions (AI Agent data requests)
		add_action( 'wp_ajax_aisales_fetch_tool_data', array( $this, 'handle_fetch_tool_data' ) );

		// Catalog organization actions
		add_action( 'wp_ajax_aisales_apply_catalog_change', array( $this, 'handle_apply_catalog_change' ) );

		// Email template actions
		add_action( 'wp_ajax_aisales_get_email_templates', array( $this, 'handle_get_email_templates' ) );
		add_action( 'wp_ajax_aisales_save_email_template', array( $this, 'handle_save_email_template' ) );
		add_action( 'wp_ajax_aisales_generate_email_template', array( $this, 'handle_generate_email_template' ) );
		add_action( 'wp_ajax_aisales_preview_email_template', array( $this, 'handle_preview_email_template' ) );
		add_action( 'wp_ajax_aisales_toggle_email_template', array( $this, 'handle_toggle_email_template' ) );
		add_action( 'wp_ajax_aisales_delete_email_template', array( $this, 'handle_delete_email_template' ) );
		add_action( 'wp_ajax_aisales_send_test_email', array( $this, 'handle_send_test_email' ) );
		add_action( 'wp_ajax_aisales_save_mail_provider_settings', array( $this, 'handle_save_mail_provider_settings' ) );
		add_action( 'wp_ajax_aisales_send_mail_provider_test', array( $this, 'handle_send_mail_provider_test' ) );

		// Bulk enhancement actions
		add_action( 'wp_ajax_aisales_apply_batch_result', array( $this, 'handle_apply_batch_result' ) );

		// Email wizard actions
		add_action( 'wp_ajax_aisales_save_wizard_context', array( $this, 'handle_save_wizard_context' ) );
		add_action( 'wp_ajax_aisales_complete_email_wizard', array( $this, 'handle_complete_email_wizard' ) );

		// Abandoned cart actions
		add_action( 'wp_ajax_aisales_create_abandoned_cart_order', array( $this, 'handle_create_abandoned_cart_order' ) );

		// Brand settings page actions
		add_action( 'wp_ajax_aisales_save_brand_settings', array( $this, 'handle_save_brand_settings' ) );
		add_action( 'wp_ajax_aisales_analyze_brand', array( $this, 'handle_analyze_brand' ) );

		// Support actions
		add_action( 'wp_ajax_aisales_support_draft', array( $this, 'handle_support_draft' ) );
		add_action( 'wp_ajax_aisales_support_clarify', array( $this, 'handle_support_clarify' ) );
		add_action( 'wp_ajax_aisales_support_submit', array( $this, 'handle_support_submit' ) );
		add_action( 'wp_ajax_aisales_support_list', array( $this, 'handle_support_list' ) );
		add_action( 'wp_ajax_aisales_support_stats', array( $this, 'handle_support_stats' ) );
		add_action( 'wp_ajax_aisales_support_upload', array( $this, 'handle_support_upload' ) );

		// API key recovery actions (no auth required for recovery request)
		add_action( 'wp_ajax_aisales_request_api_key_recovery', array( $this, 'handle_request_api_key_recovery' ) );
		add_action( 'wp_ajax_nopriv_aisales_request_api_key_recovery', array( $this, 'handle_request_api_key_recovery' ) );
		add_action( 'wp_ajax_aisales_validate_recovery_token', array( $this, 'handle_validate_recovery_token' ) );
		add_action( 'wp_ajax_nopriv_aisales_validate_recovery_token', array( $this, 'handle_validate_recovery_token' ) );
	}

	/**
	 * Handle support draft creation
	 */
	public function handle_support_draft() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$category    = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : 'support';
		$attachments_raw = isset( $_POST['attachments'] ) ? wp_unslash( $_POST['attachments'] ) : '';
		$attachments = array();
		if ( ! empty( $attachments_raw ) ) {
			$decoded = json_decode( $attachments_raw, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $attachment ) {
					if ( empty( $attachment['filename'] ) || empty( $attachment['url'] ) ) {
						continue;
					}
					$attachments[] = array(
						'filename'  => sanitize_file_name( $attachment['filename'] ),
						'mime_type' => isset( $attachment['mime_type'] ) ? sanitize_text_field( $attachment['mime_type'] ) : 'application/octet-stream',
						'url'       => esc_url_raw( $attachment['url'] ),
						'size_bytes' => isset( $attachment['size_bytes'] ) ? absint( $attachment['size_bytes'] ) : 0,
					);
				}
			}
		}

		if ( empty( $title ) || empty( $description ) ) {
			wp_send_json_error( array( 'message' => __( 'Title and description are required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->create_support_draft( array(
			'title'       => $title,
			'description' => $description,
			'category'    => $category,
			'attachments' => $attachments,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle support attachment upload
	 */
	public function handle_support_upload() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		if ( empty( $_FILES['attachment'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file = $_FILES['attachment'];
		if ( $file['size'] > 7 * 1024 * 1024 ) {
			wp_send_json_error( array( 'message' => __( 'File exceeds 7MB limit.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$attachment_id = media_handle_upload( 'attachment', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		$url = wp_get_attachment_url( $attachment_id );
		$mime = get_post_mime_type( $attachment_id );
		$filename = get_the_title( $attachment_id );

		wp_send_json_success( array(
			'id'       => $attachment_id,
			'url'      => $url,
			'filename' => $filename,
			'mime_type' => $mime,
			'size'     => isset( $file['size'] ) ? absint( $file['size'] ) : 0,
		) );
	}

	/**
	 * Handle support clarification
	 */
	public function handle_support_clarify() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$draft_id = isset( $_POST['draft_id'] ) ? sanitize_text_field( wp_unslash( $_POST['draft_id'] ) ) : '';
		$answers  = isset( $_POST['answers'] ) ? (array) wp_unslash( $_POST['answers'] ) : array();

		if ( empty( $draft_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Draft ID is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->clarify_support_draft( $draft_id, $answers );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle support ticket submission
	 */
	public function handle_support_submit() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$draft_id = isset( $_POST['draft_id'] ) ? sanitize_text_field( wp_unslash( $_POST['draft_id'] ) ) : '';

		if ( empty( $draft_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Draft ID is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->submit_support_ticket( $draft_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle support ticket list
	 */
	public function handle_support_list() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$filters = array();
		if ( isset( $_POST['status'] ) ) {
			$filters['status'] = sanitize_key( wp_unslash( $_POST['status'] ) );
		}
		if ( isset( $_POST['search'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_POST['search'] ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->get_support_tickets( $filters );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle support stats
	 */
	public function handle_support_stats() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->get_support_stats();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle creating an order from an abandoned cart.
	 */
	public function handle_create_abandoned_cart_order() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$cart_id = isset( $_POST['cart_id'] ) ? absint( wp_unslash( $_POST['cart_id'] ) ) : 0;
		if ( ! $cart_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid cart ID.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();
		$cart  = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $cart_id ),
			ARRAY_A
		);

		if ( empty( $cart ) ) {
			wp_send_json_error( array( 'message' => __( 'Cart not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		if ( ! empty( $cart['order_id'] ) ) {
			$order = wc_get_order( absint( $cart['order_id'] ) );
			if ( $order ) {
				wp_send_json_success( $this->build_abandoned_cart_order_response( $order, $cart_id ) );
			}
		}

		$cart_items = isset( $cart['cart_items'] ) ? $cart['cart_items'] : '';
		if ( function_exists( 'wp_json_decode' ) ) {
			$items = $cart_items ? wp_json_decode( $cart_items, true ) : array();
		} else {
			$items = $cart_items ? json_decode( $cart_items, true ) : array();
		}
		if ( empty( $items ) || ! is_array( $items ) ) {
			wp_send_json_error( array( 'message' => __( 'No cart items found for this cart.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$order = wc_create_order();
		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ) );
		}

		foreach ( $items as $item ) {
			$product_id = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
			$quantity   = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
			if ( ! $product_id ) {
				continue;
			}
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$order->add_product( $product, max( 1, $quantity ) );
		}

		if ( $order->get_item_count() === 0 ) {
			wp_send_json_error( array( 'message' => __( 'No valid products found for this cart.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email = isset( $cart['email'] ) ? sanitize_email( $cart['email'] ) : '';
		if ( $email ) {
			$order->set_billing_email( $email );
		}
		if ( ! empty( $cart['user_id'] ) ) {
			$order->set_customer_id( absint( $cart['user_id'] ) );
		}

		$order->calculate_totals();
		$order->update_status( 'pending', __( 'Created from abandoned cart.', 'ai-sales-manager-for-woocommerce' ) );
		$order->save();

		$wpdb->update(
			$table,
			array(
				'order_id'   => $order->get_id(),
				'status'     => 'order_created',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $cart_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		update_post_meta( $order->get_id(), '_aisales_abandoned_cart_id', $cart_id );

		wp_send_json_success( $this->build_abandoned_cart_order_response( $order, $cart_id ) );
	}

	/**
	 * Build response payload for created abandoned cart orders.
	 *
	 * @param WC_Order $order Order instance.
	 * @param int      $cart_id Cart ID.
	 * @return array
	 */
	private function build_abandoned_cart_order_response( $order, $cart_id ) {
		return array(
			'order_id'    => $order->get_id(),
			'cart_id'     => $cart_id,
			'payment_url' => $order->get_checkout_payment_url(),
			'edit_url'    => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'success'     => __( 'Order created. Share the payment link with the customer.', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get and validate a product by ID.
	 *
	 * Validates the product ID and returns the WC_Product.
	 * Sends JSON error response and halts execution if validation fails.
	 *
	 * @param int $product_id The product ID to validate.
	 * @return WC_Product The validated product object.
	 */
	private function get_validated_product( $product_id ) {
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		return $product;
	}

	/**
	 * Handle connect (domain-based auth)
	 * New accounts receive 10,000 welcome bonus tokens
	 */
	public function handle_connect() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$domain = isset( $_POST['domain'] ) ? sanitize_text_field( wp_unslash( $_POST['domain'] ) ) : '';

		if ( empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Email is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		if ( empty( $domain ) ) {
			wp_send_json_error( array( 'message' => __( 'Domain is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->connect( $email, $domain );

		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = isset( $error_data['status'] ) ? $error_data['status'] : 'unknown';
			$message    = $result->get_error_message();

			// Log for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AISales connect failed - Status: %s, Message: %s', $status, $message ) );
			}

			wp_send_json_error( array(
				'message' => $message,
				'status'  => $status,
			) );
		}

		// Store credentials
		update_option( 'aisales_api_key', $result['api_key'] );
		update_option( 'aisales_user_email', $result['email'] );
		update_option( 'aisales_balance', $result['balance_tokens'] );
		update_option( 'aisales_domain', $result['domain'] );

		$is_new        = isset( $result['is_new'] ) && $result['is_new'];
		$welcome_bonus = isset( $result['welcome_bonus'] ) ? intval( $result['welcome_bonus'] ) : 0;

		// Build appropriate success message
		if ( $is_new && $welcome_bonus > 0 ) {
			$message = sprintf(
				/* translators: %s: number of tokens */
				__( 'Account created! You received %s free tokens to get started.', 'ai-sales-manager-for-woocommerce' ),
				number_format_i18n( $welcome_bonus )
			);
		} elseif ( $is_new ) {
			$message = __( 'Account created successfully!', 'ai-sales-manager-for-woocommerce' );
		} else {
			$message = __( 'Connected successfully!', 'ai-sales-manager-for-woocommerce' );
		}

		wp_send_json_success( array(
			'message'       => $message,
			'is_new'        => $is_new,
			'welcome_bonus' => $welcome_bonus,
			'redirect'      => admin_url( 'admin.php?page=ai-sales-manager' ),
		) );
	}

	/**
	 * Handle top-up
	 */
	public function handle_topup() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->create_checkout();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'checkout_url' => $result['checkout_url'],
		) );
	}

	/**
	 * Handle quick top-up with specific plan
	 */
	public function handle_quick_topup() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$plan_id = isset( $_POST['plan_id'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_id'] ) ) : '';

		if ( empty( $plan_id ) ) {
			wp_send_json_error( array( 'message' => __( 'No plan selected.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Validate plan ID
		$valid_plans = array( 'starter_plan', 'standard_plan', 'pro_plan', 'business_plan' );
		if ( ! in_array( $plan_id, $valid_plans, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid plan selected.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->create_checkout( $plan_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'checkout_url' => $result['checkout_url'],
		) );
	}

	/**
	 * Handle update auto top-up settings
	 */
	public function handle_update_auto_topup() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$enabled      = isset( $_POST['enabled'] ) && '1' === $_POST['enabled'];
		$threshold    = isset( $_POST['threshold'] ) ? absint( wp_unslash( $_POST['threshold'] ) ) : 1000;
		$product_slug = isset( $_POST['product_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['product_slug'] ) ) : 'standard_plan';

		// Validate threshold
		$allowed_thresholds = array( 500, 1000, 2000, 5000 );
		if ( ! in_array( $threshold, $allowed_thresholds, true ) ) {
			$threshold = 1000;
		}

		$api    = AISales_API_Client::instance();
		$result = $api->update_auto_topup_settings( array(
			'enabled'     => $enabled,
			'threshold'   => $threshold,
			'productSlug' => $product_slug,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'  => $enabled
				? __( 'Auto top-up enabled.', 'ai-sales-manager-for-woocommerce' )
				: __( 'Auto top-up disabled.', 'ai-sales-manager-for-woocommerce' ),
			'settings' => $result,
		) );
	}

	/**
	 * Handle setup payment method
	 */
	public function handle_setup_payment_method() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$success_url = isset( $_POST['success_url'] ) ? esc_url_raw( wp_unslash( $_POST['success_url'] ) ) : '';
		$cancel_url  = isset( $_POST['cancel_url'] ) ? esc_url_raw( wp_unslash( $_POST['cancel_url'] ) ) : '';

		if ( empty( $success_url ) ) {
			$success_url = admin_url( 'admin.php?page=ai-sales-manager&tab=billing&payment_setup=success' );
		}
		if ( empty( $cancel_url ) ) {
			$cancel_url = admin_url( 'admin.php?page=ai-sales-manager&tab=billing&payment_setup=cancelled' );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->setup_payment_method( $success_url, $cancel_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'setup_url'  => $result['setup_url'],
			'session_id' => isset( $result['session_id'] ) ? $result['session_id'] : '',
		) );
	}

	/**
	 * Handle confirm setup session
	 */
	public function handle_confirm_setup() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

		if ( empty( $session_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Session ID required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->confirm_setup( $session_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'confirmed' => true,
		) );
	}

	/**
	 * Handle remove payment method
	 */
	public function handle_remove_payment_method() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->remove_payment_method();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Payment method removed.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle generate content
	 */
	public function handle_generate_content() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = $this->get_validated_product( $product_id );
		$action     = isset( $_POST['ai_action'] ) ? sanitize_key( wp_unslash( $_POST['ai_action'] ) ) : 'improve';

		$api    = AISales_API_Client::instance();
		$result = $api->generate_content( array(
			'product_title'       => $product->get_name(),
			'product_description' => $product->get_description(),
			'action'              => $action,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Use balance from API response, fallback to stored option for mock mode.
		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'result'      => $result['result'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle suggest taxonomy
	 */
	public function handle_suggest_taxonomy() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = $this->get_validated_product( $product_id );

		$api    = AISales_API_Client::instance();
		$result = $api->suggest_taxonomy( array(
			'product_title'       => $product->get_name(),
			'product_description' => $product->get_description(),
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Use balance from API response, fallback to stored option for mock mode.
		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'categories'  => $result['suggested_categories'],
			'tags'        => $result['suggested_tags'],
			'attributes'  => $result['suggested_attributes'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle generate image
	 */
	public function handle_generate_image() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$product    = $this->get_validated_product( $product_id );
		$style      = isset( $_POST['style'] ) ? sanitize_key( wp_unslash( $_POST['style'] ) ) : 'product_photo';

		$api    = AISales_API_Client::instance();
		$result = $api->generate_image( array(
			'product_title'       => $product->get_name(),
			'product_description' => $product->get_description(),
			'style'               => $style,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Use balance from API response, fallback to stored option for mock mode.
		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'image_url'   => $result['image_url'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle improve image
	 */
	public function handle_improve_image() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Note: product_id validation is needed here but the product is not used.
		// This ensures the user has access to a valid product context.
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$image_url = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$action    = isset( $_POST['improve_action'] ) ? sanitize_key( wp_unslash( $_POST['improve_action'] ) ) : 'enhance';

		if ( empty( $image_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No image selected.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->improve_image( array(
			'image_url' => $image_url,
			'action'    => $action,
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Use balance from API response, fallback to stored option for mock mode.
		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'image_url'   => $result['image_url'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle get balance
	 */
	public function handle_get_balance() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api     = AISales_API_Client::instance();
		$account = $api->get_account();

		if ( is_wp_error( $account ) ) {
			wp_send_json_error( array( 'message' => $account->get_error_message() ) );
		}

		wp_send_json_success( array(
			'balance' => $account['balance_tokens'],
		) );
	}

	/**
	 * Handle update product field
	 * Used by chat to apply AI suggestions to products
	 */
	public function handle_update_product_field() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$field      = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
		$value_raw  = isset( $_POST['value'] ) ? wp_kses_post( wp_unslash( $_POST['value'] ) ) : '';

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Allowed fields
		$allowed_fields = array(
			'title',
			'description',
			'short_description',
			'tags',
			'categories',
		);

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Sanitize value based on field type.
		if ( in_array( $field, array( 'description', 'short_description' ), true ) ) {
			$value = wp_kses_post( $value_raw );
		} else {
			$value = sanitize_text_field( $value_raw );
		}

		// Update the field
		switch ( $field ) {
			case 'title':
				$product->set_name( $value );
				break;

			case 'description':
				$product->set_description( $value );
				break;

			case 'short_description':
				$product->set_short_description( $value );
				break;

			case 'tags':
				$tags = array_map( 'trim', explode( ',', $value ) );
				$tags = array_filter( $tags );
				wp_set_object_terms( $product_id, $tags, 'product_tag' );
				break;

			case 'categories':
				$categories = array_map( 'trim', explode( ',', $value ) );
				$categories = array_filter( $categories );
				$term_ids   = array();

				foreach ( $categories as $cat_name ) {
					$term = get_term_by( 'name', $cat_name, 'product_cat' );
					if ( $term ) {
						$term_ids[] = $term->term_id;
					} else {
						// Create the category if it doesn't exist
						$new_term = wp_insert_term( $cat_name, 'product_cat' );
						if ( ! is_wp_error( $new_term ) ) {
							$term_ids[] = $new_term['term_id'];
						}
					}
				}

				if ( ! empty( $term_ids ) ) {
					wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
				}
				break;
		}

		// Save the product (for title, description, short_description)
		if ( in_array( $field, array( 'title', 'description', 'short_description' ), true ) ) {
			$product->save();
		}

		wp_send_json_success( array(
			'message' => __( 'Product updated successfully.', 'ai-sales-manager-for-woocommerce' ),
			'field'   => $field,
		) );
	}

	/**
	 * Handle apply catalog change
	 * Used by chat to apply AI-suggested catalog reorganization actions
	 */
	public function handle_apply_catalog_change() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$action_type = isset( $_POST['action_type'] ) ? sanitize_key( wp_unslash( $_POST['action_type'] ) ) : '';
		$params_raw  = isset( $_POST['params'] ) ? wp_unslash( $_POST['params'] ) : '';

		if ( empty( $action_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Action type is required.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Decode params JSON if it's a string
		if ( is_string( $params_raw ) ) {
			$params = json_decode( $params_raw, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_send_json_error( array( 'message' => __( 'Invalid params format.', 'ai-sales-manager-for-woocommerce' ) ) );
				return;
			}
		} else {
			$params = is_array( $params_raw ) ? $params_raw : array();
		}

		// Sanitize params
		$params = map_deep( $params, 'sanitize_text_field' );

		// Load tool executor
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-tool-executor.php';
		$executor = new AISales_Tool_Executor();

		// Execute the catalog change
		$result = $executor->execute( 'apply_catalog_change', array_merge(
			array( 'action_type' => $action_type ),
			$params
		) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			return;
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle update category field
	 * Used by chat to apply AI suggestions to categories
	 */
	public function handle_update_category_field() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_product_terms' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$category_id = isset( $_POST['category_id'] ) ? absint( wp_unslash( $_POST['category_id'] ) ) : 0;
		$field       = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
		$value_raw   = isset( $_POST['value'] ) ? wp_kses_post( wp_unslash( $_POST['value'] ) ) : '';

		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Allowed fields
		$allowed_fields = array(
			'name',
			'description',
			'seo_title',
			'meta_description',
		);

		if ( ! in_array( $field, $allowed_fields, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid field.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Sanitize value based on field type.
		if ( 'description' === $field ) {
			$value = wp_kses_post( $value_raw );
		} else {
			$value = sanitize_text_field( $value_raw );
		}

		// Update the field
		switch ( $field ) {
			case 'name':
				wp_update_term( $category_id, 'product_cat', array(
					'name' => $value,
				) );
				break;

			case 'description':
				wp_update_term( $category_id, 'product_cat', array(
					'description' => $value,
				) );
				break;

			case 'seo_title':
				// Store as term meta - works with Yoast SEO and RankMath
				update_term_meta( $category_id, '_yoast_wpseo_title', $value );
				update_term_meta( $category_id, 'rank_math_title', $value );
				update_term_meta( $category_id, 'aisales_seo_title', $value );
				break;

			case 'meta_description':
				// Store as term meta - works with Yoast SEO and RankMath
				update_term_meta( $category_id, '_yoast_wpseo_metadesc', $value );
				update_term_meta( $category_id, 'rank_math_description', $value );
				update_term_meta( $category_id, 'aisales_meta_description', $value );
				break;
		}

		wp_send_json_success( array(
			'message' => __( 'Category updated successfully.', 'ai-sales-manager-for-woocommerce' ),
			'field'   => $field,
		) );
	}

	/**
	 * Handle get category
	 * Used to fetch category data by ID
	 */
	public function handle_get_category() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$category_id = isset( $_POST['category_id'] ) ? absint( wp_unslash( $_POST['category_id'] ) ) : 0;

		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Get parent info
		$parent_name = '';
		if ( $term->parent ) {
			$parent_term = get_term( $term->parent, 'product_cat' );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent_name = $parent_term->name;
			}
		}

		// Get subcategories
		$subcategories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => $category_id,
			'hide_empty' => false,
		) );

		$subcats = array();
		if ( ! is_wp_error( $subcategories ) ) {
			foreach ( $subcategories as $subcat ) {
				$subcats[] = array(
					'id'   => $subcat->term_id,
					'name' => $subcat->name,
				);
			}
		}

		// Get SEO meta
		$seo_title = get_term_meta( $category_id, 'aisales_seo_title', true );
		if ( empty( $seo_title ) ) {
			$seo_title = get_term_meta( $category_id, '_yoast_wpseo_title', true );
		}
		if ( empty( $seo_title ) ) {
			$seo_title = get_term_meta( $category_id, 'rank_math_title', true );
		}

		$meta_description = get_term_meta( $category_id, 'aisales_meta_description', true );
		if ( empty( $meta_description ) ) {
			$meta_description = get_term_meta( $category_id, '_yoast_wpseo_metadesc', true );
		}
		if ( empty( $meta_description ) ) {
			$meta_description = get_term_meta( $category_id, 'rank_math_description', true );
		}

		wp_send_json_success( array(
			'id'                => $term->term_id,
			'name'              => $term->name,
			'slug'              => $term->slug,
			'description'       => $term->description,
			'parent_id'         => $term->parent,
			'parent_name'       => $parent_name,
			'product_count'     => $term->count,
			'subcategory_count' => count( $subcats ),
			'subcategories'     => $subcats,
			'seo_title'         => $seo_title,
			'meta_description'  => $meta_description,
			'edit_url'          => admin_url( 'term.php?taxonomy=product_cat&tag_ID=' . $category_id ),
			'view_url'          => get_term_link( $term ),
		) );
	}

	/**
	 * Handle save store context
	 */
	public function handle_save_store_context() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$context_raw = isset( $_POST['context'] ) ? map_deep( wp_unslash( $_POST['context'] ), 'sanitize_textarea_field' ) : array();
		$context     = is_array( $context_raw ) ? $context_raw : array();

		$store_context = array(
			'store_name'          => isset( $context['store_name'] ) ? sanitize_text_field( $context['store_name'] ) : '',
			'store_description'   => isset( $context['store_description'] ) ? $context['store_description'] : '',
			'business_niche'      => isset( $context['business_niche'] ) ? sanitize_key( $context['business_niche'] ) : '',
			'target_audience'     => isset( $context['target_audience'] ) ? sanitize_text_field( $context['target_audience'] ) : '',
			'brand_tone'          => isset( $context['brand_tone'] ) ? sanitize_key( $context['brand_tone'] ) : '',
			'language'            => isset( $context['language'] ) ? sanitize_text_field( $context['language'] ) : 'English',
			'custom_instructions' => isset( $context['custom_instructions'] ) ? $context['custom_instructions'] : '',
			'updated_at'          => current_time( 'mysql' ),
		);

		// Preserve sync data if it exists
		$existing = get_option( 'aisales_store_context', array() );
		if ( isset( $existing['last_sync'] ) ) {
			$store_context['last_sync']       = $existing['last_sync'];
			$store_context['category_count']  = $existing['category_count'];
			$store_context['product_count']   = $existing['product_count'];
		}

		update_option( 'aisales_store_context', $store_context );

		wp_send_json_success( array(
			'message' => __( 'Store context saved successfully.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle sync store context
	 */
	public function handle_sync_store_context() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Count products and categories
		$product_count = wp_count_posts( 'product' );
		$product_total = isset( $product_count->publish ) ? $product_count->publish : 0;

		$category_count = wp_count_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );

		if ( is_wp_error( $category_count ) ) {
			$category_count = 0;
		}

		// Update store context
		$store_context = get_option( 'aisales_store_context', array() );
		$store_context['last_sync']      = current_time( 'mysql' );
		$store_context['category_count'] = $category_count;
		$store_context['product_count']  = $product_total;

		update_option( 'aisales_store_context', $store_context );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %1$s: category count, %2$s: product count */
				__( 'Synced: %1$s categories, %2$s products', 'ai-sales-manager-for-woocommerce' ),
				number_format_i18n( $category_count ),
				number_format_i18n( $product_total )
			),
		) );
	}

	/**
	 * Handle mark chat visited
	 */
	public function handle_mark_chat_visited() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		update_user_meta( get_current_user_id(), 'aisales_chat_visited', true );

		wp_send_json_success();
	}

	/**
	 * Handle sync balance from chat page
	 */
	public function handle_sync_balance() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$balance = isset( $_POST['balance'] ) ? absint( wp_unslash( $_POST['balance'] ) ) : null;

		if ( null === $balance ) {
			wp_send_json_error( array( 'message' => __( 'Invalid balance value.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		update_option( 'aisales_balance', $balance );

		wp_send_json_success( array( 'balance' => $balance ) );
	}

	/**
	 * Handle save generated image to media library
	 * Used by agent mode to save AI-generated marketing images
	 */
	public function handle_save_generated_image() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$image_data = isset( $_POST['image_data'] ) ? sanitize_text_field( wp_unslash( $_POST['image_data'] ) ) : '';
		$image_url  = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$filename   = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : 'ai-generated-image.png';
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		// Handle image URL (data URI or remote URL)
		if ( empty( $image_data ) && ! empty( $image_url ) ) {
			// Check if it's a data URI
			if ( strpos( $image_url, 'data:image/' ) === 0 ) {
				$image_data = $image_url;
			} else {
				// Fetch from remote URL
				$response = wp_remote_get( $image_url, array(
					'timeout' => 30,
					'sslverify' => false,
				) );

				if ( is_wp_error( $response ) ) {
					wp_send_json_error( array( 'message' => __( 'Failed to download image: ', 'ai-sales-manager-for-woocommerce' ) . $response->get_error_message() ) );
					return;
				}

				$response_code = wp_remote_retrieve_response_code( $response );
				if ( 200 !== $response_code ) {
					wp_send_json_error( array( 'message' => __( 'Failed to download image. HTTP error: ', 'ai-sales-manager-for-woocommerce' ) . $response_code ) );
					return;
				}

				$decoded = wp_remote_retrieve_body( $response );
				if ( empty( $decoded ) ) {
					wp_send_json_error( array( 'message' => __( 'Downloaded image is empty.', 'ai-sales-manager-for-woocommerce' ) ) );
					return;
				}

				// Verify it's a valid image
				$finfo = new finfo( FILEINFO_MIME_TYPE );
				$mime  = $finfo->buffer( $decoded );

				$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
				if ( ! in_array( $mime, $allowed_mimes, true ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid image type from URL.', 'ai-sales-manager-for-woocommerce' ) ) );
					return;
				}

				// Continue with saving the downloaded image
				return $this->save_image_to_media_library( $decoded, $mime, $filename, $title );
			}
		}

		if ( empty( $image_data ) ) {
			wp_send_json_error( array( 'message' => __( 'No image data provided.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Handle base64 encoded data
		if ( strpos( $image_data, 'data:image/' ) === 0 ) {
			// Extract mime type and base64 data
			preg_match( '/data:image\/(\w+);base64,(.+)/', $image_data, $matches );
			if ( count( $matches ) !== 3 ) {
				wp_send_json_error( array( 'message' => __( 'Invalid image data format.', 'ai-sales-manager-for-woocommerce' ) ) );
				return;
			}
			$extension   = $matches[1];
			$image_data  = $matches[2];
			
			// Update filename extension if needed
			$filename = preg_replace( '/\.[^.]+$/', '.' . $extension, $filename );
		}

		// Decode base64
		$decoded = base64_decode( $image_data );
		if ( false === $decoded ) {
			wp_send_json_error( array( 'message' => __( 'Failed to decode image data.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Verify it's a valid image
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $decoded );

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid image type.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$this->save_image_to_media_library( $decoded, $mime, $filename, $title );
	}

	/**
	 * Save image binary data to WordPress Media Library
	 *
	 * @param string $decoded Binary image data.
	 * @param string $mime MIME type.
	 * @param string $filename Desired filename.
	 * @param string $title Image title.
	 */
	private function save_image_to_media_library( $decoded, $mime, $filename, $title ) {
		// Get WordPress upload directory
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload_dir['error'] ) );
			return;
		}

		// Generate unique filename
		$unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
		$upload_path     = $upload_dir['path'] . '/' . $unique_filename;

		// Initialize WordPress Filesystem API
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Save the file using WP_Filesystem
		$saved = $wp_filesystem->put_contents( $upload_path, $decoded, FS_CHMOD_FILE );
		if ( false === $saved ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save image file.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Prepare attachment data
		$attachment = array(
			'post_mime_type' => $mime,
			'post_title'     => ! empty( $title ) ? $title : pathinfo( $unique_filename, PATHINFO_FILENAME ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert attachment
		$attachment_id = wp_insert_attachment( $attachment, $upload_path );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $upload_path );
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		wp_send_json_success( array(
			'message'       => __( 'Image saved to media library.', 'ai-sales-manager-for-woocommerce' ),
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'edit_url'      => admin_url( 'upload.php?item=' . $attachment_id ),
		) );
	}

	/**
	 * Handle set product featured image
	 * Downloads the generated image, saves to media library, and sets as product featured image
	 */
	public function handle_set_product_featured_image() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$image_url  = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( empty( $image_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No image URL provided.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Download the image
		$response = wp_remote_get( $image_url, array(
			'timeout'   => 30,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to download image: ', 'ai-sales-manager-for-woocommerce' ) . $response->get_error_message() ) );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			wp_send_json_error( array( 'message' => __( 'Failed to download image. HTTP error: ', 'ai-sales-manager-for-woocommerce' ) . $response_code ) );
			return;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Downloaded image is empty.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Verify it's a valid image
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $image_data );

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid image type.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Generate filename based on product
		$extension = str_replace( 'image/', '', $mime );
		$filename  = sanitize_title( $product->get_name() ) . '-ai-generated.' . $extension;

		// Get WordPress upload directory
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload_dir['error'] ) );
			return;
		}

		// Generate unique filename
		$unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
		$upload_path     = $upload_dir['path'] . '/' . $unique_filename;

		// Initialize WordPress Filesystem API
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Save the file
		$saved = $wp_filesystem->put_contents( $upload_path, $image_data, FS_CHMOD_FILE );
		if ( false === $saved ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save image file.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => $mime,
			'post_title'     => $product->get_name() . ' - AI Generated',
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload_path );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $upload_path );
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Set as product featured image
		$product->set_image_id( $attachment_id );
		$product->save();

		wp_send_json_success( array(
			'message'       => __( 'Featured image set successfully.', 'ai-sales-manager-for-woocommerce' ),
			'attachment_id' => $attachment_id,
			'image_url'     => wp_get_attachment_url( $attachment_id ),
		) );
	}

	/**
	 * Handle set category thumbnail
	 * Downloads the generated image, saves to media library, and sets as category thumbnail
	 */
	public function handle_set_category_thumbnail() {
		// Use chat nonce
		if ( ! check_ajax_referer( 'aisales_chat_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! current_user_can( 'manage_product_terms' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$image_url   = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$category_id = isset( $_POST['category_id'] ) ? absint( wp_unslash( $_POST['category_id'] ) ) : 0;

		if ( empty( $image_url ) ) {
			wp_send_json_error( array( 'message' => __( 'No image URL provided.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		if ( ! $category_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category ID.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Download the image
		$response = wp_remote_get( $image_url, array(
			'timeout'   => 30,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to download image: ', 'ai-sales-manager-for-woocommerce' ) . $response->get_error_message() ) );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			wp_send_json_error( array( 'message' => __( 'Failed to download image. HTTP error: ', 'ai-sales-manager-for-woocommerce' ) . $response_code ) );
			return;
		}

		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Downloaded image is empty.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Verify it's a valid image
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $image_data );

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid image type.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Generate filename based on category
		$extension = str_replace( 'image/', '', $mime );
		$filename  = sanitize_title( $term->name ) . '-ai-generated.' . $extension;

		// Get WordPress upload directory
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			wp_send_json_error( array( 'message' => $upload_dir['error'] ) );
			return;
		}

		// Generate unique filename
		$unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
		$upload_path     = $upload_dir['path'] . '/' . $unique_filename;

		// Initialize WordPress Filesystem API
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Save the file
		$saved = $wp_filesystem->put_contents( $upload_path, $image_data, FS_CHMOD_FILE );
		if ( false === $saved ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save image file.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => $mime,
			'post_title'     => $term->name . ' - AI Generated Thumbnail',
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload_path );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $upload_path );
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
			return;
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		// Set as category thumbnail using term meta
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );

		wp_send_json_success( array(
			'message'       => __( 'Category thumbnail set successfully.', 'ai-sales-manager-for-woocommerce' ),
			'attachment_id' => $attachment_id,
			'image_url'     => wp_get_attachment_url( $attachment_id ),
		) );
	}

	/**
	 * Handle get store summary
	 * Used by agent mode to get store statistics for context
	 */
	public function handle_get_store_summary() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Get product counts
		$product_counts = wp_count_posts( 'product' );
		$total_products = isset( $product_counts->publish ) ? (int) $product_counts->publish : 0;
		$draft_products = isset( $product_counts->draft ) ? (int) $product_counts->draft : 0;

		// Get category count
		$category_count = wp_count_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $category_count ) ) {
			$category_count = 0;
		}

		// Get tag count
		$tag_count = wp_count_terms( array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
		) );
		if ( is_wp_error( $tag_count ) ) {
			$tag_count = 0;
		}

		// Get order statistics (last 30 days)
		$thirty_days_ago = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$order_counts    = array(
			'total'      => 0,
			'processing' => 0,
			'completed'  => 0,
		);

		if ( function_exists( 'wc_get_orders' ) ) {
			$recent_orders = wc_get_orders( array(
				'date_created' => '>' . $thirty_days_ago,
				'limit'        => -1,
				'return'       => 'ids',
			) );
			$order_counts['total'] = count( $recent_orders );

			$processing_orders = wc_get_orders( array(
				'status'       => 'processing',
				'date_created' => '>' . $thirty_days_ago,
				'limit'        => -1,
				'return'       => 'ids',
			) );
			$order_counts['processing'] = count( $processing_orders );

			$completed_orders = wc_get_orders( array(
				'status'       => 'completed',
				'date_created' => '>' . $thirty_days_ago,
				'limit'        => -1,
				'return'       => 'ids',
			) );
			$order_counts['completed'] = count( $completed_orders );
		}

		// Get top categories by product count
		$top_categories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 5,
			'hide_empty' => false,
		) );

		$categories = array();
		if ( ! is_wp_error( $top_categories ) ) {
			foreach ( $top_categories as $cat ) {
				$categories[] = array(
					'id'            => $cat->term_id,
					'name'          => $cat->name,
					'product_count' => $cat->count,
				);
			}
		}

		// Count products with empty description using WP_Query.
		// Use caching to reduce database load.
		$cache_key        = 'aisales_empty_desc_count';
		$empty_desc_count = wp_cache_get( $cache_key );

		if ( false === $empty_desc_count ) {
			// Query for products with empty post_content.
			$empty_desc_query = new WP_Query( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				's'              => '', // Empty search to include all.
			) );

			// Count products with truly empty descriptions.
			$empty_desc_count = 0;
			if ( $empty_desc_query->have_posts() ) {
				foreach ( $empty_desc_query->posts as $product_id ) {
					$content = get_post_field( 'post_content', $product_id );
					if ( empty( trim( $content ) ) ) {
						$empty_desc_count++;
					}
				}
			}
			wp_reset_postdata();
			wp_cache_set( $cache_key, $empty_desc_count, '', 300 ); // Cache for 5 minutes.
		}

		// Get products without images with caching.
		$cache_key_images  = 'aisales_no_image_count';
		$products_no_image = wp_cache_get( $cache_key_images );

		if ( false === $products_no_image ) {
			// Query all published products and check for thumbnails.
			$all_products_query = new WP_Query( array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );

			$products_no_image = 0;
			if ( $all_products_query->have_posts() ) {
				foreach ( $all_products_query->posts as $product_id ) {
					if ( ! has_post_thumbnail( $product_id ) ) {
						$products_no_image++;
					}
				}
			}
			wp_reset_postdata();
			wp_cache_set( $cache_key_images, $products_no_image, '', 300 ); // Cache for 5 minutes.
		}

		// Get store context for additional info
		$store_context = get_option( 'aisales_store_context', array() );

		wp_send_json_success( array(
			'products'        => array(
				'total'         => $total_products,
				'draft'         => $draft_products,
				'missing_desc'  => $empty_desc_count,
				'missing_image' => $products_no_image,
			),
			'categories'      => array(
				'total' => (int) $category_count,
				'top'   => $categories,
			),
			'tags'            => array(
				'total' => (int) $tag_count,
			),
			'orders'          => $order_counts,
			'store_context'   => array(
				'name'        => isset( $store_context['store_name'] ) ? $store_context['store_name'] : get_bloginfo( 'name' ),
				'description' => isset( $store_context['store_description'] ) ? $store_context['store_description'] : '',
				'niche'       => isset( $store_context['business_niche'] ) ? $store_context['business_niche'] : '',
				'audience'    => isset( $store_context['target_audience'] ) ? $store_context['target_audience'] : '',
				'tone'        => isset( $store_context['brand_tone'] ) ? $store_context['brand_tone'] : '',
			),
			'currency'        => get_woocommerce_currency(),
			'currency_symbol' => get_woocommerce_currency_symbol(),
		) );
	}

	/**
	 * Handle fetch tool data
	 * Used by AI Agent mode to execute tool calls and fetch store data
	 *
	 * Expected POST data:
	 * - requests: array of tool requests, each containing:
	 *   - request_id: string
	 *   - tool: string (get_products|get_categories|get_products_by_category|get_page_content)
	 *   - params: object
	 */
	public function handle_fetch_tool_data() {
		check_ajax_referer( 'aisales_chat_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$requests_raw = isset( $_POST['requests'] ) ? sanitize_text_field( wp_unslash( $_POST['requests'] ) ) : '';

		// Decode JSON string from JavaScript
		$requests = json_decode( $requests_raw, true );

		if ( empty( $requests ) || ! is_array( $requests ) ) {
			wp_send_json_error( array( 'message' => __( 'No tool requests provided.', 'ai-sales-manager-for-woocommerce' ) ) );
			return;
		}

		// Load tool executor
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-tool-executor.php';
		$executor = new AISales_Tool_Executor();

		$results = array();

		foreach ( $requests as $request ) {
			$request_id = isset( $request['request_id'] ) ? sanitize_text_field( $request['request_id'] ) : '';
			$tool       = isset( $request['tool'] ) ? sanitize_key( $request['tool'] ) : '';
			$params_raw = isset( $request['params'] ) ? $request['params'] : array();
			$params     = is_array( $params_raw ) ? map_deep( $params_raw, 'sanitize_text_field' ) : array();

			if ( empty( $request_id ) || empty( $tool ) ) {
				$results[] = array(
					'request_id' => $request_id,
					'tool'       => $tool,
					'success'    => false,
					'error'      => __( 'Invalid request format.', 'ai-sales-manager-for-woocommerce' ),
				);
				continue;
			}

			// Execute the tool
			$result = $executor->execute( $tool, $params );

			$results[] = array(
				'request_id' => $request_id,
				'tool'       => $tool,
				'success'    => ! is_wp_error( $result ),
				'data'       => is_wp_error( $result ) ? null : $result,
				'error'      => is_wp_error( $result ) ? $result->get_error_message() : null,
			);
		}

		wp_send_json_success( array(
			'tool_results' => $results,
		) );
	}

	// =============================================================================
	// Email Template Handlers
	// =============================================================================

	/**
	 * Handle get email templates
	 * Returns all templates with their status overview
	 */
	public function handle_get_email_templates() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email_manager = AISales_Email_Manager::instance();
		$overview      = $email_manager->get_templates_overview();
		$placeholders  = $email_manager->get_placeholders();

		wp_send_json_success( array(
			'templates'    => $overview,
			'placeholders' => $placeholders,
		) );
	}

	/**
	 * Handle save email template
	 * Saves a template to WordPress options
	 */
	public function handle_save_email_template() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$template_type = isset( $_POST['template_type'] ) ? sanitize_key( wp_unslash( $_POST['template_type'] ) ) : '';
		$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$subject       = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$heading       = isset( $_POST['heading'] ) ? sanitize_text_field( wp_unslash( $_POST['heading'] ) ) : '';
		// Allow HTML in content but sanitize it
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$content       = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'draft';

		if ( empty( $template_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email_manager = AISales_Email_Manager::instance();
		$valid_types   = array_keys( $email_manager->get_template_types() );

		if ( ! in_array( $template_type, $valid_types, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template type.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$template_data = array(
			'name'    => $name,
			'subject' => $subject,
			'heading' => $heading,
			'content' => $content,
			'status'  => $status,
		);

		$saved = $email_manager->save_template( $template_type, $template_data );

		if ( $saved ) {
			wp_send_json_success( array(
				'message'  => __( 'Template saved successfully.', 'ai-sales-manager-for-woocommerce' ),
				'template' => $email_manager->get_template( $template_type ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save template.', 'ai-sales-manager-for-woocommerce' ) ) );
		}
	}

	/**
	 * Handle generate email template
	 * Calls the API to generate an AI-powered template
	 */
	public function handle_generate_email_template() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api = AISales_API_Client::instance();

		if ( ! $api->is_connected() ) {
			wp_send_json_error( array( 'message' => __( 'Please connect to AI Sales Manager first.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$template_type       = isset( $_POST['template_type'] ) ? sanitize_key( wp_unslash( $_POST['template_type'] ) ) : '';
		$custom_prompt       = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['custom_prompt'] ) ) : '';
		$regenerate_part     = isset( $_POST['regenerate_part'] ) ? sanitize_key( wp_unslash( $_POST['regenerate_part'] ) ) : '';

		if ( empty( $template_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Build store context from saved settings, only including non-empty values
		$store_context_option = get_option( 'aisales_store_context', array() );
		$store_context        = array();

		// Always include store name and description with fallbacks
		$store_context['store_name'] = ! empty( $store_context_option['store_name'] )
			? $store_context_option['store_name']
			: get_bloginfo( 'name' );
		$store_context['store_description'] = ! empty( $store_context_option['store_description'] )
			? $store_context_option['store_description']
			: get_bloginfo( 'description' );

		// Only include optional fields if they have values
		if ( ! empty( $store_context_option['business_niche'] ) ) {
			$store_context['business_niche'] = $store_context_option['business_niche'];
		}
		if ( ! empty( $store_context_option['target_audience'] ) ) {
			$store_context['target_audience'] = $store_context_option['target_audience'];
		}
		if ( ! empty( $store_context_option['brand_tone'] ) ) {
			$store_context['brand_tone'] = $store_context_option['brand_tone'];
		}
		if ( ! empty( $store_context_option['language'] ) ) {
			$store_context['language'] = $store_context_option['language'];
		}
		if ( ! empty( $store_context_option['custom_instructions'] ) ) {
			$store_context['custom_instructions'] = $store_context_option['custom_instructions'];
		}

		// Build request body
		$request_body = array(
			'template_type' => $template_type,
			'store_context' => $store_context,
		);

		if ( ! empty( $custom_prompt ) ) {
			$request_body['custom_prompt'] = $custom_prompt;
		}

		if ( ! empty( $regenerate_part ) ) {
			$request_body['regenerate_part'] = $regenerate_part;
		}

		// Call the API
		$result = $api->generate_email_template( $request_body );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! isset( $result['template'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API response.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Update local balance if returned
		if ( isset( $result['template']['tokens_used'] ) ) {
			$this->update_local_balance_from_tokens( $result['template']['tokens_used'] );
		}

		// Get current balance to return to frontend
		$current_balance = get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'template'   => $result['template'],
			'validation' => isset( $result['validation'] ) ? $result['validation'] : null,
			'balance'    => intval( $current_balance ),
		) );
	}

	/**
	 * Handle preview email template
	 * Renders the template with sample data
	 */
	public function handle_preview_email_template() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$heading = isset( $_POST['heading'] ) ? sanitize_text_field( wp_unslash( $_POST['heading'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		$email_manager = AISales_Email_Manager::instance();

		$preview = $email_manager->preview_template( array(
			'subject' => $subject,
			'heading' => $heading,
			'content' => $content,
		) );

		// Generate full HTML preview
		$html = $this->generate_email_preview_html( $preview );

		wp_send_json_success( array(
			'preview' => array_merge( $preview, array( 'html' => $html ) ),
		) );
	}

	/**
	 * Handle send test email
	 */
	public function handle_send_test_email() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$recipient     = isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';
		$subject_input = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$heading_input = isset( $_POST['heading'] ) ? sanitize_text_field( wp_unslash( $_POST['heading'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$content_input = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email_manager = AISales_Email_Manager::instance();
		$preview       = $email_manager->preview_template( array(
			'subject' => $subject_input,
			'heading' => $heading_input,
			'content' => $content_input,
		) );

		if ( empty( $preview['subject'] ) ) {
			$preview['subject'] = __( 'Test Email Preview', 'ai-sales-manager-for-woocommerce' );
		}

		$html    = $this->generate_email_preview_html( $preview );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $recipient, $preview['subject'], $html, $headers );

		if ( $sent ) {
			wp_send_json_success( array(
				'message' => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ),
			) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ) ) );
	}

	/**
	 * Handle save mail provider settings
	 */
	public function handle_save_mail_provider_settings() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$settings_raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();
		if ( is_string( $settings_raw ) ) {
			$decoded = json_decode( $settings_raw, true );
			$settings = is_array( $decoded ) ? $decoded : array();
		} else {
			$settings = is_array( $settings_raw ) ? $settings_raw : array();
		}

		$mail_provider = AISales_Mail_Provider::instance();
		$success       = $mail_provider->save_settings( $settings );

		if ( ! $success ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save settings.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		wp_send_json_success( array(
			'message' => __( 'Email delivery settings saved.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle send mail provider test
	 */
	public function handle_send_mail_provider_test() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$recipient = isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';
		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$subject = __( 'Email Delivery Test', 'ai-sales-manager-for-woocommerce' );
		$body    = __( 'This is a test email sent from AI Sales Manager to verify your email delivery settings.', 'ai-sales-manager-for-woocommerce' );
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $recipient, $subject, $body, $headers );
		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		wp_send_json_error( array( 'message' => __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ) ) );
	}

	/**
	 * Generate HTML preview for email template
	 *
	 * @param array $preview Preview data with subject, heading, content.
	 * @return string Full HTML preview.
	 */
	private function generate_email_preview_html( $preview ) {
		// Get WooCommerce email settings for styling
		$base_color  = get_option( 'woocommerce_email_base_color', '#7f54b3' );
		$text_color  = get_option( 'woocommerce_email_text_color', '#3c3c3c' );
		$body_bg     = get_option( 'woocommerce_email_background_color', '#f7f7f7' );
		$footer_text = get_option( 'woocommerce_email_footer_text', '' );
		$store_name  = get_bloginfo( 'name' );

		// Get header image if set
		$header_image = get_option( 'woocommerce_email_header_image', '' );

		if ( empty( $footer_text ) ) {
			/* translators: %s: site name */
			$footer_text = sprintf( __( '%s - Powered by WooCommerce', 'ai-sales-manager-for-woocommerce' ), $store_name );
		}

		// Calculate contrasting colors
		$header_text_color = $this->get_contrasting_color( $base_color );
		$link_color        = $this->adjust_color_brightness( $base_color, -20 );

		// Convert content line breaks to HTML
		$content_html = wpautop( $preview['content'] );

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="x-apple-disable-message-reformatting">
	<meta name="color-scheme" content="light">
	<meta name="supported-color-schemes" content="light">
	<title><?php echo esc_html( $preview['subject'] ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:AllowPNG/>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style>
		/* Reset & Base */
		* { box-sizing: border-box; }
		body, html {
			margin: 0;
			padding: 0;
			width: 100%;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			font-size: 16px;
			line-height: 1.625;
			color: <?php echo esc_attr( $text_color ); ?>;
			background-color: <?php echo esc_attr( $body_bg ); ?>;
		}
		
		/* Typography */
		h1, h2, h3, h4, h5, h6 {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			font-weight: 600;
			line-height: 1.3;
			margin: 0 0 16px;
			color: <?php echo esc_attr( $text_color ); ?>;
		}
		h1 { font-size: 28px; }
		h2 { font-size: 24px; }
		h3 { font-size: 20px; }
		
		p {
			margin: 0 0 16px;
			font-size: 16px;
			line-height: 1.625;
		}
		
		a {
			color: <?php echo esc_attr( $link_color ); ?>;
			text-decoration: underline;
		}
		a:hover {
			color: <?php echo esc_attr( $base_color ); ?>;
		}
		
		/* Email Container */
		.email-wrapper {
			width: 100%;
			padding: 32px 16px;
			background-color: <?php echo esc_attr( $body_bg ); ?>;
		}
		
		.email-container {
			max-width: 600px;
			margin: 0 auto;
			background-color: #ffffff;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 10px 20px rgba(0, 0, 0, 0.03);
		}
		
		/* Header */
		.email-header {
			background: <?php echo esc_attr( $base_color ); ?>;
			background: linear-gradient(135deg, <?php echo esc_attr( $base_color ); ?> 0%, <?php echo esc_attr( $this->adjust_color_brightness( $base_color, -15 ) ); ?> 100%);
			padding: 40px 48px;
			text-align: center;
		}
		
		.email-header-logo {
			margin-bottom: 20px;
		}
		
		.email-header-logo img {
			max-width: 180px;
			max-height: 60px;
			height: auto;
		}
		
		.email-header h1 {
			margin: 0;
			font-size: 26px;
			font-weight: 600;
			color: <?php echo esc_attr( $header_text_color ); ?>;
			letter-spacing: -0.02em;
		}
		
		/* Body */
		.email-body {
			padding: 48px;
		}
		
		.email-body p:last-child {
			margin-bottom: 0;
		}
		
		/* Order Details Table */
		.order-table {
			width: 100%;
			border-collapse: collapse;
			margin: 24px 0;
			font-size: 14px;
		}
		
		.order-table th,
		.order-table td {
			padding: 14px 16px;
			text-align: left;
			border-bottom: 1px solid #edf2f7;
		}
		
		.order-table th {
			background-color: #f8fafc;
			font-weight: 600;
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}
		
		.order-table tr:last-child td {
			border-bottom: none;
		}
		
		.order-table .total-row td {
			font-weight: 600;
			font-size: 16px;
			background-color: #f8fafc;
			border-top: 2px solid #e2e8f0;
		}
		
		/* Buttons */
		.button {
			display: inline-block;
			padding: 14px 32px;
			background-color: <?php echo esc_attr( $base_color ); ?>;
			color: <?php echo esc_attr( $header_text_color ); ?> !important;
			text-decoration: none;
			font-weight: 600;
			font-size: 15px;
			border-radius: 8px;
			transition: background-color 0.2s ease;
		}
		
		.button:hover {
			background-color: <?php echo esc_attr( $this->adjust_color_brightness( $base_color, -10 ) ); ?>;
			color: <?php echo esc_attr( $header_text_color ); ?> !important;
		}
		
		/* Info Box */
		.info-box {
			background-color: #f8fafc;
			border-radius: 8px;
			padding: 20px 24px;
			margin: 24px 0;
			border-left: 4px solid <?php echo esc_attr( $base_color ); ?>;
		}
		
		.info-box p {
			margin: 0;
			font-size: 14px;
		}
		
		/* Address Block */
		.address-block {
			background-color: #f8fafc;
			border-radius: 8px;
			padding: 20px 24px;
			margin: 16px 0;
		}
		
		.address-block h4 {
			margin: 0 0 8px;
			font-size: 13px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}
		
		.address-block p {
			margin: 0;
			font-size: 14px;
			line-height: 1.6;
		}
		
		/* Footer */
		.email-footer {
			background-color: #f8fafc;
			padding: 32px 48px;
			text-align: center;
			border-top: 1px solid #e2e8f0;
		}
		
		.email-footer p {
			margin: 0 0 8px;
			font-size: 13px;
			color: #64748b;
			line-height: 1.6;
		}
		
		.email-footer p:last-child {
			margin-bottom: 0;
		}
		
		.email-footer a {
			color: <?php echo esc_attr( $base_color ); ?>;
		}
		
		.social-links {
			margin: 16px 0;
		}
		
		.social-links a {
			display: inline-block;
			margin: 0 8px;
			color: #94a3b8;
			text-decoration: none;
		}
		
		/* Divider */
		.divider {
			height: 1px;
			background-color: #e2e8f0;
			margin: 32px 0;
		}
		
		/* Responsive */
		@media only screen and (max-width: 600px) {
			.email-wrapper {
				padding: 16px 8px;
			}
			.email-header {
				padding: 32px 24px;
			}
			.email-header h1 {
				font-size: 22px;
			}
			.email-body {
				padding: 32px 24px;
			}
			.email-footer {
				padding: 24px;
			}
			.order-table th,
			.order-table td {
				padding: 10px 12px;
				font-size: 13px;
			}
		}
	</style>
</head>
<body>
	<div class="email-wrapper">
		<div class="email-container">
			<!-- Header -->
			<div class="email-header">
				<?php if ( ! empty( $header_image ) ) : ?>
				<div class="email-header-logo">
					<img src="<?php echo esc_url( $header_image ); ?>" alt="<?php echo esc_attr( $store_name ); ?>">
				</div>
				<?php endif; ?>
				<h1><?php echo esc_html( $preview['heading'] ); ?></h1>
			</div>
			
			<!-- Body -->
			<div class="email-body">
				<?php echo wp_kses_post( $content_html ); ?>
			</div>
			
			<!-- Footer -->
			<div class="email-footer">
				<p><?php echo wp_kses_post( $footer_text ); ?></p>
			</div>
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get contrasting text color (black or white) for a background color
	 *
	 * @param string $hex_color Hex color code.
	 * @return string '#ffffff' or '#000000'
	 */
	private function get_contrasting_color( $hex_color ) {
		$hex_color = ltrim( $hex_color, '#' );
		
		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );
		
		// Calculate relative luminance
		$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
		
		return $luminance > 0.5 ? '#1a1a1a' : '#ffffff';
	}

	/**
	 * Adjust color brightness
	 *
	 * @param string $hex_color Hex color code.
	 * @param int    $percent   Percent to adjust (-100 to 100).
	 * @return string Adjusted hex color.
	 */
	private function adjust_color_brightness( $hex_color, $percent ) {
		$hex_color = ltrim( $hex_color, '#' );
		
		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );
		
		$r = max( 0, min( 255, $r + ( $r * $percent / 100 ) ) );
		$g = max( 0, min( 255, $g + ( $g * $percent / 100 ) ) );
		$b = max( 0, min( 255, $b + ( $b * $percent / 100 ) ) );
		
		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Handle toggle email template (enable/disable)
	 */
	public function handle_toggle_email_template() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$template_type = isset( $_POST['template_type'] ) ? sanitize_key( wp_unslash( $_POST['template_type'] ) ) : '';
		$enabled       = isset( $_POST['enabled'] ) ? filter_var( wp_unslash( $_POST['enabled'] ), FILTER_VALIDATE_BOOLEAN ) : false;

		if ( empty( $template_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email_manager = AISales_Email_Manager::instance();

		if ( $enabled ) {
			$success = $email_manager->enable_template( $template_type );
			$message = __( 'Template enabled successfully.', 'ai-sales-manager-for-woocommerce' );
		} else {
			$success = $email_manager->disable_template( $template_type );
			$message = __( 'Template disabled successfully.', 'ai-sales-manager-for-woocommerce' );
		}

		if ( $success ) {
			wp_send_json_success( array(
				'message'  => $message,
				'template' => $email_manager->get_template( $template_type ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update template status.', 'ai-sales-manager-for-woocommerce' ) ) );
		}
	}

	/**
	 * Handle delete email template
	 */
	public function handle_delete_email_template() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$template_type = isset( $_POST['template_type'] ) ? sanitize_key( wp_unslash( $_POST['template_type'] ) ) : '';

		if ( empty( $template_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$email_manager = AISales_Email_Manager::instance();
		$success       = $email_manager->delete_template( $template_type );

		if ( $success ) {
			wp_send_json_success( array(
				'message' => __( 'Template deleted successfully.', 'ai-sales-manager-for-woocommerce' ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete template.', 'ai-sales-manager-for-woocommerce' ) ) );
		}
	}

	/**
	 * Update local balance from token usage
	 *
	 * @param array $tokens_used Token usage data with input, output, total.
	 */
	private function update_local_balance_from_tokens( $tokens_used ) {
		$current_balance = get_option( 'aisales_balance', 0 );
		$used            = isset( $tokens_used['total'] ) ? absint( $tokens_used['total'] ) : 0;
		
		if ( $used > 0 && $current_balance > 0 ) {
			$new_balance = max( 0, $current_balance - $used );
			update_option( 'aisales_balance', $new_balance );
		}
	}

	/**
	 * Handle applying batch enhancement results to a product
	 */
	public function handle_apply_batch_result() {
		check_ajax_referer( 'aisales_batch_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$suggestions = isset( $_POST['suggestions'] ) ? wp_unslash( $_POST['suggestions'] ) : '';

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Product ID is required.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Parse suggestions JSON
		$suggestions = json_decode( $suggestions, true );
		if ( ! is_array( $suggestions ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid suggestions data.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$changes_made = array();

		// Apply description
		if ( isset( $suggestions['description']['suggested'] ) && ! empty( $suggestions['description']['suggested'] ) ) {
			$product->set_description( wp_kses_post( $suggestions['description']['suggested'] ) );
			$changes_made[] = 'description';
		}

		// Apply short description
		if ( isset( $suggestions['short_description']['suggested'] ) && ! empty( $suggestions['short_description']['suggested'] ) ) {
			$product->set_short_description( wp_kses_post( $suggestions['short_description']['suggested'] ) );
			$changes_made[] = 'short_description';
		}

		// Apply SEO title (requires Yoast SEO or similar)
		if ( isset( $suggestions['seo_title']['suggested'] ) && ! empty( $suggestions['seo_title']['suggested'] ) ) {
			$seo_title = sanitize_text_field( $suggestions['seo_title']['suggested'] );
			
			// Try Yoast SEO first
			if ( defined( 'WPSEO_VERSION' ) ) {
				update_post_meta( $product_id, '_yoast_wpseo_title', $seo_title );
				$changes_made[] = 'seo_title';
			}
			// Try Rank Math
			elseif ( class_exists( 'RankMath' ) ) {
				update_post_meta( $product_id, 'rank_math_title', $seo_title );
				$changes_made[] = 'seo_title';
			}
			// Try All in One SEO
			elseif ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
				update_post_meta( $product_id, '_aioseo_title', $seo_title );
				$changes_made[] = 'seo_title';
			}
		}

		// Apply SEO description (requires Yoast SEO or similar)
		if ( isset( $suggestions['seo_description']['suggested'] ) && ! empty( $suggestions['seo_description']['suggested'] ) ) {
			$seo_desc = sanitize_text_field( $suggestions['seo_description']['suggested'] );
			
			// Try Yoast SEO first
			if ( defined( 'WPSEO_VERSION' ) ) {
				update_post_meta( $product_id, '_yoast_wpseo_metadesc', $seo_desc );
				$changes_made[] = 'seo_description';
			}
			// Try Rank Math
			elseif ( class_exists( 'RankMath' ) ) {
				update_post_meta( $product_id, 'rank_math_description', $seo_desc );
				$changes_made[] = 'seo_description';
			}
			// Try All in One SEO
			elseif ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
				update_post_meta( $product_id, '_aioseo_description', $seo_desc );
				$changes_made[] = 'seo_description';
			}
		}

		// Apply tags
		if ( isset( $suggestions['tags']['suggested'] ) && is_array( $suggestions['tags']['suggested'] ) ) {
			$tag_ids = array();
			foreach ( $suggestions['tags']['suggested'] as $tag_name ) {
				$tag_name = sanitize_text_field( $tag_name );
				if ( empty( $tag_name ) ) {
					continue;
				}

				// Check if tag exists, create if not
				$term = get_term_by( 'name', $tag_name, 'product_tag' );
				if ( ! $term ) {
					$result = wp_insert_term( $tag_name, 'product_tag' );
					if ( ! is_wp_error( $result ) ) {
						$tag_ids[] = $result['term_id'];
					}
				} else {
					$tag_ids[] = $term->term_id;
				}
			}

			if ( ! empty( $tag_ids ) ) {
				wp_set_object_terms( $product_id, $tag_ids, 'product_tag' );
				$changes_made[] = 'tags';
			}
		}

		// Apply image alt text
		if ( isset( $suggestions['image_alt']['suggested'] ) && ! empty( $suggestions['image_alt']['suggested'] ) ) {
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				update_post_meta( $image_id, '_wp_attachment_image_alt', sanitize_text_field( $suggestions['image_alt']['suggested'] ) );
				$changes_made[] = 'image_alt';
			}
		}

		// Save the product
		$product->save();

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %s: list of changes applied */
				__( 'Applied changes: %s', 'ai-sales-manager-for-woocommerce' ),
				implode( ', ', $changes_made )
			),
			'changes' => $changes_made,
		) );
	}

	// =============================================================================
	// API Key Recovery Handlers
	// =============================================================================

	/**
	 * Get current site domain (normalized - strips www. prefix)
	 *
	 * @return string
	 */
	private function get_site_domain() {
		$site_url = get_site_url();
		$parsed   = wp_parse_url( $site_url );
		$host     = isset( $parsed['host'] ) ? $parsed['host'] : '';
		
		// Normalize: strip www. prefix and lowercase
		return preg_replace( '/^www\./i', '', strtolower( trim( $host ) ) );
	}

	/**
	 * Handle API key recovery request
	 * Calls API to create recovery token, then sends email via wp_mail()
	 * 
	 * The recovery uses both email AND domain to verify the account,
	 * ensuring the user is recovering the correct account for their site.
	 */
	public function handle_request_api_key_recovery() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$domain = $this->get_site_domain();

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		if ( empty( $domain ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not determine site domain.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->create_recovery_token( $email, $domain );

		if ( is_wp_error( $result ) ) {
			// Don't reveal if email/domain exists or not for security
			// Just show a generic success message
			wp_send_json_success( array(
				'message' => __( 'If an account exists with this email for this site, you will receive a recovery link shortly.', 'ai-sales-manager-for-woocommerce' ),
			) );
			return;
		}

		// Check if account was found (has_account flag from API)
		if ( ! isset( $result['has_account'] ) || ! $result['has_account'] ) {
			// No account found, but don't reveal this - show generic message
			wp_send_json_success( array(
				'message' => __( 'If an account exists with this email for this site, you will receive a recovery link shortly.', 'ai-sales-manager-for-woocommerce' ),
			) );
			return;
		}

		// Build the recovery URL
		$recovery_url = add_query_arg( array(
			'page'           => 'ai-sales-manager',
			'recovery_token' => $result['token'],
		), admin_url( 'admin.php' ) );

		// Send the email via wp_mail()
		$store_name = get_bloginfo( 'name' );
		$subject    = sprintf(
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

		$sent = wp_mail( $email, $subject, $message, $headers );

		if ( $sent ) {
			wp_send_json_success( array(
				'message' => __( 'If an account exists with this email for this site, you will receive a recovery link shortly.', 'ai-sales-manager-for-woocommerce' ),
			) );
		} else {
			// Log the error but don't expose it to the user
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AISales: Failed to send recovery email to ' . $email );
			}
			wp_send_json_error( array(
				'message' => __( 'Failed to send recovery email. Please try again later or contact support.', 'ai-sales-manager-for-woocommerce' ),
			) );
		}
	}

	/**
	 * Handle recovery token validation
	 * Validates token with API and saves new API key
	 */
	public function handle_validate_recovery_token() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( empty( $token ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid recovery token.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api    = AISales_API_Client::instance();
		$result = $api->validate_recovery_token( $token );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Save the new API key
		if ( isset( $result['api_key'] ) ) {
			update_option( 'aisales_api_key', $result['api_key'] );

			// Update email if provided
			if ( isset( $result['email'] ) ) {
				update_option( 'aisales_user_email', $result['email'] );
			}

			wp_send_json_success( array(
				'message'  => __( 'API key recovered successfully! You are now connected.', 'ai-sales-manager-for-woocommerce' ),
				'redirect' => admin_url( 'admin.php?page=ai-sales-manager' ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid response from server.', 'ai-sales-manager-for-woocommerce' ) ) );
		}
	}

	// =============================================================================
	// Email Wizard Handlers
	// =============================================================================

	/**
	 * Handle saving wizard context (brand settings)
	 * Saves store context from the email template wizard
	 */
	public function handle_save_wizard_context() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$context_raw = isset( $_POST['context'] ) ? map_deep( wp_unslash( $_POST['context'] ), 'sanitize_textarea_field' ) : array();
		$context     = is_array( $context_raw ) ? $context_raw : array();

		// Get existing store context to preserve other fields
		$existing_context = get_option( 'aisales_store_context', array() );

		// Update with wizard values
		$store_context = array_merge( $existing_context, array(
			'store_name'      => isset( $context['store_name'] ) ? sanitize_text_field( $context['store_name'] ) : ( $existing_context['store_name'] ?? '' ),
			'business_niche'  => isset( $context['business_niche'] ) ? sanitize_key( $context['business_niche'] ) : ( $existing_context['business_niche'] ?? '' ),
			'brand_tone'      => isset( $context['brand_tone'] ) ? sanitize_key( $context['brand_tone'] ) : ( $existing_context['brand_tone'] ?? 'friendly' ),
			'target_audience' => isset( $context['target_audience'] ) ? sanitize_text_field( $context['target_audience'] ) : ( $existing_context['target_audience'] ?? '' ),
			// Branding fields
			'primary_color'   => isset( $context['primary_color'] ) ? sanitize_hex_color( $context['primary_color'] ) : ( $existing_context['primary_color'] ?? '#7f54b3' ),
			'text_color'      => isset( $context['text_color'] ) ? sanitize_hex_color( $context['text_color'] ) : ( $existing_context['text_color'] ?? '#3c3c3c' ),
			'bg_color'        => isset( $context['bg_color'] ) ? sanitize_hex_color( $context['bg_color'] ) : ( $existing_context['bg_color'] ?? '#f7f7f7' ),
			'font_family'     => isset( $context['font_family'] ) ? sanitize_key( $context['font_family'] ) : ( $existing_context['font_family'] ?? 'system' ),
			'updated_at'      => current_time( 'mysql' ),
		) );

		update_option( 'aisales_store_context', $store_context );

		wp_send_json_success( array(
			'message' => __( 'Brand context saved.', 'ai-sales-manager-for-woocommerce' ),
			'context' => $store_context,
		) );
	}

	/**
	 * Handle marking the email wizard as completed
	 * After completion, users go directly to generate without seeing wizard again
	 */
	public function handle_complete_email_wizard() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		update_option( 'aisales_email_wizard_completed', true );

		wp_send_json_success( array(
			'message' => __( 'Wizard completed.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	// =============================================================================
	// Brand Settings Page Handlers
	// =============================================================================

	/**
	 * Handle save brand settings
	 * Saves brand identity settings from the dedicated Brand Settings page
	 */
	public function handle_save_brand_settings() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Get existing context to preserve other fields
		$existing = get_option( 'aisales_store_context', array() );

		// Build new context with brand settings
		$store_context = array_merge( $existing, array(
			// Store Identity
			'store_name'       => isset( $_POST['store_name'] ) ? sanitize_text_field( wp_unslash( $_POST['store_name'] ) ) : '',
			'tagline'          => isset( $_POST['tagline'] ) ? sanitize_text_field( wp_unslash( $_POST['tagline'] ) ) : '',
			'business_niche'   => isset( $_POST['business_niche'] ) ? sanitize_key( wp_unslash( $_POST['business_niche'] ) ) : '',

			// Audience & Positioning
			'target_audience'  => isset( $_POST['target_audience'] ) ? sanitize_textarea_field( wp_unslash( $_POST['target_audience'] ) ) : '',
			'price_position'   => isset( $_POST['price_position'] ) ? sanitize_key( wp_unslash( $_POST['price_position'] ) ) : '',
			'differentiator'   => isset( $_POST['differentiator'] ) ? sanitize_textarea_field( wp_unslash( $_POST['differentiator'] ) ) : '',
			'pain_points'      => isset( $_POST['pain_points'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pain_points'] ) ) : '',

			// Brand Voice
			'brand_tone'       => isset( $_POST['brand_tone'] ) ? sanitize_key( wp_unslash( $_POST['brand_tone'] ) ) : 'friendly',
			'words_to_avoid'   => isset( $_POST['words_to_avoid'] ) ? sanitize_text_field( wp_unslash( $_POST['words_to_avoid'] ) ) : '',
			'promotion_style'  => isset( $_POST['promotion_style'] ) ? sanitize_key( wp_unslash( $_POST['promotion_style'] ) ) : 'moderate',

			// Visual Style
			'primary_color'    => isset( $_POST['primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary_color'] ) ) : '#7f54b3',
			'text_color'       => isset( $_POST['text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_color'] ) ) : '#3c3c3c',
			'bg_color'         => isset( $_POST['bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bg_color'] ) ) : '#f7f7f7',
			'font_family'      => isset( $_POST['font_family'] ) ? sanitize_key( wp_unslash( $_POST['font_family'] ) ) : 'system',

			'updated_at'       => current_time( 'mysql' ),
		) );

		update_option( 'aisales_store_context', $store_context );
		update_option( 'aisales_brand_setup_complete', true );

		wp_send_json_success( array(
			'message' => __( 'Brand settings saved successfully.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle AI brand analysis
	 * Calls the API to analyze the store and suggest brand settings
	 */
	public function handle_analyze_brand() {
		check_ajax_referer( 'aisales_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$api = AISales_API_Client::instance();

		if ( ! $api->is_connected() ) {
			wp_send_json_error( array( 'message' => __( 'Please connect to AI Sales Manager first.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		// Get analysis context from brand page
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-brand-page.php';
		$brand_page = AISales_Brand_Page::instance();
		$context    = $brand_page->get_analysis_context();

		// Call API for AI analysis
		$result = $api->analyze_brand( $context );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Update local balance if returned
		if ( isset( $result['tokens_used']['total'] ) ) {
			$this->update_local_balance_from_tokens( $result['tokens_used'] );
		}

		// Get current balance to return to frontend
		$current_balance = get_option( 'aisales_balance', 0 );

		wp_send_json_success( array(
			'suggestions' => isset( $result['suggestions'] ) ? $result['suggestions'] : array(),
			'tokens_used' => isset( $result['tokens_used'] ) ? $result['tokens_used'] : array(),
			'balance'     => intval( $current_balance ),
		) );
	}
}
