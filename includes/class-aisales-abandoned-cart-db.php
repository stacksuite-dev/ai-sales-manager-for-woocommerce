<?php
/**
 * Abandoned Cart DB Helpers
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_DB {
	/**
	 * Get abandoned cart table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'aisales_abandoned_carts';
	}

	/**
	 * Create database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = self::get_table_name();
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			cart_token varchar(64) NOT NULL,
			restore_key varchar(64) NOT NULL,
			user_id bigint(20) unsigned NULL,
			email varchar(190) NULL,
			phone varchar(50) NULL,
			cart_items longtext NULL,
			currency varchar(10) NULL,
			subtotal decimal(18,2) NULL,
			total decimal(18,2) NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			last_activity_at datetime NULL,
			abandoned_at datetime NULL,
			recovered_at datetime NULL,
			order_id bigint(20) unsigned NULL,
			last_email_step tinyint unsigned NOT NULL DEFAULT 0,
			last_email_sent_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY cart_token (cart_token),
			KEY status (status),
			KEY email (email),
			KEY abandoned_at (abandoned_at)
		) {$charset};";

		dbDelta( $sql );
	}

	/**
	 * Ensure tables exist.
	 */
	public static function maybe_create_tables() {
		if ( self::table_exists() ) {
			return;
		}

		self::create_tables();
	}

	/**
	 * Check if abandoned cart table exists.
	 *
	 * @return bool
	 */
	private static function table_exists() {
		$cache_key = 'aisales_cart_table_exists';
		$cached    = wp_cache_get( $cache_key, 'aisales_carts' );
		if ( false !== $cached ) {
			return (bool) $cached;
		}

		global $wpdb;
		$table_name = self::get_table_name();

		$found  = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);
		$exists = $found === $table_name;

		wp_cache_set( $cache_key, $exists ? 1 : 0, 'aisales_carts', 300 );

		return $exists;
	}
}
