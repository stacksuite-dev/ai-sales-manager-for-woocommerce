<?php
/**
 * Abandoned Cart Report Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Report_Page {
	/**
	 * Single instance.
	 *
	 * @var AISales_Abandoned_Cart_Report_Page
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AISales_Abandoned_Cart_Report_Page
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 30 );
	}

	/**
	 * Add submenu page.
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'Abandoned Carts', 'ai-sales-manager-for-woocommerce' ),
			__( 'Abandoned Carts', 'ai-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-abandoned-carts',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render page.
	 */
	public function render_page() {
		AISales_Abandoned_Cart_DB::maybe_create_tables();
		$stats = $this->get_stats();
		$rows  = $this->get_recent_carts();
		?>
		<div class="wrap aisales-abandoned-carts-page">
			<!-- WordPress Admin Notices Anchor -->
			<h1 class="aisales-notices-anchor"></h1>

			<?php
			$aisales_saved_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( isset( $_GET['aisales_saved'] ) && wp_verify_nonce( $aisales_saved_nonce, 'aisales_settings_saved' ) ) :
			?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'ai-sales-manager-for-woocommerce' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="aisales-abandoned-carts-page__header">
				<h2 class="aisales-abandoned-carts-page__title"><?php esc_html_e( 'Abandoned Carts', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				<button type="button" class="aisales-btn aisales-btn--pill" id="aisales-abandoned-cart-settings-trigger">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>

			<div class="aisales-stats-grid aisales-stats-grid--4col aisales-abandoned-carts-page__stats">
				<div class="aisales-stat-card">
					<span class="aisales-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['abandoned'] ) ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Abandoned', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card">
					<span class="aisales-stat-card__value"><?php echo esc_html( number_format_i18n( $stats['recovered'] ) ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Recovered', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card">
					<span class="aisales-stat-card__value"><?php echo esc_html( $stats['recovery_rate'] ); ?>%</span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Recovery Rate', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card">
					<span class="aisales-stat-card__value"><?php echo wp_kses_post( $stats['recovered_revenue'] ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Recovered Revenue', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
			</div>

			<div class="aisales-abandoned-carts-page__table">
				<table class="wp-list-table widefat fixed striped aisales-table-modern">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Email', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Cart Contents', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Total', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Last Activity', 'ai-sales-manager-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'ai-sales-manager-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $rows ) ) : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No carts found yet.', 'ai-sales-manager-for-woocommerce' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $rows as $row ) : ?>
								<tr class="aisales-abandoned-cart-row" data-cart-id="<?php echo esc_attr( $row['id'] ); ?>">
									<td data-column="email">
										<div class="aisales-cart-email">
											<span><?php echo esc_html( $row['email'] ? $row['email'] : '—' ); ?></span>
											<?php if ( $row['user_id'] ) : ?>
												<span class="aisales-cart-meta"><?php esc_html_e( 'Customer', 'ai-sales-manager-for-woocommerce' ); ?></span>
											<?php endif; ?>
										</div>
									</td>
									<td data-column="status">
										<?php echo wp_kses_post( $this->format_status_badge( $row['status'], $row['order_id'] ) ); ?>
									</td>
									<td data-column="items">
										<?php echo wp_kses_post( $this->format_cart_items_summary( $row ) ); ?>
									</td>
									<td data-column="total">
										<span class="aisales-cart-total"><?php echo wp_kses_post( $row['total'] ); ?></span>
									</td>
									<td data-column="last-activity">
										<span class="aisales-cart-timestamp"><?php echo esc_html( $row['last_activity_at'] ); ?></span>
									</td>
									<td data-column="actions">
										<?php echo wp_kses_post( $this->format_actions( $row ) ); ?>
									</td>
								</tr>
								<?php $details = $this->format_cart_items_details( $row ); ?>
								<?php if ( $details ) : ?>
									<tr class="aisales-cart-details-row" data-cart-id="<?php echo esc_attr( $row['id'] ); ?>" style="display: none;">
										<td colspan="6">
											<?php echo wp_kses_post( $details ); ?>
										</td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<?php $this->render_settings_modal(); ?>
		<?php
	}

	/**
	 * Render settings modal.
	 */
	private function render_settings_modal() {
		$settings = AISales_Abandoned_Cart_Settings::get_settings();
		?>
		<div class="aisales-modal-overlay" id="aisales-abandoned-cart-settings-overlay"></div>
		<div class="aisales-modal aisales-abandoned-cart-settings-modal" id="aisales-abandoned-cart-settings-modal">
			<div class="aisales-modal__header">
				<h3 class="aisales-modal__title"><?php esc_html_e( 'Abandoned Cart Settings', 'ai-sales-manager-for-woocommerce' ); ?></h3>
				<button type="button" class="aisales-modal__close" id="aisales-abandoned-cart-settings-close">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<form method="post">
				<div class="aisales-modal__body">
					<?php wp_nonce_field( 'aisales_abandoned_cart_settings' ); ?>

					<div class="aisales-form-group">
						<label class="aisales-form-label" for="aisales-abandon-minutes">
							<span class="dashicons dashicons-clock"></span>
							<?php esc_html_e( 'Abandon After (minutes)', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input type="number" id="aisales-abandon-minutes" name="abandon_minutes" class="aisales-form-input" min="5" value="<?php echo esc_attr( $settings['abandon_minutes'] ); ?>">
						<span class="aisales-form-hint"><?php esc_html_e( 'Mark carts as abandoned after this many minutes of inactivity.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>

					<div class="aisales-form-group">
						<label class="aisales-form-label" for="aisales-retention-days">
							<span class="dashicons dashicons-calendar-alt"></span>
							<?php esc_html_e( 'Retention (days)', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input type="number" id="aisales-retention-days" name="retention_days" class="aisales-form-input" min="1" value="<?php echo esc_attr( $settings['retention_days'] ); ?>">
						<span class="aisales-form-hint"><?php esc_html_e( 'Expire carts after this many days.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>

					<div class="aisales-form-group">
						<label class="aisales-form-label">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Recovery Emails', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<label class="aisales-checkbox-label">
							<input type="checkbox" id="aisales-enable-emails" name="enable_emails" value="1" <?php checked( $settings['enable_emails'] ); ?>>
							<?php esc_html_e( 'Enable recovery emails', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
					</div>

					<div class="aisales-form-group">
						<label class="aisales-form-label">
							<span class="dashicons dashicons-backup"></span>
							<?php esc_html_e( 'Email Timing (hours after abandonment)', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<div class="aisales-email-timing-grid">
							<div class="aisales-email-timing-item">
								<label for="aisales-email-step-1"><?php esc_html_e( 'Email 1', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="number" id="aisales-email-step-1" name="email_step_1" class="aisales-form-input" min="1" value="<?php echo esc_attr( $settings['email_steps'][1] ); ?>">
							</div>
							<div class="aisales-email-timing-item">
								<label for="aisales-email-step-2"><?php esc_html_e( 'Email 2', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="number" id="aisales-email-step-2" name="email_step_2" class="aisales-form-input" min="1" value="<?php echo esc_attr( $settings['email_steps'][2] ); ?>">
							</div>
							<div class="aisales-email-timing-item">
								<label for="aisales-email-step-3"><?php esc_html_e( 'Email 3', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="number" id="aisales-email-step-3" name="email_step_3" class="aisales-form-input" min="1" value="<?php echo esc_attr( $settings['email_steps'][3] ); ?>">
							</div>
						</div>
					</div>

					<div class="aisales-form-group">
						<label class="aisales-form-label" for="aisales-restore-redirect">
							<span class="dashicons dashicons-migrate"></span>
							<?php esc_html_e( 'Restore Link Destination', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<select id="aisales-restore-redirect" name="restore_redirect" class="aisales-form-input">
							<option value="checkout" <?php selected( $settings['restore_redirect'], 'checkout' ); ?>><?php esc_html_e( 'Checkout', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="cart" <?php selected( $settings['restore_redirect'], 'cart' ); ?>><?php esc_html_e( 'Cart', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
						<span class="aisales-form-hint"><?php esc_html_e( 'Where customers land after clicking the restore link in emails.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
				<div class="aisales-modal__footer">
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-abandoned-cart-settings-cancel">
						<?php esc_html_e( 'Cancel', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="submit" name="aisales_abandoned_cart_settings_submit" class="aisales-btn aisales-btn--primary">
						<?php esc_html_e( 'Save Settings', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Get stats.
	 *
	 * @return array
	 */
	private function get_stats() {
		$cached = wp_cache_get( 'aisales_cart_stats', 'aisales_carts' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$abandoned = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'abandoned'", $table ) );
		$recovered = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'recovered'", $table ) );
		$revenue   = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total) FROM %i WHERE status = 'recovered'", $table ) );

		$rate = $abandoned + $recovered > 0
			? round( ( $recovered / ( $abandoned + $recovered ) ) * 100, 2 )
			: 0;

		$stats = array(
			'abandoned'         => $abandoned,
			'recovered'         => $recovered,
			'recovery_rate'     => $rate,
			'recovered_revenue' => wc_price( $revenue ),
		);

		wp_cache_set( 'aisales_cart_stats', $stats, 'aisales_carts', 300 );

		return $stats;
	}

	/**
	 * Get recent carts.
	 *
	 * @return array
	 */
	private function get_recent_carts() {
		$cached = wp_cache_get( 'aisales_cart_recent_25', 'aisales_carts' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT email, status, total, currency, last_activity_at,
				 id, cart_items, order_id, user_id
				 FROM %i
				 ORDER BY last_activity_at DESC
				 LIMIT 25",
				$table
			),
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$row['total'] = wc_price( (float) $row['total'], array( 'currency' => $row['currency'] ) );
		}

		wp_cache_set( 'aisales_cart_recent_25', $rows, 'aisales_carts', 60 );

		return $rows;
	}

	/**
	 * Format cart items for display.
	 *
	 * @param array $row Cart row.
	 * @return string
	 */
	private function format_cart_items_summary( $row ) {
		$items = $this->get_cart_items_from_row( $row );
		$cart_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;

		if ( empty( $items ) ) {
			return '<span class="aisales-text-muted">' . esc_html__( 'No items captured yet.', 'ai-sales-manager-for-woocommerce' ) . '</span>';
		}

		$summary  = '<div class="aisales-cart-summary">';
		$summary .= '<div class="aisales-cart-summary__meta">';
		/* translators: %d: number of items */
		$summary .= '<span class="aisales-cart-summary__count">' . esc_html( sprintf( _n( '%d item', '%d items', count( $items ), 'ai-sales-manager-for-woocommerce' ), count( $items ) ) ) . '</span>';
		$summary .= '</div>';
		$summary .= '<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm aisales-cart-toggle" data-cart-id="' . esc_attr( $cart_id ) . '">';
		$summary .= '<span class="dashicons dashicons-visibility"></span>';
		$summary .= '<span class="aisales-btn__label">' . esc_html__( 'View items', 'ai-sales-manager-for-woocommerce' ) . '</span>';
		$summary .= '</button>';
		$summary .= '</div>';

		return $summary;
	}

	/**
	 * Build detailed cart items drawer.
	 *
	 * @param array $row Cart row.
	 * @return string
	 */
	private function format_cart_items_details( $row ) {
		$items = $this->get_cart_items_from_row( $row );
		$cart_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;

		if ( empty( $items ) ) {
			return '';
		}

		$html  = '<div class="aisales-cart-details">';
		$html .= '<div class="aisales-cart-details__header">';
		$html .= '<span class="aisales-cart-details__title">' . esc_html__( 'Cart items', 'ai-sales-manager-for-woocommerce' ) . '</span>';
		$html .= '<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm aisales-cart-toggle" data-cart-id="' . esc_attr( $cart_id ) . '">';
		$html .= '<span class="dashicons dashicons-hidden"></span>';
		$html .= '<span class="aisales-btn__label">' . esc_html__( 'Hide', 'ai-sales-manager-for-woocommerce' ) . '</span>';
		$html .= '</button>';
		$html .= '</div>';
		$html .= '<ul class="aisales-cart-details__list">';
		foreach ( $items as $item ) {
			$html .= '<li class="aisales-cart-details__item">';
			$html .= '<span class="aisales-cart-item__name">' . esc_html( $item['name'] ) . '</span>';
			$html .= '<span class="aisales-cart-item__meta">' . esc_html( sprintf( 'x%d', $item['qty'] ) ) . ' · ' . wp_kses_post( $item['total'] ) . '</span>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Parse cart items from row.
	 *
	 * @param array $row Cart row.
	 * @return array
	 */
	private function get_cart_items_from_row( $row ) {
		$items = array();
		$cart_items = isset( $row['cart_items'] ) ? $row['cart_items'] : '';
		if ( function_exists( 'wp_json_decode' ) ) {
			$decoded = $cart_items ? wp_json_decode( $cart_items, true ) : array();
		} else {
			$decoded = $cart_items ? json_decode( $cart_items, true ) : array();
		}
		$currency = isset( $row['currency'] ) ? $row['currency'] : get_woocommerce_currency();

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $item ) {
				$name = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
				$qty  = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
				$price = isset( $item['price'] ) ? (float) $item['price'] : 0;
				if ( $name ) {
					$items[] = array(
						'name'  => $name,
						'qty'   => $qty,
						'total' => wc_price( $price * $qty, array( 'currency' => $currency ) ),
					);
				}
			}
		}

		return $items;
	}

	/**
	 * Format status badge.
	 *
	 * @param string $status Cart status.
	 * @param int    $order_id Related order ID.
	 * @return string
	 */
	private function format_status_badge( $status, $order_id ) {
		$label = ucfirst( $status );
		$style = 'neutral';

		switch ( $status ) {
			case 'order_created':
				$label = __( 'Order Created', 'ai-sales-manager-for-woocommerce' );
				$style = 'info';
				break;
			case 'abandoned':
				$label = __( 'Abandoned', 'ai-sales-manager-for-woocommerce' );
				$style = 'warning';
				break;
			case 'recovered':
				$label = __( 'Recovered', 'ai-sales-manager-for-woocommerce' );
				$style = 'success';
				break;
			case 'expired':
				$label = __( 'Expired', 'ai-sales-manager-for-woocommerce' );
				$style = 'muted';
				break;
			default:
				$label = ucfirst( $status );
				$style = 'neutral';
		}

		if ( $order_id && 'recovered' !== $status ) {
			$label = __( 'Order Created', 'ai-sales-manager-for-woocommerce' );
			$style = 'info';
		}

		return sprintf(
			'<span class="aisales-status-badge aisales-status-badge--%1$s">%2$s</span>',
			esc_attr( $style ),
			esc_html( $label )
		);
	}

	/**
	 * Format actions for a row.
	 *
	 * @param array $row Cart row.
	 * @return string
	 */
	private function format_actions( $row ) {
		$order_id = isset( $row['order_id'] ) ? absint( $row['order_id'] ) : 0;
		$has_order = false;
		$payment_url = '';
		$edit_url = '';

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$has_order = true;
				$payment_url = $order->get_checkout_payment_url();
				$edit_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
			}
		}

		if ( ! $has_order ) {
			return '<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--sm aisales-abandoned-cart-create-order" data-cart-id="' . esc_attr( $row['id'] ) . '">' .
				'<span class="aisales-btn__label">' . esc_html__( 'Create order', 'ai-sales-manager-for-woocommerce' ) . '</span>' .
			'</button>';
		}

		return '<div class="aisales-action-group" data-order-id="' . esc_attr( $order_id ) . '">' .
			'<button type="button" class="aisales-action-group__btn aisales-abandoned-cart-copy-link" data-payment-url="' . esc_url( $payment_url ) . '" data-tooltip="' . esc_attr__( 'Copy link', 'ai-sales-manager-for-woocommerce' ) . '">' .
				'<span class="dashicons dashicons-admin-links"></span>' .
			'</button>' .
			'<a class="aisales-action-group__btn" href="' . esc_url( $payment_url ) . '" target="_blank" rel="noopener noreferrer" data-tooltip="' . esc_attr__( 'Open payment', 'ai-sales-manager-for-woocommerce' ) . '">' .
				'<span class="dashicons dashicons-external"></span>' .
			'</a>' .
			'<a class="aisales-action-group__btn" href="' . esc_url( $edit_url ) . '" data-tooltip="' . esc_attr__( 'View order', 'ai-sales-manager-for-woocommerce' ) . '">' .
				'<span class="dashicons dashicons-visibility"></span>' .
			'</a>' .
		'</div>';
	}
}
