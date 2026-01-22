<?php
/**
 * Mail Provider Settings Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

$provider = isset( $settings['provider'] ) ? $settings['provider'] : 'default';
$smtp     = isset( $settings['smtp'] ) && is_array( $settings['smtp'] ) ? $settings['smtp'] : array();
$sendgrid = isset( $settings['sendgrid'] ) && is_array( $settings['sendgrid'] ) ? $settings['sendgrid'] : array();
$resend   = isset( $settings['resend'] ) && is_array( $settings['resend'] ) ? $settings['resend'] : array();
$mailgun  = isset( $settings['mailgun'] ) && is_array( $settings['mailgun'] ) ? $settings['mailgun'] : array();
$postmark = isset( $settings['postmark'] ) && is_array( $settings['postmark'] ) ? $settings['postmark'] : array();
$ses      = isset( $settings['ses'] ) && is_array( $settings['ses'] ) ? $settings['ses'] : array();

$smtp_host       = isset( $smtp['host'] ) ? $smtp['host'] : '';
$smtp_port       = isset( $smtp['port'] ) ? (int) $smtp['port'] : 587;
$smtp_encryption = isset( $smtp['encryption'] ) ? $smtp['encryption'] : 'tls';
$smtp_auth       = ! empty( $smtp['auth'] );
$smtp_username   = isset( $smtp['username'] ) ? $smtp['username'] : '';
$smtp_password   = isset( $smtp['password'] ) ? $smtp['password'] : '';
$from_email      = isset( $smtp['from_email'] ) ? $smtp['from_email'] : '';
$from_name       = isset( $smtp['from_name'] ) ? $smtp['from_name'] : '';

$sendgrid_api_key = isset( $sendgrid['api_key'] ) ? $sendgrid['api_key'] : '';
$sendgrid_from_email = isset( $sendgrid['from_email'] ) ? $sendgrid['from_email'] : '';
$sendgrid_from_name = isset( $sendgrid['from_name'] ) ? $sendgrid['from_name'] : '';

$resend_api_key = isset( $resend['api_key'] ) ? $resend['api_key'] : '';
$resend_domain = isset( $resend['domain'] ) ? $resend['domain'] : '';
$resend_from_email = isset( $resend['from_email'] ) ? $resend['from_email'] : '';
$resend_from_name = isset( $resend['from_name'] ) ? $resend['from_name'] : '';

$mailgun_api_key = isset( $mailgun['api_key'] ) ? $mailgun['api_key'] : '';
$mailgun_domain = isset( $mailgun['domain'] ) ? $mailgun['domain'] : '';
$mailgun_region = isset( $mailgun['region'] ) ? $mailgun['region'] : 'us';
$mailgun_from_email = isset( $mailgun['from_email'] ) ? $mailgun['from_email'] : '';
$mailgun_from_name = isset( $mailgun['from_name'] ) ? $mailgun['from_name'] : '';

$postmark_server_token = isset( $postmark['server_token'] ) ? $postmark['server_token'] : '';
$postmark_from_email = isset( $postmark['from_email'] ) ? $postmark['from_email'] : '';
$postmark_from_name = isset( $postmark['from_name'] ) ? $postmark['from_name'] : '';

$ses_access_key = isset( $ses['access_key'] ) ? $ses['access_key'] : '';
$ses_secret_key = isset( $ses['secret_key'] ) ? $ses['secret_key'] : '';
$ses_region = isset( $ses['region'] ) ? $ses['region'] : '';
$ses_from_email = isset( $ses['from_email'] ) ? $ses['from_email'] : '';
$ses_from_name = isset( $ses['from_name'] ) ? $ses['from_name'] : '';
?>

<div class="wrap aisales-admin-wrap aisales-mail-provider-page">
	<h1 class="aisales-notices-anchor"></h1>

	<header class="aisales-mail-provider-page__header">
		<div class="aisales-mail-provider-page__header-left">
			<span class="aisales-mail-provider-page__title">
				<span class="dashicons dashicons-email"></span>
				<?php esc_html_e( 'Email Settings', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
			<span class="aisales-mail-provider-page__subtitle">
				<?php esc_html_e( 'Choose how WooCommerce emails are delivered.', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-mail-provider-page__header-right">
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
		</div>
	</header>

	<div class="aisales-mail-provider-page__content">
		<div class="aisales-card aisales-card--elevated aisales-mail-provider-card">
			<div class="aisales-mail-provider-card__header">
				<h2><?php esc_html_e( 'Provider Settings', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				<p class="aisales-text-muted">
					<?php esc_html_e( 'Use your preferred SMTP service for reliable delivery, or keep WordPress defaults.', 'ai-sales-manager-for-woocommerce' ); ?>
				</p>
			</div>

			<div class="aisales-form-group">
				<label class="aisales-label">
					<?php esc_html_e( 'Mail Provider', 'ai-sales-manager-for-woocommerce' ); ?>
				</label>
				<div class="aisales-mail-provider-options">
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="default" <?php checked( 'default', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Default WordPress Mail', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Send mail using your host\'s configured mailer.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="sendgrid" <?php checked( 'sendgrid', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'SendGrid', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Popular API-based delivery with strong analytics.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="resend" <?php checked( 'resend', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Resend', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Modern email API with clean deliverability.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="mailgun" <?php checked( 'mailgun', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Mailgun', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Reliable SMTP/API delivery for transactional mail.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="postmark" <?php checked( 'postmark', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Postmark', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Fast delivery with dedicated transactional focus.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="ses" <?php checked( 'ses', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Amazon SES', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'AWS-native email delivery with high volume support.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="aisales-mail-provider-option">
						<input type="radio" name="aisales-mail-provider" value="smtp" <?php checked( 'smtp', $provider ); ?>>
						<span class="aisales-mail-provider-option__content">
							<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'SMTP (Recommended)', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Connect a third-party mail service for higher deliverability.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</span>
					</label>
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'sendgrid' === $provider ? '' : ' is-hidden'; ?>" data-provider="sendgrid" id="aisales-mail-provider-sendgrid">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-sendgrid-key">
							<?php esc_html_e( 'SendGrid API Key', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-sendgrid-key" value="<?php echo esc_attr( $sendgrid_api_key ); ?>" autocomplete="new-password">
					</div>
				</div>
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-sendgrid-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-sendgrid-from-name" value="<?php echo esc_attr( $sendgrid_from_name ); ?>">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-sendgrid-from-email">
							<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="email" id="aisales-mail-provider-sendgrid-from-email" value="<?php echo esc_attr( $sendgrid_from_email ); ?>">
					</div>
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'resend' === $provider ? '' : ' is-hidden'; ?>" data-provider="resend" id="aisales-mail-provider-resend">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-resend-key">
							<?php esc_html_e( 'Resend API Key', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-resend-key" value="<?php echo esc_attr( $resend_api_key ); ?>" autocomplete="new-password">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-resend-domain">
							<?php esc_html_e( 'Verified Domain', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-resend-domain" value="<?php echo esc_attr( $resend_domain ); ?>" placeholder="yourdomain.com">
					</div>
				</div>
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-resend-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-resend-from-name" value="<?php echo esc_attr( $resend_from_name ); ?>">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-resend-from-email">
							<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="email" id="aisales-mail-provider-resend-from-email" value="<?php echo esc_attr( $resend_from_email ); ?>">
					</div>
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'mailgun' === $provider ? '' : ' is-hidden'; ?>" data-provider="mailgun" id="aisales-mail-provider-mailgun">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-mailgun-key">
							<?php esc_html_e( 'Mailgun API Key', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-mailgun-key" value="<?php echo esc_attr( $mailgun_api_key ); ?>" autocomplete="new-password">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-mailgun-domain">
							<?php esc_html_e( 'Mailgun Domain', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-mailgun-domain" value="<?php echo esc_attr( $mailgun_domain ); ?>" placeholder="mg.yourdomain.com">
					</div>
				</div>
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-mailgun-region">
							<?php esc_html_e( 'Region', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<select class="aisales-select" id="aisales-mail-provider-mailgun-region">
							<option value="us" <?php selected( 'us', $mailgun_region ); ?>><?php esc_html_e( 'US', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="eu" <?php selected( 'eu', $mailgun_region ); ?>><?php esc_html_e( 'EU', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-mailgun-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-mailgun-from-name" value="<?php echo esc_attr( $mailgun_from_name ); ?>">
					</div>
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-mailgun-from-email">
						<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-mailgun-from-email" value="<?php echo esc_attr( $mailgun_from_email ); ?>">
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'postmark' === $provider ? '' : ' is-hidden'; ?>" data-provider="postmark" id="aisales-mail-provider-postmark">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-postmark-token">
							<?php esc_html_e( 'Postmark Server Token', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-postmark-token" value="<?php echo esc_attr( $postmark_server_token ); ?>" autocomplete="new-password">
					</div>
				</div>
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-postmark-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-postmark-from-name" value="<?php echo esc_attr( $postmark_from_name ); ?>">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-postmark-from-email">
							<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="email" id="aisales-mail-provider-postmark-from-email" value="<?php echo esc_attr( $postmark_from_email ); ?>">
					</div>
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'ses' === $provider ? '' : ' is-hidden'; ?>" data-provider="ses" id="aisales-mail-provider-ses">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-ses-access">
							<?php esc_html_e( 'SES Access Key', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-ses-access" value="<?php echo esc_attr( $ses_access_key ); ?>">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-ses-secret">
							<?php esc_html_e( 'SES Secret Key', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-ses-secret" value="<?php echo esc_attr( $ses_secret_key ); ?>" autocomplete="new-password">
					</div>
				</div>
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-ses-region">
							<?php esc_html_e( 'SES Region', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-ses-region" value="<?php echo esc_attr( $ses_region ); ?>" placeholder="us-east-1">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-ses-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-ses-from-name" value="<?php echo esc_attr( $ses_from_name ); ?>">
					</div>
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-ses-from-email">
						<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-ses-from-email" value="<?php echo esc_attr( $ses_from_email ); ?>">
				</div>
			</div>

			<div class="aisales-mail-provider-panel<?php echo 'smtp' === $provider ? '' : ' is-hidden'; ?>" data-provider="smtp" id="aisales-mail-provider-smtp">
				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-host">
							<?php esc_html_e( 'SMTP Host', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-host" value="<?php echo esc_attr( $smtp_host ); ?>" placeholder="smtp.yourprovider.com">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-port">
							<?php esc_html_e( 'SMTP Port', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="number" id="aisales-mail-provider-port" value="<?php echo esc_attr( $smtp_port ); ?>" min="1" max="65535">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-encryption">
							<?php esc_html_e( 'Encryption', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<select class="aisales-select" id="aisales-mail-provider-encryption">
							<option value="tls" <?php selected( 'tls', $smtp_encryption ); ?>><?php esc_html_e( 'TLS', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="ssl" <?php selected( 'ssl', $smtp_encryption ); ?>><?php esc_html_e( 'SSL', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="none" <?php selected( 'none', $smtp_encryption ); ?>><?php esc_html_e( 'None', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
					</div>
				</div>

				<div class="aisales-form-group aisales-mail-provider-auth">
					<label class="aisales-checkbox-label">
						<input type="checkbox" id="aisales-mail-provider-auth" <?php checked( $smtp_auth ); ?>>
						<span><?php esc_html_e( 'Use SMTP authentication', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</label>
				</div>

				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-username">
							<?php esc_html_e( 'SMTP Username', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-username" value="<?php echo esc_attr( $smtp_username ); ?>" autocomplete="off">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-password">
							<?php esc_html_e( 'SMTP Password', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="password" id="aisales-mail-provider-password" value="<?php echo esc_attr( $smtp_password ); ?>" autocomplete="new-password">
					</div>
				</div>

				<div class="aisales-form-grid">
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-from-name">
							<?php esc_html_e( 'From Name', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="text" id="aisales-mail-provider-from-name" value="<?php echo esc_attr( $from_name ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					</div>
					<div class="aisales-form-group">
						<label class="aisales-label" for="aisales-mail-provider-from-email">
							<?php esc_html_e( 'From Email', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input class="aisales-input" type="email" id="aisales-mail-provider-from-email" value="<?php echo esc_attr( $from_email ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					</div>
				</div>
			</div>

			<div class="aisales-mail-provider-actions">
				<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-mail-provider-save">
					<span class="spinner"></span>
					<?php esc_html_e( 'Save Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
				<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-mail-provider-test">
					<span class="dashicons dashicons-email-alt"></span>
					<?php esc_html_e( 'Send Test Email', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<div class="aisales-card aisales-card--elevated aisales-mail-provider-help">
			<div class="aisales-mail-provider-help__icon">
				<span class="dashicons dashicons-info-outline"></span>
			</div>
			<div class="aisales-mail-provider-help__content">
				<h3><?php esc_html_e( 'Why configure SMTP?', 'ai-sales-manager-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'SMTP providers improve deliverability and reduce the chance of emails landing in spam.', 'ai-sales-manager-for-woocommerce' ); ?></p>
			</div>
		</div>
	</div>

	<?php include AISALES_PLUGIN_DIR . 'templates/partials/mail-provider-test-modal.php'; ?>
</div>
