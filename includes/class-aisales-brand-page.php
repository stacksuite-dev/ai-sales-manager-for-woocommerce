<?php
/**
 * Brand Settings Page
 *
 * Dedicated admin page for managing store brand identity.
 * Provides AI-powered brand analysis and manual configuration.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brand Page class
 */
class AISales_Brand_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_Brand_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Brand_Page
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
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page under AI Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'Brand Settings', 'ai-sales-manager-for-woocommerce' ),
			__( 'Brand Settings', 'ai-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-brand',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-brand' !== $hook ) {
			return;
		}

		// CSS version.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/brand-page.css' )
			: AISALES_VERSION;

		// Enqueue page styles.
		wp_enqueue_style(
			'aisales-brand-page',
			AISALES_PLUGIN_URL . 'assets/css/brand-page.css',
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
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/brand-page.js' )
			: AISALES_VERSION;

		// Enqueue page script.
		wp_enqueue_script(
			'aisales-brand-page',
			AISALES_PLUGIN_URL . 'assets/js/brand-page.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Get detected branding.
		$branding_extractor = AISales_Branding_Extractor::instance();
		$detected_branding  = $branding_extractor->get_branding();

		// Get saved store context.
		$store_context = get_option( 'aisales_store_context', array() );

		// Localize script.
		wp_localize_script(
			'aisales-brand-page',
			'aisalesBrand',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'aisales_nonce' ),
				'apiKey'           => $api_key,
				'balance'          => get_option( 'aisales_balance', 0 ),
				'hasSetup'         => $this->has_brand_settings(),
				'storeContext'     => $store_context,
				'detectedBranding' => $detected_branding,
				'safeFonts'        => $branding_extractor->get_safe_fonts(),
				'industries'       => $this->get_industries(),
				'tones'            => $this->get_brand_tones(),
				'pricePositions'   => $this->get_price_positions(),
				'promotionStyles'  => $this->get_promotion_styles(),
				'i18n'             => $this->get_i18n_strings(),
			)
		);
	}

	/**
	 * Check if brand settings have been configured
	 *
	 * @return bool
	 */
	public function has_brand_settings() {
		return (bool) get_option( 'aisales_brand_setup_complete', false );
	}

	/**
	 * Get industries list
	 *
	 * @return array
	 */
	private function get_industries() {
		return array(
			''                    => __( 'Select an industry...', 'ai-sales-manager-for-woocommerce' ),
			'fashion'             => __( 'Fashion & Apparel', 'ai-sales-manager-for-woocommerce' ),
			'electronics'         => __( 'Electronics & Technology', 'ai-sales-manager-for-woocommerce' ),
			'health_beauty'       => __( 'Health & Beauty', 'ai-sales-manager-for-woocommerce' ),
			'home_garden'         => __( 'Home & Garden', 'ai-sales-manager-for-woocommerce' ),
			'food_beverage'       => __( 'Food & Beverage', 'ai-sales-manager-for-woocommerce' ),
			'sports_outdoors'     => __( 'Sports & Outdoors', 'ai-sales-manager-for-woocommerce' ),
			'toys_games'          => __( 'Toys & Games', 'ai-sales-manager-for-woocommerce' ),
			'books_media'         => __( 'Books & Media', 'ai-sales-manager-for-woocommerce' ),
			'automotive'          => __( 'Automotive', 'ai-sales-manager-for-woocommerce' ),
			'jewelry_accessories' => __( 'Jewelry & Accessories', 'ai-sales-manager-for-woocommerce' ),
			'pet_supplies'        => __( 'Pet Supplies', 'ai-sales-manager-for-woocommerce' ),
			'arts_crafts'         => __( 'Arts & Crafts', 'ai-sales-manager-for-woocommerce' ),
			'baby_kids'           => __( 'Baby & Kids', 'ai-sales-manager-for-woocommerce' ),
			'office_supplies'     => __( 'Office Supplies', 'ai-sales-manager-for-woocommerce' ),
			'services'            => __( 'Services', 'ai-sales-manager-for-woocommerce' ),
			'digital_products'    => __( 'Digital Products', 'ai-sales-manager-for-woocommerce' ),
			'other'               => __( 'Other', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get brand tone options
	 *
	 * @return array
	 */
	private function get_brand_tones() {
		return array(
			'professional' => array(
				'label'       => __( 'Professional', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Formal, trustworthy, and business-focused', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-businessman',
			),
			'friendly'     => array(
				'label'       => __( 'Friendly', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Warm, approachable, and conversational', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-smiley',
			),
			'casual'       => array(
				'label'       => __( 'Casual', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Relaxed, informal, and easy-going', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-coffee',
			),
			'luxury'       => array(
				'label'       => __( 'Luxury', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Elegant, sophisticated, and premium', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-star-filled',
			),
			'playful'      => array(
				'label'       => __( 'Playful', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Fun, energetic, and creative', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-heart',
			),
		);
	}

	/**
	 * Get price positioning options
	 *
	 * @return array
	 */
	private function get_price_positions() {
		return array(
			''          => __( 'Select positioning...', 'ai-sales-manager-for-woocommerce' ),
			'budget'    => __( 'Budget-Friendly', 'ai-sales-manager-for-woocommerce' ),
			'value'     => __( 'Value for Money', 'ai-sales-manager-for-woocommerce' ),
			'mid_range' => __( 'Mid-Range', 'ai-sales-manager-for-woocommerce' ),
			'premium'   => __( 'Premium', 'ai-sales-manager-for-woocommerce' ),
			'luxury'    => __( 'Luxury', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get promotion style options
	 *
	 * @return array
	 */
	private function get_promotion_styles() {
		return array(
			'aggressive' => array(
				'label'       => __( 'Aggressive', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Frequent sales, urgency language, flash deals', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-megaphone',
			),
			'moderate'   => array(
				'label'       => __( 'Moderate', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Seasonal promotions, occasional discounts', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-calendar-alt',
			),
			'minimal'    => array(
				'label'       => __( 'Minimal', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Rare discounts, focus on value over price', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-awards',
			),
			'never'      => array(
				'label'       => __( 'Never Discount', 'ai-sales-manager-for-woocommerce' ),
				'description' => __( 'Premium positioning, no sales or promotions', 'ai-sales-manager-for-woocommerce' ),
				'icon'        => 'dashicons-lock',
			),
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
			'loading'              => __( 'Loading...', 'ai-sales-manager-for-woocommerce' ),
			'error'                => __( 'Error', 'ai-sales-manager-for-woocommerce' ),
			'success'              => __( 'Success', 'ai-sales-manager-for-woocommerce' ),
			'cancel'               => __( 'Cancel', 'ai-sales-manager-for-woocommerce' ),
			'save'                 => __( 'Save', 'ai-sales-manager-for-woocommerce' ),
			'saving'               => __( 'Saving...', 'ai-sales-manager-for-woocommerce' ),
			'saved'                => __( 'Saved!', 'ai-sales-manager-for-woocommerce' ),

			// Empty state.
			'welcomeTitle'         => __( "Let's set up your brand identity", 'ai-sales-manager-for-woocommerce' ),
			'welcomeDescription'   => __( 'Your brand settings help our AI generate content that matches your store\'s unique voice and style.', 'ai-sales-manager-for-woocommerce' ),
			'aiAnalyze'            => __( 'AI Analyze My Store', 'ai-sales-manager-for-woocommerce' ),
			'setupManually'        => __( 'Set up manually', 'ai-sales-manager-for-woocommerce' ),

			// Analyzing state.
			'analyzingTitle'       => __( 'Analyzing your store...', 'ai-sales-manager-for-woocommerce' ),
			'analyzingStep1'       => __( 'Gathering store information', 'ai-sales-manager-for-woocommerce' ),
			'analyzingStep2'       => __( 'Analyzing brand characteristics', 'ai-sales-manager-for-woocommerce' ),
			'analyzingStep3'       => __( 'Generating suggestions', 'ai-sales-manager-for-woocommerce' ),

			// Review state.
			'reviewTitle'          => __( 'Review AI Suggestions', 'ai-sales-manager-for-woocommerce' ),
			'reviewDescription'    => __( 'Our AI analyzed your store. Review and adjust these suggestions before saving.', 'ai-sales-manager-for-woocommerce' ),
			'acceptSuggestions'    => __( 'Accept & Continue', 'ai-sales-manager-for-woocommerce' ),
			'editSuggestions'      => __( 'Edit Suggestions', 'ai-sales-manager-for-woocommerce' ),

			// Form.
			'saveSettings'         => __( 'Save Settings', 'ai-sales-manager-for-woocommerce' ),
			'settingsSaved'        => __( 'Brand settings saved successfully!', 'ai-sales-manager-for-woocommerce' ),
			'resetToDetected'      => __( 'Reset to detected', 'ai-sales-manager-for-woocommerce' ),
			'colorsReset'          => __( 'Colors reset to detected values.', 'ai-sales-manager-for-woocommerce' ),

			// Cards.
			'storeIdentity'        => __( 'Store Identity', 'ai-sales-manager-for-woocommerce' ),
			'storeIdentityDesc'    => __( 'Basic information about your business', 'ai-sales-manager-for-woocommerce' ),
			'brandVoice'           => __( 'Brand Voice', 'ai-sales-manager-for-woocommerce' ),
			'brandVoiceDesc'       => __( 'How your brand communicates', 'ai-sales-manager-for-woocommerce' ),
			'brandStyle'           => __( 'Brand Style', 'ai-sales-manager-for-woocommerce' ),
			'brandStyleDesc'       => __( 'Visual identity for your emails', 'ai-sales-manager-for-woocommerce' ),

			// Fields.
			'storeName'            => __( 'Store Name', 'ai-sales-manager-for-woocommerce' ),
			'industry'             => __( 'Industry', 'ai-sales-manager-for-woocommerce' ),
			'targetAudience'       => __( 'Target Audience', 'ai-sales-manager-for-woocommerce' ),
			'targetAudiencePlaceholder' => __( 'e.g., Young professionals aged 25-35 interested in sustainable fashion', 'ai-sales-manager-for-woocommerce' ),
			'brandTone'            => __( 'Brand Tone', 'ai-sales-manager-for-woocommerce' ),
			'primaryColor'         => __( 'Primary Color', 'ai-sales-manager-for-woocommerce' ),
			'textColor'            => __( 'Text Color', 'ai-sales-manager-for-woocommerce' ),
			'backgroundColor'      => __( 'Background Color', 'ai-sales-manager-for-woocommerce' ),
			'fontFamily'           => __( 'Font Family', 'ai-sales-manager-for-woocommerce' ),

			// Errors.
			'connectionError'      => __( 'Connection error. Please try again.', 'ai-sales-manager-for-woocommerce' ),
			'analyzeError'         => __( 'Could not analyze store. Please try again or set up manually.', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Get analysis context from WordPress
	 *
	 * @return array Data to send for AI analysis.
	 */
	public function get_analysis_context() {
		$context = array(
			'store_name'        => get_bloginfo( 'name' ),
			'store_description' => get_bloginfo( 'description' ),
			'store_url'         => home_url(),
		);

		// Get product categories.
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 10,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$context['categories'] = wp_list_pluck( $terms, 'name' );
		}

		// Get sample products.
		$wc_products = wc_get_products(
			array(
				'limit'   => 5,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		if ( ! empty( $wc_products ) ) {
			$context['products'] = array();
			foreach ( $wc_products as $product ) {
				$context['products'][] = array(
					'name'        => $product->get_name(),
					'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
					'price'       => $product->get_price() ? wc_price( $product->get_price() ) : '',
				);
			}
		}

		// Get detected branding for theme colors.
		$branding_extractor = AISales_Branding_Extractor::instance();
		$detected_branding  = $branding_extractor->get_branding();

		if ( ! empty( $detected_branding ) ) {
			$context['theme_colors'] = array(
				'primary'    => isset( $detected_branding['primary_color'] ) ? $detected_branding['primary_color'] : '',
				'secondary'  => isset( $detected_branding['secondary_color'] ) ? $detected_branding['secondary_color'] : '',
				'text'       => isset( $detected_branding['text_color'] ) ? $detected_branding['text_color'] : '',
				'background' => isset( $detected_branding['bg_color'] ) ? $detected_branding['bg_color'] : '',
			);
		}

		return $context;
	}

	/**
	 * Render the page
	 */
	public function render_page() {
		// Check if connected.
		$aisales_api_key = get_option( 'aisales_api_key' );
		$aisales_balance = get_option( 'aisales_balance', 0 );

		// Get store context.
		$aisales_store_context = get_option( 'aisales_store_context', array() );
		$aisales_has_setup     = $this->has_brand_settings();

		// Get detected branding.
		$branding_extractor        = AISales_Branding_Extractor::instance();
		$aisales_detected_branding = $branding_extractor->get_branding();

		// Get options for the form.
		$aisales_industries       = $this->get_industries();
		$aisales_tones            = $this->get_brand_tones();
		$aisales_price_positions  = $this->get_price_positions();
		$aisales_promotion_styles = $this->get_promotion_styles();
		$aisales_safe_fonts       = $branding_extractor->get_safe_fonts();

		// Include the template.
		include AISALES_PLUGIN_DIR . 'templates/admin-brand-page.php';
	}
}
