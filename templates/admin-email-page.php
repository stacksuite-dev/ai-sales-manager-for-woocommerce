<?php
/**
 * Email Templates Management Page
 *
 * Dedicated admin page for managing AI-generated WooCommerce email templates.
 * Replaces the slide-out panel with a full-page experience.
 *
 * Variables passed from AISales_Email_Page::render_page():
 * - $api_key (string) - API key for the service
 * - $balance (int) - Current token balance
 * - $templates (array) - Templates overview data
 * - $placeholders (array) - Available placeholders grouped by category
 * - $grouped_templates (array) - Templates grouped by category
 * - $stats (array) - Template statistics (active, draft, not_created, total)
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap aisales-admin-wrap aisales-email-page">
	<!-- WordPress Admin Notices Area - h1 triggers WP notice placement -->
	<h1 class="aisales-notices-anchor"></h1>

	<!-- Page Header -->
	<header class="aisales-email-page__header">
		<div class="aisales-email-page__header-left">
			<span class="aisales-email-page__title">
				<span class="dashicons dashicons-email-alt"></span>
				<?php esc_html_e( 'Email Templates', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-email-page__header-right">
			<!-- Balance Indicator -->
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
		</div>
	</header>

	<?php if ( empty( $api_key ) ) : ?>
		<!-- Not Connected State -->
		<div class="aisales-email-page__not-connected">
			<div class="aisales-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<h2><?php esc_html_e( 'Not Connected', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Please connect your AI Sales Manager account to use email templates.', 'ai-sales-manager-for-woocommerce' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="aisales-btn aisales-btn--primary">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Go to Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<!-- Tab Navigation (widgets-style) -->
		<nav class="aisales-email-page__tabs">
			<button type="button" class="aisales-email-tab aisales-email-tab--active" data-tab="templates">
				<span class="dashicons dashicons-email-alt"></span>
				<span class="aisales-email-tab__text"><?php esc_html_e( 'Templates', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<span class="aisales-email-tab__count"><?php echo esc_html( $stats['total'] ); ?></span>
			</button>
			<button type="button" class="aisales-email-tab" data-tab="settings">
				<span class="dashicons dashicons-admin-generic"></span>
				<span class="aisales-email-tab__text"><?php esc_html_e( 'Settings', 'ai-sales-manager-for-woocommerce' ); ?></span>
			</button>
		</nav>

		<!-- Tab Panel: Templates -->
		<div class="aisales-email-tab-panel aisales-email-tab-panel--active" data-tab-panel="templates">
		<!-- Main Content Container -->
		<div class="aisales-email-page__content">
			<!-- List View (Default) -->
			<div class="aisales-email-page__list-view" id="aisales-email-list-view">
			<!-- Stats + Actions Row -->
			<div class="aisales-email-overview">
				<div class="aisales-email-stats">
					<div class="aisales-email-stat aisales-email-stat--active">
						<span class="aisales-email-stat__icon"></span>
						<span class="aisales-email-stat__count"><?php echo esc_html( $stats['active'] ); ?></span>
						<span class="aisales-email-stat__label"><?php esc_html_e( 'Active', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-email-stat aisales-email-stat--draft">
						<span class="aisales-email-stat__icon"></span>
						<span class="aisales-email-stat__count"><?php echo esc_html( $stats['draft'] ); ?></span>
						<span class="aisales-email-stat__label"><?php esc_html_e( 'Draft', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-email-stat aisales-email-stat--none">
						<span class="aisales-email-stat__icon"></span>
						<span class="aisales-email-stat__count"><?php echo esc_html( $stats['not_created'] ); ?></span>
						<span class="aisales-email-stat__label"><?php esc_html_e( 'Not Created', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>

				<div class="aisales-email-actions">
					<button type="button" class="aisales-btn aisales-btn--pill" id="aisales-brand-settings-btn" title="<?php esc_attr_e( 'Configure your brand voice and tone for AI-generated emails', 'ai-sales-manager-for-woocommerce' ); ?>">
						<span class="dashicons dashicons-admin-customizer"></span>
						<?php esc_html_e( 'Brand Settings', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<button type="button" class="aisales-btn aisales-btn--pill" id="aisales-generate-all-btn" <?php echo $stats['not_created'] === 0 ? 'disabled' : ''; ?>>
						<span class="dashicons dashicons-controls-repeat"></span>
						<?php esc_html_e( 'Generate All Missing', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

				<!-- Templates List -->
				<div class="aisales-email-templates">
					<?php foreach ( $grouped_templates as $category_key => $category ) : ?>
						<div class="aisales-email-category">
							<div class="aisales-email-category__header">
								<span class="dashicons <?php echo esc_attr( $category['icon'] ); ?>"></span>
								<h2><?php echo esc_html( $category['label'] ); ?></h2>
							</div>
							<div class="aisales-email-category__list">
								<?php foreach ( $category['templates'] as $type => $template_data ) : ?>
									<div class="aisales-email-template-card" data-template-type="<?php echo esc_attr( $type ); ?>">
										<div class="aisales-email-template-card__icon">
											<span class="dashicons <?php echo esc_attr( AISales_Email_Page::get_template_icon( $type ) ); ?>"></span>
										</div>
										<div class="aisales-email-template-card__info">
											<h3 class="aisales-email-template-card__title"><?php echo esc_html( $template_data['label'] ); ?></h3>
											<p class="aisales-email-template-card__desc"><?php echo esc_html( $template_data['description'] ); ?></p>
											<?php if ( $template_data['has_template'] && ! empty( $template_data['template']['subject'] ) ) : ?>
												<p class="aisales-email-template-card__subject">
													<span class="dashicons dashicons-email"></span>
													<?php echo esc_html( $template_data['template']['subject'] ); ?>
												</p>
											<?php endif; ?>
										</div>
										<div class="aisales-email-template-card__status">
											<?php if ( $template_data['is_active'] ) : ?>
												<span class="aisales-status-badge aisales-status-badge--active">
													<span class="dashicons dashicons-yes-alt"></span>
													<?php esc_html_e( 'Active', 'ai-sales-manager-for-woocommerce' ); ?>
												</span>
											<?php elseif ( $template_data['has_template'] ) : ?>
												<span class="aisales-status-badge aisales-status-badge--draft">
													<span class="dashicons dashicons-edit"></span>
													<?php esc_html_e( 'Draft', 'ai-sales-manager-for-woocommerce' ); ?>
												</span>
											<?php else : ?>
												<span class="aisales-status-badge aisales-status-badge--none">
													<span class="dashicons dashicons-minus"></span>
													<?php esc_html_e( 'Not Created', 'ai-sales-manager-for-woocommerce' ); ?>
												</span>
											<?php endif; ?>
										</div>
										<div class="aisales-email-template-card__actions">
											<?php if ( $template_data['has_template'] ) : ?>
												<button type="button" class="aisales-btn aisales-btn--outline aisales-btn--sm aisales-email-edit-btn" 
													data-template-type="<?php echo esc_attr( $type ); ?>"
													title="<?php esc_attr_e( 'Edit Template', 'ai-sales-manager-for-woocommerce' ); ?>">
													<span class="dashicons dashicons-edit"></span>
													<span class="aisales-btn__text"><?php esc_html_e( 'Edit', 'ai-sales-manager-for-woocommerce' ); ?></span>
												</button>
												<label class="aisales-toggle-switch" title="<?php echo $template_data['is_active'] ? esc_attr__( 'Deactivate', 'ai-sales-manager-for-woocommerce' ) : esc_attr__( 'Activate', 'ai-sales-manager-for-woocommerce' ); ?>">
													<input type="checkbox" class="aisales-email-toggle" 
														data-template-type="<?php echo esc_attr( $type ); ?>" 
														<?php checked( $template_data['is_active'] ); ?>>
													<span class="aisales-toggle-switch__slider"></span>
												</label>
											<?php else : ?>
												<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--sm aisales-email-generate-btn" 
													data-template-type="<?php echo esc_attr( $type ); ?>">
													<span class="dashicons dashicons-admin-customizer"></span>
													<span class="aisales-btn__text"><?php esc_html_e( 'Generate', 'ai-sales-manager-for-woocommerce' ); ?></span>
												</button>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Help Section -->
				<div class="aisales-email-help">
					<div class="aisales-email-help__icon">
						<span class="dashicons dashicons-info-outline"></span>
					</div>
					<div class="aisales-email-help__content">
						<h4><?php esc_html_e( 'How it works', 'ai-sales-manager-for-woocommerce' ); ?></h4>
						<p><?php esc_html_e( 'AI-generated email templates replace the default WooCommerce transactional emails. Generate templates using your store context, customize them with placeholders, and activate to start sending personalized emails.', 'ai-sales-manager-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Editor View (Split Pane) -->
			<div class="aisales-email-page__editor-view" id="aisales-email-editor-view" style="display: none;">
				<!-- Editor Header -->
				<div class="aisales-email-editor__header">
					<button type="button" class="aisales-btn aisales-btn--ghost" id="aisales-email-back-btn">
						<span class="dashicons dashicons-arrow-left-alt"></span>
						<?php esc_html_e( 'Back to Templates', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>
					<div class="aisales-email-editor__title">
						<span class="aisales-email-editor__type-badge" id="aisales-editor-type-badge"></span>
						<span class="aisales-email-editor__status" id="aisales-editor-status"></span>
					</div>
					<div class="aisales-email-editor__header-actions">
						<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm" id="aisales-email-test-btn">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Send Test', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm aisales-btn--danger" id="aisales-email-delete-btn">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Delete', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<!-- Editor Split Pane -->
				<div class="aisales-email-editor__split">
					<!-- Left: Form -->
					<div class="aisales-email-editor__form-pane">
						<form id="aisales-email-editor-form" class="aisales-email-editor__form">
							<input type="hidden" name="template_type" id="aisales-editor-type-input">

							<!-- Template Name -->
							<div class="aisales-email-field">
								<label for="aisales-email-name">
									<span class="dashicons dashicons-tag"></span>
									<?php esc_html_e( 'Template Name', 'ai-sales-manager-for-woocommerce' ); ?>
								</label>
								<input type="text" id="aisales-email-name" name="name" 
									placeholder="<?php esc_attr_e( 'e.g., Order Confirmation - Friendly', 'ai-sales-manager-for-woocommerce' ); ?>">
							</div>

							<!-- Subject Line -->
							<div class="aisales-email-field">
								<label for="aisales-email-subject">
									<span class="dashicons dashicons-email"></span>
									<?php esc_html_e( 'Subject Line', 'ai-sales-manager-for-woocommerce' ); ?>
									<button type="button" class="aisales-regenerate-btn" data-field="subject" title="<?php esc_attr_e( 'Regenerate with AI', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-update"></span>
									</button>
								</label>
								<div class="aisales-email-field__input-wrap">
									<input type="text" id="aisales-email-subject" name="subject" 
										placeholder="<?php esc_attr_e( 'e.g., Order #{order_number} confirmed!', 'ai-sales-manager-for-woocommerce' ); ?>">
									<button type="button" class="aisales-placeholder-insert-btn" data-target="aisales-email-subject" title="<?php esc_attr_e( 'Insert Placeholder', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
									</button>
								</div>
							</div>

							<!-- Heading -->
							<div class="aisales-email-field">
								<label for="aisales-email-heading">
									<span class="dashicons dashicons-heading"></span>
									<?php esc_html_e( 'Email Heading', 'ai-sales-manager-for-woocommerce' ); ?>
									<button type="button" class="aisales-regenerate-btn" data-field="heading" title="<?php esc_attr_e( 'Regenerate with AI', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-update"></span>
									</button>
								</label>
								<div class="aisales-email-field__input-wrap">
									<input type="text" id="aisales-email-heading" name="heading" 
										placeholder="<?php esc_attr_e( 'e.g., Thank you for your order!', 'ai-sales-manager-for-woocommerce' ); ?>">
									<button type="button" class="aisales-placeholder-insert-btn" data-target="aisales-email-heading" title="<?php esc_attr_e( 'Insert Placeholder', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
									</button>
								</div>
							</div>

							<!-- Content -->
							<div class="aisales-email-field aisales-email-field--content">
								<label for="aisales-email-content">
									<span class="dashicons dashicons-text"></span>
									<?php esc_html_e( 'Email Content', 'ai-sales-manager-for-woocommerce' ); ?>
									<button type="button" class="aisales-regenerate-btn" data-field="content" title="<?php esc_attr_e( 'Regenerate with AI', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-update"></span>
									</button>
								</label>
								<div class="aisales-email-field__textarea-wrap">
									<textarea id="aisales-email-content" name="content" rows="10"
										placeholder="<?php esc_attr_e( 'Write your email content here. Use placeholders like {customer_name} to personalize...', 'ai-sales-manager-for-woocommerce' ); ?>"></textarea>
									<button type="button" class="aisales-placeholder-insert-btn aisales-placeholder-insert-btn--textarea" data-target="aisales-email-content">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php esc_html_e( 'Insert Placeholder', 'ai-sales-manager-for-woocommerce' ); ?>
									</button>
								</div>
							</div>

							<!-- AI Improvement -->
							<div class="aisales-email-field aisales-email-field--ai">
								<label>
									<span class="dashicons dashicons-admin-customizer"></span>
									<?php esc_html_e( 'Improve with AI', 'ai-sales-manager-for-woocommerce' ); ?>
								</label>
								<div class="aisales-email-improve-wrap">
									<input type="text" id="aisales-email-improve-prompt" 
										placeholder="<?php esc_attr_e( 'e.g., Make it more friendly, add urgency, shorter...', 'ai-sales-manager-for-woocommerce' ); ?>">
									<button type="button" class="aisales-btn aisales-btn--primary aisales-btn--sm" id="aisales-email-improve-btn">
										<?php esc_html_e( 'Apply', 'ai-sales-manager-for-woocommerce' ); ?>
									</button>
								</div>
							</div>

							<!-- Placeholders Reference (Collapsible) -->
							<div class="aisales-email-placeholders-ref">
								<button type="button" class="aisales-email-placeholders-toggle" id="aisales-placeholders-toggle">
									<span class="dashicons dashicons-editor-code"></span>
									<?php esc_html_e( 'Available Placeholders', 'ai-sales-manager-for-woocommerce' ); ?>
									<span class="dashicons dashicons-arrow-down-alt2"></span>
								</button>
								<div class="aisales-email-placeholders-list" id="aisales-placeholders-list" style="display: none;">
									<?php foreach ( $placeholders as $group => $group_placeholders ) : ?>
										<div class="aisales-placeholder-group">
											<h4 class="aisales-placeholder-group__title"><?php echo esc_html( ucfirst( $group ) ); ?></h4>
											<div class="aisales-placeholder-group__items">
												<?php foreach ( $group_placeholders as $placeholder => $description ) : ?>
													<button type="button" class="aisales-placeholder-chip" 
														data-placeholder="<?php echo esc_attr( $placeholder ); ?>" 
														title="<?php echo esc_attr( $description ); ?>">
														<?php echo esc_html( $placeholder ); ?>
													</button>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</form>

						<!-- Form Actions -->
						<div class="aisales-email-editor__form-actions">
							<button type="button" class="aisales-btn aisales-btn--outline" id="aisales-email-save-draft">
								<?php esc_html_e( 'Save Draft', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
							<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-email-save-activate">
								<span class="dashicons dashicons-saved"></span>
								<?php esc_html_e( 'Save & Activate', 'ai-sales-manager-for-woocommerce' ); ?>
							</button>
						</div>
					</div>

					<!-- Resizer Handle -->
					<div class="aisales-email-editor__resizer" id="aisales-editor-resizer"></div>

					<!-- Right: Preview -->
					<div class="aisales-email-editor__preview-pane">
						<div class="aisales-email-preview">
							<!-- Preview Header -->
							<div class="aisales-email-preview__header">
								<span class="aisales-email-preview__title">
									<span class="dashicons dashicons-visibility"></span>
									<?php esc_html_e( 'Live Preview', 'ai-sales-manager-for-woocommerce' ); ?>
								</span>
								<div class="aisales-email-preview__device-switcher">
									<button type="button" class="aisales-device-btn aisales-device-btn--active" data-device="mobile" title="<?php esc_attr_e( 'Mobile (375px)', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-smartphone"></span>
									</button>
									<button type="button" class="aisales-device-btn" data-device="tablet" title="<?php esc_attr_e( 'Tablet (768px)', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-tablet"></span>
									</button>
									<button type="button" class="aisales-device-btn" data-device="desktop" title="<?php esc_attr_e( 'Desktop (100%)', 'ai-sales-manager-for-woocommerce' ); ?>">
										<span class="dashicons dashicons-desktop"></span>
									</button>
								</div>
							</div>

							<!-- Preview Subject -->
							<div class="aisales-email-preview__subject">
								<strong><?php esc_html_e( 'Subject:', 'ai-sales-manager-for-woocommerce' ); ?></strong>
								<span id="aisales-preview-subject"><?php esc_html_e( 'Your subject line will appear here...', 'ai-sales-manager-for-woocommerce' ); ?></span>
							</div>

							<!-- Preview Frame Container -->
							<div class="aisales-email-preview__frame-container" id="aisales-preview-container">
								<div class="aisales-email-preview__frame-wrapper" id="aisales-preview-wrapper">
									<iframe id="aisales-preview-iframe" title="<?php esc_attr_e( 'Email Preview', 'ai-sales-manager-for-woocommerce' ); ?>"></iframe>
								</div>
							</div>

							<!-- Preview Footer -->
							<div class="aisales-email-preview__footer">
								<span class="dashicons dashicons-info-outline"></span>
								<?php esc_html_e( 'Preview uses sample order data', 'ai-sales-manager-for-woocommerce' ); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Loading Overlay -->
		<div class="aisales-email-loading" id="aisales-email-loading" style="display: none;">
			<div class="aisales-email-loading__content">
				<div class="aisales-spinner aisales-spinner--lg"></div>
				<p id="aisales-loading-message"><?php esc_html_e( 'Generating email template...', 'ai-sales-manager-for-woocommerce' ); ?></p>
			</div>
		</div>

		<!-- Balance Modal (Shared Partial) -->
		<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-modal.php'; ?>

		<!-- Test Email Modal -->
		<?php include AISALES_PLUGIN_DIR . 'templates/partials/email-test-modal.php'; ?>

		<!-- Placeholder Picker Dropdown -->
		<div class="aisales-placeholder-picker" id="aisales-placeholder-picker" style="display: none;">
			<?php foreach ( $placeholders as $group => $group_placeholders ) : ?>
				<div class="aisales-placeholder-picker__group">
					<div class="aisales-placeholder-picker__group-title"><?php echo esc_html( ucfirst( $group ) ); ?></div>
					<?php foreach ( $group_placeholders as $placeholder => $description ) : ?>
						<button type="button" class="aisales-placeholder-picker__item" data-placeholder="<?php echo esc_attr( $placeholder ); ?>">
							<code><?php echo esc_html( $placeholder ); ?></code>
							<span><?php echo esc_html( $description ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
		</div><!-- /.aisales-email-tab-panel[templates] -->

		<!-- Tab Panel: Settings -->
		<div class="aisales-email-tab-panel" data-tab-panel="settings">
			<?php
			$mail_settings = AISales_Mail_Provider::instance()->get_settings();
			include AISALES_PLUGIN_DIR . 'templates/partials/email-settings-panel.php';
			?>
		</div><!-- /.aisales-email-tab-panel[settings] -->
	<?php endif; ?>
</div>

<!-- Template Data for JavaScript -->
<script type="text/javascript">
	window.aisalesEmailData = {
		templates: <?php echo wp_json_encode( $templates ); ?>,
		placeholders: <?php echo wp_json_encode( $placeholders ); ?>
	};
</script>
