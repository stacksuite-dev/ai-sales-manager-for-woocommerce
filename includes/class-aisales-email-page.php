<?php
/**
 * Email Templates Management Page
 *
 * Dedicated admin page for managing AI-generated WooCommerce email templates.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email Page class
 */
class AISales_Email_Page {

	/**
	 * Single instance
	 *
	 * @var AISales_Email_Page
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Email_Page
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
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page under AI Sales Manager
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'ai-sales-manager',
			__( 'Email Templates', 'ai-sales-manager-for-woocommerce' ),
			__( 'Email Templates', 'ai-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-emails',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'ai-sales-manager_page_ai-sales-emails' !== $hook ) {
			return;
		}

		// CSS version.
		$css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/email-page.css' )
			: AISALES_VERSION;

		// Enqueue page styles.
		wp_enqueue_style(
			'aisales-email-page',
			AISALES_PLUGIN_URL . 'assets/css/email-page.css',
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
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/email-page.js' )
			: AISALES_VERSION;

		// Enqueue page script.
		wp_enqueue_script(
			'aisales-email-page',
			AISALES_PLUGIN_URL . 'assets/js/email-page.js',
			array( 'jquery' ),
			$js_version,
			true
		);

		// Get email manager data.
		$email_manager = AISales_Email_Manager::instance();

		// Localize script.
		wp_localize_script(
			'aisales-email-page',
			'aisalesEmail',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'aisales_nonce' ),
				'apiKey'       => $api_key,
				'balance'      => get_option( 'aisales_balance', 0 ),
				'storeContext' => get_option( 'aisales_store_context', array() ),
				'placeholders' => $email_manager->get_placeholders(),
				'adminEmail'   => get_option( 'admin_email' ),
				'i18n'         => $this->get_i18n_strings(),
			)
		);

		// Enqueue wizard assets.
		$this->enqueue_wizard_assets( $email_manager );

		// Enqueue mail provider assets for Settings tab.
		$this->enqueue_mail_provider_assets();
	}

	/**
	 * Enqueue mail provider assets for Settings tab
	 */
	private function enqueue_mail_provider_assets() {
		$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// CSS.
		$css_path = AISALES_PLUGIN_DIR . 'assets/css/mail-provider-page.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'aisales-mail-provider',
				AISALES_PLUGIN_URL . 'assets/css/mail-provider-page.css',
				array( 'aisales-admin' ),
				$is_debug ? filemtime( $css_path ) : AISALES_VERSION
			);
		}

		// JS.
		$js_path = AISALES_PLUGIN_DIR . 'assets/js/mail-provider-page.js';
		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'aisales-mail-provider',
				AISALES_PLUGIN_URL . 'assets/js/mail-provider-page.js',
				array( 'jquery' ),
				$is_debug ? filemtime( $js_path ) : AISALES_VERSION,
				true
			);
		}

		// Localize.
		wp_localize_script(
			'aisales-mail-provider',
			'aisalesMailProvider',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'aisales_nonce' ),
				'settings'   => AISales_Mail_Provider::instance()->get_settings(),
				'adminEmail' => get_option( 'admin_email' ),
				'i18n'       => array(
					'save'         => __( 'Save Settings', 'ai-sales-manager-for-woocommerce' ),
					'saved'        => __( 'Email delivery settings saved.', 'ai-sales-manager-for-woocommerce' ),
					'saveFailed'   => __( 'Failed to save settings.', 'ai-sales-manager-for-woocommerce' ),
					'invalidEmail' => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ),
					'testing'      => __( 'Sending test email...', 'ai-sales-manager-for-woocommerce' ),
					'testSent'     => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ),
					'testFailed'   => __( 'Failed to send test email.', 'ai-sales-manager-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Enqueue wizard scripts and styles
	 *
	 * @param AISales_Email_Manager $email_manager Email manager instance.
	 */
	private function enqueue_wizard_assets( $email_manager ) {
		// Wizard CSS.
		$wizard_css_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/css/email-wizard.css' )
			: AISALES_VERSION;

		wp_enqueue_style(
			'aisales-email-wizard',
			AISALES_PLUGIN_URL . 'assets/css/email-wizard.css',
			array( 'aisales-email-page' ),
			$wizard_css_version
		);

		// Wizard JS.
		$wizard_js_version = defined( 'WP_DEBUG' ) && WP_DEBUG
			? filemtime( AISALES_PLUGIN_DIR . 'assets/js/email-wizard.js' )
			: AISALES_VERSION;

		wp_enqueue_script(
			'aisales-email-wizard',
			AISALES_PLUGIN_URL . 'assets/js/email-wizard.js',
			array( 'jquery', 'aisales-email-page' ),
			$wizard_js_version,
			true
		);

		// Check if wizard should be shown.
		$wizard_completed = get_option( 'aisales_email_wizard_completed', false );
		$store_context    = get_option( 'aisales_store_context', array() );

		// Get detected branding from extractor.
		$branding_extractor = AISales_Branding_Extractor::instance();
		$detected_branding  = $branding_extractor->get_branding();

		// Prepare templates data for wizard.
		$templates_overview = $email_manager->get_templates_overview();
		$wizard_templates   = array();
		foreach ( $templates_overview as $type => $template ) {
			// Only include MVP templates in wizard.
			if ( ! empty( $template['is_mvp'] ) ) {
				$wizard_templates[ $type ] = array(
					'label'        => $template['label'],
					'description'  => $template['description'],
					'has_template' => $template['has_template'],
				);
			}
		}

		// Localize wizard data.
		wp_localize_script(
			'aisales-email-wizard',
			'aisalesWizardData',
			array(
				'showWizard' => ! $wizard_completed,
				'context'    => array(
					'store_name'      => $store_context['store_name'] ?? get_bloginfo( 'name' ),
					'business_niche'  => $store_context['business_niche'] ?? '',
					'brand_tone'      => $store_context['brand_tone'] ?? 'friendly',
					'target_audience' => $store_context['target_audience'] ?? '',
					// Branding fields: use saved values or fall back to detected branding
					'primary_color'   => $store_context['primary_color'] ?? $detected_branding['colors']['primary'] ?? '#7f54b3',
					'text_color'      => $store_context['text_color'] ?? $detected_branding['colors']['text'] ?? '#3c3c3c',
					'bg_color'        => $store_context['bg_color'] ?? $detected_branding['colors']['background'] ?? '#f7f7f7',
					'font_family'     => $store_context['font_family'] ?? $detected_branding['fonts']['body_slug'] ?? 'system',
				),
				'templates'  => $wizard_templates,
			)
		);

		// Localize wizard i18n and config.
		wp_localize_script(
			'aisales-email-wizard',
			'aisalesWizard',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aisales_nonce' ),
				'i18n'    => array(
					'step1Title'         => __( 'Personalize Your Emails', 'ai-sales-manager-for-woocommerce' ),
					'step2Title'         => __( 'Choose Templates', 'ai-sales-manager-for-woocommerce' ),
					'step3Title'         => __( 'Generating...', 'ai-sales-manager-for-woocommerce' ),
					'step4Title'         => __( 'All Done!', 'ai-sales-manager-for-woocommerce' ),
					'brandSettingsTitle' => __( 'Brand Settings', 'ai-sales-manager-for-woocommerce' ),
					'skipSetup'          => __( 'Skip setup', 'ai-sales-manager-for-woocommerce' ),
					'continue'           => __( 'Continue', 'ai-sales-manager-for-woocommerce' ),
					'back'               => __( 'Back', 'ai-sales-manager-for-woocommerce' ),
					'saveSettings'       => __( 'Save Settings', 'ai-sales-manager-for-woocommerce' ),
					'settingsSaved'      => __( 'Brand settings saved successfully!', 'ai-sales-manager-for-woocommerce' ),
					'generateCount'      => __( 'Generate {count} Template(s)', 'ai-sales-manager-for-woocommerce' ),
					'viewTemplates'      => __( 'View Templates', 'ai-sales-manager-for-woocommerce' ),
					'selectAtLeastOne'   => __( 'Please select at least one template to generate.', 'ai-sales-manager-for-woocommerce' ),
					'partialSuccess'     => __( 'Almost there!', 'ai-sales-manager-for-woocommerce' ),
					'partialSuccessMsg'  => __( '{success} templates generated, {errors} failed.', 'ai-sales-manager-for-woocommerce' ),
				),
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
			'loading'           => __( 'Loading...', 'ai-sales-manager-for-woocommerce' ),
			'error'             => __( 'Error', 'ai-sales-manager-for-woocommerce' ),
			'success'           => __( 'Success', 'ai-sales-manager-for-woocommerce' ),
			'cancel'            => __( 'Cancel', 'ai-sales-manager-for-woocommerce' ),
			'confirm'           => __( 'Confirm', 'ai-sales-manager-for-woocommerce' ),
			'delete'            => __( 'Delete', 'ai-sales-manager-for-woocommerce' ),
			'save'              => __( 'Save', 'ai-sales-manager-for-woocommerce' ),

			// Status.
			'active'            => __( 'Active', 'ai-sales-manager-for-woocommerce' ),
			'draft'             => __( 'Draft', 'ai-sales-manager-for-woocommerce' ),
			'notCreated'        => __( 'Not Created', 'ai-sales-manager-for-woocommerce' ),

			// Actions.
			'generating'        => __( 'Generating...', 'ai-sales-manager-for-woocommerce' ),
			'saving'            => __( 'Saving...', 'ai-sales-manager-for-woocommerce' ),
			'deleting'          => __( 'Deleting...', 'ai-sales-manager-for-woocommerce' ),
			'previewing'        => __( 'Loading preview...', 'ai-sales-manager-for-woocommerce' ),

			// Messages.
			'templateGenerated' => __( 'Template generated successfully!', 'ai-sales-manager-for-woocommerce' ),
			'templateSaved'     => __( 'Template saved successfully!', 'ai-sales-manager-for-woocommerce' ),
			'templateActivated' => __( 'Template activated!', 'ai-sales-manager-for-woocommerce' ),
			'templateDeleted'   => __( 'Template deleted.', 'ai-sales-manager-for-woocommerce' ),
			'allGenerated'      => __( 'All templates generated!', 'ai-sales-manager-for-woocommerce' ),
			'confirmDelete'     => __( 'Are you sure you want to delete this template?', 'ai-sales-manager-for-woocommerce' ),
			'connectionError'   => __( 'Connection error. Please try again.', 'ai-sales-manager-for-woocommerce' ),

			// Preview.
			'previewNote'       => __( 'Preview uses sample order data', 'ai-sales-manager-for-woocommerce' ),
			'mobile'            => __( 'Mobile', 'ai-sales-manager-for-woocommerce' ),
			'tablet'            => __( 'Tablet', 'ai-sales-manager-for-woocommerce' ),
			'desktop'           => __( 'Desktop', 'ai-sales-manager-for-woocommerce' ),

			// Test email.
			'sendTest'          => __( 'Send Test', 'ai-sales-manager-for-woocommerce' ),
			'sendTestEmail'     => __( 'Send Test Email', 'ai-sales-manager-for-woocommerce' ),
			'sendingTest'       => __( 'Sending test email...', 'ai-sales-manager-for-woocommerce' ),
			'invalidEmail'      => __( 'Please enter a valid email address.', 'ai-sales-manager-for-woocommerce' ),
			'testSendSuccess'   => __( 'Test email sent successfully.', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Render the page
	 */
	public function render_page() {
		// Check if connected.
		$api_key = get_option( 'aisales_api_key' );
		$balance = get_option( 'aisales_balance', 0 );

		// Get email manager and templates.
		$email_manager = AISales_Email_Manager::instance();
		$templates     = $email_manager->get_templates_overview();
		$placeholders  = $email_manager->get_placeholders();

		// Group templates by category.
		$grouped_templates = $this->group_templates_by_category( $templates );

		// Calculate stats.
		$stats = $this->calculate_stats( $templates );

		// Include the template.
		include AISALES_PLUGIN_DIR . 'templates/admin-email-page.php';

		// Include wizard modal (only if connected).
		if ( ! empty( $api_key ) ) {
			$this->render_wizard_modal( $templates );
		}
	}

	/**
	 * Render the wizard modal
	 *
	 * @param array $templates All templates overview.
	 */
	private function render_wizard_modal( $templates ) {
		$store_context     = get_option( 'aisales_store_context', array() );
		$wizard_completed  = get_option( 'aisales_email_wizard_completed', false );

		// Pass variables to the template.
		include AISALES_PLUGIN_DIR . 'templates/partials/email-wizard-modal.php';
	}

	/**
	 * Group templates by category
	 *
	 * @param array $templates All templates.
	 * @return array Grouped templates.
	 */
	private function group_templates_by_category( $templates ) {
		$groups = array(
			'transactional' => array(
				'label'     => __( 'Transactional Emails', 'ai-sales-manager-for-woocommerce' ),
				'icon'      => 'dashicons-cart',
				'templates' => array(),
			),
			'customer'      => array(
				'label'     => __( 'Customer Emails', 'ai-sales-manager-for-woocommerce' ),
				'icon'      => 'dashicons-admin-users',
				'templates' => array(),
			),
			'admin'         => array(
				'label'     => __( 'Admin Notifications', 'ai-sales-manager-for-woocommerce' ),
				'icon'      => 'dashicons-admin-tools',
				'templates' => array(),
			),
		);

		// Category mapping.
		$category_map = array(
			// Phase 1: Transactional.
			'order_processing'        => 'transactional',
			'order_shipped'           => 'transactional',
			'order_completed'         => 'transactional',
			// Phase 2: Customer Account.
			'customer_new_account'    => 'customer',
			'customer_reset_password' => 'customer',
			'customer_invoice'        => 'customer',
			'customer_note'           => 'customer',
			'customer_refunded_order' => 'customer',
			// Phase 3: Admin Notifications.
			'new_order_admin'         => 'admin',
			'cancelled_order_admin'   => 'admin',
			'failed_order_admin'      => 'admin',
			// Phase 4: Order Status (Customer).
			'order_on_hold'           => 'transactional',
			'order_cancelled'         => 'transactional',
			'order_failed'            => 'transactional',
		);

		foreach ( $templates as $type => $template ) {
			$category = isset( $category_map[ $type ] ) ? $category_map[ $type ] : 'transactional';
			$groups[ $category ]['templates'][ $type ] = $template;
		}

		// Remove empty groups.
		return array_filter(
			$groups,
			function ( $group ) {
				return ! empty( $group['templates'] );
			}
		);
	}

	/**
	 * Calculate template stats
	 *
	 * @param array $templates All templates.
	 * @return array Stats array.
	 */
	private function calculate_stats( $templates ) {
		$stats = array(
			'active'      => 0,
			'draft'       => 0,
			'not_created' => 0,
			'total'       => 0,
		);

		foreach ( $templates as $template ) {
			if ( ! $template['is_mvp'] ) {
				continue;
			}
			$stats['total']++;
			if ( $template['is_active'] ) {
				$stats['active']++;
			} elseif ( $template['has_template'] ) {
				$stats['draft']++;
			} else {
				$stats['not_created']++;
			}
		}

		return $stats;
	}

	/**
	 * Get template icon based on type
	 *
	 * @param string $type Template type.
	 * @return string Dashicon class.
	 */
	public static function get_template_icon( $type ) {
		$icons = array(
			// Phase 1: Transactional.
			'order_processing'        => 'dashicons-yes-alt',
			'order_shipped'           => 'dashicons-airplane',
			'order_completed'         => 'dashicons-awards',
			// Phase 2: Customer Account.
			'customer_new_account'    => 'dashicons-admin-users',
			'customer_reset_password' => 'dashicons-lock',
			'customer_invoice'        => 'dashicons-media-text',
			'customer_note'           => 'dashicons-format-aside',
			'customer_refunded_order' => 'dashicons-money-alt',
			// Phase 3: Admin Notifications.
			'new_order_admin'         => 'dashicons-store',
			'cancelled_order_admin'   => 'dashicons-dismiss',
			'failed_order_admin'      => 'dashicons-warning',
			// Phase 4: Order Status (Customer).
			'order_on_hold'           => 'dashicons-clock',
			'order_cancelled'         => 'dashicons-no-alt',
			'order_failed'            => 'dashicons-thumbs-down',
		);

		return isset( $icons[ $type ] ) ? $icons[ $type ] : 'dashicons-email-alt';
	}
}
