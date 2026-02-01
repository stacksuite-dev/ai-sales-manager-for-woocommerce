<?php
/**
 * Email Settings Panel Partial
 *
 * Renders the mail provider settings form for embedding in Email Templates page.
 * Variables expected:
 * - $aisales_mail_settings (array) - Mail provider settings from AISales_Mail_Provider::get_settings()
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Helper to safely get nested array values with defaults.
$aisales_get = function ( $arr, $key, $default = '' ) {
	return isset( $arr[ $key ] ) ? $arr[ $key ] : $default;
};

// Extract provider settings with defaults.
$aisales_provider = $aisales_get( $aisales_mail_settings, 'provider', 'default' );
$aisales_smtp     = is_array( $aisales_get( $aisales_mail_settings, 'smtp' ) ) ? $aisales_mail_settings['smtp'] : array();
$aisales_sendgrid = is_array( $aisales_get( $aisales_mail_settings, 'sendgrid' ) ) ? $aisales_mail_settings['sendgrid'] : array();
$aisales_resend   = is_array( $aisales_get( $aisales_mail_settings, 'resend' ) ) ? $aisales_mail_settings['resend'] : array();
$aisales_mailgun  = is_array( $aisales_get( $aisales_mail_settings, 'mailgun' ) ) ? $aisales_mail_settings['mailgun'] : array();
$aisales_postmark = is_array( $aisales_get( $aisales_mail_settings, 'postmark' ) ) ? $aisales_mail_settings['postmark'] : array();
$aisales_ses      = is_array( $aisales_get( $aisales_mail_settings, 'ses' ) ) ? $aisales_mail_settings['ses'] : array();

// SMTP settings.
$aisales_smtp_host       = $aisales_get( $aisales_smtp, 'host' );
$aisales_smtp_port       = (int) $aisales_get( $aisales_smtp, 'port', 587 );
$aisales_smtp_encryption = $aisales_get( $aisales_smtp, 'encryption', 'tls' );
$aisales_smtp_auth       = ! empty( $aisales_smtp['auth'] );
$aisales_smtp_username   = $aisales_get( $aisales_smtp, 'username' );
$aisales_smtp_password   = $aisales_get( $aisales_smtp, 'password' );
$aisales_from_email      = $aisales_get( $aisales_smtp, 'from_email' );
$aisales_from_name       = $aisales_get( $aisales_smtp, 'from_name' );

// SendGrid settings.
$aisales_sendgrid_api_key    = $aisales_get( $aisales_sendgrid, 'api_key' );
$aisales_sendgrid_from_email = $aisales_get( $aisales_sendgrid, 'from_email' );
$aisales_sendgrid_from_name  = $aisales_get( $aisales_sendgrid, 'from_name' );

// Resend settings.
$aisales_resend_api_key    = $aisales_get( $aisales_resend, 'api_key' );
$aisales_resend_domain     = $aisales_get( $aisales_resend, 'domain' );
$aisales_resend_from_email = $aisales_get( $aisales_resend, 'from_email' );
$aisales_resend_from_name  = $aisales_get( $aisales_resend, 'from_name' );

// Mailgun settings.
$aisales_mailgun_api_key    = $aisales_get( $aisales_mailgun, 'api_key' );
$aisales_mailgun_domain     = $aisales_get( $aisales_mailgun, 'domain' );
$aisales_mailgun_region     = $aisales_get( $aisales_mailgun, 'region', 'us' );
$aisales_mailgun_from_email = $aisales_get( $aisales_mailgun, 'from_email' );
$aisales_mailgun_from_name  = $aisales_get( $aisales_mailgun, 'from_name' );

// Postmark settings.
$aisales_postmark_server_token = $aisales_get( $aisales_postmark, 'server_token' );
$aisales_postmark_from_email   = $aisales_get( $aisales_postmark, 'from_email' );
$aisales_postmark_from_name    = $aisales_get( $aisales_postmark, 'from_name' );

// Amazon SES settings.
$aisales_ses_access_key = $aisales_get( $aisales_ses, 'access_key' );
$aisales_ses_secret_key = $aisales_get( $aisales_ses, 'secret_key' );
$aisales_ses_region     = $aisales_get( $aisales_ses, 'region' );
$aisales_ses_from_email = $aisales_get( $aisales_ses, 'from_email' );
$aisales_ses_from_name  = $aisales_get( $aisales_ses, 'from_name' );
?>

<div class="aisales-email-settings-panel">
	<div class="aisales-card aisales-card--elevated aisales-mail-provider-card">
		<div class="aisales-mail-provider-card__header">
			<h2><?php esc_html_e( 'Provider Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
			<p class="aisales-text-muted">
				<?php esc_html_e( 'Use your preferred SMTP service for reliable delivery, or keep WordPress defaults.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</p>
		</div>

		<div class="aisales-form-group">
			<label class="aisales-label">
				<?php esc_html_e( 'Mail Provider', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</label>
			<div class="aisales-mail-provider-options">
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="default" <?php checked( 'default', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Default WordPress Mail', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Send mail using your host\'s configured mailer.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="sendgrid" <?php checked( 'sendgrid', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'SendGrid', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Popular API-based delivery with strong analytics.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="resend" <?php checked( 'resend', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Resend', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Modern email API with clean deliverability.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="mailgun" <?php checked( 'mailgun', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Mailgun', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Reliable SMTP/API delivery for transactional mail.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="postmark" <?php checked( 'postmark', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Postmark', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Fast delivery with dedicated transactional focus.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="ses" <?php checked( 'ses', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'Amazon SES', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'AWS-native email delivery with high volume support.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="aisales-mail-provider-option">
					<input type="radio" name="aisales-mail-provider" value="smtp" <?php checked( 'smtp', $aisales_provider ); ?>>
					<span class="aisales-mail-provider-option__content">
						<span class="aisales-mail-provider-option__title"><?php esc_html_e( 'SMTP (Recommended)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-mail-provider-option__desc"><?php esc_html_e( 'Connect a third-party mail service for higher deliverability.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</span>
				</label>
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'sendgrid' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="sendgrid" id="aisales-mail-provider-sendgrid">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-sendgrid-key">
						<?php esc_html_e( 'SendGrid API Key', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-sendgrid-key" value="<?php echo esc_attr( $aisales_sendgrid_api_key ); ?>" autocomplete="new-password">
				</div>
			</div>
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-sendgrid-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-sendgrid-from-name" value="<?php echo esc_attr( $aisales_sendgrid_from_name ); ?>">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-sendgrid-from-email">
						<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-sendgrid-from-email" value="<?php echo esc_attr( $aisales_sendgrid_from_email ); ?>">
				</div>
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'resend' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="resend" id="aisales-mail-provider-resend">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-resend-key">
						<?php esc_html_e( 'Resend API Key', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-resend-key" value="<?php echo esc_attr( $aisales_resend_api_key ); ?>" autocomplete="new-password">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-resend-domain">
						<?php esc_html_e( 'Verified Domain', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-resend-domain" value="<?php echo esc_attr( $aisales_resend_domain ); ?>" placeholder="yourdomain.com">
				</div>
			</div>
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-resend-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-resend-from-name" value="<?php echo esc_attr( $aisales_resend_from_name ); ?>">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-resend-from-email">
						<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-resend-from-email" value="<?php echo esc_attr( $aisales_resend_from_email ); ?>">
				</div>
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'mailgun' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="mailgun" id="aisales-mail-provider-mailgun">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-mailgun-key">
						<?php esc_html_e( 'Mailgun API Key', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-mailgun-key" value="<?php echo esc_attr( $aisales_mailgun_api_key ); ?>" autocomplete="new-password">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-mailgun-domain">
						<?php esc_html_e( 'Mailgun Domain', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-mailgun-domain" value="<?php echo esc_attr( $aisales_mailgun_domain ); ?>" placeholder="mg.yourdomain.com">
				</div>
			</div>
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-mailgun-region">
						<?php esc_html_e( 'Region', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<select class="aisales-select" id="aisales-mail-provider-mailgun-region">
						<option value="us" <?php selected( 'us', $aisales_mailgun_region ); ?>><?php esc_html_e( 'US', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
						<option value="eu" <?php selected( 'eu', $aisales_mailgun_region ); ?>><?php esc_html_e( 'EU', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
					</select>
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-mailgun-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-mailgun-from-name" value="<?php echo esc_attr( $aisales_mailgun_from_name ); ?>">
				</div>
			</div>
			<div class="aisales-form-group">
				<label class="aisales-label" for="aisales-mail-provider-mailgun-from-email">
					<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<input class="aisales-input" type="email" id="aisales-mail-provider-mailgun-from-email" value="<?php echo esc_attr( $aisales_mailgun_from_email ); ?>">
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'postmark' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="postmark" id="aisales-mail-provider-postmark">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-postmark-token">
						<?php esc_html_e( 'Postmark Server Token', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-postmark-token" value="<?php echo esc_attr( $aisales_postmark_server_token ); ?>" autocomplete="new-password">
				</div>
			</div>
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-postmark-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-postmark-from-name" value="<?php echo esc_attr( $aisales_postmark_from_name ); ?>">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-postmark-from-email">
						<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-postmark-from-email" value="<?php echo esc_attr( $aisales_postmark_from_email ); ?>">
				</div>
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'ses' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="ses" id="aisales-mail-provider-ses">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-ses-access">
						<?php esc_html_e( 'SES Access Key', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-ses-access" value="<?php echo esc_attr( $aisales_ses_access_key ); ?>">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-ses-secret">
						<?php esc_html_e( 'SES Secret Key', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-ses-secret" value="<?php echo esc_attr( $aisales_ses_secret_key ); ?>" autocomplete="new-password">
				</div>
			</div>
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-ses-region">
						<?php esc_html_e( 'SES Region', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-ses-region" value="<?php echo esc_attr( $aisales_ses_region ); ?>" placeholder="us-east-1">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-ses-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-ses-from-name" value="<?php echo esc_attr( $aisales_ses_from_name ); ?>">
				</div>
			</div>
			<div class="aisales-form-group">
				<label class="aisales-label" for="aisales-mail-provider-ses-from-email">
					<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</label>
				<input class="aisales-input" type="email" id="aisales-mail-provider-ses-from-email" value="<?php echo esc_attr( $aisales_ses_from_email ); ?>">
			</div>
		</div>

		<div class="aisales-mail-provider-panel<?php echo 'smtp' === $aisales_provider ? '' : ' is-hidden'; ?>" data-provider="smtp" id="aisales-mail-provider-smtp">
			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-host">
						<?php esc_html_e( 'SMTP Host', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-host" value="<?php echo esc_attr( $aisales_smtp_host ); ?>" placeholder="smtp.yourprovider.com">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-port">
						<?php esc_html_e( 'SMTP Port', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="number" id="aisales-mail-provider-port" value="<?php echo esc_attr( $aisales_smtp_port ); ?>" min="1" max="65535">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-encryption">
						<?php esc_html_e( 'Encryption', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<select class="aisales-select" id="aisales-mail-provider-encryption">
						<option value="tls" <?php selected( 'tls', $aisales_smtp_encryption ); ?>><?php esc_html_e( 'TLS', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
						<option value="ssl" <?php selected( 'ssl', $aisales_smtp_encryption ); ?>><?php esc_html_e( 'SSL', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
						<option value="none" <?php selected( 'none', $aisales_smtp_encryption ); ?>><?php esc_html_e( 'None', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
					</select>
				</div>
			</div>

			<div class="aisales-form-group aisales-mail-provider-auth">
				<label class="aisales-checkbox-label">
					<input type="checkbox" id="aisales-mail-provider-auth" <?php checked( $aisales_smtp_auth ); ?>>
					<span><?php esc_html_e( 'Use SMTP authentication', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
				</label>
			</div>

			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-username">
						<?php esc_html_e( 'SMTP Username', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-username" value="<?php echo esc_attr( $aisales_smtp_username ); ?>" autocomplete="off">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-password">
						<?php esc_html_e( 'SMTP Password', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="password" id="aisales-mail-provider-password" value="<?php echo esc_attr( $aisales_smtp_password ); ?>" autocomplete="new-password">
				</div>
			</div>

			<div class="aisales-form-grid">
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-from-name">
						<?php esc_html_e( 'From Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="text" id="aisales-mail-provider-from-name" value="<?php echo esc_attr( $aisales_from_name ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				</div>
				<div class="aisales-form-group">
					<label class="aisales-label" for="aisales-mail-provider-from-email">
						<?php esc_html_e( 'From Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</label>
					<input class="aisales-input" type="email" id="aisales-mail-provider-from-email" value="<?php echo esc_attr( $aisales_from_email ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
				</div>
			</div>
		</div>

		<div class="aisales-mail-provider-actions">
			<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-mail-provider-save">
				<span class="spinner"></span>
				<?php esc_html_e( 'Save Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-mail-provider-test">
				<span class="dashicons dashicons-email-alt"></span>
				<?php esc_html_e( 'Send Test Email', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>

	<div class="aisales-card aisales-card--elevated aisales-mail-provider-help">
		<div class="aisales-mail-provider-help__icon">
			<span class="dashicons dashicons-info-outline"></span>
		</div>
		<div class="aisales-mail-provider-help__content">
			<h3><?php esc_html_e( 'Why configure SMTP?', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'SMTP providers improve deliverability and reduce the chance of emails landing in spam.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
		</div>
	</div>
</div>

<?php include AISALES_PLUGIN_DIR . 'templates/partials/mail-provider-test-modal.php'; ?>
