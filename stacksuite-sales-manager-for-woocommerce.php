<?php
/**
 * Plugin Name: StackSuite Sales Manager for WooCommerce
 * Plugin URI: https://github.com/stacksuite-dev/woo-ai-sales-manager
 * Description: AI-powered product catalog management for WooCommerce. Generate content, suggest tags/categories, and create/improve product images using Google Gemini.
 * Version: 1.4.4
 * Author: StackSuite
 * Author URI: https://stacksuite.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stacksuite-sales-manager-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.2
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package AI_Sales_Manager_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook( __FILE__, 'aisales_activate' );
register_deactivation_hook( __FILE__, 'aisales_deactivate' );

// Plugin constants
define( 'AISALES_VERSION', '1.4.4' );
define( 'AISALES_PLUGIN_FILE', __FILE__ );
define( 'AISALES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AISALES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AISALES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Default API endpoint (can be overridden via wp-config.php or filter)
if ( ! defined( 'AISALES_API_URL' ) ) {
	define( 'AISALES_API_URL', 'https://woo-ai-worker.simplebuild.site' );
}

// Client-side API URL for browser requests (defaults to same as server URL)
// In Docker environments, server uses internal hostname but browser needs localhost
if ( ! defined( 'AISALES_API_URL_CLIENT' ) ) {
	define( 'AISALES_API_URL_CLIENT', AISALES_API_URL );
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function aisales_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice when WooCommerce is not active.
 */
function aisales_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'StackSuite Sales Manager for WooCommerce', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
			<?php esc_html_e( 'requires WooCommerce to be installed and active.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Main plugin class
 */
final class AISales_Sales_Manager {

	/**
	 * Single instance
	 *
	 * @var AISales_Sales_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Sales_Manager
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-api-client.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-admin-settings.php';
		require_once AISALES_PLUGIN_DIR . 'includes/ajax/class-aisales-ajax-loader.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-mail-provider.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-email-manager.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-branding-extractor.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-db.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-settings.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-tracker.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-emails.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-scheduler.php';
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-restore.php';

		// Only load admin components
		if ( is_admin() ) {
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-product-metabox.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-category-metabox.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-chat-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-email-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-mail-provider-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-settings-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-report-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-support-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-support-ui.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-brand-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/widgets/class-aisales-widgets-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-batch-page.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-seo-checker-page.php';
		}

		// Frontend shortcodes (load on both admin and frontend for preview support)
		require_once AISALES_PLUGIN_DIR . 'includes/widgets/class-aisales-shortcodes.php';

		// Widget injector (auto-inject widgets into WooCommerce pages)
		require_once AISALES_PLUGIN_DIR . 'includes/widgets/class-aisales-widget-injector.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . AISALES_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Initialize components
		AISales_Admin_Settings::instance();
		AISales_Ajax_Loader::instance();
		AISales_Mail_Provider::instance();
		AISales_Email_Manager::instance();
		AISales_Abandoned_Cart_DB::maybe_create_tables();
		AISales_Abandoned_Cart_Tracker::instance();
		AISales_Abandoned_Cart_Scheduler::instance();
		AISales_Abandoned_Cart_Restore::instance();

		if ( is_admin() ) {
			AISales_Product_Metabox::instance();
			AISales_Category_Metabox::instance();
			AISales_Chat_Page::instance();
			AISales_Email_Page::instance();
			AISales_Mail_Provider_Page::instance();
			AISales_Abandoned_Cart_Settings_Page::instance();
			AISales_Abandoned_Cart_Report_Page::instance();
			AISales_Support_Page::instance();
			AISales_Support_UI::instance();
			AISales_Brand_Page::instance();
			AISales_Widgets_Page::instance();
			AISales_Batch_Page::instance();
			AISales_SEO_Checker_Page::instance();
		}

		add_action( 'admin_menu', array( $this, 'reorder_submenu_items' ), 999 );

		// Frontend shortcodes (always load for frontend and admin preview)
		AISales_Shortcodes::instance();

		// Widget auto-injection (frontend only)
		AISales_Widget_Injector::instance();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Check if we should load assets on this page.
		if ( ! $this->should_load_admin_assets( $hook ) ) {
			return;
		}

		// Use file modification time for versioning in dev mode.
		$shared_css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/shared-components.css' )
			: AISALES_VERSION;
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/admin.css' )
			: AISALES_VERSION;
		$js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/admin.js' )
			: AISALES_VERSION;
		$support_css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/support-page.css' )
			: AISALES_VERSION;
		$support_js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/support-modal.js' )
			: AISALES_VERSION;

		// Shared components CSS (store context button, balance indicator, etc.)
		wp_enqueue_style(
			'aisales-shared',
			AISALES_PLUGIN_URL . 'assets/css/shared-components.css',
			array(),
			$shared_css_version
		);

		wp_enqueue_style(
			'aisales-admin',
			AISALES_PLUGIN_URL . 'assets/css/admin.css',
			array( 'aisales-shared' ),
			$css_version
		);

		wp_enqueue_script(
			'aisales-admin',
			AISALES_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		wp_enqueue_style(
			'aisales-support-page',
			AISALES_PLUGIN_URL . 'assets/css/support-page.css',
			array( 'aisales-admin' ),
			$support_css_version
		);

		wp_enqueue_script(
			'aisales-support-modal',
			AISALES_PLUGIN_URL . 'assets/js/support-modal.js',
			array( 'jquery' ),
			$support_js_version,
			true
		);

		wp_localize_script(
			'aisales-admin',
			'aisalesAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'aisales_nonce' ),
				'chatNonce' => wp_create_nonce( 'aisales_chat_nonce' ),
				'strings'   => array(
					'error'        => __( 'An error occurred. Please try again.', 'stacksuite-sales-manager-for-woocommerce' ),
					'generating'   => __( 'Generating...', 'stacksuite-sales-manager-for-woocommerce' ),
					'applying'     => __( 'Applying...', 'stacksuite-sales-manager-for-woocommerce' ),
					'success'      => __( 'Success!', 'stacksuite-sales-manager-for-woocommerce' ),
					'lowBalance'   => __( 'Low balance. Please top up.', 'stacksuite-sales-manager-for-woocommerce' ),
					'confirmApply' => __( 'Apply this suggestion?', 'stacksuite-sales-manager-for-woocommerce' ),
					'clickToTopUp' => __( 'Click to add tokens', 'stacksuite-sales-manager-for-woocommerce' ),
				),
			)
		);

		add_thickbox();
	}

	/**
	 * Check if admin assets should be loaded on the current page
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool
	 */
	private function should_load_admin_assets( $hook ) {
		// Plugin pages.
		if ( in_array( $hook, array( 'toplevel_page_ai-sales-manager', 'ai-sales-manager_page_ai-sales-agent', 'ai-sales-manager_page_ai-sales-emails', 'ai-sales-manager_page_ai-sales-support', 'ai-sales-manager_page_ai-sales-brand', 'ai-sales-manager_page_ai-sales-widgets', 'ai-sales-manager_page_ai-sales-abandoned-carts', 'ai-sales-manager_page_ai-sales-abandoned-cart-settings', 'ai-sales-manager_page_ai-sales-bulk', 'ai-sales-manager_page_ai-sales-seo-checker' ), true ) ) {
			return true;
		}

		// Product edit pages.
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			$screen = get_current_screen();
			return $screen && 'product' === $screen->post_type;
		}

		// Category edit pages.
		if ( in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			return 'product_cat' === $taxonomy;
		}

		return false;
	}

	/**
	 * Ensure Support Center appears last in submenu
	 */
	public function reorder_submenu_items() {
		global $submenu;

		if ( empty( $submenu['ai-sales-manager'] ) ) {
			return;
		}

		$items = $submenu['ai-sales-manager'];
		$support_item = null;

		foreach ( $items as $index => $item ) {
			if ( isset( $item[2] ) && 'ai-sales-support' === $item[2] ) {
				$support_item = $item;
				unset( $items[ $index ] );
				break;
			}
		}

		if ( null === $support_item ) {
			return;
		}

		$items[] = $support_item;
		$submenu['ai-sales-manager'] = $items;
	}

	/**
	 * Add plugin action links
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=ai-sales-manager' ) . '">' . __( 'Settings', 'stacksuite-sales-manager-for-woocommerce' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get API client instance
	 *
	 * @return AISales_API_Client
	 */
	public function api() {
		return AISales_API_Client::instance();
	}
}

/**
 * Get main plugin instance
 *
 * @return AISales_Sales_Manager|null Returns null if WooCommerce is not active.
 */
function aisales() {
	if ( ! aisales_is_woocommerce_active() ) {
		return null;
	}
	return AISales_Sales_Manager::instance();
}

/**
 * Initialize plugin or show admin notice if WooCommerce is missing.
 */
function aisales_init() {
	if ( ! aisales_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'aisales_woocommerce_missing_notice' );
		return;
	}

	aisales();
}

/**
 * Plugin activation tasks.
 */
function aisales_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-abandoned-cart-db.php';
	AISales_Abandoned_Cart_DB::create_tables();
}

/**
 * Plugin deactivation tasks.
 */
function aisales_deactivate() {
	$timestamp = wp_next_scheduled( 'aisales_abandoned_cart_cron' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'aisales_abandoned_cart_cron' );
	}
}

// Initialize plugin after all plugins are loaded.
add_action( 'plugins_loaded', 'aisales_init' );
