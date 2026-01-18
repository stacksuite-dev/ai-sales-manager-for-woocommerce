<?php
/**
 * Product Editor Metabox
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Metabox class
 */
class WooAI_Product_Metabox {

	/**
	 * Single instance
	 *
	 * @var WooAI_Product_Metabox
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return WooAI_Product_Metabox
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add meta box
	 */
	public function add_meta_box() {
		add_meta_box(
			'wooai-tools',
			__( 'AI Tools', 'woo-ai-sales-manager' ),
			array( $this, 'render_meta_box' ),
			'product',
			'side',
			'high'
		);
	}

	/**
	 * Render meta box
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		$api          = WooAI_API_Client::instance();
		$is_connected = $api->is_connected();

		if ( ! $is_connected ) {
			$this->render_not_connected();
			return;
		}

		?>
		<div class="wooai-metabox">
			<div class="wooai-metabox-agent">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-agent&product_id=' . $post->ID ) ); ?>"
				   class="button button-primary button-large wooai-agent-btn">
					<span class="dashicons dashicons-format-chat"></span>
					<?php esc_html_e( 'Open AI Agent', 'woo-ai-sales-manager' ); ?>
				</a>
				<p class="wooai-agent-description">
					<?php esc_html_e( 'Chat with AI to improve your product content, generate descriptions, suggest tags, and more.', 'woo-ai-sales-manager' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render not connected state
	 */
	private function render_not_connected() {
		?>
		<div class="wooai-metabox wooai-not-connected">
			<p>
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'Connect your account to use AI tools.', 'woo-ai-sales-manager' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect Account', 'woo-ai-sales-manager' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
