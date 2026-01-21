<?php
/**
 * Abandoned Cart Scheduler
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Scheduler {
	/**
	 * Single instance.
	 *
	 * @var AISales_Abandoned_Cart_Scheduler
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AISales_Abandoned_Cart_Scheduler
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
		add_action( 'aisales_abandoned_cart_cron', array( $this, 'process_abandoned_carts' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
	}

	/**
	 * Schedule recurring events.
	 */
	public function schedule_events() {
		if ( ! wp_next_scheduled( 'aisales_abandoned_cart_cron' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS * 10, 'hourly', 'aisales_abandoned_cart_cron' );
		}
	}

	/**
	 * Process abandoned carts.
	 */
	public function process_abandoned_carts() {
		global $wpdb;

		$settings   = AISales_Abandoned_Cart_Settings::get_settings();
		$table      = AISales_Abandoned_Cart_DB::get_table_name();
		$cutoff     = $this->get_cutoff_time( (int) $settings['abandon_minutes'], MINUTE_IN_SECONDS );
		$retention  = $this->get_cutoff_time( (int) $settings['retention_days'], DAY_IN_SECONDS );
		$now        = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = 'abandoned', abandoned_at = %s, updated_at = %s
				 WHERE status = 'active' AND last_activity_at IS NOT NULL AND last_activity_at < %s",
				$now,
				$now,
				$cutoff
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET status = 'expired', updated_at = %s
				 WHERE status IN ('abandoned','active') AND last_activity_at IS NOT NULL AND last_activity_at < %s",
				$now,
				$retention
			)
		);

		if ( empty( $settings['enable_emails'] ) ) {
			return;
		}

		$this->send_recovery_emails( $settings );
	}

	/**
	 * Send recovery emails based on step timing.
	 *
	 * @param array $settings Settings array.
	 */
	private function send_recovery_emails( $settings ) {
		global $wpdb;
		$table  = AISales_Abandoned_Cart_DB::get_table_name();
		$emails = new AISales_Abandoned_Cart_Emails();
		$now    = current_time( 'mysql' );

		foreach ( $settings['email_steps'] as $step => $hours ) {
			$cutoff = $this->get_cutoff_time( (int) $hours, HOUR_IN_SECONDS );

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table}
					WHERE status = 'abandoned'
					AND abandoned_at IS NOT NULL
					AND abandoned_at < %s
					AND (last_email_step < %d OR last_email_step IS NULL)
					AND email IS NOT NULL AND email <> ''",
					$cutoff,
					(int) $step
				),
				ARRAY_A
			);

			foreach ( $rows as $cart ) {
				if ( ! $emails->send_recovery_email( $cart, (int) $step ) ) {
					continue;
				}

				$wpdb->update(
					$table,
					array(
						'last_email_step'    => (int) $step,
						'last_email_sent_at' => $now,
						'updated_at'         => $now,
					),
					array( 'id' => $cart['id'] ),
					array( '%d', '%s', '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Get cutoff time string for interval.
	 *
	 * @param int $value Number of units.
	 * @param int $unit_seconds Unit size in seconds.
	 * @return string
	 */
	private function get_cutoff_time( $value, $unit_seconds ) {
		return gmdate( 'Y-m-d H:i:s', time() - ( $value * $unit_seconds ) );
	}
}
