<?php
/**
 * Brand Settings Page
 *
 * Dedicated admin page for managing store brand identity.
 * Provides empty state, AI analysis flow, and settings form.
 *
 * Variables passed from AISales_Brand_Page::render_page():
 * - $aisales_api_key (string) - API key for the service
 * - $aisales_balance (int) - Current token balance
 * - $aisales_store_context (array) - Saved store context settings
 * - $aisales_has_setup (bool) - Whether brand settings have been configured
 * - $aisales_detected_branding (array) - Auto-detected branding from extractor
 * - $aisales_industries (array) - Industry options for dropdown
 * - $aisales_tones (array) - Brand tone options
 * - $aisales_price_positions (array) - Price positioning options
 * - $aisales_promotion_styles (array) - Promotion style options
 * - $aisales_safe_fonts (array) - Email-safe font options
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Prepare current values with fallbacks.
$aisales_current_store_name      = $aisales_store_context['store_name'] ?? get_bloginfo( 'name' );
$aisales_current_tagline         = $aisales_store_context['tagline'] ?? get_bloginfo( 'description' );
$aisales_current_industry        = $aisales_store_context['business_niche'] ?? '';
$aisales_current_target_audience = $aisales_store_context['target_audience'] ?? '';
$aisales_current_price_position  = $aisales_store_context['price_position'] ?? '';
$aisales_current_differentiator  = $aisales_store_context['differentiator'] ?? '';
$aisales_current_pain_points     = $aisales_store_context['pain_points'] ?? '';
$aisales_current_tone            = $aisales_store_context['brand_tone'] ?? 'friendly';
$aisales_current_words_avoid     = $aisales_store_context['words_to_avoid'] ?? '';
$aisales_current_promo_style     = $aisales_store_context['promotion_style'] ?? 'moderate';
$aisales_current_primary_color   = $aisales_store_context['primary_color'] ?? $aisales_detected_branding['colors']['primary'] ?? '#7f54b3';
$aisales_current_text_color      = $aisales_store_context['text_color'] ?? $aisales_detected_branding['colors']['text'] ?? '#3c3c3c';
$aisales_current_bg_color        = $aisales_store_context['bg_color'] ?? $aisales_detected_branding['colors']['background'] ?? '#f7f7f7';
$aisales_current_font            = $aisales_store_context['font_family'] ?? $aisales_detected_branding['fonts']['body_slug'] ?? 'system';
?>

<div class="wrap aisales-admin-wrap aisales-brand-page">
	<!-- WordPress Admin Notices Area -->
	<h1 class="aisales-notices-anchor"></h1>

	<!-- Page Header -->
	<header class="aisales-brand-page__header">
		<div class="aisales-brand-page__header-left">
			<span class="aisales-brand-page__title">
				<span class="dashicons dashicons-art"></span>
				<?php esc_html_e( 'Brand Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-brand-page__header-right">
			<?php if ( ! empty( $aisales_api_key ) && $aisales_has_setup ) : ?>
				<!-- AI Re-analyze Button (pill style like balance indicator) -->
				<button type="button" class="aisales-btn aisales-btn--pill" id="aisales-brand-reanalyze-btn">
					<span class="dashicons dashicons-admin-customizer"></span>
					<?php esc_html_e( 'AI Re-analyze', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</button>
			<?php endif; ?>
			<!-- Balance Indicator -->
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
		</div>
	</header>

	<?php if ( empty( $aisales_api_key ) ) : ?>
		<!-- Not Connected State -->
		<div class="aisales-brand-page__not-connected">
			<div class="aisales-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<h2><?php esc_html_e( 'Not Connected', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Please connect your StackSuite Sales Manager account to configure brand settings.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="aisales-btn aisales-btn--primary">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Go to Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<?php if ( ! $aisales_has_setup ) : ?>
			<!-- Empty State (First Visit) -->
			<div class="aisales-brand-page__empty-state" id="aisales-brand-empty-state">
				<div class="aisales-brand-welcome">
					<div class="aisales-brand-welcome__icon">
						<span class="dashicons dashicons-art"></span>
					</div>
					<h2 class="aisales-brand-welcome__title"><?php esc_html_e( "Let's set up your brand identity", 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
					<p class="aisales-brand-welcome__description">
						<?php esc_html_e( "Your brand settings help our AI generate content that matches your store's unique voice and style. This ensures consistent, on-brand emails and product descriptions.", 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</p>
					<div class="aisales-brand-welcome__actions">
						<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--lg" id="aisales-brand-analyze-btn">
							<span class="dashicons dashicons-admin-customizer"></span>
							<?php esc_html_e( 'AI Analyze My Store', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-btn aisales-btn--ghost" id="aisales-brand-manual-btn">
							<?php esc_html_e( 'Set up manually', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
					<div class="aisales-brand-welcome__features">
						<div class="aisales-brand-welcome__feature">
							<span class="dashicons dashicons-yes-alt"></span>
							<span><?php esc_html_e( 'Detects your industry and niche', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-brand-welcome__feature">
							<span class="dashicons dashicons-yes-alt"></span>
							<span><?php esc_html_e( 'Identifies your target audience', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-brand-welcome__feature">
							<span class="dashicons dashicons-yes-alt"></span>
							<span><?php esc_html_e( 'Suggests an appropriate brand tone', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Analyzing State (Always in DOM for re-analyze) -->
		<div class="aisales-brand-page__analyzing-state" id="aisales-brand-analyzing-state" style="display: none;">
			<div class="aisales-brand-analyzing">
				<div class="aisales-brand-analyzing__spinner">
					<div class="aisales-spinner aisales-spinner--lg"></div>
				</div>
				<h2 class="aisales-brand-analyzing__title"><?php esc_html_e( 'Analyzing your store...', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
				<div class="aisales-brand-analyzing__steps">
					<div class="aisales-brand-analyzing__step" data-step="1">
						<span class="aisales-brand-analyzing__step-icon">
							<span class="dashicons dashicons-search"></span>
						</span>
						<span class="aisales-brand-analyzing__step-text"><?php esc_html_e( 'Gathering store information', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-brand-analyzing__step" data-step="2">
						<span class="aisales-brand-analyzing__step-icon">
							<span class="dashicons dashicons-admin-customizer"></span>
						</span>
						<span class="aisales-brand-analyzing__step-text"><?php esc_html_e( 'Analyzing brand characteristics', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-brand-analyzing__step" data-step="3">
						<span class="aisales-brand-analyzing__step-icon">
							<span class="dashicons dashicons-lightbulb"></span>
						</span>
						<span class="aisales-brand-analyzing__step-text"><?php esc_html_e( 'Generating suggestions', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Review State (Always in DOM for re-analyze) -->
		<div class="aisales-brand-page__review-state" id="aisales-brand-review-state" style="display: none;">
			<div class="aisales-brand-review">
				<div class="aisales-brand-review__header">
					<span class="dashicons dashicons-chart-area"></span>
					<div class="aisales-brand-review__header-text">
						<h2 class="aisales-brand-review__title"><?php esc_html_e( 'AI Analyze Result', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
						<p class="aisales-brand-review__description"><?php esc_html_e( 'Here is what we discovered about your store. Click Continue to customize these settings.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>
				</div>
				<div class="aisales-brand-review__content">
					<!-- Suggestions will be populated by JavaScript -->
					<div class="aisales-brand-review__suggestions" id="aisales-brand-suggestions"></div>
				</div>
				<div class="aisales-brand-review__actions">
					<button type="button" class="aisales-btn aisales-btn--ai aisales-btn--lg" id="aisales-brand-continue-btn">
						<?php esc_html_e( 'Continue', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						<span class="dashicons dashicons-arrow-right-alt"></span>
					</button>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Form State (Always in DOM, shown after setup or when has_setup) -->
	<div class="aisales-brand-page__form-state" id="aisales-brand-form-state" <?php echo ( ! $aisales_has_setup || empty( $aisales_api_key ) ) ? 'style="display: none;"' : ''; ?>>
		<form id="aisales-brand-form" class="aisales-brand-form">
			<div class="aisales-brand-settings">
				<!-- Unified Settings Header -->
				<div class="aisales-brand-settings__header">
					<span class="dashicons dashicons-art"></span>
					<div class="aisales-brand-settings__header-text">
						<h2 class="aisales-brand-settings__title"><?php esc_html_e( 'Brand Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?></h2>
						<p class="aisales-brand-settings__description"><?php esc_html_e( 'Configure your store identity, voice, and visual style for AI-generated content.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					</div>
				</div>

				<!-- Settings Content -->
				<div class="aisales-brand-settings__content">
					<!-- Store Identity Section -->
					<div class="aisales-settings-section">
						<h3 class="aisales-settings-section__title">
							<span class="dashicons dashicons-store"></span>
							<?php esc_html_e( 'Store Identity', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<div class="aisales-settings-section__content">
							<!-- Store Name -->
							<div class="aisales-form-group">
								<label for="aisales-store-name" class="aisales-form-label">
									<?php esc_html_e( 'Store Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<input type="text" 
									id="aisales-store-name" 
									name="store_name" 
									class="aisales-form-input" 
									value="<?php echo esc_attr( $aisales_current_store_name ); ?>"
									placeholder="<?php esc_attr_e( 'Your Store Name', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
							</div>

							<!-- Tagline -->
							<div class="aisales-form-group">
								<label for="aisales-tagline" class="aisales-form-label">
									<?php esc_html_e( 'Tagline / Value Proposition', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<input type="text" 
									id="aisales-tagline" 
									name="tagline" 
									class="aisales-form-input" 
									value="<?php echo esc_attr( $aisales_current_tagline ); ?>"
									placeholder="<?php esc_attr_e( 'e.g., Handcrafted coffee for the modern connoisseur', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
								<p class="aisales-form-help"><?php esc_html_e( 'A short phrase that captures your brand essence.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Industry -->
							<div class="aisales-form-group">
								<label for="aisales-industry" class="aisales-form-label">
									<?php esc_html_e( 'Industry', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<select id="aisales-industry" name="business_niche" class="aisales-form-select">
									<?php foreach ( $aisales_industries as $aisales_value => $aisales_label ) : ?>
										<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_current_industry, $aisales_value ); ?>>
											<?php echo esc_html( $aisales_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>

					<!-- Audience & Positioning Section -->
					<div class="aisales-settings-section">
						<h3 class="aisales-settings-section__title">
							<span class="dashicons dashicons-groups"></span>
							<?php esc_html_e( 'Audience & Positioning', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<div class="aisales-settings-section__content">
							<!-- Target Audience -->
							<div class="aisales-form-group">
								<label for="aisales-target-audience" class="aisales-form-label">
									<?php esc_html_e( 'Target Audience', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<textarea 
									id="aisales-target-audience" 
									name="target_audience" 
									class="aisales-form-textarea" 
									rows="2"
									placeholder="<?php esc_attr_e( 'e.g., Young professionals aged 25-35 who value quality and sustainability', 'stacksuite-sales-manager-for-woocommerce' ); ?>"><?php echo esc_textarea( $aisales_current_target_audience ); ?></textarea>
								<p class="aisales-form-help"><?php esc_html_e( 'Who are your ideal customers? Include demographics, interests, and values.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Price Positioning -->
							<div class="aisales-form-group">
								<label for="aisales-price-position" class="aisales-form-label">
									<?php esc_html_e( 'Price Positioning', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<select id="aisales-price-position" name="price_position" class="aisales-form-select">
									<?php foreach ( $aisales_price_positions as $aisales_value => $aisales_label ) : ?>
										<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_current_price_position, $aisales_value ); ?>>
											<?php echo esc_html( $aisales_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="aisales-form-help"><?php esc_html_e( 'This affects how AI discusses pricing and value in communications.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Business Differentiator -->
							<div class="aisales-form-group">
								<label for="aisales-differentiator" class="aisales-form-label">
									<?php esc_html_e( 'What Makes You Different?', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<textarea 
									id="aisales-differentiator" 
									name="differentiator" 
									class="aisales-form-textarea" 
									rows="2"
									placeholder="<?php esc_attr_e( 'e.g., We source directly from family farms and roast in small batches for peak freshness', 'stacksuite-sales-manager-for-woocommerce' ); ?>"><?php echo esc_textarea( $aisales_current_differentiator ); ?></textarea>
								<p class="aisales-form-help"><?php esc_html_e( 'Your unique selling points and competitive advantages.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Customer Pain Points -->
							<div class="aisales-form-group">
								<label for="aisales-pain-points" class="aisales-form-label">
									<?php esc_html_e( 'Customer Pain Points', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<textarea 
									id="aisales-pain-points" 
									name="pain_points" 
									class="aisales-form-textarea" 
									rows="2"
									placeholder="<?php esc_attr_e( 'e.g., Hard to find consistent quality, overwhelmed by choices, unsure what to buy', 'stacksuite-sales-manager-for-woocommerce' ); ?>"><?php echo esc_textarea( $aisales_current_pain_points ); ?></textarea>
								<p class="aisales-form-help"><?php esc_html_e( 'What problems does your store solve for customers?', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Brand Voice Section -->
					<div class="aisales-settings-section">
						<h3 class="aisales-settings-section__title">
							<span class="dashicons dashicons-megaphone"></span>
							<?php esc_html_e( 'Brand Voice', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<div class="aisales-settings-section__content">
							<!-- Brand Tone Selection -->
							<div class="aisales-form-group">
								<label class="aisales-form-label">
									<?php esc_html_e( 'Brand Tone', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<div class="aisales-tone-options">
									<?php foreach ( $aisales_tones as $aisales_value => $aisales_tone_data ) : ?>
										<label class="aisales-tone-option <?php echo $aisales_current_tone === $aisales_value ? 'aisales-tone-option--selected' : ''; ?>">
											<input type="radio" 
												name="brand_tone" 
												value="<?php echo esc_attr( $aisales_value ); ?>" 
												<?php checked( $aisales_current_tone, $aisales_value ); ?>>
											<span class="aisales-tone-option__icon">
												<span class="dashicons <?php echo esc_attr( $aisales_tone_data['icon'] ); ?>"></span>
											</span>
											<span class="aisales-tone-option__content">
												<span class="aisales-tone-option__label"><?php echo esc_html( $aisales_tone_data['label'] ); ?></span>
												<span class="aisales-tone-option__desc"><?php echo esc_html( $aisales_tone_data['description'] ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Words to Avoid -->
							<div class="aisales-form-group">
								<label for="aisales-words-avoid" class="aisales-form-label">
									<?php esc_html_e( 'Words or Phrases to Avoid', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<input type="text" 
									id="aisales-words-avoid" 
									name="words_to_avoid" 
									class="aisales-form-input" 
									value="<?php echo esc_attr( $aisales_current_words_avoid ); ?>"
									placeholder="<?php esc_attr_e( 'e.g., cheap, discount, budget, competitor names', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
								<p class="aisales-form-help"><?php esc_html_e( 'Comma-separated list of words AI should never use in your content.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Promotion Style -->
							<div class="aisales-form-group">
								<label class="aisales-form-label">
									<?php esc_html_e( 'Promotion Style', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<div class="aisales-tone-options aisales-promo-options">
									<?php foreach ( $aisales_promotion_styles as $aisales_value => $aisales_style_data ) : ?>
										<label class="aisales-tone-option <?php echo $aisales_current_promo_style === $aisales_value ? 'aisales-tone-option--selected' : ''; ?>">
											<input type="radio" 
												name="promotion_style" 
												value="<?php echo esc_attr( $aisales_value ); ?>" 
												<?php checked( $aisales_current_promo_style, $aisales_value ); ?>>
											<span class="aisales-tone-option__icon">
												<span class="dashicons <?php echo esc_attr( $aisales_style_data['icon'] ); ?>"></span>
											</span>
											<span class="aisales-tone-option__content">
												<span class="aisales-tone-option__label"><?php echo esc_html( $aisales_style_data['label'] ); ?></span>
												<span class="aisales-tone-option__desc"><?php echo esc_html( $aisales_style_data['description'] ); ?></span>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="aisales-form-help"><?php esc_html_e( 'How aggressively should AI use urgency and promotional language?', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Brand Style Section -->
					<div class="aisales-settings-section">
						<h3 class="aisales-settings-section__title">
							<span class="dashicons dashicons-admin-appearance"></span>
							<?php esc_html_e( 'Brand Style', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<div class="aisales-settings-section__content">
							<!-- Color Pickers -->
							<div class="aisales-brand-colors">
								<div class="aisales-form-group aisales-form-group--color">
									<label for="aisales-primary-color" class="aisales-form-label">
										<?php esc_html_e( 'Primary Color', 'stacksuite-sales-manager-for-woocommerce' ); ?>
									</label>
									<input type="text" 
										id="aisales-primary-color" 
										name="primary_color" 
										class="aisales-color-picker" 
										value="<?php echo esc_attr( $aisales_current_primary_color ); ?>"
										data-default-color="<?php echo esc_attr( $aisales_detected_branding['colors']['primary'] ?? '#7f54b3' ); ?>">
								</div>
								<div class="aisales-form-group aisales-form-group--color">
									<label for="aisales-text-color" class="aisales-form-label">
										<?php esc_html_e( 'Text Color', 'stacksuite-sales-manager-for-woocommerce' ); ?>
									</label>
									<input type="text" 
										id="aisales-text-color" 
										name="text_color" 
										class="aisales-color-picker" 
										value="<?php echo esc_attr( $aisales_current_text_color ); ?>"
										data-default-color="<?php echo esc_attr( $aisales_detected_branding['colors']['text'] ?? '#3c3c3c' ); ?>">
								</div>
								<div class="aisales-form-group aisales-form-group--color">
									<label for="aisales-bg-color" class="aisales-form-label">
										<?php esc_html_e( 'Background Color', 'stacksuite-sales-manager-for-woocommerce' ); ?>
									</label>
									<input type="text" 
										id="aisales-bg-color" 
										name="bg_color" 
										class="aisales-color-picker" 
										value="<?php echo esc_attr( $aisales_current_bg_color ); ?>"
										data-default-color="<?php echo esc_attr( $aisales_detected_branding['colors']['background'] ?? '#f7f7f7' ); ?>">
								</div>
							</div>

							<!-- Reset Colors Button -->
							<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm" id="aisales-reset-colors-btn">
								<span class="dashicons dashicons-image-rotate"></span>
								<?php esc_html_e( 'Reset to detected colors', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</button>

							<!-- Font Family -->
							<div class="aisales-form-group">
								<label for="aisales-font-family" class="aisales-form-label">
									<?php esc_html_e( 'Font Family', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</label>
								<select id="aisales-font-family" name="font_family" class="aisales-form-select">
									<?php foreach ( $aisales_safe_fonts as $aisales_slug => $aisales_font_data ) : ?>
										<option value="<?php echo esc_attr( $aisales_slug ); ?>" <?php selected( $aisales_current_font, $aisales_slug ); ?>>
											<?php echo esc_html( $aisales_font_data['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="aisales-form-help"><?php esc_html_e( 'Email-safe fonts that work across all email clients.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
							</div>

							<!-- Live Preview -->
							<div class="aisales-brand-preview">
								<label class="aisales-form-label"><?php esc_html_e( 'Preview', 'stacksuite-sales-manager-for-woocommerce' ); ?></label>
								<div class="aisales-brand-preview__box" id="aisales-brand-preview">
									<div class="aisales-brand-preview__header" id="aisales-preview-header">
										<?php echo esc_html( $aisales_current_store_name ); ?>
									</div>
									<div class="aisales-brand-preview__body" id="aisales-preview-body">
										<p class="aisales-brand-preview__heading"><?php esc_html_e( 'Thank you for your order!', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
										<p class="aisales-brand-preview__text"><?php esc_html_e( 'We appreciate your business and are excited to get your order to you.', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
										<button type="button" class="aisales-brand-preview__button" id="aisales-preview-button"><?php esc_html_e( 'View Order', 'stacksuite-sales-manager-for-woocommerce' ); ?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Settings Actions Footer -->
				<div class="aisales-brand-settings__actions">
					<button type="submit" class="aisales-btn aisales-btn--ai aisales-btn--lg" id="aisales-brand-save-btn">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Save Settings', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>

	<?php if ( ! empty( $aisales_api_key ) ) : ?>
		<!-- Balance Modal (Shared Partial) -->
		<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-modal.php'; ?>
	<?php endif; ?>
</div>

<?php
// Note: Brand data (detectedBranding, industries, tones) is passed via wp_localize_script()
// in AISales_Brand_Page::enqueue_scripts() as the 'aisalesBrand' object.
// This ensures compliance with WordPress plugin guidelines for proper script enqueuing.
?>
