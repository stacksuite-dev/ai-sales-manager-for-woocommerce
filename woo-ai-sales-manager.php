<?php
/**
 * Plugin Name: WooAI Sales Manager
 * Plugin URI: https://github.com/stacksuite-dev/woo-ai-sales-manager
 * Description: AI-powered product catalog management for WooCommerce. Generate content, suggest tags/categories, and create/improve product images using Google Gemini.
 * Version: 1.1.0
 * Author: StackSuite
 * Author URI: https://stacksuite.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-ai-sales-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'WOOAI_VERSION', '1.1.0' );
define( 'WOOAI_PLUGIN_FILE', __FILE__ );
define( 'WOOAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Default API endpoint (can be overridden via wp-config.php or filter)
if ( ! defined( 'WOOAI_API_URL' ) ) {
	define( 'WOOAI_API_URL', 'https://woo-ai-worker.simplebuild.site' );
}

// Client-side API URL for browser requests (defaults to same as server URL)
// In Docker environments, server uses internal hostname but browser needs localhost
if ( ! defined( 'WOOAI_API_URL_CLIENT' ) ) {
	define( 'WOOAI_API_URL_CLIENT', WOOAI_API_URL );
}

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function wooai_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Display admin notice when WooCommerce is not active.
 */
function wooai_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'WooAI Sales Manager', 'woo-ai-sales-manager' ); ?></strong>
			<?php esc_html_e( 'requires WooCommerce to be installed and active.', 'woo-ai-sales-manager' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Main plugin class
 */
final class WooAI_Sales_Manager {

	/**
	 * Single instance
	 *
	 * @var WooAI_Sales_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WooAI_Sales_Manager
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
		require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-api-client.php';
		require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-admin-settings.php';
		require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-ajax-handlers.php';

		// Only load admin components
		if ( is_admin() ) {
			require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-product-metabox.php';
			require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-category-metabox.php';
			require_once WOOAI_PLUGIN_DIR . 'includes/class-wooai-chat-page.php';
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . WOOAI_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Initialize components
		WooAI_Admin_Settings::instance();
		WooAI_Ajax_Handlers::instance();

		if ( is_admin() ) {
			WooAI_Product_Metabox::instance();
			WooAI_Category_Metabox::instance();
			WooAI_Chat_Page::instance();
		}
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'woo-ai-sales-manager',
			false,
			dirname( WOOAI_PLUGIN_BASENAME ) . '/languages'
		);
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
			? filemtime( WOOAI_PLUGIN_DIR . 'assets/css/shared-components.css' )
			: WOOAI_VERSION;
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( WOOAI_PLUGIN_DIR . 'assets/css/admin.css' )
			: WOOAI_VERSION;
		$js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( WOOAI_PLUGIN_DIR . 'assets/js/admin.js' )
			: WOOAI_VERSION;

		// Shared components CSS (store context button, balance indicator, etc.)
		wp_enqueue_style(
			'wooai-shared',
			WOOAI_PLUGIN_URL . 'assets/css/shared-components.css',
			array(),
			$shared_css_version
		);

		wp_enqueue_style(
			'wooai-admin',
			WOOAI_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wooai-shared' ),
			$css_version
		);

		wp_enqueue_script(
			'wooai-admin',
			WOOAI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		wp_localize_script(
			'wooai-admin',
			'wooaiAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wooai_nonce' ),
				'chatNonce' => wp_create_nonce( 'wooai_chat_nonce' ),
				'strings'   => array(
					'error'        => __( 'An error occurred. Please try again.', 'woo-ai-sales-manager' ),
					'generating'   => __( 'Generating...', 'woo-ai-sales-manager' ),
					'applying'     => __( 'Applying...', 'woo-ai-sales-manager' ),
					'success'      => __( 'Success!', 'woo-ai-sales-manager' ),
					'lowBalance'   => __( 'Low balance. Please top up.', 'woo-ai-sales-manager' ),
					'confirmApply' => __( 'Apply this suggestion?', 'woo-ai-sales-manager' ),
					'clickToTopUp' => __( 'Click to add tokens', 'woo-ai-sales-manager' ),
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
		if ( in_array( $hook, array( 'toplevel_page_woo-ai-manager', 'wooai-manager_page_woo-ai-agent' ), true ) ) {
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
	 * Add plugin action links
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=woo-ai-manager' ) . '">' . __( 'Settings', 'woo-ai-sales-manager' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get API client instance
	 *
	 * @return WooAI_API_Client
	 */
	public function api() {
		return WooAI_API_Client::instance();
	}
}

/**
 * Get main plugin instance
 *
 * @return WooAI_Sales_Manager|null Returns null if WooCommerce is not active.
 */
function wooai() {
	if ( ! wooai_is_woocommerce_active() ) {
		return null;
	}
	return WooAI_Sales_Manager::instance();
}

/**
 * Initialize plugin or show admin notice if WooCommerce is missing.
 */
function wooai_init() {
	if ( ! wooai_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'wooai_woocommerce_missing_notice' );
		return;
	}

	wooai();
}

// Initialize plugin after all plugins are loaded.
add_action( 'plugins_loaded', 'wooai_init' );
