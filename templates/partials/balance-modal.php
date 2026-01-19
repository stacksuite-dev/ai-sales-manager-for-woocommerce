<?php
/**
 * Balance Top-Up Modal
 *
 * Displays current balance and allows purchasing more tokens.
 * Included on both chat page and settings page.
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get current balance - passed from parent template or fetch fresh
$current_balance = isset( $balance ) ? $balance : 0;
$is_low_balance  = $current_balance < 1000;

// Plan data - single plan for now (fetched from API at runtime via JS)
// Default fallback shown before JS loads
$default_plan = array(
	'id'          => 'standard_plan',
	'name'        => 'Standard Plan',
	'tokens'      => 10000,
	'price_usd'   => 9.00,
	'description' => '10,000 AI tokens for content generation',
);
?>

<!-- Balance Modal Overlay -->
<div class="wooai-modal-overlay wooai-balance-modal-overlay" id="wooai-balance-modal-overlay"></div>

<!-- Balance Modal -->
<div class="wooai-modal wooai-balance-modal" id="wooai-balance-modal" role="dialog" aria-modal="true" aria-labelledby="wooai-balance-modal-title">
	<!-- Modal Header -->
	<div class="wooai-modal__header wooai-balance-modal__header">
		<h2 class="wooai-modal__title" id="wooai-balance-modal-title">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'Token Balance', 'woo-ai-sales-manager' ); ?>
		</h2>
		<button type="button" class="wooai-modal__close" id="wooai-balance-modal-close" aria-label="<?php esc_attr_e( 'Close', 'woo-ai-sales-manager' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<!-- Modal Body -->
	<div class="wooai-modal__body wooai-balance-modal__body">
		<!-- Current Balance Display -->
		<div class="wooai-balance-current <?php echo $is_low_balance ? 'wooai-balance-current--low' : ''; ?>">
			<div class="wooai-balance-current__label">
				<?php esc_html_e( 'Current Balance', 'woo-ai-sales-manager' ); ?>
			</div>
			<div class="wooai-balance-current__amount">
				<span class="wooai-balance-current__value" id="wooai-balance-modal-value"><?php echo esc_html( number_format( $current_balance ) ); ?></span>
				<span class="wooai-balance-current__unit"><?php esc_html_e( 'tokens', 'woo-ai-sales-manager' ); ?></span>
			</div>
			<?php if ( $is_low_balance ) : ?>
			<div class="wooai-balance-current__warning">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Low balance - top up to continue using AI features', 'woo-ai-sales-manager' ); ?>
			</div>
			<?php endif; ?>
			<!-- Progress bar visual -->
			<div class="wooai-balance-progress">
				<div class="wooai-balance-progress__bar" id="wooai-balance-progress-bar" style="width: <?php echo esc_attr( min( 100, ( $current_balance / 10000 ) * 100 ) ); ?>%;"></div>
			</div>
			<div class="wooai-balance-progress__labels">
				<span>0</span>
				<span>10,000</span>
			</div>
		</div>

		<!-- Divider -->
		<div class="wooai-balance-modal__divider">
			<span><?php esc_html_e( 'Add More Tokens', 'woo-ai-sales-manager' ); ?></span>
		</div>

		<!-- Token Package -->
		<div class="wooai-package-grid" id="wooai-package-grid">
			<button type="button" class="wooai-package-card wooai-package-card--selected" data-plan-id="standard_plan">
				<div class="wooai-package-card__tokens">
					<span class="wooai-package-card__amount" id="wooai-plan-tokens">10,000</span>
					<span class="wooai-package-card__unit"><?php esc_html_e( 'tokens', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-package-card__price">
					<span class="wooai-package-card__currency">$</span>
					<span class="wooai-package-card__value" id="wooai-plan-price">9</span>
				</div>
				<div class="wooai-package-card__rate" id="wooai-plan-rate">
					<?php esc_html_e( '$0.90 per 1,000 tokens', 'woo-ai-sales-manager' ); ?>
				</div>
			</button>
		</div>

		<!-- Usage Estimates -->
		<div class="wooai-usage-estimates" id="wooai-usage-estimates">
			<div class="wooai-usage-estimates__title">
				<?php esc_html_e( 'What you can do with 10,000 tokens:', 'woo-ai-sales-manager' ); ?>
			</div>
			<div class="wooai-usage-estimates__grid">
				<div class="wooai-usage-estimate">
					<span class="dashicons dashicons-text"></span>
					<span class="wooai-usage-estimate__value" id="wooai-estimate-content">~40</span>
					<span class="wooai-usage-estimate__label"><?php esc_html_e( 'Content optimizations', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-usage-estimate">
					<span class="dashicons dashicons-tag"></span>
					<span class="wooai-usage-estimate__value" id="wooai-estimate-taxonomy">~100</span>
					<span class="wooai-usage-estimate__label"><?php esc_html_e( 'Tag/category updates', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-usage-estimate">
					<span class="dashicons dashicons-format-image"></span>
					<span class="wooai-usage-estimate__value" id="wooai-estimate-images">~8</span>
					<span class="wooai-usage-estimate__label"><?php esc_html_e( 'Image generations', 'woo-ai-sales-manager' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Footer -->
	<div class="wooai-modal__footer wooai-balance-modal__footer">
		<div class="wooai-modal__info">
			<span class="dashicons dashicons-lock"></span>
			<?php esc_html_e( 'Secure checkout via Stripe', 'woo-ai-sales-manager' ); ?>
		</div>
		<div class="wooai-modal__actions">
			<button type="button" class="wooai-btn wooai-btn--primary wooai-btn--lg" id="wooai-purchase-btn">
				<span class="wooai-btn__text"><?php esc_html_e( 'Purchase Tokens', 'woo-ai-sales-manager' ); ?></span>
				<span class="dashicons dashicons-arrow-right-alt"></span>
			</button>
		</div>
	</div>

	<!-- Loading State -->
	<div class="wooai-balance-modal__loading" id="wooai-balance-modal-loading" style="display: none;">
		<div class="wooai-balance-modal__spinner">
			<span class="spinner is-active"></span>
		</div>
		<div class="wooai-balance-modal__loading-text">
			<?php esc_html_e( 'Preparing checkout...', 'woo-ai-sales-manager' ); ?>
		</div>
	</div>
</div>
