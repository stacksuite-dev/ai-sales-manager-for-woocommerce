<?php
/**
 * Bulk Enhancement Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bulk Enhancement Page class
 */
class AISales_Batch_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_Batch_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Batch_Page
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
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page under StackSuite Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'Manage Catalog', 'stacksuite-sales-manager-for-woocommerce' ),
			__( 'Manage Catalog', 'stacksuite-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-bulk',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-bulk' !== $hook ) {
			return;
		}

		// CSS version.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/batch.css' )
			: AISALES_VERSION;

		// Enqueue batch styles.
		wp_enqueue_style(
			'aisales-batch',
			AISALES_PLUGIN_URL . 'assets/css/batch.css',
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
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/batch.js' )
			: AISALES_VERSION;

		// Enqueue batch script.
		wp_enqueue_script(
			'aisales-batch',
			AISALES_PLUGIN_URL . 'assets/js/batch.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Get products for selector.
		$products = $this->get_products_for_batch();

		// Get categories for filtering.
		$categories = $this->get_categories_for_filter();

		// Get store context.
		$store_context = get_option( 'aisales_store_context', array() );

		// Localize script.
		wp_localize_script(
			'aisales-batch',
			'aisalesBatch',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'aisales_batch_nonce' ),
				'apiBaseUrl'   => apply_filters( 'aisales_api_url_client', AISALES_API_URL_CLIENT ),
				'apiKey'       => $api_key,
				'balance'      => get_option( 'aisales_balance', 0 ),
				'products'     => $products,
				'categories'   => $categories,
				'storeContext' => $store_context,
				'previewSize'  => 5,
				'batchSize'    => 10,
				'i18n'         => $this->get_i18n_strings(),
			)
		);
	}

	/**
	 * Get i18n strings for JavaScript
	 *
	 * @return array
	 */
	private function get_i18n_strings() {
		return array(
			// General.
			'loading'                => __( 'Loading...', 'stacksuite-sales-manager-for-woocommerce' ),
			'error'                  => __( 'Error', 'stacksuite-sales-manager-for-woocommerce' ),
			'success'                => __( 'Success', 'stacksuite-sales-manager-for-woocommerce' ),
			'cancel'                 => __( 'Cancel', 'stacksuite-sales-manager-for-woocommerce' ),
			'confirm'                => __( 'Confirm', 'stacksuite-sales-manager-for-woocommerce' ),

			// Steps.
			'stepSelectProducts'     => __( 'Select Products', 'stacksuite-sales-manager-for-woocommerce' ),
			'stepChooseEnhancements' => __( 'Choose Enhancements', 'stacksuite-sales-manager-for-woocommerce' ),
			'stepPreview'            => __( 'Preview & Refine', 'stacksuite-sales-manager-for-woocommerce' ),
			'stepProcess'            => __( 'Process All', 'stacksuite-sales-manager-for-woocommerce' ),
			'stepApply'              => __( 'Review & Apply', 'stacksuite-sales-manager-for-woocommerce' ),

			// Products.
			'selectAll'              => __( 'Select All', 'stacksuite-sales-manager-for-woocommerce' ),
			'deselectAll'            => __( 'Deselect All', 'stacksuite-sales-manager-for-woocommerce' ),
			'productsSelected'       => __( 'products selected', 'stacksuite-sales-manager-for-woocommerce' ),
			'noProductsFound'        => __( 'No products found', 'stacksuite-sales-manager-for-woocommerce' ),
			'filterByCategory'       => __( 'Filter by category', 'stacksuite-sales-manager-for-woocommerce' ),
			'filterByStatus'         => __( 'Filter by status', 'stacksuite-sales-manager-for-woocommerce' ),
			'searchProducts'         => __( 'Search products...', 'stacksuite-sales-manager-for-woocommerce' ),

			// Enhancements.
			'description'            => __( 'Product Description', 'stacksuite-sales-manager-for-woocommerce' ),
			'shortDescription'       => __( 'Short Description', 'stacksuite-sales-manager-for-woocommerce' ),
			'seoTitle'               => __( 'SEO Title', 'stacksuite-sales-manager-for-woocommerce' ),
			'seoDescription'         => __( 'SEO Meta Description', 'stacksuite-sales-manager-for-woocommerce' ),
			'tags'                   => __( 'Product Tags', 'stacksuite-sales-manager-for-woocommerce' ),
			'categories'             => __( 'Categories', 'stacksuite-sales-manager-for-woocommerce' ),
			'imageAlt'               => __( 'Image Alt Text', 'stacksuite-sales-manager-for-woocommerce' ),

			// Refinement options.
			'refineDirection'        => __( 'Adjust Direction', 'stacksuite-sales-manager-for-woocommerce' ),
			'lengthStructure'        => __( 'Length & Structure', 'stacksuite-sales-manager-for-woocommerce' ),
			'toneStyle'              => __( 'Tone & Style', 'stacksuite-sales-manager-for-woocommerce' ),
			'contentFocus'           => __( 'Content Focus', 'stacksuite-sales-manager-for-woocommerce' ),
			'seoSpecific'            => __( 'SEO Specific', 'stacksuite-sales-manager-for-woocommerce' ),
			'tagsCategories'         => __( 'Tags & Categories', 'stacksuite-sales-manager-for-woocommerce' ),
			'additionalComments'     => __( 'Additional Comments', 'stacksuite-sales-manager-for-woocommerce' ),
			'uploadReference'        => __( 'Upload Reference Files', 'stacksuite-sales-manager-for-woocommerce' ),

			// Preview.
			'generatingPreview'      => __( 'Generating preview...', 'stacksuite-sales-manager-for-woocommerce' ),
			'previewReady'           => __( 'Preview Ready', 'stacksuite-sales-manager-for-woocommerce' ),
			'howDoTheseLook'         => __( 'How do these look?', 'stacksuite-sales-manager-for-woocommerce' ),
			'looksGood'              => __( 'Looks good! Process all', 'stacksuite-sales-manager-for-woocommerce' ),
			'adjustDirection'        => __( 'Adjust direction', 'stacksuite-sales-manager-for-woocommerce' ),
			'regeneratePreview'      => __( 'Regenerate Preview', 'stacksuite-sales-manager-for-woocommerce' ),
			'cancelBatch'            => __( 'Cancel Batch', 'stacksuite-sales-manager-for-woocommerce' ),

			// Processing.
			'processing'             => __( 'Processing...', 'stacksuite-sales-manager-for-woocommerce' ),
			'processingProducts'     => __( 'Processing products', 'stacksuite-sales-manager-for-woocommerce' ),
			'pause'                  => __( 'Pause', 'stacksuite-sales-manager-for-woocommerce' ),
			'resume'                 => __( 'Resume', 'stacksuite-sales-manager-for-woocommerce' ),
			'complete'               => __( 'Complete', 'stacksuite-sales-manager-for-woocommerce' ),

			// Results.
			'reviewResults'          => __( 'Review Results', 'stacksuite-sales-manager-for-woocommerce' ),
			'applyAll'               => __( 'Apply All', 'stacksuite-sales-manager-for-woocommerce' ),
			'applySelected'          => __( 'Apply Selected', 'stacksuite-sales-manager-for-woocommerce' ),
			'current'                => __( 'Current', 'stacksuite-sales-manager-for-woocommerce' ),
			'suggested'              => __( 'Suggested', 'stacksuite-sales-manager-for-woocommerce' ),
			'applied'                => __( 'Applied', 'stacksuite-sales-manager-for-woocommerce' ),
			'skipped'                => __( 'Skipped', 'stacksuite-sales-manager-for-woocommerce' ),
			'failed'                 => __( 'Failed', 'stacksuite-sales-manager-for-woocommerce' ),

			// Tokens.
			'estimatedTokens'        => __( 'Estimated tokens', 'stacksuite-sales-manager-for-woocommerce' ),
			'tokensUsed'             => __( 'Tokens used', 'stacksuite-sales-manager-for-woocommerce' ),
			'yourBalance'            => __( 'Your balance', 'stacksuite-sales-manager-for-woocommerce' ),
			'insufficientBalance'    => __( 'Insufficient balance', 'stacksuite-sales-manager-for-woocommerce' ),

			// Errors.
			'errorOccurred'          => __( 'An error occurred. Please try again.', 'stacksuite-sales-manager-for-woocommerce' ),
			'connectionError'        => __( 'Connection error. Please check your internet.', 'stacksuite-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get products for batch selection
	 *
	 * @return array
	 */
	private function get_products_for_batch() {
		$products = array();

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 500, // Higher limit for batch operations.
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( ! $product ) {
					continue;
				}

				$products[] = array(
					'id'                => (string) $product->get_id(),
					'title'             => $product->get_name(),
					'description'       => $product->get_description(),
					'short_description' => $product->get_short_description(),
					'price'             => $product->get_price(),
					'sku'               => $product->get_sku(),
					'stock_status'      => $product->get_stock_status(),
					'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
					'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
					'image_url'         => wp_get_attachment_url( $product->get_image_id() ),
					'status'            => $product->get_status(),
					'edit_url'          => get_edit_post_link( $product->get_id(), 'raw' ),
					'view_url'          => get_permalink( $product->get_id() ),
				);
			}
			wp_reset_postdata();
		}

		return $products;
	}

	/**
	 * Get categories for filter dropdown
	 *
	 * @return array
	 */
	private function get_categories_for_filter() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$categories[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $categories;
	}

	/**
	 * Render the batch page
	 */
	public function render_page() {
		$api_key = get_option( 'aisales_api_key' );

		if ( empty( $api_key ) ) {
			$this->render_not_connected();
			return;
		}

		// Fetch fresh balance.
		$balance = $this->fetch_fresh_balance();

		// Get store context.
		$store_context = get_option( 'aisales_store_context', array() );

		include AISALES_PLUGIN_DIR . 'templates/admin-batch-page.php';
	}

	/**
	 * Render not connected state
	 */
	private function render_not_connected() {
		?>
		<div class="wrap aisales-admin-wrap">
			<h1>
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Manage Catalog', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</h1>

			<div class="aisales-connect-wrap">
				<div class="aisales-card aisales-card--centered">
					<div class="aisales-empty-state">
						<span class="dashicons dashicons-admin-network"></span>
						<h3><?php esc_html_e( 'Connect Your Account', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Connect your StackSuite Sales Manager account to start using Manage Catalog.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="aisales-btn aisales-btn--primary">
							<?php esc_html_e( 'Go to Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Fetch fresh balance from API
	 *
	 * @return int
	 */
	private function fetch_fresh_balance() {
		$api     = AISales_API_Client::instance();
		$account = $api->get_account();

		if ( ! is_wp_error( $account ) && isset( $account['balance_tokens'] ) ) {
			$balance = (int) $account['balance_tokens'];
			update_option( 'aisales_balance', $balance );
			return $balance;
		}

		return (int) get_option( 'aisales_balance', 0 );
	}
}
