<?php
/**
 * Admin Chat Page Template - Enhanced with Entity Switcher & Store Context
 *
 * Variables passed from AISales_Chat_Page::render_page():
 * - $api_key (string) - API key for the service
 * - $balance (int) - Current token balance
 * - $entity_type (string) - 'product' or 'category'
 * - $product_id (int) - Pre-selected product ID (0 if none)
 * - $category_id (int) - Pre-selected category ID (0 if none)
 * - $product_data (array|null) - Pre-loaded product data
 * - $category_data (array|null) - Pre-loaded category data
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get store context status
$aisales_store_context  = get_option( 'aisales_store_context', array() );
$aisales_context_status = 'missing';
$aisales_store_name     = '';

if ( ! empty( $aisales_store_context ) ) {
	$aisales_store_name    = isset( $aisales_store_context['store_name'] ) ? $aisales_store_context['store_name'] : get_bloginfo( 'name' );
	$aisales_has_required  = ! empty( $aisales_store_context['store_name'] ) || ! empty( $aisales_store_context['business_niche'] );
	$aisales_has_optional  = ! empty( $aisales_store_context['target_audience'] ) || ! empty( $aisales_store_context['brand_tone'] );
	$aisales_context_status = $aisales_has_required ? ( $aisales_has_optional ? 'configured' : 'partial' ) : 'missing';
} else {
	$aisales_store_name = get_bloginfo( 'name' );
}

// Check if this is first visit (for onboarding)
$aisales_has_visited    = get_user_meta( get_current_user_id(), 'aisales_chat_visited', true );
$aisales_show_onboarding = empty( $aisales_has_visited ) && 'missing' === $aisales_context_status;
?>

<div class="wrap aisales-admin-wrap aisales-chat-wrap">
	<!-- WordPress Admin Notices Area - h1 triggers WP notice placement -->
	<h1 class="aisales-notices-anchor"></h1>
	
	<!-- Page Header -->
	<header class="aisales-chat-page-header">
		<div class="aisales-chat-page-header__left">
			<span class="aisales-chat-page-title">
				<span class="dashicons dashicons-format-chat"></span>
				<?php esc_html_e( 'AI Agent', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-chat-page-header__right">
			<!-- Store Context Button -->
			<button type="button" class="aisales-store-context-btn" id="aisales-open-context" title="<?php esc_attr_e( 'Store Context Settings', 'ai-sales-manager-for-woocommerce' ); ?>">
				<span class="dashicons dashicons-store"></span>
				<span class="aisales-store-name"><?php echo esc_html( $aisales_store_name ?: __( 'Set Up Store', 'ai-sales-manager-for-woocommerce' ) ); ?></span>
				<span class="aisales-context-status aisales-context-status--<?php echo esc_attr( $aisales_context_status ); ?>"></span>
			</button>
			<!-- Balance Indicator -->
			<span class="aisales-balance-indicator">
				<span class="dashicons dashicons-money-alt"></span>
				<span id="aisales-balance-display"><?php echo esc_html( number_format( $balance ) ); ?></span>
				<?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
	</header>

	<!-- Onboarding Overlay (shown on first visit if no store context) -->
	<?php if ( $aisales_show_onboarding ) : ?>
	<div class="aisales-onboarding-overlay" id="aisales-onboarding">
		<div class="aisales-onboarding-card">
			<div class="aisales-onboarding-icon">
				<span class="dashicons dashicons-welcome-learn-more"></span>
			</div>
			<h2><?php esc_html_e( 'Welcome to AI Agent!', 'ai-sales-manager-for-woocommerce' ); ?></h2>
			<p><?php esc_html_e( 'To get the best results, tell us a bit about your store. This helps AI write content that matches your brand voice.', 'ai-sales-manager-for-woocommerce' ); ?></p>
			<div class="aisales-onboarding-actions">
				<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--lg" id="aisales-onboarding-setup">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Set Up Store Context', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
				<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--lg" id="aisales-onboarding-skip">
					<?php esc_html_e( 'Skip for Now', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>
			<p class="aisales-onboarding-hint">
				<span class="dashicons dashicons-info-outline"></span>
				<?php esc_html_e( 'You can always configure this later from the store icon in the header.', 'ai-sales-manager-for-woocommerce' ); ?>
			</p>
		</div>
	</div>
	<?php endif; ?>

	<!-- Store Context Slide-out Panel (Shared Partial) -->
	<?php include AISALES_PLUGIN_DIR . 'templates/partials/store-context-panel.php'; ?>

	<!-- Balance Top-Up Modal (Shared Partial) -->
	<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-modal.php'; ?>

	<!-- Main Chat Container -->
	<div class="aisales-chat-container">
		<!-- Chat Panel (Left - 70%) -->
		<div class="aisales-chat-panel">
			<!-- Entity Switcher & Selector -->
			<div class="aisales-chat-header">
				<div class="aisales-entity-switcher">
					<div class="aisales-entity-tabs">
						<button type="button" class="aisales-entity-tab <?php echo 'product' === $entity_type ? 'aisales-entity-tab--active' : ''; ?>" data-type="product">
							<span class="dashicons dashicons-products"></span>
							<?php esc_html_e( 'Products', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-entity-tab <?php echo 'category' === $entity_type ? 'aisales-entity-tab--active' : ''; ?>" data-type="category">
							<span class="dashicons dashicons-category"></span>
							<?php esc_html_e( 'Categories', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-entity-tab <?php echo 'agent' === $entity_type ? 'aisales-entity-tab--active' : ''; ?>" data-type="agent">
							<span class="dashicons dashicons-superhero-alt"></span>
							<?php esc_html_e( 'Agent', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
					<div class="aisales-entity-selector">
						<select id="aisales-entity-select" class="aisales-select" data-entity-type="<?php echo esc_attr( $entity_type ); ?>">
							<option value=""><?php echo 'product' === $entity_type ? esc_html__( 'Select a product...', 'ai-sales-manager-for-woocommerce' ) : esc_html__( 'Select a category...', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
					</div>
				</div>
				<div class="aisales-chat-actions">
					<button type="button" id="aisales-new-chat" class="aisales-btn aisales-btn--secondary aisales-btn--sm">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'New Chat', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Chat Messages -->
			<div class="aisales-chat-messages" id="aisales-chat-messages">
				<!-- Welcome State with Entity Cards -->
				<div class="aisales-chat-welcome" id="aisales-chat-welcome">
					<div class="aisales-chat-welcome__icon">
						<span class="dashicons dashicons-lightbulb"></span>
					</div>
					<h3><?php esc_html_e( 'What would you like to improve?', 'ai-sales-manager-for-woocommerce' ); ?></h3>
					<p><?php esc_html_e( 'Select an entity type and item above, or choose from below to get started.', 'ai-sales-manager-for-woocommerce' ); ?></p>
					
					<div class="aisales-welcome-cards">
						<button type="button" class="aisales-welcome-card" data-entity="product">
							<div class="aisales-welcome-card__icon">
								<span class="dashicons dashicons-products"></span>
							</div>
							<div class="aisales-welcome-card__content">
								<h4><?php esc_html_e( 'Products', 'ai-sales-manager-for-woocommerce' ); ?></h4>
								<p><?php esc_html_e( 'Improve titles, descriptions, and tags for better conversions', 'ai-sales-manager-for-woocommerce' ); ?></p>
							</div>
							<span class="aisales-welcome-card__arrow">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</span>
						</button>
						<button type="button" class="aisales-welcome-card" data-entity="category">
							<div class="aisales-welcome-card__icon aisales-welcome-card__icon--category">
								<span class="dashicons dashicons-category"></span>
							</div>
							<div class="aisales-welcome-card__content">
								<h4><?php esc_html_e( 'Categories', 'ai-sales-manager-for-woocommerce' ); ?></h4>
								<p><?php esc_html_e( 'Generate compelling category descriptions and SEO content', 'ai-sales-manager-for-woocommerce' ); ?></p>
							</div>
							<span class="aisales-welcome-card__arrow">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</span>
						</button>
						<button type="button" class="aisales-welcome-card aisales-welcome-card--agent" data-entity="agent">
							<div class="aisales-welcome-card__icon aisales-welcome-card__icon--agent">
								<span class="dashicons dashicons-superhero-alt"></span>
							</div>
							<div class="aisales-welcome-card__content">
								<h4><?php esc_html_e( 'Marketing Agent', 'ai-sales-manager-for-woocommerce' ); ?></h4>
								<p><?php esc_html_e( 'Create campaigns, social content, emails, and marketing images', 'ai-sales-manager-for-woocommerce' ); ?></p>
							</div>
							<span class="aisales-welcome-card__arrow">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</span>
						</button>
					</div>

					<?php if ( 'missing' === $aisales_context_status || 'partial' === $aisales_context_status ) : ?>
					<div class="aisales-welcome-context-hint">
						<span class="dashicons dashicons-info-outline"></span>
						<span><?php esc_html_e( 'Tip: Set up your store context for better AI-generated content', 'ai-sales-manager-for-woocommerce' ); ?></span>
						<button type="button" class="aisales-btn aisales-btn--link" id="aisales-welcome-setup-context">
							<?php esc_html_e( 'Set up now', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Quick Actions - Product -->
			<div class="aisales-quick-actions" id="aisales-quick-actions-product" <?php echo 'category' === $entity_type ? 'style="display:none"' : ''; ?>>
				<span class="aisales-quick-actions__label"><?php esc_html_e( 'Quick Actions:', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-quick-actions__buttons">
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="improve_title" disabled>
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Improve Title', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="improve_description" disabled>
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Improve Description', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="seo_optimize" disabled>
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Optimize', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="suggest_tags" disabled>
						<span class="dashicons dashicons-tag"></span>
						<?php esc_html_e( 'Suggest Tags', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="suggest_categories" disabled>
						<span class="dashicons dashicons-category"></span>
						<?php esc_html_e( 'Suggest Categories', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="generate_content" disabled>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Generate Content', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Quick Actions - Category -->
			<div class="aisales-quick-actions" id="aisales-quick-actions-category" <?php echo 'product' === $entity_type ? 'style="display:none"' : ''; ?>>
				<span class="aisales-quick-actions__label"><?php esc_html_e( 'Quick Actions:', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-quick-actions__buttons">
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="improve_name" disabled>
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Improve Name', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="improve_cat_description" disabled>
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Generate Description', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="cat_seo_optimize" disabled>
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Optimize', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="generate_cat_content" disabled>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Full Optimize', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Quick Actions - Agent (Marketing) -->
			<div class="aisales-quick-actions aisales-quick-actions--agent" id="aisales-quick-actions-agent" style="display:none">
				<span class="aisales-quick-actions__label"><?php esc_html_e( 'Marketing Actions:', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-quick-actions__buttons">
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="create_campaign" disabled>
						<span class="dashicons dashicons-megaphone"></span>
						<?php esc_html_e( 'Campaign', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="social_content" disabled>
						<span class="dashicons dashicons-share"></span>
						<?php esc_html_e( 'Social', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="email_campaign" disabled>
						<span class="dashicons dashicons-email"></span>
						<?php esc_html_e( 'Email', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="generate_image" disabled>
						<span class="dashicons dashicons-format-image"></span>
						<?php esc_html_e( 'Image', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="store_analysis" disabled>
						<span class="dashicons dashicons-chart-bar"></span>
						<?php esc_html_e( 'Analyze', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-btn--agent" data-action="bulk_optimize" disabled>
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Bulk Optimize', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<!-- Message Input -->
			<div class="aisales-chat-input">
				<!-- Attachment Previews -->
				<div class="aisales-attachment-previews" id="aisales-attachment-previews" style="display: none;"></div>
				
				<div class="aisales-chat-input__wrapper">
					<button type="button" id="aisales-attach-button" class="aisales-btn aisales-btn--icon" title="<?php esc_attr_e( 'Attach files (images, PDF)', 'ai-sales-manager-for-woocommerce' ); ?>">
						<span class="dashicons dashicons-paperclip"></span>
					</button>
					<input type="file" id="aisales-file-input" multiple accept="image/png,image/jpeg,image/webp,image/heic,image/heif,application/pdf" style="display: none;">
					<textarea
						id="aisales-message-input"
						placeholder="<?php esc_attr_e( 'Type your message...', 'ai-sales-manager-for-woocommerce' ); ?>"
						rows="1"
						disabled
					></textarea>
					<button type="button" id="aisales-send-message" class="aisales-btn aisales-btn--primary" disabled>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<span class="aisales-btn__text"><?php esc_html_e( 'Send', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</button>
				</div>
				<div class="aisales-chat-input__hint">
					<span class="aisales-attachment-hint"><?php esc_html_e( 'You can attach images or PDFs (max 5 files)', 'ai-sales-manager-for-woocommerce' ); ?></span>
					<span class="aisales-tokens-used" id="aisales-tokens-used"></span>
				</div>
			</div>
		</div>

		<!-- Entity Info Panel (Right - 30%) -->
		<div class="aisales-entity-panel" id="aisales-entity-panel">
			<!-- Empty State -->
			<div class="aisales-entity-empty" id="aisales-entity-empty">
				<span class="dashicons dashicons-<?php echo 'category' === $entity_type ? 'category' : 'products'; ?>" id="aisales-empty-icon"></span>
				<p id="aisales-empty-text"><?php echo 'category' === $entity_type ? esc_html__( 'Select a category to view details', 'ai-sales-manager-for-woocommerce' ) : esc_html__( 'Select a product to view details', 'ai-sales-manager-for-woocommerce' ); ?></p>
			</div>

			<!-- Product Info (hidden by default) -->
			<div class="aisales-product-info" id="aisales-product-info" style="display: none;">
				<!-- Product Image -->
				<div class="aisales-product-info__image">
					<img id="aisales-product-image" src="" alt="">
				</div>

				<!-- Product Title -->
				<div class="aisales-product-info__field" data-field="title">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-heading"></span>
						<?php esc_html_e( 'Title', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-title"></div>
					<div class="aisales-product-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Product Description -->
				<div class="aisales-product-info__field aisales-product-info__field--expandable" data-field="description">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Description', 'ai-sales-manager-for-woocommerce' ); ?>
						<button type="button" class="aisales-expand-toggle">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
					<div class="aisales-product-info__value aisales-product-info__value--truncated" id="aisales-product-description"></div>
					<div class="aisales-product-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Product Short Description -->
				<div class="aisales-product-info__field" data-field="short_description">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-editor-paragraph"></span>
						<?php esc_html_e( 'Short Description', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-short-description"></div>
					<div class="aisales-product-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Category -->
				<div class="aisales-product-info__field" data-field="categories">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-category"></span>
						<?php esc_html_e( 'Categories', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-categories">
						<span class="aisales-no-value"><?php esc_html_e( 'None', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-product-info__pending" style="display: none;">
						<div class="aisales-pending-change aisales-pending-change--tags">
							<div class="aisales-pending-change__added"></div>
							<div class="aisales-pending-change__removed"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Tags -->
				<div class="aisales-product-info__field" data-field="tags">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-tag"></span>
						<?php esc_html_e( 'Tags', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value aisales-product-info__tags" id="aisales-product-tags">
						<span class="aisales-no-value"><?php esc_html_e( 'None', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-product-info__pending" style="display: none;">
						<div class="aisales-pending-change aisales-pending-change--tags">
							<div class="aisales-pending-change__added"></div>
							<div class="aisales-pending-change__removed"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Price -->
				<div class="aisales-product-info__field" data-field="price">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-money-alt"></span>
						<?php esc_html_e( 'Price', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-price"></div>
				</div>

				<!-- Stock -->
				<div class="aisales-product-info__field" data-field="stock">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-archive"></span>
						<?php esc_html_e( 'Stock', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-stock"></div>
				</div>

				<!-- Status -->
				<div class="aisales-product-info__field" data-field="status">
					<div class="aisales-product-info__label">
						<span class="dashicons dashicons-post-status"></span>
						<?php esc_html_e( 'Status', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-product-info__value" id="aisales-product-status"></div>
				</div>

				<!-- Pending Changes Summary -->
				<div class="aisales-pending-summary" id="aisales-pending-summary" style="display: none;">
					<div class="aisales-pending-summary__header">
						<span class="dashicons dashicons-warning"></span>
						<span id="aisales-pending-count">0</span> <?php esc_html_e( 'Pending Changes', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-pending-summary__actions">
						<button type="button" id="aisales-accept-all" class="aisales-btn aisales-btn--success aisales-btn--sm">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Accept All', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" id="aisales-discard-all" class="aisales-btn aisales-btn--danger aisales-btn--sm">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Discard All', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<!-- Product Actions -->
				<div class="aisales-product-info__actions">
					<a id="aisales-edit-product" href="#" class="aisales-btn aisales-btn--secondary aisales-btn--sm" target="_blank">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit Product', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
					<a id="aisales-view-product" href="#" class="aisales-btn aisales-btn--outline aisales-btn--sm" target="_blank">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
				</div>
			</div>

			<!-- Category Info (hidden by default) -->
			<div class="aisales-category-info" id="aisales-category-info" style="display: none;">
				<!-- Category Header -->
				<div class="aisales-category-info__header">
					<div class="aisales-category-info__icon">
						<span class="dashicons dashicons-category"></span>
					</div>
					<div class="aisales-category-info__title">
						<span id="aisales-category-name">Category Name</span>
						<span class="aisales-category-info__parent" id="aisales-category-parent"></span>
					</div>
				</div>

				<!-- Category Stats -->
				<div class="aisales-category-info__stats">
					<div class="aisales-category-stat">
						<span class="aisales-category-stat__value" id="aisales-category-product-count">0</span>
						<span class="aisales-category-stat__label"><?php esc_html_e( 'Products', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-category-stat">
						<span class="aisales-category-stat__value" id="aisales-category-subcat-count">0</span>
						<span class="aisales-category-stat__label"><?php esc_html_e( 'Subcategories', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>

				<!-- Category Name Field -->
				<div class="aisales-category-info__field" data-field="name">
					<div class="aisales-category-info__label">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Name', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-category-info__value" id="aisales-category-name-value"></div>
					<div class="aisales-category-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Category Description -->
				<div class="aisales-category-info__field aisales-category-info__field--expandable" data-field="description">
					<div class="aisales-category-info__label">
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Description', 'ai-sales-manager-for-woocommerce' ); ?>
						<button type="button" class="aisales-expand-toggle">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
					<div class="aisales-category-info__value aisales-category-info__value--truncated" id="aisales-category-description">
						<span class="aisales-no-value"><?php esc_html_e( 'No description', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-category-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- SEO Title -->
				<div class="aisales-category-info__field" data-field="seo_title">
					<div class="aisales-category-info__label">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Title', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-category-info__value" id="aisales-category-seo-title">
						<span class="aisales-no-value"><?php esc_html_e( 'Not set', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-category-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Meta Description -->
				<div class="aisales-category-info__field" data-field="meta_description">
					<div class="aisales-category-info__label">
						<span class="dashicons dashicons-media-text"></span>
						<?php esc_html_e( 'Meta Description', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-category-info__value" id="aisales-category-meta-description">
						<span class="aisales-no-value"><?php esc_html_e( 'Not set', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-category-info__pending" style="display: none;">
						<div class="aisales-pending-change">
							<div class="aisales-pending-change__new"></div>
							<div class="aisales-pending-change__actions">
								<button type="button" class="aisales-btn aisales-btn--success aisales-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Subcategories -->
				<div class="aisales-category-info__field" data-field="subcategories">
					<div class="aisales-category-info__label">
						<span class="dashicons dashicons-networking"></span>
						<?php esc_html_e( 'Subcategories', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-category-info__value aisales-category-info__subcats" id="aisales-category-subcategories">
						<span class="aisales-no-value"><?php esc_html_e( 'None', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>

				<!-- Pending Changes Summary (Category) -->
				<div class="aisales-pending-summary" id="aisales-category-pending-summary" style="display: none;">
					<div class="aisales-pending-summary__header">
						<span class="dashicons dashicons-warning"></span>
						<span id="aisales-category-pending-count">0</span> <?php esc_html_e( 'Pending Changes', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-pending-summary__actions">
						<button type="button" id="aisales-category-accept-all" class="aisales-btn aisales-btn--success aisales-btn--sm">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Accept All', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" id="aisales-category-discard-all" class="aisales-btn aisales-btn--danger aisales-btn--sm">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Discard All', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<!-- Category Actions -->
				<div class="aisales-category-info__actions">
					<a id="aisales-edit-category" href="#" class="aisales-btn aisales-btn--secondary aisales-btn--sm" target="_blank">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit Category', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
					<a id="aisales-view-category" href="#" class="aisales-btn aisales-btn--outline aisales-btn--sm" target="_blank">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
				</div>
			</div>

			<!-- Agent Info (hidden by default) -->
			<div class="aisales-agent-info" id="aisales-agent-info" style="display: none;">
				<!-- Agent Header -->
				<div class="aisales-agent-info__header">
					<div class="aisales-agent-info__icon">
						<span class="dashicons dashicons-superhero-alt"></span>
					</div>
					<div class="aisales-agent-info__title">
						<span><?php esc_html_e( 'Marketing Agent', 'ai-sales-manager-for-woocommerce' ); ?></span>
						<span class="aisales-agent-info__subtitle"><?php esc_html_e( 'Store-wide Marketing Operations', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>

				<!-- Agent Capabilities -->
				<div class="aisales-agent-info__section">
					<div class="aisales-agent-info__section-title">
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Capabilities', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-agent-info__capabilities">
						<button type="button" class="aisales-capability-btn" data-action="create_campaign">
							<span class="dashicons dashicons-megaphone"></span>
							<?php esc_html_e( 'Create marketing campaigns', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-capability-btn" data-action="social_content">
							<span class="dashicons dashicons-share"></span>
							<?php esc_html_e( 'Generate social media content', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-capability-btn" data-action="email_campaign">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Draft email campaigns', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-capability-btn" data-action="generate_image">
							<span class="dashicons dashicons-format-image"></span>
							<?php esc_html_e( 'Generate marketing images', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-capability-btn" data-action="store_analysis">
							<span class="dashicons dashicons-chart-bar"></span>
							<?php esc_html_e( 'Analyze store performance', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-capability-btn" data-action="bulk_optimize">
							<span class="dashicons dashicons-admin-generic"></span>
							<?php esc_html_e( 'Suggest bulk optimizations', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<!-- Agent Tips -->
				<div class="aisales-agent-info__tips">
					<div class="aisales-agent-info__tip">
						<span class="dashicons dashicons-info-outline"></span>
						<span><?php esc_html_e( 'Tip: Use the quick actions above or type naturally to describe what you need.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Message Templates -->
<script type="text/template" id="aisales-message-template">
	<div class="aisales-message aisales-message--{role}" data-message-id="{id}">
		<div class="aisales-message__avatar">
			<span class="dashicons {icon}"></span>
		</div>
		<div class="aisales-message__content">
			<div class="aisales-message__text">{content}</div>
			<div class="aisales-message__meta">
				<span class="aisales-message__time">{time}</span>
				{tokens}
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="aisales-suggestion-template">
	<div class="aisales-suggestion" data-suggestion-id="{id}" data-suggestion-type="{type}">
		<div class="aisales-suggestion__header">
			<span class="dashicons dashicons-lightbulb"></span>
			<span class="aisales-suggestion__type">{typeLabel}</span>
		</div>
		<div class="aisales-suggestion__preview">
			<div class="aisales-suggestion__current">
				<div class="aisales-suggestion__label"><?php esc_html_e( 'Current:', 'ai-sales-manager-for-woocommerce' ); ?></div>
				<div class="aisales-suggestion__value">{currentValue}</div>
			</div>
			<div class="aisales-suggestion__arrow">
				<span class="dashicons dashicons-arrow-right-alt"></span>
			</div>
			<div class="aisales-suggestion__new">
				<div class="aisales-suggestion__label"><?php esc_html_e( 'Suggested:', 'ai-sales-manager-for-woocommerce' ); ?></div>
				<div class="aisales-suggestion__value">{suggestedValue}</div>
			</div>
		</div>
		<div class="aisales-suggestion__actions">
			<button type="button" class="aisales-btn aisales-btn--success aisales-btn--sm" data-action="apply">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Apply', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" data-action="edit">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Edit', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--danger aisales-btn--sm" data-action="discard">
				<span class="dashicons dashicons-no"></span>
				<?php esc_html_e( 'Discard', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="aisales-thinking-template">
	<div class="aisales-message aisales-message--assistant aisales-message--thinking">
		<div class="aisales-message__avatar">
			<span class="dashicons dashicons-admin-site-alt3"></span>
		</div>
		<div class="aisales-message__content">
			<div class="aisales-thinking">
				<span class="aisales-thinking__dot"></span>
				<span class="aisales-thinking__dot"></span>
				<span class="aisales-thinking__dot"></span>
				<span class="aisales-thinking__text"><?php esc_html_e( 'Thinking...', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</div>
		</div>
	</div>
</script>

<?php if ( ! empty( $product_data ) ) : ?>
<script type="text/javascript">
	// Pre-select product from metabox redirect
	window.aisalesPreselectedProduct = <?php echo wp_json_encode( $product_data ); ?>;
	window.aisalesPreselectedEntityType = 'product';
</script>
<?php endif; ?>

<?php if ( ! empty( $category_data ) ) : ?>
<script type="text/javascript">
	// Pre-select category from URL parameter
	window.aisalesPreselectedCategory = <?php echo wp_json_encode( $category_data ); ?>;
	window.aisalesPreselectedEntityType = 'category';
</script>
<?php endif; ?>

<script type="text/javascript">
	// Store context status for JS
	window.aisalesStoreContext = {
		status: '<?php echo esc_js( $aisales_context_status ); ?>',
		isConfigured: <?php echo $aisales_context_status === 'configured' ? 'true' : 'false'; ?>
	};
</script>
