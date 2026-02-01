<?php
/**
 * Email AJAX Handlers
 *
 * Handles all email template-related AJAX actions including
 * template CRUD, generation, preview, and email provider settings.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email AJAX Handlers class
 */
class AISales_Ajax_Email extends AISales_Ajax_Base {

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		// Template management
		$this->add_action( 'get_email_templates', 'handle_get_email_templates' );
		$this->add_action( 'save_email_template', 'handle_save_email_template' );
		$this->add_action( 'toggle_email_template', 'handle_toggle_email_template' );
		$this->add_action( 'delete_email_template', 'handle_delete_email_template' );

		// AI generation
		$this->add_action( 'generate_email_template', 'handle_generate_email_template' );

		// Preview and testing
		$this->add_action( 'preview_email_template', 'handle_preview_email_template' );
		$this->add_action( 'send_test_email', 'handle_send_test_email' );

		// Mail provider
		$this->add_action( 'save_mail_provider_settings', 'handle_save_mail_provider_settings' );
		$this->add_action( 'send_mail_provider_test', 'handle_send_mail_provider_test' );

		// Email wizard
		$this->add_action( 'save_wizard_context', 'handle_save_wizard_context' );
		$this->add_action( 'complete_email_wizard', 'handle_complete_email_wizard' );
	}

	/**
	 * Get email manager instance
	 *
	 * @return AISales_Email_Manager
	 */
	private function email_manager() {
		return AISales_Email_Manager::instance();
	}

	/**
	 * Handle get email templates
	 */
	public function handle_get_email_templates() {
		$this->verify_request();

		$email_manager = $this->email_manager();

		$this->success( array(
			'templates'    => $email_manager->get_templates_overview(),
			'placeholders' => $email_manager->get_placeholders(),
		) );
	}

	/**
	 * Handle save email template
	 */
	public function handle_save_email_template() {
		$this->verify_request();

		$template_type = $this->require_post( 'template_type', 'key', __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) );
		$name          = $this->get_post( 'name', 'text' );
		$subject       = $this->get_post( 'subject', 'text' );
		$heading       = $this->get_post( 'heading', 'text' );
		$status        = $this->get_post( 'status', 'key', 'draft' );

		// Allow HTML in content but sanitize it
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		$email_manager = $this->email_manager();
		$valid_types   = array_keys( $email_manager->get_template_types() );

		if ( ! in_array( $template_type, $valid_types, true ) ) {
			$this->error( __( 'Invalid template type.', 'ai-sales-manager-for-woocommerce' ) );
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
			$this->success( array(
				'message'  => __( 'Template saved successfully.', 'ai-sales-manager-for-woocommerce' ),
				'template' => $email_manager->get_template( $template_type ),
			) );
		}

		$this->error( __( 'Failed to save template.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Handle generate email template via AI
	 */
	public function handle_generate_email_template() {
		$this->verify_request();

		if ( ! $this->api()->is_connected() ) {
			$this->error( __( 'Please connect to AI Sales Manager first.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$template_type   = $this->require_post( 'template_type', 'key', __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) );
		$custom_prompt   = $this->get_post( 'custom_prompt', 'textarea' );
		$regenerate_part = $this->get_post( 'regenerate_part', 'key' );

		// Build store context
		$store_context = $this->build_store_context();

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

		$result = $this->handle_api_result( $this->api()->generate_email_template( $request_body ) );

		if ( ! isset( $result['template'] ) ) {
			$this->error( __( 'Invalid API response.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Update local balance if returned
		if ( isset( $result['template']['tokens_used'] ) ) {
			$this->update_local_balance_from_tokens( $result['template']['tokens_used'] );
		}

		$this->success( array(
			'template'   => $result['template'],
			'validation' => isset( $result['validation'] ) ? $result['validation'] : null,
			'balance'    => intval( get_option( 'aisales_balance', 0 ) ),
		) );
	}

	/**
	 * Handle preview email template
	 */
	public function handle_preview_email_template() {
		$this->verify_request();

		$subject = $this->get_post( 'subject', 'text' );
		$heading = $this->get_post( 'heading', 'text' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		$preview = $this->email_manager()->preview_template( array(
			'subject' => $subject,
			'heading' => $heading,
			'content' => $content,
		) );

		$html = $this->generate_email_preview_html( $preview );

		$this->success( array(
			'preview' => array_merge( $preview, array( 'html' => $html ) ),
		) );
	}

	/**
	 * Handle send test email
	 */
	public function handle_send_test_email() {
		$this->verify_request();

		$recipient = $this->get_post( 'recipient', 'email' );
		$subject   = $this->get_post( 'subject', 'text' );
		$heading   = $this->get_post( 'heading', 'text' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$content   = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			$this->error( __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$preview = $this->email_manager()->preview_template( array(
			'subject' => $subject,
			'heading' => $heading,
			'content' => $content,
		) );

		if ( empty( $preview['subject'] ) ) {
			$preview['subject'] = __( 'Test Email Preview', 'ai-sales-manager-for-woocommerce' );
		}

		$html    = $this->generate_email_preview_html( $preview );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $recipient, $preview['subject'], $html, $headers );

		if ( $sent ) {
			$this->success( array(
				'message' => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ),
			) );
		}

		$this->error( __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Handle save mail provider settings
	 */
	public function handle_save_mail_provider_settings() {
		$this->verify_request();

		$settings_raw = $this->get_post( 'settings', 'raw' );
		if ( is_string( $settings_raw ) ) {
			$decoded  = json_decode( $settings_raw, true );
			$settings = is_array( $decoded ) ? $decoded : array();
		} else {
			$settings = is_array( $settings_raw ) ? $settings_raw : array();
		}

		$mail_provider = AISales_Mail_Provider::instance();
		$success       = $mail_provider->save_settings( $settings );

		if ( ! $success ) {
			$this->error( __( 'Failed to save settings.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$this->success( array(
			'message' => __( 'Email delivery settings saved.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle send mail provider test
	 */
	public function handle_send_mail_provider_test() {
		$this->verify_request();

		$recipient = $this->get_post( 'recipient', 'email' );

		if ( empty( $recipient ) || ! is_email( $recipient ) ) {
			$this->error( __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$subject = __( 'Email Delivery Test', 'ai-sales-manager-for-woocommerce' );
		$body    = __( 'This is a test email sent from AI Sales Manager to verify your email delivery settings.', 'ai-sales-manager-for-woocommerce' );
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $recipient, $subject, $body, $headers );

		if ( $sent ) {
			$this->success( array( 'message' => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ) ) );
		}

		$this->error( __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Handle toggle email template (enable/disable)
	 */
	public function handle_toggle_email_template() {
		$this->verify_request();

		$template_type = $this->require_post( 'template_type', 'key', __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) );
		$enabled       = $this->get_post( 'enabled', 'bool', false );

		$email_manager = $this->email_manager();

		if ( $enabled ) {
			$success = $email_manager->enable_template( $template_type );
			$message = __( 'Template enabled successfully.', 'ai-sales-manager-for-woocommerce' );
		} else {
			$success = $email_manager->disable_template( $template_type );
			$message = __( 'Template disabled successfully.', 'ai-sales-manager-for-woocommerce' );
		}

		if ( $success ) {
			$this->success( array(
				'message'  => $message,
				'template' => $email_manager->get_template( $template_type ),
			) );
		}

		$this->error( __( 'Failed to update template status.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Handle delete email template
	 */
	public function handle_delete_email_template() {
		$this->verify_request();

		$template_type = $this->require_post( 'template_type', 'key', __( 'Template type is required.', 'ai-sales-manager-for-woocommerce' ) );

		$email_manager = $this->email_manager();
		$success       = $email_manager->delete_template( $template_type );

		if ( $success ) {
			$this->success( array(
				'message' => __( 'Template deleted successfully.', 'ai-sales-manager-for-woocommerce' ),
			) );
		}

		$this->error( __( 'Failed to delete template.', 'ai-sales-manager-for-woocommerce' ) );
	}

	/**
	 * Handle save wizard context
	 */
	public function handle_save_wizard_context() {
		$this->verify_request();

		$context = $this->get_json_post( 'context', true, array() );

		if ( empty( $context ) ) {
			$this->error( __( 'Invalid wizard context.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Sanitize context values
		$sanitized = array();
		foreach ( $context as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = is_array( $value )
				? array_map( 'sanitize_text_field', $value )
				: sanitize_text_field( $value );
		}

		update_option( 'aisales_email_wizard_context', $sanitized );

		$this->success( array(
			'message' => __( 'Wizard context saved.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle complete email wizard
	 */
	public function handle_complete_email_wizard() {
		$this->verify_request();

		// Mark wizard as completed
		update_option( 'aisales_email_wizard_completed', true );

		// Clear wizard context
		delete_option( 'aisales_email_wizard_context' );

		$this->success( array(
			'message' => __( 'Email setup completed successfully.', 'ai-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Build store context for AI generation
	 *
	 * @return array Store context data.
	 */
	private function build_store_context() {
		$store_context_option = get_option( 'aisales_store_context', array() );
		$store_context        = array();

		// Always include store name and description with fallbacks
		$store_context['store_name'] = ! empty( $store_context_option['store_name'] )
			? $store_context_option['store_name']
			: get_bloginfo( 'name' );
		$store_context['store_description'] = ! empty( $store_context_option['store_description'] )
			? $store_context_option['store_description']
			: get_bloginfo( 'description' );

		// Optional fields
		$optional_fields = array( 'business_niche', 'target_audience', 'brand_tone', 'language', 'custom_instructions' );
		foreach ( $optional_fields as $field ) {
			if ( ! empty( $store_context_option[ $field ] ) ) {
				$store_context[ $field ] = $store_context_option[ $field ];
			}
		}

		return $store_context;
	}

	/**
	 * Update local balance from tokens used
	 *
	 * @param int $tokens_used Tokens used in operation.
	 */
	private function update_local_balance_from_tokens( $tokens_used ) {
		$current = get_option( 'aisales_balance', 0 );
		$new     = max( 0, intval( $current ) - intval( $tokens_used ) );
		update_option( 'aisales_balance', $new );
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

		$header_image = get_option( 'woocommerce_email_header_image', '' );

		if ( empty( $footer_text ) ) {
			/* translators: %s: site name */
			$footer_text = sprintf( __( '%s - Powered by WooCommerce', 'ai-sales-manager-for-woocommerce' ), $store_name );
		}

		$header_text_color = $this->get_contrasting_color( $base_color );
		$link_color        = $this->adjust_color_brightness( $base_color, -20 );
		$content_html      = wpautop( $preview['content'] );

		ob_start();
		include AISALES_PLUGIN_PATH . 'templates/partials/email-preview-template.php';
		$html = ob_get_clean();

		// If template doesn't exist, use inline HTML
		if ( empty( $html ) ) {
			$html = $this->generate_inline_email_html( $preview, $base_color, $text_color, $body_bg, $header_text_color, $link_color, $content_html, $store_name, $header_image, $footer_text );
		}

		return $html;
	}

	/**
	 * Generate inline email HTML (fallback)
	 *
	 * @param array  $preview           Preview data.
	 * @param string $base_color        Base color.
	 * @param string $text_color        Text color.
	 * @param string $body_bg           Body background.
	 * @param string $header_text_color Header text color.
	 * @param string $link_color        Link color.
	 * @param string $content_html      Content HTML.
	 * @param string $store_name        Store name.
	 * @param string $header_image      Header image URL.
	 * @param string $footer_text       Footer text.
	 * @return string HTML email.
	 */
	private function generate_inline_email_html( $preview, $base_color, $text_color, $body_bg, $header_text_color, $link_color, $content_html, $store_name, $header_image, $footer_text ) {
		$gradient_end = $this->adjust_color_brightness( $base_color, -15 );

		return sprintf(
			'<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>%s</title></head>
<body style="margin:0;padding:32px 16px;background:%s;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif;color:%s;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
<div style="background:linear-gradient(135deg,%s 0%%,%s 100%%);padding:40px 48px;text-align:center;">
%s
<h1 style="margin:0;font-size:26px;color:%s;">%s</h1>
</div>
<div style="padding:48px;">%s</div>
<div style="background:#f8fafc;padding:32px 48px;text-align:center;border-top:1px solid #e2e8f0;">
<p style="margin:0;font-size:13px;color:#64748b;">%s</p>
</div>
</div>
</body></html>',
			esc_html( $preview['subject'] ),
			esc_attr( $body_bg ),
			esc_attr( $text_color ),
			esc_attr( $base_color ),
			esc_attr( $gradient_end ),
			$header_image ? sprintf( '<div style="margin-bottom:20px;"><img src="%s" alt="%s" style="max-width:180px;max-height:60px;"></div>', esc_url( $header_image ), esc_attr( $store_name ) ) : '',
			esc_attr( $header_text_color ),
			esc_html( $preview['heading'] ),
			wp_kses_post( $content_html ),
			wp_kses_post( $footer_text )
		);
	}

	/**
	 * Get contrasting text color for a background color
	 *
	 * @param string $hex_color Hex color code.
	 * @return string '#ffffff' or '#1a1a1a'
	 */
	private function get_contrasting_color( $hex_color ) {
		$hex_color = ltrim( $hex_color, '#' );

		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );

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
}
