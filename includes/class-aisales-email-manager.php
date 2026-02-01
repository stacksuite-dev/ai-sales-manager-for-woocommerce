<?php
/**
 * Email Template Manager
 *
 * Manages AI-generated email templates and integrates with WooCommerce emails.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email Manager class
 */
class AISales_Email_Manager {

	/**
	 * Single instance
	 *
	 * @var AISales_Email_Manager
	 */
	private static $instance = null;

	/**
	 * Option key for storing email templates
	 *
	 * @var string
	 */
	const OPTION_KEY = 'aisales_email_templates';

	/**
	 * Supported email template types
	 *
	 * @var array
	 */
	private $template_types = array(
		// Phase 1: Transactional Emails (MVP).
		'order_processing'        => array(
			'label'       => 'Order Processing',
			'description' => 'Sent when a new order is received and payment is confirmed',
			'wc_email_id' => 'customer_processing_order',
			'is_mvp'      => true,
			'category'    => 'transactional',
			'icon'        => 'dashicons-yes-alt',
		),
		'order_shipped'           => array(
			'label'       => 'Order Shipped',
			'description' => 'Sent when an order is marked as shipped with tracking info',
			'wc_email_id' => 'customer_note',
			'is_mvp'      => true,
			'category'    => 'transactional',
			'icon'        => 'dashicons-airplane',
		),
		'order_completed'         => array(
			'label'       => 'Order Completed',
			'description' => 'Sent when an order is marked as complete',
			'wc_email_id' => 'customer_completed_order',
			'is_mvp'      => true,
			'category'    => 'transactional',
			'icon'        => 'dashicons-saved',
		),
		// Phase 2: Customer Account Emails.
		'customer_new_account'    => array(
			'label'       => 'New Account',
			'description' => 'Welcome email sent when a customer creates an account',
			'wc_email_id' => 'customer_new_account',
			'is_mvp'      => false,
			'category'    => 'customer',
			'icon'        => 'dashicons-admin-users',
			'is_user'     => true,
		),
		'customer_reset_password' => array(
			'label'       => 'Password Reset',
			'description' => 'Sent when a customer requests to reset their password',
			'wc_email_id' => 'customer_reset_password',
			'is_mvp'      => false,
			'category'    => 'customer',
			'icon'        => 'dashicons-lock',
			'is_user'     => true,
		),
		'customer_invoice'        => array(
			'label'       => 'Customer Invoice',
			'description' => 'Sent to customers with order details and payment link',
			'wc_email_id' => 'customer_invoice',
			'is_mvp'      => false,
			'category'    => 'customer',
			'icon'        => 'dashicons-media-text',
		),
		'customer_note'           => array(
			'label'       => 'Customer Note',
			'description' => 'Sent when the store adds a note to the customer order',
			'wc_email_id' => 'customer_note',
			'is_mvp'      => false,
			'category'    => 'customer',
			'icon'        => 'dashicons-testimonial',
		),
		'customer_refunded_order' => array(
			'label'       => 'Order Refunded',
			'description' => 'Sent when an order is fully or partially refunded',
			'wc_email_id' => 'customer_refunded_order',
			'is_mvp'      => false,
			'category'    => 'customer',
			'icon'        => 'dashicons-money-alt',
		),
		// Phase 3: Admin Notification Emails.
		'new_order_admin'         => array(
			'label'       => 'New Order (Admin)',
			'description' => 'Sent to admin when a new order is placed',
			'wc_email_id' => 'new_order',
			'is_mvp'      => false,
			'is_admin'    => true,
			'category'    => 'admin',
			'icon'        => 'dashicons-cart',
		),
		'cancelled_order_admin'   => array(
			'label'       => 'Cancelled Order (Admin)',
			'description' => 'Sent to admin when an order is cancelled',
			'wc_email_id' => 'cancelled_order',
			'is_mvp'      => false,
			'is_admin'    => true,
			'category'    => 'admin',
			'icon'        => 'dashicons-dismiss',
		),
		'failed_order_admin'      => array(
			'label'       => 'Failed Order (Admin)',
			'description' => 'Sent to admin when a payment fails',
			'wc_email_id' => 'failed_order',
			'is_mvp'      => false,
			'is_admin'    => true,
			'category'    => 'admin',
			'icon'        => 'dashicons-warning',
		),
		// Phase 4: Order Status Emails (Customer).
		'order_on_hold'           => array(
			'label'       => 'Order On Hold',
			'description' => 'Sent when an order is placed on hold awaiting payment',
			'wc_email_id' => 'customer_on_hold_order',
			'is_mvp'      => false,
			'category'    => 'transactional',
			'icon'        => 'dashicons-clock',
		),
		'order_cancelled'         => array(
			'label'       => 'Order Cancelled',
			'description' => 'Sent when a customer order is cancelled',
			'wc_email_id' => 'customer_cancelled_order',
			'is_mvp'      => false,
			'category'    => 'transactional',
			'icon'        => 'dashicons-no',
		),
		'order_failed'            => array(
			'label'       => 'Payment Failed',
			'description' => 'Sent when a customer payment fails',
			'wc_email_id' => 'customer_failed_order',
			'is_mvp'      => false,
			'category'    => 'transactional',
			'icon'        => 'dashicons-warning',
		),
		// Phase 5: Abandoned Cart Recovery Emails.
		'abandoned_cart_1'        => array(
			'label'       => 'Cart Reminder (1st)',
			'description' => 'First reminder sent after cart abandonment',
			'wc_email_id' => 'aisales_abandoned_cart_1',
			'is_mvp'      => false,
			'is_recovery' => true,
			'category'    => 'recovery',
			'icon'        => 'dashicons-email',
		),
		'abandoned_cart_2'        => array(
			'label'       => 'Cart Reminder (2nd)',
			'description' => 'Second follow-up reminder for abandoned cart',
			'wc_email_id' => 'aisales_abandoned_cart_2',
			'is_mvp'      => false,
			'is_recovery' => true,
			'category'    => 'recovery',
			'icon'        => 'dashicons-email-alt',
		),
		'abandoned_cart_3'        => array(
			'label'       => 'Cart Reminder (Final)',
			'description' => 'Final reminder before cart expires',
			'wc_email_id' => 'aisales_abandoned_cart_3',
			'is_mvp'      => false,
			'is_recovery' => true,
			'category'    => 'recovery',
			'icon'        => 'dashicons-email-alt2',
		),
	);

	/**
	 * Available placeholders for email templates
	 *
	 * @var array
	 */
	private $placeholders = array(
		'customer'  => array(
			'{customer_name}'       => 'Full customer name',
			'{customer_first_name}' => 'Customer first name',
			'{customer_last_name}'  => 'Customer last name',
			'{customer_email}'      => 'Customer email address',
		),
		'order'     => array(
			'{order_number}'          => 'WooCommerce order number',
			'{order_date}'            => 'Date order was placed',
			'{order_total}'           => 'Total order amount with currency',
			'{order_subtotal}'        => 'Subtotal before shipping/tax',
			'{order_shipping_total}'  => 'Shipping cost',
			'{order_tax_total}'       => 'Tax amount',
			'{order_payment_method}'  => 'How customer paid',
			'{order_shipping_method}' => 'Selected shipping method',
			'{order_status}'          => 'Current order status',
		),
		'address'   => array(
			'{billing_address}'       => 'Full billing address',
			'{shipping_address}'      => 'Full shipping address',
			'{billing_first_name}'    => 'Billing first name',
			'{billing_last_name}'     => 'Billing last name',
			'{shipping_first_name}'   => 'Shipping first name',
			'{shipping_last_name}'    => 'Shipping last name',
		),
		'tracking'  => array(
			'{tracking_number}' => 'Shipment tracking number',
			'{tracking_url}'    => 'Link to track shipment',
			'{carrier_name}'    => 'Shipping carrier name',
		),
		'store'     => array(
			'{store_name}'  => 'Your store name',
			'{store_url}'   => 'Your store website URL',
			'{store_email}' => 'Store contact email',
			'{store_phone}' => 'Store contact phone',
		),
		'content'   => array(
			'{order_items}'       => 'Formatted list of ordered items',
			'{order_items_table}' => 'HTML table of ordered items',
		),
		'account'   => array(
			'{account_url}'          => 'Link to My Account page',
			'{password_reset_link}'  => 'Password reset URL',
			'{customer_note_text}'   => 'Note content added by store',
			'{invoice_pay_link}'     => 'Link to pay invoice',
		),
		'refund'    => array(
			'{refund_amount}'  => 'Total refund amount',
			'{refund_reason}'  => 'Reason for refund (if provided)',
			'{refund_date}'    => 'Date of refund',
		),
		'admin'     => array(
			'{admin_order_url}'    => 'Link to view order in admin',
			'{order_edit_url}'     => 'Link to edit order in admin',
			'{payment_gateway}'    => 'Payment gateway used',
			'{order_item_count}'   => 'Number of items in order',
			'{customer_ip}'        => 'Customer IP address',
			'{order_currency}'     => 'Order currency code',
		),
		'cart'      => array(
			'{cart_items}'         => 'List of items left in cart',
			'{cart_total}'         => 'Total value of abandoned cart',
			'{restore_link}'       => 'Link to restore cart and checkout',
			'{cart_item_count}'    => 'Number of items in cart',
			'{cart_abandoned_date}' => 'Date cart was abandoned',
		),
	);

	/**
	 * Get instance
	 *
	 * @return AISales_Email_Manager
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
		// Hook into WooCommerce emails
		add_action( 'init', array( $this, 'init_email_hooks' ) );
	}

	/**
	 * WooCommerce email ID to template type mapping.
	 * Built from template_types on demand.
	 *
	 * @var array|null
	 */
	private $wc_email_map = null;

	/**
	 * Get WooCommerce email ID to template type mapping.
	 *
	 * @return array Map of wc_email_id => template_type.
	 */
	private function get_wc_email_map() {
		if ( null === $this->wc_email_map ) {
			$this->wc_email_map = array();
			foreach ( $this->template_types as $type => $meta ) {
				// Skip abandoned cart templates (handled by separate system).
				if ( ! empty( $meta['is_recovery'] ) ) {
					continue;
				}
				$this->wc_email_map[ $meta['wc_email_id'] ] = $type;
			}
		}
		return $this->wc_email_map;
	}

	/**
	 * Initialize WooCommerce email hooks
	 */
	public function init_email_hooks() {
		// Only hook if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Register subject and heading filters for all template types dynamically.
		foreach ( $this->template_types as $type => $meta ) {
			// Skip abandoned cart templates (handled by separate email system).
			if ( ! empty( $meta['is_recovery'] ) ) {
				continue;
			}

			$wc_email_id = $meta['wc_email_id'];

			// Add subject filter.
			add_filter(
				'woocommerce_email_subject_' . $wc_email_id,
				array( $this, 'filter_email_subject' ),
				10,
				2
			);

			// Add heading filter.
			add_filter(
				'woocommerce_email_heading_' . $wc_email_id,
				array( $this, 'filter_email_heading' ),
				10,
				2
			);
		}

		// Content hooks - inject custom content before order table.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'inject_custom_content' ), 10, 4 );
	}

	/**
	 * Get all stored templates
	 *
	 * @return array
	 */
	public function get_templates() {
		$templates = get_option( self::OPTION_KEY, array() );
		return is_array( $templates ) ? $templates : array();
	}

	/**
	 * Get a specific template by type
	 *
	 * @param string $template_type The template type (e.g., 'order_processing').
	 * @return array|null Template data or null if not found.
	 */
	public function get_template( $template_type ) {
		$templates = $this->get_templates();
		return isset( $templates[ $template_type ] ) ? $templates[ $template_type ] : null;
	}

	/**
	 * Get the active/default template for a type
	 *
	 * @param string $template_type The template type.
	 * @return array|null Active template or null if none active.
	 */
	public function get_active_template( $template_type ) {
		$template = $this->get_template( $template_type );
		
		if ( $template && isset( $template['status'] ) && 'active' === $template['status'] ) {
			return $template;
		}

		return null;
	}

	/**
	 * Save a template
	 *
	 * @param string $template_type The template type.
	 * @param array  $template_data Template data (subject, heading, content, etc.).
	 * @return bool Success status.
	 */
	public function save_template( $template_type, $template_data ) {
		if ( ! isset( $this->template_types[ $template_type ] ) ) {
			return false;
		}

		$templates = $this->get_templates();

		$templates[ $template_type ] = array(
			'name'       => isset( $template_data['name'] ) ? sanitize_text_field( $template_data['name'] ) : '',
			'subject'    => isset( $template_data['subject'] ) ? sanitize_text_field( $template_data['subject'] ) : '',
			'heading'    => isset( $template_data['heading'] ) ? sanitize_text_field( $template_data['heading'] ) : '',
			'content'    => isset( $template_data['content'] ) ? wp_kses_post( $template_data['content'] ) : '',
			'status'     => isset( $template_data['status'] ) ? sanitize_key( $template_data['status'] ) : 'draft',
			'updated_at' => current_time( 'mysql' ),
			'created_at' => isset( $templates[ $template_type ]['created_at'] ) 
				? $templates[ $template_type ]['created_at'] 
				: current_time( 'mysql' ),
		);

		return update_option( self::OPTION_KEY, $templates );
	}

	/**
	 * Delete a template
	 *
	 * @param string $template_type The template type.
	 * @return bool Success status.
	 */
	public function delete_template( $template_type ) {
		$templates = $this->get_templates();

		if ( isset( $templates[ $template_type ] ) ) {
			unset( $templates[ $template_type ] );
			return update_option( self::OPTION_KEY, $templates );
		}

		return false;
	}

	/**
	 * Enable a template (set status to active)
	 *
	 * @param string $template_type The template type.
	 * @return bool Success status.
	 */
	public function enable_template( $template_type ) {
		$template = $this->get_template( $template_type );
		
		if ( ! $template ) {
			return false;
		}

		$template['status'] = 'active';
		return $this->save_template( $template_type, $template );
	}

	/**
	 * Disable a template (set status to disabled)
	 *
	 * @param string $template_type The template type.
	 * @return bool Success status.
	 */
	public function disable_template( $template_type ) {
		$template = $this->get_template( $template_type );
		
		if ( ! $template ) {
			return false;
		}

		$template['status'] = 'disabled';
		return $this->save_template( $template_type, $template );
	}

	/**
	 * Replace placeholders in template with actual order data
	 *
	 * @param string   $template The template string with placeholders.
	 * @param WC_Order $order    The WooCommerce order object.
	 * @param array    $extra    Extra placeholder values (e.g., tracking info).
	 * @return string Template with placeholders replaced.
	 */
	public function replace_placeholders( $template, $order, $extra = array() ) {
		$replacements = $this->get_placeholder_values( $order, $extra );

		foreach ( $replacements as $placeholder => $value ) {
			$template = str_replace( $placeholder, $value, $template );
		}

		return $template;
	}

	/**
	 * Get placeholder values from an order
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 * @param array    $extra Extra placeholder values.
	 * @return array Placeholder => value pairs.
	 */
	public function get_placeholder_values( $order, $extra = array() ) {
		$values = array(
			// Customer
			'{customer_name}'       => $order->get_formatted_billing_full_name(),
			'{customer_first_name}' => $order->get_billing_first_name(),
			'{customer_last_name}'  => $order->get_billing_last_name(),
			'{customer_email}'      => $order->get_billing_email(),

			// Order
			'{order_number}'          => $order->get_order_number(),
			'{order_date}'            => wc_format_datetime( $order->get_date_created() ),
			'{order_total}'           => $order->get_formatted_order_total(),
			'{order_subtotal}'        => wc_price( $order->get_subtotal() ),
			'{order_shipping_total}'  => wc_price( $order->get_shipping_total() ),
			'{order_tax_total}'       => wc_price( $order->get_total_tax() ),
			'{order_payment_method}'  => $order->get_payment_method_title(),
			'{order_shipping_method}' => $order->get_shipping_method(),
			'{order_status}'          => wc_get_order_status_name( $order->get_status() ),

			// Address
			'{billing_address}'      => $order->get_formatted_billing_address(),
			'{shipping_address}'     => $order->get_formatted_shipping_address(),
			'{billing_first_name}'   => $order->get_billing_first_name(),
			'{billing_last_name}'    => $order->get_billing_last_name(),
			'{shipping_first_name}'  => $order->get_shipping_first_name(),
			'{shipping_last_name}'   => $order->get_shipping_last_name(),

			// Tracking (from extra or empty).
			'{tracking_number}' => isset( $extra['tracking_number'] ) ? $extra['tracking_number'] : '',
			'{tracking_url}'    => isset( $extra['tracking_url'] ) ? $extra['tracking_url'] : '',
			'{carrier_name}'    => isset( $extra['carrier_name'] ) ? $extra['carrier_name'] : '',

			// Store.
			'{store_name}'  => get_bloginfo( 'name' ),
			'{store_url}'   => home_url(),
			'{store_email}' => get_option( 'woocommerce_email_from_address' ),
			'{store_phone}' => get_option( 'woocommerce_store_phone', '' ),

			// Content.
			'{order_items}'       => $this->get_order_items_text( $order ),
			'{order_items_table}' => $this->get_order_items_table( $order ),

			// Account (Phase 2).
			'{account_url}'         => wc_get_page_permalink( 'myaccount' ),
			'{invoice_pay_link}'    => $order->get_checkout_payment_url(),
			'{customer_note_text}'  => isset( $extra['customer_note_text'] ) ? $extra['customer_note_text'] : '',

			// Refund (Phase 2).
			'{refund_amount}' => isset( $extra['refund_amount'] ) ? wc_price( $extra['refund_amount'] ) : '',
			'{refund_reason}' => isset( $extra['refund_reason'] ) ? $extra['refund_reason'] : '',
			'{refund_date}'   => isset( $extra['refund_date'] ) ? $extra['refund_date'] : wp_date( get_option( 'date_format' ) ),

			// Admin (Phase 3).
			'{admin_order_url}'  => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'{order_edit_url}'   => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'{payment_gateway}'  => $order->get_payment_method(),
			'{order_item_count}' => $order->get_item_count(),
			'{customer_ip}'      => $order->get_customer_ip_address(),
			'{order_currency}'   => $order->get_currency(),
		);

		// Merge with any extra values
		return array_merge( $values, $extra );
	}

	/**
	 * Get order items as text
	 *
	 * @param WC_Order $order The order object.
	 * @return string Formatted text list of items.
	 */
	private function get_order_items_text( $order ) {
		$items = array();
		
		foreach ( $order->get_items() as $item ) {
			$qty   = $item->get_quantity();
			$name  = $item->get_name();
			$total = wc_price( $item->get_total() );
			$items[] = sprintf( '%dx %s (%s)', $qty, $name, $total );
		}

		return implode( ', ', $items );
	}

	/**
	 * Get order items as HTML table
	 *
	 * @param WC_Order $order The order object.
	 * @return string HTML table of items.
	 */
	private function get_order_items_table( $order ) {
		ob_start();
		?>
		<table style="width:100%;border-collapse:collapse;margin:16px 0;">
			<thead>
				<tr style="background:#f5f5f5;">
					<th style="padding:12px;text-align:left;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Product', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
					<th style="padding:12px;text-align:center;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Qty', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
					<th style="padding:12px;text-align:right;border-bottom:1px solid #ddd;"><?php esc_html_e( 'Price', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $order->get_items() as $item ) : ?>
					<tr>
						<td style="padding:12px;border-bottom:1px solid #eee;"><?php echo esc_html( $item->get_name() ); ?></td>
						<td style="padding:12px;text-align:center;border-bottom:1px solid #eee;"><?php echo esc_html( $item->get_quantity() ); ?></td>
						<td style="padding:12px;text-align:right;border-bottom:1px solid #eee;"><?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generic email subject filter.
	 *
	 * Determines template type from current filter and applies custom subject.
	 *
	 * @param string              $subject Default subject.
	 * @param WC_Order|WP_User    $object  Order or User object.
	 * @return string Filtered subject.
	 */
	public function filter_email_subject( $subject, $object ) {
		$template_type = $this->get_template_type_from_filter();

		if ( ! $template_type ) {
			return $subject;
		}

		$template = $this->get_active_template( $template_type );

		if ( ! $template || empty( $template['subject'] ) ) {
			return $subject;
		}

		// Use user placeholders for user-based emails.
		$meta = $this->template_types[ $template_type ] ?? array();
		if ( ! empty( $meta['is_user'] ) && $object instanceof WP_User ) {
			return $this->replace_user_placeholders( $template['subject'], $object );
		}

		// Use order placeholders for order-based emails.
		if ( $object instanceof WC_Order ) {
			return $this->replace_placeholders( $template['subject'], $object );
		}

		return $subject;
	}

	/**
	 * Generic email heading filter.
	 *
	 * Determines template type from current filter and applies custom heading.
	 *
	 * @param string              $heading Default heading.
	 * @param WC_Order|WP_User    $object  Order or User object.
	 * @return string Filtered heading.
	 */
	public function filter_email_heading( $heading, $object ) {
		$template_type = $this->get_template_type_from_filter();

		if ( ! $template_type ) {
			return $heading;
		}

		$template = $this->get_active_template( $template_type );

		if ( ! $template || empty( $template['heading'] ) ) {
			return $heading;
		}

		// Use user placeholders for user-based emails.
		$meta = $this->template_types[ $template_type ] ?? array();
		if ( ! empty( $meta['is_user'] ) && $object instanceof WP_User ) {
			return $this->replace_user_placeholders( $template['heading'], $object );
		}

		// Use order placeholders for order-based emails.
		if ( $object instanceof WC_Order ) {
			return $this->replace_placeholders( $template['heading'], $object );
		}

		return $heading;
	}

	/**
	 * Determine template type from current filter hook.
	 *
	 * @return string|null Template type or null if not found.
	 */
	private function get_template_type_from_filter() {
		$current_filter = current_filter();

		// Extract WC email ID from filter name.
		// Filter format: woocommerce_email_subject_{wc_email_id} or woocommerce_email_heading_{wc_email_id}
		if ( preg_match( '/^woocommerce_email_(?:subject|heading)_(.+)$/', $current_filter, $matches ) ) {
			$wc_email_id = $matches[1];
			$map = $this->get_wc_email_map();
			return $map[ $wc_email_id ] ?? null;
		}

		return null;
	}

	/**
	 * Replace placeholders for user-based emails (no order context)
	 *
	 * @param string  $template The template string with placeholders.
	 * @param WP_User $user     The WordPress user object.
	 * @param array   $extra    Extra placeholder values.
	 * @return string Template with placeholders replaced.
	 */
	public function replace_user_placeholders( $template, $user, $extra = array() ) {
		$values = array(
			// Customer.
			'{customer_name}'       => $user->display_name,
			'{customer_first_name}' => $user->first_name ?: $user->display_name,
			'{customer_last_name}'  => $user->last_name,
			'{customer_email}'      => $user->user_email,

			// Account.
			'{account_url}'         => wc_get_page_permalink( 'myaccount' ),

			// Store.
			'{store_name}'  => get_bloginfo( 'name' ),
			'{store_url}'   => home_url(),
			'{store_email}' => get_option( 'woocommerce_email_from_address' ),
			'{store_phone}' => get_option( 'woocommerce_store_phone', '' ),
		);

		// Merge with any extra values.
		$values = array_merge( $values, $extra );

		foreach ( $values as $placeholder => $value ) {
			$template = str_replace( $placeholder, $value, $template );
		}

		return $template;
	}

	/**
	 * Inject custom content before order table in emails
	 *
	 * @param WC_Order $order         Order object.
	 * @param bool     $sent_to_admin Is sent to admin.
	 * @param bool     $plain_text    Is plain text email.
	 * @param WC_Email $email         Email object.
	 */
	public function inject_custom_content( $order, $sent_to_admin, $plain_text, $email ) {
		// Map WooCommerce email IDs to our template types.
		$customer_email_map = array(
			// Phase 1: Transactional.
			'customer_processing_order' => 'order_processing',
			'customer_completed_order'  => 'order_completed',
			// Phase 2: Customer account (order-related).
			'customer_invoice'          => 'customer_invoice',
			'customer_note'             => 'customer_note',
			'customer_refunded_order'   => 'customer_refunded_order',
			// Phase 4: Order status (customer).
			'customer_on_hold_order'    => 'order_on_hold',
			'customer_cancelled_order'  => 'order_cancelled',
			'customer_failed_order'     => 'order_failed',
		);

		$admin_email_map = array(
			// Phase 3: Admin notifications.
			'new_order'       => 'new_order_admin',
			'cancelled_order' => 'cancelled_order_admin',
			'failed_order'    => 'failed_order_admin',
		);

		// Determine template type based on admin flag and email ID.
		$template_type = null;
		if ( $sent_to_admin && isset( $admin_email_map[ $email->id ] ) ) {
			$template_type = $admin_email_map[ $email->id ];
		} elseif ( ! $sent_to_admin && isset( $customer_email_map[ $email->id ] ) ) {
			$template_type = $customer_email_map[ $email->id ];
		}

		if ( ! $template_type ) {
			return;
		}

		$template = $this->get_active_template( $template_type );

		if ( ! $template || empty( $template['content'] ) ) {
			return;
		}

		// Get extra context for specific email types.
		$extra = array();
		if ( 'customer_note' === $template_type && isset( $email->customer_note ) ) {
			$extra['customer_note_text'] = $email->customer_note;
		}

		$content = $this->replace_placeholders( $template['content'], $order, $extra );

		// Convert line breaks to HTML for HTML emails.
		if ( ! $plain_text ) {
			$content = wpautop( $content );
		}

		echo wp_kses_post( $content );
	}

	/**
	 * Get template types with metadata
	 *
	 * @param bool $mvp_only Return only MVP types.
	 * @return array Template type data.
	 */
	public function get_template_types( $mvp_only = false ) {
		if ( $mvp_only ) {
			return array_filter( $this->template_types, function( $type ) {
				return isset( $type['is_mvp'] ) && $type['is_mvp'];
			} );
		}

		return $this->template_types;
	}

	/**
	 * Get all placeholder groups
	 *
	 * @return array Placeholder groups with descriptions.
	 */
	public function get_placeholders() {
		return $this->placeholders;
	}

	/**
	 * Get default sample order data for preview
	 *
	 * @return array Sample order data.
	 */
	public function get_sample_order_data() {
		return array(
			'{customer_name}'        => 'Sarah Johnson',
			'{customer_first_name}'  => 'Sarah',
			'{customer_last_name}'   => 'Johnson',
			'{customer_email}'       => 'sarah.johnson@example.com',
			'{order_number}'         => '#1234',
			'{order_date}'           => wp_date( get_option( 'date_format' ) ),
			'{order_total}'          => wc_price( 124.97 ),
			'{order_subtotal}'       => wc_price( 109.98 ),
			'{order_shipping_total}' => wc_price( 9.99 ),
			'{order_tax_total}'      => wc_price( 5.00 ),
			'{order_payment_method}' => 'Credit Card (Visa ending in 4242)',
			'{order_shipping_method}'=> 'Standard Shipping (5-7 business days)',
			'{order_status}'         => 'Processing',
			'{billing_address}'      => '123 Main Street, Apt 4B, New York, NY 10001, United States',
			'{shipping_address}'     => '456 Oak Avenue, Suite 100, Los Angeles, CA 90001, United States',
			'{billing_first_name}'   => 'Sarah',
			'{billing_last_name}'    => 'Johnson',
			'{shipping_first_name}'  => 'Sarah',
			'{shipping_last_name}'   => 'Johnson',
			'{tracking_number}'      => '1Z999AA10123456784',
			'{tracking_url}'         => 'https://www.ups.com/track?tracknum=1Z999AA10123456784',
			'{carrier_name}'         => 'UPS',
			'{store_name}'           => get_bloginfo( 'name' ),
			'{store_url}'            => home_url(),
			'{store_email}'          => get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ),
			'{store_phone}'          => get_option( 'woocommerce_store_phone', '(555) 123-4567' ),
			'{order_items}'          => '1x Wireless Bluetooth Headphones ($79.99), 1x Phone Case - Black ($29.99)',
			'{order_items_table}'    => $this->get_sample_order_items_table(),

			// Account placeholders (Phase 2).
			'{account_url}'          => wc_get_page_permalink( 'myaccount' ) ?: home_url( '/my-account/' ),
			'{password_reset_link}'  => home_url( '/my-account/lost-password/?key=abc123&id=1' ),
			'{customer_note_text}'   => 'Thank you for your order! We wanted to let you know that your package will be shipped via express delivery.',
			'{invoice_pay_link}'     => home_url( '/checkout/order-pay/1234/?key=wc_order_abc123' ),

			// Refund placeholders (Phase 2).
			'{refund_amount}'        => wc_price( 45.00 ),
			'{refund_reason}'        => 'Item returned - customer preference',
			'{refund_date}'          => wp_date( get_option( 'date_format' ) ),

			// Admin placeholders (Phase 3).
			'{admin_order_url}'      => admin_url( 'post.php?post=1234&action=edit' ),
			'{order_edit_url}'       => admin_url( 'post.php?post=1234&action=edit' ),
			'{payment_gateway}'      => 'stripe',
			'{order_item_count}'     => '2',
			'{customer_ip}'          => '192.168.1.100',
			'{order_currency}'       => get_woocommerce_currency(),

			// Cart placeholders (Phase 5 - Abandoned Cart).
			'{cart_items}'           => '<ul><li>1x Wireless Bluetooth Headphones</li><li>1x Phone Case - Black</li></ul>',
			'{cart_total}'           => wc_price( 109.98 ),
			'{restore_link}'         => home_url( '/?action=aisales_restore_cart&token=abc123&key=xyz789' ),
			'{cart_item_count}'      => '2',
			'{cart_abandoned_date}'  => wp_date( get_option( 'date_format' ), strtotime( '-2 days' ) ),
		);
	}

	/**
	 * Get sample order items table HTML
	 *
	 * @return string Sample items table HTML.
	 */
	private function get_sample_order_items_table() {
		return '<table style="width:100%;border-collapse:collapse;margin:16px 0;">
			<thead>
				<tr style="background:#f5f5f5;">
					<th style="padding:12px;text-align:left;border-bottom:1px solid #ddd;">' . esc_html__( 'Product', 'stacksuite-sales-manager-for-woocommerce' ) . '</th>
					<th style="padding:12px;text-align:center;border-bottom:1px solid #ddd;">' . esc_html__( 'Qty', 'stacksuite-sales-manager-for-woocommerce' ) . '</th>
					<th style="padding:12px;text-align:right;border-bottom:1px solid #ddd;">' . esc_html__( 'Price', 'stacksuite-sales-manager-for-woocommerce' ) . '</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td style="padding:12px;border-bottom:1px solid #eee;">Wireless Bluetooth Headphones</td>
					<td style="padding:12px;text-align:center;border-bottom:1px solid #eee;">1</td>
					<td style="padding:12px;text-align:right;border-bottom:1px solid #eee;">' . wc_price( 79.99 ) . '</td>
				</tr>
				<tr>
					<td style="padding:12px;border-bottom:1px solid #eee;">Phone Case - Black</td>
					<td style="padding:12px;text-align:center;border-bottom:1px solid #eee;">1</td>
					<td style="padding:12px;text-align:right;border-bottom:1px solid #eee;">' . wc_price( 29.99 ) . '</td>
				</tr>
			</tbody>
		</table>';
	}

	/**
	 * Preview a template with sample data
	 *
	 * @param array $template Template data with subject, heading, content.
	 * @param array $custom_data Optional custom sample data overrides.
	 * @return array Preview data with replaced placeholders.
	 */
	public function preview_template( $template, $custom_data = array() ) {
		$sample_data = array_merge( $this->get_sample_order_data(), $custom_data );

		$preview = array(
			'subject' => '',
			'heading' => '',
			'content' => '',
		);

		if ( isset( $template['subject'] ) ) {
			$preview['subject'] = str_replace( 
				array_keys( $sample_data ), 
				array_values( $sample_data ), 
				$template['subject'] 
			);
		}

		if ( isset( $template['heading'] ) ) {
			$preview['heading'] = str_replace( 
				array_keys( $sample_data ), 
				array_values( $sample_data ), 
				$template['heading'] 
			);
		}

		if ( isset( $template['content'] ) ) {
			$preview['content'] = str_replace( 
				array_keys( $sample_data ), 
				array_values( $sample_data ), 
				$template['content'] 
			);
		}

		return $preview;
	}

	/**
	 * Get templates overview for admin display
	 *
	 * @return array Template overview with status indicators.
	 */
	public function get_templates_overview() {
		$overview = array();
		$templates = $this->get_templates();

		foreach ( $this->template_types as $type => $meta ) {
			$overview[ $type ] = array(
				'type'        => $type,
				'label'       => $meta['label'],
				'description' => $meta['description'],
				'is_mvp'      => $meta['is_mvp'],
				'has_template' => isset( $templates[ $type ] ),
				'is_active'   => isset( $templates[ $type ] ) && 'active' === $templates[ $type ]['status'],
				'template'    => isset( $templates[ $type ] ) ? $templates[ $type ] : null,
			);
		}

		return $overview;
	}
}
