<?php
/**
 * Category Editor Metabox
 *
 * Adds AI tools section to WooCommerce product category edit page.
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category Metabox class
 */
class WooAI_Category_Metabox {

	/**
	 * Single instance
	 *
	 * @var WooAI_Category_Metabox
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WooAI_Category_Metabox
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
		// Add fields to category edit page.
		add_action( 'product_cat_edit_form_fields', array( $this, 'render_edit_fields' ), 20 );

		// Add fields to category add page (optional, less prominent).
		add_action( 'product_cat_add_form_fields', array( $this, 'render_add_fields' ), 20 );

		// Enqueue scripts on taxonomy pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts for category pages
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		wp_add_inline_style( 'wooai-admin', $this->get_inline_styles() );
	}

	/**
	 * Get inline styles for category metabox
	 *
	 * @return string
	 */
	private function get_inline_styles() {
		return '
			.wooai-category-metabox {
				padding: 16px;
				background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				margin-top: 8px;
			}
			.wooai-category-metabox__header {
				display: flex;
				align-items: center;
				gap: 8px;
				margin-bottom: 12px;
			}
			.wooai-category-metabox__header h4 {
				margin: 0;
				font-size: 14px;
				font-weight: 600;
				color: #1e1e1e;
			}
			.wooai-category-metabox__header .dashicons {
				color: var(--wooai-primary, #5c5ce5);
				font-size: 18px;
				width: 18px;
				height: 18px;
			}
			.wooai-category-metabox__content p {
				margin: 0 0 12px;
				color: #50575e;
				font-size: 13px;
				line-height: 1.5;
			}
			.wooai-category-metabox .button-primary {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				background: var(--wooai-primary, #5c5ce5);
				border-color: var(--wooai-primary, #5c5ce5);
			}
			.wooai-category-metabox .button-primary:hover,
			.wooai-category-metabox .button-primary:focus {
				background: var(--wooai-primary-dark, #4b4bd4);
				border-color: var(--wooai-primary-dark, #4b4bd4);
			}
			.wooai-category-metabox .button-primary .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 1;
			}
			.wooai-category-metabox--not-connected {
				text-align: center;
			}
			.wooai-category-metabox--not-connected .dashicons-admin-network {
				font-size: 24px;
				width: 24px;
				height: 24px;
				color: #b3b3b3;
				margin-bottom: 8px;
			}
			.wooai-category-metabox--add {
				padding: 12px;
			}
			.wooai-category-metabox--add p {
				color: #646970;
				font-style: italic;
				margin: 0;
			}
			.wooai-category-metabox--add .dashicons {
				font-size: 14px;
				width: 14px;
				height: 14px;
				vertical-align: middle;
			}
		';
	}

	/**
	 * Render fields on category edit page
	 *
	 * @param WP_Term $term Current term being edited.
	 */
	public function render_edit_fields( $term ) {
		$is_connected = WooAI_API_Client::instance()->is_connected();
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'AI Tools', 'woo-ai-sales-manager' ); ?></label>
			</th>
			<td>
				<?php if ( $is_connected ) : ?>
					<div class="wooai-category-metabox">
						<div class="wooai-category-metabox__header">
							<span class="dashicons dashicons-superhero"></span>
							<h4><?php esc_html_e( 'AI-Powered Content', 'woo-ai-sales-manager' ); ?></h4>
						</div>
						<div class="wooai-category-metabox__content">
							<p><?php esc_html_e( 'Use AI to improve your category name, write compelling descriptions, and optimize for SEO.', 'woo-ai-sales-manager' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-agent&category_id=' . $term->term_id . '&entity_type=category' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-format-chat"></span>
								<?php esc_html_e( 'Open in AI Agent', 'woo-ai-sales-manager' ); ?>
							</a>
						</div>
					</div>
				<?php else : ?>
					<div class="wooai-category-metabox wooai-category-metabox--not-connected">
						<span class="dashicons dashicons-admin-network"></span>
						<p><?php esc_html_e( 'Connect your account to use AI tools.', 'woo-ai-sales-manager' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager' ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Connect Account', 'woo-ai-sales-manager' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render fields on category add page (simpler version)
	 */
	public function render_add_fields() {
		if ( ! WooAI_API_Client::instance()->is_connected() ) {
			return;
		}
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'AI Tools', 'woo-ai-sales-manager' ); ?></label>
			<div class="wooai-category-metabox wooai-category-metabox--add">
				<p>
					<span class="dashicons dashicons-info-outline"></span>
					<?php esc_html_e( 'AI tools will be available after you create this category.', 'woo-ai-sales-manager' ); ?>
				</p>
			</div>
		</div>
		<?php
	}
}
