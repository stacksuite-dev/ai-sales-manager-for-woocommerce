<?php
/**
 * Products AJAX Handlers
 *
 * Handles all product and category-related AJAX actions including
 * field updates, catalog changes, and product/category data retrieval.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Products AJAX Handlers class
 */
class AISales_Ajax_Products extends AISales_Ajax_Base {

	/**
	 * Nonce action for chat-related actions
	 *
	 * @var string
	 */
	protected $chat_nonce_action = 'aisales_chat_nonce';

	/**
	 * Allowed product fields
	 *
	 * @var array
	 */
	private $allowed_product_fields = array(
		'title',
		'description',
		'short_description',
		'tags',
		'categories',
	);

	/**
	 * Allowed category fields
	 *
	 * @var array
	 */
	private $allowed_category_fields = array(
		'name',
		'description',
		'seo_title',
		'meta_description',
	);

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		// Product actions
		$this->add_action( 'update_product_field', 'handle_update_product_field' );
		$this->add_action( 'set_product_featured_image', 'handle_set_product_featured_image' );

		// Category actions
		$this->add_action( 'update_category_field', 'handle_update_category_field' );
		$this->add_action( 'get_category', 'handle_get_category' );
		$this->add_action( 'set_category_thumbnail', 'handle_set_category_thumbnail' );

		// Catalog organization
		$this->add_action( 'apply_catalog_change', 'handle_apply_catalog_change' );

		// Batch operations
		$this->add_action( 'apply_batch_result', 'handle_apply_batch_result' );
	}

	/**
	 * Verify request with chat nonce
	 *
	 * @param string $capability Required capability.
	 */
	protected function verify_chat_request( $capability = 'edit_products' ) {
		check_ajax_referer( $this->chat_nonce_action, $this->nonce_field );

		if ( ! current_user_can( $capability ) ) {
			$this->error( __( 'Permission denied.', 'ai-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Handle update product field
	 */
	public function handle_update_product_field() {
		$this->verify_chat_request( 'edit_products' );

		$product_id = $this->get_post( 'product_id', 'int' );
		$field      = $this->get_post( 'field', 'key' );

		$product = $this->get_validated_product( $product_id );

		if ( ! in_array( $field, $this->allowed_product_fields, true ) ) {
			$this->error( __( 'Invalid field.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Get value with appropriate sanitization
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value_raw = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
		$value     = in_array( $field, array( 'description', 'short_description' ), true )
			? wp_kses_post( $value_raw )
			: sanitize_text_field( $value_raw );

		// Update the field
		switch ( $field ) {
			case 'title':
				$product->set_name( $value );
				$product->save();
				break;

			case 'description':
				$product->set_description( $value );
				$product->save();
				break;

			case 'short_description':
				$product->set_short_description( $value );
				$product->save();
				break;

			case 'tags':
				$tags = array_filter( array_map( 'trim', explode( ',', $value ) ) );
				wp_set_object_terms( $product_id, $tags, 'product_tag' );
				break;

			case 'categories':
				$this->update_product_categories( $product_id, $value );
				break;
		}

		$this->success( array(
			'message' => __( 'Product updated successfully.', 'ai-sales-manager-for-woocommerce' ),
			'field'   => $field,
		) );
	}

	/**
	 * Handle update category field
	 */
	public function handle_update_category_field() {
		$this->verify_chat_request( 'manage_product_terms' );

		$category_id = $this->get_post( 'category_id', 'int' );
		$field       = $this->get_post( 'field', 'key' );

		$term = $this->get_validated_category( $category_id );

		if ( ! in_array( $field, $this->allowed_category_fields, true ) ) {
			$this->error( __( 'Invalid field.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Get value with appropriate sanitization
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value_raw = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
		$value     = 'description' === $field
			? wp_kses_post( $value_raw )
			: sanitize_text_field( $value_raw );

		// Update the field
		switch ( $field ) {
			case 'name':
			case 'description':
				wp_update_term( $category_id, 'product_cat', array( $field => $value ) );
				break;

			case 'seo_title':
				update_term_meta( $category_id, '_yoast_wpseo_title', $value );
				update_term_meta( $category_id, 'rank_math_title', $value );
				update_term_meta( $category_id, 'aisales_seo_title', $value );
				break;

			case 'meta_description':
				update_term_meta( $category_id, '_yoast_wpseo_metadesc', $value );
				update_term_meta( $category_id, 'rank_math_description', $value );
				update_term_meta( $category_id, 'aisales_meta_description', $value );
				break;
		}

		$this->success( array(
			'message' => __( 'Category updated successfully.', 'ai-sales-manager-for-woocommerce' ),
			'field'   => $field,
		) );
	}

	/**
	 * Handle get category data
	 */
	public function handle_get_category() {
		$this->verify_chat_request( 'manage_product_terms' );

		$category_id = $this->get_post( 'category_id', 'int' );
		$term        = $this->get_validated_category( $category_id );

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

		// Get SEO meta (check multiple sources)
		$seo_title        = $this->get_category_seo_meta( $category_id, 'title' );
		$meta_description = $this->get_category_seo_meta( $category_id, 'description' );

		// Get product count
		$product_count = $term->count;

		// Get thumbnail
		$thumbnail_id  = get_term_meta( $category_id, 'thumbnail_id', true );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '';

		$this->success( array(
			'category' => array(
				'id'               => $term->term_id,
				'name'             => $term->name,
				'slug'             => $term->slug,
				'description'      => $term->description,
				'parent_id'        => $term->parent,
				'parent_name'      => $parent_name,
				'product_count'    => $product_count,
				'subcategories'    => $subcats,
				'seo_title'        => $seo_title,
				'meta_description' => $meta_description,
				'thumbnail_url'    => $thumbnail_url,
			),
		) );
	}

	/**
	 * Handle apply catalog change
	 */
	public function handle_apply_catalog_change() {
		$this->verify_chat_request( 'manage_woocommerce' );

		$action_type = $this->require_post( 'action_type', 'key', __( 'Action type is required.', 'ai-sales-manager-for-woocommerce' ) );

		$params_raw = $this->get_post( 'params', 'raw' );
		if ( is_string( $params_raw ) ) {
			$params = json_decode( $params_raw, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$this->error( __( 'Invalid params format.', 'ai-sales-manager-for-woocommerce' ) );
			}
		} else {
			$params = is_array( $params_raw ) ? $params_raw : array();
		}

		// Sanitize params
		$params = map_deep( $params, 'sanitize_text_field' );

		// Load and execute tool
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-tool-executor.php';
		$executor = new AISales_Tool_Executor();

		$result = $executor->execute( 'apply_catalog_change', array_merge(
			array( 'action_type' => $action_type ),
			$params
		) );

		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		}

		$this->success( $result );
	}

	/**
	 * Handle set product featured image
	 */
	public function handle_set_product_featured_image() {
		$this->verify_chat_request( 'edit_products' );

		$product_id = $this->get_post( 'product_id', 'int' );
		$image_url  = $this->get_post( 'image_url', 'url' );

		$product = $this->get_validated_product( $product_id );

		if ( empty( $image_url ) ) {
			$this->error( __( 'Image URL is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Download and attach image
		$attachment_id = $this->sideload_image( $image_url, $product_id );

		if ( is_wp_error( $attachment_id ) ) {
			$this->error( $attachment_id->get_error_message() );
		}

		// Set as featured image
		set_post_thumbnail( $product_id, $attachment_id );

		$this->success( array(
			'message'       => __( 'Featured image set successfully.', 'ai-sales-manager-for-woocommerce' ),
			'attachment_id' => $attachment_id,
			'image_url'     => wp_get_attachment_url( $attachment_id ),
		) );
	}

	/**
	 * Handle set category thumbnail
	 */
	public function handle_set_category_thumbnail() {
		$this->verify_chat_request( 'manage_product_terms' );

		$category_id = $this->get_post( 'category_id', 'int' );
		$image_url   = $this->get_post( 'image_url', 'url' );

		$term = $this->get_validated_category( $category_id );

		if ( empty( $image_url ) ) {
			$this->error( __( 'Image URL is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Download and attach image
		$attachment_id = $this->sideload_image( $image_url, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			$this->error( $attachment_id->get_error_message() );
		}

		// Set as category thumbnail
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );

		$this->success( array(
			'message'       => __( 'Category thumbnail set successfully.', 'ai-sales-manager-for-woocommerce' ),
			'attachment_id' => $attachment_id,
			'image_url'     => wp_get_attachment_url( $attachment_id ),
		) );
	}

	/**
	 * Handle apply batch result
	 */
	public function handle_apply_batch_result() {
		$this->verify_request();

		$entity_type = $this->require_post( 'entity_type', 'key' );
		$entity_id   = $this->require_post( 'entity_id', 'int' );
		$fields      = $this->get_json_post( 'fields', true, array() );

		if ( empty( $fields ) ) {
			$this->error( __( 'No fields to update.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = null;

		switch ( $entity_type ) {
			case 'product':
				$result = $this->apply_batch_to_product( $entity_id, $fields );
				break;

			case 'category':
				$result = $this->apply_batch_to_category( $entity_id, $fields );
				break;

			default:
				$this->error( __( 'Invalid entity type.', 'ai-sales-manager-for-woocommerce' ) );
		}

		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		}

		$this->success( array(
			'message' => __( 'Changes applied successfully.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Update product categories
	 *
	 * @param int    $product_id Product ID.
	 * @param string $value      Comma-separated category names.
	 */
	private function update_product_categories( $product_id, $value ) {
		$categories = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		$term_ids   = array();

		foreach ( $categories as $cat_name ) {
			$term = get_term_by( 'name', $cat_name, 'product_cat' );
			if ( $term ) {
				$term_ids[] = $term->term_id;
			} else {
				$new_term = wp_insert_term( $cat_name, 'product_cat' );
				if ( ! is_wp_error( $new_term ) ) {
					$term_ids[] = $new_term['term_id'];
				}
			}
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
		}
	}

	/**
	 * Get category SEO meta from various sources
	 *
	 * @param int    $category_id Category ID.
	 * @param string $type        Meta type ('title' or 'description').
	 * @return string Meta value.
	 */
	private function get_category_seo_meta( $category_id, $type ) {
		$meta_keys = 'title' === $type
			? array( 'aisales_seo_title', '_yoast_wpseo_title', 'rank_math_title' )
			: array( 'aisales_meta_description', '_yoast_wpseo_metadesc', 'rank_math_description' );

		foreach ( $meta_keys as $key ) {
			$value = get_term_meta( $category_id, $key, true );
			if ( ! empty( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Sideload image from URL
	 *
	 * @param string $url     Image URL.
	 * @param int    $post_id Post ID to attach to.
	 * @return int|WP_Error Attachment ID or error.
	 */
	private function sideload_image( $url, $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file_array = array(
			'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Clean up temp file
		if ( file_exists( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return $attachment_id;
	}

	/**
	 * Apply batch updates to a product
	 *
	 * @param int   $product_id Product ID.
	 * @param array $fields     Fields to update.
	 * @return true|WP_Error
	 */
	private function apply_batch_to_product( $product_id, $fields ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'invalid_product', __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		foreach ( $fields as $field => $value ) {
			switch ( $field ) {
				case 'title':
					$product->set_name( sanitize_text_field( $value ) );
					break;
				case 'description':
					$product->set_description( wp_kses_post( $value ) );
					break;
				case 'short_description':
					$product->set_short_description( wp_kses_post( $value ) );
					break;
			}
		}

		$product->save();
		return true;
	}

	/**
	 * Apply batch updates to a category
	 *
	 * @param int   $category_id Category ID.
	 * @param array $fields      Fields to update.
	 * @return true|WP_Error
	 */
	private function apply_batch_to_category( $category_id, $fields ) {
		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'invalid_category', __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$term_data = array();
		foreach ( $fields as $field => $value ) {
			switch ( $field ) {
				case 'name':
					$term_data['name'] = sanitize_text_field( $value );
					break;
				case 'description':
					$term_data['description'] = wp_kses_post( $value );
					break;
				case 'seo_title':
					update_term_meta( $category_id, 'aisales_seo_title', sanitize_text_field( $value ) );
					break;
				case 'meta_description':
					update_term_meta( $category_id, 'aisales_meta_description', sanitize_text_field( $value ) );
					break;
			}
		}

		if ( ! empty( $term_data ) ) {
			wp_update_term( $category_id, 'product_cat', $term_data );
		}

		return true;
	}
}
