<?php
/**
 * Brand AJAX Handlers
 *
 * Handles all brand settings-related AJAX actions including
 * saving brand settings and AI-powered brand analysis.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand AJAX Handlers class
 */
class AISales_Ajax_Brand extends AISales_Ajax_Base {

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		$this->add_action( 'save_brand_settings', 'handle_save_brand_settings' );
		$this->add_action( 'analyze_brand', 'handle_analyze_brand' );
	}

	/**
	 * Handle save brand settings
	 */
	public function handle_save_brand_settings() {
		$this->verify_request();

		// Get existing context to preserve other fields
		$existing = get_option( 'aisales_store_context', array() );

		// Build new context with brand settings
		$store_context = array_merge( $existing, array(
			// Store Identity
			'store_name'       => $this->get_post( 'store_name', 'text' ),
			'tagline'          => $this->get_post( 'tagline', 'text' ),
			'business_niche'   => $this->get_post( 'business_niche', 'key' ),

			// Audience & Positioning
			'target_audience'  => $this->get_post( 'target_audience', 'textarea' ),
			'price_position'   => $this->get_post( 'price_position', 'key' ),
			'differentiator'   => $this->get_post( 'differentiator', 'textarea' ),
			'pain_points'      => $this->get_post( 'pain_points', 'textarea' ),

			// Brand Voice
			'brand_tone'       => $this->get_post( 'brand_tone', 'key', 'friendly' ),
			'words_to_avoid'   => $this->get_post( 'words_to_avoid', 'text' ),
			'promotion_style'  => $this->get_post( 'promotion_style', 'key', 'moderate' ),

			// Visual Style
			'primary_color'    => $this->sanitize_hex_color( $this->get_post( 'primary_color', 'text', '#7f54b3' ) ),
			'text_color'       => $this->sanitize_hex_color( $this->get_post( 'text_color', 'text', '#3c3c3c' ) ),
			'bg_color'         => $this->sanitize_hex_color( $this->get_post( 'bg_color', 'text', '#f7f7f7' ) ),
			'font_family'      => $this->get_post( 'font_family', 'key', 'system' ),

			'updated_at'       => current_time( 'mysql' ),
		) );

		update_option( 'aisales_store_context', $store_context );
		update_option( 'aisales_brand_setup_complete', true );

		$this->success( array(
			'message' => __( 'Brand settings saved successfully.', 'stacksuite-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle AI brand analysis
	 */
	public function handle_analyze_brand() {
		$this->verify_request();

		if ( ! $this->api()->is_connected() ) {
			$this->error( __( 'Please connect to StackSuite Sales Manager first.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Get analysis context from brand page
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-brand-page.php';
		$brand_page = AISales_Brand_Page::instance();
		$context    = $brand_page->get_analysis_context();

		// Call API for AI analysis
		$result = $this->handle_api_result( $this->api()->analyze_brand( $context ) );

		// Update local balance if returned
		if ( isset( $result['tokens_used']['total'] ) ) {
			$this->update_local_balance( $result['tokens_used'] );
		}

		$this->success( array(
			'suggestions' => isset( $result['suggestions'] ) ? $result['suggestions'] : array(),
			'tokens_used' => isset( $result['tokens_used'] ) ? $result['tokens_used'] : array(),
			'balance'     => intval( get_option( 'aisales_balance', 0 ) ),
		) );
	}

	/**
	 * Sanitize hex color
	 *
	 * @param string $color Color value.
	 * @return string Sanitized hex color.
	 */
	private function sanitize_hex_color( $color ) {
		$sanitized = sanitize_hex_color( $color );
		return $sanitized ? $sanitized : '#000000';
	}

	/**
	 * Update local balance from tokens used
	 *
	 * @param array $tokens_used Tokens used data.
	 */
	private function update_local_balance( $tokens_used ) {
		if ( isset( $tokens_used['total'] ) ) {
			$current = get_option( 'aisales_balance', 0 );
			$new     = max( 0, intval( $current ) - intval( $tokens_used['total'] ) );
			update_option( 'aisales_balance', $new );
		}
	}
}
