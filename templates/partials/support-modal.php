<?php
/**
 * Support Modal
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="aisales-support-fab" id="aisales-support-fab" title="<?php esc_attr_e( 'Support Center', 'stacksuite-sales-manager-for-woocommerce' ); ?>" data-aisales-support-trigger>
	<span class="dashicons dashicons-sos"></span>
	<span class="aisales-support-fab__label"><?php esc_html_e( 'Support', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
</div>

<div class="aisales-modal-overlay" id="aisales-support-modal-overlay"></div>
<div class="aisales-modal aisales-support-modal" id="aisales-support-modal">
	<div class="aisales-modal__header">
		<h3 class="aisales-modal__title"><?php esc_html_e( 'Start a Support Request', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
		<button type="button" class="aisales-modal__close" id="aisales-support-modal-close">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>
	<div class="aisales-modal__body">
		<div class="aisales-support-modal__steps">
			<button type="button" class="aisales-support-step-tab is-active" data-step="draft">
				<span>1</span>
				<?php esc_html_e( 'Draft', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-support-step-tab" data-step="clarify">
				<span>2</span>
				<?php esc_html_e( 'Clarify', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-support-step-tab" data-step="review">
				<span>3</span>
				<?php esc_html_e( 'Submit', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>

		<div class="aisales-support-step-panel is-active" data-step="draft">
			<div class="aisales-form-group">
				<label class="aisales-form-label" for="aisales-support-draft-title">
					<span class="dashicons dashicons-edit"></span>
					<?php esc_html_e( 'Issue Title', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<input id="aisales-support-draft-title" class="aisales-form-input" type="text" placeholder="<?php esc_attr_e( 'Checkout page spinner stuck', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
				<span class="aisales-form-hint"><?php esc_html_e( 'Short title helps AI classify quickly.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-form-group">
				<label class="aisales-form-label" for="aisales-support-draft-description">
					<span class="dashicons dashicons-align-left"></span>
					<?php esc_html_e( 'Describe what happened', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<textarea id="aisales-support-draft-description" class="aisales-support-textarea" rows="4" placeholder="<?php esc_attr_e( 'Steps to reproduce, expected behavior, logs, and timing details...', 'stacksuite-sales-manager-for-woocommerce' ); ?>"></textarea>
			</div>
			<div class="aisales-form-group">
				<label class="aisales-form-label">
					<span class="dashicons dashicons-tag"></span>
					<?php esc_html_e( 'Category', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<div class="aisales-support-chip-group">
					<button type="button" class="aisales-support-chip is-selected">Support</button>
					<button type="button" class="aisales-support-chip">Bug</button>
					<button type="button" class="aisales-support-chip">Feature</button>
				</div>
			</div>
			<div class="aisales-form-group">
				<label class="aisales-form-label">
					<span class="dashicons dashicons-paperclip"></span>
					<?php esc_html_e( 'Attachments', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<div class="aisales-support-upload">
					<input type="file" id="aisales-support-upload-input" class="aisales-support-upload__input" multiple accept=".png,.jpg,.jpeg,.webp,.pdf">
					<span class="dashicons dashicons-upload"></span>
					<div>
						<strong><?php esc_html_e( 'Drop files or click to upload', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
						<p><?php esc_html_e( 'PNG, JPG, PDF up to 7MB', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="aisales-support-attachment-list"></div>
			</div>
		</div>

		<div class="aisales-support-step-panel" data-step="clarify">
			<div class="aisales-support-ai-card">
				<div class="aisales-support-ai-card__header">
					<span class="dashicons dashicons-admin-site-alt3"></span>
					<?php esc_html_e( 'AI Summary', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</div>
				<p><?php esc_html_e( 'We detected a checkout regression after the latest update. The issue might be related to caching or a conflicting payment gateway.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
			</div>
			<div class="aisales-support-clarify">
				<h4><?php esc_html_e( 'Clarifying Questions', 'stacksuite-sales-manager-for-woocommerce' ); ?></h4>
				<div class="aisales-support-clarify__item">
					<label><?php esc_html_e( 'Which payment gateway is selected?', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
					<input type="text" class="aisales-form-input" placeholder="<?php esc_attr_e( 'Stripe, PayPal, WooPayments...', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
				</div>
				<div class="aisales-support-clarify__item">
					<label><?php esc_html_e( 'Is caching enabled on the checkout page?', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
					<div class="aisales-support-chip-group">
						<button type="button" class="aisales-support-chip">Yes</button>
						<button type="button" class="aisales-support-chip is-selected">No</button>
						<button type="button" class="aisales-support-chip">Not Sure</button>
					</div>
				</div>
			</div>
		</div>

		<div class="aisales-support-step-panel" data-step="review">
			<div class="aisales-support-review">
				<div>
					<h4><?php esc_html_e( 'Ready to submit', 'stacksuite-sales-manager-for-woocommerce' ); ?></h4>
					<p><?php esc_html_e( 'We will notify you and support@stacksuite.dev when this ticket is submitted.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
				</div>
				<div class="aisales-support-review__summary">
					<span class="aisales-support-review__label"><?php esc_html_e( 'AI Summary', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					<p class="aisales-support-review__summary-text">...</p>
				</div>
				<div class="aisales-support-review__meta">
					<span class="aisales-status-badge aisales-status-badge--draft">
						<span class="dashicons dashicons-flag"></span>
						<?php esc_html_e( 'Draft', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</span>
					<span class="aisales-status-badge aisales-status-badge--active">
						<span class="dashicons dashicons-tag"></span>
						<?php esc_html_e( 'Bug', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</span>
					<span class="aisales-status-badge aisales-status-badge--warning">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Normal', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="aisales-support-review__box">
					<strong><?php esc_html_e( 'Checkout page spinner stuck', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
					<p><?php esc_html_e( 'Checkout does not complete since updating to 1.2.0. No console errors. Cache purged.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<div class="aisales-modal__footer">
		<span class="aisales-modal__info"><?php esc_html_e( 'AI will ask follow-ups if needed.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		<div class="aisales-modal__actions">
			<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-support-back">Back</button>
			<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-support-next">Next</button>
		</div>
	</div>
</div>
