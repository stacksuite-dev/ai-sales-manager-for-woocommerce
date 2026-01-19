<?php
/**
 * Plugin Name: AI Sales Manager for WooCommerce
 * Plugin URI: https://github.com/stacksuite-dev/woo-ai-sales-manager
 * Description: AI-powered product catalog management for WooCommerce. Generate content, suggest tags/categories, and create/improve product images using Google Gemini.
 * Version: 1.2.0
 * Author: StackSuite
 * Author URI: https://stacksuite.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-sales-manager-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package AI_Sales_Manager_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'AISALES_VERSION', '1.2.0' );
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
			<strong><?php esc_html_e( 'AI Sales Manager for WooCommerce', 'ai-sales-manager-for-woocommerce' ); ?></strong>
			<?php esc_html_e( 'requires WooCommerce to be installed and active.', 'ai-sales-manager-for-woocommerce' ); ?>
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
		require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-ajax-handlers.php';

		// Only load admin components
		if ( is_admin() ) {
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-product-metabox.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-category-metabox.php';
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-chat-page.php';
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . AISALES_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

		// Initialize components
		AISales_Admin_Settings::instance();
		AISales_Ajax_Handlers::instance();

		if ( is_admin() ) {
			AISales_Product_Metabox::instance();
			AISales_Category_Metabox::instance();
			AISales_Chat_Page::instance();
		}
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

		wp_localize_script(
			'aisales-admin',
			'aisalesAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'aisales_nonce' ),
				'chatNonce' => wp_create_nonce( 'aisales_chat_nonce' ),
				'strings'   => array(
					'error'        => __( 'An error occurred. Please try again.', 'ai-sales-manager-for-woocommerce' ),
					'generating'   => __( 'Generating...', 'ai-sales-manager-for-woocommerce' ),
					'applying'     => __( 'Applying...', 'ai-sales-manager-for-woocommerce' ),
					'success'      => __( 'Success!', 'ai-sales-manager-for-woocommerce' ),
					'lowBalance'   => __( 'Low balance. Please top up.', 'ai-sales-manager-for-woocommerce' ),
					'confirmApply' => __( 'Apply this suggestion?', 'ai-sales-manager-for-woocommerce' ),
					'clickToTopUp' => __( 'Click to add tokens', 'ai-sales-manager-for-woocommerce' ),
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
		if ( in_array( $hook, array( 'toplevel_page_ai-sales-manager', 'ai-sales-manager_page_ai-sales-agent' ), true ) ) {
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
			'<a href="' . admin_url( 'admin.php?page=ai-sales-manager' ) . '">' . __( 'Settings', 'ai-sales-manager-for-woocommerce' ) . '</a>',
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

// Initialize plugin after all plugins are loaded.
add_action( 'plugins_loaded', 'aisales_init' );
