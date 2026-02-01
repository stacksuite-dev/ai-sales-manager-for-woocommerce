<?php
/**
 * SEO AJAX Handler
 *
 * Handles AJAX requests for SEO scanning and fixing functionality.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO AJAX Handler class
 */
class AISales_Ajax_SEO extends AISales_Ajax_Base {

	/**
	 * Register AJAX actions
	 */
	protected function register_actions() {
		$this->add_action( 'seo_run_scan', 'handle_run_scan' );
		$this->add_action( 'seo_get_results', 'handle_get_results' );
		$this->add_action( 'seo_generate_fix', 'handle_generate_fix' );
		$this->add_action( 'seo_apply_fix', 'handle_apply_fix' );
		$this->add_action( 'seo_dismiss_issue', 'handle_dismiss_issue' );
	}

	/**
	 * Handle run scan request
	 */
	public function handle_run_scan() {
		$this->verify_request();

		$filter_type     = $this->get_post( 'filter_type', 'key', 'all' );
		$filter_priority = $this->get_post( 'filter_priority', 'key', 'recent' );

		// Load the analyzer if not already loaded.
		if ( ! class_exists( 'AISales_SEO_Analyzer' ) ) {
			require_once AISALES_PLUGIN_DIR . 'includes/class-aisales-seo-analyzer.php';
		}

		$analyzer    = new AISales_SEO_Analyzer();
		$new_results = $analyzer->run_scan( $filter_type, $filter_priority );

		// If filtered scan, merge with existing results.
		if ( 'all' !== $filter_type ) {
			$new_results = $this->merge_scan_results( $new_results, $filter_type );
		}

		// Save results to options.
		update_option( 'aisales_seo_scan_results', $new_results );

		$this->success( array(
			'results' => $new_results,
		) );
	}

	/**
	 * Merge filtered scan results with existing results
	 *
	 * @param array  $new_results New scan results.
	 * @param string $filter_type The content type that was scanned.
	 * @return array Merged results.
	 */
	private function merge_scan_results( $new_results, $filter_type ) {
		$existing = get_option( 'aisales_seo_scan_results', array() );

		// If no existing results, just return new results.
		if ( empty( $existing ) || ! isset( $existing['scores'] ) ) {
			return $new_results;
		}

		// Get newly scanned categories from the analyzer results.
		$newly_scanned = isset( $new_results['scanned_categories'] ) ? $new_results['scanned_categories'] : array( $filter_type );

		// Merge with previously scanned categories.
		$existing_scanned   = isset( $existing['scanned_categories'] ) ? $existing['scanned_categories'] : array();
		$scanned_categories = array_unique( array_merge( $existing_scanned, $newly_scanned ) );

		// Merge scores - keep existing for non-scanned categories.
		$merged_scores = $existing['scores'];
		foreach ( $new_results['scores'] as $category => $score ) {
			$merged_scores[ $category ] = $score;
		}

		// Merge detailed_issues - keep existing for non-scanned categories.
		$merged_issues = isset( $existing['detailed_issues'] ) ? $existing['detailed_issues'] : array();
		foreach ( $new_results['detailed_issues'] as $category => $issues ) {
			$merged_issues[ $category ] = $issues;
		}

		// Recalculate issue counts from merged detailed_issues.
		$critical_count = 0;
		$warning_count  = 0;
		$passed_count   = isset( $existing['issues']['passed'] ) ? $existing['issues']['passed'] : 0;

		// Subtract old passed count for scanned categories, add new.
		foreach ( $scanned_categories as $cat ) {
			// We don't have a way to know old passed count per category, so we'll recalculate.
		}

		// Count issues from all categories.
		foreach ( $merged_issues as $category => $issues ) {
			foreach ( $issues as $issue ) {
				if ( isset( $issue['severity'] ) ) {
					if ( 'critical' === $issue['severity'] ) {
						++$critical_count;
					} elseif ( 'warning' === $issue['severity'] ) {
						++$warning_count;
					}
				}
			}
		}

		// Use new passed count for scanned category, keep existing for others.
		// For simplicity, we'll use the new passed count if available.
		$passed_count = isset( $new_results['issues']['passed'] ) ? $new_results['issues']['passed'] : 0;
		// Add existing passed counts for non-scanned categories (estimate).
		foreach ( $existing['scores'] as $category => $score ) {
			if ( ! in_array( $category, $scanned_categories, true ) && isset( $existing['detailed_issues'][ $category ] ) ) {
				// Estimate passed checks based on score and issues.
				// This is approximate - we don't store passed count per category.
				$cat_issues = count( $existing['detailed_issues'][ $category ] );
				// Assume roughly 8 checks per item, estimate items from issues.
				$passed_count += max( 0, 10 - $cat_issues ); // Rough estimate.
			}
		}

		// Recalculate overall score using weighted average.
		$overall_score = $this->calculate_merged_overall_score( $merged_scores );

		// Update items scanned count.
		$items_scanned = $new_results['items_scanned'];
		if ( isset( $existing['items_scanned'] ) ) {
			// Add existing items for non-scanned categories (rough estimate).
			$items_scanned = max( $items_scanned, $existing['items_scanned'] );
		}

		return array(
			'scan_date'          => $new_results['scan_date'],
			'overall_score'      => $overall_score,
			'scores'             => $merged_scores,
			'issues'             => array(
				'critical' => $critical_count,
				'warnings' => $warning_count,
				'passed'   => $passed_count,
			),
			'items_scanned'      => $items_scanned,
			'detailed_issues'    => $merged_issues,
			'scanned_categories' => array_values( $scanned_categories ), // Ensure sequential array.
		);
	}

	/**
	 * Calculate overall score from merged category scores
	 *
	 * @param array $scores Category scores.
	 * @return int Overall score.
	 */
	private function calculate_merged_overall_score( $scores ) {
		// Weighted scoring - products and homepage more important.
		$weights = array(
			'products'       => 3,
			'categories'     => 2,
			'pages'          => 2,
			'posts'          => 1,
			'store_settings' => 2,
			'homepage'       => 3,
		);

		$total_weight = 0;
		$weighted_sum = 0;

		foreach ( $scores as $category => $score ) {
			$weight        = isset( $weights[ $category ] ) ? $weights[ $category ] : 1;
			$weighted_sum += $score * $weight;
			$total_weight += $weight;
		}

		return $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
	}

	/**
	 * Handle get results request
	 */
	public function handle_get_results() {
		$this->verify_request();

		$results = get_option( 'aisales_seo_scan_results', array() );

		$this->success( array(
			'results' => $results,
		) );
	}

	/**
	 * Handle generate fix request
	 */
	public function handle_generate_fix() {
		$this->verify_request();

		$issue_json = $this->get_post( 'issue', 'raw' );
		if ( empty( $issue_json ) ) {
			$this->error( __( 'No issue provided.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$issue = json_decode( $issue_json, true );
		if ( ! $issue ) {
			$this->error( __( 'Invalid issue data.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Load the fixer if not already loaded.
		if ( ! class_exists( 'AISales_SEO_Fixer' ) ) {
			require_once AISALES_PLUGIN_DIR . 'includes/seo/class-aisales-seo-fixer.php';
		}

		$fixer  = new AISales_SEO_Fixer();
		$result = $fixer->generate_fix( $issue );

		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		}

		// Update balance if tokens were used.
		$new_balance = null;
		if ( isset( $result['tokens_used'] ) ) {
			$current_balance = get_option( 'aisales_balance', 0 );
			$new_balance     = max( 0, intval( $current_balance ) - intval( $result['tokens_used'] ) );
			update_option( 'aisales_balance', $new_balance );
		}

		$this->success( array(
			'fix'         => $result['fix'],
			'tokens_used' => $result['tokens_used'] ?? 0,
			'new_balance' => $new_balance,
		) );
	}

	/**
	 * Handle apply fix request
	 */
	public function handle_apply_fix() {
		$this->verify_request();

		$issue_json = $this->get_post( 'issue', 'raw' );
		$fix_json   = $this->get_post( 'fix', 'raw' );

		if ( empty( $issue_json ) || empty( $fix_json ) ) {
			$this->error( __( 'Missing issue or fix data.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$issue = json_decode( $issue_json, true );
		$fix   = json_decode( $fix_json, true );

		if ( ! $issue || ! $fix ) {
			$this->error( __( 'Invalid issue or fix data.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Load the fixer if not already loaded.
		if ( ! class_exists( 'AISales_SEO_Fixer' ) ) {
			require_once AISALES_PLUGIN_DIR . 'includes/seo/class-aisales-seo-fixer.php';
		}

		$fixer  = new AISales_SEO_Fixer();
		$result = $fixer->apply_fix( $issue, $fix );

		if ( is_wp_error( $result ) ) {
			$this->error( $result->get_error_message() );
		}

		$this->success( array(
			'message' => __( 'Fix applied successfully.', 'stacksuite-sales-manager-for-woocommerce' ),
		) );
	}

	/**
	 * Handle dismiss issue request
	 */
	public function handle_dismiss_issue() {
		$this->verify_request();

		$issue_id = $this->get_post( 'issue_id', 'text' );
		if ( empty( $issue_id ) ) {
			$this->error( __( 'No issue ID provided.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Get current dismissed issues.
		$dismissed = get_option( 'aisales_seo_dismissed_issues', array() );

		// Add this issue to dismissed list.
		if ( ! in_array( $issue_id, $dismissed, true ) ) {
			$dismissed[] = $issue_id;
			update_option( 'aisales_seo_dismissed_issues', $dismissed );
		}

		$this->success( array(
			'message' => __( 'Issue dismissed.', 'stacksuite-sales-manager-for-woocommerce' ),
		) );
	}
}
