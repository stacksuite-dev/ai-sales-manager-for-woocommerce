<?php
/**
 * AI Generation AJAX Handlers
 *
 * Handles all AI generation-related AJAX actions including
 * content generation, image generation, and taxonomy suggestions.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * AI Generation AJAX Handlers class
 */
class AISales_Ajax_AI extends AISales_Ajax_Base {

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		$this->add_action( 'generate_content', 'handle_generate_content' );
		$this->add_action( 'suggest_taxonomy', 'handle_suggest_taxonomy' );
		$this->add_action( 'generate_image', 'handle_generate_image' );
		$this->add_action( 'improve_image', 'handle_improve_image' );
	}

	/**
	 * Handle generate content
	 */
	public function handle_generate_content() {
		$this->verify_request();

		$product_id = $this->get_post( 'product_id', 'int' );
		$product    = $this->get_validated_product( $product_id );
		$action     = $this->get_post( 'ai_action', 'key', 'improve' );

		$result = $this->handle_api_result(
			$this->api()->generate_content( array(
				'product_title'       => $product->get_name(),
				'product_description' => $product->get_description(),
				'action'              => $action,
			) )
		);

		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		$this->success( array(
			'result'      => $result['result'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle suggest taxonomy
	 */
	public function handle_suggest_taxonomy() {
		$this->verify_request();

		$product_id = $this->get_post( 'product_id', 'int' );
		$product    = $this->get_validated_product( $product_id );

		$result = $this->handle_api_result(
			$this->api()->suggest_taxonomy( array(
				'product_title'       => $product->get_name(),
				'product_description' => $product->get_description(),
			) )
		);

		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		$this->success( array(
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
		$this->verify_request();

		$product_id = $this->get_post( 'product_id', 'int' );
		$product    = $this->get_validated_product( $product_id );
		$style      = $this->get_post( 'style', 'key', 'product_photo' );

		$result = $this->handle_api_result(
			$this->api()->generate_image( array(
				'product_title'       => $product->get_name(),
				'product_description' => $product->get_description(),
				'style'               => $style,
			) )
		);

		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		$this->success( array(
			'image_url'   => $result['image_url'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}

	/**
	 * Handle improve image
	 */
	public function handle_improve_image() {
		$this->verify_request();

		$product_id = $this->get_post( 'product_id', 'int' );

		if ( ! $product_id ) {
			$this->error( __( 'Invalid product.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$image_url = $this->require_post( 'image_url', 'url', __( 'No image selected.', 'stacksuite-sales-manager-for-woocommerce' ) );
		$action    = $this->get_post( 'improve_action', 'key', 'enhance' );

		$result = $this->handle_api_result(
			$this->api()->improve_image( array(
				'image_url' => $image_url,
				'action'    => $action,
			) )
		);

		$balance = isset( $result['new_balance'] ) ? $result['new_balance'] : get_option( 'aisales_balance', 0 );

		$this->success( array(
			'image_url'   => $result['image_url'],
			'tokens_used' => $result['tokens_used'],
			'new_balance' => $balance,
		) );
	}
}
