<?php
/**
 * Abandoned Cart Settings Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

class AISales_Abandoned_Cart_Settings_Page {
	/**
	 * Single instance.
	 *
	 * @var AISales_Abandoned_Cart_Settings_Page
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return AISales_Abandoned_Cart_Settings_Page
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	/**
	 * Handle form submission.
	 */
	public function handle_form_submission() {
		if ( ! isset( $_POST['aisales_abandoned_cart_settings_submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'aisales_abandoned_cart_settings' );

		$post     = wp_unslash( $_POST );
		$settings = AISales_Abandoned_Cart_Settings::get_settings();
		$steps    = $settings['email_steps'];
		$abandon  = isset( $post['abandon_minutes'] ) ? absint( $post['abandon_minutes'] ) : $settings['abandon_minutes'];
		$retain   = isset( $post['retention_days'] ) ? absint( $post['retention_days'] ) : $settings['retention_days'];
		$step_1   = isset( $post['email_step_1'] ) ? absint( $post['email_step_1'] ) : $steps[1];
		$step_2   = isset( $post['email_step_2'] ) ? absint( $post['email_step_2'] ) : $steps[2];
		$step_3   = isset( $post['email_step_3'] ) ? absint( $post['email_step_3'] ) : $steps[3];
		$redirect = isset( $post['restore_redirect'] ) ? sanitize_key( $post['restore_redirect'] ) : $settings['restore_redirect'];

		$abandon = max( 5, $abandon );
		$retain  = max( 1, $retain );
		$step_1  = max( 1, $step_1 );
		$step_2  = max( $step_1 + 1, $step_2 );
		$step_3  = max( $step_2 + 1, $step_3 );

		$allowed_redirects = array( 'checkout', 'cart' );
		if ( ! in_array( $redirect, $allowed_redirects, true ) ) {
			$redirect = 'checkout';
		}

		$settings['abandon_minutes']  = $abandon;
		$settings['retention_days']   = $retain;
		$settings['enable_emails']    = ! empty( $post['enable_emails'] );
		$settings['restore_redirect'] = $redirect;
		$settings['email_steps']      = array(
			1 => $step_1,
			2 => $step_2,
			3 => $step_3,
		);

		update_option( AISales_Abandoned_Cart_Settings::OPTION_KEY, $settings );

		$redirect_url = admin_url( 'admin.php?page=ai-sales-abandoned-carts&aisales_saved=1' );
		wp_safe_redirect( wp_nonce_url( $redirect_url, 'aisales_settings_saved' ) );
		exit;
	}

	/**
	 * Render page.
	 */
	public function render_page() {
		$settings = AISales_Abandoned_Cart_Settings::get_settings();
		?>
		<section class="aisales-abandoned-carts-settings">
			<h2><?php esc_html_e( 'Abandoned Cart Settings', 'ai-sales-manager-for-woocommerce' ); ?></h2>
			<?php
		$aisales_saved_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		?>
		<?php if ( isset( $_GET['aisales_saved'] ) && wp_verify_nonce( $aisales_saved_nonce, 'aisales_settings_saved' ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'ai-sales-manager-for-woocommerce' ); ?></p>
				</div>
			<?php endif; ?>
			<form method="post">
				<?php wp_nonce_field( 'aisales_abandoned_cart_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="aisales-abandon-minutes"><?php esc_html_e( 'Abandon After (minutes)', 'ai-sales-manager-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="number" id="aisales-abandon-minutes" name="abandon_minutes" class="small-text" min="5" value="<?php echo esc_attr( $settings['abandon_minutes'] ); ?>">
							<p class="description"><?php esc_html_e( 'Mark carts abandoned after inactivity.', 'ai-sales-manager-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aisales-retention-days"><?php esc_html_e( 'Retention (days)', 'ai-sales-manager-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="number" id="aisales-retention-days" name="retention_days" class="small-text" min="1" value="<?php echo esc_attr( $settings['retention_days'] ); ?>">
							<p class="description"><?php esc_html_e( 'Expire carts after this many days.', 'ai-sales-manager-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Recovery Emails', 'ai-sales-manager-for-woocommerce' ); ?></th>
						<td>
							<label for="aisales-enable-emails">
								<input type="checkbox" id="aisales-enable-emails" name="enable_emails" value="1" <?php checked( $settings['enable_emails'] ); ?>>
								<?php esc_html_e( 'Enable recovery emails', 'ai-sales-manager-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Email Timing (hours)', 'ai-sales-manager-for-woocommerce' ); ?></th>
						<td>
							<label for="aisales-email-step-1"><?php esc_html_e( 'Email 1', 'ai-sales-manager-for-woocommerce' ); ?></label>
							<input type="number" id="aisales-email-step-1" name="email_step_1" class="small-text" min="1" value="<?php echo esc_attr( $settings['email_steps'][1] ); ?>">
							<label for="aisales-email-step-2" style="margin-left:10px;">
								<?php esc_html_e( 'Email 2', 'ai-sales-manager-for-woocommerce' ); ?>
							</label>
							<input type="number" id="aisales-email-step-2" name="email_step_2" class="small-text" min="1" value="<?php echo esc_attr( $settings['email_steps'][2] ); ?>">
							<label for="aisales-email-step-3" style="margin-left:10px;">
								<?php esc_html_e( 'Email 3', 'ai-sales-manager-for-woocommerce' ); ?>
							</label>
							<input type="number" id="aisales-email-step-3" name="email_step_3" class="small-text" min="1" value="<?php echo esc_attr( $settings['email_steps'][3] ); ?>">
							<p class="description"><?php esc_html_e( 'Hours after abandonment.', 'ai-sales-manager-for-woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="aisales-restore-redirect"><?php esc_html_e( 'Restore Link Destination', 'ai-sales-manager-for-woocommerce' ); ?></label>
						</th>
						<td>
							<select id="aisales-restore-redirect" name="restore_redirect">
								<option value="checkout" <?php selected( $settings['restore_redirect'], 'checkout' ); ?>><?php esc_html_e( 'Checkout', 'ai-sales-manager-for-woocommerce' ); ?></option>
								<option value="cart" <?php selected( $settings['restore_redirect'], 'cart' ); ?>><?php esc_html_e( 'Cart', 'ai-sales-manager-for-woocommerce' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" name="aisales_abandoned_cart_settings_submit" class="button button-primary">
						<?php esc_html_e( 'Save Changes', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</p>
			</form>
		</section>
		<?php
	}
}
