<?php
/**
 * Catalog Analysis and Actions Tool
 *
 * Handles catalog structure analysis and modification actions
 * for the AI Agent Mode tool executor.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tool Catalog class - Catalog analysis and actions
 */
class AISales_Tool_Catalog {

	/**
	 * Analyze catalog structure
	 *
	 * @param array $params Parameters.
	 * @return array Catalog analysis result.
	 */
	public function analyze_structure( $params ) {
		$include_products   = isset( $params['include_products'] ) ? (bool) $params['include_products'] : true;
		$include_categories = isset( $params['include_categories'] ) ? (bool) $params['include_categories'] : true;
		$focus_area         = isset( $params['focus_area'] ) ? sanitize_key( $params['focus_area'] ) : 'all';

		$result = array(
			'summary'                     => $this->get_catalog_summary(),
			'structure_issues'            => array(),
			'category_balance'            => array(),
			'seo_opportunities'           => array(),
			'conversion_recommendations'  => array(),
			'recommended_depth'           => 3,
			'depth_reasoning'             => '',
		);

		if ( $include_categories || in_array( $focus_area, array( 'structure', 'all' ), true ) ) {
			$result['structure_issues'] = $this->detect_structure_issues();
			$result['category_balance'] = $this->calculate_category_balance();
		}

		if ( $include_products && in_array( $focus_area, array( 'seo', 'all' ), true ) ) {
			$result['seo_opportunities'] = $this->find_seo_opportunities();
		}

		if ( in_array( $focus_area, array( 'conversion', 'all' ), true ) ) {
			$result['conversion_recommendations'] = $this->get_conversion_recommendations();
		}

		// Determine recommended depth based on catalog size
		$result = $this->calculate_recommended_depth( $result );

		return $result;
	}

	/**
	 * Apply a catalog change action
	 *
	 * @param array $params Parameters with action details.
	 * @return array|WP_Error Result of the operation.
	 */
	public function apply_change( $params ) {
		$action_type = isset( $params['action_type'] ) ? sanitize_key( $params['action_type'] ) : '';

		if ( empty( $action_type ) ) {
			return new WP_Error( 'missing_param', __( 'action_type is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		switch ( $action_type ) {
			case 'move_product':
				return $this->action_move_product( $params );

			case 'move_category':
				return $this->action_move_category( $params );

			case 'create_category':
				return $this->action_create_category( $params );

			case 'merge_categories':
				return $this->action_merge_categories( $params );

			case 'delete_category':
				return $this->action_delete_category( $params );

			default:
				return new WP_Error( 'invalid_action', __( 'Unknown action type.', 'ai-sales-manager-for-woocommerce' ) );
		}
	}

	/**
	 * Get catalog summary statistics
	 *
	 * @return array Catalog summary.
	 */
	private function get_catalog_summary() {
		global $wpdb;

		// Get total published products
		$total_products = wp_count_posts( 'product' )->publish;

		// Get all categories
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			$categories = array();
		}

		$total_categories  = count( $categories );
		$empty_categories  = 0;
		$max_depth         = 0;

		foreach ( $categories as $cat ) {
			if ( 0 === $cat->count ) {
				$empty_categories++;
			}
			$depth     = $this->get_category_depth( $cat->term_id );
			$max_depth = max( $max_depth, $depth );
		}

		// Count uncategorized products
		$uncategorized       = get_term_by( 'slug', 'uncategorized', 'product_cat' );
		$uncategorized_count = $uncategorized ? $uncategorized->count : 0;

		// Count products in multiple categories
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$multi_cat_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT tr.object_id
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				WHERE tt.taxonomy = 'product_cat'
				AND p.post_type = 'product'
				AND p.post_status = 'publish'
				GROUP BY tr.object_id
				HAVING COUNT(*) > 1
			) as multi_cat"
		);

		$avg_products = $total_categories > 0 ? round( $total_products / $total_categories, 1 ) : 0;

		return array(
			'total_products'           => (int) $total_products,
			'total_categories'         => $total_categories,
			'avg_products_per_category' => $avg_products,
			'max_category_depth'       => $max_depth,
			'uncategorized_products'   => (int) $uncategorized_count,
			'empty_categories'         => $empty_categories,
			'multi_category_products'  => (int) $multi_cat_count,
		);
	}

	/**
	 * Get depth level of a category
	 *
	 * @param int $term_id Category term ID.
	 * @return int Depth level (0 = root).
	 */
	private function get_category_depth( $term_id ) {
		$depth  = 0;
		$parent = get_term_field( 'parent', $term_id, 'product_cat' );

		while ( $parent && $parent > 0 && ! is_wp_error( $parent ) ) {
			$depth++;
			$parent = get_term_field( 'parent', $parent, 'product_cat' );
		}

		return $depth;
	}

	/**
	 * Detect structure issues in the catalog
	 *
	 * @return array Structure issues found.
	 */
	private function detect_structure_issues() {
		$issues     = array();
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			return $issues;
		}

		foreach ( $categories as $cat ) {
			$depth = $this->get_category_depth( $cat->term_id );

			// Check for deep nesting (>4 levels is problematic)
			if ( $depth > 4 ) {
				$issues[] = array(
					'type'           => 'too_deep',
					'severity'       => 'high',
					'description'    => sprintf( 'Category "%s" is %d levels deep, which can hurt navigation and SEO.', $cat->name, $depth ),
					'affected_items' => array(
						array(
							'id'   => (string) $cat->term_id,
							'name' => $cat->name,
							'type' => 'category',
						),
					),
					'suggested_fix'  => 'Move to a shallower position (max 3 levels recommended) or restructure parent categories.',
				);
			}

			// Check for single-child parent categories
			$children = get_term_children( $cat->term_id, 'product_cat' );
			if ( ! is_wp_error( $children ) && 1 === count( $children ) && 0 === $cat->count ) {
				$child_term = get_term( $children[0], 'product_cat' );
				$issues[]   = array(
					'type'           => 'confusing_hierarchy',
					'severity'       => 'medium',
					'description'    => sprintf( 'Category "%s" has only one subcategory ("%s") and no direct products.', $cat->name, $child_term ? $child_term->name : 'Unknown' ),
					'affected_items' => array(
						array(
							'id'   => (string) $cat->term_id,
							'name' => $cat->name,
							'type' => 'category',
						),
					),
					'suggested_fix'  => 'Consider merging with the single subcategory to simplify navigation.',
				);
			}

			// Check for empty categories (not orphan)
			if ( 0 === $cat->count && empty( get_term_children( $cat->term_id, 'product_cat' ) ) ) {
				$issues[] = array(
					'type'           => 'orphan_category',
					'severity'       => 'low',
					'description'    => sprintf( 'Category "%s" has no products and no subcategories.', $cat->name ),
					'affected_items' => array(
						array(
							'id'   => (string) $cat->term_id,
							'name' => $cat->name,
							'type' => 'category',
						),
					),
					'suggested_fix'  => 'Add products to this category or delete it if no longer needed.',
				);
			}
		}

		// Check for very large categories (>50 products without subcategories)
		foreach ( $categories as $cat ) {
			$children = get_term_children( $cat->term_id, 'product_cat' );
			if ( $cat->count > 50 && ( is_wp_error( $children ) || empty( $children ) ) ) {
				$issues[] = array(
					'type'           => 'imbalanced',
					'severity'       => 'medium',
					'description'    => sprintf( 'Category "%s" has %d products but no subcategories.', $cat->name, $cat->count ),
					'affected_items' => array(
						array(
							'id'   => (string) $cat->term_id,
							'name' => $cat->name,
							'type' => 'category',
						),
					),
					'suggested_fix'  => 'Consider creating subcategories to improve browsability and user experience.',
				);
			}
		}

		// Sort by severity
		usort(
			$issues,
			function ( $a, $b ) {
				$severity_order = array(
					'high'   => 0,
					'medium' => 1,
					'low'    => 2,
				);
				return $severity_order[ $a['severity'] ] - $severity_order[ $b['severity'] ];
			}
		);

		return array_slice( $issues, 0, 15 ); // Limit to top 15 issues
	}

	/**
	 * Calculate balance scores for categories
	 *
	 * @return array Category balance data.
	 */
	private function calculate_category_balance() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) ) {
			return array();
		}

		// Calculate average products per category
		$counts  = wp_list_pluck( $categories, 'count' );
		$avg_products = count( $counts ) > 0 ? array_sum( $counts ) / count( $counts ) : 0;

		$balances = array();

		foreach ( $categories as $cat ) {
			$depth    = $this->get_category_depth( $cat->term_id );
			$children = get_term_children( $cat->term_id, 'product_cat' );
			$subcats  = is_wp_error( $children ) ? 0 : count( $children );

			// Calculate balance score (0-100)
			$issues = array();
			$score  = 100;

			// Penalize empty categories
			if ( 0 === $cat->count && 0 === $subcats ) {
				$score  -= 40;
				$issues[] = 'Empty category with no subcategories';
			} elseif ( 0 === $cat->count ) {
				$score  -= 15;
				$issues[] = 'No direct products (container category only)';
			}

			// Penalize oversized categories (>3x average)
			if ( $avg_products > 0 && $cat->count > $avg_products * 3 ) {
				$score  -= 25;
				$issues[] = 'Too many products - consider subcategories';
			}

			// Penalize undersized categories (<5 products, not containers)
			if ( $cat->count > 0 && $cat->count < 5 && 0 === $subcats ) {
				$score  -= 10;
				$issues[] = 'Very few products - consider merging';
			}

			// Penalize deep categories
			if ( $depth > 3 ) {
				$score  -= 20;
				$issues[] = 'Deep nesting hurts navigation';
			} elseif ( $depth > 2 ) {
				$score  -= 5;
				$issues[] = 'Consider flattening structure';
			}

			$balances[] = array(
				'category_id'       => (string) $cat->term_id,
				'category_name'     => $cat->name,
				'product_count'     => $cat->count,
				'depth_level'       => $depth,
				'subcategory_count' => $subcats,
				'balance_score'     => max( 0, $score ),
				'issues'            => $issues,
			);
		}

		// Sort by score (lowest first = most issues)
		usort(
			$balances,
			function ( $a, $b ) {
				return $a['balance_score'] - $b['balance_score'];
			}
		);

		return array_slice( $balances, 0, 20 ); // Return top 20
	}

	/**
	 * Find SEO opportunities in products and categories
	 *
	 * @return array SEO opportunities.
	 */
	private function find_seo_opportunities() {
		$opportunities = array();

		// Check products
		$products = wc_get_products(
			array(
				'limit'  => 100,
				'status' => 'publish',
			)
		);

		foreach ( $products as $product ) {
			$desc       = $product->get_description();
			$short_desc = $product->get_short_description();

			// Missing description entirely
			if ( empty( $desc ) && empty( $short_desc ) ) {
				$opportunities[] = array(
					'type'        => 'missing_description',
					'entity_type' => 'product',
					'entity_id'   => (string) $product->get_id(),
					'entity_name' => $product->get_name(),
					'suggestion'  => 'Add product description to improve SEO and help customers make decisions.',
				);
			} elseif ( strlen( $desc ) < 100 && strlen( $short_desc ) < 50 ) {
				// Very short descriptions
				$opportunities[] = array(
					'type'          => 'short_description',
					'entity_type'   => 'product',
					'entity_id'     => (string) $product->get_id(),
					'entity_name'   => $product->get_name(),
					'current_value' => $desc ?: $short_desc,
					'suggestion'    => 'Expand descriptions (aim for 150+ characters) for better SEO ranking.',
				);
			}

			// Missing product image
			if ( ! $product->get_image_id() ) {
				$opportunities[] = array(
					'type'        => 'missing_image',
					'entity_type' => 'product',
					'entity_id'   => (string) $product->get_id(),
					'entity_name' => $product->get_name(),
					'suggestion'  => 'Add product image - products with images have significantly higher conversion rates.',
				);
			}
		}

		// Check categories
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( empty( $cat->description ) ) {
					$opportunities[] = array(
						'type'        => 'missing_description',
						'entity_type' => 'category',
						'entity_id'   => (string) $cat->term_id,
						'entity_name' => $cat->name,
						'suggestion'  => 'Add category description for SEO and to help customers understand what products to expect.',
					);
				} elseif ( strlen( $cat->description ) < 50 ) {
					$opportunities[] = array(
						'type'          => 'short_description',
						'entity_type'   => 'category',
						'entity_id'     => (string) $cat->term_id,
						'entity_name'   => $cat->name,
						'current_value' => $cat->description,
						'suggestion'    => 'Expand category description for better SEO (aim for 100+ characters).',
					);
				}

				// Check for category thumbnail
				$thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
				if ( ! $thumbnail_id && $cat->count > 0 ) {
					$opportunities[] = array(
						'type'        => 'missing_image',
						'entity_type' => 'category',
						'entity_id'   => (string) $cat->term_id,
						'entity_name' => $cat->name,
						'suggestion'  => 'Add category thumbnail to make category pages more visually appealing.',
					);
				}
			}
		}

		return array_slice( $opportunities, 0, 30 ); // Limit to 30
	}

	/**
	 * Get conversion optimization recommendations
	 *
	 * @return array Conversion recommendations.
	 */
	private function get_conversion_recommendations() {
		$recommendations = array();
		$categories      = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);

		if ( is_wp_error( $categories ) ) {
			return $recommendations;
		}

		// Find potential featured categories (top performers)
		$top_categories = array_slice( $categories, 0, 3 );
		if ( count( $top_categories ) > 0 ) {
			$affected = array();
			foreach ( $top_categories as $cat ) {
				$affected[] = array(
					'id'   => (string) $cat->term_id,
					'name' => $cat->name,
					'type' => 'category',
				);
			}

			$recommendations[] = array(
				'type'            => 'featured_category',
				'priority'        => 'high',
				'description'     => 'Consider featuring your top categories prominently on the homepage.',
				'expected_impact' => 'Increased visibility can drive 15-25% more category page visits.',
				'affected_items'  => $affected,
			);
		}

		// Find categories at similar price points for bundle opportunities
		$products = wc_get_products(
			array(
				'limit'   => 50,
				'status'  => 'publish',
				'orderby' => 'popularity',
				'order'   => 'DESC',
			)
		);

		if ( count( $products ) >= 3 ) {
			$price_groups = array();
			foreach ( $products as $product ) {
				$price = (float) $product->get_price();
				if ( $price > 0 ) {
					$tier = $price < 25 ? 'low' : ( $price < 100 ? 'medium' : 'high' );
					if ( ! isset( $price_groups[ $tier ] ) ) {
						$price_groups[ $tier ] = array();
					}
					$price_groups[ $tier ][] = $product;
				}
			}

			foreach ( $price_groups as $tier => $tier_products ) {
				if ( count( $tier_products ) >= 3 ) {
					$sample    = array_slice( $tier_products, 0, 3 );
					$affected  = array();
					foreach ( $sample as $p ) {
						$affected[] = array(
							'id'   => (string) $p->get_id(),
							'name' => $p->get_name(),
							'type' => 'product',
						);
					}

					$recommendations[] = array(
						'type'            => 'bundle_opportunity',
						'priority'        => 'medium',
						'description'     => sprintf( 'Create product bundles from %s-priced items that are frequently browsed together.', $tier ),
						'expected_impact' => 'Product bundles typically increase average order value by 10-30%.',
						'affected_items'  => $affected,
					);
					break; // Just suggest one bundle opportunity
				}
			}
		}

		// Navigation improvement for deep/complex structures
		$deep_categories = array();
		foreach ( $categories as $cat ) {
			$depth = $this->get_category_depth( $cat->term_id );
			if ( $depth >= 3 ) {
				$deep_categories[] = $cat;
			}
		}

		if ( count( $deep_categories ) > 3 ) {
			$affected = array();
			foreach ( array_slice( $deep_categories, 0, 3 ) as $cat ) {
				$affected[] = array(
					'id'   => (string) $cat->term_id,
					'name' => $cat->name,
					'type' => 'category',
				);
			}

			$recommendations[] = array(
				'type'            => 'navigation_improvement',
				'priority'        => 'medium',
				'description'     => 'Your category structure has multiple deep levels. Consider adding breadcrumbs or a mega menu.',
				'expected_impact' => 'Better navigation reduces bounce rates and helps customers find products faster.',
				'affected_items'  => $affected,
			);
		}

		return $recommendations;
	}

	/**
	 * Calculate recommended category depth based on catalog analysis
	 *
	 * @param array $result Current analysis result.
	 * @return array Updated result with depth recommendation.
	 */
	private function calculate_recommended_depth( $result ) {
		$summary           = $result['summary'];
		$total_products    = $summary['total_products'];

		// Base recommendation on catalog size and complexity
		if ( $total_products < 50 ) {
			$recommended = 2;
			$reasoning   = 'With fewer than 50 products, a flat structure with 2 levels (main categories and optional subcategories) keeps navigation simple.';
		} elseif ( $total_products < 200 ) {
			$recommended = 3;
			$reasoning   = 'For 50-200 products, 3 levels provide good organization without overwhelming customers.';
		} elseif ( $total_products < 1000 ) {
			$recommended = 3;
			$reasoning   = 'For 200-1000 products, maintain 3 levels but consider more subcategories. Deeper than 3 levels can hurt discoverability.';
		} else {
			$recommended = 4;
			$reasoning   = 'For large catalogs (1000+ products), 4 levels may be necessary, but ensure each level adds meaningful organization.';
		}

		// Adjust based on current structure issues
		if ( $summary['max_category_depth'] > $recommended + 1 ) {
			$reasoning .= sprintf( ' Your current max depth is %d - consider flattening.', $summary['max_category_depth'] );
		}

		$result['recommended_depth'] = $recommended;
		$result['depth_reasoning']   = $reasoning;

		return $result;
	}

	// =========================================================================
	// CATALOG ACTION METHODS
	// =========================================================================

	/**
	 * Move a product to a different category
	 *
	 * @param array $params Parameters.
	 * @return array|WP_Error Result.
	 */
	private function action_move_product( $params ) {
		$product_id       = isset( $params['product_id'] ) ? absint( $params['product_id'] ) : 0;
		$to_category_id   = isset( $params['to_category_id'] ) ? absint( $params['to_category_id'] ) : 0;
		$from_category_id = isset( $params['from_category_id'] ) ? absint( $params['from_category_id'] ) : 0;

		if ( ! $product_id || ! $to_category_id ) {
			return new WP_Error( 'missing_param', __( 'product_id and to_category_id are required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'not_found', __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Get current categories
		$current_cat_ids = $product->get_category_ids();

		// Remove from old category if specified
		if ( $from_category_id && in_array( $from_category_id, $current_cat_ids, true ) ) {
			$current_cat_ids = array_diff( $current_cat_ids, array( $from_category_id ) );
		}

		// Add to new category
		if ( ! in_array( $to_category_id, $current_cat_ids, true ) ) {
			$current_cat_ids[] = $to_category_id;
		}

		$product->set_category_ids( $current_cat_ids );
		$product->save();

		return array(
			'success' => true,
			'message' => 'Product moved to category successfully.',
		);
	}

	/**
	 * Move a category to a new parent
	 *
	 * @param array $params Parameters.
	 * @return array|WP_Error Result.
	 */
	private function action_move_category( $params ) {
		$category_id   = isset( $params['category_id'] ) ? absint( $params['category_id'] ) : 0;
		$new_parent_id = isset( $params['new_parent_id'] ) ? absint( $params['new_parent_id'] ) : 0;

		if ( ! $category_id ) {
			return new WP_Error( 'missing_param', __( 'category_id is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = wp_update_term(
			$category_id,
			'product_cat',
			array( 'parent' => $new_parent_id )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => 'Category moved successfully.',
		);
	}

	/**
	 * Create a new category
	 *
	 * @param array $params Parameters.
	 * @return array|WP_Error Result.
	 */
	private function action_create_category( $params ) {
		$name        = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$parent_id   = isset( $params['parent_id'] ) ? absint( $params['parent_id'] ) : 0;
		$description = isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';

		if ( empty( $name ) ) {
			return new WP_Error( 'missing_param', __( 'Category name is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = wp_insert_term(
			$name,
			'product_cat',
			array(
				'parent'      => $parent_id,
				'description' => $description,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term = get_term( $result['term_id'], 'product_cat' );

		return array(
			'success'  => true,
			'message'  => sprintf( 'Category "%s" created successfully.', $name ),
			'category' => array(
				'id'          => (string) $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_id'   => $term->parent ? (string) $term->parent : null,
				'count'       => $term->count,
			),
		);
	}

	/**
	 * Merge two categories
	 *
	 * @param array $params Parameters.
	 * @return array|WP_Error Result.
	 */
	private function action_merge_categories( $params ) {
		$source_id = isset( $params['source_category_id'] ) ? absint( $params['source_category_id'] ) : 0;
		$target_id = isset( $params['target_category_id'] ) ? absint( $params['target_category_id'] ) : 0;

		if ( ! $source_id || ! $target_id ) {
			return new WP_Error( 'missing_param', __( 'source_category_id and target_category_id are required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$source_term = get_term( $source_id, 'product_cat' );
		$target_term = get_term( $target_id, 'product_cat' );

		if ( ! $source_term || is_wp_error( $source_term ) ) {
			return new WP_Error( 'not_found', __( 'Source category not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		if ( ! $target_term || is_wp_error( $target_term ) ) {
			return new WP_Error( 'not_found', __( 'Target category not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Move all products from source to target
		$products = wc_get_products(
			array(
				'category' => array( $source_id ),
				'limit'    => -1,
			)
		);

		$moved_count = 0;
		foreach ( $products as $product ) {
			$cat_ids = $product->get_category_ids();
			$cat_ids = array_diff( $cat_ids, array( $source_id ) );
			if ( ! in_array( $target_id, $cat_ids, true ) ) {
				$cat_ids[] = $target_id;
			}
			$product->set_category_ids( $cat_ids );
			$product->save();
			$moved_count++;
		}

		// Move subcategories to target
		$children = get_term_children( $source_id, 'product_cat' );
		if ( ! is_wp_error( $children ) ) {
			foreach ( $children as $child_id ) {
				$child = get_term( $child_id, 'product_cat' );
				if ( $child && ! is_wp_error( $child ) && (int) $child->parent === $source_id ) {
					wp_update_term( $child_id, 'product_cat', array( 'parent' => $target_id ) );
				}
			}
		}

		// Delete the source category
		wp_delete_term( $source_id, 'product_cat' );

		return array(
			'success' => true,
			'message' => sprintf(
				'Merged "%s" into "%s". Moved %d products.',
				$source_term->name,
				$target_term->name,
				$moved_count
			),
		);
	}

	/**
	 * Delete an empty category
	 *
	 * @param array $params Parameters.
	 * @return array|WP_Error Result.
	 */
	private function action_delete_category( $params ) {
		$category_id = isset( $params['category_id'] ) ? absint( $params['category_id'] ) : 0;
		$force       = isset( $params['force'] ) && $params['force'];

		if ( ! $category_id ) {
			return new WP_Error( 'missing_param', __( 'category_id is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$term = get_term( $category_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'not_found', __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Safety check - don't delete categories with products unless forced
		if ( $term->count > 0 && ! $force ) {
			return new WP_Error(
				'has_products',
				sprintf(
					/* translators: %1$s: category name, %2$d: product count */
					__( 'Category "%1$s" has %2$d products. Use force=true to delete anyway.', 'ai-sales-manager-for-woocommerce' ),
					$term->name,
					$term->count
				)
			);
		}

		$name   = $term->name;
		$result = wp_delete_term( $category_id, 'product_cat' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'message' => sprintf( 'Category "%s" deleted successfully.', $name ),
		);
	}
}
