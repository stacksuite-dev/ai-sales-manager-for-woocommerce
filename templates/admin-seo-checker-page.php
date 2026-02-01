<?php
/**
 * SEO Checker Page Template
 *
 * Admin page template for comprehensive store-wide SEO auditing.
 *
 * Variables passed from AISales_SEO_Checker_Page::render_page():
 * - $aisales_debug_message (string|null) - Debug action message
 * - $aisales_api_key (string) - API key for the service
 * - $aisales_balance (int) - Current token balance
 * - $aisales_scan_results (array) - Last scan results
 * - $aisales_has_results (bool) - Whether scan results exist
 * - $aisales_overall_score (int) - Overall SEO score (0-100)
 * - $aisales_scores (array) - Category scores
 * - $aisales_issues (array) - Issue counts by type
 * - $aisales_scan_date (string) - Last scan date
 * - $aisales_items_scanned (int) - Number of items scanned
 * - $aisales_detailed_issues (array) - Detailed issue list
 * - $aisales_content_counts (array) - Content type counts
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

// Category definitions.
$aisales_categories = array(
	'products'       => array(
		'label' => __( 'Products', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-cart',
	),
	'categories'     => array(
		'label' => __( 'Categories', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-category',
	),
	'pages'          => array(
		'label' => __( 'Pages', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-admin-page',
	),
	'posts'          => array(
		'label' => __( 'Blog Posts', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-admin-post',
	),
	'store_settings' => array(
		'label' => __( 'Store Settings', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-admin-settings',
	),
	'homepage'       => array(
		'label' => __( 'Homepage', 'ai-sales-manager-for-woocommerce' ),
		'icon'  => 'dashicons-admin-home',
	),
);
?>

<div class="wrap aisales-admin-wrap aisales-seo-checker-page">
	<!-- WordPress Admin Notices Area -->
	<h1 class="aisales-notices-anchor"></h1>

	<?php if ( ! empty( $aisales_debug_message ) ) : ?>
		<!-- Debug Success Message -->
		<div class="notice notice-success is-dismissible aisales-debug-notice">
			<p>
				<strong>✅ <?php esc_html_e( 'Debug', 'ai-sales-manager-for-woocommerce' ); ?>:</strong>
				<?php echo esc_html( $aisales_debug_message ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Available debug actions:', 'ai-sales-manager-for-woocommerce' ); ?>
				<code>?debug=clear</code> (<?php esc_html_e( 'clear all data', 'ai-sales-manager-for-woocommerce' ); ?>),
				<code>?debug=clear_meta</code> (<?php esc_html_e( 'clear per-item meta only', 'ai-sales-manager-for-woocommerce' ); ?>)
			</p>
		</div>
	<?php endif; ?>

	<!-- Page Header -->
	<header class="aisales-seo-checker-page__header">
		<div class="aisales-seo-checker-page__header-left">
			<span class="aisales-seo-checker-page__title">
				<span class="dashicons dashicons-search"></span>
				<?php esc_html_e( 'SEO Checker', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
			<span class="aisales-seo-checker-page__subtitle">
				<?php esc_html_e( 'Comprehensive store-wide SEO audit', 'ai-sales-manager-for-woocommerce' ); ?>
			</span>
		</div>
		<div class="aisales-seo-checker-page__header-right">
			<!-- Balance Indicator -->
			<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-indicator.php'; ?>
		</div>
	</header>

	<?php if ( empty( $aisales_api_key ) ) : ?>
		<!-- Not Connected State -->
		<div class="aisales-seo-checker-page__not-connected">
			<div class="aisales-empty-state">
				<span class="dashicons dashicons-warning"></span>
				<h2><?php esc_html_e( 'Not Connected', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Please connect your AI Sales Manager account to use the SEO Checker.', 'ai-sales-manager-for-woocommerce' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager' ) ); ?>" class="aisales-btn aisales-btn--primary">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Go to Settings', 'ai-sales-manager-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>
		<!-- Score Card Section -->
		<div class="aisales-seo-score-card" id="aisales-seo-score-card">
			<div class="aisales-seo-score-card__main">
				<!-- Overall Score Gauge -->
				<div class="aisales-seo-score-gauge <?php echo esc_attr( AISales_SEO_Checker_Page::get_score_class( $aisales_overall_score ) ); ?>" id="aisales-seo-score-gauge">
					<svg class="aisales-seo-score-gauge__svg" viewBox="0 0 120 120">
						<circle class="aisales-seo-score-gauge__bg" cx="60" cy="60" r="54" />
						<circle class="aisales-seo-score-gauge__progress" cx="60" cy="60" r="54"
							stroke-dasharray="339.292"
							stroke-dashoffset="<?php echo esc_attr( 339.292 * ( 1 - $aisales_overall_score / 100 ) ); ?>" />
					</svg>
					<div class="aisales-seo-score-gauge__value">
						<span class="aisales-seo-score-gauge__number" id="aisales-seo-score-number"><?php echo $aisales_has_results ? esc_html( $aisales_overall_score ) : '--'; ?></span>
						<span class="aisales-seo-score-gauge__label" id="aisales-seo-score-label">
							<?php echo $aisales_has_results ? esc_html( AISales_SEO_Checker_Page::get_score_label( $aisales_overall_score ) ) : esc_html__( 'Not Scanned', 'ai-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
				</div>

				<!-- Category Breakdown -->
				<?php
				// Get scanned categories for breakdown display.
				$aisales_breakdown_scanned_categories = isset( $aisales_scan_results['scanned_categories'] ) ? $aisales_scan_results['scanned_categories'] : array();
				?>
				<div class="aisales-seo-score-breakdown" id="aisales-seo-score-breakdown">
					<h3 class="aisales-seo-score-breakdown__title"><?php esc_html_e( 'Category Scores', 'ai-sales-manager-for-woocommerce' ); ?></h3>
					<div class="aisales-seo-score-breakdown__items">
						<?php foreach ( $aisales_categories as $aisales_key => $aisales_category ) : ?>
							<?php
							$aisales_cat_was_scanned = in_array( $aisales_key, $aisales_breakdown_scanned_categories, true );
							$aisales_cat_score       = isset( $aisales_scores[ $aisales_key ] ) ? (int) $aisales_scores[ $aisales_key ] : 0;
							$aisales_score_class     = $aisales_cat_was_scanned ? AISales_SEO_Checker_Page::get_score_class( $aisales_cat_score ) : 'aisales-seo-score--not-scanned';
							?>
							<div class="aisales-seo-score-breakdown__item" data-category="<?php echo esc_attr( $aisales_key ); ?>">
								<div class="aisales-seo-score-breakdown__item-header">
									<span class="aisales-seo-score-breakdown__item-label">
										<span class="dashicons <?php echo esc_attr( $aisales_category['icon'] ); ?>"></span>
										<?php echo esc_html( $aisales_category['label'] ); ?>
									</span>
									<span class="aisales-seo-score-breakdown__item-value <?php echo esc_attr( $aisales_score_class ); ?>">
										<?php
										if ( ! $aisales_has_results ) {
											echo '--';
										} elseif ( $aisales_cat_was_scanned ) {
											echo esc_html( $aisales_cat_score );
										} else {
											echo '—';
										}
										?>
									</span>
								</div>
								<div class="aisales-seo-score-breakdown__item-bar">
									<div class="aisales-seo-score-breakdown__item-progress <?php echo esc_attr( $aisales_score_class ); ?>"
										style="width: <?php echo ( $aisales_has_results && $aisales_cat_was_scanned ) ? esc_attr( $aisales_cat_score ) : '0'; ?>%;">
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Scan Controls -->
		<div class="aisales-seo-controls">
			<div class="aisales-seo-controls__left">
				<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-seo-run-scan-btn">
					<span class="dashicons dashicons-search"></span>
					<span class="aisales-seo-controls__btn-text"><?php esc_html_e( 'Run Scan', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</button>

				<!-- Filters -->
				<div class="aisales-seo-controls__filters">
					<select id="aisales-seo-filter-type" class="aisales-form-select">
						<option value="all"><?php esc_html_e( 'All Content', 'ai-sales-manager-for-woocommerce' ); ?></option>
						<option value="products"><?php esc_html_e( 'Products', 'ai-sales-manager-for-woocommerce' ); ?> (<?php echo esc_html( $aisales_content_counts['products'] ); ?>)</option>
						<option value="categories"><?php esc_html_e( 'Categories', 'ai-sales-manager-for-woocommerce' ); ?> (<?php echo esc_html( $aisales_content_counts['categories'] ); ?>)</option>
						<option value="pages"><?php esc_html_e( 'Pages', 'ai-sales-manager-for-woocommerce' ); ?> (<?php echo esc_html( $aisales_content_counts['pages'] ); ?>)</option>
						<option value="posts"><?php esc_html_e( 'Posts', 'ai-sales-manager-for-woocommerce' ); ?> (<?php echo esc_html( $aisales_content_counts['posts'] ); ?>)</option>
					</select>

					<select id="aisales-seo-filter-priority" class="aisales-form-select">
						<option value="recent"><?php esc_html_e( 'Recent First', 'ai-sales-manager-for-woocommerce' ); ?></option>
						<option value="score_asc"><?php esc_html_e( 'Lowest Score First', 'ai-sales-manager-for-woocommerce' ); ?></option>
						<option value="score_desc"><?php esc_html_e( 'Highest Score First', 'ai-sales-manager-for-woocommerce' ); ?></option>
						<option value="alpha"><?php esc_html_e( 'Alphabetical', 'ai-sales-manager-for-woocommerce' ); ?></option>
					</select>
				</div>
			</div>

			<div class="aisales-seo-controls__right">
				<?php if ( $aisales_has_results ) : ?>
					<span class="aisales-seo-controls__meta">
						<?php
						printf(
							/* translators: 1: date, 2: number of items */
							esc_html__( 'Last scan: %1$s · %2$s items', 'ai-sales-manager-for-woocommerce' ),
							esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $aisales_scan_date ) ) ),
							esc_html( number_format_i18n( $aisales_items_scanned ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<!-- Scan Progress (hidden by default) -->
		<div class="aisales-seo-progress" id="aisales-seo-progress" style="display: none;">
			<div class="aisales-seo-progress__bar">
				<div class="aisales-seo-progress__fill" id="aisales-seo-progress-fill" style="width: 0%;"></div>
			</div>
			<div class="aisales-seo-progress__status">
				<span class="aisales-seo-progress__text" id="aisales-seo-progress-text"><?php esc_html_e( 'Initializing scan...', 'ai-sales-manager-for-woocommerce' ); ?></span>
				<span class="aisales-seo-progress__count" id="aisales-seo-progress-count">0 / 0</span>
			</div>
		</div>

		<!-- Issues Summary Bar -->
		<?php if ( $aisales_has_results ) : ?>
			<div class="aisales-seo-issues-summary" id="aisales-seo-issues-summary">
				<div class="aisales-seo-issues-summary__item aisales-seo-issues-summary__item--critical">
					<span class="aisales-seo-issues-summary__icon">
						<span class="dashicons dashicons-warning"></span>
					</span>
					<span class="aisales-seo-issues-summary__count" id="aisales-seo-critical-count"><?php echo esc_html( $aisales_issues['critical'] ?? 0 ); ?></span>
					<span class="aisales-seo-issues-summary__label"><?php esc_html_e( 'Critical', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-seo-issues-summary__item aisales-seo-issues-summary__item--warning">
					<span class="aisales-seo-issues-summary__icon">
						<span class="dashicons dashicons-info"></span>
					</span>
					<span class="aisales-seo-issues-summary__count" id="aisales-seo-warning-count"><?php echo esc_html( $aisales_issues['warnings'] ?? 0 ); ?></span>
					<span class="aisales-seo-issues-summary__label"><?php esc_html_e( 'Warnings', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-seo-issues-summary__item aisales-seo-issues-summary__item--passed">
					<span class="aisales-seo-issues-summary__icon">
						<span class="dashicons dashicons-yes-alt"></span>
					</span>
					<span class="aisales-seo-issues-summary__count" id="aisales-seo-passed-count"><?php echo esc_html( $aisales_issues['passed'] ?? 0 ); ?></span>
					<span class="aisales-seo-issues-summary__label"><?php esc_html_e( 'Passed', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<!-- Category Accordions -->
		<div class="aisales-seo-categories" id="aisales-seo-categories">
			<?php if ( ! $aisales_has_results ) : ?>
				<!-- Empty State -->
				<div class="aisales-seo-empty-state" id="aisales-seo-empty-state">
					<div class="aisales-seo-empty-state__icon">
						<span class="dashicons dashicons-search"></span>
					</div>
					<h3 class="aisales-seo-empty-state__title"><?php esc_html_e( 'No Scan Results', 'ai-sales-manager-for-woocommerce' ); ?></h3>
					<p class="aisales-seo-empty-state__description">
						<?php esc_html_e( 'Click "Run Scan" to analyze your store\'s SEO health and get actionable recommendations.', 'ai-sales-manager-for-woocommerce' ); ?>
					</p>
				</div>
			<?php else : ?>
				<!-- Category Accordions -->
				<?php
				// Get scanned categories from results.
				$aisales_scanned_categories = isset( $aisales_scan_results['scanned_categories'] ) ? $aisales_scan_results['scanned_categories'] : array();
				?>
				<?php foreach ( $aisales_categories as $aisales_key => $aisales_category ) : ?>
					<?php
					$aisales_was_scanned     = in_array( $aisales_key, $aisales_scanned_categories, true );
					$aisales_cat_score       = isset( $aisales_scores[ $aisales_key ] ) ? (int) $aisales_scores[ $aisales_key ] : 0;
					$aisales_score_class     = $aisales_was_scanned ? AISales_SEO_Checker_Page::get_score_class( $aisales_cat_score ) : 'aisales-seo-score--not-scanned';
					$aisales_cat_issues      = isset( $aisales_detailed_issues[ $aisales_key ] ) ? $aisales_detailed_issues[ $aisales_key ] : array();
					$aisales_critical_count  = 0;
					$aisales_warning_count   = 0;

					foreach ( $aisales_cat_issues as $aisales_issue ) {
						if ( isset( $aisales_issue['severity'] ) ) {
							if ( 'critical' === $aisales_issue['severity'] ) {
								++$aisales_critical_count;
							} elseif ( 'warning' === $aisales_issue['severity'] ) {
								++$aisales_warning_count;
							}
						}
					}

					// Get human-readable filter label for the "not scanned" message.
					$aisales_filter_labels = array(
						'products'       => __( 'Products', 'ai-sales-manager-for-woocommerce' ),
						'categories'     => __( 'Categories', 'ai-sales-manager-for-woocommerce' ),
						'pages'          => __( 'Pages', 'ai-sales-manager-for-woocommerce' ),
						'posts'          => __( 'Blog Posts', 'ai-sales-manager-for-woocommerce' ),
						'store_settings' => __( 'All', 'ai-sales-manager-for-woocommerce' ),
						'homepage'       => __( 'All', 'ai-sales-manager-for-woocommerce' ),
					);
					$aisales_filter_label = isset( $aisales_filter_labels[ $aisales_key ] ) ? $aisales_filter_labels[ $aisales_key ] : __( 'All', 'ai-sales-manager-for-woocommerce' );
					?>
					<div class="aisales-seo-accordion" data-category="<?php echo esc_attr( $aisales_key ); ?>">
						<button type="button" class="aisales-seo-accordion__header" aria-expanded="false">
							<span class="aisales-seo-accordion__icon">
								<span class="dashicons <?php echo esc_attr( $aisales_category['icon'] ); ?>"></span>
							</span>
							<span class="aisales-seo-accordion__title"><?php echo esc_html( $aisales_category['label'] ); ?></span>
							<?php if ( $aisales_was_scanned ) : ?>
								<span class="aisales-seo-accordion__score <?php echo esc_attr( $aisales_score_class ); ?>"><?php echo esc_html( $aisales_cat_score ); ?>/100</span>
							<?php else : ?>
								<span class="aisales-seo-accordion__score aisales-seo-score--not-scanned">—</span>
							<?php endif; ?>
							<?php if ( $aisales_was_scanned ) : ?>
								<?php if ( $aisales_critical_count > 0 ) : ?>
									<span class="aisales-seo-accordion__badge aisales-seo-accordion__badge--critical">
										<?php echo esc_html( $aisales_critical_count ); ?> <?php esc_html_e( 'critical', 'ai-sales-manager-for-woocommerce' ); ?>
									</span>
								<?php endif; ?>
								<?php if ( $aisales_warning_count > 0 ) : ?>
									<span class="aisales-seo-accordion__badge aisales-seo-accordion__badge--warning">
										<?php echo esc_html( $aisales_warning_count ); ?> <?php esc_html_e( 'warnings', 'ai-sales-manager-for-woocommerce' ); ?>
									</span>
								<?php endif; ?>
							<?php else : ?>
								<span class="aisales-seo-accordion__badge aisales-seo-accordion__badge--not-scanned">
									<?php esc_html_e( 'Not scanned', 'ai-sales-manager-for-woocommerce' ); ?>
								</span>
							<?php endif; ?>
							<span class="aisales-seo-accordion__toggle">
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</span>
						</button>
						<div class="aisales-seo-accordion__content" style="display: none;">
							<?php if ( ! $aisales_was_scanned ) : ?>
								<div class="aisales-seo-accordion__not-scanned">
									<span class="dashicons dashicons-info-outline"></span>
									<span>
										<?php
										printf(
											/* translators: %s: filter label */
											esc_html__( 'This category hasn\'t been scanned yet. Click "Run Scan" with the filter set to "All" or "%s" to analyze it.', 'ai-sales-manager-for-woocommerce' ),
											esc_html( $aisales_filter_label )
										);
										?>
									</span>
								</div>
							<?php elseif ( empty( $aisales_cat_issues ) ) : ?>
								<div class="aisales-seo-accordion__empty">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'No issues found. Great job!', 'ai-sales-manager-for-woocommerce' ); ?>
								</div>
							<?php else : ?>
								<!-- Bulk Fix Button -->
								<?php if ( $aisales_critical_count + $aisales_warning_count > 1 ) : ?>
									<div class="aisales-seo-accordion__bulk-actions">
										<button type="button" class="aisales-btn aisales-btn--ghost aisales-btn--sm aisales-seo-bulk-fix-btn"
											data-category="<?php echo esc_attr( $aisales_key ); ?>">
											<span class="dashicons dashicons-admin-customizer"></span>
											<?php esc_html_e( 'AI Fix All', 'ai-sales-manager-for-woocommerce' ); ?>
										</button>
									</div>
								<?php endif; ?>

								<!-- Issues List -->
								<div class="aisales-seo-issues-list">
									<?php foreach ( $aisales_cat_issues as $aisales_issue ) : ?>
										<div class="aisales-seo-issue <?php echo esc_attr( 'aisales-seo-issue--' . ( $aisales_issue['severity'] ?? 'warning' ) ); ?>"
											data-issue-id="<?php echo esc_attr( $aisales_issue['id'] ?? '' ); ?>"
											data-item-type="<?php echo esc_attr( $aisales_issue['item_type'] ?? '' ); ?>"
											data-item-id="<?php echo esc_attr( $aisales_issue['item_id'] ?? '' ); ?>">
											<div class="aisales-seo-issue__indicator">
												<span class="dashicons <?php echo 'critical' === ( $aisales_issue['severity'] ?? 'warning' ) ? 'dashicons-warning' : 'dashicons-info'; ?>"></span>
											</div>
											<div class="aisales-seo-issue__content">
												<span class="aisales-seo-issue__title"><?php echo esc_html( $aisales_issue['title'] ?? '' ); ?></span>
												<span class="aisales-seo-issue__item-name"><?php echo esc_html( $aisales_issue['item_name'] ?? '' ); ?></span>
												<span class="aisales-seo-issue__description"><?php echo esc_html( $aisales_issue['description'] ?? '' ); ?></span>
											</div>
											<div class="aisales-seo-issue__actions">
												<?php if ( ! empty( $aisales_issue['fixable'] ) ) : ?>
													<button type="button" class="aisales-btn aisales-btn--pill aisales-btn--sm aisales-seo-fix-btn"
														data-issue="<?php echo esc_attr( wp_json_encode( $aisales_issue ) ); ?>">
														<span class="dashicons dashicons-admin-customizer"></span>
														<?php esc_html_e( 'AI Fix', 'ai-sales-manager-for-woocommerce' ); ?>
													</button>
												<?php endif; ?>
												<?php if ( ! empty( $aisales_issue['edit_url'] ) ) : ?>
													<a href="<?php echo esc_url( $aisales_issue['edit_url'] ); ?>" class="aisales-btn aisales-btn--ghost aisales-btn--sm" target="_blank">
														<span class="dashicons dashicons-edit"></span>
														<?php esc_html_e( 'Edit', 'ai-sales-manager-for-woocommerce' ); ?>
													</a>
												<?php endif; ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<!-- Fix Modal -->
		<div class="aisales-seo-modal" id="aisales-seo-fix-modal" style="display: none;">
			<div class="aisales-seo-modal__overlay"></div>
			<div class="aisales-seo-modal__container">
				<div class="aisales-seo-modal__header">
					<h3 class="aisales-seo-modal__title" id="aisales-seo-fix-modal-title"><?php esc_html_e( 'Fix SEO Issue', 'ai-sales-manager-for-woocommerce' ); ?></h3>
					<button type="button" class="aisales-seo-modal__close" id="aisales-seo-fix-modal-close">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="aisales-seo-modal__body" id="aisales-seo-fix-modal-body">
					<!-- Issue details -->
					<div class="aisales-seo-fix-details" id="aisales-seo-fix-details">
						<div class="aisales-seo-fix-details__item">
							<span class="aisales-seo-fix-details__label"><?php esc_html_e( 'Item:', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-seo-fix-details__value" id="aisales-seo-fix-item-name"></span>
						</div>
						<div class="aisales-seo-fix-details__item">
							<span class="aisales-seo-fix-details__label"><?php esc_html_e( 'Issue:', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-seo-fix-details__value" id="aisales-seo-fix-issue-title"></span>
						</div>
						<div class="aisales-seo-fix-details__item">
							<span class="aisales-seo-fix-details__label"><?php esc_html_e( 'Current:', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span class="aisales-seo-fix-details__value aisales-seo-fix-details__value--current" id="aisales-seo-fix-current"></span>
						</div>
					</div>

					<!-- Fix preview -->
					<div class="aisales-seo-fix-preview" id="aisales-seo-fix-preview" style="display: none;">
						<label class="aisales-form-label"><?php esc_html_e( 'AI Suggested Fix:', 'ai-sales-manager-for-woocommerce' ); ?></label>
						<div class="aisales-seo-fix-preview__content" id="aisales-seo-fix-preview-content"></div>
					</div>

					<!-- Loading state -->
					<div class="aisales-seo-fix-loading" id="aisales-seo-fix-loading" style="display: none;">
						<div class="aisales-spinner"></div>
						<span id="aisales-seo-fix-loading-text"><?php esc_html_e( 'Generating AI fix...', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>
				<div class="aisales-seo-modal__footer">
					<div class="aisales-seo-modal__footer-left">
						<span class="aisales-seo-fix-cost" id="aisales-seo-fix-cost" style="display: none;">
							<span class="dashicons dashicons-money-alt"></span>
							<span id="aisales-seo-fix-cost-value">~50</span> <?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
					<div class="aisales-seo-modal__footer-right">
						<button type="button" class="aisales-btn aisales-btn--ghost" id="aisales-seo-fix-cancel-btn">
							<?php esc_html_e( 'Cancel', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-btn aisales-btn--ai" id="aisales-seo-fix-generate-btn">
							<span class="dashicons dashicons-admin-customizer"></span>
							<?php esc_html_e( 'Generate Fix', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
						<button type="button" class="aisales-btn aisales-btn--primary" id="aisales-seo-fix-apply-btn" style="display: none;">
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Apply Fix', 'ai-sales-manager-for-woocommerce' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Balance Modal (Shared Partial) -->
		<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-modal.php'; ?>
	<?php endif; ?>
</div>
