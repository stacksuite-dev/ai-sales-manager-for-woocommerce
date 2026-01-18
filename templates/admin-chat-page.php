<?php
/**
 * Admin Chat Page Template - Enhanced with Entity Switcher & Store Context
 *
 * Variables passed from WooAI_Chat_Page::render_page():
 * - $api_key (string) - API key for the service
 * - $balance (int) - Current token balance
 * - $entity_type (string) - 'product' or 'category'
 * - $product_id (int) - Pre-selected product ID (0 if none)
 * - $category_id (int) - Pre-selected category ID (0 if none)
 * - $product_data (array|null) - Pre-loaded product data
 * - $category_data (array|null) - Pre-loaded category data
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Get store context status
$store_context   = get_option( 'wooai_store_context', array() );
$context_status  = 'missing';
$store_name      = '';

if ( ! empty( $store_context ) ) {
	$store_name = isset( $store_context['store_name'] ) ? $store_context['store_name'] : get_bloginfo( 'name' );
	$has_required = ! empty( $store_context['store_name'] ) || ! empty( $store_context['business_niche'] );
	$has_optional = ! empty( $store_context['target_audience'] ) || ! empty( $store_context['brand_tone'] );
	$context_status = $has_required ? ( $has_optional ? 'configured' : 'partial' ) : 'missing';
} else {
	$store_name = get_bloginfo( 'name' );
}

// Check if this is first visit (for onboarding)
$has_visited = get_user_meta( get_current_user_id(), 'wooai_chat_visited', true );
$show_onboarding = empty( $has_visited ) && 'missing' === $context_status;
?>

<div class="wrap wooai-admin-wrap wooai-chat-wrap">
	<!-- Page Header -->
	<header class="wooai-chat-page-header">
		<div class="wooai-chat-page-header__left">
			<h1>
				<span class="dashicons dashicons-format-chat"></span>
				<?php esc_html_e( 'AI Agent', 'woo-ai-sales-manager' ); ?>
			</h1>
		</div>
		<div class="wooai-chat-page-header__right">
			<!-- Store Context Button -->
			<button type="button" class="wooai-store-context-btn" id="wooai-open-context" title="<?php esc_attr_e( 'Store Context Settings', 'woo-ai-sales-manager' ); ?>">
				<span class="dashicons dashicons-store"></span>
				<span class="wooai-store-name"><?php echo esc_html( $store_name ?: __( 'Set Up Store', 'woo-ai-sales-manager' ) ); ?></span>
				<span class="wooai-context-status wooai-context-status--<?php echo esc_attr( $context_status ); ?>"></span>
			</button>
			<!-- Balance Indicator -->
			<span class="wooai-balance-indicator">
				<span class="dashicons dashicons-money-alt"></span>
				<span id="wooai-balance-display"><?php echo esc_html( number_format( $balance ) ); ?></span>
				<?php esc_html_e( 'tokens', 'woo-ai-sales-manager' ); ?>
			</span>
		</div>
	</header>

	<!-- Onboarding Overlay (shown on first visit if no store context) -->
	<?php if ( $show_onboarding ) : ?>
	<div class="wooai-onboarding-overlay" id="wooai-onboarding">
		<div class="wooai-onboarding-card">
			<div class="wooai-onboarding-icon">
				<span class="dashicons dashicons-welcome-learn-more"></span>
			</div>
			<h2><?php esc_html_e( 'Welcome to AI Agent!', 'woo-ai-sales-manager' ); ?></h2>
			<p><?php esc_html_e( 'To get the best results, tell us a bit about your store. This helps AI write content that matches your brand voice.', 'woo-ai-sales-manager' ); ?></p>
			<div class="wooai-onboarding-actions">
				<button type="button" class="wooai-btn wooai-btn--primary wooai-btn--lg" id="wooai-onboarding-setup">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Set Up Store Context', 'woo-ai-sales-manager' ); ?>
				</button>
				<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--lg" id="wooai-onboarding-skip">
					<?php esc_html_e( 'Skip for Now', 'woo-ai-sales-manager' ); ?>
				</button>
			</div>
			<p class="wooai-onboarding-hint">
				<span class="dashicons dashicons-info-outline"></span>
				<?php esc_html_e( 'You can always configure this later from the store icon in the header.', 'woo-ai-sales-manager' ); ?>
			</p>
		</div>
	</div>
	<?php endif; ?>

	<!-- Store Context Slide-out Panel -->
	<div class="wooai-context-panel" id="wooai-context-panel">
		<div class="wooai-context-panel__backdrop" id="wooai-context-backdrop"></div>
		<div class="wooai-context-panel__content">
			<div class="wooai-context-panel__header">
				<h2>
					<span class="dashicons dashicons-store"></span>
					<?php esc_html_e( 'Store Context', 'woo-ai-sales-manager' ); ?>
				</h2>
				<button type="button" class="wooai-context-panel__close" id="wooai-close-context">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="wooai-context-panel__body">
				<p class="wooai-context-panel__intro">
					<?php esc_html_e( 'Your store information helps AI write better content that matches your brand.', 'woo-ai-sales-manager' ); ?>
				</p>

				<form id="wooai-context-form" class="wooai-context-form">
					<!-- Store Name -->
					<div class="wooai-context-field">
						<label for="wooai-store-name"><?php esc_html_e( 'Store Name', 'woo-ai-sales-manager' ); ?></label>
						<input type="text" id="wooai-store-name" name="store_name" 
							value="<?php echo esc_attr( isset( $store_context['store_name'] ) ? $store_context['store_name'] : get_bloginfo( 'name' ) ); ?>"
							placeholder="<?php esc_attr_e( 'My Awesome Store', 'woo-ai-sales-manager' ); ?>">
					</div>

					<!-- Store Description -->
					<div class="wooai-context-field">
						<label for="wooai-store-description"><?php esc_html_e( 'Store Description', 'woo-ai-sales-manager' ); ?></label>
						<textarea id="wooai-store-description" name="store_description" rows="2"
							placeholder="<?php esc_attr_e( 'Brief description of what you sell...', 'woo-ai-sales-manager' ); ?>"><?php echo esc_textarea( isset( $store_context['store_description'] ) ? $store_context['store_description'] : get_bloginfo( 'description' ) ); ?></textarea>
					</div>

					<!-- Business Niche -->
					<div class="wooai-context-field">
						<label for="wooai-business-niche"><?php esc_html_e( 'Business Niche', 'woo-ai-sales-manager' ); ?></label>
						<select id="wooai-business-niche" name="business_niche">
							<option value=""><?php esc_html_e( 'Select a niche...', 'woo-ai-sales-manager' ); ?></option>
							<?php
							$niches = array(
								'fashion'       => __( 'Fashion & Apparel', 'woo-ai-sales-manager' ),
								'electronics'   => __( 'Electronics & Tech', 'woo-ai-sales-manager' ),
								'home_garden'   => __( 'Home & Garden', 'woo-ai-sales-manager' ),
								'health_beauty' => __( 'Health & Beauty', 'woo-ai-sales-manager' ),
								'sports'        => __( 'Sports & Outdoors', 'woo-ai-sales-manager' ),
								'toys_games'    => __( 'Toys & Games', 'woo-ai-sales-manager' ),
								'food_beverage' => __( 'Food & Beverage', 'woo-ai-sales-manager' ),
								'jewelry'       => __( 'Jewelry & Accessories', 'woo-ai-sales-manager' ),
								'automotive'    => __( 'Automotive', 'woo-ai-sales-manager' ),
								'books_media'   => __( 'Books & Media', 'woo-ai-sales-manager' ),
								'pets'          => __( 'Pet Supplies', 'woo-ai-sales-manager' ),
								'art_crafts'    => __( 'Art & Crafts', 'woo-ai-sales-manager' ),
								'office'        => __( 'Office & Business', 'woo-ai-sales-manager' ),
								'baby_kids'     => __( 'Baby & Kids', 'woo-ai-sales-manager' ),
								'other'         => __( 'Other', 'woo-ai-sales-manager' ),
							);
							$current_niche = isset( $store_context['business_niche'] ) ? $store_context['business_niche'] : '';
							foreach ( $niches as $value => $label ) :
							?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_niche, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Target Audience -->
					<div class="wooai-context-field">
						<label for="wooai-target-audience"><?php esc_html_e( 'Target Audience', 'woo-ai-sales-manager' ); ?></label>
						<input type="text" id="wooai-target-audience" name="target_audience"
							value="<?php echo esc_attr( isset( $store_context['target_audience'] ) ? $store_context['target_audience'] : '' ); ?>"
							placeholder="<?php esc_attr_e( 'e.g., Young professionals 25-40', 'woo-ai-sales-manager' ); ?>">
					</div>

					<!-- Brand Tone -->
					<div class="wooai-context-field">
						<label><?php esc_html_e( 'Brand Tone', 'woo-ai-sales-manager' ); ?></label>
						<div class="wooai-tone-options">
							<?php
							$tones = array(
								'professional' => array( 'icon' => 'businessman', 'label' => __( 'Professional', 'woo-ai-sales-manager' ) ),
								'friendly'     => array( 'icon' => 'smiley', 'label' => __( 'Friendly', 'woo-ai-sales-manager' ) ),
								'luxury'       => array( 'icon' => 'star-filled', 'label' => __( 'Luxury', 'woo-ai-sales-manager' ) ),
								'casual'       => array( 'icon' => 'format-status', 'label' => __( 'Casual', 'woo-ai-sales-manager' ) ),
								'technical'    => array( 'icon' => 'desktop', 'label' => __( 'Technical', 'woo-ai-sales-manager' ) ),
								'playful'      => array( 'icon' => 'heart', 'label' => __( 'Playful', 'woo-ai-sales-manager' ) ),
							);
							$current_tone = isset( $store_context['brand_tone'] ) ? $store_context['brand_tone'] : '';
							foreach ( $tones as $value => $tone ) :
							?>
								<label class="wooai-tone-option">
									<input type="radio" name="brand_tone" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current_tone, $value ); ?>>
									<span class="wooai-tone-option__content">
										<span class="dashicons dashicons-<?php echo esc_attr( $tone['icon'] ); ?>"></span>
										<span><?php echo esc_html( $tone['label'] ); ?></span>
									</span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Language -->
					<div class="wooai-context-field">
						<label for="wooai-language"><?php esc_html_e( 'Content Language', 'woo-ai-sales-manager' ); ?></label>
						<select id="wooai-language" name="language">
							<?php
							$languages = array(
								'English'    => __( 'English', 'woo-ai-sales-manager' ),
								'Spanish'    => __( 'Spanish', 'woo-ai-sales-manager' ),
								'French'     => __( 'French', 'woo-ai-sales-manager' ),
								'German'     => __( 'German', 'woo-ai-sales-manager' ),
								'Italian'    => __( 'Italian', 'woo-ai-sales-manager' ),
								'Portuguese' => __( 'Portuguese', 'woo-ai-sales-manager' ),
								'Dutch'      => __( 'Dutch', 'woo-ai-sales-manager' ),
								'Japanese'   => __( 'Japanese', 'woo-ai-sales-manager' ),
								'Chinese'    => __( 'Chinese', 'woo-ai-sales-manager' ),
								'Korean'     => __( 'Korean', 'woo-ai-sales-manager' ),
								'Thai'       => __( 'Thai', 'woo-ai-sales-manager' ),
							);
							$current_lang = isset( $store_context['language'] ) ? $store_context['language'] : 'English';
							foreach ( $languages as $value => $label ) :
							?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_lang, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<!-- Custom Instructions -->
					<div class="wooai-context-field">
						<label for="wooai-custom-instructions"><?php esc_html_e( 'Custom Instructions', 'woo-ai-sales-manager' ); ?> <span class="wooai-optional"><?php esc_html_e( '(optional)', 'woo-ai-sales-manager' ); ?></span></label>
						<textarea id="wooai-custom-instructions" name="custom_instructions" rows="3"
							placeholder="<?php esc_attr_e( 'e.g., Always mention free shipping, use metric units, avoid certain words...', 'woo-ai-sales-manager' ); ?>"><?php echo esc_textarea( isset( $store_context['custom_instructions'] ) ? $store_context['custom_instructions'] : '' ); ?></textarea>
					</div>

					<!-- Sync Status -->
					<div class="wooai-context-sync">
						<div class="wooai-context-sync__status">
							<span class="dashicons dashicons-update"></span>
							<span id="wooai-sync-status">
								<?php
								$last_sync = isset( $store_context['last_sync'] ) ? $store_context['last_sync'] : '';
								if ( $last_sync ) {
									printf(
										/* translators: %1$s: number of categories, %2$s: number of products, %3$s: date */
										esc_html__( 'Synced: %1$s categories, %2$s products on %3$s', 'woo-ai-sales-manager' ),
										isset( $store_context['category_count'] ) ? esc_html( $store_context['category_count'] ) : '0',
										isset( $store_context['product_count'] ) ? esc_html( $store_context['product_count'] ) : '0',
										esc_html( date_i18n( get_option( 'date_format' ), strtotime( $last_sync ) ) )
									);
								} else {
									esc_html_e( 'Not synced yet', 'woo-ai-sales-manager' );
								}
								?>
							</span>
						</div>
						<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" id="wooai-sync-context">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Sync Now', 'woo-ai-sales-manager' ); ?>
						</button>
					</div>
				</form>
			</div>
			<div class="wooai-context-panel__footer">
				<button type="button" class="wooai-btn wooai-btn--outline" id="wooai-cancel-context">
					<?php esc_html_e( 'Cancel', 'woo-ai-sales-manager' ); ?>
				</button>
				<button type="button" class="wooai-btn wooai-btn--primary" id="wooai-save-context">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Context', 'woo-ai-sales-manager' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Main Chat Container -->
	<div class="wooai-chat-container">
		<!-- Chat Panel (Left - 70%) -->
		<div class="wooai-chat-panel">
			<!-- Entity Switcher & Selector -->
			<div class="wooai-chat-header">
				<div class="wooai-entity-switcher">
					<div class="wooai-entity-tabs">
						<button type="button" class="wooai-entity-tab <?php echo 'product' === $entity_type ? 'wooai-entity-tab--active' : ''; ?>" data-type="product">
							<span class="dashicons dashicons-products"></span>
							<?php esc_html_e( 'Products', 'woo-ai-sales-manager' ); ?>
						</button>
						<button type="button" class="wooai-entity-tab <?php echo 'category' === $entity_type ? 'wooai-entity-tab--active' : ''; ?>" data-type="category">
							<span class="dashicons dashicons-category"></span>
							<?php esc_html_e( 'Categories', 'woo-ai-sales-manager' ); ?>
						</button>
					</div>
					<div class="wooai-entity-selector">
						<select id="wooai-entity-select" class="wooai-select" data-entity-type="<?php echo esc_attr( $entity_type ); ?>">
							<option value=""><?php echo 'product' === $entity_type ? esc_html__( 'Select a product...', 'woo-ai-sales-manager' ) : esc_html__( 'Select a category...', 'woo-ai-sales-manager' ); ?></option>
						</select>
					</div>
				</div>
				<div class="wooai-chat-actions">
					<button type="button" id="wooai-new-chat" class="wooai-btn wooai-btn--secondary wooai-btn--sm">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'New Chat', 'woo-ai-sales-manager' ); ?>
					</button>
				</div>
			</div>

			<!-- Chat Messages -->
			<div class="wooai-chat-messages" id="wooai-chat-messages">
				<!-- Welcome State with Entity Cards -->
				<div class="wooai-chat-welcome" id="wooai-chat-welcome">
					<div class="wooai-chat-welcome__icon">
						<span class="dashicons dashicons-lightbulb"></span>
					</div>
					<h3><?php esc_html_e( 'What would you like to improve?', 'woo-ai-sales-manager' ); ?></h3>
					<p><?php esc_html_e( 'Select an entity type and item above, or choose from below to get started.', 'woo-ai-sales-manager' ); ?></p>
					
					<div class="wooai-welcome-cards">
						<button type="button" class="wooai-welcome-card" data-entity="product">
							<div class="wooai-welcome-card__icon">
								<span class="dashicons dashicons-products"></span>
							</div>
							<div class="wooai-welcome-card__content">
								<h4><?php esc_html_e( 'Products', 'woo-ai-sales-manager' ); ?></h4>
								<p><?php esc_html_e( 'Improve titles, descriptions, and tags for better conversions', 'woo-ai-sales-manager' ); ?></p>
							</div>
							<span class="wooai-welcome-card__arrow">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</span>
						</button>
						<button type="button" class="wooai-welcome-card" data-entity="category">
							<div class="wooai-welcome-card__icon wooai-welcome-card__icon--category">
								<span class="dashicons dashicons-category"></span>
							</div>
							<div class="wooai-welcome-card__content">
								<h4><?php esc_html_e( 'Categories', 'woo-ai-sales-manager' ); ?></h4>
								<p><?php esc_html_e( 'Generate compelling category descriptions and SEO content', 'woo-ai-sales-manager' ); ?></p>
							</div>
							<span class="wooai-welcome-card__arrow">
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</span>
						</button>
					</div>

					<?php if ( 'missing' === $context_status || 'partial' === $context_status ) : ?>
					<div class="wooai-welcome-context-hint">
						<span class="dashicons dashicons-info-outline"></span>
						<span><?php esc_html_e( 'Tip: Set up your store context for better AI-generated content', 'woo-ai-sales-manager' ); ?></span>
						<button type="button" class="wooai-btn wooai-btn--link" id="wooai-welcome-setup-context">
							<?php esc_html_e( 'Set up now', 'woo-ai-sales-manager' ); ?>
						</button>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Quick Actions - Product -->
			<div class="wooai-quick-actions" id="wooai-quick-actions-product" <?php echo 'category' === $entity_type ? 'style="display:none"' : ''; ?>>
				<span class="wooai-quick-actions__label"><?php esc_html_e( 'Quick Actions:', 'woo-ai-sales-manager' ); ?></span>
				<div class="wooai-quick-actions__buttons">
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="improve_title" disabled>
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Improve Title', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="improve_description" disabled>
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Improve Description', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="seo_optimize" disabled>
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Optimize', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="suggest_tags" disabled>
						<span class="dashicons dashicons-tag"></span>
						<?php esc_html_e( 'Suggest Tags', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="suggest_categories" disabled>
						<span class="dashicons dashicons-category"></span>
						<?php esc_html_e( 'Suggest Categories', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="generate_content" disabled>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Generate Content', 'woo-ai-sales-manager' ); ?>
					</button>
				</div>
			</div>

			<!-- Quick Actions - Category -->
			<div class="wooai-quick-actions" id="wooai-quick-actions-category" <?php echo 'product' === $entity_type ? 'style="display:none"' : ''; ?>>
				<span class="wooai-quick-actions__label"><?php esc_html_e( 'Quick Actions:', 'woo-ai-sales-manager' ); ?></span>
				<div class="wooai-quick-actions__buttons">
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="improve_name" disabled>
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Improve Name', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="improve_cat_description" disabled>
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Generate Description', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="cat_seo_optimize" disabled>
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Optimize', 'woo-ai-sales-manager' ); ?>
					</button>
					<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="generate_cat_content" disabled>
						<span class="dashicons dashicons-lightbulb"></span>
						<?php esc_html_e( 'Full Optimize', 'woo-ai-sales-manager' ); ?>
					</button>
				</div>
			</div>

			<!-- Message Input -->
			<div class="wooai-chat-input">
				<div class="wooai-chat-input__wrapper">
					<textarea
						id="wooai-message-input"
						placeholder="<?php esc_attr_e( 'Type your message...', 'woo-ai-sales-manager' ); ?>"
						rows="1"
						disabled
					></textarea>
					<button type="button" id="wooai-send-message" class="wooai-btn wooai-btn--primary" disabled>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
						<span class="wooai-btn__text"><?php esc_html_e( 'Send', 'woo-ai-sales-manager' ); ?></span>
					</button>
				</div>
				<div class="wooai-chat-input__hint">
					<span class="wooai-tokens-used" id="wooai-tokens-used"></span>
				</div>
			</div>
		</div>

		<!-- Entity Info Panel (Right - 30%) -->
		<div class="wooai-entity-panel" id="wooai-entity-panel">
			<!-- Empty State -->
			<div class="wooai-entity-empty" id="wooai-entity-empty">
				<span class="dashicons dashicons-<?php echo 'category' === $entity_type ? 'category' : 'products'; ?>" id="wooai-empty-icon"></span>
				<p id="wooai-empty-text"><?php echo 'category' === $entity_type ? esc_html__( 'Select a category to view details', 'woo-ai-sales-manager' ) : esc_html__( 'Select a product to view details', 'woo-ai-sales-manager' ); ?></p>
			</div>

			<!-- Product Info (hidden by default) -->
			<div class="wooai-product-info" id="wooai-product-info" style="display: none;">
				<!-- Product Image -->
				<div class="wooai-product-info__image">
					<img id="wooai-product-image" src="" alt="">
				</div>

				<!-- Product Title -->
				<div class="wooai-product-info__field" data-field="title">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-heading"></span>
						<?php esc_html_e( 'Title', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-title"></div>
					<div class="wooai-product-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Product Description -->
				<div class="wooai-product-info__field wooai-product-info__field--expandable" data-field="description">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Description', 'woo-ai-sales-manager' ); ?>
						<button type="button" class="wooai-expand-toggle">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
					<div class="wooai-product-info__value wooai-product-info__value--truncated" id="wooai-product-description"></div>
					<div class="wooai-product-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Product Short Description -->
				<div class="wooai-product-info__field" data-field="short_description">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-editor-paragraph"></span>
						<?php esc_html_e( 'Short Description', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-short-description"></div>
					<div class="wooai-product-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Category -->
				<div class="wooai-product-info__field" data-field="categories">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-category"></span>
						<?php esc_html_e( 'Categories', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-categories">
						<span class="wooai-no-value"><?php esc_html_e( 'None', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-product-info__pending" style="display: none;">
						<div class="wooai-pending-change wooai-pending-change--tags">
							<div class="wooai-pending-change__added"></div>
							<div class="wooai-pending-change__removed"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Tags -->
				<div class="wooai-product-info__field" data-field="tags">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-tag"></span>
						<?php esc_html_e( 'Tags', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value wooai-product-info__tags" id="wooai-product-tags">
						<span class="wooai-no-value"><?php esc_html_e( 'None', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-product-info__pending" style="display: none;">
						<div class="wooai-pending-change wooai-pending-change--tags">
							<div class="wooai-pending-change__added"></div>
							<div class="wooai-pending-change__removed"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Price -->
				<div class="wooai-product-info__field" data-field="price">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-money-alt"></span>
						<?php esc_html_e( 'Price', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-price"></div>
				</div>

				<!-- Stock -->
				<div class="wooai-product-info__field" data-field="stock">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-archive"></span>
						<?php esc_html_e( 'Stock', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-stock"></div>
				</div>

				<!-- Status -->
				<div class="wooai-product-info__field" data-field="status">
					<div class="wooai-product-info__label">
						<span class="dashicons dashicons-post-status"></span>
						<?php esc_html_e( 'Status', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-product-info__value" id="wooai-product-status"></div>
				</div>

				<!-- Pending Changes Summary -->
				<div class="wooai-pending-summary" id="wooai-pending-summary" style="display: none;">
					<div class="wooai-pending-summary__header">
						<span class="dashicons dashicons-warning"></span>
						<span id="wooai-pending-count">0</span> <?php esc_html_e( 'Pending Changes', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-pending-summary__actions">
						<button type="button" id="wooai-accept-all" class="wooai-btn wooai-btn--success wooai-btn--sm">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Accept All', 'woo-ai-sales-manager' ); ?>
						</button>
						<button type="button" id="wooai-discard-all" class="wooai-btn wooai-btn--danger wooai-btn--sm">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Discard All', 'woo-ai-sales-manager' ); ?>
						</button>
					</div>
				</div>

				<!-- Product Actions -->
				<div class="wooai-product-info__actions">
					<a id="wooai-edit-product" href="#" class="wooai-btn wooai-btn--secondary wooai-btn--sm" target="_blank">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit Product', 'woo-ai-sales-manager' ); ?>
					</a>
					<a id="wooai-view-product" href="#" class="wooai-btn wooai-btn--outline wooai-btn--sm" target="_blank">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View', 'woo-ai-sales-manager' ); ?>
					</a>
				</div>
			</div>

			<!-- Category Info (hidden by default) -->
			<div class="wooai-category-info" id="wooai-category-info" style="display: none;">
				<!-- Category Header -->
				<div class="wooai-category-info__header">
					<div class="wooai-category-info__icon">
						<span class="dashicons dashicons-category"></span>
					</div>
					<div class="wooai-category-info__title">
						<span id="wooai-category-name">Category Name</span>
						<span class="wooai-category-info__parent" id="wooai-category-parent"></span>
					</div>
				</div>

				<!-- Category Stats -->
				<div class="wooai-category-info__stats">
					<div class="wooai-category-stat">
						<span class="wooai-category-stat__value" id="wooai-category-product-count">0</span>
						<span class="wooai-category-stat__label"><?php esc_html_e( 'Products', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-category-stat">
						<span class="wooai-category-stat__value" id="wooai-category-subcat-count">0</span>
						<span class="wooai-category-stat__label"><?php esc_html_e( 'Subcategories', 'woo-ai-sales-manager' ); ?></span>
					</div>
				</div>

				<!-- Category Name Field -->
				<div class="wooai-category-info__field" data-field="name">
					<div class="wooai-category-info__label">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Name', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-category-info__value" id="wooai-category-name-value"></div>
					<div class="wooai-category-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Category Description -->
				<div class="wooai-category-info__field wooai-category-info__field--expandable" data-field="description">
					<div class="wooai-category-info__label">
						<span class="dashicons dashicons-text"></span>
						<?php esc_html_e( 'Description', 'woo-ai-sales-manager' ); ?>
						<button type="button" class="wooai-expand-toggle">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</button>
					</div>
					<div class="wooai-category-info__value wooai-category-info__value--truncated" id="wooai-category-description">
						<span class="wooai-no-value"><?php esc_html_e( 'No description', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-category-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- SEO Title -->
				<div class="wooai-category-info__field" data-field="seo_title">
					<div class="wooai-category-info__label">
						<span class="dashicons dashicons-search"></span>
						<?php esc_html_e( 'SEO Title', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-category-info__value" id="wooai-category-seo-title">
						<span class="wooai-no-value"><?php esc_html_e( 'Not set', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-category-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Meta Description -->
				<div class="wooai-category-info__field" data-field="meta_description">
					<div class="wooai-category-info__label">
						<span class="dashicons dashicons-media-text"></span>
						<?php esc_html_e( 'Meta Description', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-category-info__value" id="wooai-category-meta-description">
						<span class="wooai-no-value"><?php esc_html_e( 'Not set', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-category-info__pending" style="display: none;">
						<div class="wooai-pending-change">
							<div class="wooai-pending-change__new"></div>
							<div class="wooai-pending-change__actions">
								<button type="button" class="wooai-btn wooai-btn--success wooai-btn--xs" data-action="accept">
									<span class="dashicons dashicons-yes"></span>
								</button>
								<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--xs" data-action="undo">
									<span class="dashicons dashicons-undo"></span>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Subcategories -->
				<div class="wooai-category-info__field" data-field="subcategories">
					<div class="wooai-category-info__label">
						<span class="dashicons dashicons-networking"></span>
						<?php esc_html_e( 'Subcategories', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-category-info__value wooai-category-info__subcats" id="wooai-category-subcategories">
						<span class="wooai-no-value"><?php esc_html_e( 'None', 'woo-ai-sales-manager' ); ?></span>
					</div>
				</div>

				<!-- Pending Changes Summary (Category) -->
				<div class="wooai-pending-summary" id="wooai-category-pending-summary" style="display: none;">
					<div class="wooai-pending-summary__header">
						<span class="dashicons dashicons-warning"></span>
						<span id="wooai-category-pending-count">0</span> <?php esc_html_e( 'Pending Changes', 'woo-ai-sales-manager' ); ?>
					</div>
					<div class="wooai-pending-summary__actions">
						<button type="button" id="wooai-category-accept-all" class="wooai-btn wooai-btn--success wooai-btn--sm">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Accept All', 'woo-ai-sales-manager' ); ?>
						</button>
						<button type="button" id="wooai-category-discard-all" class="wooai-btn wooai-btn--danger wooai-btn--sm">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Discard All', 'woo-ai-sales-manager' ); ?>
						</button>
					</div>
				</div>

				<!-- Category Actions -->
				<div class="wooai-category-info__actions">
					<a id="wooai-edit-category" href="#" class="wooai-btn wooai-btn--secondary wooai-btn--sm" target="_blank">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit Category', 'woo-ai-sales-manager' ); ?>
					</a>
					<a id="wooai-view-category" href="#" class="wooai-btn wooai-btn--outline wooai-btn--sm" target="_blank">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View', 'woo-ai-sales-manager' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Message Templates -->
<script type="text/template" id="wooai-message-template">
	<div class="wooai-message wooai-message--{role}" data-message-id="{id}">
		<div class="wooai-message__avatar">
			<span class="dashicons {icon}"></span>
		</div>
		<div class="wooai-message__content">
			<div class="wooai-message__text">{content}</div>
			<div class="wooai-message__meta">
				<span class="wooai-message__time">{time}</span>
				{tokens}
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="wooai-suggestion-template">
	<div class="wooai-suggestion" data-suggestion-id="{id}" data-suggestion-type="{type}">
		<div class="wooai-suggestion__header">
			<span class="dashicons dashicons-lightbulb"></span>
			<span class="wooai-suggestion__type">{typeLabel}</span>
		</div>
		<div class="wooai-suggestion__preview">
			<div class="wooai-suggestion__current">
				<div class="wooai-suggestion__label"><?php esc_html_e( 'Current:', 'woo-ai-sales-manager' ); ?></div>
				<div class="wooai-suggestion__value">{currentValue}</div>
			</div>
			<div class="wooai-suggestion__arrow">
				<span class="dashicons dashicons-arrow-right-alt"></span>
			</div>
			<div class="wooai-suggestion__new">
				<div class="wooai-suggestion__label"><?php esc_html_e( 'Suggested:', 'woo-ai-sales-manager' ); ?></div>
				<div class="wooai-suggestion__value">{suggestedValue}</div>
			</div>
		</div>
		<div class="wooai-suggestion__actions">
			<button type="button" class="wooai-btn wooai-btn--success wooai-btn--sm" data-action="apply">
				<span class="dashicons dashicons-yes"></span>
				<?php esc_html_e( 'Apply', 'woo-ai-sales-manager' ); ?>
			</button>
			<button type="button" class="wooai-btn wooai-btn--outline wooai-btn--sm" data-action="edit">
				<span class="dashicons dashicons-edit"></span>
				<?php esc_html_e( 'Edit', 'woo-ai-sales-manager' ); ?>
			</button>
			<button type="button" class="wooai-btn wooai-btn--danger wooai-btn--sm" data-action="discard">
				<span class="dashicons dashicons-no"></span>
				<?php esc_html_e( 'Discard', 'woo-ai-sales-manager' ); ?>
			</button>
		</div>
	</div>
</script>

<script type="text/template" id="wooai-thinking-template">
	<div class="wooai-message wooai-message--assistant wooai-message--thinking">
		<div class="wooai-message__avatar">
			<span class="dashicons dashicons-admin-site-alt3"></span>
		</div>
		<div class="wooai-message__content">
			<div class="wooai-thinking">
				<span class="wooai-thinking__dot"></span>
				<span class="wooai-thinking__dot"></span>
				<span class="wooai-thinking__dot"></span>
				<span class="wooai-thinking__text"><?php esc_html_e( 'Thinking...', 'woo-ai-sales-manager' ); ?></span>
			</div>
		</div>
	</div>
</script>

<?php if ( ! empty( $product_data ) ) : ?>
<script type="text/javascript">
	// Pre-select product from metabox redirect
	window.wooaiPreselectedProduct = <?php echo wp_json_encode( $product_data ); ?>;
	window.wooaiPreselectedEntityType = 'product';
</script>
<?php endif; ?>

<?php if ( ! empty( $category_data ) ) : ?>
<script type="text/javascript">
	// Pre-select category from URL parameter
	window.wooaiPreselectedCategory = <?php echo wp_json_encode( $category_data ); ?>;
	window.wooaiPreselectedEntityType = 'category';
</script>
<?php endif; ?>

<script type="text/javascript">
	// Store context status for JS
	window.wooaiStoreContext = {
		status: '<?php echo esc_js( $context_status ); ?>',
		isConfigured: <?php echo $context_status === 'configured' ? 'true' : 'false'; ?>
	};
</script>
