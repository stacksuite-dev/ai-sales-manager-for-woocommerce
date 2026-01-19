<?php
/**
 * Admin Settings Page
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings class
 */
class AISales_Admin_Settings {

	/**
	 * Single instance
	 *
	 * @var AISales_Admin_Settings
	 */
	private static $instance = null;

	/**
	 * Current tab
	 *
	 * @var string
	 */
	private $current_tab = 'dashboard';

	/**
	 * Toast notification data to show on page load
	 *
	 * @var array|null
	 */
	private $page_toast = null;

	/**
	 * Get instance
	 *
	 * @return AISales_Admin_Settings
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_toast_script' ), 20 );
	}

	/**
	 * Enqueue toast script data if needed
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_toast_script( $hook ) {
		if ( 'toplevel_page_ai-sales-manager' !== $hook || null === $this->page_toast ) {
			return;
		}

		// Add toast data as inline script after the main admin script.
		$toast_data = wp_json_encode( $this->page_toast );
		wp_add_inline_script(
			'aisales-admin',
			'if (typeof aisalesAdmin !== "undefined") { aisalesAdmin.toast = ' . $toast_data . '; }',
			'before'
		);
	}

	/**
	 * Add menu page
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'AI Sales Manager', 'ai-sales-manager-for-woocommerce' ),
			__( 'AI Sales Manager', 'ai-sales-manager-for-woocommerce' ),
			'manage_woocommerce',
			'ai-sales-manager',
			array( $this, 'render_page' ),
			'dashicons-lightbulb',
			56
		);
	}

	/**
	 * Handle form actions
	 */
	public function handle_actions() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Handle top-up success.
		if ( isset( $_GET['page'] ) && 'ai-sales-manager' === $_GET['page'] && isset( $_GET['topup'] ) && 'success' === $_GET['topup'] ) {
			$this->page_toast = array(
				'type'     => 'success',
				'icon'     => 'dashicons-yes-alt',
				'title'    => __( 'Top-Up Successful!', 'ai-sales-manager-for-woocommerce' ),
				'message'  => __( '10,000 tokens have been added to your account.', 'ai-sales-manager-for-woocommerce' ),
				'duration' => 5000,
			);
		}

		// Handle disconnected notice - show toast on page load.
		if ( isset( $_GET['page'] ) && 'ai-sales-manager' === $_GET['page'] && isset( $_GET['disconnected'] ) ) {
			$this->page_toast = array(
				'type'     => 'info',
				'icon'     => 'dashicons-unlock',
				'title'    => __( 'Disconnected', 'ai-sales-manager-for-woocommerce' ),
				'message'  => __( 'Your account has been disconnected from this site.', 'ai-sales-manager-for-woocommerce' ),
				'duration' => 4000,
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Handle disconnect.
		if ( isset( $_POST['aisales_disconnect'] ) && check_admin_referer( 'aisales_disconnect_nonce' ) ) {
			delete_option( 'aisales_api_key' );
			delete_option( 'aisales_user_email' );
			delete_option( 'aisales_balance' );

			wp_safe_redirect( admin_url( 'admin.php?page=ai-sales-manager&disconnected=1' ) );
			exit;
		}
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		$api = AISales_API_Client::instance();

		// Determine current tab from URL parameter.
		// This is a navigation parameter, not a form submission, so nonce verification is not required.
		// Using filter_input for cleaner superglobal access.
		$tab_param = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$this->current_tab = $tab_param ? sanitize_key( $tab_param ) : 'dashboard';

		// Check if connected
		$is_connected = $api->is_connected();

		// Get balance for header display
		$balance = 0;
		$balance_class = '';
		if ( $is_connected ) {
			$account = $api->get_account();
			if ( ! is_wp_error( $account ) && isset( $account['balance_tokens'] ) ) {
				$balance = $account['balance_tokens'];
				if ( 500 > $balance ) {
					$balance_class = 'aisales-page-header__balance--critical';
				} elseif ( 2000 > $balance ) {
					$balance_class = 'aisales-page-header__balance--low';
				}
			}
		}

		// Get store context status
		$store_context  = get_option( 'aisales_store_context', array() );
		$context_status = 'missing';
		$store_name     = get_bloginfo( 'name' );

		if ( ! empty( $store_context ) ) {
			$store_name   = isset( $store_context['store_name'] ) ? $store_context['store_name'] : $store_name;
			$has_required = ! empty( $store_context['store_name'] ) || ! empty( $store_context['business_niche'] );
			$has_optional = ! empty( $store_context['target_audience'] ) || ! empty( $store_context['brand_tone'] );
			$context_status = $has_required ? ( $has_optional ? 'configured' : 'partial' ) : 'missing';
		}

		?>
		<div class="wrap aisales-admin-wrap">
			<?php if ( $is_connected ) : ?>
				<!-- Enhanced Page Header -->
				<header class="aisales-page-header">
					<div class="aisales-page-header__logo">
						<span class="dashicons dashicons-superhero-alt"></span>
					</div>
					<div class="aisales-page-header__content">
						<h1 class="aisales-page-header__title"><?php esc_html_e( 'AI Sales Manager', 'ai-sales-manager-for-woocommerce' ); ?></h1>
						<p class="aisales-page-header__subtitle"><?php esc_html_e( 'AI-powered tools for your WooCommerce products', 'ai-sales-manager-for-woocommerce' ); ?></p>
					</div>
					<div class="aisales-page-header__right">
						<button type="button" class="aisales-store-context-btn" id="aisales-open-context" title="<?php esc_attr_e( 'Store Context Settings', 'ai-sales-manager-for-woocommerce' ); ?>">
							<span class="dashicons dashicons-store"></span>
							<span class="aisales-store-name"><?php echo esc_html( $store_name ); ?></span>
							<span class="aisales-context-status aisales-context-status--<?php echo esc_attr( $context_status ); ?>"></span>
						</button>
						<span class="aisales-balance-indicator">
							<span class="dashicons dashicons-database"></span>
							<span class="aisales-balance-indicator__count" id="aisales-balance-count"><?php echo esc_html( number_format( $balance ) ); ?></span>
							<?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?>
						</span>
					</div>
				</header>

				<!-- Store Context Slide-out Panel (Shared Partial) -->
				<?php include AISALES_PLUGIN_DIR . 'templates/partials/store-context-panel.php'; ?>

				<!-- Balance Top-Up Modal (Shared Partial) -->
				<?php include AISALES_PLUGIN_DIR . 'templates/partials/balance-modal.php'; ?>

				<!-- Modern Navigation Tabs -->
				<nav class="aisales-nav">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager&tab=dashboard' ) ); ?>"
					   class="aisales-nav__tab <?php echo 'dashboard' === $this->current_tab ? 'aisales-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-dashboard"></span>
						<?php esc_html_e( 'Dashboard', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager&tab=usage' ) ); ?>"
					   class="aisales-nav__tab <?php echo 'usage' === $this->current_tab ? 'aisales-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-chart-area"></span>
						<?php esc_html_e( 'Usage History', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager&tab=billing' ) ); ?>"
					   class="aisales-nav__tab <?php echo 'billing' === $this->current_tab ? 'aisales-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-money-alt"></span>
						<?php esc_html_e( 'Billing', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager&tab=account' ) ); ?>"
					   class="aisales-nav__tab <?php echo 'account' === $this->current_tab ? 'aisales-nav__tab--active' : ''; ?>">
						<span class="dashicons dashicons-admin-users"></span>
						<?php esc_html_e( 'Account', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
				</nav>

				<?php
				switch ( $this->current_tab ) {
					case 'usage':
						$this->render_usage_tab();
						break;
					case 'billing':
						$this->render_billing_tab();
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
		<div class="aisales-auth aisales-auth--left">
			<!-- Main Auth Card -->
			<div class="aisales-auth__card aisales-card">
				<div class="aisales-auth__header aisales-auth__header--left">
					<div class="aisales-auth__logo">
						<span class="dashicons dashicons-superhero-alt"></span>
					</div>
					<h2 class="aisales-auth__title"><?php esc_html_e( 'Welcome to AI Sales Manager', 'ai-sales-manager-for-woocommerce' ); ?></h2>
					<p class="aisales-auth__subtitle">
						<?php esc_html_e( 'AI-powered tools for your WooCommerce products', 'ai-sales-manager-for-woocommerce' ); ?>
					</p>
				</div>

				<!-- Connect Form -->
				<div id="aisales-connect-form" class="aisales-auth-form aisales-auth__form">
					<div class="aisales-form-group">
						<label for="aisales-connect-email" class="aisales-form-label">
							<span class="dashicons dashicons-email"></span>
							<?php esc_html_e( 'Email Address', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<input type="email" id="aisales-connect-email" class="aisales-form-input"
							   placeholder="you@example.com" value="<?php echo esc_attr( $admin_email ); ?>" required>
						<span class="aisales-form-hint"><?php esc_html_e( 'We\'ll use this for purchase receipts and notifications.', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>

					<div class="aisales-form-group">
						<label class="aisales-form-label">
							<span class="dashicons dashicons-admin-site-alt3"></span>
							<?php esc_html_e( 'Site Domain', 'ai-sales-manager-for-woocommerce' ); ?>
						</label>
						<div class="aisales-form-static">
							<span class="dashicons dashicons-lock"></span>
							<code><?php echo esc_html( $domain ); ?></code>
						</div>
						<span class="aisales-form-hint"><?php esc_html_e( 'Your site domain is used as your unique account identifier.', 'ai-sales-manager-for-woocommerce' ); ?></span>
						<input type="hidden" id="aisales-connect-domain" value="<?php echo esc_attr( $domain ); ?>">
					</div>

					<button type="button" id="aisales-connect-btn" class="aisales-btn aisales-btn--primary aisales-btn--lg aisales-btn--full">
						<span class="spinner"></span>
						<?php esc_html_e( 'Get Started', 'ai-sales-manager-for-woocommerce' ); ?>
					</button>

					<p class="aisales-auth__footer-text">
						<?php esc_html_e( 'Enter the same email to reconnect an existing account.', 'ai-sales-manager-for-woocommerce' ); ?>
					</p>
				</div>

				<!-- Auth Message -->
				<div id="aisales-auth-message" class="aisales-alert" style="display: none;"></div>
			</div>

			<!-- Pricing Card -->
			<div class="aisales-auth__pricing aisales-card">
				<div class="aisales-pricing">
					<div class="aisales-pricing__header">
						<span class="aisales-pricing__badge"><?php esc_html_e( 'Simple Pricing', 'ai-sales-manager-for-woocommerce' ); ?></span>
						<div class="aisales-pricing__amount">
							<span class="aisales-pricing__currency">$</span>
							<span class="aisales-pricing__value">9</span>
						</div>
						<div class="aisales-pricing__period"><?php esc_html_e( 'for 10,000 tokens', 'ai-sales-manager-for-woocommerce' ); ?></div>
					</div>
					<ul class="aisales-pricing__features">
						<li>
							<span class="aisales-op-badge aisales-op-badge--content"><?php esc_html_e( 'Content', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span>~200 <?php esc_html_e( 'tokens/product', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</li>
						<li>
							<span class="aisales-op-badge aisales-op-badge--taxonomy"><?php esc_html_e( 'Taxonomy', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span>~100 <?php esc_html_e( 'tokens/product', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</li>
						<li>
							<span class="aisales-op-badge aisales-op-badge--image_generate"><?php esc_html_e( 'Images', 'ai-sales-manager-for-woocommerce' ); ?></span>
							<span>~1,000 <?php esc_html_e( 'tokens/image', 'ai-sales-manager-for-woocommerce' ); ?></span>
						</li>
					</ul>
					<div class="aisales-pricing__note">
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'No subscription. Pay only for what you use.', 'ai-sales-manager-for-woocommerce' ); ?>
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
		$api     = AISales_API_Client::instance();
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
			<div class="aisales-alert aisales-alert--danger aisales-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="aisales-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'ai-sales-manager-for-woocommerce' ); ?></strong>
					<?php echo esc_html( $account->get_error_message() ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="aisales-dashboard">
			<!-- Balance Card - Featured Style -->
			<div class="aisales-card aisales-card--featured aisales-card--elevated">
				<div class="aisales-card__header">
					<div class="aisales-card__icon">
						<span class="dashicons dashicons-database"></span>
					</div>
					<h2><?php esc_html_e( 'Token Balance', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				</div>

				<div class="aisales-balance aisales-balance--hero">
					<span class="aisales-balance__amount"><?php echo esc_html( number_format( $balance ) ); ?></span>
					<span class="aisales-balance__label"><?php esc_html_e( 'tokens available', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>

				<div class="aisales-progress aisales-progress--thick aisales-mb-4">
					<div class="aisales-progress__fill aisales-progress__fill--<?php echo esc_attr( $balance_class ); ?>"
						 style="width: <?php echo esc_attr( $balance_percentage ); ?>%;"></div>
				</div>

				<?php if ( 1000 > $balance ) : ?>
					<div class="aisales-alert aisales-alert--warning aisales-alert--compact aisales-mb-4">
						<span class="dashicons dashicons-warning"></span>
						<div class="aisales-alert__content">
							<?php esc_html_e( 'Low balance! Top up to continue using AI tools.', 'ai-sales-manager-for-woocommerce' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<button type="button" id="aisales-topup-btn" class="aisales-btn aisales-btn--gradient aisales-btn--lg aisales-btn--full">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Top Up $9 → 10,000 tokens', 'ai-sales-manager-for-woocommerce' ); ?>
				</button>
			</div>

			<!-- Quick Stats Card -->
			<div class="aisales-card aisales-card--elevated">
				<div class="aisales-card__header">
					<div class="aisales-card__icon aisales-card__icon--success">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<h2><?php esc_html_e( 'AI Activity', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				</div>

				<div class="aisales-stats-grid">
					<div class="aisales-stat-card aisales-stat-card--content">
						<span class="aisales-stat-card__value">12</span>
						<span class="aisales-stat-card__label"><?php esc_html_e( 'Content', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-stat-card aisales-stat-card--taxonomy">
						<span class="aisales-stat-card__value">45</span>
						<span class="aisales-stat-card__label"><?php esc_html_e( 'Tags', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
					<div class="aisales-stat-card aisales-stat-card--image">
						<span class="aisales-stat-card__value">8</span>
						<span class="aisales-stat-card__label"><?php esc_html_e( 'Images', 'ai-sales-manager-for-woocommerce' ); ?></span>
					</div>
				</div>

				<p class="aisales-text-muted aisales-text-sm aisales-mt-4">
					<span class="dashicons dashicons-email" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
					<?php echo esc_html( $email ); ?>
				</p>
			</div>

			<!-- Recent Usage Card -->
			<div class="aisales-card aisales-card--elevated aisales-dashboard__full">
				<div class="aisales-card__header">
					<div class="aisales-card__icon">
						<span class="dashicons dashicons-clock"></span>
					</div>
					<h2><?php esc_html_e( 'Recent Activity', 'ai-sales-manager-for-woocommerce' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-sales-manager&tab=usage' ) ); ?>"
					   class="aisales-btn aisales-btn--secondary aisales-btn--sm aisales-card__header-action">
						<?php esc_html_e( 'View All', 'ai-sales-manager-for-woocommerce' ); ?>
					</a>
				</div>

				<?php if ( ! empty( $usage['logs'] ) ) : ?>
					<table class="aisales-table-modern">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'ai-sales-manager-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Operation', 'ai-sales-manager-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Tokens Used', 'ai-sales-manager-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $usage['logs'] as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $this->format_date( $log['created_at'] ) ); ?></td>
									<td>
										<span class="aisales-op-badge aisales-op-badge--<?php echo esc_attr( $log['operation'] ); ?>">
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
					<div class="aisales-empty-state--enhanced">
						<span class="dashicons dashicons-clock"></span>
						<p><?php esc_html_e( 'No usage yet', 'ai-sales-manager-for-woocommerce' ); ?></p>
						<p><?php esc_html_e( 'Start by editing a product and using the AI tools!', 'ai-sales-manager-for-woocommerce' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- How to Use Section - Enhanced -->
		<div class="aisales-howto aisales-mt-6">
			<div class="aisales-howto__header">
				<div class="aisales-howto__icon">
					<span class="dashicons dashicons-lightbulb"></span>
				</div>
				<h3 class="aisales-howto__title"><?php esc_html_e( 'Getting Started', 'ai-sales-manager-for-woocommerce' ); ?></h3>
			</div>
			<ol class="aisales-howto__steps">
				<li class="aisales-howto__step"><?php esc_html_e( 'Go to Products → Edit any product', 'ai-sales-manager-for-woocommerce' ); ?></li>
				<li class="aisales-howto__step"><?php esc_html_e( 'Find the "AI Tools" panel in the sidebar', 'ai-sales-manager-for-woocommerce' ); ?></li>
				<li class="aisales-howto__step"><?php esc_html_e( 'Click any AI action to generate content', 'ai-sales-manager-for-woocommerce' ); ?></li>
				<li class="aisales-howto__step"><?php esc_html_e( 'Review and apply the suggestions', 'ai-sales-manager-for-woocommerce' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Render usage tab
	 */
	private function render_usage_tab() {
		$api   = AISales_API_Client::instance();
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
			<div class="aisales-alert aisales-alert--danger aisales-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="aisales-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'ai-sales-manager-for-woocommerce' ); ?></strong>
					<?php echo esc_html( $error_message ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="aisales-usage aisales-mt-5">

			<!-- Usage Summary Cards -->
			<div class="aisales-stats-grid aisales-stats-grid--4col aisales-mb-6">
				<div class="aisales-stat-card aisales-stat-card--total">
					<div class="aisales-stat-card__icon">
						<span class="dashicons dashicons-database"></span>
					</div>
					<span class="aisales-stat-card__value"><?php echo esc_html( number_format( $total_tokens ) ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Total Tokens Used', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card aisales-stat-card--content">
					<div class="aisales-stat-card__icon">
						<span class="dashicons dashicons-edit"></span>
					</div>
					<span class="aisales-stat-card__value"><?php echo esc_html( $operation_counts['content'] ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Content', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card aisales-stat-card--taxonomy">
					<div class="aisales-stat-card__icon">
						<span class="dashicons dashicons-tag"></span>
					</div>
					<span class="aisales-stat-card__value"><?php echo esc_html( $operation_counts['taxonomy'] ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Taxonomy', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
				<div class="aisales-stat-card aisales-stat-card--image">
					<div class="aisales-stat-card__icon">
						<span class="dashicons dashicons-format-image"></span>
					</div>
					<span class="aisales-stat-card__value"><?php echo esc_html( $operation_counts['image_generate'] + $operation_counts['image_improve'] ); ?></span>
					<span class="aisales-stat-card__label"><?php esc_html_e( 'Images', 'ai-sales-manager-for-woocommerce' ); ?></span>
				</div>
			</div>

			<!-- Usage History Table -->
			<div class="aisales-card aisales-card--elevated">
				<div class="aisales-card__header">
					<div class="aisales-card__icon aisales-card__icon--purple">
						<span class="dashicons dashicons-chart-area"></span>
					</div>
					<h2><?php esc_html_e( 'Usage History', 'ai-sales-manager-for-woocommerce' ); ?></h2>
				</div>

				<?php if ( ! empty( $usage['logs'] ) ) : ?>
					<div class="aisales-table-wrapper">
						<table class="aisales-table-modern">
							<thead>
								<tr>
									<th class="aisales-table__col--date"><?php esc_html_e( 'Date', 'ai-sales-manager-for-woocommerce' ); ?></th>
									<th class="aisales-table__col--operation"><?php esc_html_e( 'Operation', 'ai-sales-manager-for-woocommerce' ); ?></th>
									<th class="aisales-table__col--tokens"><?php esc_html_e( 'Input', 'ai-sales-manager-for-woocommerce' ); ?></th>
									<th class="aisales-table__col--tokens"><?php esc_html_e( 'Output', 'ai-sales-manager-for-woocommerce' ); ?></th>
									<th class="aisales-table__col--tokens"><?php esc_html_e( 'Total', 'ai-sales-manager-for-woocommerce' ); ?></th>
									<th class="aisales-table__col--product"><?php esc_html_e( 'Product', 'ai-sales-manager-for-woocommerce' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $usage['logs'] as $log ) : ?>
									<tr>
										<td class="aisales-table__col--date">
											<span class="aisales-table__date"><?php echo esc_html( $this->format_date( $log['created_at'] ) ); ?></span>
										</td>
										<td class="aisales-table__col--operation">
											<span class="aisales-op-badge aisales-op-badge--<?php echo esc_attr( $log['operation'] ); ?>">
												<?php echo esc_html( $this->format_operation( $log['operation'] ) ); ?>
											</span>
										</td>
										<td class="aisales-table__col--tokens">
											<span class="aisales-table__tokens aisales-table__tokens--input"><?php echo esc_html( number_format( $log['input_tokens'] ) ); ?></span>
										</td>
										<td class="aisales-table__col--tokens">
											<span class="aisales-table__tokens aisales-table__tokens--output"><?php echo esc_html( number_format( $log['output_tokens'] ) ); ?></span>
										</td>
										<td class="aisales-table__col--tokens">
											<span class="aisales-table__tokens aisales-table__tokens--total"><?php echo esc_html( number_format( $log['total_tokens'] ) ); ?></span>
										</td>
										<td class="aisales-table__col--product">
											<?php if ( ! empty( $log['product_id'] ) ) : ?>
												<a href="<?php echo esc_url( get_edit_post_link( $log['product_id'] ) ); ?>" class="aisales-table__product-link">
													<span class="dashicons dashicons-admin-post"></span>
													#<?php echo esc_html( $log['product_id'] ); ?>
												</a>
											<?php else : ?>
												<span class="aisales-text-muted">—</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<div class="aisales-empty-state aisales-empty-state--enhanced">
						<div class="aisales-empty-state__icon">
							<span class="dashicons dashicons-chart-area"></span>
						</div>
						<h3><?php esc_html_e( 'No usage history yet', 'ai-sales-manager-for-woocommerce' ); ?></h3>
						<p><?php esc_html_e( 'Start by editing a product and using the AI tools to generate content, tags, or images.', 'ai-sales-manager-for-woocommerce' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render billing tab
	 */
	private function render_billing_tab() {
		$api     = AISales_API_Client::instance();
		$account = $api->get_account();

		// Handle API errors gracefully
		$has_error = is_wp_error( $account );
		$balance   = ( ! $has_error && isset( $account['balance_tokens'] ) ) ? $account['balance_tokens'] : 0;

		// Include the billing template
		include AISALES_PLUGIN_DIR . 'templates/pages/billing.php';
	}

	/**
	 * Render account tab
	 */
	private function render_account_tab() {
		$api     = AISales_API_Client::instance();
		$account = $api->get_account();

		// Handle API errors gracefully
		$has_error = is_wp_error( $account );
		$email     = ( ! $has_error && isset( $account['email'] ) ) ? $account['email'] : '';
		$balance   = ( ! $has_error && isset( $account['balance_tokens'] ) ) ? $account['balance_tokens'] : 0;
		$site_url  = wp_parse_url( home_url(), PHP_URL_HOST );

		?>
		<?php if ( $has_error ) : ?>
			<div class="aisales-alert aisales-alert--danger aisales-mb-5">
				<span class="dashicons dashicons-warning"></span>
				<div class="aisales-alert__content">
					<strong><?php esc_html_e( 'API Connection Error:', 'ai-sales-manager-for-woocommerce' ); ?></strong>
					<?php echo esc_html( $account->get_error_message() ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="aisales-account aisales-mt-5">

			<div class="aisales-account__grid">
				<!-- Account Info Card -->
				<div class="aisales-card aisales-card--featured aisales-card--elevated">
					<div class="aisales-card__header">
						<div class="aisales-card__icon aisales-card__icon--blue">
							<span class="dashicons dashicons-admin-users"></span>
						</div>
						<h2><?php esc_html_e( 'Account Information', 'ai-sales-manager-for-woocommerce' ); ?></h2>
					</div>

					<div class="aisales-account__info">
						<div class="aisales-account__row">
							<span class="aisales-account__label">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Email', 'ai-sales-manager-for-woocommerce' ); ?>
							</span>
							<span class="aisales-account__value"><?php echo esc_html( $email ); ?></span>
						</div>
						<div class="aisales-account__row">
							<span class="aisales-account__label">
								<span class="dashicons dashicons-admin-site-alt3"></span>
								<?php esc_html_e( 'Connected Site', 'ai-sales-manager-for-woocommerce' ); ?>
							</span>
							<span class="aisales-account__value"><?php echo esc_html( $site_url ); ?></span>
						</div>
						<div class="aisales-account__row aisales-account__row--highlight">
							<span class="aisales-account__label">
								<span class="dashicons dashicons-database"></span>
								<?php esc_html_e( 'Token Balance', 'ai-sales-manager-for-woocommerce' ); ?>
							</span>
							<span class="aisales-account__value aisales-account__value--balance">
								<?php echo esc_html( number_format( $balance ) ); ?>
								<small><?php esc_html_e( 'tokens', 'ai-sales-manager-for-woocommerce' ); ?></small>
							</span>
						</div>
					</div>

					<div class="aisales-card__footer">
						<a href="<?php echo esc_url( AISALES_API_URL . '/dashboard' ); ?>" target="_blank" class="aisales-btn aisales-btn--gradient">
							<span class="dashicons dashicons-external"></span>
							<?php esc_html_e( 'Manage Account', 'ai-sales-manager-for-woocommerce' ); ?>
						</a>
					</div>
				</div>

				<!-- Danger Zone Card -->
				<div class="aisales-card aisales-card--danger-zone aisales-card--elevated">
					<div class="aisales-card__header">
						<div class="aisales-card__icon aisales-card__icon--red">
							<span class="dashicons dashicons-warning"></span>
						</div>
						<h2><?php esc_html_e( 'Danger Zone', 'ai-sales-manager-for-woocommerce' ); ?></h2>
					</div>

					<div class="aisales-card__body">
						<p class="aisales-text-muted aisales-mb-4">
							<?php esc_html_e( 'Disconnect your account from this WordPress site. Your SaaS account and token balance will remain intact — you can reconnect anytime using the same email.', 'ai-sales-manager-for-woocommerce' ); ?>
						</p>

						<form method="post">
							<?php wp_nonce_field( 'aisales_disconnect_nonce' ); ?>
							<button type="submit" name="aisales_disconnect" class="aisales-btn aisales-btn--danger"
									onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to disconnect? You can reconnect anytime.', 'ai-sales-manager-for-woocommerce' ); ?>');">
								<span class="dashicons dashicons-dismiss"></span>
								<?php esc_html_e( 'Disconnect Account', 'ai-sales-manager-for-woocommerce' ); ?>
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
			/* translators: %d: number of minutes */
			return sprintf( __( '%d min ago', 'ai-sales-manager-for-woocommerce' ), floor( $diff / MINUTE_IN_SECONDS ) );
		} elseif ( $diff < DAY_IN_SECONDS ) {
			/* translators: %d: number of hours */
			return sprintf( __( '%d hours ago', 'ai-sales-manager-for-woocommerce' ), floor( $diff / HOUR_IN_SECONDS ) );
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
			'content'        => __( 'Content', 'ai-sales-manager-for-woocommerce' ),
			'taxonomy'       => __( 'Taxonomy', 'ai-sales-manager-for-woocommerce' ),
			'image_generate' => __( 'Image Generate', 'ai-sales-manager-for-woocommerce' ),
			'image_improve'  => __( 'Image Improve', 'ai-sales-manager-for-woocommerce' ),
		);

		return isset( $labels[ $operation ] ) ? $labels[ $operation ] : $operation;
	}
}
