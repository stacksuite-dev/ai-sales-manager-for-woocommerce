<?php
/**
 * Mail Provider Test Send Modal
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="aisales-modal-overlay aisales-mail-provider-test-overlay" id="aisales-mail-provider-test-overlay"></div>

<div class="aisales-modal aisales-mail-provider-test" id="aisales-mail-provider-test" role="dialog" aria-modal="true" aria-labelledby="aisales-mail-provider-test-title">
	<div class="aisales-modal__header">
		<h2 class="aisales-modal__title" id="aisales-mail-provider-test-title">
			<span class="dashicons dashicons-email-alt"></span>
			<?php esc_html_e( 'Send Test Email', 'ai-sales-manager-for-woocommerce' ); ?>
		</h2>
		<button type="button" class="aisales-modal__close" id="aisales-mail-provider-test-close" aria-label="<?php esc_attr_e( 'Close', 'ai-sales-manager-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<div class="aisales-modal__body">
		<div class="aisales-form-group">
			<label class="aisales-form-label" for="aisales-mail-provider-test-recipient">
				<span class="dashicons dashicons-admin-users"></span>
				<?php esc_html_e( 'Recipient Email', 'ai-sales-manager-for-woocommerce' ); ?>
			</label>
			<input
				type="email"
				class="aisales-form-input"
				id="aisales-mail-provider-test-recipient"
				placeholder="<?php esc_attr_e( 'name@example.com', 'ai-sales-manager-for-woocommerce' ); ?>"
			>
			<span class="aisales-form-hint">
				<?php esc_html_e( 'Send a sample delivery email to verify settings.', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
	</div>

	<div class="aisales-modal__footer">
		<div class="aisales-modal__info">
			<span class="dashicons dashicons-info-outline"></span>
			<?php esc_html_e( 'Use a mailbox you can access to confirm delivery.', 'ai-sales-manager-for-woocommerce' ); ?>
		</div>
		<div class="aisales-modal__actions">
			<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-mail-provider-test-cancel">
				<?php esc_html_e( 'Cancel', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-mail-provider-test-send">
				<span class="dashicons dashicons-email-alt"></span>
				<?php esc_html_e( 'Send Test', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
