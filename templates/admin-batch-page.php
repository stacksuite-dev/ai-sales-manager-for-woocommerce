<?php
/**
 * Bulk Enhancement Page Template
 *
 * Variables passed from AISales_Batch_Page::render_page():
 * - $balance (int) - Current token balance
 * - $store_context (array) - Store context settings
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap aisales-admin-wrap aisales-batch-wrap">
	<!-- WordPress Admin Notices Area -->
	<h1 class="aisales-notices-anchor"></h1>

	<!-- Page Header -->
	<header class="aisales-batch-header">
		<div class="aisales-batch-header__left">
			<span class="aisales-batch-title">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Manage Catalog', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
			<span class="aisales-batch-subtitle">
				<?php esc_html_e( 'AI-powered product optimization at scale', 'stacksuite-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-batch-header__right">
			<!-- Balance Indicator -->
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
		</div>
	</header>

	<!-- Step Indicator -->
	<div class="aisales-batch-steps">
		<div class="aisales-batch-step aisales-batch-step--active" data-step="1">
			<span class="aisales-batch-step__number">1</span>
			<span class="aisales-batch-step__label"><?php esc_html_e( 'Select Products', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-batch-step__connector"></div>
		<div class="aisales-batch-step" data-step="2">
			<span class="aisales-batch-step__number">2</span>
			<span class="aisales-batch-step__label"><?php esc_html_e( 'Configure', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-batch-step__connector"></div>
		<div class="aisales-batch-step" data-step="3">
			<span class="aisales-batch-step__number">3</span>
			<span class="aisales-batch-step__label"><?php esc_html_e( 'Preview & Refine', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-batch-step__connector"></div>
		<div class="aisales-batch-step" data-step="4">
			<span class="aisales-batch-step__number">4</span>
			<span class="aisales-batch-step__label"><?php esc_html_e( 'Process', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-batch-step__connector"></div>
		<div class="aisales-batch-step" data-step="5">
			<span class="aisales-batch-step__number">5</span>
			<span class="aisales-batch-step__label"><?php esc_html_e( 'Apply', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
		</div>
	</div>

	<!-- Main Content Container -->
	<div class="aisales-batch-content">
		
		<!-- Step 1: Select Products -->
		<div class="aisales-batch-panel" id="aisales-step-1" data-step="1">
			<div class="aisales-card">
				<div class="aisales-card__header">
					<h3>
						<span class="dashicons dashicons-products"></span>
						<?php esc_html_e( 'Select Products to Enhance', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</h3>
					<div class="aisales-card__header-action">
						<span class="aisales-selected-count">
							<span id="aisales-selected-count">0</span> <?php esc_html_e( 'selected', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
				</div>
				<div class="aisales-card__body">
					<!-- Filters -->
					<div class="aisales-batch-filters">
						<div class="aisales-batch-filter">
							<input type="text" id="aisales-product-search" class="aisales-input" placeholder="<?php esc_attr_e( 'Search products...', 'stacksuite-sales-manager-for-woocommerce' ); ?>">
						</div>
						<div class="aisales-batch-filter">
							<select id="aisales-category-filter" class="aisales-select">
								<option value=""><?php esc_html_e( 'All Categories', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
							</select>
						</div>
						<div class="aisales-batch-filter">
							<select id="aisales-status-filter" class="aisales-select">
								<option value=""><?php esc_html_e( 'All Statuses', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
								<option value="publish"><?php esc_html_e( 'Published', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
								<option value="pending"><?php esc_html_e( 'Pending', 'stacksuite-sales-manager-for-woocommerce' ); ?></option>
							</select>
						</div>
						<div class="aisales-batch-filter-actions">
							<button type="button" class="aisales-btn aisales-btn--secondary aisales-btn--sm" id="aisales-select-all">
								<?php esc_html_e( 'Select All', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</button>
							<button type="button" class="aisales-btn aisales-btn--secondary aisales-btn--sm" id="aisales-deselect-all">
								<?php esc_html_e( 'Deselect All', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
					</div>

					<!-- Products Grid -->
					<div class="aisales-batch-products" id="aisales-products-grid">
						<!-- Products will be rendered here by JavaScript -->
						<div class="aisales-batch-products__loading">
							<span class="spinner is-active"></span>
							<?php esc_html_e( 'Loading products...', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 1 Actions -->
			<div class="aisales-batch-actions">
				<div class="aisales-batch-actions__left">
					<span class="aisales-batch-info">
						<?php esc_html_e( 'Select at least one product to continue', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="aisales-batch-actions__right">
					<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-step1-next" disabled>
						<?php esc_html_e( 'Next: Configure Enhancements', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</button>
				</div>
			</div>
		</div>

		<!-- Step 2: Configure Enhancements -->
		<div class="aisales-batch-panel" id="aisales-step-2" data-step="2" style="display: none;">
			<div class="aisales-batch-config-grid">
				<!-- Enhancement Types -->
				<div class="aisales-card">
					<div class="aisales-card__header">
						<h3>
							<span class="dashicons dashicons-admin-tools"></span>
							<?php esc_html_e( 'What to Enhance', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
					</div>
					<div class="aisales-card__body">
						<div class="aisales-enhancement-options">
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="description" checked>
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-text"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'Product Description', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Full product description with features and benefits', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="short_description" checked>
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-editor-paragraph"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'Short Description', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Brief summary for product listings', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="seo_title">
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-search"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'SEO Title', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Optimized page title for search engines', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="seo_description">
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-media-text"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'SEO Meta Description', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Compelling meta description for search results', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="tags">
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-tag"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'Product Tags', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Relevant tags for better discoverability', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="categories">
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-category"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'Category Suggestions', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Better category placement recommendations', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
							<label class="aisales-enhancement-option">
								<input type="checkbox" name="enhancements[]" value="image_alt">
								<div class="aisales-enhancement-option__content">
									<span class="aisales-enhancement-option__icon">
										<span class="dashicons dashicons-format-image"></span>
									</span>
									<div class="aisales-enhancement-option__info">
										<strong><?php esc_html_e( 'Image Alt Text', 'stacksuite-sales-manager-for-woocommerce' ); ?></strong>
										<span><?php esc_html_e( 'Accessible and SEO-friendly alt text', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</div>
								</div>
							</label>
						</div>
					</div>
				</div>

				<!-- Direction Input -->
				<div class="aisales-card">
					<div class="aisales-card__header">
						<h3>
							<span class="dashicons dashicons-lightbulb"></span>
							<?php esc_html_e( 'Your Direction', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
					</div>
					<div class="aisales-card__body">
						<div class="aisales-form-group">
							<label class="aisales-label" for="aisales-direction">
								<?php esc_html_e( 'Tell AI what you want (optional)', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</label>
							<textarea 
								id="aisales-direction" 
								class="aisales-textarea" 
								rows="4"
								placeholder="<?php esc_attr_e( 'e.g., "Focus on luxury and premium quality. Use sophisticated language. Emphasize craftsmanship and attention to detail."', 'stacksuite-sales-manager-for-woocommerce' ); ?>"
							></textarea>
							<span class="aisales-help-text">
								<?php esc_html_e( 'Describe the tone, style, or specific aspects you want the AI to focus on.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</span>
						</div>

						<!-- Token Estimate -->
						<div class="aisales-token-estimate" id="aisales-token-estimate">
							<div class="aisales-token-estimate__header">
								<span class="dashicons dashicons-calculator"></span>
								<?php esc_html_e( 'Estimated Cost', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</div>
							<div class="aisales-token-estimate__body">
								<div class="aisales-token-estimate__row">
									<span><?php esc_html_e( 'Products:', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									<span id="aisales-estimate-products">0</span>
								</div>
								<div class="aisales-token-estimate__row">
									<span><?php esc_html_e( 'Enhancements:', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									<span id="aisales-estimate-enhancements">0</span>
								</div>
								<div class="aisales-token-estimate__row aisales-token-estimate__row--total">
									<span><?php esc_html_e( 'Est. Tokens:', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									<span id="aisales-estimate-tokens">~0</span>
								</div>
								<div class="aisales-token-estimate__row">
									<span><?php esc_html_e( 'Your Balance:', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									<span id="aisales-estimate-balance"><?php echo esc_html( number_format( $balance ) ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 2 Actions -->
			<div class="aisales-batch-actions">
				<div class="aisales-batch-actions__left">
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-step2-back">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php esc_html_e( 'Back', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
				<div class="aisales-batch-actions__right">
					<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-step2-next" disabled>
						<?php esc_html_e( 'Generate Preview', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</button>
				</div>
			</div>
		</div>

		<!-- Step 3: Preview & Refine -->
		<div class="aisales-batch-panel" id="aisales-step-3" data-step="3" style="display: none;">
			<!-- Preview Loading State -->
			<div class="aisales-batch-preview-loading" id="aisales-preview-loading">
				<div class="aisales-card aisales-card--centered">
					<div class="aisales-batch-loading-animation">
						<span class="dashicons dashicons-update aisales-spin"></span>
					</div>
					<h3><?php esc_html_e( 'Generating Preview...', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
					<p id="aisales-preview-status"><?php esc_html_e( 'Initializing...', 'stacksuite-sales-manager-for-woocommerce' ); ?></p>
					<div class="aisales-progress">
						<div class="aisales-progress__fill aisales-progress__fill--brand" id="aisales-preview-progress" style="width: 0%"></div>
					</div>
				</div>
			</div>

			<!-- Preview Results -->
			<div class="aisales-batch-preview-results" id="aisales-preview-results" style="display: none;">
				<div class="aisales-card">
					<div class="aisales-card__header">
						<h3>
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Preview Results', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<div class="aisales-card__header-action">
							<span class="aisales-badge aisales-badge--info" id="aisales-preview-count-badge">
								<?php esc_html_e( 'sample products', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</span>
						</div>
					</div>
					<div class="aisales-card__body">
						<p class="aisales-preview-intro">
							<?php esc_html_e( 'Review how AI enhanced these sample products. You can refine the direction if needed.', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</p>

						<!-- Preview Products Tabs -->
						<div class="aisales-preview-tabs" id="aisales-preview-tabs">
							<!-- Tabs will be rendered here by JavaScript -->
						</div>

						<!-- Preview Content -->
						<div class="aisales-preview-content" id="aisales-preview-content">
							<!-- Content will be rendered here by JavaScript -->
						</div>
					</div>
				</div>

				<!-- Refinement Panel (Collapsible) -->
				<div class="aisales-card aisales-card--collapsible" id="aisales-refinement-panel">
					<div class="aisales-card__header aisales-card__header--clickable" id="aisales-refinement-toggle">
						<h3>
							<span class="dashicons dashicons-admin-settings"></span>
							<?php esc_html_e( 'Adjust Direction', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</h3>
						<span class="dashicons dashicons-arrow-down-alt2 aisales-collapse-icon"></span>
					</div>
					<div class="aisales-card__body aisales-collapsible-content" style="display: none;">
						<!-- Guided Refinement Options -->
						<div class="aisales-refinement-sections">
							<!-- Length & Structure -->
							<div class="aisales-refinement-section">
								<h4>
									<span class="dashicons dashicons-text-page"></span>
									<?php esc_html_e( 'Length & Structure', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</h4>
								<div class="aisales-refinement-options">
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[length_structure][]" value="shorter_descriptions">
										<span><?php esc_html_e( 'Shorter descriptions (under 100 words)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[length_structure][]" value="longer_descriptions">
										<span><?php esc_html_e( 'Longer descriptions (200-300 words)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[length_structure][]" value="use_bullet_points">
										<span><?php esc_html_e( 'Use bullet points for features', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[length_structure][]" value="add_paragraphs">
										<span><?php esc_html_e( 'Short paragraphs (2-3 sentences)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[length_structure][]" value="seo_title_60">
										<span><?php esc_html_e( 'SEO titles under 60 characters', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
								</div>
							</div>

							<!-- Tone & Style -->
							<div class="aisales-refinement-section">
								<h4>
									<span class="dashicons dashicons-format-status"></span>
									<?php esc_html_e( 'Tone & Style', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</h4>
								<div class="aisales-refinement-options">
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_professional">
										<span><?php esc_html_e( 'More professional/formal', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_casual">
										<span><?php esc_html_e( 'More casual/conversational', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_luxurious">
										<span><?php esc_html_e( 'More luxurious/premium', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_playful">
										<span><?php esc_html_e( 'More playful/energetic', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_technical">
										<span><?php esc_html_e( 'More technical/detailed', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tone_style][]" value="more_emotional">
										<span><?php esc_html_e( 'More emotional/storytelling', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
								</div>
							</div>

							<!-- Content Focus -->
							<div class="aisales-refinement-section">
								<h4>
									<span class="dashicons dashicons-editor-alignleft"></span>
									<?php esc_html_e( 'Content Focus', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</h4>
								<div class="aisales-refinement-options">
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="focus_benefits">
										<span><?php esc_html_e( 'Focus on customer benefits', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="focus_features">
										<span><?php esc_html_e( 'Focus on product features', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="add_urgency">
										<span><?php esc_html_e( 'Add urgency (limited time, etc.)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="remove_urgency">
										<span><?php esc_html_e( 'Remove urgency/pressure', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="emphasize_value">
										<span><?php esc_html_e( 'Emphasize value/savings', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="emphasize_quality">
										<span><?php esc_html_e( 'Emphasize quality/craftsmanship', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="add_social_proof">
										<span><?php esc_html_e( 'Add social proof (bestseller, popular)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[content_focus][]" value="eco_friendly">
										<span><?php esc_html_e( 'Eco-friendly/sustainable focus', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
								</div>
							</div>

							<!-- SEO Specific -->
							<div class="aisales-refinement-section">
								<h4>
									<span class="dashicons dashicons-search"></span>
									<?php esc_html_e( 'SEO Specific', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</h4>
								<div class="aisales-refinement-options">
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[seo_specific][]" value="product_name_first">
										<span><?php esc_html_e( 'Product name at start of SEO title', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[seo_specific][]" value="include_brand">
										<span><?php esc_html_e( 'Include brand name in titles', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[seo_specific][]" value="location_keywords">
										<span><?php esc_html_e( 'Add location/regional keywords', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[seo_specific][]" value="price_in_meta">
										<span><?php esc_html_e( 'Mention price in meta description', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[seo_specific][]" value="cta_in_meta">
										<span><?php esc_html_e( 'Call-to-action in meta description', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
								</div>
							</div>

							<!-- Tags & Categories -->
							<div class="aisales-refinement-section">
								<h4>
									<span class="dashicons dashicons-tag"></span>
									<?php esc_html_e( 'Tags & Categories', 'stacksuite-sales-manager-for-woocommerce' ); ?>
								</h4>
								<div class="aisales-refinement-options">
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tags_categories][]" value="fewer_tags">
										<span><?php esc_html_e( 'Fewer tags (max 5 per product)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tags_categories][]" value="more_tags">
										<span><?php esc_html_e( 'More tags (8-12 per product)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tags_categories][]" value="existing_tags_only">
										<span><?php esc_html_e( 'Only use existing store tags', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
									<label class="aisales-refinement-option">
										<input type="checkbox" name="refinement[tags_categories][]" value="conservative_categories">
										<span><?php esc_html_e( 'Conservative category changes', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									</label>
								</div>
							</div>
						</div>

						<!-- Additional Comments -->
						<div class="aisales-form-group">
							<label class="aisales-label" for="aisales-refinement-comments">
								<?php esc_html_e( 'Additional Comments', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</label>
							<textarea 
								id="aisales-refinement-comments" 
								class="aisales-textarea" 
								rows="3"
								placeholder="<?php esc_attr_e( 'Any other specific feedback or adjustments...', 'stacksuite-sales-manager-for-woocommerce' ); ?>"
							></textarea>
						</div>

						<!-- File Attachments -->
						<div class="aisales-form-group">
							<label class="aisales-label">
								<?php esc_html_e( 'Reference Files (optional)', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</label>
							<div class="aisales-file-upload">
								<input type="file" id="aisales-refinement-files" accept=".pdf,.png,.jpg,.jpeg,.webp,.txt,.md" multiple>
								<div class="aisales-file-upload__dropzone" id="aisales-dropzone">
									<span class="dashicons dashicons-upload"></span>
									<span><?php esc_html_e( 'Drop files here or click to upload', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
									<span class="aisales-file-upload__hint"><?php esc_html_e( 'PDF, images, text (max 10MB)', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
								</div>
								<div class="aisales-file-list" id="aisales-file-list"></div>
							</div>
						</div>

						<div class="aisales-refinement-actions">
							<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-regenerate-preview">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Regenerate Preview', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Step 3 Actions -->
			<div class="aisales-batch-actions" id="aisales-step3-actions" style="display: none;">
				<div class="aisales-batch-actions__left">
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-step3-back">
						<span class="dashicons dashicons-arrow-left-alt2"></span>
						<?php esc_html_e( 'Back', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--outline" id="aisales-cancel-batch">
						<?php esc_html_e( 'Cancel Batch', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
				<div class="aisales-batch-actions__right">
					<button type="button" class="aisales-btn aisales-btn--success" id="aisales-approve-preview">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Looks Good! Process All Products', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Step 4: Processing -->
		<div class="aisales-batch-panel" id="aisales-step-4" data-step="4" style="display: none;">
			<div class="aisales-card">
				<div class="aisales-card__header">
					<h3>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Processing Products', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</h3>
					<div class="aisales-card__header-action">
						<span class="aisales-badge aisales-badge--primary" id="aisales-process-status">
							<?php esc_html_e( 'Running', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
				</div>
				<div class="aisales-card__body">
					<!-- Progress Stats -->
					<div class="aisales-process-stats">
						<div class="aisales-process-stat">
							<span class="aisales-process-stat__value" id="aisales-processed-count">0</span>
							<span class="aisales-process-stat__label"><?php esc_html_e( 'Processed', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-process-stat aisales-process-stat--success">
							<span class="aisales-process-stat__value" id="aisales-success-count">0</span>
							<span class="aisales-process-stat__label"><?php esc_html_e( 'Successful', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-process-stat aisales-process-stat--danger">
							<span class="aisales-process-stat__value" id="aisales-failed-count">0</span>
							<span class="aisales-process-stat__label"><?php esc_html_e( 'Failed', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-process-stat">
							<span class="aisales-process-stat__value" id="aisales-tokens-count">0</span>
							<span class="aisales-process-stat__label"><?php esc_html_e( 'Tokens Used', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
					</div>

					<!-- Progress Bar -->
					<div class="aisales-process-progress">
						<div class="aisales-process-progress__header">
							<span id="aisales-current-product"><?php esc_html_e( 'Starting...', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
							<span><span id="aisales-progress-percent">0</span>%</span>
						</div>
						<div class="aisales-progress aisales-progress--lg">
							<div class="aisales-progress__fill aisales-progress__fill--brand" id="aisales-process-progress" style="width: 0%"></div>
						</div>
					</div>

					<!-- Processing Log -->
					<div class="aisales-process-log" id="aisales-process-log">
						<!-- Log entries will be added here by JavaScript -->
					</div>
				</div>
			</div>

			<!-- Step 4 Actions -->
			<div class="aisales-batch-actions">
				<div class="aisales-batch-actions__left">
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-pause-process">
						<span class="dashicons dashicons-controls-pause"></span>
						<?php esc_html_e( 'Pause', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-resume-process" style="display: none;">
						<span class="dashicons dashicons-controls-play"></span>
						<?php esc_html_e( 'Resume', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
				<div class="aisales-batch-actions__right">
					<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--outline" id="aisales-cancel-process">
						<?php esc_html_e( 'Cancel', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Step 5: Review & Apply -->
		<div class="aisales-batch-panel" id="aisales-step-5" data-step="5" style="display: none;">
			<div class="aisales-card">
				<div class="aisales-card__header">
					<h3>
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Review & Apply Results', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</h3>
					<div class="aisales-card__header-action">
						<span class="aisales-badge aisales-badge--success" id="aisales-results-badge">
							<?php esc_html_e( 'Complete', 'stacksuite-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
				</div>
				<div class="aisales-card__body">
					<!-- Results Summary -->
					<div class="aisales-results-summary">
						<div class="aisales-results-summary__stat">
							<span class="dashicons dashicons-yes-alt aisales-text-success"></span>
							<span><strong id="aisales-final-success">0</strong> <?php esc_html_e( 'products enhanced', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
						<div class="aisales-results-summary__stat aisales-results-summary__stat--failed" id="aisales-failed-summary" style="display: none;">
							<span class="dashicons dashicons-warning aisales-text-danger"></span>
							<span><strong id="aisales-final-failed">0</strong> <?php esc_html_e( 'products failed', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
							<button type="button" class="aisales-btn aisales-btn--warning aisales-btn--sm" id="aisales-retry-failed" style="margin-left: 10px;">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Retry Failed', 'stacksuite-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
						<div class="aisales-results-summary__stat">
							<span class="dashicons dashicons-money-alt"></span>
							<span><strong id="aisales-final-tokens">0</strong> <?php esc_html_e( 'tokens used', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
						</div>
					</div>

					<!-- Results Table -->
					<div class="aisales-results-table-wrapper">
						<table class="aisales-results-table" id="aisales-results-table">
							<thead>
								<tr>
									<th class="aisales-results-table__check">
										<input type="checkbox" id="aisales-select-all-results" checked>
									</th>
									<th><?php esc_html_e( 'Product', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
									<th><?php esc_html_e( 'Changes', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
									<th><?php esc_html_e( 'Status', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'stacksuite-sales-manager-for-woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody id="aisales-results-body">
								<!-- Results will be rendered here by JavaScript -->
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<!-- Step 5 Actions -->
			<div class="aisales-batch-actions">
				<div class="aisales-batch-actions__left">
					<span class="aisales-batch-info">
						<span id="aisales-apply-count">0</span> <?php esc_html_e( 'products selected to apply', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</span>
				</div>
				<div class="aisales-batch-actions__right">
					<button type="button" class="aisales-btn aisales-btn--secondary" id="aisales-new-batch">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Start New Batch', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--success" id="aisales-apply-results">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e( 'Apply Selected Changes', 'stacksuite-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Result Details Modal -->
<div class="aisales-modal-overlay" id="aisales-result-modal-overlay"></div>
<div class="aisales-modal" id="aisales-result-modal">
	<div class="aisales-modal__header">
		<h3 id="aisales-result-modal-title"><?php esc_html_e( 'Change Details', 'stacksuite-sales-manager-for-woocommerce' ); ?></h3>
		<button type="button" class="aisales-modal__close" id="aisales-result-modal-close">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>
	<div class="aisales-modal__body" id="aisales-result-modal-body">
		<!-- Content will be rendered by JavaScript -->
	</div>
	<div class="aisales-modal__footer">
		<a href="#" target="_blank" rel="noopener noreferrer" class="aisales-btn aisales-btn--secondary" id="aisales-result-modal-view">
			<span class="dashicons dashicons-external"></span>
			<?php esc_html_e( 'View Product', 'stacksuite-sales-manager-for-woocommerce' ); ?>
		</a>
		<a href="#" target="_blank" rel="noopener noreferrer" class="aisales-btn aisales-btn--primary" id="aisales-result-modal-edit">
			<span class="dashicons dashicons-edit"></span>
			<?php esc_html_e( 'Edit Product', 'stacksuite-sales-manager-for-woocommerce' ); ?>
		</a>
	</div>
</div>

<!-- Result Detail Modal Template -->
<script type="text/template" id="aisales-result-detail-template">
	<div class="aisales-result-detail">
		<div class="aisales-result-detail__header">
			<h4>{product_name}</h4>
		</div>
		<div class="aisales-result-detail__fields">
			{fields}
		</div>
	</div>
</script>

<!-- Diff Field Template -->
<script type="text/template" id="aisales-diff-field-template">
	<div class="aisales-diff-field" data-field="{field_key}">
		<div class="aisales-diff-field__header">
			<span class="aisales-diff-field__label">{field_label}</span>
			<div class="aisales-diff-field__actions">
				<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept-field">
					<span class="dashicons dashicons-yes"></span>
				</button>
				<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="reject-field">
					<span class="dashicons dashicons-no"></span>
				</button>
			</div>
		</div>
		<div class="aisales-diff-field__content">
			<div class="aisales-diff-field__current">
				<span class="aisales-diff-field__subtitle"><?php esc_html_e( 'Current', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-diff-field__value">{current_value}</div>
			</div>
			<div class="aisales-diff-field__arrow">
				<span class="dashicons dashicons-arrow-right-alt"></span>
			</div>
			<div class="aisales-diff-field__suggested">
				<span class="aisales-diff-field__subtitle"><?php esc_html_e( 'Suggested', 'stacksuite-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-diff-field__value">{suggested_value}</div>
			</div>
		</div>
	</div>
</script>

<!-- Product Card Template -->
<script type="text/template" id="aisales-product-card-template">
	<div class="aisales-product-card" data-product-id="{id}">
		<div class="aisales-product-card__checkbox">
			<input type="checkbox" name="products[]" value="{id}">
		</div>
		<div class="aisales-product-card__image">
			<img src="{image_url}" alt="{title}">
		</div>
		<div class="aisales-product-card__content">
			<div class="aisales-product-card__title">{title}</div>
			<div class="aisales-product-card__meta">
				<span class="aisales-product-card__price">{price}</span>
				<span class="aisales-product-card__status aisales-product-card__status--{status}">{status_label}</span>
			</div>
			<div class="aisales-product-card__categories">{categories}</div>
		</div>
	</div>
</script>
