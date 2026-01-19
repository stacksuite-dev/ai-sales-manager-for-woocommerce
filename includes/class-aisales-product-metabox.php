<?php
/**
 * Product Editor Metabox
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product Metabox class
 */
class AISales_Product_Metabox {

	/**
	 * Single instance
	 *
	 * @var AISales_Product_Metabox
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Product_Metabox
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
			'aisales-tools',
			__( 'AI Tools', 'ai-sales-manager-for-woocommerce' ),
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
		$api          = AISales_API_Client::instance();
		$is_connected = $api->is_connected();

		if ( ! $is_connected ) {
			$this->render_not_connected();
			return;
		}

		?>
		<div class="aisales-metabox">
			<div class="aisales-metabox-agent">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-agent&product_id=' . $post->ID ) ); ?>"
				   class="button button-primary button-large aisales-agent-btn">
					<span class="dashicons dashicons-format-chat"></span>
					<?php esc_html_e( 'Open AI Agent', 'ai-sales-manager-for-woocommerce' ); ?>
				</a>
				<p class="aisales-agent-description">
					<?php esc_html_e( 'Chat with AI to improve your product content, generate descriptions, suggest tags, and more.', 'ai-sales-manager-for-woocommerce' ); ?>
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
		<div class="aisales-metabox aisales-not-connected">
			<p>
				<span class="dashicons dashicons-admin-network"></span>
				<?php esc_html_e( 'Connect your account to use AI tools.', 'ai-sales-manager-for-woocommerce' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Connect Account', 'ai-sales-manager-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
