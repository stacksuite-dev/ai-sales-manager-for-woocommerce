<?php
/**
 * SEO Analyzer
 *
 * Core analysis engine that coordinates SEO checks across
 * products, categories, pages, posts, store settings, and homepage.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Analyzer class
 */
class AISales_SEO_Analyzer {

	/**
	 * Local checks instance
	 *
	 * @var AISales_SEO_Checks_Local
	 */
	private $local_checks;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->local_checks = new AISales_SEO_Checks_Local();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once AISALES_PLUGIN_DIR . 'includes/seo/class-aisales-seo-checks-local.php';
	}

	/**
	 * Run a full SEO scan
	 *
	 * @param string $filter_type     Content type filter (all, products, categories, pages, posts).
	 * @param string $filter_priority Priority filter (recent, score_asc, score_desc, alpha).
	 * @return array Scan results.
	 */
	public function run_scan( $filter_type = 'all', $filter_priority = 'recent' ) {
		$results = array(
			'scan_date'          => current_time( 'mysql' ),
			'overall_score'      => 0,
			'scores'             => array(),
			'issues'             => array(
				'critical' => 0,
				'warnings' => 0,
				'passed'   => 0,
			),
			'items_scanned'      => 0,
			'detailed_issues'    => array(),
			'scanned_categories' => array(), // Track which categories were actually scanned.
		);

		// Get dismissed issues.
		$dismissed = get_option( 'aisales_seo_dismissed_issues', array() );

		// Run checks based on filter.
		$categories_to_scan = $this->get_categories_to_scan( $filter_type );

		foreach ( $categories_to_scan as $category ) {
			$category_results = $this->scan_category( $category, $filter_priority, $dismissed );

			$results['scores'][ $category ]          = $category_results['score'];
			$results['detailed_issues'][ $category ] = $category_results['issues'];
			$results['items_scanned']               += $category_results['items_scanned'];
			$results['scanned_categories'][]         = $category; // Mark as scanned.

			// Count issues by severity.
			foreach ( $category_results['issues'] as $issue ) {
				if ( 'critical' === $issue['severity'] ) {
					++$results['issues']['critical'];
				} elseif ( 'warning' === $issue['severity'] ) {
					++$results['issues']['warnings'];
				}
			}
			$results['issues']['passed'] += $category_results['checks_passed'];
		}

		// Calculate overall score.
		$results['overall_score'] = $this->calculate_overall_score( $results['scores'] );

		return $results;
	}

	/**
	 * Get categories to scan based on filter
	 *
	 * @param string $filter_type Content type filter.
	 * @return array Categories to scan.
	 */
	private function get_categories_to_scan( $filter_type ) {
		$all_categories = array( 'products', 'categories', 'pages', 'posts', 'store_settings', 'homepage' );

		if ( 'all' === $filter_type ) {
			return $all_categories;
		}

		// Map filter type to category.
		$mapping = array(
			'products'   => array( 'products' ),
			'categories' => array( 'categories' ),
			'pages'      => array( 'pages' ),
			'posts'      => array( 'posts' ),
		);

		return isset( $mapping[ $filter_type ] ) ? $mapping[ $filter_type ] : $all_categories;
	}

	/**
	 * Scan a specific category
	 *
	 * @param string $category        Category to scan.
	 * @param string $filter_priority Priority filter.
	 * @param array  $dismissed       Dismissed issue IDs.
	 * @return array Category scan results.
	 */
	private function scan_category( $category, $filter_priority, $dismissed = array() ) {
		switch ( $category ) {
			case 'products':
				return $this->scan_products( $filter_priority, $dismissed );
			case 'categories':
				return $this->scan_categories( $filter_priority, $dismissed );
			case 'pages':
				return $this->scan_pages( $filter_priority, $dismissed );
			case 'posts':
				return $this->scan_posts( $filter_priority, $dismissed );
			case 'store_settings':
				return $this->scan_store_settings( $dismissed );
			case 'homepage':
				return $this->scan_homepage( $dismissed );
			default:
				return array(
					'score'         => 100,
					'issues'        => array(),
					'items_scanned' => 0,
					'checks_passed' => 0,
				);
		}
	}

	/**
	 * Scan products for SEO issues
	 *
	 * @param string $filter_priority Priority filter.
	 * @param array  $dismissed       Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_products( $filter_priority, $dismissed = array() ) {
		$issues        = array();
		$checks_passed = 0;
		$total_score   = 0;

		// Get products based on priority.
		$args = array(
			'limit'  => 100, // Limit for performance.
			'status' => 'publish',
		);

		switch ( $filter_priority ) {
			case 'recent':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'alpha':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$products = wc_get_products( $args );

		foreach ( $products as $product ) {
			$product_issues = $this->local_checks->check_product( $product );

			foreach ( $product_issues as $issue ) {
				$issue_id = 'product_' . $product->get_id() . '_' . $issue['check'];

				if ( in_array( $issue_id, $dismissed, true ) ) {
					continue;
				}

				$issues[] = array_merge( $issue, array(
					'id'         => $issue_id,
					'item_type'  => 'product',
					'item_id'    => $product->get_id(),
					'item_name'  => $product->get_name(),
					'edit_url'   => get_edit_post_link( $product->get_id(), 'raw' ),
					'fixable'    => $this->is_issue_fixable( $issue['check'] ),
				) );
			}

			// Count passed checks.
			$checks_passed += ( 8 - count( $product_issues ) ); // 8 total checks per product.

			// Cache individual product score.
			$product_score = $this->calculate_item_score( count( $product_issues ), 8 );
			update_post_meta( $product->get_id(), '_aisales_seo_score', $product_score );
			update_post_meta( $product->get_id(), '_aisales_seo_last_check', current_time( 'mysql' ) );

			$total_score += $product_score;
		}

		$items_count = count( $products );
		$avg_score   = $items_count > 0 ? round( $total_score / $items_count ) : 100;

		// Sort issues by severity if needed.
		if ( 'score_asc' === $filter_priority ) {
			usort( $issues, function( $a, $b ) {
				return ( 'critical' === $a['severity'] ) ? -1 : 1;
			} );
		}

		return array(
			'score'         => $avg_score,
			'issues'        => $issues,
			'items_scanned' => $items_count,
			'checks_passed' => $checks_passed,
		);
	}

	/**
	 * Scan categories for SEO issues
	 *
	 * @param string $filter_priority Priority filter.
	 * @param array  $dismissed       Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_categories( $filter_priority, $dismissed = array() ) {
		$issues        = array();
		$checks_passed = 0;
		$total_score   = 0;

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 100, // Limit for performance.
		);

		switch ( $filter_priority ) {
			case 'alpha':
				$args['orderby'] = 'name';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'count';
				$args['order']   = 'DESC';
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return array(
				'score'         => 100,
				'issues'        => array(),
				'items_scanned' => 0,
				'checks_passed' => 0,
			);
		}

		foreach ( $terms as $term ) {
			$term_issues = $this->local_checks->check_category( $term );

			foreach ( $term_issues as $issue ) {
				$issue_id = 'category_' . $term->term_id . '_' . $issue['check'];

				if ( in_array( $issue_id, $dismissed, true ) ) {
					continue;
				}

				$issues[] = array_merge( $issue, array(
					'id'         => $issue_id,
					'item_type'  => 'category',
					'item_id'    => $term->term_id,
					'item_name'  => $term->name,
					'edit_url'   => get_edit_term_link( $term->term_id, 'product_cat' ),
					'fixable'    => $this->is_issue_fixable( $issue['check'] ),
				) );
			}

			// Count passed checks.
			$checks_passed += ( 5 - count( $term_issues ) ); // 5 total checks per category.

			// Cache individual term score.
			$term_score = $this->calculate_item_score( count( $term_issues ), 5 );
			update_term_meta( $term->term_id, '_aisales_seo_score', $term_score );

			$total_score += $term_score;
		}

		$items_count = count( $terms );
		$avg_score   = $items_count > 0 ? round( $total_score / $items_count ) : 100;

		return array(
			'score'         => $avg_score,
			'issues'        => $issues,
			'items_scanned' => $items_count,
			'checks_passed' => $checks_passed,
		);
	}

	/**
	 * Scan pages for SEO issues
	 *
	 * @param string $filter_priority Priority filter.
	 * @param array  $dismissed       Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_pages( $filter_priority, $dismissed = array() ) {
		$issues        = array();
		$checks_passed = 0;
		$total_score   = 0;

		$args = array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		);

		switch ( $filter_priority ) {
			case 'recent':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'alpha':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$pages = get_posts( $args );

		foreach ( $pages as $page ) {
			$page_issues = $this->local_checks->check_page( $page );

			foreach ( $page_issues as $issue ) {
				$issue_id = 'page_' . $page->ID . '_' . $issue['check'];

				if ( in_array( $issue_id, $dismissed, true ) ) {
					continue;
				}

				$issues[] = array_merge( $issue, array(
					'id'         => $issue_id,
					'item_type'  => 'page',
					'item_id'    => $page->ID,
					'item_name'  => $page->post_title,
					'edit_url'   => get_edit_post_link( $page->ID, 'raw' ),
					'fixable'    => $this->is_issue_fixable( $issue['check'] ),
				) );
			}

			$checks_passed += ( 9 - count( $page_issues ) );

			$page_score = $this->calculate_item_score( count( $page_issues ), 9 );
			update_post_meta( $page->ID, '_aisales_seo_score', $page_score );

			$total_score += $page_score;
		}

		$items_count = count( $pages );
		$avg_score   = $items_count > 0 ? round( $total_score / $items_count ) : 100;

		return array(
			'score'         => $avg_score,
			'issues'        => $issues,
			'items_scanned' => $items_count,
			'checks_passed' => $checks_passed,
		);
	}

	/**
	 * Scan blog posts for SEO issues
	 *
	 * @param string $filter_priority Priority filter.
	 * @param array  $dismissed       Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_posts( $filter_priority, $dismissed = array() ) {
		$issues        = array();
		$checks_passed = 0;
		$total_score   = 0;

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		);

		switch ( $filter_priority ) {
			case 'recent':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			case 'alpha':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$post_issues = $this->local_checks->check_post( $post );

			foreach ( $post_issues as $issue ) {
				$issue_id = 'post_' . $post->ID . '_' . $issue['check'];

				if ( in_array( $issue_id, $dismissed, true ) ) {
					continue;
				}

				$issues[] = array_merge( $issue, array(
					'id'         => $issue_id,
					'item_type'  => 'post',
					'item_id'    => $post->ID,
					'item_name'  => $post->post_title,
					'edit_url'   => get_edit_post_link( $post->ID, 'raw' ),
					'fixable'    => $this->is_issue_fixable( $issue['check'] ),
				) );
			}

			$checks_passed += ( 10 - count( $post_issues ) );

			$post_score = $this->calculate_item_score( count( $post_issues ), 10 );
			update_post_meta( $post->ID, '_aisales_seo_score', $post_score );

			$total_score += $post_score;
		}

		$items_count = count( $posts );
		$avg_score   = $items_count > 0 ? round( $total_score / $items_count ) : 100;

		return array(
			'score'         => $avg_score,
			'issues'        => $issues,
			'items_scanned' => $items_count,
			'checks_passed' => $checks_passed,
		);
	}

	/**
	 * Scan store settings for SEO issues
	 *
	 * @param array $dismissed Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_store_settings( $dismissed = array() ) {
		$store_issues = $this->local_checks->check_store_settings();
		$issues       = array();

		foreach ( $store_issues as $issue ) {
			$issue_id = 'store_' . $issue['check'];

			if ( in_array( $issue_id, $dismissed, true ) ) {
				continue;
			}

			$issues[] = array_merge( $issue, array(
				'id'         => $issue_id,
				'item_type'  => 'store_settings',
				'item_id'    => 0,
				'item_name'  => __( 'Store Settings', 'stacksuite-sales-manager-for-woocommerce' ),
				'edit_url'   => admin_url( 'options-general.php' ),
				'fixable'    => false,
			) );
		}

		$total_checks = 6;
		$score        = $this->calculate_item_score( count( $issues ), $total_checks );

		return array(
			'score'         => $score,
			'issues'        => $issues,
			'items_scanned' => 1,
			'checks_passed' => $total_checks - count( $issues ),
		);
	}

	/**
	 * Scan homepage for SEO issues
	 *
	 * @param array $dismissed Dismissed issue IDs.
	 * @return array Scan results.
	 */
	private function scan_homepage( $dismissed = array() ) {
		$homepage_issues = $this->local_checks->check_homepage();
		$issues          = array();

		foreach ( $homepage_issues as $issue ) {
			$issue_id = 'homepage_' . $issue['check'];

			if ( in_array( $issue_id, $dismissed, true ) ) {
				continue;
			}

			$issues[] = array_merge( $issue, array(
				'id'         => $issue_id,
				'item_type'  => 'homepage',
				'item_id'    => get_option( 'page_on_front', 0 ),
				'item_name'  => __( 'Homepage', 'stacksuite-sales-manager-for-woocommerce' ),
				'edit_url'   => admin_url( 'options-reading.php' ),
				'fixable'    => $this->is_issue_fixable( $issue['check'] ),
			) );
		}

		$total_checks = 5;
		$score        = $this->calculate_item_score( count( $issues ), $total_checks );

		return array(
			'score'         => $score,
			'issues'        => $issues,
			'items_scanned' => 1,
			'checks_passed' => $total_checks - count( $issues ),
		);
	}

	/**
	 * Check if an issue type is fixable via AI
	 *
	 * @param string $check_type The check type.
	 * @return bool Whether the issue can be fixed.
	 */
	private function is_issue_fixable( $check_type ) {
		$fixable_checks = array(
			'title_length',
			'meta_description_missing',
			'meta_description_length',
			'content_thin',
			'focus_keyword',
		);

		return in_array( $check_type, $fixable_checks, true );
	}

	/**
	 * Calculate item score based on issues
	 *
	 * @param int $issues_count Number of issues found.
	 * @param int $total_checks Total number of checks.
	 * @return int Score (0-100).
	 */
	private function calculate_item_score( $issues_count, $total_checks ) {
		if ( $total_checks <= 0 ) {
			return 100;
		}

		$passed = $total_checks - $issues_count;

		return max( 0, min( 100, round( ( $passed / $total_checks ) * 100 ) ) );
	}

	/**
	 * Calculate overall score from category scores
	 *
	 * @param array $scores Category scores.
	 * @return int Overall score (0-100).
	 */
	private function calculate_overall_score( $scores ) {
		if ( empty( $scores ) ) {
			return 0;
		}

		// Weighted average - products and homepage are more important.
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
}
