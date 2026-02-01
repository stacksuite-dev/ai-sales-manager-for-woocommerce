<?php
/**
 * Email Template Wizard Modal
 *
 * First-time setup wizard for email template generation.
 * Guides users through brand context setup and template selection.
 *
 * Variables passed from parent:
 * - $aisales_templates (array) - Available template types
 * - $aisales_store_context (array) - Existing store context
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get branding data from extractor
$aisales_branding_extractor = AISales_Branding_Extractor::instance();
$aisales_detected_branding  = $aisales_branding_extractor->get_branding();
$aisales_safe_fonts         = $aisales_branding_extractor->get_safe_fonts();

// Business niche options
$aisales_niche_options = array(
	''            => __( 'Select your industry...', 'stacksuite-sales-manager-for-woocommerce' ),
	'fashion'     => __( 'Fashion & Apparel', 'stacksuite-sales-manager-for-woocommerce' ),
	'electronics' => __( 'Electronics & Tech', 'stacksuite-sales-manager-for-woocommerce' ),
	'home'        => __( 'Home & Garden', 'stacksuite-sales-manager-for-woocommerce' ),
	'beauty'      => __( 'Beauty & Cosmetics', 'stacksuite-sales-manager-for-woocommerce' ),
	'food'        => __( 'Food & Beverages', 'stacksuite-sales-manager-for-woocommerce' ),
	'health'      => __( 'Health & Wellness', 'stacksuite-sales-manager-for-woocommerce' ),
	'sports'      => __( 'Sports & Outdoors', 'stacksuite-sales-manager-for-woocommerce' ),
	'toys'        => __( 'Toys & Games', 'stacksuite-sales-manager-for-woocommerce' ),
	'jewelry'     => __( 'Jewelry & Accessories', 'stacksuite-sales-manager-for-woocommerce' ),
	'books'       => __( 'Books & Media', 'stacksuite-sales-manager-for-woocommerce' ),
	'automotive'  => __( 'Automotive', 'stacksuite-sales-manager-for-woocommerce' ),
	'pets'        => __( 'Pet Supplies', 'stacksuite-sales-manager-for-woocommerce' ),
	'crafts'      => __( 'Arts & Crafts', 'stacksuite-sales-manager-for-woocommerce' ),
	'services'    => __( 'Digital Services', 'stacksuite-sales-manager-for-woocommerce' ),
	'other'       => __( 'Other', 'stacksuite-sales-manager-for-woocommerce' ),
);

// Brand tone options with descriptions
$aisales_tone_options = array(
	'professional' => array(
		'icon'  => '👔',
		'label' => __( 'Professional', 'stacksuite-sales-manager-for-woocommerce' ),
		'desc'  => __( 'Formal, trustworthy, corporate', 'stacksuite-sales-manager-for-woocommerce' ),
	),
	'friendly'     => array(
		'icon'  => '😊',
		'label' => __( 'Friendly', 'stacksuite-sales-manager-for-woocommerce' ),
		'desc'  => __( 'Warm, approachable, helpful', 'stacksuite-sales-manager-for-woocommerce' ),
	),
	'casual'       => array(
		'icon'  => '✌️',
		'label' => __( 'Casual', 'stacksuite-sales-manager-for-woocommerce' ),
		'desc'  => __( 'Relaxed, conversational, fun', 'stacksuite-sales-manager-for-woocommerce' ),
	),
	'luxury'       => array(
		'icon'  => '✨',
		'label' => __( 'Luxury', 'stacksuite-sales-manager-for-woocommerce' ),
		'desc'  => __( 'Elegant, sophisticated, premium', 'stacksuite-sales-manager-for-woocommerce' ),
	),
);

// Default store context values
$aisales_default_context = array(
	'store_name'      => get_bloginfo( 'name' ),
	'business_niche'  => '',
	'brand_tone'      => 'friendly',
	'target_audience' => '',
	// Branding - merge detected values with any saved overrides
	'primary_color'   => $aisales_detected_branding['colors']['primary'] ?? '#7f54b3',
	'text_color'      => $aisales_detected_branding['colors']['text'] ?? '#3c3c3c',
	'bg_color'        => $aisales_detected_branding['colors']['background'] ?? '#f7f7f7',
	'font_family'     => $aisales_detected_branding['fonts']['body_slug'] ?? 'system',
	'logo_url'        => $aisales_detected_branding['logo']['url'] ?? '',
);
$aisales_context = wp_parse_args( $aisales_store_context, $aisales_default_context );

// Determine branding source for UI hint
$aisales_branding_source = $aisales_detected_branding['colors']['source'] ?? 'default';
$aisales_branding_source_label = array(
	'woocommerce'      => __( 'Imported from WooCommerce email settings', 'stacksuite-sales-manager-for-woocommerce' ),
	'block_theme'      => __( 'Imported from your theme', 'stacksuite-sales-manager-for-woocommerce' ),
	'theme_customizer' => __( 'Imported from theme customizer', 'stacksuite-sales-manager-for-woocommerce' ),
	'default'          => __( 'Using default colors', 'stacksuite-sales-manager-for-woocommerce' ),
);
?>

<!-- Wizard Overlay -->
<div class="aisales-wizard-overlay" id="aisales-wizard-overlay">
	<div class="aisales-wizard" id="aisales-email-wizard">
		<!-- Header -->
		<div class="aisales-wizard__header">
			<button type="button" class="aisales-wizard__close" aria-label="<?php esc_attr_e( 'Close wizard', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
			<h2 class="aisales-wizard__title">
				<span class="dashicons dashicons-email-alt"></span>
				<span><?php esc_html_e( 'Personalize Your Emails', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</h2>
			<p class="aisales-wizard__subtitle"><?php esc_html_e( 'Let\'s create emails that match your brand personality', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
		</div>

		<!-- Progress Indicator -->
		<div class="aisales-wizard__progress">
			<div class="aisales-wizard__progress-step aisales-wizard__progress-step--active" data-step="1">
				<span class="aisales-wizard__progress-dot"></span>
				<span class="aisales-wizard__progress-label"><?php esc_html_e( 'Brand', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-wizard__progress-connector"></div>
			<div class="aisales-wizard__progress-step" data-step="2">
				<span class="aisales-wizard__progress-dot"></span>
				<span class="aisales-wizard__progress-label"><?php esc_html_e( 'Templates', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-wizard__progress-connector"></div>
			<div class="aisales-wizard__progress-step" data-step="3">
				<span class="aisales-wizard__progress-dot"></span>
				<span class="aisales-wizard__progress-label"><?php esc_html_e( 'Generate', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
			<div class="aisales-wizard__progress-connector"></div>
			<div class="aisales-wizard__progress-step" data-step="4">
				<span class="aisales-wizard__progress-dot"></span>
				<span class="aisales-wizard__progress-label"><?php esc_html_e( 'Done', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
			</div>
		</div>

		<!-- Body / Steps -->
		<div class="aisales-wizard__body">
			<!-- Step 1: Brand Context -->
			<div class="aisales-wizard__step aisales-wizard__step--active" data-step="1">
				<div class="aisales-wizard__step-scroll">
					<div class="aisales-wizard__intro">
						<div class="aisales-wizard__intro-icon">
							<span class="dashicons dashicons-store"></span>
						</div>
						<h3><?php esc_html_e( 'Tell us about your store', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'This helps AI craft emails that sound like they\'re written by you.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>

					<div class="aisales-wizard__form">
						<!-- Store Name -->
						<div class="aisales-wizard__field">
							<label for="aisales-wizard-store-name"><?php esc_html_e( 'Store Name', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
							<input type="text" id="aisales-wizard-store-name" 
								value="<?php echo esc_attr( $aisales_context['store_name'] ); ?>" 
								placeholder="<?php esc_attr_e( 'e.g., Acme Store', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
						</div>

						<!-- Business Niche -->
						<div class="aisales-wizard__field">
							<label for="aisales-wizard-business-niche"><?php esc_html_e( 'What do you sell?', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
							<select id="aisales-wizard-business-niche">
								<?php foreach ( $aisales_niche_options as $aisales_value => $aisales_label ) : ?>
									<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_context['business_niche'], $aisales_value ); ?>>
										<?php echo esc_html( $aisales_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Brand Tone -->
						<div class="aisales-wizard__field">
							<label><?php esc_html_e( 'How should your emails sound?', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
							<div class="aisales-wizard__tone-grid">
								<?php foreach ( $aisales_tone_options as $aisales_value => $aisales_option ) : ?>
									<label class="aisales-wizard__tone-option">
										<input type="radio" name="wizard_brand_tone" value="<?php echo esc_attr( $aisales_value ); ?>" 
											<?php checked( $aisales_context['brand_tone'], $aisales_value ); ?>>
										<div class="aisales-wizard__tone-card">
											<span class="aisales-wizard__tone-icon"><?php echo esc_html( $aisales_option['icon'] ); ?></span>
											<span class="aisales-wizard__tone-name"><?php echo esc_html( $aisales_option['label'] ); ?></span>
											<span class="aisales-wizard__tone-desc"><?php echo esc_html( $aisales_option['desc'] ); ?></span>
										</div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Branding Section: Colors & Typography -->
						<div class="aisales-wizard__section">
							<div class="aisales-wizard__section-header">
								<span class="dashicons dashicons-art"></span>
								<span><?php esc_html_e( 'Brand Colors & Typography', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								<?php if ( 'default' !== $aisales_branding_source ) : ?>
									<span class="aisales-wizard__section-badge">
										<?php echo esc_html( $aisales_branding_source_label[ $aisales_branding_source ] ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="aisales-wizard__branding-grid">
								<!-- Primary Color -->
								<div class="aisales-wizard__color-field">
									<label for="aisales-wizard-primary-color"><?php esc_html_e( 'Primary Color', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
									<div class="aisales-wizard__color-input-wrap">
										<input type="color" id="aisales-wizard-primary-color" 
											value="<?php echo esc_attr( $aisales_context['primary_color'] ); ?>"
											class="aisales-wizard__color-picker">
										<input type="text" id="aisales-wizard-primary-color-hex" 
											value="<?php echo esc_attr( $aisales_context['primary_color'] ); ?>"
											class="aisales-wizard__color-hex"
											pattern="^#[0-9A-Fa-f]{6}$"
											maxlength="7">
									</div>
									<span class="aisales-wizard__color-hint"><?php esc_html_e( 'Used for buttons, links, and headers', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								</div>

								<!-- Text Color -->
								<div class="aisales-wizard__color-field">
									<label for="aisales-wizard-text-color"><?php esc_html_e( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
									<div class="aisales-wizard__color-input-wrap">
										<input type="color" id="aisales-wizard-text-color" 
											value="<?php echo esc_attr( $aisales_context['text_color'] ); ?>"
											class="aisales-wizard__color-picker">
										<input type="text" id="aisales-wizard-text-color-hex" 
											value="<?php echo esc_attr( $aisales_context['text_color'] ); ?>"
											class="aisales-wizard__color-hex"
											pattern="^#[0-9A-Fa-f]{6}$"
											maxlength="7">
									</div>
									<span class="aisales-wizard__color-hint"><?php esc_html_e( 'Main body text color', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								</div>

								<!-- Background Color -->
								<div class="aisales-wizard__color-field">
									<label for="aisales-wizard-bg-color"><?php esc_html_e( 'Background', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
									<div class="aisales-wizard__color-input-wrap">
										<input type="color" id="aisales-wizard-bg-color" 
											value="<?php echo esc_attr( $aisales_context['bg_color'] ); ?>"
											class="aisales-wizard__color-picker">
										<input type="text" id="aisales-wizard-bg-color-hex" 
											value="<?php echo esc_attr( $aisales_context['bg_color'] ); ?>"
											class="aisales-wizard__color-hex"
											pattern="^#[0-9A-Fa-f]{6}$"
											maxlength="7">
									</div>
									<span class="aisales-wizard__color-hint"><?php esc_html_e( 'Email wrapper background', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								</div>

								<!-- Font Family -->
								<div class="aisales-wizard__font-field">
									<label for="aisales-wizard-font-family"><?php esc_html_e( 'Font Family', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
									<select id="aisales-wizard-font-family" class="aisales-wizard__font-select">
										<?php foreach ( $aisales_safe_fonts as $aisales_slug => $aisales_font_data ) : ?>
											<option value="<?php echo esc_attr( $aisales_slug ); ?>" 
												<?php selected( $aisales_context['font_family'], $aisales_slug ); ?>
												data-family="<?php echo esc_attr( $aisales_font_data['family'] ); ?>">
												<?php echo esc_html( $aisales_font_data['label'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<div class="aisales-wizard__font-preview" id="aisales-wizard-font-preview">
										<?php esc_html_e( 'The quick brown fox jumps over the lazy dog.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
									</div>
								</div>
							</div>

							<!-- Live Preview Mini -->
							<div class="aisales-wizard__branding-preview">
								<span class="aisales-wizard__preview-label"><?php esc_html_e( 'Preview', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								<div class="aisales-wizard__preview-email" id="aisales-wizard-email-preview">
									<div class="aisales-wizard__preview-header" id="aisales-preview-header">
										<?php echo esc_html( $aisales_context['store_name'] ); ?>
									</div>
									<div class="aisales-wizard__preview-body" id="aisales-preview-body">
										<p style="margin: 0 0 8px; font-weight: 600;"><?php esc_html_e( 'Thank you for your order!', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
										<p style="margin: 0; opacity: 0.8;"><?php esc_html_e( 'Your order #1234 has been confirmed.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
									</div>
									<div class="aisales-wizard__preview-button" id="aisales-preview-button">
										<?php esc_html_e( 'View Order', 'stacksuite-sales-manager-for-woocommerce' ); ?>
									</div>
								</div>
							</div>
						</div>

						<!-- Target Audience (Optional) -->
						<div class="aisales-wizard__field">
							<label for="aisales-wizard-target-audience"><?php esc_html_e( 'Who are your customers?', 'stacksuite-sales-manager-for-woocommerce' ); ?> <small>(<?php esc_html_e( 'optional', 'stacksuite-sales-manager-for-woocommerce' ); ?>)</small></label>
							<input type="text" id="aisales-wizard-target-audience" 
								value="<?php echo esc_attr( $aisales_context['target_audience'] ); ?>" 
								placeholder="<?php esc_attr_e( 'e.g., Young professionals, busy parents, fitness enthusiasts', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
							<span class="aisales-wizard__field-hint"><?php esc_html_e( 'Describing your audience helps personalize email tone and language.', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 2: Template Selection -->
			<div class="aisales-wizard__step" data-step="2">
				<div class="aisales-wizard__intro">
					<div class="aisales-wizard__intro-icon">
						<span class="dashicons dashicons-email-alt2"></span>
					</div>
					<h3><?php esc_html_e( 'Choose templates to generate', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'Select the email templates you\'d like AI to create for you.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
				</div>

				<div class="aisales-wizard__template-list">
					<?php foreach ( $aisales_templates as $aisales_type => $aisales_template_data ) : ?>
						<div class="aisales-wizard__template-item <?php echo $aisales_template_data['has_template'] ? 'is-disabled' : ''; ?>" 
							data-template-type="<?php echo esc_attr( $aisales_type ); ?>">
							<span class="aisales-wizard__template-checkbox">
								<span class="dashicons dashicons-yes"></span>
							</span>
							<div class="aisales-wizard__template-info">
								<span class="aisales-wizard__template-name"><?php echo esc_html( $aisales_template_data['label'] ); ?></span>
								<span class="aisales-wizard__template-desc"><?php echo esc_html( $aisales_template_data['description'] ); ?></span>
							</div>
							<?php if ( $aisales_template_data['has_template'] ) : ?>
								<span class="aisales-wizard__template-badge aisales-wizard__template-badge--existing">
									<?php esc_html_e( 'Exists', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="aisales-wizard__select-actions">
					<button type="button" class="aisales-wizard__select-btn" id="aisales-wizard-select-all">
						<?php esc_html_e( 'Select all missing', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-wizard__select-btn" id="aisales-wizard-select-none">
						<?php esc_html_e( 'Clear selection', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Step 3: Generation Progress -->
			<div class="aisales-wizard__step" data-step="3">
				<div class="aisales-wizard__generating">
					<div class="aisales-wizard__generating-animation">
						<div class="aisales-wizard__generating-ring"></div>
						<div class="aisales-wizard__generating-ring"></div>
						<span class="aisales-wizard__generating-icon">✨</span>
					</div>
					<h3><?php esc_html_e( 'Creating your personalized emails', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'AI is crafting each template based on your brand...', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>

					<div class="aisales-wizard__progress-list" id="aisales-wizard-progress-list">
						<!-- Populated by JavaScript -->
					</div>
				</div>
			</div>

			<!-- Step 4: Completion -->
			<div class="aisales-wizard__step" data-step="4">
				<div class="aisales-wizard__complete">
					<div class="aisales-wizard__complete-icon">
						<span class="dashicons dashicons-yes-alt"></span>
					</div>
					<h3><?php esc_html_e( 'Your emails are ready!', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'We\'ve created personalized email templates for your store. You can edit and customize them anytime.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>

					<div class="aisales-wizard__summary">
						<div class="aisales-wizard__summary-stat">
							<span class="aisales-wizard__summary-value" id="aisales-wizard-success-count">0</span>
							<span class="aisales-wizard__summary-label"><?php esc_html_e( 'Generated', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-wizard__summary-stat">
							<span class="aisales-wizard__summary-value" id="aisales-wizard-error-count">0</span>
							<span class="aisales-wizard__summary-label"><?php esc_html_e( 'Failed', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Footer -->
		<div class="aisales-wizard__footer">
			<div class="aisales-wizard__footer-left">
				<button type="button" class="aisales-wizard__skip-btn">
					<?php esc_html_e( 'Skip setup', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>
			<div class="aisales-wizard__footer-right">
				<button type="button" class="aisales-wizard__btn aisales-wizard__btn--secondary" id="aisales-wizard-prev" style="display: none;">
					<span class="dashicons dashicons-arrow-left-alt"></span>
					<span><?php esc_html_e( 'Back', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
				</button>
				<button type="button" class="aisales-wizard__btn aisales-wizard__btn--primary" id="aisales-wizard-next">
					<span><?php esc_html_e( 'Continue', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt"></span>
				</button>
				<button type="button" class="aisales-wizard__btn aisales-wizard__btn--success" id="aisales-wizard-finish" style="display: none;">
					<span class="dashicons dashicons-yes-alt"></span>
					<span><?php esc_html_e( 'View Templates', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
				</button>
			</div>
		</div>
	</div>
</div>
