<?php
/**
 * Billing Page Template
 *
 * Displays payment settings and purchase history.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables passed from parent:
 * - $aisales_api: AISales_API_Client instance
 * - $aisales_account: Account data array
 * - $aisales_balance: Current token balance
 */

// Get auto top-up settings.
$aisales_auto_topup = $aisales_api->get_auto_topup_settings();
$aisales_has_auto_topup_error = is_wp_error( $aisales_auto_topup );

if ( $aisales_has_auto_topup_error ) {
	$aisales_auto_topup = array(
		'enabled'           => false,
		'threshold'         => 1000,
		'productSlug'       => 'standard_plan',
		'hasPaymentMethod'  => false,
		'lastTopupAt'       => null,
	);
}

// Get payment method details.
$aisales_payment_method = $aisales_api->get_payment_method();
$aisales_has_payment_method = ! is_wp_error( $aisales_payment_method ) && ! empty( $aisales_payment_method['payment_method'] );
$aisales_card_details = $aisales_has_payment_method ? $aisales_payment_method['payment_method'] : null;

// Get purchases.
$aisales_purchases = $aisales_api->get_purchases( 10, 0 );
$aisales_has_purchases_error = is_wp_error( $aisales_purchases );
$aisales_purchase_list = ! $aisales_has_purchases_error && isset( $aisales_purchases['purchases'] ) ? $aisales_purchases['purchases'] : array();

// Get available plans for dropdown.
$aisales_plans = $aisales_api->get_plans();
$aisales_plan_list = ! is_wp_error( $aisales_plans ) && isset( $aisales_plans['plans'] ) ? $aisales_plans['plans'] : array();

// Threshold options.
$aisales_threshold_options = array(
	500   => __( '500 tokens', 'ai-sales-manager-for-woocommerce' ),
	1000  => __( '1,000 tokens', 'ai-sales-manager-for-woocommerce' ),
	2000  => __( '2,000 tokens', 'ai-sales-manager-for-woocommerce' ),
	5000  => __( '5,000 tokens', 'ai-sales-manager-for-woocommerce' ),
);

// Calculate cooldown status.
$aisales_cooldown_active = false;
$aisales_cooldown_minutes = 0;
if ( ! empty( $aisales_auto_topup['lastTopupAt'] ) ) {
	$aisales_last_topup = strtotime( $aisales_auto_topup['lastTopupAt'] );
	$aisales_cooldown_end = $aisales_last_topup + HOUR_IN_SECONDS;
	$aisales_now = time();
	if ( $aisales_now < $aisales_cooldown_end ) {
		$aisales_cooldown_active = true;
		$aisales_cooldown_minutes = ceil( ( $aisales_cooldown_end - $aisales_now ) / MINUTE_IN_SECONDS );
	}
}

// Calculate purchase summary.
$aisales_total_purchased = 0;
$aisales_total_spent = 0;
$aisales_auto_topup_count = 0;
$aisales_manual_count = 0;

if ( ! empty( $aisales_purchase_list ) ) {
	foreach ( $aisales_purchase_list as $aisales_purchase ) {
		$aisales_total_purchased += $aisales_purchase['amount_tokens'];
		$aisales_total_spent += $aisales_purchase['amount_usd'];
		if ( 'auto_topup' === $aisales_purchase['type'] ) {
			$aisales_auto_topup_count++;
		} else {
			$aisales_manual_count++;
		}
	}
}
?>

<div class="aisales-billing aisales-mt-5">

	<!-- Summary Stats Grid -->
	<div class="aisales-stats-grid aisales-stats-grid--4col aisales-mb-6">
		<div class="aisales-stat-card aisales-stat-card--balance">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-database"></span>
			</div>
			<span class="aisales-stat-card__value"><?php echo esc_html( number_format( $aisales_balance ) ); ?></span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Current Balance', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-stat-card aisales-stat-card--purchased">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-plus-alt"></span>
			</div>
			<span class="aisales-stat-card__value"><?php echo esc_html( number_format( $aisales_total_purchased ) ); ?></span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Tokens Purchased', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-stat-card aisales-stat-card--spent">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-money-alt"></span>
			</div>
			<span class="aisales-stat-card__value">$<?php echo esc_html( number_format( $aisales_total_spent / 100, 2 ) ); ?></span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Total Spent', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-stat-card aisales-stat-card--autotopup-status">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-update"></span>
			</div>
			<span class="aisales-stat-card__value">
				<?php if ( $aisales_auto_topup['enabled'] ) : ?>
					<?php esc_html_e( 'ON', 'ai-sales-manager-for-woocommerce' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'OFF', 'ai-sales-manager-for-woocommerce' ); ?>
				<?php endif; ?>
			</span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Auto Top-Up', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>
	</div>

	<!-- Recent Auto Top-Up Notice -->
	<?php if ( $aisales_cooldown_active ) : ?>
		<div class="aisales-alert aisales-alert--info aisales-mb-5">
			<span class="dashicons dashicons-clock"></span>
			<div class="aisales-alert__content">
				<?php
				/* translators: %d: minutes remaining */
				echo esc_html( sprintf( __( 'Auto top-up recently triggered. Next auto top-up available in %d minutes.', 'ai-sales-manager-for-woocommerce' ), $aisales_cooldown_minutes ) );
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Unified Billing & Payments Card -->
	<div class="aisales-card aisales-card--elevated aisales-mb-6">
		<div class="aisales-card__header">
			<div class="aisales-card__icon aisales-card__icon--purple">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
					<path d="M2 10H22" stroke="currentColor" stroke-width="1.5"/>
					<path d="M6 15H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</div>
			<h2><?php esc_html_e( 'Billing & Payments', 'ai-sales-manager-for-woocommerce' ); ?></h2>
		</div>

		<div class="aisales-card__body aisales-card__body--sectioned">

			<!-- Section: Payment Method -->
			<div class="aisales-billing-section">
				<div class="aisales-billing-section__label"><?php esc_html_e( 'Payment Method', 'ai-sales-manager-for-woocommerce' ); ?></div>

				<?php if ( $aisales_has_payment_method && $aisales_card_details ) : ?>
					<div class="aisales-payment-method">
						<div class="aisales-payment-method__card">
							<div class="aisales-payment-method__icon">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
									<path d="M2 10H22" stroke="currentColor" stroke-width="1.5"/>
									<path d="M6 15H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
								</svg>
							</div>
							<div class="aisales-payment-method__info">
								<span class="aisales-payment-method__brand"><?php echo esc_html( ucfirst( $aisales_card_details['brand'] ) ); ?></span>
								<span class="aisales-payment-method__number">&bull;&bull;&bull;&bull; <?php echo esc_html( $aisales_card_details['last4'] ); ?></span>
								<span class="aisales-payment-method__expiry">
									<?php
									/* translators: %1$d: expiry month, %2$d: expiry year */
									echo esc_html( sprintf( __( 'Exp %1$02d/%2$d', 'ai-sales-manager-for-woocommerce' ), $aisales_card_details['exp_month'], $aisales_card_details['exp_year'] % 100 ) );
									?>
								</span>
							</div>
						</div>
						<div class="aisales-payment-method__actions">
							<button type="button" id="aisales-change-card-btn" class="aisales-btn aisales-btn--secondary aisales-btn--sm">
								<?php esc_html_e( 'Change', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
							<button type="button" id="aisales-remove-card-btn" class="aisales-btn aisales-btn--ghost aisales-btn--sm">
								<?php esc_html_e( 'Remove', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
					</div>
				<?php else : ?>
					<div class="aisales-payment-method aisales-payment-method--empty">
						<div class="aisales-payment-method__empty-state">
							<svg class="aisales-payment-method__empty-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
								<path d="M2 10H22" stroke="currentColor" stroke-width="1.5"/>
								<path d="M6 15H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
							</svg>
							<span><?php esc_html_e( 'No payment method on file', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<button type="button" id="aisales-add-card-btn" class="aisales-btn aisales-btn--primary aisales-btn--sm">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Card', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<!-- Section: Auto Top-Up -->
			<div class="aisales-billing-section">
				<div class="aisales-billing-section__label"><?php esc_html_e( 'Auto Top-Up', 'ai-sales-manager-for-woocommerce' ); ?></div>

				<div class="aisales-autotopup">
					<!-- Toggle Row -->
					<div class="aisales-autotopup__toggle-row">
						<label class="aisales-toggle">
							<input type="checkbox" 
								   id="aisales-autotopup-enabled" 
								   <?php checked( $aisales_auto_topup['enabled'] ); ?>
								   <?php disabled( ! $aisales_has_payment_method ); ?>>
							<span class="aisales-toggle__slider"></span>
						</label>
						<div class="aisales-autotopup__toggle-label">
							<span class="aisales-autotopup__toggle-text">
								<?php esc_html_e( 'Enable automatic refills', 'ai-sales-manager-for-woocommerce' ); ?>
							</span>
							<?php if ( ! $aisales_has_payment_method ) : ?>
								<span class="aisales-autotopup__requires-card">
									<?php esc_html_e( 'Requires payment method', 'ai-sales-manager-for-woocommerce' ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Settings Row -->
					<div class="aisales-autotopup__settings <?php echo ( ! $aisales_auto_topup['enabled'] || ! $aisales_has_payment_method ) ? 'aisales-autotopup__settings--disabled' : ''; ?>" id="aisales-autotopup-options">
						<div class="aisales-autotopup__field">
							<label for="aisales-autotopup-threshold" class="aisales-autotopup__field-label">
								<?php esc_html_e( 'When balance falls below', 'ai-sales-manager-for-woocommerce' ); ?>
							</label>
							<select id="aisales-autotopup-threshold" class="aisales-form-select" <?php disabled( ! $aisales_auto_topup['enabled'] || ! $aisales_has_payment_method ); ?>>
								<?php foreach ( $aisales_threshold_options as $aisales_value => $aisales_label ) : ?>
									<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_auto_topup['threshold'], $aisales_value ); ?>>
										<?php echo esc_html( $aisales_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="aisales-autotopup__field">
							<label for="aisales-autotopup-product" class="aisales-autotopup__field-label">
								<?php esc_html_e( 'Add this package', 'ai-sales-manager-for-woocommerce' ); ?>
							</label>
							<select id="aisales-autotopup-product" class="aisales-form-select" <?php disabled( ! $aisales_auto_topup['enabled'] || ! $aisales_has_payment_method ); ?>>
								<?php foreach ( $aisales_plan_list as $aisales_plan) : ?>
									<option value="<?php echo esc_attr( $aisales_plan['id'] ); ?>" <?php selected( $aisales_auto_topup['productSlug'], $aisales_plan['id'] ); ?>>
										<?php echo esc_html( sprintf( '%s tokens - $%s', number_format( $aisales_plan['tokens'] ), number_format( $aisales_plan['price_usd'], 2 ) ) ); ?>
									</option>
								<?php endforeach; ?>
								<?php if ( empty( $aisales_plan_list ) ) : ?>
									<option value="standard_plan" selected>10,000 tokens - $9.00</option>
								<?php endif; ?>
							</select>
						</div>
					</div>

					<!-- Info Notice -->
					<div class="aisales-autotopup__notice">
						<span class="dashicons dashicons-info-outline"></span>
						<span><?php esc_html_e( 'Card charged automatically when balance is low. Maximum once per hour.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Section: Buy Tokens -->
			<div class="aisales-billing-section aisales-billing-section--last">
				<div class="aisales-billing-section__label"><?php esc_html_e( 'Purchase Tokens', 'ai-sales-manager-for-woocommerce' ); ?></div>

				<div class="aisales-buy-tokens">
					<p class="aisales-buy-tokens__description">
						<?php esc_html_e( 'Select a package and complete checkout to add tokens to your balance.', 'ai-sales-manager-for-woocommerce' ); ?>
					</p>
					<button type="button" id="aisales-billing-topup-btn" class="aisales-btn aisales-btn--primary">
						<span class="dashicons dashicons-cart"></span>
						<?php esc_html_e( 'Buy Tokens', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

		</div>
	</div>

	<!-- Purchase History Card -->
	<div class="aisales-card aisales-card--elevated">
		<div class="aisales-card__header">
			<div class="aisales-card__icon aisales-card__icon--blue">
				<span class="dashicons dashicons-list-view"></span>
			</div>
			<h2><?php esc_html_e( 'Purchase History', 'ai-sales-manager-for-woocommerce' ); ?></h2>
		</div>

		<?php if ( ! empty( $aisales_purchase_list ) ) : ?>
			<div class="aisales-table-wrapper">
				<table class="aisales-table-modern">
					<thead>
						<tr>
							<th class="aisales-table__col--date"><?php esc_html_e( 'Date', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th class="aisales-table__col--type"><?php esc_html_e( 'Type', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th class="aisales-table__col--tokens"><?php esc_html_e( 'Tokens', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th class="aisales-table__col--amount"><?php esc_html_e( 'Amount', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th class="aisales-table__col--status"><?php esc_html_e( 'Status', 'ai-sales-manager-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $aisales_purchase_list as $aisales_purchase ) : ?>
							<tr>
								<td class="aisales-table__col--date">
									<span class="aisales-table__date"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $aisales_purchase['created_at'] ) ) ); ?></span>
									<span class="aisales-table__time"><?php echo esc_html( wp_date( 'g:i A', strtotime( $aisales_purchase['created_at'] ) ) ); ?></span>
								</td>
								<td class="aisales-table__col--type">
									<?php if ( 'auto_topup' === $aisales_purchase['type'] ) : ?>
										<span class="aisales-purchase-badge aisales-purchase-badge--auto">
											<span class="dashicons dashicons-update"></span>
											<?php esc_html_e( 'Auto', 'ai-sales-manager-for-woocommerce' ); ?>
										</span>
									<?php else : ?>
										<span class="aisales-purchase-badge aisales-purchase-badge--manual">
											<span class="dashicons dashicons-cart"></span>
											<?php esc_html_e( 'Manual', 'ai-sales-manager-for-woocommerce' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="aisales-table__col--tokens">
									<span class="aisales-tokens-badge aisales-tokens-badge--positive">
										+<?php echo esc_html( number_format( $aisales_purchase['amount_tokens'] ) ); ?>
									</span>
								</td>
								<td class="aisales-table__col--amount">
									<span class="aisales-table__amount">$<?php echo esc_html( number_format( $aisales_purchase['amount_usd'] / 100, 2 ) ); ?></span>
								</td>
								<td class="aisales-table__col--status">
									<span class="aisales-status-badge aisales-status-badge--success">
										<span class="aisales-status-dot"></span>
										<?php esc_html_e( 'Completed', 'ai-sales-manager-for-woocommerce' ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="aisales-empty-state aisales-empty-state--enhanced">
				<div class="aisales-empty-state__icon">
					<span class="dashicons dashicons-cart"></span>
				</div>
				<h3><?php esc_html_e( 'No purchases yet', 'ai-sales-manager-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'Your purchase history will appear here after you buy tokens.', 'ai-sales-manager-for-woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

</div>
