<?php
/**
 * Abandoned Cart Settings
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Settings {
	/**
	 * Option key for settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'aisales_abandoned_cart_settings';

	/**
	 * Get settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = self::get_defaults();
		$settings = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get_setting( $key ) {
		$settings = self::get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private static function get_defaults() {
		return array(
			'abandon_minutes' => 45,
			'retention_days'  => 30,
			'enable_emails'   => true,
			'email_steps'     => array(
				1 => 1,
				2 => 24,
				3 => 72,
			),
			'restore_redirect' => 'checkout',
		);
	}
}
