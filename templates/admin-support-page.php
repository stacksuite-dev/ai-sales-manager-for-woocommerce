<?php
/**
 * Support Center Page
 *
 * Dummy UI for AI-powered support workflow.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap aisales-admin-wrap aisales-support-page">
	<h1 class="aisales-notices-anchor"></h1>

	<header class="aisales-support-page__header">
		<div class="aisales-support-page__header-left">
			<span class="aisales-support-page__title">
				<span class="dashicons dashicons-sos"></span>
				<?php esc_html_e( 'Support Center', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
			<span class="aisales-support-page__subtitle">
				<?php esc_html_e( 'Track support, bugs, and feature requests in one place.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-support-page__header-right">
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
			<button type="button" class="aisales-btn aisales-btn--pill" data-aisales-support-trigger>
				<span class="dashicons dashicons-plus-alt2"></span>
				<?php esc_html_e( 'New Ticket', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</header>

	<?php if ( empty( $aisales_api_key ) ) : ?>
		<div class="aisales-support-page__not-connected">
			<div class="aisales-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<h2><?php esc_html_e( 'Not Connected', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Connect your account to submit support tickets and track updates.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="aisales-btn aisales-btn--primary">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Go to Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<?php
		$aisales_status_map = array(
			'open'     => array( 'label' => __( 'Open', 'stacksuite-sales-manager-for-woocommerce' ), 'class' => 'aisales-status-badge--active', 'icon' => 'dashicons-flag' ),
			'pending'  => array( 'label' => __( 'Pending', 'stacksuite-sales-manager-for-woocommerce' ), 'class' => 'aisales-status-badge--warning', 'icon' => 'dashicons-update' ),
			'resolved' => array( 'label' => __( 'Resolved', 'stacksuite-sales-manager-for-woocommerce' ), 'class' => 'aisales-status-badge--success', 'icon' => 'dashicons-yes-alt' ),
			'closed'   => array( 'label' => __( 'Closed', 'stacksuite-sales-manager-for-woocommerce' ), 'class' => 'aisales-status-badge--none', 'icon' => 'dashicons-minus' ),
			'draft'    => array( 'label' => __( 'Draft', 'stacksuite-sales-manager-for-woocommerce' ), 'class' => 'aisales-status-badge--draft', 'icon' => 'dashicons-edit' ),
		);
		?>
		<div class="aisales-support-page__stats aisales-stats-grid aisales-stats-grid--4col">
			<div class="aisales-stat-card">
				<span class="aisales-stat-card__value"><?php echo esc_html( number_format_i18n( (int) $aisales_stats['open'] ) ); ?></span>
				<span class="aisales-stat-card__label"><?php esc_html_e( 'Open', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-stat-card">
				<span class="aisales-stat-card__value"><?php echo esc_html( number_format_i18n( (int) $aisales_stats['pending'] ) ); ?></span>
				<span class="aisales-stat-card__label"><?php esc_html_e( 'Pending', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-stat-card">
				<span class="aisales-stat-card__value"><?php echo esc_html( number_format_i18n( (int) $aisales_stats['resolved'] ) ); ?></span>
				<span class="aisales-stat-card__label"><?php esc_html_e( 'Resolved', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-stat-card">
				<span class="aisales-stat-card__value"><?php echo esc_html( $aisales_stats['average'] ); ?></span>
				<span class="aisales-stat-card__label"><?php esc_html_e( 'Avg Response', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
		</div>

		<div class="aisales-support-layout">
			<section class="aisales-support-panel aisales-support-panel--tickets">
				<div class="aisales-support-panel__header">
					<div>
						<h2><?php esc_html_e( 'My Tickets', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
						<p><?php esc_html_e( 'Track updates from the Support team.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>
					<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm">
						<?php esc_html_e( 'Filter', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>

				<?php if ( empty( $aisales_tickets ) ) : ?>
					<div class="aisales-empty-state--enhanced">
						<div class="aisales-empty-state__icon">
							<span class="dashicons dashicons-sos"></span>
						</div>
						<h3><?php esc_html_e( 'No tickets yet', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Tap the floating Support button to start a request.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $aisales_tickets as $aisales_ticket ) : ?>
						<?php
							$aisales_status_key = isset( $aisales_ticket['status'] ) ? $aisales_ticket['status'] : 'open';
							$aisales_status    = isset( $aisales_status_map[ $aisales_status_key ] ) ? $aisales_status_map[ $aisales_status_key ] : $aisales_status_map['open'];
							$aisales_category  = isset( $aisales_ticket['category'] ) ? $aisales_ticket['category'] : 'support';
							$aisales_priority  = isset( $aisales_ticket['priority'] ) ? $aisales_ticket['priority'] : 'normal';
							$aisales_updated_at = ! empty( $aisales_ticket['updated_at'] ) ? strtotime( $aisales_ticket['updated_at'] ) : 0;
							$aisales_time_label = $aisales_updated_at
								/* translators: %s: human-readable time difference */
								? sprintf( __( 'Last update %s', 'stacksuite-sales-manager-for-woocommerce' ), human_time_diff( $aisales_updated_at, current_time( 'timestamp' ) ) . ' ago' )
								: __( 'Last update -', 'stacksuite-sales-manager-for-woocommerce' );
							$aisales_category_label = ucfirst( $aisales_category );
							$aisales_priority_label = ucfirst( $aisales_priority );
						?>
						<div class="aisales-support-ticket">
							<div class="aisales-support-ticket__meta">
								<span class="aisales-support-ticket__id"><?php echo esc_html( $aisales_ticket['id'] ); ?></span>
								<span class="aisales-status-badge <?php echo esc_attr( $aisales_status['class'] ); ?>">
									<span class="dashicons <?php echo esc_attr( $aisales_status['icon'] ); ?>"></span>
									<?php echo esc_html( $aisales_status['label'] ); ?>
								</span>
							</div>
							<h3><?php echo esc_html( $aisales_ticket['title'] ); ?></h3>
							<p><?php echo esc_html( $aisales_ticket['preview'] ); ?></p>
							<div class="aisales-support-ticket__footer">
								<span><?php echo esc_html( $aisales_time_label ); ?></span>
								<div class="aisales-support-ticket__tags">
									<span class="aisales-support-ticket__pill"><?php echo esc_html( $aisales_category_label ); ?></span>
									<span class="aisales-support-ticket__pill aisales-support-ticket__pill--priority"><?php echo esc_html( $aisales_priority_label ); ?></span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		</div>
	<?php endif; ?>
</div>
