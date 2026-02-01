<?php
/**
 * Store Context AJAX Handlers
 *
 * Handles all store context and chat-related AJAX actions including
 * context saving, syncing, balance management, and image operations.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Store Context AJAX Handlers class
 */
class AISales_Ajax_Store extends AISales_Ajax_Base {

	/**
	 * Chat nonce action
	 *
	 * @var string
	 */
	protected $chat_nonce_action = 'aisales_chat_nonce';

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		// Store context
		$this->add_action( 'save_store_context', 'handle_save_store_context' );
		$this->add_action( 'sync_store_context', 'handle_sync_store_context' );
		$this->add_action( 'mark_chat_visited', 'handle_mark_chat_visited' );

		// Balance
		$this->add_action( 'sync_balance', 'handle_sync_balance' );

		// Image operations
		$this->add_action( 'save_generated_image', 'handle_save_generated_image' );

		// Store summary
		$this->add_action( 'get_store_summary', 'handle_get_store_summary' );

		// Tool data fetching
		$this->add_action( 'fetch_tool_data', 'handle_fetch_tool_data' );
	}

	/**
	 * Verify chat request
	 *
	 * @param string $capability Required capability.
	 */
	protected function verify_chat_request( $capability = 'manage_woocommerce' ) {
		$this->nonce_action = $this->chat_nonce_action;
		check_ajax_referer( $this->nonce_action, $this->nonce_field );

		if ( ! current_user_can( $capability ) ) {
			$this->error( __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Handle save store context
	 */
	public function handle_save_store_context() {
		$this->verify_chat_request();

		$context_raw = $this->get_post( 'context', 'raw' );
		$context     = is_array( $context_raw ) ? map_deep( $context_raw, 'sanitize_textarea_field' ) : array();

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
			$store_context['last_sync']      = $existing['last_sync'];
			$store_context['category_count'] = $existing['category_count'];
			$store_context['product_count']  = $existing['product_count'];
		}

		update_option( 'aisales_store_context', $store_context );

		$this->success( array(
			'message' => __( 'Store context saved successfully.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle sync store context
	 */
	public function handle_sync_store_context() {
		$this->verify_chat_request();

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
		$store_context                   = get_option( 'aisales_store_context', array() );
		$store_context['last_sync']      = current_time( 'mysql' );
		$store_context['category_count'] = $category_count;
		$store_context['product_count']  = $product_total;

		update_option( 'aisales_store_context', $store_context );

		$this->success( array(
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
		check_ajax_referer( $this->chat_nonce_action, $this->nonce_field );

		update_user_meta( get_current_user_id(), 'aisales_chat_visited', true );

		$this->success();
	}

	/**
	 * Handle sync balance
	 */
	public function handle_sync_balance() {
		$this->verify_chat_request();

		$balance = $this->get_post( 'balance', 'int' );

		if ( null === $balance ) {
			$this->error( __( 'Invalid balance value.', 'ai-sales-manager-for-woocommerce' ) );
		}

		update_option( 'aisales_balance', $balance );

		$this->success( array( 'balance' => $balance ) );
	}

	/**
	 * Handle save generated image
	 */
	public function handle_save_generated_image() {
		check_ajax_referer( $this->chat_nonce_action, $this->nonce_field );

		if ( ! current_user_can( 'upload_files' ) ) {
			$this->error( __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$image_data = $this->get_post( 'image_data', 'text' );
		$image_url  = $this->get_post( 'image_url', 'url' );
		$filename   = $this->get_post( 'filename', 'text', 'ai-generated-image.png' );
		$title      = $this->get_post( 'title', 'text' );

		// Sanitize filename
		$filename = sanitize_file_name( $filename );

		// Handle image URL (data URI or remote URL)
		if ( empty( $image_data ) && ! empty( $image_url ) ) {
			if ( strpos( $image_url, 'data:image/' ) === 0 ) {
				$image_data = $image_url;
			} else {
				// Fetch from remote URL
				$this->save_remote_image( $image_url, $filename, $title );
				return;
			}
		}

		if ( empty( $image_data ) ) {
			$this->error( __( 'No image data provided.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Handle base64 encoded data
		$this->save_base64_image( $image_data, $filename, $title );
	}

	/**
	 * Handle get store summary
	 */
	public function handle_get_store_summary() {
		$this->verify_chat_request();

		// Get product stats
		$product_count = wp_count_posts( 'product' );
		$products      = isset( $product_count->publish ) ? $product_count->publish : 0;

		// Get category stats
		$categories = wp_count_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );

		if ( is_wp_error( $categories ) ) {
			$categories = 0;
		}

		// Get order stats
		$order_count = wc_orders_count( 'completed' ) + wc_orders_count( 'processing' );

		// Get store context
		$store_context = get_option( 'aisales_store_context', array() );

		$this->success( array(
			'summary' => array(
				'products'      => $products,
				'categories'    => $categories,
				'orders'        => $order_count,
				'store_name'    => isset( $store_context['store_name'] ) ? $store_context['store_name'] : get_bloginfo( 'name' ),
				'currency'      => get_woocommerce_currency(),
				'currency_symbol' => get_woocommerce_currency_symbol(),
			),
		) );
	}

	/**
	 * Handle fetch tool data
	 */
	public function handle_fetch_tool_data() {
		$this->verify_chat_request();

		$tool_name = $this->require_post( 'tool_name', 'key', __( 'Tool name is required.', 'ai-sales-manager-for-woocommerce' ) );
		$params    = $this->get_json_post( 'params', true, array() );

		// Sanitize params
		$params = map_deep( $params, 'sanitize_text_field' );

		// Load and execute tool
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-tool-executor.php';
		$executor = new AISales_Tool_Executor();

		$result = $executor->execute( $tool_name, $params );

		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		}

		$this->success( $result );
	}

	/**
	 * Save remote image to media library
	 *
	 * @param string $url      Image URL.
	 * @param string $filename Filename.
	 * @param string $title    Image title.
	 */
	private function save_remote_image( $url, $filename, $title ) {
		$response = wp_remote_get( $url, array(
			'timeout'   => 30,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			$this->error( __( 'Failed to download image: ', 'ai-sales-manager-for-woocommerce' ) . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			$this->error( __( 'Failed to download image. HTTP error: ', 'ai-sales-manager-for-woocommerce' ) . $response_code );
		}

		$decoded = wp_remote_retrieve_body( $response );
		if ( empty( $decoded ) ) {
			$this->error( __( 'Downloaded image is empty.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Verify it's a valid image
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $decoded );

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			$this->error( __( 'Invalid image type from URL.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$this->save_image_to_media_library( $decoded, $mime, $filename, $title );
	}

	/**
	 * Save base64 image to media library
	 *
	 * @param string $image_data Base64 image data.
	 * @param string $filename   Filename.
	 * @param string $title      Image title.
	 */
	private function save_base64_image( $image_data, $filename, $title ) {
		// Handle data URI format
		if ( strpos( $image_data, 'data:image/' ) === 0 ) {
			preg_match( '/data:image\/(\w+);base64,(.+)/', $image_data, $matches );
			if ( count( $matches ) !== 3 ) {
				$this->error( __( 'Invalid image data format.', 'ai-sales-manager-for-woocommerce' ) );
			}
			$extension  = $matches[1];
			$image_data = $matches[2];
			$filename   = preg_replace( '/\.[^.]+$/', '.' . $extension, $filename );
		}

		// Decode base64
		$decoded = base64_decode( $image_data );
		if ( false === $decoded ) {
			$this->error( __( 'Failed to decode image data.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Determine mime type
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$mime  = $finfo->buffer( $decoded );

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			$this->error( __( 'Invalid image type.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$this->save_image_to_media_library( $decoded, $mime, $filename, $title );
	}

	/**
	 * Save image data to media library
	 *
	 * @param string $image_data Binary image data.
	 * @param string $mime       MIME type.
	 * @param string $filename   Filename.
	 * @param string $title      Image title.
	 */
	private function save_image_to_media_library( $image_data, $mime, $filename, $title ) {
		$upload_dir = wp_upload_dir();

		// Create unique filename
		$ext       = str_replace( 'image/', '', $mime );
		$ext       = ( 'jpeg' === $ext ) ? 'jpg' : $ext;
		$filename  = preg_replace( '/\.[^.]+$/', '', $filename ) . '.' . $ext;
		$filename  = wp_unique_filename( $upload_dir['path'], $filename );
		$file_path = $upload_dir['path'] . '/' . $filename;

		// Write file
		$result = file_put_contents( $file_path, $image_data );
		if ( false === $result ) {
			$this->error( __( 'Failed to save image file.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => $mime,
			'post_title'     => ! empty( $title ) ? $title : sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attachment_id = wp_insert_attachment( $attachment, $file_path );

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $file_path );
			$this->error( $attachment_id->get_error_message() );
		}

		// Generate metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		$this->success( array(
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'message'       => __( 'Image saved to media library.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}
}
