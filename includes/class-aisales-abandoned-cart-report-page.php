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
		$stats = $this->get_stats();
		$rows  = $this->get_recent_carts();
		$settings_url = admin_url( 'admin.php?page=ai-sales-abandoned-cart-settings' );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Abandoned Carts', 'ai-sales-manager-for-woocommerce' ); ?></h1>
			<a href="<?php echo esc_url( $settings_url ); ?>" class="page-title-action">
				<?php esc_html_e( 'Settings', 'ai-sales-manager-for-woocommerce' ); ?>
			</a>
			<hr class="wp-header-end">
			<div class="card">
				<h2 class="title"><?php esc_html_e( 'Overview', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				<p><strong><?php esc_html_e( 'Abandoned:', 'ai-sales-manager-for-woocommerce' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['abandoned'] ) ); ?></p>
				<p><strong><?php esc_html_e( 'Recovered:', 'ai-sales-manager-for-woocommerce' ); ?></strong> <?php echo esc_html( number_format_i18n( $stats['recovered'] ) ); ?></p>
				<p><strong><?php esc_html_e( 'Recovery Rate:', 'ai-sales-manager-for-woocommerce' ); ?></strong> <?php echo esc_html( $stats['recovery_rate'] ); ?>%</p>
				<p><strong><?php esc_html_e( 'Recovered Revenue:', 'ai-sales-manager-for-woocommerce' ); ?></strong> <?php echo wp_kses_post( $stats['recovered_revenue'] ); ?></p>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'ai-sales-manager-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ai-sales-manager-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Total', 'ai-sales-manager-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Last Activity', 'ai-sales-manager-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows ) ) : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No carts found yet.', 'ai-sales-manager-for-woocommerce' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['email'] ? $row['email'] : '—' ); ?></td>
								<td><?php echo esc_html( ucfirst( $row['status'] ) ); ?></td>
								<td><?php echo wp_kses_post( $row['total'] ); ?></td>
								<td><?php echo esc_html( $row['last_activity_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Get stats.
	 *
	 * @return array
	 */
	private function get_stats() {
		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$abandoned = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'abandoned'" );
		$recovered = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'recovered'" );
		$revenue   = (float) $wpdb->get_var( "SELECT SUM(total) FROM {$table} WHERE status = 'recovered'" );

		$rate = $abandoned + $recovered > 0
			? round( ( $recovered / ( $abandoned + $recovered ) ) * 100, 2 )
			: 0;

		return array(
			'abandoned'         => $abandoned,
			'recovered'         => $recovered,
			'recovery_rate'     => $rate,
			'recovered_revenue' => wc_price( $revenue ),
		);
	}

	/**
	 * Get recent carts.
	 *
	 * @return array
	 */
	private function get_recent_carts() {
		global $wpdb;
		$table = AISales_Abandoned_Cart_DB::get_table_name();

		$rows = $wpdb->get_results(
			"SELECT email, status, total, currency, last_activity_at
			 FROM {$table}
			 ORDER BY last_activity_at DESC
			 LIMIT 25",
			ARRAY_A
		);

		foreach ( $rows as &$row ) {
			$row['total'] = wc_price( (float) $row['total'], array( 'currency' => $row['currency'] ) );
		}

		return $rows;
	}
}
