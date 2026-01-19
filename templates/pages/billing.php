<?php
/**
 * Billing Page Template
 *
 * Displays auto top-up settings and purchase history.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variables passed from parent:
 * - $api: AISales_API_Client instance
 * - $account: Account data array
 * - $balance: Current token balance
 */

// Get auto top-up settings.
$auto_topup = $api->get_auto_topup_settings();
$has_auto_topup_error = is_wp_error( $auto_topup );

if ( $has_auto_topup_error ) {
	$auto_topup = array(
		'enabled'           => false,
		'threshold'         => 1000,
		'productSlug'       => 'standard_plan',
		'hasPaymentMethod'  => false,
		'lastTopupAt'       => null,
	);
}

// Get payment method details.
$payment_method = $api->get_payment_method();
$has_payment_method = ! is_wp_error( $payment_method ) && ! empty( $payment_method['payment_method'] );
$card_details = $has_payment_method ? $payment_method['payment_method'] : null;

// Get purchases.
$purchases = $api->get_purchases( 10, 0 );
$has_purchases_error = is_wp_error( $purchases );
$purchase_list = ! $has_purchases_error && isset( $purchases['purchases'] ) ? $purchases['purchases'] : array();

// Get available plans for dropdown.
$plans = $api->get_plans();
$plan_list = ! is_wp_error( $plans ) && isset( $plans['plans'] ) ? $plans['plans'] : array();

// Threshold options.
$threshold_options = array(
	500   => __( '500 tokens', 'ai-sales-manager-for-woocommerce' ),
	1000  => __( '1,000 tokens', 'ai-sales-manager-for-woocommerce' ),
	2000  => __( '2,000 tokens', 'ai-sales-manager-for-woocommerce' ),
	5000  => __( '5,000 tokens', 'ai-sales-manager-for-woocommerce' ),
);

// Calculate cooldown status.
$cooldown_active = false;
$cooldown_minutes = 0;
if ( ! empty( $auto_topup['lastTopupAt'] ) ) {
	$last_topup = strtotime( $auto_topup['lastTopupAt'] );
	$cooldown_end = $last_topup + HOUR_IN_SECONDS;
	$now = time();
	if ( $now < $cooldown_end ) {
		$cooldown_active = true;
		$cooldown_minutes = ceil( ( $cooldown_end - $now ) / MINUTE_IN_SECONDS );
	}
}
?>

<div class="aisales-billing aisales-mt-5">

	<!-- Stat Cards Row -->
	<div class="aisales-billing__stats">
		<div class="aisales-stat-card aisales-stat-card--balance">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-database"></span>
			</div>
			<span class="aisales-stat-card__value"><?php echo esc_html( number_format( $balance ) ); ?></span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Current Balance', 'ai-sales-manager-for-woocommerce' ); ?></span>
			<button type="button" id="aisales-billing-topup-btn" class="aisales-btn aisales-btn--primary aisales-btn--sm aisales-mt-3">
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'Add Tokens', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>

		<div class="aisales-stat-card aisales-stat-card--autotopup <?php echo $auto_topup['enabled'] ? 'aisales-stat-card--enabled' : 'aisales-stat-card--disabled'; ?>">
			<div class="aisales-stat-card__icon">
				<span class="dashicons dashicons-update"></span>
			</div>
			<span class="aisales-stat-card__value">
				<?php if ( $auto_topup['enabled'] ) : ?>
					<span class="aisales-status-dot aisales-status-dot--success"></span>
					<?php esc_html_e( 'ON', 'ai-sales-manager-for-woocommerce' ); ?>
				<?php else : ?>
					<span class="aisales-status-dot aisales-status-dot--muted"></span>
					<?php esc_html_e( 'OFF', 'ai-sales-manager-for-woocommerce' ); ?>
				<?php endif; ?>
			</span>
			<span class="aisales-stat-card__label"><?php esc_html_e( 'Auto Top-Up', 'ai-sales-manager-for-woocommerce' ); ?></span>
			<?php if ( $auto_topup['enabled'] ) : ?>
				<span class="aisales-stat-card__detail">
					<?php
					/* translators: %s: threshold amount */
					printf( esc_html__( 'When below %s tokens', 'ai-sales-manager-for-woocommerce' ), number_format( $auto_topup['threshold'] ) );
					?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<!-- Recent Auto Top-Up Notice -->
	<?php if ( $cooldown_active ) : ?>
		<div class="aisales-alert aisales-alert--info aisales-mb-5">
			<span class="dashicons dashicons-clock"></span>
			<div class="aisales-alert__content">
				<?php
				/* translators: %d: minutes remaining */
				printf(
					esc_html__( 'Auto top-up recently triggered. Next auto top-up available in %d minutes.', 'ai-sales-manager-for-woocommerce' ),
					$cooldown_minutes
				);
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Auto Top-Up Settings Card -->
	<div class="aisales-card aisales-card--elevated aisales-billing__settings">
		<div class="aisales-card__header">
			<div class="aisales-card__icon aisales-card__icon--purple">
				<span class="dashicons dashicons-update-alt"></span>
			</div>
			<h2><?php esc_html_e( 'Auto Top-Up Settings', 'ai-sales-manager-for-woocommerce' ); ?></h2>
		</div>

		<div class="aisales-card__body">
			<!-- Enable Toggle -->
			<div class="aisales-setting-row aisales-setting-row--toggle">
				<div class="aisales-setting-row__content">
					<label for="aisales-autotopup-enabled" class="aisales-setting-row__label">
						<?php esc_html_e( 'Enable Auto Top-Up', 'ai-sales-manager-for-woocommerce' ); ?>
					</label>
					<p class="aisales-setting-row__description">
						<?php esc_html_e( 'Automatically purchase tokens when your balance is low', 'ai-sales-manager-for-woocommerce' ); ?>
					</p>
				</div>
				<div class="aisales-setting-row__control">
					<label class="aisales-toggle">
						<input type="checkbox" 
							   id="aisales-autotopup-enabled" 
							   <?php checked( $auto_topup['enabled'] ); ?>
							   <?php disabled( ! $has_payment_method ); ?>>
						<span class="aisales-toggle__slider"></span>
					</label>
				</div>
			</div>

			<!-- Threshold and Package Selection -->
			<div class="aisales-setting-row aisales-setting-row--grid <?php echo ! $auto_topup['enabled'] ? 'aisales-setting-row--disabled' : ''; ?>" id="aisales-autotopup-options">
				<div class="aisales-form-group">
					<label for="aisales-autotopup-threshold" class="aisales-form-label">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Balance Threshold', 'ai-sales-manager-for-woocommerce' ); ?>
					</label>
					<select id="aisales-autotopup-threshold" class="aisales-form-select" <?php disabled( ! $auto_topup['enabled'] ); ?>>
						<?php foreach ( $threshold_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $auto_topup['threshold'], $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="aisales-form-hint"><?php esc_html_e( 'Top up when balance falls below this amount', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>

				<div class="aisales-form-group">
					<label for="aisales-autotopup-product" class="aisales-form-label">
						<span class="dashicons dashicons-cart"></span>
						<?php esc_html_e( 'Reload Package', 'ai-sales-manager-for-woocommerce' ); ?>
					</label>
					<select id="aisales-autotopup-product" class="aisales-form-select" <?php disabled( ! $auto_topup['enabled'] ); ?>>
						<?php foreach ( $plan_list as $plan ) : ?>
							<option value="<?php echo esc_attr( $plan['id'] ); ?>" <?php selected( $auto_topup['productSlug'], $plan['id'] ); ?>>
								<?php echo esc_html( sprintf( '%s ($%s)', number_format( $plan['tokens'] ) . ' tokens', number_format( $plan['price_usd'], 2 ) ) ); ?>
							</option>
						<?php endforeach; ?>
						<?php if ( empty( $plan_list ) ) : ?>
							<option value="standard_plan" selected>10,000 tokens ($9.00)</option>
						<?php endif; ?>
					</select>
					<span class="aisales-form-hint"><?php esc_html_e( 'Package to purchase automatically', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Payment Method Section -->
			<div class="aisales-setting-row aisales-setting-row--payment">
				<label class="aisales-form-label">
					<span class="dashicons dashicons-credit-card"></span>
					<?php esc_html_e( 'Payment Method', 'ai-sales-manager-for-woocommerce' ); ?>
				</label>

				<?php if ( $has_payment_method && $card_details ) : ?>
					<div class="aisales-payment-card">
						<div class="aisales-payment-card__info">
							<span class="aisales-payment-card__brand"><?php echo esc_html( ucfirst( $card_details['brand'] ) ); ?></span>
							<span class="aisales-payment-card__number">
								&bull;&bull;&bull;&bull; <?php echo esc_html( $card_details['last4'] ); ?>
							</span>
							<span class="aisales-payment-card__expiry">
								<?php
								/* translators: %1$d: expiry month, %2$d: expiry year */
								printf(
									esc_html__( 'Exp: %1$02d/%2$d', 'ai-sales-manager-for-woocommerce' ),
									$card_details['exp_month'],
									$card_details['exp_year'] % 100
								);
								?>
							</span>
						</div>
						<div class="aisales-payment-card__actions">
							<button type="button" id="aisales-change-card-btn" class="aisales-btn aisales-btn--secondary aisales-btn--sm">
								<?php esc_html_e( 'Change', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
							<button type="button" id="aisales-remove-card-btn" class="aisales-btn aisales-btn--danger-outline aisales-btn--sm">
								<?php esc_html_e( 'Remove', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
					</div>
				<?php else : ?>
					<div class="aisales-payment-empty">
						<div class="aisales-payment-empty__icon">
							<span class="dashicons dashicons-credit-card"></span>
						</div>
						<p class="aisales-payment-empty__text">
							<?php esc_html_e( 'Add a payment method to enable auto top-up', 'ai-sales-manager-for-woocommerce' ); ?>
						</p>
						<button type="button" id="aisales-add-card-btn" class="aisales-btn aisales-btn--primary">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Payment Method', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				<?php endif; ?>
			</div>

			<!-- Info Notice -->
			<div class="aisales-info-box">
				<span class="dashicons dashicons-info"></span>
				<p>
					<?php esc_html_e( 'Your card will be charged automatically when your balance falls below the threshold. Maximum one charge per hour to prevent excess billing.', 'ai-sales-manager-for-woocommerce' ); ?>
				</p>
			</div>
		</div>
	</div>

	<!-- Purchase History Card -->
	<div class="aisales-card aisales-card--elevated aisales-billing__history">
		<div class="aisales-card__header">
			<div class="aisales-card__icon aisales-card__icon--green">
				<span class="dashicons dashicons-list-view"></span>
			</div>
			<h2><?php esc_html_e( 'Purchase History', 'ai-sales-manager-for-woocommerce' ); ?></h2>
		</div>

		<?php if ( ! empty( $purchase_list ) ) : ?>
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
						<?php foreach ( $purchase_list as $purchase ) : ?>
							<tr>
								<td class="aisales-table__col--date">
									<?php echo esc_html( wp_date( 'M j, Y', strtotime( $purchase['created_at'] ) ) ); ?>
								</td>
								<td class="aisales-table__col--type">
									<?php if ( 'auto_topup' === $purchase['type'] ) : ?>
										<span class="aisales-purchase-type aisales-purchase-type--auto">
											<span class="dashicons dashicons-update"></span>
											<?php esc_html_e( 'Auto', 'ai-sales-manager-for-woocommerce' ); ?>
										</span>
									<?php else : ?>
										<span class="aisales-purchase-type aisales-purchase-type--manual">
											<span class="dashicons dashicons-cart"></span>
											<?php esc_html_e( 'Manual', 'ai-sales-manager-for-woocommerce' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td class="aisales-table__col--tokens">
									<span class="aisales-tokens-badge">
										+<?php echo esc_html( number_format( $purchase['amount_tokens'] ) ); ?>
									</span>
								</td>
								<td class="aisales-table__col--amount">
									$<?php echo esc_html( number_format( $purchase['amount_usd'] / 100, 2 ) ); ?>
								</td>
								<td class="aisales-table__col--status">
									<span class="aisales-status-badge aisales-status-badge--success">
										<span class="aisales-status-dot aisales-status-dot--success"></span>
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
