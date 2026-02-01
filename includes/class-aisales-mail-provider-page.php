<?php
/**
 * Mail Provider Settings Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mail Provider Page class
 */
class AISales_Mail_Provider_Page {

	/**
	 * Single instance.
	 *
	 * @var AISales_Mail_Provider_Page
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AISales_Mail_Provider_Page
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page under AI Sales Manager.
	 *
	 * Note: Menu item removed - Email Settings now accessed via Email Templates page.
	 * Keeping method for backward compatibility in case of direct URL access.
	 */
	public function add_submenu_page() {
		// Menu registration removed - settings integrated into Email Templates page.
		// The functionality is now available via the Settings tab in Email Templates.
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-email-delivery' !== $hook ) {
			return;
		}

		$css_path = AISALES_PLUGIN_DIR . 'assets/css/mail-provider-page.css';
		if ( file_exists( $css_path ) ) {
			$css_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG )
				? filemtime( $css_path )
				: AISALES_VERSION;

			wp_enqueue_style(
				'aisales-mail-provider',
				AISALES_PLUGIN_URL . 'assets/css/mail-provider-page.css',
				array( 'aisales-admin' ),
				$css_version
			);
		}

		$js_path = AISALES_PLUGIN_DIR . 'assets/js/mail-provider-page.js';
		if ( file_exists( $js_path ) ) {
			$js_version = ( defined( 'WP_DEBUG' ) && WP_DEBUG )
				? filemtime( $js_path )
				: AISALES_VERSION;

			wp_enqueue_script(
				'aisales-mail-provider',
				AISALES_PLUGIN_URL . 'assets/js/mail-provider-page.js',
				array( 'jquery' ),
				$js_version,
				true
			);
		}

		$settings = AISales_Mail_Provider::instance()->get_settings();

		wp_localize_script(
			'aisales-mail-provider',
			'aisalesMailProvider',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aisales_nonce' ),
				'settings' => $settings,
				'adminEmail' => get_option( 'admin_email' ),
				'i18n'     => $this->get_i18n_strings(),
			)
		);
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
		$aisales_settings = AISales_Mail_Provider::instance()->get_settings();
		include AISALES_PLUGIN_DIR . 'templates/admin-mail-provider-page.php';
	}

	/**
	 * Get i18n strings.
	 *
	 * @return array
	 */
	private function get_i18n_strings() {
		return array(
			'save'         => __( 'Save Settings', 'ai-sales-manager-for-woocommerce' ),
			'saved'        => __( 'Email delivery settings saved.', 'ai-sales-manager-for-woocommerce' ),
			'saveFailed'   => __( 'Failed to save settings.', 'ai-sales-manager-for-woocommerce' ),
			'invalidEmail' => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ),
			'testing'      => __( 'Sending test email...', 'ai-sales-manager-for-woocommerce' ),
			'testSent'     => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ),
			'testFailed'   => __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ),
		);
	}
}
