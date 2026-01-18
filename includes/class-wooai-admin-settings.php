<?php
/**
 * Admin Settings Page
 *
 * @package WooAI_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings class
 */
class WooAI_Admin_Settings {

	/**
	 * Single instance
	 *
	 * @var WooAI_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * Current tab
	 *
	 * @var string
	 */
	private $current_tab = 'dashboard';

	/**
	 * Get instance
	 *
	 * @return WooAI_Admin_Settings
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Add menu page
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'WooAI Manager', 'woo-ai-sales-manager' ),
			__( 'WooAI Manager', 'woo-ai-sales-manager' ),
			'manage_woocommerce',
			'woo-ai-manager',
			array( $this, 'render_page' ),
			'dashicons-lightbulb',
			56
		);
	}

	/**
	 * Handle form actions
	 */
	public function handle_actions() {
		// Handle top-up success
		if ( isset( $_GET['page'] ) && 'woo-ai-manager' === $_GET['page'] && isset( $_GET['topup'] ) && 'success' === $_GET['topup'] ) {
			add_action( 'admin_footer', function() {
				?>
				<script>
				(function($) {
					$(document).ready(function() {
						if (typeof WooAI !== 'undefined' && WooAI.showRichToast) {
							WooAI.showRichToast({
								type: 'success',
								icon: 'dashicons-yes-alt',
								title: '<?php echo esc_js( __( 'Top-Up Successful!', 'woo-ai-sales-manager' ) ); ?>',
								message: '<?php echo esc_js( __( '10,000 tokens have been added to your account.', 'woo-ai-sales-manager' ) ); ?>',
								duration: 5000
							});
						}
					});
				})(jQuery);
				</script>
				<?php
			} );
		}

		// Handle disconnect
		if ( isset( $_POST['wooai_disconnect'] ) && check_admin_referer( 'wooai_disconnect_nonce' ) ) {
			delete_option( 'wooai_api_key' );
			delete_option( 'wooai_user_email' );
			delete_option( 'wooai_balance' );

			wp_safe_redirect( admin_url( 'admin.php?page=woo-ai-manager&disconnected=1' ) );
			exit;
		}

		// Handle disconnected notice - show toast on page load
		if ( isset( $_GET['page'] ) && 'woo-ai-manager' === $_GET['page'] && isset( $_GET['disconnected'] ) ) {
			add_action( 'admin_footer', function() {
				?>
				<script>
				(function($) {
					$(document).ready(function() {
						if (typeof WooAI !== 'undefined' && WooAI.showRichToast) {
							WooAI.showRichToast({
								type: 'info',
								icon: 'dashicons-unlock',
								title: '<?php echo esc_js( __( 'Disconnected', 'woo-ai-sales-manager' ) ); ?>',
								message: '<?php echo esc_js( __( 'Your account has been disconnected from this site.', 'woo-ai-sales-manager' ) ); ?>',
								duration: 4000
							});
						}
					});
				})(jQuery);
				</script>
				<?php
			} );
		}
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		$api = WooAI_API_Client::instance();

		// Determine current tab
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

		// Check if connected
		$is_connected = $api->is_connected();

		// Get balance for header display
		$balance = 0;
		$balance_class = '';
		if ( $is_connected ) {
			$account = $api->get_account();
			if ( ! is_wp_error( $account ) && isset( $account['balance_tokens'] ) ) {
				$balance = $account['balance_tokens'];
				if ( $balance < 500 ) {
					$balance_class = 'wooai-page-header__balance--critical';
				} elseif ( $balance < 2000 ) {
					$balance_class = 'wooai-page-header__balance--low';
				}
			}
		}

		?>
		<div class="wrap wooai-admin-wrap">
			<?php if ( $is_connected ) : ?>
				<!-- Enhanced Page Header -->
				<header class="wooai-page-header">
					<div class="wooai-page-header__logo">
						<span class="dashicons dashicons-superhero-alt"></span>
					</div>
					<div class="wooai-page-header__content">
						<h1 class="wooai-page-header__title"><?php esc_html_e( 'WooAI Sales Manager', 'woo-ai-sales-manager' ); ?></h1>
						<p class="wooai-page-header__subtitle"><?php esc_html_e( 'AI-powered tools for your WooCommerce products', 'woo-ai-sales-manager' ); ?></p>
					</div>
					<div class="wooai-page-header__balance <?php echo esc_attr( $balance_class ); ?>">
						<span class="dashicons dashicons-database"></span>
						<span class="wooai-page-header__balance-count" id="wooai-balance-count"><?php echo esc_html( number_format( $balance ) ); ?></span>
						<span><?php esc_html_e( 'tokens', 'woo-ai-sales-manager' ); ?></span>
					</div>
				</header>

				<!-- Modern Navigation Tabs -->
				<nav class="wooai-nav">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager&tab=dashboard' ) ); ?>"
					   class="wooai-nav__tab <?php echo 'dashboard' === $this->current_tab ? 'wooai-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-dashboard"></span>
						<?php esc_html_e( 'Dashboard', 'woo-ai-sales-manager' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager&tab=usage' ) ); ?>"
					   class="wooai-nav__tab <?php echo 'usage' === $this->current_tab ? 'wooai-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-chart-area"></span>
						<?php esc_html_e( 'Usage History', 'woo-ai-sales-manager' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager&tab=account' ) ); ?>"
					   class="wooai-nav__tab <?php echo 'account' === $this->current_tab ? 'wooai-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-admin-users"></span>
						<?php esc_html_e( 'Account', 'woo-ai-sales-manager' ); ?>
					</a>
				</nav>

				<?php
				switch ( $this->current_tab ) {
					case 'usage':
						$this->render_usage_tab();
						break;
					case 'account':
						$this->render_account_tab();
						break;
					default:
						$this->render_dashboard_tab();
						break;
				}
				?>
			<?php else : ?>
				<?php $this->render_connect_form(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get current site domain
	 *
	 * @return string
	 */
	private function get_site_domain() {
		$site_url = get_site_url();
		$parsed   = wp_parse_url( $site_url );
		return isset( $parsed['host'] ) ? $parsed['host'] : '';
	}

	/**
	 * Render connect form (simplified domain-based auth)
	 */
	private function render_connect_form() {
		$domain      = $this->get_site_domain();
		$admin_email = get_option( 'admin_email' );
		?>
		<div class="wooai-auth wooai-auth--left">
			<!-- Main Auth Card -->
			<div class="wooai-auth__card wooai-card">
				<div class="wooai-auth__header wooai-auth__header--left">
					<div class="wooai-auth__logo">
						<span class="dashicons dashicons-superhero-alt"></span>
					</div>
					<h2 class="wooai-auth__title"><?php esc_html_e( 'Welcome to WooAI', 'woo-ai-sales-manager' ); ?></h2>
					<p class="wooai-auth__subtitle">
						<?php esc_html_e( 'AI-powered tools for your WooCommerce products', 'woo-ai-sales-manager' ); ?>
					</p>
				</div>

				<!-- Connect Form -->
				<div id="wooai-connect-form" class="wooai-auth-form wooai-auth__form">
					<div class="wooai-form-group">
						<label for="wooai-connect-email" class="wooai-form-label">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Email Address', 'woo-ai-sales-manager' ); ?>
						</label>
						<input type="email" id="wooai-connect-email" class="wooai-form-input"
							   placeholder="you@example.com" value="<?php echo esc_attr( $admin_email ); ?>" required>
						<span class="wooai-form-hint"><?php esc_html_e( 'We\'ll use this for purchase receipts and notifications.', 'woo-ai-sales-manager' ); ?></span>
					</div>

					<div class="wooai-form-group">
						<label class="wooai-form-label">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php esc_html_e( 'Site Domain', 'woo-ai-sales-manager' ); ?>
						</label>
						<div class="wooai-form-static">
							<span class="dashicons dashicons-lock"></span>
							<code><?php echo esc_html( $domain ); ?></code>
						</div>
						<span class="wooai-form-hint"><?php esc_html_e( 'Your site domain is used as your unique account identifier.', 'woo-ai-sales-manager' ); ?></span>
						<input type="hidden" id="wooai-connect-domain" value="<?php echo esc_attr( $domain ); ?>">
					</div>

					<button type="button" id="wooai-connect-btn" class="wooai-btn wooai-btn--primary wooai-btn--lg wooai-btn--full">
						<span class="spinner"></span>
						<?php esc_html_e( 'Get Started', 'woo-ai-sales-manager' ); ?>
					</button>

					<p class="wooai-auth__footer-text">
						<?php esc_html_e( 'Enter the same email to reconnect an existing account.', 'woo-ai-sales-manager' ); ?>
					</p>
				</div>

				<!-- Auth Message -->
				<div id="wooai-auth-message" class="wooai-alert" style="display: none;"></div>
			</div>

			<!-- Pricing Card -->
			<div class="wooai-auth__pricing wooai-card">
				<div class="wooai-pricing">
					<div class="wooai-pricing__header">
						<span class="wooai-pricing__badge"><?php esc_html_e( 'Simple Pricing', 'woo-ai-sales-manager' ); ?></span>
						<div class="wooai-pricing__amount">
							<span class="wooai-pricing__currency">$</span>
							<span class="wooai-pricing__value">9</span>
						</div>
						<div class="wooai-pricing__period"><?php esc_html_e( 'for 10,000 tokens', 'woo-ai-sales-manager' ); ?></div>
					</div>
					<ul class="wooai-pricing__features">
						<li>
							<span class="wooai-op-badge wooai-op-badge--content"><?php esc_html_e( 'Content', 'woo-ai-sales-manager' ); ?></span>
							<span>~200 <?php esc_html_e( 'tokens/product', 'woo-ai-sales-manager' ); ?></span>
						</li>
						<li>
							<span class="wooai-op-badge wooai-op-badge--taxonomy"><?php esc_html_e( 'Taxonomy', 'woo-ai-sales-manager' ); ?></span>
							<span>~100 <?php esc_html_e( 'tokens/product', 'woo-ai-sales-manager' ); ?></span>
						</li>
						<li>
							<span class="wooai-op-badge wooai-op-badge--image_generate"><?php esc_html_e( 'Images', 'woo-ai-sales-manager' ); ?></span>
							<span>~1,000 <?php esc_html_e( 'tokens/image', 'woo-ai-sales-manager' ); ?></span>
						</li>
					</ul>
					<div class="wooai-pricing__note">
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'No subscription. Pay only for what you use.', 'woo-ai-sales-manager' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab
	 */
	private function render_dashboard_tab() {
		$api     = WooAI_API_Client::instance();
		$account = $api->get_account();
		$usage   = $api->get_usage( 5, 0 );

		// Handle API errors gracefully
		$has_error = is_wp_error( $account );
		$balance   = ( ! $has_error && isset( $account['balance_tokens'] ) ) ? $account['balance_tokens'] : 0;
		$email     = ( ! $has_error && isset( $account['email'] ) ) ? $account['email'] : '';
		$usage     = is_wp_error( $usage ) ? array( 'logs' => array() ) : $usage;

		// Calculate balance percentage (assuming 10,000 is "full")
		$balance_percentage = min( 100, ( $balance / 10000 ) * 100 );
		$balance_class      = $balance_percentage < 20 ? 'low' : ( $balance_percentage < 50 ? 'medium' : 'good' );

		?>
		<?php if ( $has_error ) : ?>
			<div class="wooai-alert wooai-alert--danger wooai-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="wooai-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'woo-ai-sales-manager' ); ?></strong>
					<?php echo esc_html( $account->get_error_message() ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="wooai-dashboard">
			<!-- Balance Card - Featured Style -->
			<div class="wooai-card wooai-card--featured wooai-card--elevated">
				<div class="wooai-card__header">
					<div class="wooai-card__icon">
						<span class="dashicons dashicons-database"></span>
					</div>
					<h2><?php esc_html_e( 'Token Balance', 'woo-ai-sales-manager' ); ?></h2>
				</div>

				<div class="wooai-balance wooai-balance--hero">
					<span class="wooai-balance__amount"><?php echo esc_html( number_format( $balance ) ); ?></span>
					<span class="wooai-balance__label"><?php esc_html_e( 'tokens available', 'woo-ai-sales-manager' ); ?></span>
				</div>

				<div class="wooai-progress wooai-progress--thick wooai-mb-4">
					<div class="wooai-progress__fill wooai-progress__fill--<?php echo esc_attr( $balance_class ); ?>"
						 style="width: <?php echo esc_attr( $balance_percentage ); ?>%;"></div>
				</div>

				<?php if ( $balance < 1000 ) : ?>
					<div class="wooai-alert wooai-alert--warning wooai-alert--compact wooai-mb-4">
						<span class="dashicons dashicons-warning"></span>
						<div class="wooai-alert__content">
							<?php esc_html_e( 'Low balance! Top up to continue using AI tools.', 'woo-ai-sales-manager' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<button type="button" id="wooai-topup-btn" class="wooai-btn wooai-btn--gradient wooai-btn--lg wooai-btn--full">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Top Up $9 → 10,000 tokens', 'woo-ai-sales-manager' ); ?>
				</button>
			</div>

			<!-- Quick Stats Card -->
			<div class="wooai-card wooai-card--elevated">
				<div class="wooai-card__header">
					<div class="wooai-card__icon wooai-card__icon--success">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<h2><?php esc_html_e( 'AI Activity', 'woo-ai-sales-manager' ); ?></h2>
				</div>

				<div class="wooai-stats-grid">
					<div class="wooai-stat-card wooai-stat-card--content">
						<span class="wooai-stat-card__value">12</span>
						<span class="wooai-stat-card__label"><?php esc_html_e( 'Content', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-stat-card wooai-stat-card--taxonomy">
						<span class="wooai-stat-card__value">45</span>
						<span class="wooai-stat-card__label"><?php esc_html_e( 'Tags', 'woo-ai-sales-manager' ); ?></span>
					</div>
					<div class="wooai-stat-card wooai-stat-card--image">
						<span class="wooai-stat-card__value">8</span>
						<span class="wooai-stat-card__label"><?php esc_html_e( 'Images', 'woo-ai-sales-manager' ); ?></span>
					</div>
				</div>

				<p class="wooai-text-muted wooai-text-sm wooai-mt-4">
					<span class="dashicons dashicons-email" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
					<?php echo esc_html( $email ); ?>
				</p>
			</div>

			<!-- Recent Usage Card -->
			<div class="wooai-card wooai-card--elevated wooai-dashboard__full">
				<div class="wooai-card__header">
					<div class="wooai-card__icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<h2><?php esc_html_e( 'Recent Activity', 'woo-ai-sales-manager' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-ai-manager&tab=usage' ) ); ?>"
					   class="wooai-btn wooai-btn--secondary wooai-btn--sm wooai-card__header-action">
						<?php esc_html_e( 'View All', 'woo-ai-sales-manager' ); ?>
					</a>
				</div>

				<?php if ( ! empty( $usage['logs'] ) ) : ?>
					<table class="wooai-table-modern">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'woo-ai-sales-manager' ); ?></th>
								<th><?php esc_html_e( 'Operation', 'woo-ai-sales-manager' ); ?></th>
								<th><?php esc_html_e( 'Tokens Used', 'woo-ai-sales-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $usage['logs'] as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $this->format_date( $log['created_at'] ) ); ?></td>
									<td>
										<span class="wooai-op-badge wooai-op-badge--<?php echo esc_attr( $log['operation'] ); ?>">
											<?php echo esc_html( $this->format_operation( $log['operation'] ) ); ?>
										</span>
									</td>
									<td>
										<strong><?php echo esc_html( number_format( $log['total_tokens'] ) ); ?></strong>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="wooai-empty-state--enhanced">
						<span class="dashicons dashicons-clock"></span>
						<p><?php esc_html_e( 'No usage yet', 'woo-ai-sales-manager' ); ?></p>
						<p><?php esc_html_e( 'Start by editing a product and using the AI tools!', 'woo-ai-sales-manager' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- How to Use Section - Enhanced -->
		<div class="wooai-howto wooai-mt-6">
			<div class="wooai-howto__header">
				<div class="wooai-howto__icon">
					<span class="dashicons dashicons-lightbulb"></span>
				</div>
				<h3 class="wooai-howto__title"><?php esc_html_e( 'Getting Started', 'woo-ai-sales-manager' ); ?></h3>
			</div>
			<ol class="wooai-howto__steps">
				<li class="wooai-howto__step"><?php esc_html_e( 'Go to Products → Edit any product', 'woo-ai-sales-manager' ); ?></li>
				<li class="wooai-howto__step"><?php esc_html_e( 'Find the "AI Tools" panel in the sidebar', 'woo-ai-sales-manager' ); ?></li>
				<li class="wooai-howto__step"><?php esc_html_e( 'Click any AI action to generate content', 'woo-ai-sales-manager' ); ?></li>
				<li class="wooai-howto__step"><?php esc_html_e( 'Review and apply the suggestions', 'woo-ai-sales-manager' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Render usage tab
	 */
	private function render_usage_tab() {
		$api   = WooAI_API_Client::instance();
		$usage = $api->get_usage( 20, 0 );

		// Handle API errors gracefully
		$has_error = is_wp_error( $usage );
		if ( $has_error ) {
			$error_message = $usage->get_error_message();
			$usage         = array( 'logs' => array() );
		}

		// Calculate usage summary
		$total_tokens = 0;
		$operation_counts = array(
			'content'        => 0,
			'taxonomy'       => 0,
			'image_generate' => 0,
			'image_improve'  => 0,
		);

		if ( ! empty( $usage['logs'] ) ) {
			foreach ( $usage['logs'] as $log ) {
				$total_tokens += $log['total_tokens'];
				if ( isset( $operation_counts[ $log['operation'] ] ) ) {
					$operation_counts[ $log['operation'] ]++;
				}
			}
		}

		?>
		<?php if ( $has_error ) : ?>
			<div class="wooai-alert wooai-alert--danger wooai-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="wooai-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'woo-ai-sales-manager' ); ?></strong>
					<?php echo esc_html( $error_message ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="wooai-usage wooai-mt-5">

			<!-- Usage Summary Cards -->
			<div class="wooai-stats-grid wooai-stats-grid--4col wooai-mb-6">
				<div class="wooai-stat-card wooai-stat-card--total">
					<div class="wooai-stat-card__icon">
						<span class="dashicons dashicons-database"></span>
					</div>
					<span class="wooai-stat-card__value"><?php echo esc_html( number_format( $total_tokens ) ); ?></span>
					<span class="wooai-stat-card__label"><?php esc_html_e( 'Total Tokens Used', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-stat-card wooai-stat-card--content">
					<div class="wooai-stat-card__icon">
						<span class="dashicons dashicons-edit"></span>
					</div>
					<span class="wooai-stat-card__value"><?php echo esc_html( $operation_counts['content'] ); ?></span>
					<span class="wooai-stat-card__label"><?php esc_html_e( 'Content', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-stat-card wooai-stat-card--taxonomy">
					<div class="wooai-stat-card__icon">
						<span class="dashicons dashicons-tag"></span>
					</div>
					<span class="wooai-stat-card__value"><?php echo esc_html( $operation_counts['taxonomy'] ); ?></span>
					<span class="wooai-stat-card__label"><?php esc_html_e( 'Taxonomy', 'woo-ai-sales-manager' ); ?></span>
				</div>
				<div class="wooai-stat-card wooai-stat-card--image">
					<div class="wooai-stat-card__icon">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<span class="wooai-stat-card__value"><?php echo esc_html( $operation_counts['image_generate'] + $operation_counts['image_improve'] ); ?></span>
					<span class="wooai-stat-card__label"><?php esc_html_e( 'Images', 'woo-ai-sales-manager' ); ?></span>
				</div>
			</div>

			<!-- Usage History Table -->
			<div class="wooai-card wooai-card--elevated">
				<div class="wooai-card__header">
					<div class="wooai-card__icon wooai-card__icon--purple">
						<span class="dashicons dashicons-chart-area"></span>
					</div>
					<h2><?php esc_html_e( 'Usage History', 'woo-ai-sales-manager' ); ?></h2>
				</div>

				<?php if ( ! empty( $usage['logs'] ) ) : ?>
					<div class="wooai-table-wrapper">
						<table class="wooai-table-modern">
							<thead>
								<tr>
									<th class="wooai-table__col--date"><?php esc_html_e( 'Date', 'woo-ai-sales-manager' ); ?></th>
									<th class="wooai-table__col--operation"><?php esc_html_e( 'Operation', 'woo-ai-sales-manager' ); ?></th>
									<th class="wooai-table__col--tokens"><?php esc_html_e( 'Input', 'woo-ai-sales-manager' ); ?></th>
									<th class="wooai-table__col--tokens"><?php esc_html_e( 'Output', 'woo-ai-sales-manager' ); ?></th>
									<th class="wooai-table__col--tokens"><?php esc_html_e( 'Total', 'woo-ai-sales-manager' ); ?></th>
									<th class="wooai-table__col--product"><?php esc_html_e( 'Product', 'woo-ai-sales-manager' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $usage['logs'] as $log ) : ?>
									<tr>
										<td class="wooai-table__col--date">
											<span class="wooai-table__date"><?php echo esc_html( $this->format_date( $log['created_at'] ) ); ?></span>
										</td>
										<td class="wooai-table__col--operation">
											<span class="wooai-op-badge wooai-op-badge--<?php echo esc_attr( $log['operation'] ); ?>">
												<?php echo esc_html( $this->format_operation( $log['operation'] ) ); ?>
											</span>
										</td>
										<td class="wooai-table__col--tokens">
											<span class="wooai-table__tokens wooai-table__tokens--input"><?php echo esc_html( number_format( $log['input_tokens'] ) ); ?></span>
										</td>
										<td class="wooai-table__col--tokens">
											<span class="wooai-table__tokens wooai-table__tokens--output"><?php echo esc_html( number_format( $log['output_tokens'] ) ); ?></span>
										</td>
										<td class="wooai-table__col--tokens">
											<span class="wooai-table__tokens wooai-table__tokens--total"><?php echo esc_html( number_format( $log['total_tokens'] ) ); ?></span>
										</td>
										<td class="wooai-table__col--product">
											<?php if ( ! empty( $log['product_id'] ) ) : ?>
												<a href="<?php echo esc_url( get_edit_post_link( $log['product_id'] ) ); ?>" class="wooai-table__product-link">
													<span class="dashicons dashicons-admin-post"></span>
													#<?php echo esc_html( $log['product_id'] ); ?>
												</a>
											<?php else : ?>
												<span class="wooai-text-muted">—</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="wooai-empty-state wooai-empty-state--enhanced">
						<div class="wooai-empty-state__icon">
							<span class="dashicons dashicons-chart-area"></span>
						</div>
						<h3><?php esc_html_e( 'No usage history yet', 'woo-ai-sales-manager' ); ?></h3>
						<p><?php esc_html_e( 'Start by editing a product and using the AI tools to generate content, tags, or images.', 'woo-ai-sales-manager' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render account tab
	 */
	private function render_account_tab() {
		$api     = WooAI_API_Client::instance();
		$account = $api->get_account();

		// Handle API errors gracefully
		$has_error = is_wp_error( $account );
		$email     = ( ! $has_error && isset( $account['email'] ) ) ? $account['email'] : '';
		$balance   = ( ! $has_error && isset( $account['balance_tokens'] ) ) ? $account['balance_tokens'] : 0;
		$site_url  = wp_parse_url( home_url(), PHP_URL_HOST );

		?>
		<?php if ( $has_error ) : ?>
			<div class="wooai-alert wooai-alert--danger wooai-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="wooai-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'woo-ai-sales-manager' ); ?></strong>
					<?php echo esc_html( $account->get_error_message() ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="wooai-account wooai-mt-5">

			<div class="wooai-account__grid">
				<!-- Account Info Card -->
				<div class="wooai-card wooai-card--featured wooai-card--elevated">
					<div class="wooai-card__header">
						<div class="wooai-card__icon wooai-card__icon--blue">
							<span class="dashicons dashicons-admin-users"></span>
						</div>
						<h2><?php esc_html_e( 'Account Information', 'woo-ai-sales-manager' ); ?></h2>
					</div>

					<div class="wooai-account__info">
						<div class="wooai-account__row">
							<span class="wooai-account__label">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Email', 'woo-ai-sales-manager' ); ?>
							</span>
							<span class="wooai-account__value"><?php echo esc_html( $email ); ?></span>
						</div>
						<div class="wooai-account__row">
							<span class="wooai-account__label">
								<span class="dashicons dashicons-admin-site-alt3"></span>
								<?php esc_html_e( 'Connected Site', 'woo-ai-sales-manager' ); ?>
							</span>
							<span class="wooai-account__value"><?php echo esc_html( $site_url ); ?></span>
						</div>
						<div class="wooai-account__row wooai-account__row--highlight">
							<span class="wooai-account__label">
								<span class="dashicons dashicons-database"></span>
								<?php esc_html_e( 'Token Balance', 'woo-ai-sales-manager' ); ?>
							</span>
							<span class="wooai-account__value wooai-account__value--balance">
								<?php echo esc_html( number_format( $balance ) ); ?>
								<small><?php esc_html_e( 'tokens', 'woo-ai-sales-manager' ); ?></small>
							</span>
						</div>
					</div>

					<div class="wooai-card__footer">
						<a href="<?php echo esc_url( WOOAI_API_URL . '/dashboard' ); ?>" target="_blank" class="wooai-btn wooai-btn--gradient">
							<span class="dashicons dashicons-external"></span>
							<?php esc_html_e( 'Manage Account', 'woo-ai-sales-manager' ); ?>
						</a>
					</div>
				</div>

				<!-- Danger Zone Card -->
				<div class="wooai-card wooai-card--danger-zone wooai-card--elevated">
					<div class="wooai-card__header">
						<div class="wooai-card__icon wooai-card__icon--red">
							<span class="dashicons dashicons-warning"></span>
						</div>
						<h2><?php esc_html_e( 'Danger Zone', 'woo-ai-sales-manager' ); ?></h2>
					</div>

					<div class="wooai-card__body">
						<p class="wooai-text-muted wooai-mb-4">
							<?php esc_html_e( 'Disconnect your account from this WordPress site. Your SaaS account and token balance will remain intact — you can reconnect anytime using the same email.', 'woo-ai-sales-manager' ); ?>
						</p>

						<form method="post">
							<?php wp_nonce_field( 'wooai_disconnect_nonce' ); ?>
							<button type="submit" name="wooai_disconnect" class="wooai-btn wooai-btn--danger"
									onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to disconnect? You can reconnect anytime.', 'woo-ai-sales-manager' ); ?>');">
								<span class="dashicons dashicons-dismiss"></span>
								<?php esc_html_e( 'Disconnect Account', 'woo-ai-sales-manager' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Format date for display
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	private function format_date( $date ) {
		$timestamp = strtotime( $date );
		$now       = current_time( 'timestamp' );
		$diff      = $now - $timestamp;

		if ( $diff < HOUR_IN_SECONDS ) {
			return sprintf( __( '%d min ago', 'woo-ai-sales-manager' ), floor( $diff / MINUTE_IN_SECONDS ) );
		} elseif ( $diff < DAY_IN_SECONDS ) {
			return sprintf( __( '%d hours ago', 'woo-ai-sales-manager' ), floor( $diff / HOUR_IN_SECONDS ) );
		} else {
			return wp_date( 'M j, g:i a', $timestamp );
		}
	}

	/**
	 * Format operation type for display
	 *
	 * @param string $operation Operation type.
	 * @return string
	 */
	private function format_operation( $operation ) {
		$labels = array(
			'content'        => __( 'Content', 'woo-ai-sales-manager' ),
			'taxonomy'       => __( 'Taxonomy', 'woo-ai-sales-manager' ),
			'image_generate' => __( 'Image Generate', 'woo-ai-sales-manager' ),
			'image_improve'  => __( 'Image Improve', 'woo-ai-sales-manager' ),
		);

		return isset( $labels[ $operation ] ) ? $labels[ $operation ] : $operation;
	}
}
