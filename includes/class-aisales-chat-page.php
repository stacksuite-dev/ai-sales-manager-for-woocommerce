<?php
/**
 * AI Agent Chat Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chat Page class
 */
class AISales_Chat_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_Chat_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Chat_Page
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

		// Invalidate product cache when products change.
		add_action( 'save_post_product', array( $this, 'clear_products_cache' ) );
		add_action( 'deleted_post', array( $this, 'clear_products_cache' ) );
		add_action( 'trashed_post', array( $this, 'clear_products_cache' ) );

		// Invalidate category cache when categories change.
		add_action( 'created_product_cat', array( $this, 'clear_categories_cache' ) );
		add_action( 'edited_product_cat', array( $this, 'clear_categories_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'clear_categories_cache' ) );
	}

	/**
	 * Clear products selector cache
	 */
	public function clear_products_cache() {
		delete_transient( 'aisales_products_selector' );
	}

	/**
	 * Clear categories selector cache
	 */
	public function clear_categories_cache() {
		delete_transient( 'aisales_categories_selector' );
	}

	/**
	 * Add submenu page under StackSuite Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'AI Agent', 'stacksuite-sales-manager-for-woocommerce' ),
			__( 'AI Agent', 'stacksuite-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-agent',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-agent' !== $hook ) {
			return;
		}

		// Use file modification time for versioning in dev mode.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/chat.css' )
			: AISALES_VERSION;

		// Always enqueue chat styles (even for "not connected" state).
		wp_enqueue_style(
			'aisales-chat',
			AISALES_PLUGIN_URL . 'assets/css/chat.css',
			array( 'aisales-admin' ),
			$css_version
		);

		// Check if connected - only load JS if connected.
		$api_key = get_option( 'aisales_api_key' );
		if ( empty( $api_key ) ) {
			return;
		}

		// Use file modification time for JS versioning in dev mode.
		$js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/chat.js' )
			: AISALES_VERSION;

		// Enqueue modular JS files in dependency order.
		// 1. Utils module (no dependencies beyond jQuery).
		wp_enqueue_script(
			'aisales-chat-utils',
			AISALES_PLUGIN_URL . 'assets/js/chat/utils.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// 2. Attachments module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-attachments',
			AISALES_PLUGIN_URL . 'assets/js/chat/attachments.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 3. Context panel module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-context',
			AISALES_PLUGIN_URL . 'assets/js/chat/context.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 4. Entities module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-entities',
			AISALES_PLUGIN_URL . 'assets/js/chat/entities.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 5. Wizard module (depends on utils, entities).
		wp_enqueue_script(
			'aisales-chat-wizard',
			AISALES_PLUGIN_URL . 'assets/js/chat/wizard.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-entities' ),
			$js_version,
			true
		);

		// 6. Messaging module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-messaging',
			AISALES_PLUGIN_URL . 'assets/js/chat/messaging.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 7. Suggestions module (depends on utils, messaging).
		wp_enqueue_script(
			'aisales-chat-suggestions',
			AISALES_PLUGIN_URL . 'assets/js/chat/suggestions.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-messaging' ),
			$js_version,
			true
		);

		// 8. Images module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-images',
			AISALES_PLUGIN_URL . 'assets/js/chat/images.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 9. Formatting module (depends on utils).
		wp_enqueue_script(
			'aisales-chat-formatting',
			AISALES_PLUGIN_URL . 'assets/js/chat/formatting.js',
			array( 'jquery', 'aisales-chat-utils' ),
			$js_version,
			true
		);

		// 10. Panels module (depends on utils, formatting).
		wp_enqueue_script(
			'aisales-chat-panels',
			AISALES_PLUGIN_URL . 'assets/js/chat/panels.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-formatting' ),
			$js_version,
			true
		);

		// 11. UI module (depends on utils, formatting).
		wp_enqueue_script(
			'aisales-chat-ui',
			AISALES_PLUGIN_URL . 'assets/js/chat/ui.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-formatting' ),
			$js_version,
			true
		);

		// 12. Quick Actions module (depends on utils, suggestions, images).
		wp_enqueue_script(
			'aisales-chat-quick-actions',
			AISALES_PLUGIN_URL . 'assets/js/chat/quick-actions.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-suggestions', 'aisales-chat-images' ),
			$js_version,
			true
		);

		// 13. Main chat script (depends on all modules).
		wp_enqueue_script(
			'aisales-chat',
			AISALES_PLUGIN_URL . 'assets/js/chat.js',
			array( 'jquery', 'aisales-chat-utils', 'aisales-chat-attachments', 'aisales-chat-context', 'aisales-chat-entities', 'aisales-chat-wizard', 'aisales-chat-messaging', 'aisales-chat-suggestions', 'aisales-chat-images', 'aisales-chat-formatting', 'aisales-chat-panels', 'aisales-chat-ui', 'aisales-chat-quick-actions' ),
			$js_version,
			true
		);

		// Get products and categories for selectors (with caching).
		$products = get_transient( 'aisales_products_selector' );
		if ( false === $products ) {
			$products = $this->get_products_for_selector();
			set_transient( 'aisales_products_selector', $products, HOUR_IN_SECONDS );
		}

		$categories = get_transient( 'aisales_categories_selector' );
		if ( false === $categories ) {
			$categories = $this->get_categories_for_selector();
			set_transient( 'aisales_categories_selector', $categories, HOUR_IN_SECONDS );
		}

		// Get store context.
		$store_context = get_option( 'aisales_store_context', array() );

		// Check if user has visited chat before (for onboarding).
		$user_id      = get_current_user_id();
		$chat_visited = get_user_meta( $user_id, 'aisales_chat_visited', true );

		// Localize script.
		wp_localize_script(
			'aisales-chat',
			'aisalesChat',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'aisales_chat_nonce' ),
				'apiBaseUrl'   => apply_filters( 'aisales_api_url_client', AISALES_API_URL_CLIENT ),
				'apiKey'       => $api_key,
				'balance'      => get_option( 'aisales_balance', 0 ),
				'products'     => $products,
				'categories'   => $categories,
				'storeContext' => $store_context,
				'chatVisited'  => ! empty( $chat_visited ),
				'i18n'         => array(
					// General.
					'sendMessage'         => __( 'Send', 'stacksuite-sales-manager-for-woocommerce' ),
					'typePlaceholder'     => __( 'Type your message...', 'stacksuite-sales-manager-for-woocommerce' ),
					'thinking'            => __( 'Thinking...', 'stacksuite-sales-manager-for-woocommerce' ),
					'apply'               => __( 'Apply', 'stacksuite-sales-manager-for-woocommerce' ),
					'discard'             => __( 'Discard', 'stacksuite-sales-manager-for-woocommerce' ),
					'applied'             => __( 'Applied', 'stacksuite-sales-manager-for-woocommerce' ),
					'discarded'           => __( 'Discarded', 'stacksuite-sales-manager-for-woocommerce' ),
					'errorOccurred'       => __( 'An error occurred. Please try again.', 'stacksuite-sales-manager-for-woocommerce' ),
					'insufficientBalance' => __( 'Insufficient balance. Please top up.', 'stacksuite-sales-manager-for-woocommerce' ),
					'connectionError'     => __( 'Connection error. Please check your internet.', 'stacksuite-sales-manager-for-woocommerce' ),
					'newChat'             => __( 'New Chat', 'stacksuite-sales-manager-for-woocommerce' ),
					'chatHistory'         => __( 'Chat History', 'stacksuite-sales-manager-for-woocommerce' ),
					'quickActions'        => __( 'Quick Actions', 'stacksuite-sales-manager-for-woocommerce' ),
					'tokensUsed'          => __( 'tokens used', 'stacksuite-sales-manager-for-woocommerce' ),
					'pendingChanges'      => __( 'Pending Changes', 'stacksuite-sales-manager-for-woocommerce' ),
					'acceptAll'           => __( 'Accept All', 'stacksuite-sales-manager-for-woocommerce' ),
					'discardAll'          => __( 'Discard All', 'stacksuite-sales-manager-for-woocommerce' ),

					// Products.
					'selectProduct'       => __( 'Select a product...', 'stacksuite-sales-manager-for-woocommerce' ),
					'noProducts'          => __( 'No products found', 'stacksuite-sales-manager-for-woocommerce' ),
					'productInfo'         => __( 'Product Info', 'stacksuite-sales-manager-for-woocommerce' ),
					'editProduct'         => __( 'Edit Product', 'stacksuite-sales-manager-for-woocommerce' ),
					'viewProduct'         => __( 'View Product', 'stacksuite-sales-manager-for-woocommerce' ),
					'improveTitle'        => __( 'Improve Title', 'stacksuite-sales-manager-for-woocommerce' ),
					'improveDescription'  => __( 'Improve Description', 'stacksuite-sales-manager-for-woocommerce' ),
					'seoOptimize'         => __( 'SEO Optimize', 'stacksuite-sales-manager-for-woocommerce' ),
					'suggestTags'         => __( 'Suggest Tags', 'stacksuite-sales-manager-for-woocommerce' ),
					'suggestCategories'   => __( 'Suggest Categories', 'stacksuite-sales-manager-for-woocommerce' ),
					'generateContent'     => __( 'Generate Content', 'stacksuite-sales-manager-for-woocommerce' ),

					// Categories.
					'selectCategory'      => __( 'Select a category...', 'stacksuite-sales-manager-for-woocommerce' ),
					'noCategories'        => __( 'No categories found', 'stacksuite-sales-manager-for-woocommerce' ),
					'categoryInfo'        => __( 'Category Info', 'stacksuite-sales-manager-for-woocommerce' ),
					'editCategory'        => __( 'Edit Category', 'stacksuite-sales-manager-for-woocommerce' ),
					'viewCategory'        => __( 'View Category', 'stacksuite-sales-manager-for-woocommerce' ),
					'improveCatName'      => __( 'Improve Name', 'stacksuite-sales-manager-for-woocommerce' ),
					'improveCatDesc'      => __( 'Improve Description', 'stacksuite-sales-manager-for-woocommerce' ),
					'catSeoOptimize'      => __( 'SEO Optimize', 'stacksuite-sales-manager-for-woocommerce' ),
					'generateCatContent'  => __( 'Generate Content', 'stacksuite-sales-manager-for-woocommerce' ),
					'subcategories'       => __( 'Subcategories', 'stacksuite-sales-manager-for-woocommerce' ),
					'parentCategory'      => __( 'Parent Category', 'stacksuite-sales-manager-for-woocommerce' ),
					'productCount'        => __( 'Products', 'stacksuite-sales-manager-for-woocommerce' ),

					// Store context.
					'storeContext'        => __( 'Store Context', 'stacksuite-sales-manager-for-woocommerce' ),
					'storeContextDesc'    => __( 'Help AI understand your store better', 'stacksuite-sales-manager-for-woocommerce' ),
					'saveContext'         => __( 'Save Context', 'stacksuite-sales-manager-for-woocommerce' ),
					'contextSaved'        => __( 'Store context saved successfully', 'stacksuite-sales-manager-for-woocommerce' ),
					'contextError'        => __( 'Failed to save store context', 'stacksuite-sales-manager-for-woocommerce' ),

					// Onboarding.
					'welcomeTitle'        => __( 'Welcome to AI Agent', 'stacksuite-sales-manager-for-woocommerce' ),
					'welcomeDesc'         => __( 'Select what you want to work on', 'stacksuite-sales-manager-for-woocommerce' ),
					'workOnProducts'      => __( 'Work on Products', 'stacksuite-sales-manager-for-woocommerce' ),
					'workOnCategories'    => __( 'Work on Categories', 'stacksuite-sales-manager-for-woocommerce' ),
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
			? array( '_aisales_seo_title', '_yoast_wpseo_title', 'rank_math_title' )
			: array( '_aisales_seo_description', '_yoast_wpseo_metadesc', 'rank_math_description' );

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
		$api_key = get_option( 'aisales_api_key' );

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

		// Add inline script data for preselected entities.
		$this->add_preselected_entity_scripts( $product_data, $category_data );

		include AISALES_PLUGIN_DIR . 'templates/admin-chat-page.php';
	}

	/**
	 * Add inline scripts for preselected entity data using wp_add_inline_script
	 *
	 * @param array|null $product_data  Preselected product data.
	 * @param array|null $category_data Preselected category data.
	 */
	private function add_preselected_entity_scripts( $product_data, $category_data ) {
		// Get store context status.
		$store_context  = get_option( 'aisales_store_context', array() );
		$context_status = 'missing';

		if ( ! empty( $store_context ) ) {
			$has_required   = ! empty( $store_context['store_name'] ) || ! empty( $store_context['business_niche'] );
			$has_optional   = ! empty( $store_context['target_audience'] ) || ! empty( $store_context['brand_tone'] );
			$context_status = $has_required ? ( $has_optional ? 'configured' : 'partial' ) : 'missing';
		}

		$inline_script = '';

		// Preselected product data.
		if ( ! empty( $product_data ) ) {
			$inline_script .= 'window.aisalesPreselectedProduct = ' . wp_json_encode( $product_data ) . ";\n";
			$inline_script .= "window.aisalesPreselectedEntityType = 'product';\n";
		}

		// Preselected category data.
		if ( ! empty( $category_data ) ) {
			$inline_script .= 'window.aisalesPreselectedCategory = ' . wp_json_encode( $category_data ) . ";\n";
			$inline_script .= "window.aisalesPreselectedEntityType = 'category';\n";
		}

		// Store context status.
		$inline_script .= 'window.aisalesStoreContext = {';
		$inline_script .= "status: '" . esc_js( $context_status ) . "',";
		$inline_script .= 'isConfigured: ' . ( 'configured' === $context_status ? 'true' : 'false' );
		$inline_script .= '};';

		if ( ! empty( $inline_script ) ) {
			wp_add_inline_script( 'aisales-chat', $inline_script, 'before' );
		}
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
		<div class="wrap aisales-admin-wrap">
			<h1>
				<span class="dashicons dashicons-format-chat"></span>
				<?php esc_html_e( 'AI Agent', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</h1>

			<div class="aisales-connect-wrap">
				<div class="aisales-card aisales-card--centered">
					<div class="aisales-empty-state">
						<span class="dashicons dashicons-admin-network"></span>
						<h3><?php esc_html_e( 'Connect Your Account', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Connect your StackSuite Sales Manager account to start using the AI Agent.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
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
	 * Fetch fresh balance from API and update local cache.
	 *
	 * @return int The current token balance.
	 */
	private function fetch_fresh_balance() {
		$api     = AISales_API_Client::instance();
		$account = $api->get_account();

		if ( ! is_wp_error( $account ) && isset( $account['balance_tokens'] ) ) {
			$balance = (int) $account['balance_tokens'];
			update_option( 'aisales_balance', $balance );
			return $balance;
		}

		// Fallback to cached value if API call fails.
		return (int) get_option( 'aisales_balance', 0 );
	}
}
