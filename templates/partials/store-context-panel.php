<?php
/**
 * Store Context Panel Partial
 *
 * Shared slide-out panel for configuring store context used by AI.
 * Used on both the AI Agent page and the main AI Sales Manager page.
 *
 * Expected variable:
 * - $aisales_store_context (array) - Current store context from get_option( 'aisales_store_context' )
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Ensure store_context is set
if ( ! isset( $aisales_store_context ) ) {
	$aisales_store_context = get_option( 'aisales_store_context', array() );
}
?>
<!-- Store Context Slide-out Panel -->
<div class="aisales-context-panel" id="aisales-context-panel">
	<div class="aisales-context-panel__backdrop" id="aisales-context-backdrop"></div>
	<div class="aisales-context-panel__content">
		<div class="aisales-context-panel__header">
			<h2>
				<span class="dashicons dashicons-store"></span>
				<?php esc_html_e( 'Store Context', 'ai-sales-manager-for-woocommerce' ); ?>
			</h2>
			<button type="button" class="aisales-context-panel__close" id="aisales-close-context">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="aisales-context-panel__body">
			<p class="aisales-context-panel__intro">
				<?php esc_html_e( 'Your store information helps AI write better content that matches your brand.', 'ai-sales-manager-for-woocommerce' ); ?>
			</p>

			<form id="aisales-context-form" class="aisales-context-form">
				<!-- Store Name -->
				<div class="aisales-context-field">
					<label for="aisales-store-name"><?php esc_html_e( 'Store Name', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<input type="text" id="aisales-store-name" name="store_name" 
						value="<?php echo esc_attr( isset( $aisales_store_context['store_name'] ) ? $aisales_store_context['store_name'] : get_bloginfo( 'name' ) ); ?>"
						placeholder="<?php esc_attr_e( 'My Awesome Store', 'ai-sales-manager-for-woocommerce' ); ?>">
				</div>

				<!-- Store Description -->
				<div class="aisales-context-field">
					<label for="aisales-store-description"><?php esc_html_e( 'Store Description', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<textarea id="aisales-store-description" name="store_description" rows="2"
						placeholder="<?php esc_attr_e( 'Brief description of what you sell...', 'ai-sales-manager-for-woocommerce' ); ?>"><?php echo esc_textarea( isset( $aisales_store_context['store_description'] ) ? $aisales_store_context['store_description'] : get_bloginfo( 'description' ) ); ?></textarea>
				</div>

				<!-- Business Niche -->
				<div class="aisales-context-field">
					<label for="aisales-business-niche"><?php esc_html_e( 'Business Niche', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<select id="aisales-business-niche" name="business_niche">
						<option value=""><?php esc_html_e( 'Select a niche...', 'ai-sales-manager-for-woocommerce' ); ?></option>
						<?php
						$aisales_niches = array(
							'fashion'       => __( 'Fashion & Apparel', 'ai-sales-manager-for-woocommerce' ),
							'electronics'   => __( 'Electronics & Tech', 'ai-sales-manager-for-woocommerce' ),
							'home_garden'   => __( 'Home & Garden', 'ai-sales-manager-for-woocommerce' ),
							'health_beauty' => __( 'Health & Beauty', 'ai-sales-manager-for-woocommerce' ),
							'sports'        => __( 'Sports & Outdoors', 'ai-sales-manager-for-woocommerce' ),
							'toys_games'    => __( 'Toys & Games', 'ai-sales-manager-for-woocommerce' ),
							'food_beverage' => __( 'Food & Beverage', 'ai-sales-manager-for-woocommerce' ),
							'jewelry'       => __( 'Jewelry & Accessories', 'ai-sales-manager-for-woocommerce' ),
							'automotive'    => __( 'Automotive', 'ai-sales-manager-for-woocommerce' ),
							'books_media'   => __( 'Books & Media', 'ai-sales-manager-for-woocommerce' ),
							'pets'          => __( 'Pet Supplies', 'ai-sales-manager-for-woocommerce' ),
							'art_crafts'    => __( 'Art & Crafts', 'ai-sales-manager-for-woocommerce' ),
							'office'        => __( 'Office & Business', 'ai-sales-manager-for-woocommerce' ),
							'baby_kids'     => __( 'Baby & Kids', 'ai-sales-manager-for-woocommerce' ),
							'other'         => __( 'Other', 'ai-sales-manager-for-woocommerce' ),
						);
						$aisales_current_niche = isset( $aisales_store_context['business_niche'] ) ? $aisales_store_context['business_niche'] : '';
						foreach ( $aisales_niches as $aisales_value => $aisales_label ) :
						?>
							<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_current_niche, $aisales_value ); ?>><?php echo esc_html( $aisales_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Target Audience -->
				<div class="aisales-context-field">
					<label for="aisales-target-audience"><?php esc_html_e( 'Target Audience', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<input type="text" id="aisales-target-audience" name="target_audience"
						value="<?php echo esc_attr( isset( $aisales_store_context['target_audience'] ) ? $aisales_store_context['target_audience'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'e.g., Young professionals 25-40', 'ai-sales-manager-for-woocommerce' ); ?>">
				</div>

				<!-- Brand Tone -->
				<div class="aisales-context-field">
					<label><?php esc_html_e( 'Brand Tone', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<div class="aisales-tone-options">
						<?php
						$aisales_tones = array(
							'professional' => array( 'icon' => 'businessman', 'label' => __( 'Professional', 'ai-sales-manager-for-woocommerce' ) ),
							'friendly'     => array( 'icon' => 'smiley', 'label' => __( 'Friendly', 'ai-sales-manager-for-woocommerce' ) ),
							'luxury'       => array( 'icon' => 'star-filled', 'label' => __( 'Luxury', 'ai-sales-manager-for-woocommerce' ) ),
							'casual'       => array( 'icon' => 'format-status', 'label' => __( 'Casual', 'ai-sales-manager-for-woocommerce' ) ),
							'technical'    => array( 'icon' => 'desktop', 'label' => __( 'Technical', 'ai-sales-manager-for-woocommerce' ) ),
							'playful'      => array( 'icon' => 'heart', 'label' => __( 'Playful', 'ai-sales-manager-for-woocommerce' ) ),
						);
						$aisales_current_tone = isset( $aisales_store_context['brand_tone'] ) ? $aisales_store_context['brand_tone'] : '';
						foreach ( $aisales_tones as $aisales_value => $aisales_tone ) :
						?>
							<label class="aisales-tone-option">
								<input type="radio" name="brand_tone" value="<?php echo esc_attr( $aisales_value ); ?>" <?php checked( $aisales_current_tone, $aisales_value ); ?>>
								<span class="aisales-tone-option__content">
									<span class="dashicons dashicons-<?php echo esc_attr( $aisales_tone['icon'] ); ?>"></span>
									<span><?php echo esc_html( $aisales_tone['label'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Language -->
				<div class="aisales-context-field">
					<label for="aisales-language"><?php esc_html_e( 'Content Language', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<select id="aisales-language" name="language">
						<?php
						$aisales_languages = array(
							'English'    => __( 'English', 'ai-sales-manager-for-woocommerce' ),
							'Spanish'    => __( 'Spanish', 'ai-sales-manager-for-woocommerce' ),
							'French'     => __( 'French', 'ai-sales-manager-for-woocommerce' ),
							'German'     => __( 'German', 'ai-sales-manager-for-woocommerce' ),
							'Italian'    => __( 'Italian', 'ai-sales-manager-for-woocommerce' ),
							'Portuguese' => __( 'Portuguese', 'ai-sales-manager-for-woocommerce' ),
							'Dutch'      => __( 'Dutch', 'ai-sales-manager-for-woocommerce' ),
							'Japanese'   => __( 'Japanese', 'ai-sales-manager-for-woocommerce' ),
							'Chinese'    => __( 'Chinese', 'ai-sales-manager-for-woocommerce' ),
							'Korean'     => __( 'Korean', 'ai-sales-manager-for-woocommerce' ),
							'Thai'       => __( 'Thai', 'ai-sales-manager-for-woocommerce' ),
						);
						$aisales_current_lang = isset( $aisales_store_context['language'] ) ? $aisales_store_context['language'] : 'English';
						foreach ( $aisales_languages as $aisales_value => $aisales_label ) :
						?>
							<option value="<?php echo esc_attr( $aisales_value ); ?>" <?php selected( $aisales_current_lang, $aisales_value ); ?>><?php echo esc_html( $aisales_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Custom Instructions -->
				<div class="aisales-context-field">
					<label for="aisales-custom-instructions"><?php esc_html_e( 'Custom Instructions', 'ai-sales-manager-for-woocommerce' ); ?> <span class="aisales-optional"><?php esc_html_e( '(optional)', 'ai-sales-manager-for-woocommerce' ); ?></span></label>
					<textarea id="aisales-custom-instructions" name="custom_instructions" rows="3"
						placeholder="<?php esc_attr_e( 'e.g., Always mention free shipping, use metric units, avoid certain words...', 'ai-sales-manager-for-woocommerce' ); ?>"><?php echo esc_textarea( isset( $aisales_store_context['custom_instructions'] ) ? $aisales_store_context['custom_instructions'] : '' ); ?></textarea>
				</div>

				<!-- Sync Status -->
				<div class="aisales-context-sync">
					<div class="aisales-context-sync__info">
						<span class="dashicons dashicons-update"></span>
						<span id="aisales-sync-status">
							<?php
							$aisales_last_sync = isset( $aisales_store_context['last_sync'] ) ? $aisales_store_context['last_sync'] : '';
							if ( $aisales_last_sync ) {
								printf(
									/* translators: %1$s: number of categories, %2$s: number of products, %3$s: date */
									esc_html__( 'Synced: %1$s categories, %2$s products on %3$s', 'ai-sales-manager-for-woocommerce' ),
									isset( $aisales_store_context['category_count'] ) ? esc_html( $aisales_store_context['category_count'] ) : '0',
									isset( $aisales_store_context['product_count'] ) ? esc_html( $aisales_store_context['product_count'] ) : '0',
									esc_html( date_i18n( get_option( 'date_format' ), strtotime( $aisales_last_sync ) ) )
								);
							} else {
								esc_html_e( 'Not synced yet', 'ai-sales-manager-for-woocommerce' );
							}
							?>
						</span>
					</div>
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm" id="aisales-sync-context">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Sync Now', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</form>
		</div>
		<div class="aisales-context-panel__footer">
			<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-cancel-context">
				<?php esc_html_e( 'Cancel', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-save-context">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Save Context', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
