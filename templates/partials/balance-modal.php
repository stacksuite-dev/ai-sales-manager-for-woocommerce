<?php
/**
 * Balance Top-Up Modal
 *
 * Displays current balance and allows purchasing more tokens.
 * Included on both chat page and settings page.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get current balance - passed from parent template or fetch fresh
$aisales_current_balance = isset( $balance ) ? $balance : 0;
$aisales_is_low_balance  = $aisales_current_balance < 1000;

// Get auto top-up status
$aisales_api             = AISales_API_Client::instance();
$aisales_auto_topup      = $aisales_api->get_auto_topup_settings();
$aisales_auto_topup_on   = ! is_wp_error( $aisales_auto_topup ) && ! empty( $aisales_auto_topup['enabled'] );
$aisales_billing_url     = admin_url( 'admin.php?page=aisales-billing' );

// Plan data - single plan for now (fetched from API at runtime via JS)
// Default fallback shown before JS loads
$aisales_default_plan = array(
	'id'          => 'standard_plan',
	'name'        => 'Standard Plan',
	'tokens'      => 10000,
	'price_usd'   => 9.00,
	'description' => '10,000 AI tokens for content generation',
);
?>

<!-- Balance Modal Overlay -->
<div class="aisales-modal-overlay aisales-balance-modal-overlay" id="aisales-balance-modal-overlay"></div>

<!-- Balance Modal -->
<div class="aisales-modal aisales-balance-modal" id="aisales-balance-modal" role="dialog" aria-modal="true" aria-labelledby="aisales-balance-modal-title">
	<!-- Modal Header -->
	<div class="aisales-modal__header aisales-balance-modal__header">
		<h2 class="aisales-modal__title" id="aisales-balance-modal-title">
			<span class="dashicons dashicons-database"></span>
			<?php esc_html_e( 'Token Balance', 'ai-sales-manager-for-woocommerce' ); ?>
		</h2>
		<button type="button" class="aisales-modal__close" id="aisales-balance-modal-close" aria-label="<?php esc_attr_e( 'Close', 'ai-sales-manager-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<!-- Modal Body -->
	<div class="aisales-modal__body aisales-balance-modal__body">
		<!-- Current Balance Display -->
		<div class="aisales-balance-current <?php echo $aisales_is_low_balance ? 'aisales-balance-current--low' : ''; ?>">
			<div class="aisales-balance-current__label">
				<?php esc_html_e( 'Current Balance', 'ai-sales-manager-for-woocommerce' ); ?>
			</div>
			<div class="aisales-balance-current__amount">
				<span class="aisales-balance-current__value" id="aisales-balance-modal-value"><?php echo esc_html( number_format( $aisales_current_balance ) ); ?></span>
				<span class="aisales-balance-current__unit"><?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<?php if ( $aisales_is_low_balance ) : ?>
			<div class="aisales-balance-current__warning">
				<span class="dashicons dashicons-warning"></span>
				<?php esc_html_e( 'Low balance - top up to continue using AI features', 'ai-sales-manager-for-woocommerce' ); ?>
			</div>
			<?php endif; ?>
			<!-- Progress bar visual -->
			<div class="aisales-balance-progress">
				<div class="aisales-balance-progress__bar" id="aisales-balance-progress-bar" style="width: <?php echo esc_attr( min( 100, ( $aisales_current_balance / 10000 ) * 100 ) ); ?>%;"></div>
			</div>
			<div class="aisales-balance-progress__labels">
				<span>0</span>
				<span>10,000</span>
			</div>
		</div>

		<!-- Divider -->
		<div class="aisales-balance-modal__divider">
			<span><?php esc_html_e( 'Add More Tokens', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>

		<!-- Token Package -->
		<div class="aisales-package-grid" id="aisales-package-grid">
			<button type="button" class="aisales-package-card aisales-package-card--selected" data-plan-id="standard_plan">
				<div class="aisales-package-card__tokens">
					<span class="aisales-package-card__amount" id="aisales-plan-tokens">10,000</span>
					<span class="aisales-package-card__unit"><?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-package-card__price">
					<span class="aisales-package-card__currency">$</span>
					<span class="aisales-package-card__value" id="aisales-plan-price">9</span>
				</div>
				<div class="aisales-package-card__rate" id="aisales-plan-rate">
					<?php esc_html_e( '$0.90 per 1,000 tokens', 'ai-sales-manager-for-woocommerce' ); ?>
				</div>
			</button>
		</div>

		<!-- Usage Estimates -->
		<div class="aisales-usage-estimates" id="aisales-usage-estimates">
			<div class="aisales-usage-estimates__title">
				<?php esc_html_e( 'What you can do with 10,000 tokens:', 'ai-sales-manager-for-woocommerce' ); ?>
			</div>
			<div class="aisales-usage-estimates__grid">
				<div class="aisales-usage-estimate">
					<span class="dashicons dashicons-text"></span>
					<span class="aisales-usage-estimate__value" id="aisales-estimate-content">~40</span>
					<span class="aisales-usage-estimate__label"><?php esc_html_e( 'Content optimizations', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-usage-estimate">
					<span class="dashicons dashicons-tag"></span>
					<span class="aisales-usage-estimate__value" id="aisales-estimate-taxonomy">~100</span>
					<span class="aisales-usage-estimate__label"><?php esc_html_e( 'Tag/category updates', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-usage-estimate">
					<span class="dashicons dashicons-format-image"></span>
					<span class="aisales-usage-estimate__value" id="aisales-estimate-images">~8</span>
					<span class="aisales-usage-estimate__label"><?php esc_html_e( 'Image generations', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Auto Top-Up Status -->
		<div class="aisales-auto-topup-notice <?php echo $aisales_auto_topup_on ? 'aisales-auto-topup-notice--enabled' : 'aisales-auto-topup-notice--disabled'; ?>">
			<?php if ( $aisales_auto_topup_on ) : ?>
				<div class="aisales-auto-topup-notice__icon">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="aisales-auto-topup-notice__content">
					<span class="aisales-auto-topup-notice__title"><?php esc_html_e( 'Auto top-up is enabled', 'ai-sales-manager-for-woocommerce' ); ?></span>
					<span class="aisales-auto-topup-notice__desc">
						<?php
						printf(
							/* translators: %s: threshold tokens */
							esc_html__( 'When below %s tokens', 'ai-sales-manager-for-woocommerce' ),
							esc_html( number_format( $aisales_auto_topup['threshold'] ) )
						);
						?>
					</span>
				</div>
				<a href="<?php echo esc_url( $aisales_billing_url ); ?>" class="aisales-auto-topup-notice__link">
					<?php esc_html_e( 'Manage', 'ai-sales-manager-for-woocommerce' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			<?php else : ?>
				<div class="aisales-auto-topup-notice__icon">
					<span class="dashicons dashicons-update"></span>
				</div>
				<div class="aisales-auto-topup-notice__content">
					<span class="aisales-auto-topup-notice__title"><?php esc_html_e( 'Never run out of tokens', 'ai-sales-manager-for-woocommerce' ); ?></span>
					<span class="aisales-auto-topup-notice__desc"><?php esc_html_e( 'Enable auto top-up to keep your balance ready', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<a href="<?php echo esc_url( $aisales_billing_url ); ?>" class="aisales-btn aisales-btn--secondary aisales-btn--sm">
					<?php esc_html_e( 'Set Up', 'ai-sales-manager-for-woocommerce' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Modal Footer -->
	<div class="aisales-modal__footer aisales-balance-modal__footer">
		<div class="aisales-modal__info">
			<span class="dashicons dashicons-lock"></span>
			<?php esc_html_e( 'Secure checkout via Stripe', 'ai-sales-manager-for-woocommerce' ); ?>
		</div>
		<div class="aisales-modal__actions">
			<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--lg" id="aisales-purchase-btn">
				<span class="aisales-btn__text"><?php esc_html_e( 'Purchase Tokens', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<span class="dashicons dashicons-arrow-right-alt"></span>
			</button>
		</div>
	</div>

	<!-- Loading State -->
	<div class="aisales-balance-modal__loading" id="aisales-balance-modal-loading" style="display: none;">
		<div class="aisales-balance-modal__spinner">
			<span class="spinner is-active"></span>
		</div>
		<div class="aisales-balance-modal__loading-text">
			<?php esc_html_e( 'Preparing checkout...', 'ai-sales-manager-for-woocommerce' ); ?>
		</div>
	</div>
</div>
