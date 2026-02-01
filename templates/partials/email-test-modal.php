<?php
/**
 * Email Template Test Send Modal
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- Test Email Modal Overlay -->
<div class="aisales-modal-overlay aisales-email-test-modal-overlay" id="aisales-email-test-modal-overlay"></div>

<!-- Test Email Modal -->
<div class="aisales-modal aisales-email-test-modal" id="aisales-email-test-modal" role="dialog" aria-modal="true" aria-labelledby="aisales-email-test-modal-title">
	<div class="aisales-modal__header">
		<h2 class="aisales-modal__title" id="aisales-email-test-modal-title">
			<span class="dashicons dashicons-email-alt"></span>
			<?php esc_html_e( 'Send Test Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
		</h2>
		<button type="button" class="aisales-modal__close" id="aisales-email-test-modal-close" aria-label="<?php esc_attr_e( 'Close', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<div class="aisales-modal__body">
		<div class="aisales-form-group">
			<label class="aisales-form-label" for="aisales-email-test-recipient">
				<span class="dashicons dashicons-admin-users"></span>
				<?php esc_html_e( 'Recipient Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</label>
			<input
				type="email"
				class="aisales-form-input"
				id="aisales-email-test-recipient"
				placeholder="<?php esc_attr_e( 'name@example.com', 'stacksuite-sales-manager-for-woocommerce' ); ?>"
			>
			<span class="aisales-form-hint">
				<?php esc_html_e( 'Uses sample order data and does not send to customers.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
	</div>

	<div class="aisales-modal__footer">
		<div class="aisales-modal__info">
			<span class="dashicons dashicons-info-outline"></span>
			<?php esc_html_e( 'Preview your content in a real inbox.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
		</div>
		<div class="aisales-modal__actions">
			<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-email-test-cancel">
				<?php esc_html_e( 'Cancel', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-email-test-send">
				<span class="dashicons dashicons-email-alt"></span>
				<?php esc_html_e( 'Send Test', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
