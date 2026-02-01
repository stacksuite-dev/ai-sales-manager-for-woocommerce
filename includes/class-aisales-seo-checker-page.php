<?php
/**
 * SEO Checker Page
 *
 * Admin page for comprehensive store-wide SEO auditing.
 * Provides scoring, recommendations, and AI-powered fixes.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Checker Page class
 */
class AISales_SEO_Checker_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_SEO_Checker_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_SEO_Checker_Page
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
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 26 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_debug_actions_early' ) );
	}

	/**
	 * Handle debug actions early (before scripts enqueue)
	 *
	 * This runs on admin_init to clear data before wp_localize_script
	 * passes stale data to JavaScript. Redirects after clearing.
	 */
	public function handle_debug_actions_early() {
		// Only run on our page.
		if ( ! isset( $_GET['page'] ) || 'ai-sales-seo-checker' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$debug_action = isset( $_GET['debug'] ) ? sanitize_text_field( wp_unslash( $_GET['debug'] ) ) : '';

		if ( empty( $debug_action ) ) {
			return;
		}

		// Require manage_woocommerce capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Verify nonce for debug actions.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aisales_seo_debug' ) ) {
			return;
		}

		$message = '';

		switch ( $debug_action ) {
			case 'clear':
				$this->debug_clear_all_data();
				$message = 'cleared';
				break;

			case 'clear_meta':
				$this->debug_clear_item_meta();
				$message = 'meta_cleared';
				break;

			default:
				return;
		}

		// Redirect to remove debug param and show success message.
		$redirect_url = add_query_arg(
			array(
				'page'          => 'ai-sales-seo-checker',
				'debug_success' => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( wp_nonce_url( $redirect_url, 'aisales_seo_debug_success' ) );
		exit;
	}

	/**
	 * Add submenu page under AI Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'SEO Checker', 'ai-sales-manager-for-woocommerce' ),
			__( 'SEO Checker', 'ai-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-seo-checker',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-seo-checker' !== $hook ) {
			return;
		}

		// CSS version.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/seo-checker-page.css' )
			: AISALES_VERSION;

		// Enqueue page styles.
		wp_enqueue_style(
			'aisales-seo-checker-page',
			AISALES_PLUGIN_URL . 'assets/css/seo-checker-page.css',
			array( 'aisales-admin' ),
			$css_version
		);

		// Check if connected.
		$api_key = get_option( 'aisales_api_key' );
		if ( empty( $api_key ) ) {
			return;
		}

		// JS version.
		$js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/seo-checker-page.js' )
			: AISALES_VERSION;

		// Enqueue page script.
		wp_enqueue_script(
			'aisales-seo-checker-page',
			AISALES_PLUGIN_URL . 'assets/js/seo-checker-page.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Get last scan results.
		$scan_results = get_option( 'aisales_seo_scan_results', array() );

		// Get content counts for filters.
		$content_counts = $this->get_content_counts();

		// Localize script.
		wp_localize_script(
			'aisales-seo-checker-page',
			'aisalesSeoChecker',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'aisales_nonce' ),
				'apiKey'        => $api_key,
				'balance'       => get_option( 'aisales_balance', 0 ),
				'scanResults'   => $scan_results,
				'contentCounts' => $content_counts,
				'categories'    => $this->get_category_definitions(),
				'i18n'          => $this->get_i18n_strings(),
			)
		);
	}

	/**
	 * Get content counts for the store
	 *
	 * @return array Content type counts.
	 */
	private function get_content_counts() {
		$counts = array(
			'products'   => 0,
			'categories' => 0,
			'pages'      => 0,
			'posts'      => 0,
		);

		// Products count.
		$counts['products'] = (int) wp_count_posts( 'product' )->publish;

		// Categories count.
		$counts['categories'] = (int) wp_count_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		) );

		// Pages count.
		$counts['pages'] = (int) wp_count_posts( 'page' )->publish;

		// Posts count.
		$counts['posts'] = (int) wp_count_posts( 'post' )->publish;

		return $counts;
	}

	/**
	 * Get category definitions for JavaScript
	 *
	 * @return array Category definitions with labels and icons.
	 */
	private function get_category_definitions() {
		return array(
			'products'       => array(
				'label' => __( 'Products', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-cart',
			),
			'categories'     => array(
				'label' => __( 'Categories', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-category',
			),
			'pages'          => array(
				'label' => __( 'Pages', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-admin-page',
			),
			'posts'          => array(
				'label' => __( 'Blog Posts', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-admin-post',
			),
			'store_settings' => array(
				'label' => __( 'Store Settings', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-admin-settings',
			),
			'homepage'       => array(
				'label' => __( 'Homepage', 'ai-sales-manager-for-woocommerce' ),
				'icon'  => 'dashicons-admin-home',
			),
		);
	}

	/**
	 * Get i18n strings for JavaScript
	 *
	 * @return array Translated strings.
	 */
	private function get_i18n_strings() {
		return array(
			// General.
			'loading'           => __( 'Loading...', 'ai-sales-manager-for-woocommerce' ),
			'error'             => __( 'Error', 'ai-sales-manager-for-woocommerce' ),
			'success'           => __( 'Success', 'ai-sales-manager-for-woocommerce' ),
			'cancel'            => __( 'Cancel', 'ai-sales-manager-for-woocommerce' ),

			// Scan states.
			'scanning'          => __( 'Scanning...', 'ai-sales-manager-for-woocommerce' ),
			'scanComplete'      => __( 'Scan Complete!', 'ai-sales-manager-for-woocommerce' ),
			'scanError'         => __( 'Scan failed. Please try again.', 'ai-sales-manager-for-woocommerce' ),
			'noResults'         => __( 'No scan results yet. Click "Run Scan" to analyze your store.', 'ai-sales-manager-for-woocommerce' ),

			// Score labels.
			'excellent'         => __( 'Excellent', 'ai-sales-manager-for-woocommerce' ),
			'good'              => __( 'Good', 'ai-sales-manager-for-woocommerce' ),
			'needsWork'         => __( 'Needs Work', 'ai-sales-manager-for-woocommerce' ),
			'critical'          => __( 'Critical', 'ai-sales-manager-for-woocommerce' ),

			// Issue types.
			'criticalIssues'    => __( 'Critical', 'ai-sales-manager-for-woocommerce' ),
			'warnings'          => __( 'Warnings', 'ai-sales-manager-for-woocommerce' ),
			'passed'            => __( 'Passed', 'ai-sales-manager-for-woocommerce' ),

			// Categories.
			'products'          => __( 'Products', 'ai-sales-manager-for-woocommerce' ),
			'categories'        => __( 'Categories', 'ai-sales-manager-for-woocommerce' ),
			'pages'             => __( 'Pages', 'ai-sales-manager-for-woocommerce' ),
			'posts'             => __( 'Blog Posts', 'ai-sales-manager-for-woocommerce' ),
			'storeSettings'     => __( 'Store Settings', 'ai-sales-manager-for-woocommerce' ),
			'homepage'          => __( 'Homepage', 'ai-sales-manager-for-woocommerce' ),

			// Actions.
			'fix'               => __( 'Fix', 'ai-sales-manager-for-woocommerce' ),
			'fixAll'            => __( 'Fix All', 'ai-sales-manager-for-woocommerce' ),
			'dismiss'           => __( 'Dismiss', 'ai-sales-manager-for-woocommerce' ),
			'view'              => __( 'View', 'ai-sales-manager-for-woocommerce' ),
			'edit'              => __( 'Edit', 'ai-sales-manager-for-woocommerce' ),
			'fixing'            => __( 'Fixing...', 'ai-sales-manager-for-woocommerce' ),
			'fixed'             => __( 'Fixed!', 'ai-sales-manager-for-woocommerce' ),
			'allFixed'          => __( 'All issues fixed. Great job!', 'ai-sales-manager-for-woocommerce' ),
			'applyFix'          => __( 'Apply Fix', 'ai-sales-manager-for-woocommerce' ),
			'previewFix'        => __( 'Preview Fix', 'ai-sales-manager-for-woocommerce' ),

			// Fix modal.
			'fixModalTitle'     => __( 'Fix SEO Issue', 'ai-sales-manager-for-woocommerce' ),
			'bulkFixModalTitle' => __( 'Bulk Fix SEO Issues', 'ai-sales-manager-for-woocommerce' ),
			'estimatedCost'     => __( 'Estimated Cost', 'ai-sales-manager-for-woocommerce' ),
			'tokens'            => __( 'tokens', 'ai-sales-manager-for-woocommerce' ),
			'generating'        => __( 'Generating fix...', 'ai-sales-manager-for-woocommerce' ),
			'applying'          => __( 'Applying fix...', 'ai-sales-manager-for-woocommerce' ),

			// Checks.
			'titleLength'       => __( 'Title length', 'ai-sales-manager-for-woocommerce' ),
			'metaDescription'   => __( 'Meta description', 'ai-sales-manager-for-woocommerce' ),
			'imageAltTags'      => __( 'Image alt tags', 'ai-sales-manager-for-woocommerce' ),
			'contentLength'     => __( 'Content length', 'ai-sales-manager-for-woocommerce' ),
			'headingStructure'  => __( 'Heading structure', 'ai-sales-manager-for-woocommerce' ),
			'internalLinks'     => __( 'Internal links', 'ai-sales-manager-for-woocommerce' ),
			'focusKeyword'      => __( 'Focus keyword', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get score color class based on score value
	 *
	 * @param int $score The score value (0-100).
	 * @return string CSS class name.
	 */
	public static function get_score_class( $score ) {
		if ( $score >= 90 ) {
			return 'aisales-seo-score--excellent';
		} elseif ( $score >= 70 ) {
			return 'aisales-seo-score--good';
		} elseif ( $score >= 50 ) {
			return 'aisales-seo-score--warning';
		} else {
			return 'aisales-seo-score--critical';
		}
	}

	/**
	 * Get score label based on score value
	 *
	 * @param int $score The score value (0-100).
	 * @return string Label text.
	 */
	public static function get_score_label( $score ) {
		if ( $score >= 90 ) {
			return __( 'Excellent', 'ai-sales-manager-for-woocommerce' );
		} elseif ( $score >= 70 ) {
			return __( 'Good', 'ai-sales-manager-for-woocommerce' );
		} elseif ( $score >= 50 ) {
			return __( 'Needs Work', 'ai-sales-manager-for-woocommerce' );
		} else {
			return __( 'Critical', 'ai-sales-manager-for-woocommerce' );
		}
	}

	/**
	 * Handle debug actions
	 *
	 * Shows success message after redirect from handle_debug_actions_early().
	 * Supports: ?debug=clear - Clears all saved SEO scan data
	 *           ?debug=clear_meta - Clears per-item meta only
	 *
	 * @return string|null Debug message to display.
	 */
	private function handle_debug_actions() {
		// Check for success message from redirect.
		if ( ! isset( $_GET['debug_success'] ) ) {
			return null;
		}

		// Verify nonce for debug success display.
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aisales_seo_debug_success' ) ) {
			return null;
		}

		$debug_success = sanitize_text_field( wp_unslash( $_GET['debug_success'] ) );

		if ( ! empty( $debug_success ) ) {
			switch ( $debug_success ) {
				case 'cleared':
					return __( 'All SEO scan data has been cleared (option + post/term meta).', 'ai-sales-manager-for-woocommerce' );

				case 'meta_cleared':
					return __( 'Per-item SEO meta has been cleared. Main scan results preserved.', 'ai-sales-manager-for-woocommerce' );
			}
		}

		return null;
	}

	/**
	 * Clear all SEO scan data
	 *
	 * @return string Success message.
	 */
	private function debug_clear_all_data() {
		global $wpdb;

		// Delete main scan results option.
		delete_option( 'aisales_seo_scan_results' );

		// Delete per-item SEO meta from posts (products, pages, posts).
		$aisales_seo_meta_keys = array( '_aisales_seo_score', '_aisales_seo_issues', '_aisales_seo_last_check' );
		foreach ( $aisales_seo_meta_keys as $aisales_meta_key ) {
			delete_metadata( 'post', 0, $aisales_meta_key, '', true );
		}

		// Delete per-item SEO meta from terms (categories).
		foreach ( $aisales_seo_meta_keys as $aisales_meta_key ) {
			delete_metadata( 'term', 0, $aisales_meta_key, '', true );
		}

		// Translators: %d is the number of items cleared.
		return sprintf(
			/* translators: Debug action result message */
			__( 'Debug: Cleared all SEO scan data (option + post/term meta).', 'ai-sales-manager-for-woocommerce' )
		);
	}

	/**
	 * Clear only per-item SEO meta (keep main results)
	 *
	 * @return string Success message.
	 */
	private function debug_clear_item_meta() {
		global $wpdb;

		// Delete per-item SEO meta from posts.
		$aisales_seo_meta_keys = array( '_aisales_seo_score', '_aisales_seo_issues', '_aisales_seo_last_check' );
		$posts_deleted         = 0;
		foreach ( $aisales_seo_meta_keys as $aisales_meta_key ) {
			delete_metadata( 'post', 0, $aisales_meta_key, '', true );
			++$posts_deleted;
		}

		// Delete per-item SEO meta from terms.
		$terms_deleted = 0;
		foreach ( $aisales_seo_meta_keys as $aisales_meta_key ) {
			delete_metadata( 'term', 0, $aisales_meta_key, '', true );
			++$terms_deleted;
		}

		return sprintf(
			/* translators: %1$d is posts meta count, %2$d is terms meta count */
			__( 'Debug: Cleared per-item SEO meta (%1$d post meta rows, %2$d term meta rows).', 'ai-sales-manager-for-woocommerce' ),
			$posts_deleted,
			$terms_deleted
		);
	}

	/**
	 * Render the page
	 */
	public function render_page() {
		// Handle debug actions.
		$aisales_debug_message = $this->handle_debug_actions();

		// Check if connected.
		$aisales_api_key = get_option( 'aisales_api_key' );
		$aisales_balance = get_option( 'aisales_balance', 0 );

		// Get last scan results.
		$aisales_scan_results   = get_option( 'aisales_seo_scan_results', array() );
		$aisales_has_results    = ! empty( $aisales_scan_results ) && isset( $aisales_scan_results['overall_score'] );
		$aisales_overall_score  = $aisales_has_results ? (int) $aisales_scan_results['overall_score'] : 0;
		$aisales_scores         = $aisales_has_results ? $aisales_scan_results['scores'] : array();
		$aisales_issues         = $aisales_has_results ? $aisales_scan_results['issues'] : array();
		$aisales_scan_date      = $aisales_has_results ? $aisales_scan_results['scan_date'] : '';
		$aisales_items_scanned  = $aisales_has_results ? (int) $aisales_scan_results['items_scanned'] : 0;
		$aisales_detailed_issues = $aisales_has_results && isset( $aisales_scan_results['detailed_issues'] ) ? $aisales_scan_results['detailed_issues'] : array();

		// Get content counts.
		$aisales_content_counts = $this->get_content_counts();

		// Include the template.
		include AISALES_PLUGIN_DIR . 'templates/admin-seo-checker-page.php';
	}
}
