<?php
/**
 * AI Agent Chat Page
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chat Page class
 */
class WooAI_Chat_Page {

	/**
	 * Single instance
	 *
	 * @var WooAI_Chat_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WooAI_Chat_Page
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
	 * Add submenu page under WooAI Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'woo-ai-manager',
			__( 'AI Agent', 'woo-ai-sales-manager' ),
			__( 'AI Agent', 'woo-ai-sales-manager' ),
			'manage_woocommerce',
			'woo-ai-agent',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'wooai-manager_page_woo-ai-agent' !== $hook ) {
			return;
		}

		// Use file modification time for versioning in dev mode.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( WOOAI_PLUGIN_DIR . 'assets/css/chat.css' )
			: WOOAI_VERSION;

		// Always enqueue chat styles (even for "not connected" state).
		wp_enqueue_style(
			'wooai-chat',
			WOOAI_PLUGIN_URL . 'assets/css/chat.css',
			array( 'wooai-admin' ),
			$css_version
		);

		// Check if connected - only load JS if connected.
		$api_key = get_option( 'wooai_api_key' );
		if ( empty( $api_key ) ) {
			return;
		}

		// Use file modification time for JS versioning in dev mode.
		$js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( WOOAI_PLUGIN_DIR . 'assets/js/chat.js' )
			: WOOAI_VERSION;

		// Enqueue chat script.
		wp_enqueue_script(
			'wooai-chat',
			WOOAI_PLUGIN_URL . 'assets/js/chat.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Get products and categories for selectors.
		$products   = $this->get_products_for_selector();
		$categories = $this->get_categories_for_selector();

		// Get store context.
		$store_context = get_option( 'wooai_store_context', array() );

		// Check if user has visited chat before (for onboarding).
		$user_id      = get_current_user_id();
		$chat_visited = get_user_meta( $user_id, 'wooai_chat_visited', true );

		// Localize script.
		wp_localize_script(
			'wooai-chat',
			'wooaiChat',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'wooai_chat_nonce' ),
				'apiBaseUrl'   => apply_filters( 'wooai_api_url_client', WOOAI_API_URL_CLIENT ),
				'apiKey'       => $api_key,
				'balance'      => get_option( 'wooai_balance', 0 ),
				'products'     => $products,
				'categories'   => $categories,
				'storeContext' => $store_context,
				'chatVisited'  => ! empty( $chat_visited ),
				'i18n'         => array(
					// General.
					'sendMessage'         => __( 'Send', 'woo-ai-sales-manager' ),
					'typePlaceholder'     => __( 'Type your message...', 'woo-ai-sales-manager' ),
					'thinking'            => __( 'Thinking...', 'woo-ai-sales-manager' ),
					'apply'               => __( 'Apply', 'woo-ai-sales-manager' ),
					'discard'             => __( 'Discard', 'woo-ai-sales-manager' ),
					'applied'             => __( 'Applied', 'woo-ai-sales-manager' ),
					'discarded'           => __( 'Discarded', 'woo-ai-sales-manager' ),
					'errorOccurred'       => __( 'An error occurred. Please try again.', 'woo-ai-sales-manager' ),
					'insufficientBalance' => __( 'Insufficient balance. Please top up.', 'woo-ai-sales-manager' ),
					'connectionError'     => __( 'Connection error. Please check your internet.', 'woo-ai-sales-manager' ),
					'newChat'             => __( 'New Chat', 'woo-ai-sales-manager' ),
					'chatHistory'         => __( 'Chat History', 'woo-ai-sales-manager' ),
					'quickActions'        => __( 'Quick Actions', 'woo-ai-sales-manager' ),
					'tokensUsed'          => __( 'tokens used', 'woo-ai-sales-manager' ),
					'pendingChanges'      => __( 'Pending Changes', 'woo-ai-sales-manager' ),
					'acceptAll'           => __( 'Accept All', 'woo-ai-sales-manager' ),
					'discardAll'          => __( 'Discard All', 'woo-ai-sales-manager' ),

					// Products.
					'selectProduct'       => __( 'Select a product...', 'woo-ai-sales-manager' ),
					'noProducts'          => __( 'No products found', 'woo-ai-sales-manager' ),
					'productInfo'         => __( 'Product Info', 'woo-ai-sales-manager' ),
					'editProduct'         => __( 'Edit Product', 'woo-ai-sales-manager' ),
					'viewProduct'         => __( 'View Product', 'woo-ai-sales-manager' ),
					'improveTitle'        => __( 'Improve Title', 'woo-ai-sales-manager' ),
					'improveDescription'  => __( 'Improve Description', 'woo-ai-sales-manager' ),
					'seoOptimize'         => __( 'SEO Optimize', 'woo-ai-sales-manager' ),
					'suggestTags'         => __( 'Suggest Tags', 'woo-ai-sales-manager' ),
					'suggestCategories'   => __( 'Suggest Categories', 'woo-ai-sales-manager' ),
					'generateContent'     => __( 'Generate Content', 'woo-ai-sales-manager' ),

					// Categories.
					'selectCategory'      => __( 'Select a category...', 'woo-ai-sales-manager' ),
					'noCategories'        => __( 'No categories found', 'woo-ai-sales-manager' ),
					'categoryInfo'        => __( 'Category Info', 'woo-ai-sales-manager' ),
					'editCategory'        => __( 'Edit Category', 'woo-ai-sales-manager' ),
					'viewCategory'        => __( 'View Category', 'woo-ai-sales-manager' ),
					'improveCatName'      => __( 'Improve Name', 'woo-ai-sales-manager' ),
					'improveCatDesc'      => __( 'Improve Description', 'woo-ai-sales-manager' ),
					'catSeoOptimize'      => __( 'SEO Optimize', 'woo-ai-sales-manager' ),
					'generateCatContent'  => __( 'Generate Content', 'woo-ai-sales-manager' ),
					'subcategories'       => __( 'Subcategories', 'woo-ai-sales-manager' ),
					'parentCategory'      => __( 'Parent Category', 'woo-ai-sales-manager' ),
					'productCount'        => __( 'Products', 'woo-ai-sales-manager' ),

					// Store context.
					'storeContext'        => __( 'Store Context', 'woo-ai-sales-manager' ),
					'storeContextDesc'    => __( 'Help AI understand your store better', 'woo-ai-sales-manager' ),
					'saveContext'         => __( 'Save Context', 'woo-ai-sales-manager' ),
					'contextSaved'        => __( 'Store context saved successfully', 'woo-ai-sales-manager' ),
					'contextError'        => __( 'Failed to save store context', 'woo-ai-sales-manager' ),

					// Onboarding.
					'welcomeTitle'        => __( 'Welcome to AI Agent', 'woo-ai-sales-manager' ),
					'welcomeDesc'         => __( 'Select what you want to work on', 'woo-ai-sales-manager' ),
					'workOnProducts'      => __( 'Work on Products', 'woo-ai-sales-manager' ),
					'workOnCategories'    => __( 'Work on Categories', 'woo-ai-sales-manager' ),
				),
			)
		);
	}

	/**
	 * Get products for the selector dropdown
	 *
	 * @param int $limit Max number of products to return.
	 * @return array
	 */
	private function get_products_for_selector( $limit = 100 ) {
		$products = array();

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
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
					'id'                => $product->get_id(),
					'title'             => $product->get_name(),
					'description'       => $product->get_description(),
					'short_description' => $product->get_short_description(),
					'price'             => $product->get_price(),
					'regular_price'     => $product->get_regular_price(),
					'sale_price'        => $product->get_sale_price(),
					'sku'               => $product->get_sku(),
					'stock_status'      => $product->get_stock_status(),
					'stock_quantity'    => $product->get_stock_quantity(),
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
	 * Get categories for the selector dropdown
	 *
	 * @param int $limit Max number of categories to return.
	 * @return array
	 */
	private function get_categories_for_selector( $limit = 100 ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => $limit,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		// Build hierarchy info for subcategory counts.
		$term_children = array();
		foreach ( $terms as $term ) {
			if ( $term->parent > 0 ) {
				$term_children[ $term->parent ][] = $term->term_id;
			}
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$subcategory_count = isset( $term_children[ $term->term_id ] ) ? count( $term_children[ $term->term_id ] ) : 0;
			$categories[]      = $this->build_category_data( $term, $subcategory_count );
		}

		return $categories;
	}

	/**
	 * Build category data array from a term object
	 *
	 * @param WP_Term  $term             The category term.
	 * @param int|null $subcategory_count Optional subcategory count (null to fetch).
	 * @return array
	 */
	private function build_category_data( $term, $subcategory_count = null ) {
		// Get parent name.
		$parent_name = '';
		if ( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, 'product_cat' );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent_name = $parent_term->name;
			}
		}

		// Get subcategories if count not provided.
		$subcategories = array();
		if ( is_null( $subcategory_count ) ) {
			$child_terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => $term->term_id,
					'fields'     => 'names',
				)
			);
			$subcategories     = is_array( $child_terms ) ? $child_terms : array();
			$subcategory_count = count( $subcategories );
		}

		// Get thumbnail.
		$thumbnail_id  = get_term_meta( $term->term_id, 'thumbnail_id', true );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '';

		// Get SEO meta.
		$seo_title        = $this->get_term_seo_meta( $term->term_id, 'title' );
		$meta_description = $this->get_term_seo_meta( $term->term_id, 'description' );

		$data = array(
			'id'               => $term->term_id,
			'name'             => $term->name,
			'slug'             => $term->slug,
			'description'      => $term->description,
			'parent_id'        => $term->parent,
			'parent_name'      => $parent_name,
			'product_count'    => $term->count,
			'subcategory_count' => $subcategory_count,
			'thumbnail_url'    => $thumbnail_url,
			'seo_title'        => $seo_title,
			'meta_description' => $meta_description,
			'edit_url'         => get_edit_term_link( $term->term_id, 'product_cat' ),
			'view_url'         => get_term_link( $term->term_id, 'product_cat' ),
		);

		// Include subcategories array when fetched.
		if ( ! empty( $subcategories ) ) {
			$data['subcategories'] = $subcategories;
		}

		return $data;
	}

	/**
	 * Get SEO meta for a term (compatible with Yoast and RankMath)
	 *
	 * @param int    $term_id The term ID.
	 * @param string $type    Either 'title' or 'description'.
	 * @return string
	 */
	private function get_term_seo_meta( $term_id, $type ) {
		$meta_keys = 'title' === $type
			? array( '_wooai_seo_title', '_yoast_wpseo_title', 'rank_math_title' )
			: array( '_wooai_seo_description', '_yoast_wpseo_metadesc', 'rank_math_description' );

		foreach ( $meta_keys as $key ) {
			$value = get_term_meta( $term_id, $key, true );
			if ( ! empty( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Render the chat page
	 */
	public function render_page() {
		$api_key = get_option( 'wooai_api_key' );

		if ( empty( $api_key ) ) {
			$this->render_not_connected();
			return;
		}

		// Fetch fresh balance from API.
		$balance = $this->fetch_fresh_balance();

		// Get pre-selected entity from URL.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$entity_type = isset( $_GET['entity_type'] ) ? sanitize_text_field( wp_unslash( $_GET['entity_type'] ) ) : 'product';
		$product_id  = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		$category_id = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$product_data  = null;
		$category_data = null;

		// Load pre-selected entity data.
		if ( $category_id > 0 ) {
			$entity_type   = 'category';
			$category_data = $this->get_category_data( $category_id );
		} elseif ( $product_id > 0 ) {
			$product_data = $this->get_product_data( $product_id );
		}

		include WOOAI_PLUGIN_DIR . 'templates/admin-chat-page.php';
	}

	/**
	 * Get category data by ID
	 *
	 * @param int $category_id The category term ID.
	 * @return array|null
	 */
	private function get_category_data( $category_id ) {
		$term = get_term( $category_id, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		return $this->build_category_data( $term );
	}

	/**
	 * Get product data by ID
	 *
	 * @param int $product_id The product post ID.
	 * @return array|null
	 */
	private function get_product_data( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return null;
		}

		return array(
			'id'                => $product->get_id(),
			'title'             => $product->get_name(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'price'             => $product->get_price(),
			'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
			'image_url'         => wp_get_attachment_url( $product->get_image_id() ),
			'status'            => $product->get_status(),
			'edit_url'          => get_edit_post_link( $product->get_id(), 'raw' ),
			'view_url'          => get_permalink( $product->get_id() ),
		);
	}

	/**
	 * Render not connected state
	 */
	private function render_not_connected() {
		?>
		<div class="wrap wooai-admin-wrap">
			<h1>
				<span class="dashicons dashicons-format-chat"></span>
				<?php esc_html_e( 'AI Agent', 'woo-ai-sales-manager' ); ?>
			</h1>

			<div class="wooai-connect-wrap">
				<div class="wooai-card wooai-card--centered">
					<div class="wooai-empty-state">
						<span class="dashicons dashicons-admin-network"></span>
						<h3><?php esc_html_e( 'Connect Your Account', 'woo-ai-sales-manager' ); ?></h3>
						<p><?php esc_html_e( 'Connect your WooAI account to start using the AI Agent.', 'woo-ai-sales-manager' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager' ) ); ?>" class="wooai-btn wooai-btn--primary">
							<?php esc_html_e( 'Go to Settings', 'woo-ai-sales-manager' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Fetch fresh balance from API and update local cache.
	 *
	 * @return int The current token balance.
	 */
	private function fetch_fresh_balance() {
		$api     = WooAI_API_Client::instance();
		$account = $api->get_account();

		if ( ! is_wp_error( $account ) && isset( $account['balance_tokens'] ) ) {
			$balance = (int) $account['balance_tokens'];
			update_option( 'wooai_balance', $balance );
			return $balance;
		}

		// Fallback to cached value if API call fails.
		return (int) get_option( 'wooai_balance', 0 );
	}
}
