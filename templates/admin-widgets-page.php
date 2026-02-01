<?php
/**
 * Admin Widgets Page Template
 *
 * A beautiful, modern dashboard for managing WooCommerce widgets/shortcodes.
 * Features live previews, shortcode builders, and intuitive controls.
 *
 * Variables passed from AISales_Widgets_Page::render_page():
 * - $aisales_settings (array) - Current widget settings
 * - $aisales_categories (array) - Widget categories
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

$aisales_widgets_page  = AISales_Widgets_Page::instance();
$aisales_all_widgets   = $aisales_widgets_page->get_widgets();

// Count only feature/injectable-type widgets for the active badge.
$aisales_toggleable_widgets = array_filter( $aisales_all_widgets, function( $w ) { return in_array( $w['type'], array( 'feature', 'injectable' ), true ); } );
$aisales_enabled_toggleable = array_intersect( array_keys( $aisales_toggleable_widgets ), $aisales_settings['enabled_widgets'] );
$aisales_enabled_count   = count( $aisales_enabled_toggleable );
$aisales_feature_count   = count( $aisales_toggleable_widgets );
$aisales_total_count     = count( $aisales_all_widgets );
?>

<div class="wrap aisales-admin-wrap aisales-widgets-wrap">
	<!-- WordPress Admin Notices Area -->
	<h1 class="aisales-notices-anchor"></h1>

	<!-- Page Header -->
	<header class="aisales-widgets-header">
		<div class="aisales-widgets-header__left">
			<span class="aisales-widgets-header__title">
				<span class="dashicons dashicons-screenoptions"></span>
				<?php esc_html_e( 'Widgets & Shortcodes', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
			<span class="aisales-widgets-header__subtitle"><?php esc_html_e( 'Boost conversions with free, powerful widgets', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</div>
		<div class="aisales-widgets-header__right">
			<span class="aisales-widgets-active-badge">
				<span class="dashicons dashicons-yes-alt"></span>
				<span class="aisales-widgets-active-badge__count" id="aisales-widgets-active-count"><?php echo esc_html( $aisales_enabled_count ); ?></span>
				<?php esc_html_e( 'active', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
	</header>

	<!-- Category Tabs -->
	<nav class="aisales-widgets-tabs">
		<button type="button" class="aisales-widgets-tab aisales-widgets-tab--active" data-category="all">
			<span class="dashicons dashicons-grid-view"></span>
			<span class="aisales-widgets-tab__text"><?php esc_html_e( 'All Widgets', 'ai-sales-manager-for-woocommerce' ); ?></span>
			<span class="aisales-widgets-tab__count"><?php echo esc_html( $aisales_total_count ); ?></span>
		</button>
		<?php foreach ( $aisales_categories as $cat_slug => $cat_data ) : ?>
		<button type="button" class="aisales-widgets-tab" data-category="<?php echo esc_attr( $cat_slug ); ?>">
			<span class="dashicons <?php echo esc_attr( $cat_data['icon'] ); ?>"></span>
			<span class="aisales-widgets-tab__text"><?php echo esc_html( $cat_data['name'] ); ?></span>
			<span class="aisales-widgets-tab__count"><?php echo esc_html( count( $aisales_widgets_page->get_widgets_by_category( $cat_slug ) ) ); ?></span>
		</button>
		<?php endforeach; ?>
		<button type="button" class="aisales-widgets-tab" data-category="settings">
			<span class="dashicons dashicons-admin-generic"></span>
			<span class="aisales-widgets-tab__text"><?php esc_html_e( 'Settings', 'ai-sales-manager-for-woocommerce' ); ?></span>
		</button>
	</nav>

	<!-- Widgets Grid -->
	<div class="aisales-widgets-content">
		<div class="aisales-widgets-grid" id="widgets-grid">
		<?php foreach ( $aisales_all_widgets as $widget_key => $widget ) : 
			$is_enabled    = in_array( $widget_key, $aisales_settings['enabled_widgets'], true );
			$is_toggleable = in_array( $widget['type'], array( 'feature', 'injectable' ), true );
			$is_injectable = 'injectable' === $widget['type'];
			$card_class    = 'aisales-widget-card';
			$card_class   .= $is_toggleable ? ' aisales-widget-card--toggleable' : ' aisales-widget-card--shortcode';
			if ( $is_injectable ) {
				$card_class .= ' aisales-widget-card--injectable';
			}
			// Get current position for injectable widgets.
			$current_position = '';
			if ( $is_injectable && isset( $aisales_settings['widget_positions'][ $widget_key ] ) ) {
				$current_position = $aisales_settings['widget_positions'][ $widget_key ];
			} elseif ( $is_injectable && isset( $widget['default_pos'] ) ) {
				$current_position = $widget['default_pos'];
			}
		?>
			<div class="<?php echo esc_attr( $card_class ); ?>" data-widget="<?php echo esc_attr( $widget_key ); ?>" data-category="<?php echo esc_attr( $widget['category'] ); ?>" data-type="<?php echo esc_attr( $widget['type'] ); ?>">
				<!-- Card Header -->
				<div class="aisales-widget-card__header">
					<div class="aisales-widget-card__icon aisales-widget-card__icon--<?php echo esc_attr( $widget['category'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $widget['icon'] ); ?>"></span>
					</div>
					<div class="aisales-widget-card__info">
						<h3 class="aisales-widget-card__name"><?php echo esc_html( $widget['name'] ); ?></h3>
						<span class="aisales-widget-card__category"><?php echo esc_html( $aisales_categories[ $widget['category'] ]['name'] ); ?></span>
					</div>
					<?php if ( $is_toggleable ) : ?>
					<label class="aisales-toggle-switch">
						<input type="checkbox" 
							   name="widget_enabled[<?php echo esc_attr( $widget_key ); ?>]" 
							   value="1" 
							   <?php checked( $is_enabled ); ?>
							   data-widget="<?php echo esc_attr( $widget_key ); ?>">
						<span class="aisales-toggle-switch__slider"></span>
					</label>
					<?php endif; ?>
				</div>

				<!-- Card Description -->
				<p class="aisales-widget-card__description"><?php echo esc_html( $widget['description'] ); ?></p>

				<!-- Live Preview -->
				<div class="aisales-widget-card__preview">
					<div class="aisales-widget-card__preview-label">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Preview', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-widget-card__preview-content">
						<?php aisales_render_widget_preview( $widget_key, $widget ); ?>
					</div>
				</div>

				<!-- Shortcode Display -->
				<div class="aisales-widget-card__shortcode">
					<div class="aisales-widget-card__shortcode-label"><?php esc_html_e( 'Shortcode', 'ai-sales-manager-for-woocommerce' ); ?></div>
					<div class="aisales-widget-card__shortcode-box">
						<code class="aisales-widget-card__shortcode-code">[<?php echo esc_html( $widget['shortcode'] ); ?>]</code>
						<button type="button" class="aisales-widget-card__copy-btn" data-shortcode="[<?php echo esc_attr( $widget['shortcode'] ); ?>]" title="<?php esc_attr_e( 'Copy to clipboard', 'ai-sales-manager-for-woocommerce' ); ?>">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
				</div>

				<!-- Card Actions -->
				<div class="aisales-widget-card__actions">
					<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-widget-card__settings-btn" data-widget="<?php echo esc_attr( $widget_key ); ?>">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Settings', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--text aisales-btn--sm aisales-widget-card__docs-btn" data-widget="<?php echo esc_attr( $widget_key ); ?>">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Docs', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Settings Panel -->
		<div class="aisales-widgets-settings" id="widgets-settings" style="display: none;">
			<div class="aisales-widgets-settings__section">
				<h2 class="aisales-widgets-settings__title">
					<span class="dashicons dashicons-admin-appearance"></span>
					<?php esc_html_e( 'Appearance', 'ai-sales-manager-for-woocommerce' ); ?>
				</h2>
				<div class="aisales-widgets-settings__content">
					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Styling Mode', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<div class="aisales-field__options">
							<label class="aisales-radio-card">
								<input type="radio" name="styling_mode" value="inherit" <?php checked( $aisales_settings['styling_mode'], 'inherit' ); ?>>
								<div class="aisales-radio-card__content">
									<span class="dashicons dashicons-admin-customizer"></span>
									<span class="aisales-radio-card__title"><?php esc_html_e( 'Inherit Theme', 'ai-sales-manager-for-woocommerce' ); ?></span>
									<span class="aisales-radio-card__desc"><?php esc_html_e( 'Match your theme styles', 'ai-sales-manager-for-woocommerce' ); ?></span>
								</div>
							</label>
							<label class="aisales-radio-card">
								<input type="radio" name="styling_mode" value="custom" <?php checked( $aisales_settings['styling_mode'], 'custom' ); ?>>
								<div class="aisales-radio-card__content">
									<span class="dashicons dashicons-art"></span>
									<span class="aisales-radio-card__title"><?php esc_html_e( 'Custom Styles', 'ai-sales-manager-for-woocommerce' ); ?></span>
									<span class="aisales-radio-card__desc"><?php esc_html_e( 'Use custom colors', 'ai-sales-manager-for-woocommerce' ); ?></span>
								</div>
							</label>
						</div>
					</div>

					<div class="aisales-field aisales-field--colors" id="custom-colors" style="<?php echo $aisales_settings['styling_mode'] !== 'custom' ? 'display:none;' : ''; ?>">
						<label class="aisales-field__label"><?php esc_html_e( 'Custom Colors', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<div class="aisales-color-grid">
							<div class="aisales-color-field">
								<label><?php esc_html_e( 'Primary', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="color" name="colors[primary]" value="<?php echo esc_attr( $aisales_settings['colors']['primary'] ); ?>">
							</div>
							<div class="aisales-color-field">
								<label><?php esc_html_e( 'Success', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="color" name="colors[success]" value="<?php echo esc_attr( $aisales_settings['colors']['success'] ); ?>">
							</div>
							<div class="aisales-color-field">
								<label><?php esc_html_e( 'Urgency', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="color" name="colors[urgency]" value="<?php echo esc_attr( $aisales_settings['colors']['urgency'] ); ?>">
							</div>
							<div class="aisales-color-field">
								<label><?php esc_html_e( 'Text', 'ai-sales-manager-for-woocommerce' ); ?></label>
								<input type="color" name="colors[text]" value="<?php echo esc_attr( $aisales_settings['colors']['text'] ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="aisales-widgets-settings__section">
				<h2 class="aisales-widgets-settings__title">
					<span class="dashicons dashicons-groups"></span>
					<?php esc_html_e( 'Social Proof Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</h2>
				<div class="aisales-widgets-settings__content">
					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Customer Privacy Level', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<p class="aisales-field__help"><?php esc_html_e( 'How to display customer information in recent purchase notifications', 'ai-sales-manager-for-woocommerce' ); ?></p>
						<select name="social_proof[privacy_level]" class="aisales-select">
							<option value="full" <?php selected( $aisales_settings['social_proof']['privacy_level'], 'full' ); ?>><?php esc_html_e( 'Full Name (John Smith)', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="first_city" <?php selected( $aisales_settings['social_proof']['privacy_level'], 'first_city' ); ?>><?php esc_html_e( 'First Name + City (John from NYC)', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="anonymous" <?php selected( $aisales_settings['social_proof']['privacy_level'], 'anonymous' ); ?>><?php esc_html_e( 'Anonymous (Someone from NYC)', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
					</div>

					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Popup Position', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<div class="aisales-position-grid">
							<label class="aisales-position-option">
								<input type="radio" name="social_proof[popup_position]" value="top-left" <?php checked( $aisales_settings['social_proof']['popup_position'], 'top-left' ); ?>>
								<span class="aisales-position-option__box">
									<span class="aisales-position-option__dot aisales-position-option__dot--top-left"></span>
								</span>
							</label>
							<label class="aisales-position-option">
								<input type="radio" name="social_proof[popup_position]" value="top-right" <?php checked( $aisales_settings['social_proof']['popup_position'], 'top-right' ); ?>>
								<span class="aisales-position-option__box">
									<span class="aisales-position-option__dot aisales-position-option__dot--top-right"></span>
								</span>
							</label>
							<label class="aisales-position-option">
								<input type="radio" name="social_proof[popup_position]" value="bottom-left" <?php checked( $aisales_settings['social_proof']['popup_position'], 'bottom-left' ); ?>>
								<span class="aisales-position-option__box">
									<span class="aisales-position-option__dot aisales-position-option__dot--bottom-left"></span>
								</span>
							</label>
							<label class="aisales-position-option">
								<input type="radio" name="social_proof[popup_position]" value="bottom-right" <?php checked( $aisales_settings['social_proof']['popup_position'], 'bottom-right' ); ?>>
								<span class="aisales-position-option__box">
									<span class="aisales-position-option__dot aisales-position-option__dot--bottom-right"></span>
								</span>
							</label>
						</div>
					</div>

					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Popup Duration', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<div class="aisales-range-field">
							<input type="range" name="social_proof[popup_duration]" min="3" max="15" value="<?php echo esc_attr( $aisales_settings['social_proof']['popup_duration'] ); ?>" class="aisales-range">
							<span class="aisales-range-value"><span id="popup-duration-value"><?php echo esc_html( $aisales_settings['social_proof']['popup_duration'] ); ?></span>s</span>
						</div>
					</div>
				</div>
			</div>

			<div class="aisales-widgets-settings__section">
				<h2 class="aisales-widgets-settings__title">
					<span class="dashicons dashicons-chart-area"></span>
					<?php esc_html_e( 'Conversion Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</h2>
				<div class="aisales-widgets-settings__content">
					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Free Shipping Threshold', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<p class="aisales-field__help"><?php esc_html_e( 'Set to 0 to use your WooCommerce shipping settings', 'ai-sales-manager-for-woocommerce' ); ?></p>
						<div class="aisales-input-group">
							<span class="aisales-input-group__prefix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
							<input type="number" name="conversion[shipping_threshold]" value="<?php echo esc_attr( $aisales_settings['conversion']['shipping_threshold'] ); ?>" min="0" step="0.01" class="aisales-input">
						</div>
					</div>

					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Stock Urgency Threshold', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<p class="aisales-field__help"><?php esc_html_e( 'Show "Only X left" when stock is at or below this number', 'ai-sales-manager-for-woocommerce' ); ?></p>
						<input type="number" name="conversion[stock_urgency_at]" value="<?php echo esc_attr( $aisales_settings['conversion']['stock_urgency_at'] ); ?>" min="1" max="100" class="aisales-input aisales-input--sm">
					</div>

					<div class="aisales-field">
						<label class="aisales-field__label"><?php esc_html_e( 'Cache Duration', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<p class="aisales-field__help"><?php esc_html_e( 'How long to cache widget data for performance', 'ai-sales-manager-for-woocommerce' ); ?></p>
						<select name="cache_duration" class="aisales-select">
							<option value="900" <?php selected( $aisales_settings['cache_duration'], 900 ); ?>><?php esc_html_e( '15 minutes', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="1800" <?php selected( $aisales_settings['cache_duration'], 1800 ); ?>><?php esc_html_e( '30 minutes', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="3600" <?php selected( $aisales_settings['cache_duration'], 3600 ); ?>><?php esc_html_e( '1 hour', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="7200" <?php selected( $aisales_settings['cache_duration'], 7200 ); ?>><?php esc_html_e( '2 hours', 'ai-sales-manager-for-woocommerce' ); ?></option>
							<option value="21600" <?php selected( $aisales_settings['cache_duration'], 21600 ); ?>><?php esc_html_e( '6 hours', 'ai-sales-manager-for-woocommerce' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="aisales-widgets-settings__actions">
				<button type="button" class="aisales-btn aisales-btn--primary" id="save-settings">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'Save Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Shortcode Builder Modal -->
<div class="aisales-modal-overlay" id="builder-modal-overlay">
	<div class="aisales-modal aisales-builder-modal" id="builder-modal">
		<div class="aisales-modal__header">
			<h2 class="aisales-modal__title">
				<span class="dashicons dashicons-admin-settings"></span>
				<span id="builder-modal-title"><?php esc_html_e( 'Shortcode Builder', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</h2>
			<button type="button" class="aisales-modal__close" id="builder-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="aisales-modal__body">
			<div class="aisales-builder">
				<div class="aisales-builder__form" id="builder-form">
					<!-- Dynamic form fields will be inserted here -->
				</div>
				<div class="aisales-builder__preview">
					<div class="aisales-builder__preview-label">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Live Preview', 'ai-sales-manager-for-woocommerce' ); ?>
					</div>
					<div class="aisales-builder__preview-content" id="builder-preview">
						<!-- Preview will be rendered here -->
					</div>
				</div>
				<div class="aisales-builder__output">
					<label class="aisales-builder__output-label"><?php esc_html_e( 'Generated Shortcode', 'ai-sales-manager-for-woocommerce' ); ?></label>
					<div class="aisales-builder__output-box">
						<code id="builder-shortcode">[shortcode]</code>
						<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--sm" id="builder-copy">
							<span class="dashicons dashicons-admin-page"></span>
							<?php esc_html_e( 'Copy', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div class="aisales-modal__footer">
			<div class="aisales-modal__info">
				<span class="dashicons dashicons-info-outline"></span>
				<?php esc_html_e( 'Paste this shortcode anywhere in your pages, posts, or widgets', 'ai-sales-manager-for-woocommerce' ); ?>
			</div>
		</div>
	</div>
</div>

<!-- Documentation Modal -->
<div class="aisales-modal-overlay" id="docs-modal-overlay">
	<div class="aisales-modal aisales-docs-modal" id="docs-modal">
		<div class="aisales-modal__header">
			<h2 class="aisales-modal__title">
				<span class="dashicons dashicons-book"></span>
				<span id="docs-modal-title"><?php esc_html_e( 'Documentation', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</h2>
			<button type="button" class="aisales-modal__close" id="docs-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="aisales-modal__body">
			<div class="aisales-docs" id="docs-content">
				<!-- Documentation content will be inserted here -->
			</div>
		</div>
	</div>
</div>

<!-- Widget Settings Modal - Power User Configuration -->
<div class="aisales-modal-overlay" id="settings-modal-overlay">
	<div class="aisales-modal aisales-settings-modal" id="settings-modal">
		<div class="aisales-modal__header">
			<h2 class="aisales-modal__title">
				<span class="dashicons dashicons-admin-generic"></span>
				<span id="settings-modal-title"><?php esc_html_e( 'Widget Settings', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</h2>
			<button type="button" class="aisales-modal__close" id="settings-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		
		<!-- Settings Tabs -->
		<nav class="aisales-settings-tabs" id="settings-tabs">
			<button type="button" class="aisales-settings-tab aisales-settings-tab--active" data-panel="appearance">
				<span class="dashicons dashicons-admin-appearance aisales-settings-tab__icon"></span>
				<?php esc_html_e( 'Appearance', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-settings-tab" data-panel="display">
				<span class="dashicons dashicons-visibility aisales-settings-tab__icon"></span>
				<?php esc_html_e( 'Display', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-settings-tab" data-panel="behavior">
				<span class="dashicons dashicons-controls-play aisales-settings-tab__icon"></span>
				<?php esc_html_e( 'Behavior', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
			<button type="button" class="aisales-settings-tab" data-panel="advanced">
				<span class="dashicons dashicons-admin-tools aisales-settings-tab__icon"></span>
				<?php esc_html_e( 'Advanced', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</nav>
		
		<div class="aisales-modal__body">
			<!-- Appearance Panel -->
			<div class="aisales-settings-panel aisales-settings-panel--active" data-panel="appearance" id="settings-panel-appearance">
				<!-- Dynamic content inserted by JS -->
			</div>
			
			<!-- Display Panel -->
			<div class="aisales-settings-panel" data-panel="display" id="settings-panel-display">
				<!-- Dynamic content inserted by JS -->
			</div>
			
			<!-- Behavior Panel -->
			<div class="aisales-settings-panel" data-panel="behavior" id="settings-panel-behavior">
				<!-- Dynamic content inserted by JS -->
			</div>
			
			<!-- Advanced Panel -->
			<div class="aisales-settings-panel" data-panel="advanced" id="settings-panel-advanced">
				<!-- Dynamic content inserted by JS -->
			</div>
		</div>
		
		<div class="aisales-modal__footer">
			<div class="aisales-settings-footer__left">
				<button type="button" class="aisales-settings-reset-btn" id="settings-reset">
					<span class="dashicons dashicons-image-rotate"></span>
					<?php esc_html_e( 'Reset to Defaults', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>
			<button type="button" class="aisales-settings-save-btn" id="settings-save">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Save Settings', 'ai-sales-manager-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>

<?php
/**
 * Render widget preview based on widget type
 *
 * @param string $key Widget key.
 * @param array  $widget Widget data.
 */
function aisales_render_widget_preview( $key, $widget ) {
	$preview = $widget['preview'];
	$category = $widget['category'];
	
	switch ( $key ) {
		case 'total_sold':
			?>
			<div class="aisales-preview aisales-preview--sales-counter">
				<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
				<span class="aisales-preview__value"><?php echo esc_html( $preview['value'] ); ?></span>
				<span class="aisales-preview__label"><?php echo esc_html( $preview['label'] ); ?></span>
			</div>
			<?php
			break;
			
		case 'recent_purchase':
			?>
			<div class="aisales-preview aisales-preview--recent-purchase">
				<div class="aisales-preview__avatar">
					<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
				</div>
				<div class="aisales-preview__content">
					<strong><?php echo esc_html( $preview['name'] ); ?></strong> <?php esc_html_e( 'from', 'ai-sales-manager-for-woocommerce' ); ?> <?php echo esc_html( $preview['location'] ); ?>
					<br><small><?php esc_html_e( 'purchased this', 'ai-sales-manager-for-woocommerce' ); ?> <?php echo esc_html( $preview['time'] ); ?></small>
				</div>
			</div>
			<?php
			break;
			
		case 'live_viewers':
			?>
			<div class="aisales-preview aisales-preview--live-viewers">
				<span class="aisales-preview__pulse"></span>
				<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
				<span class="aisales-preview__value"><?php echo esc_html( $preview['value'] ); ?></span>
				<span class="aisales-preview__label"><?php echo esc_html( $preview['label'] ); ?></span>
			</div>
			<?php
			break;
			
		case 'stock_urgency':
			?>
			<div class="aisales-preview aisales-preview--stock-urgency">
				<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
				<span class="aisales-preview__value"><?php echo esc_html( $preview['value'] ); ?></span>
			</div>
			<?php
			break;
			
		case 'review_summary':
			?>
			<div class="aisales-preview aisales-preview--review-summary">
				<div class="aisales-preview__stars">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="dashicons dashicons-star-<?php echo $i <= floor( $preview['rating'] ) ? 'filled' : ( $i - 0.5 <= $preview['rating'] ? 'half' : 'empty' ); ?>"></span>
					<?php endfor; ?>
				</div>
				<span class="aisales-preview__rating"><?php echo esc_html( $preview['rating'] ); ?></span>
				<span class="aisales-preview__count">(<?php echo esc_html( $preview['count'] ); ?> <?php esc_html_e( 'reviews', 'ai-sales-manager-for-woocommerce' ); ?>)</span>
			</div>
			<?php
			break;
			
		case 'shipping_bar':
			?>
			<div class="aisales-preview aisales-preview--shipping-bar">
				<div class="aisales-preview__bar">
					<div class="aisales-preview__bar-fill" style="width: <?php echo esc_attr( $preview['progress'] ); ?>%;"></div>
				</div>
				<div class="aisales-preview__message">
					<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
					<?php echo esc_html( $preview['message'] ); ?>
				</div>
			</div>
			<?php
			break;
			
		case 'countdown':
			?>
			<div class="aisales-preview aisales-preview--countdown">
				<div class="aisales-preview__timer">
					<div class="aisales-preview__timer-block">
						<span class="aisales-preview__timer-value"><?php echo esc_html( str_pad( $preview['hours'], 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="aisales-preview__timer-label"><?php esc_html_e( 'HRS', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<span class="aisales-preview__timer-sep">:</span>
					<div class="aisales-preview__timer-block">
						<span class="aisales-preview__timer-value"><?php echo esc_html( str_pad( $preview['minutes'], 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="aisales-preview__timer-label"><?php esc_html_e( 'MIN', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<span class="aisales-preview__timer-sep">:</span>
					<div class="aisales-preview__timer-block">
						<span class="aisales-preview__timer-value"><?php echo esc_html( str_pad( $preview['seconds'], 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="aisales-preview__timer-label"><?php esc_html_e( 'SEC', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>
			<?php
			break;
			
		case 'price_drop':
			?>
			<div class="aisales-preview aisales-preview--price-drop">
				<span class="aisales-preview__badge"><?php echo esc_html( $preview['percentage'] ); ?> <?php esc_html_e( 'OFF', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<div class="aisales-preview__prices">
					<span class="aisales-preview__original"><?php echo esc_html( $preview['original'] ); ?></span>
					<span class="aisales-preview__sale"><?php echo esc_html( $preview['sale'] ); ?></span>
				</div>
			</div>
			<?php
			break;
			
		default:
			if ( isset( $preview['type'] ) && 'products' === $preview['type'] ) {
				?>
				<div class="aisales-preview aisales-preview--products">
					<?php for ( $i = 0; $i < min( $preview['count'], 4 ); $i++ ) : ?>
					<div class="aisales-preview__product">
						<div class="aisales-preview__product-image"></div>
						<?php if ( isset( $preview['badge'] ) ) : ?>
						<span class="aisales-preview__product-badge"><?php echo esc_html( $preview['badge'] ); ?></span>
						<?php endif; ?>
					</div>
					<?php endfor; ?>
				</div>
				<?php
			} else {
				?>
				<div class="aisales-preview aisales-preview--generic">
					<span class="dashicons <?php echo esc_attr( $preview['icon'] ); ?>"></span>
					<?php if ( isset( $preview['message'] ) ) : ?>
					<span class="aisales-preview__message"><?php echo esc_html( $preview['message'] ); ?></span>
					<?php endif; ?>
				</div>
				<?php
			}
			break;
	}
}

// Make function available to template
?>
