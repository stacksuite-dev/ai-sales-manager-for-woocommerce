<?php
/**
 * SEO API Checks
 *
 * Performs advanced SEO checks that require API calls (uses tokens).
 * Includes keyword analysis, content quality, duplicate detection, etc.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO API Checks class
 */
class AISales_SEO_Checks_API {

	/**
	 * API client instance
	 *
	 * @var AISales_API_Client
	 */
	private $api;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->api = AISales_API_Client::instance();
	}

	/**
	 * Check if API checks are available (has balance)
	 *
	 * @return bool
	 */
	public function is_available() {
		$balance = get_option( 'aisales_balance', 0 );
		return $balance >= 100; // Minimum balance for API checks.
	}

	/**
	 * Analyze keyword density for content
	 *
	 * @param string $content      The content to analyze.
	 * @param string $focus_keyword Optional focus keyword.
	 * @return array|WP_Error Analysis result or error.
	 */
	public function analyze_keyword_density( $content, $focus_keyword = '' ) {
		if ( ! $this->is_available() ) {
			return new WP_Error( 'insufficient_balance', __( 'Insufficient token balance for API checks.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$prompt = "Analyze the keyword density and SEO optimization of this content.\n\n";
		$prompt .= "Content:\n" . wp_trim_words( wp_strip_all_tags( $content ), 500 ) . "\n\n";

		if ( ! empty( $focus_keyword ) ) {
			$prompt .= "Focus keyword: " . $focus_keyword . "\n\n";
		}

		$prompt .= "Respond with a JSON object containing:\n";
		$prompt .= "- main_keywords: array of top 5 keywords found\n";
		$prompt .= "- keyword_density: object with keyword => percentage\n";
		$prompt .= "- recommendations: array of improvement suggestions\n";
		$prompt .= "- score: 0-100 overall keyword optimization score";

		$result = $this->api->generate_content( array(
			'type'   => 'seo_keyword_analysis',
			'prompt' => $prompt,
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Parse JSON response.
		$analysis = $this->parse_json_response( $result );

		return array(
			'analysis'    => $analysis,
			'tokens_used' => $result['tokens_used']['total'] ?? 100,
		);
	}

	/**
	 * Check content quality and readability
	 *
	 * @param string $content The content to check.
	 * @param string $type    Content type (product, page, post).
	 * @return array|WP_Error Quality assessment or error.
	 */
	public function check_content_quality( $content, $type = 'content' ) {
		if ( ! $this->is_available() ) {
			return new WP_Error( 'insufficient_balance', __( 'Insufficient token balance for API checks.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$prompt = "Analyze the quality and readability of this {$type} content for e-commerce SEO.\n\n";
		$prompt .= "Content:\n" . wp_trim_words( wp_strip_all_tags( $content ), 500 ) . "\n\n";
		$prompt .= "Evaluate:\n";
		$prompt .= "1. Readability (is it easy to understand?)\n";
		$prompt .= "2. Engagement (does it encourage action?)\n";
		$prompt .= "3. SEO value (does it include relevant keywords naturally?)\n";
		$prompt .= "4. Structure (proper use of paragraphs, flow)\n\n";
		$prompt .= "Respond with JSON:\n";
		$prompt .= "- readability_score: 0-100\n";
		$prompt .= "- engagement_score: 0-100\n";
		$prompt .= "- seo_score: 0-100\n";
		$prompt .= "- structure_score: 0-100\n";
		$prompt .= "- overall_score: 0-100\n";
		$prompt .= "- issues: array of specific problems found\n";
		$prompt .= "- improvements: array of suggestions";

		$result = $this->api->generate_content( array(
			'type'   => 'seo_content_quality',
			'prompt' => $prompt,
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$analysis = $this->parse_json_response( $result );

		return array(
			'analysis'    => $analysis,
			'tokens_used' => $result['tokens_used']['total'] ?? 150,
		);
	}

	/**
	 * Detect duplicate or similar content
	 *
	 * @param string $content       Content to check.
	 * @param int    $exclude_id    ID to exclude from comparison.
	 * @param string $content_type  Type of content (product, post, page).
	 * @return array|WP_Error Duplicate detection results or error.
	 */
	public function detect_duplicates( $content, $exclude_id = 0, $content_type = 'product' ) {
		// This is a simplified local check - comparing with other content.
		$similar_items = array();
		$content_hash  = md5( wp_strip_all_tags( $content ) );
		$content_words = str_word_count( wp_strip_all_tags( $content ) );

		if ( 'product' === $content_type ) {
			$products = wc_get_products( array(
				'limit'   => 50,
				'exclude' => array( $exclude_id ),
				'status'  => 'publish',
			) );

			foreach ( $products as $product ) {
				$product_content = $product->get_description() . ' ' . $product->get_short_description();
				$similarity      = $this->calculate_similarity( $content, $product_content );

				if ( $similarity > 70 ) {
					$similar_items[] = array(
						'id'         => $product->get_id(),
						'title'      => $product->get_name(),
						'similarity' => $similarity,
						'edit_url'   => get_edit_post_link( $product->get_id(), 'raw' ),
					);
				}
			}
		}

		return array(
			'has_duplicates' => ! empty( $similar_items ),
			'similar_items'  => $similar_items,
			'tokens_used'    => 0, // Local check, no API tokens used.
		);
	}

	/**
	 * Validate schema markup
	 *
	 * @param int    $item_id   The item ID.
	 * @param string $item_type The item type.
	 * @return array|WP_Error Schema validation results or error.
	 */
	public function validate_schema( $item_id, $item_type = 'product' ) {
		$issues = array();

		if ( 'product' === $item_type ) {
			$product = wc_get_product( $item_id );
			if ( ! $product ) {
				return new WP_Error( 'product_not_found', __( 'Product not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
			}

			// Check for required schema properties.
			if ( empty( $product->get_name() ) ) {
				$issues[] = array(
					'field'    => 'name',
					'severity' => 'critical',
					'message'  => __( 'Product name is required for schema.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}

			if ( empty( $product->get_price() ) && ! $product->is_type( 'variable' ) ) {
				$issues[] = array(
					'field'    => 'price',
					'severity' => 'critical',
					'message'  => __( 'Product price is required for schema.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}

			if ( ! $product->get_image_id() ) {
				$issues[] = array(
					'field'    => 'image',
					'severity' => 'warning',
					'message'  => __( 'Product image improves schema display.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}

			if ( empty( $product->get_description() ) && empty( $product->get_short_description() ) ) {
				$issues[] = array(
					'field'    => 'description',
					'severity' => 'warning',
					'message'  => __( 'Product description improves schema display.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}

			// Check availability.
			$stock_status = $product->get_stock_status();
			if ( empty( $stock_status ) ) {
				$issues[] = array(
					'field'    => 'availability',
					'severity' => 'warning',
					'message'  => __( 'Stock status should be set for schema.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}
		}

		$score = count( $issues ) > 0 ? max( 0, 100 - ( count( $issues ) * 15 ) ) : 100;

		return array(
			'is_valid'    => empty( $issues ),
			'issues'      => $issues,
			'score'       => $score,
			'tokens_used' => 0, // Local check.
		);
	}

	/**
	 * Suggest internal linking opportunities
	 *
	 * @param int    $item_id   The item ID.
	 * @param string $item_type The item type.
	 * @return array|WP_Error Linking suggestions or error.
	 */
	public function suggest_internal_links( $item_id, $item_type = 'product' ) {
		$suggestions = array();

		if ( 'product' === $item_type ) {
			$product = wc_get_product( $item_id );
			if ( ! $product ) {
				return new WP_Error( 'product_not_found', __( 'Product not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
			}

			// Get related products.
			$related_ids = wc_get_related_products( $item_id, 5 );
			foreach ( $related_ids as $related_id ) {
				$related = wc_get_product( $related_id );
				if ( $related ) {
					$suggestions[] = array(
						'type'   => 'related_product',
						'id'     => $related_id,
						'title'  => $related->get_name(),
						'url'    => $related->get_permalink(),
						'reason' => __( 'Related product', 'stacksuite-sales-manager-for-woocommerce' ),
					);
				}
			}

			// Get category pages.
			$category_ids = $product->get_category_ids();
			foreach ( $category_ids as $cat_id ) {
				$term = get_term( $cat_id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$suggestions[] = array(
						'type'   => 'category',
						'id'     => $cat_id,
						'title'  => $term->name,
						'url'    => get_term_link( $term ),
						'reason' => __( 'Product category', 'stacksuite-sales-manager-for-woocommerce' ),
					);
				}
			}

			// Upsells and cross-sells.
			$upsell_ids = $product->get_upsell_ids();
			foreach ( array_slice( $upsell_ids, 0, 3 ) as $upsell_id ) {
				$upsell = wc_get_product( $upsell_id );
				if ( $upsell ) {
					$suggestions[] = array(
						'type'   => 'upsell',
						'id'     => $upsell_id,
						'title'  => $upsell->get_name(),
						'url'    => $upsell->get_permalink(),
						'reason' => __( 'Upsell product', 'stacksuite-sales-manager-for-woocommerce' ),
					);
				}
			}
		}

		return array(
			'suggestions' => $suggestions,
			'tokens_used' => 0, // Local analysis.
		);
	}

	/**
	 * Calculate text similarity (simple Jaccard similarity)
	 *
	 * @param string $text1 First text.
	 * @param string $text2 Second text.
	 * @return float Similarity percentage (0-100).
	 */
	private function calculate_similarity( $text1, $text2 ) {
		$words1 = array_unique( str_word_count( strtolower( wp_strip_all_tags( $text1 ) ), 1 ) );
		$words2 = array_unique( str_word_count( strtolower( wp_strip_all_tags( $text2 ) ), 1 ) );

		if ( empty( $words1 ) || empty( $words2 ) ) {
			return 0;
		}

		$intersection = array_intersect( $words1, $words2 );
		$union        = array_unique( array_merge( $words1, $words2 ) );

		if ( empty( $union ) ) {
			return 0;
		}

		return round( ( count( $intersection ) / count( $union ) ) * 100, 1 );
	}

	/**
	 * Parse JSON response from API
	 *
	 * @param array $result API result.
	 * @return array Parsed data.
	 */
	private function parse_json_response( $result ) {
		$content = $result['content'] ?? $result['text'] ?? '';

		// Try to extract JSON from the response.
		if ( preg_match( '/\{[\s\S]*\}/', $content, $matches ) ) {
			$decoded = json_decode( $matches[0], true );
			if ( $decoded ) {
				return $decoded;
			}
		}

		// Fallback: return as-is.
		return array(
			'raw_response' => $content,
		);
	}
}
