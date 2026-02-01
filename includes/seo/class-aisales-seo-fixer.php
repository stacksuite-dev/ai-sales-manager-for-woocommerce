<?php
/**
 * SEO Fixer
 *
 * Handles AI-powered SEO fix generation and application.
 * Sends comprehensive context to AI for intelligent suggestions.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Fixer class
 */
class AISales_SEO_Fixer {

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
	 * Generate an AI-powered fix for an issue
	 *
	 * @param array $issue The issue to fix.
	 * @return array|WP_Error Fix data or error.
	 */
	public function generate_fix( $issue ) {
		if ( empty( $issue['check'] ) || empty( $issue['item_type'] ) ) {
			return new WP_Error( 'invalid_issue', __( 'Invalid issue data.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Validate fix type is supported.
		$fix_type = $this->get_fix_type( $issue['check'] );
		if ( ! $fix_type ) {
			return new WP_Error( 'unsupported_fix', __( 'This issue type cannot be auto-fixed.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Build comprehensive request data.
		$request_data = $this->build_request_data( $issue, $fix_type );
		if ( is_wp_error( $request_data ) ) {
			return $request_data;
		}

		// Call AI API.
		$result = $this->api->generate_seo_content( $request_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Format response.
		return $this->format_fix_response( $result, $issue, $fix_type );
	}

	/**
	 * Get fix type from issue check
	 *
	 * @param string $check The check identifier.
	 * @return string|false Fix type or false if unsupported.
	 */
	private function get_fix_type( $check ) {
		$mapping = array(
			'title_length'             => 'title',
			'meta_description_missing' => 'meta_description',
			'meta_description_length'  => 'meta_description',
			'content_thin'             => 'content',
			'focus_keyword'            => 'keyword',
		);

		return isset( $mapping[ $check ] ) ? $mapping[ $check ] : false;
	}

	/**
	 * Build comprehensive request data for AI
	 *
	 * @param array  $issue    The issue data.
	 * @param string $fix_type The type of fix needed.
	 * @return array|WP_Error Request data or error.
	 */
	private function build_request_data( $issue, $fix_type ) {
		// Get full item context.
		$item_context = $this->get_item_context( $issue );
		if ( is_wp_error( $item_context ) ) {
			return $item_context;
		}

		// Get store context for brand-aware suggestions.
		$store_context = $this->get_store_context();

		// Build the request.
		return array(
			'fix_type'      => $fix_type,
			'item'          => $item_context,
			'issue'         => array(
				'check'         => $issue['check'],
				'severity'      => $issue['severity'] ?? 'warning',
				'current_value' => $issue['current_value'] ?? '',
				'description'   => $issue['description'] ?? '',
			),
			'store_context' => $store_context,
			'requirements'  => $this->get_fix_requirements( $fix_type ),
		);
	}

	/**
	 * Get comprehensive context for an item
	 *
	 * @param array $issue The issue data.
	 * @return array|WP_Error Context data or error.
	 */
	private function get_item_context( $issue ) {
		$item_type = $issue['item_type'] ?? '';
		$item_id   = $issue['item_id'] ?? 0;

		switch ( $item_type ) {
			case 'product':
				return $this->get_product_context( $item_id );

			case 'category':
				return $this->get_category_context( $item_id );

			case 'page':
			case 'post':
				return $this->get_post_context( $item_id, $item_type );

			default:
				return new WP_Error( 'unsupported_type', __( 'Unsupported item type.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Get product context with all relevant data
	 *
	 * @param int $product_id Product ID.
	 * @return array|WP_Error Product context or error.
	 */
	private function get_product_context( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'product_not_found', __( 'Product not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Get category names.
		$category_ids = $product->get_category_ids();
		$categories   = array();
		if ( ! empty( $category_ids ) ) {
			$terms = get_terms( array(
				'taxonomy'   => 'product_cat',
				'include'    => $category_ids,
				'hide_empty' => false,
			) );
			if ( ! is_wp_error( $terms ) ) {
				$categories = wp_list_pluck( $terms, 'name' );
			}
		}

		// Get tag names.
		$tag_ids = $product->get_tag_ids();
		$tags    = array();
		if ( ! empty( $tag_ids ) ) {
			$terms = get_terms( array(
				'taxonomy'   => 'product_tag',
				'include'    => $tag_ids,
				'hide_empty' => false,
			) );
			if ( ! is_wp_error( $terms ) ) {
				$tags = wp_list_pluck( $terms, 'name' );
			}
		}

		// Get attributes - format as array of {name, value} objects for API.
		$attributes    = array();
		$product_attrs = $product->get_attributes();
		foreach ( $product_attrs as $attr ) {
			if ( is_a( $attr, 'WC_Product_Attribute' ) ) {
				$options = $attr->get_options();
				$attributes[] = array(
					'name'  => $attr->get_name(),
					'value' => is_array( $options ) ? implode( ', ', $options ) : (string) $options,
				);
			}
		}

		return array(
			'type'              => 'product',
			'id'                => $product_id,
			'title'             => $product->get_name(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'url'               => $product->get_permalink(),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'sku'               => $product->get_sku(),
			'categories'        => $categories,
			'tags'              => $tags,
			'attributes'        => $attributes,
			'stock_status'      => $product->get_stock_status(),
			'average_rating'    => $product->get_average_rating(),
			'review_count'      => $product->get_review_count(),
			'meta_description'  => $this->get_meta_description( $product_id ),
		);
	}

	/**
	 * Get category context
	 *
	 * @param int $term_id Term ID.
	 * @return array|WP_Error Category context or error.
	 */
	private function get_category_context( $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'category_not_found', __( 'Category not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Get parent category if exists.
		$parent_name = '';
		if ( $term->parent > 0 ) {
			$parent = get_term( $term->parent, 'product_cat' );
			if ( $parent && ! is_wp_error( $parent ) ) {
				$parent_name = $parent->name;
			}
		}

		// Get sample products in this category.
		$sample_products = wc_get_products( array(
			'limit'    => 5,
			'category' => array( $term->slug ),
			'status'   => 'publish',
		) );
		$product_names = array();
		foreach ( $sample_products as $product ) {
			$product_names[] = $product->get_name();
		}

		return array(
			'type'             => 'category',
			'id'               => $term_id,
			'title'            => $term->name,
			'description'      => $term->description,
			'slug'             => $term->slug,
			'url'              => get_term_link( $term ),
			'product_count'    => $term->count,
			'parent_category'  => $parent_name,
			'sample_products'  => $product_names,
			'meta_description' => $this->get_term_meta_description( $term_id ),
		);
	}

	/**
	 * Get post/page context
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $post_type Post type.
	 * @return array|WP_Error Post context or error.
	 */
	private function get_post_context( $post_id, $post_type ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Content not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Get categories for posts.
		$categories = array();
		if ( 'post' === $post_type ) {
			$terms = get_the_category( $post_id );
			if ( ! empty( $terms ) ) {
				$categories = wp_list_pluck( $terms, 'name' );
			}
		}

		// Get tags.
		$tags      = array();
		$tag_terms = get_the_tags( $post_id );
		if ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) {
			$tags = wp_list_pluck( $tag_terms, 'name' );
		}

		// Extract headings from content for context.
		$headings = $this->extract_headings( $post->post_content );

		return array(
			'type'             => $post_type,
			'id'               => $post_id,
			'title'            => $post->post_title,
			'content'          => $post->post_content,
			'excerpt'          => $post->post_excerpt,
			'url'              => get_permalink( $post_id ),
			'categories'       => $categories,
			'tags'             => $tags,
			'headings'         => $headings,
			'word_count'       => str_word_count( wp_strip_all_tags( $post->post_content ) ),
			'meta_description' => $this->get_meta_description( $post_id ),
		);
	}

	/**
	 * Get store context for brand-aware suggestions
	 *
	 * @return array Store context.
	 */
	private function get_store_context() {
		// Get brand settings if available.
		$brand_settings = get_option( 'aisales_brand_settings', array() );

		// Get basic store info.
		$store_name = get_bloginfo( 'name' );
		$store_desc = get_bloginfo( 'description' );

		return array(
			'store_name'    => $store_name,
			'store_tagline' => $store_desc,
			'brand_voice'   => $brand_settings['voice'] ?? 'professional',
			'brand_tone'    => $brand_settings['tone'] ?? 'friendly',
			'target_market' => $brand_settings['target_market'] ?? '',
			'unique_selling_points' => $brand_settings['usp'] ?? array(),
			'industry'      => $brand_settings['industry'] ?? '',
		);
	}

	/**
	 * Get fix requirements based on type
	 *
	 * @param string $fix_type The fix type.
	 * @return array Requirements for the fix.
	 */
	private function get_fix_requirements( $fix_type ) {
		$requirements = array(
			'title' => array(
				'min_length'  => 30,
				'max_length'  => 60,
				'must_include_keywords' => true,
				'compelling'  => true,
				'unique'      => true,
			),
			'meta_description' => array(
				'min_length'  => 120,
				'max_length'  => 160,
				'call_to_action' => true,
				'include_keywords' => true,
				'encourage_clicks' => true,
			),
			'content' => array(
				'min_words'   => 150,
				'use_headings' => true,
				'natural_keywords' => true,
				'engaging'    => true,
				'informative' => true,
			),
			'keyword' => array(
				'word_count'  => '2-4 words',
				'relevant'    => true,
				'searchable'  => true,
			),
		);

		return $requirements[ $fix_type ] ?? array();
	}

	/**
	 * Get meta description from SEO plugins or excerpt
	 *
	 * @param int $post_id Post ID.
	 * @return string Meta description.
	 */
	private function get_meta_description( $post_id ) {
		// Try Yoast SEO.
		$meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Try RankMath.
		$meta = get_post_meta( $post_id, 'rank_math_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Try All in One SEO.
		$meta = get_post_meta( $post_id, '_aioseo_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Fallback to excerpt.
		$post = get_post( $post_id );
		return $post ? $post->post_excerpt : '';
	}

	/**
	 * Get term meta description
	 *
	 * @param int $term_id Term ID.
	 * @return string Meta description.
	 */
	private function get_term_meta_description( $term_id ) {
		// Try Yoast SEO.
		$meta = get_term_meta( $term_id, 'wpseo_desc', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Try RankMath.
		$meta = get_term_meta( $term_id, 'rank_math_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Fallback to term description.
		$term = get_term( $term_id );
		return $term && ! is_wp_error( $term ) ? $term->description : '';
	}

	/**
	 * Extract headings from content
	 *
	 * @param string $content The content.
	 * @return array Headings found.
	 */
	private function extract_headings( $content ) {
		$headings = array();
		preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $content, $matches );

		if ( ! empty( $matches[2] ) ) {
			foreach ( $matches[2] as $index => $text ) {
				$headings[] = array(
					'level' => 'h' . $matches[1][ $index ],
					'text'  => wp_strip_all_tags( $text ),
				);
			}
		}

		return $headings;
	}

	/**
	 * Format the fix response from API result
	 *
	 * @param array  $result   API result.
	 * @param array  $issue    Original issue.
	 * @param string $fix_type Fix type.
	 * @return array Formatted fix response.
	 */
	private function format_fix_response( $result, $issue, $fix_type ) {
		$suggested_value = $this->extract_suggestion( $result );
		$explanation     = $this->extract_explanation( $result );

		// Apply length constraints if needed.
		$suggested_value = $this->apply_constraints( $suggested_value, $fix_type );

		// Determine the field to update.
		$field = $this->get_field_for_fix_type( $fix_type, $issue['item_type'] ?? '' );

		// Extract tokens used.
		$tokens_used = 100; // Default.
		if ( isset( $result['tokens_used']['total'] ) ) {
			$tokens_used = $result['tokens_used']['total'];
		} elseif ( isset( $result['tokens_used'] ) && is_numeric( $result['tokens_used'] ) ) {
			$tokens_used = $result['tokens_used'];
		}

		return array(
			'fix' => array(
				'field'           => $field,
				'current_value'   => $issue['current_value'] ?? '',
				'suggested_value' => $suggested_value,
				'explanation'     => $explanation,
			),
			'tokens_used' => $tokens_used,
			'new_balance' => $result['new_balance'] ?? null,
		);
	}

	/**
	 * Extract suggestion from API result
	 *
	 * Handles multiple response formats:
	 * - API format: result.suggested_value
	 * - Mock format: various field names
	 *
	 * @param array $result API result.
	 * @return string Suggestion text.
	 */
	private function extract_suggestion( $result ) {
		// API response format: result.suggested_value
		if ( isset( $result['result']['suggested_value'] ) ) {
			return trim( $result['result']['suggested_value'] );
		}

		// Direct suggested_value (mock format).
		if ( isset( $result['suggested_value'] ) ) {
			return trim( $result['suggested_value'] );
		}

		// Handle other response structures (backwards compatibility).
		if ( isset( $result['suggestion'] ) ) {
			return trim( $result['suggestion'] );
		}
		if ( isset( $result['content'] ) ) {
			return trim( $result['content'] );
		}
		if ( isset( $result['text'] ) ) {
			return trim( $result['text'] );
		}
		if ( isset( $result['generated'] ) ) {
			return trim( $result['generated'] );
		}

		return '';
	}

	/**
	 * Extract explanation from API result
	 *
	 * @param array $result API result.
	 * @return string Explanation text.
	 */
	private function extract_explanation( $result ) {
		// API response format: result.explanation
		if ( isset( $result['result']['explanation'] ) ) {
			return trim( $result['result']['explanation'] );
		}

		// Direct explanation (mock format).
		if ( isset( $result['explanation'] ) ) {
			return trim( $result['explanation'] );
		}

		return '';
	}

	/**
	 * Apply length/format constraints to suggestion
	 *
	 * @param string $value    The suggestion.
	 * @param string $fix_type Fix type.
	 * @return string Constrained value.
	 */
	private function apply_constraints( $value, $fix_type ) {
		switch ( $fix_type ) {
			case 'title':
				// Ensure title is within bounds.
				if ( strlen( $value ) > 60 ) {
					$value = substr( $value, 0, 57 ) . '...';
				}
				break;

			case 'meta_description':
				// Ensure meta description is within bounds.
				if ( strlen( $value ) > 160 ) {
					$value = substr( $value, 0, 157 ) . '...';
				}
				break;
		}

		return $value;
	}

	/**
	 * Get the field name for a fix type
	 *
	 * @param string $fix_type  Fix type.
	 * @param string $item_type Item type.
	 * @return string Field name.
	 */
	private function get_field_for_fix_type( $fix_type, $item_type ) {
		if ( 'content' === $fix_type ) {
			return 'product' === $item_type ? 'description' : 'content';
		}

		return $fix_type;
	}

	/**
	 * Apply a generated fix
	 *
	 * @param array $issue The original issue.
	 * @param array $fix   The fix to apply.
	 * @return true|WP_Error True on success or error.
	 */
	public function apply_fix( $issue, $fix ) {
		if ( empty( $fix['field'] ) || ! isset( $fix['suggested_value'] ) ) {
			return new WP_Error( 'invalid_fix', __( 'Invalid fix data.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		$item_type = $issue['item_type'] ?? '';
		$item_id   = $issue['item_id'] ?? 0;

		switch ( $item_type ) {
			case 'product':
				return $this->apply_product_fix( $item_id, $fix );
			case 'category':
				return $this->apply_category_fix( $item_id, $fix );
			case 'page':
			case 'post':
				return $this->apply_post_fix( $item_id, $fix );
			default:
				return new WP_Error( 'unsupported_item', __( 'Cannot apply fix to this item type.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Apply fix to a product
	 *
	 * @param int   $product_id Product ID.
	 * @param array $fix        Fix data.
	 * @return true|WP_Error
	 */
	private function apply_product_fix( $product_id, $fix ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'product_not_found', __( 'Product not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		switch ( $fix['field'] ) {
			case 'title':
				$product->set_name( $fix['suggested_value'] );
				$product->save();
				break;

			case 'meta_description':
				$this->save_meta_description( $product_id, $fix['suggested_value'] );
				break;

			case 'description':
				$product->set_description( $fix['suggested_value'] );
				$product->save();
				break;

			default:
				return new WP_Error( 'unsupported_field', __( 'Cannot update this field.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Clear SEO cache.
		delete_post_meta( $product_id, '_aisales_seo_score' );

		return true;
	}

	/**
	 * Apply fix to a category
	 *
	 * @param int   $term_id Term ID.
	 * @param array $fix     Fix data.
	 * @return true|WP_Error
	 */
	private function apply_category_fix( $term_id, $fix ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'category_not_found', __( 'Category not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		switch ( $fix['field'] ) {
			case 'title':
				wp_update_term( $term_id, 'product_cat', array(
					'name' => $fix['suggested_value'],
				) );
				break;

			case 'description':
			case 'meta_description':
				wp_update_term( $term_id, 'product_cat', array(
					'description' => $fix['suggested_value'],
				) );
				break;

			default:
				return new WP_Error( 'unsupported_field', __( 'Cannot update this field.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Clear SEO cache.
		delete_term_meta( $term_id, '_aisales_seo_score' );

		return true;
	}

	/**
	 * Apply fix to a post/page
	 *
	 * @param int   $post_id Post ID.
	 * @param array $fix     Fix data.
	 * @return true|WP_Error
	 */
	private function apply_post_fix( $post_id, $fix ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', __( 'Content not found.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		switch ( $fix['field'] ) {
			case 'title':
				wp_update_post( array(
					'ID'         => $post_id,
					'post_title' => $fix['suggested_value'],
				) );
				break;

			case 'meta_description':
				$this->save_meta_description( $post_id, $fix['suggested_value'] );
				break;

			case 'content':
				wp_update_post( array(
					'ID'           => $post_id,
					'post_content' => $fix['suggested_value'],
				) );
				break;

			default:
				return new WP_Error( 'unsupported_field', __( 'Cannot update this field.', 'stacksuite-sales-manager-for-woocommerce' ) );
		}

		// Clear SEO cache.
		delete_post_meta( $post_id, '_aisales_seo_score' );

		return true;
	}

	/**
	 * Save meta description to appropriate SEO plugin or fallback
	 *
	 * @param int    $post_id Post ID.
	 * @param string $value   Meta description value.
	 */
	private function save_meta_description( $post_id, $value ) {
		// Save to Yoast if available.
		if ( class_exists( 'WPSEO_Meta' ) ) {
			update_post_meta( $post_id, '_yoast_wpseo_metadesc', $value );
			return;
		}

		// Save to RankMath if available.
		if ( class_exists( 'RankMath' ) ) {
			update_post_meta( $post_id, 'rank_math_description', $value );
			return;
		}

		// Save to All in One SEO if available.
		if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
			update_post_meta( $post_id, '_aioseo_description', $value );
			return;
		}

		// Fallback: save as excerpt for posts, short description for products.
		$post = get_post( $post_id );
		if ( $post && 'product' === $post->post_type ) {
			$product = wc_get_product( $post_id );
			if ( $product ) {
				$product->set_short_description( $value );
				$product->save();
			}
		} else {
			wp_update_post( array(
				'ID'           => $post_id,
				'post_excerpt' => $value,
			) );
		}
	}
}
