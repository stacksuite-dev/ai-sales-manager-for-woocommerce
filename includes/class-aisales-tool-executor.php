<?php
/**
 * Tool Executor for AI Agent Mode
 *
 * Executes tool calls requested by the AI to fetch store data.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tool Executor class
 */
class AISales_Tool_Executor {

	/**
	 * Available tools
	 *
	 * @var array
	 */
	private $available_tools = array(
		'get_products',
		'get_categories',
		'get_products_by_category',
		'get_page_content',
	);

	/**
	 * Execute a tool with given parameters
	 *
	 * @param string $tool   Tool name.
	 * @param array  $params Tool parameters.
	 * @return array|WP_Error Result data or error.
	 */
	public function execute( $tool, $params = array() ) {
		if ( ! in_array( $tool, $this->available_tools, true ) ) {
			return new WP_Error( 'invalid_tool', __( 'Unknown tool.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$method = 'execute_' . $tool;

		if ( ! method_exists( $this, $method ) ) {
			return new WP_Error( 'not_implemented', __( 'Tool not implemented.', 'ai-sales-manager-for-woocommerce' ) );
		}

		return $this->$method( $params );
	}

	/**
	 * Execute get_products tool
	 *
	 * @param array $params Parameters.
	 * @return array Products data.
	 */
	private function execute_get_products( $params ) {
		$limit        = isset( $params['limit'] ) ? min( absint( $params['limit'] ), 100 ) : 50;
		$offset       = isset( $params['offset'] ) ? absint( $params['offset'] ) : 0;
		$search       = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$category_id  = isset( $params['category_id'] ) ? sanitize_text_field( $params['category_id'] ) : '';
		$stock_status = isset( $params['stock_status'] ) ? sanitize_key( $params['stock_status'] ) : '';
		$orderby      = isset( $params['orderby'] ) ? sanitize_key( $params['orderby'] ) : 'date';
		$order        = isset( $params['order'] ) ? strtoupper( sanitize_key( $params['order'] ) ) : 'DESC';

		// Build query args
		$args = array(
			'status'  => 'publish',
			'limit'   => $limit,
			'offset'  => $offset,
			'orderby' => $this->map_orderby( $orderby ),
			'order'   => in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC',
		);

		// Search
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Category filter
		if ( ! empty( $category_id ) ) {
			$args['category'] = array( $category_id );
		}

		// Stock status filter
		if ( ! empty( $stock_status ) ) {
			$args['stock_status'] = $stock_status;
		}

		// Get products
		$products = wc_get_products( $args );

		// Get total count for pagination
		$count_args          = $args;
		$count_args['limit'] = -1;
		unset( $count_args['offset'] );
		$total = count( wc_get_products( $count_args ) );

		$result = array(
			'products' => array(),
			'total'    => $total,
			'has_more' => ( $offset + count( $products ) ) < $total,
		);

		foreach ( $products as $product ) {
			$result['products'][] = $this->format_product_summary( $product );
		}

		return $result;
	}

	/**
	 * Execute get_categories tool
	 *
	 * @param array $params Parameters.
	 * @return array Categories data.
	 */
	private function execute_get_categories( $params ) {
		$include_empty = isset( $params['include_empty'] ) && $params['include_empty'];
		$parent_id     = isset( $params['parent_id'] ) ? absint( $params['parent_id'] ) : null;

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => ! $include_empty,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( null !== $parent_id ) {
			$args['parent'] = $parent_id;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		// Build tree structure
		return $this->build_category_tree( $terms, $parent_id );
	}

	/**
	 * Execute get_products_by_category tool
	 *
	 * @param array $params Parameters.
	 * @return array Category and products data.
	 */
	private function execute_get_products_by_category( $params ) {
		$category_id          = isset( $params['category_id'] ) ? sanitize_text_field( $params['category_id'] ) : '';
		$include_subcategories = isset( $params['include_subcategories'] ) && $params['include_subcategories'];
		$limit                = isset( $params['limit'] ) ? min( absint( $params['limit'] ), 100 ) : 50;

		if ( empty( $category_id ) ) {
			return new WP_Error( 'missing_param', __( 'category_id is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		// Get category data
		$term = get_term( $category_id, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'not_found', __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$category_data = $this->format_category_data( $term );

		// Get products
		$category_ids = array( $category_id );

		if ( $include_subcategories ) {
			$children = get_term_children( $category_id, 'product_cat' );
			if ( ! is_wp_error( $children ) ) {
				$category_ids = array_merge( $category_ids, $children );
			}
		}

		$args = array(
			'status'   => 'publish',
			'limit'    => $limit,
			'category' => $category_ids,
			'orderby'  => 'date',
			'order'    => 'DESC',
		);

		$products = wc_get_products( $args );

		// Get total count
		$count_args          = $args;
		$count_args['limit'] = -1;
		$total               = count( wc_get_products( $count_args ) );

		$products_data = array(
			'products' => array(),
			'total'    => $total,
			'has_more' => count( $products ) < $total,
		);

		foreach ( $products as $product ) {
			$products_data['products'][] = $this->format_product_summary( $product );
		}

		return array(
			'category' => $category_data,
			'products' => $products_data,
		);
	}

	/**
	 * Execute get_page_content tool
	 *
	 * @param array $params Parameters.
	 * @return array Page content data.
	 */
	private function execute_get_page_content( $params ) {
		$page_type = isset( $params['page_type'] ) ? sanitize_key( $params['page_type'] ) : '';
		$page_id   = isset( $params['page_id'] ) ? sanitize_text_field( $params['page_id'] ) : '';
		$slug      = isset( $params['slug'] ) ? sanitize_title( $params['slug'] ) : '';

		if ( empty( $page_type ) ) {
			return new WP_Error( 'missing_param', __( 'page_type is required.', 'ai-sales-manager-for-woocommerce' ) );
		}

		$result = array(
			'title'            => '',
			'meta_description' => '',
			'meta_keywords'    => '',
			'text_content'     => '',
			'url'              => '',
		);

		switch ( $page_type ) {
			case 'homepage':
				$page_id = get_option( 'page_on_front' );
				if ( $page_id ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$result = $this->extract_page_content( $page );
					}
				} else {
					$result['title']        = get_bloginfo( 'name' );
					$result['text_content'] = get_bloginfo( 'description' );
					$result['url']          = home_url( '/' );
				}
				break;

			case 'shop':
				$page_id = wc_get_page_id( 'shop' );
				if ( $page_id > 0 ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$result = $this->extract_page_content( $page );
					}
				}
				break;

			case 'cart':
				$page_id = wc_get_page_id( 'cart' );
				if ( $page_id > 0 ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$result = $this->extract_page_content( $page );
					}
				}
				break;

			case 'checkout':
				$page_id = wc_get_page_id( 'checkout' );
				if ( $page_id > 0 ) {
					$page = get_post( $page_id );
					if ( $page ) {
						$result = $this->extract_page_content( $page );
					}
				}
				break;

			case 'page':
				if ( ! empty( $page_id ) ) {
					$page = get_post( absint( $page_id ) );
				} elseif ( ! empty( $slug ) ) {
					$page = get_page_by_path( $slug );
				}

				if ( isset( $page ) && $page ) {
					$result = $this->extract_page_content( $page );
				} else {
					return new WP_Error( 'not_found', __( 'Page not found.', 'ai-sales-manager-for-woocommerce' ) );
				}
				break;

			case 'product':
				if ( ! empty( $page_id ) ) {
					$product = wc_get_product( absint( $page_id ) );
				} elseif ( ! empty( $slug ) ) {
					$product = get_page_by_path( $slug, OBJECT, 'product' );
					if ( $product ) {
						$product = wc_get_product( $product->ID );
					}
				}

				if ( isset( $product ) && $product ) {
					$result = $this->extract_product_content( $product );
				} else {
					return new WP_Error( 'not_found', __( 'Product not found.', 'ai-sales-manager-for-woocommerce' ) );
				}
				break;

			case 'category':
				if ( ! empty( $page_id ) ) {
					$term = get_term( absint( $page_id ), 'product_cat' );
				} elseif ( ! empty( $slug ) ) {
					$term = get_term_by( 'slug', $slug, 'product_cat' );
				}

				if ( isset( $term ) && $term && ! is_wp_error( $term ) ) {
					$result = $this->extract_category_content( $term );
				} else {
					return new WP_Error( 'not_found', __( 'Category not found.', 'ai-sales-manager-for-woocommerce' ) );
				}
				break;

			default:
				return new WP_Error( 'invalid_type', __( 'Invalid page type.', 'ai-sales-manager-for-woocommerce' ) );
		}

		return $result;
	}

	/**
	 * Map orderby parameter to WC query format
	 *
	 * @param string $orderby Orderby parameter.
	 * @return string WC orderby value.
	 */
	private function map_orderby( $orderby ) {
		$map = array(
			'date'  => 'date',
			'price' => 'price',
			'title' => 'title',
			'sales' => 'popularity',
		);

		return isset( $map[ $orderby ] ) ? $map[ $orderby ] : 'date';
	}

	/**
	 * Format product data for summary view
	 *
	 * @param WC_Product $product Product object.
	 * @return array Formatted product data.
	 */
	private function format_product_summary( $product ) {
		$categories = array();
		$terms      = get_the_terms( $product->get_id(), 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[] = $term->name;
			}
		}

		$thumbnail_url = '';
		$thumbnail_id  = $product->get_image_id();
		if ( $thumbnail_id ) {
			$thumbnail_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
		}

		return array(
			'id'            => (string) $product->get_id(),
			'title'         => $product->get_name(),
			'price'         => $product->get_price(),
			'regular_price' => $product->get_regular_price(),
			'sale_price'    => $product->get_sale_price(),
			'stock_status'  => $product->get_stock_status(),
			'stock_quantity' => $product->get_stock_quantity(),
			'categories'    => $categories,
			'thumbnail_url' => $thumbnail_url,
			'sku'           => $product->get_sku(),
			'type'          => $product->get_type(),
		);
	}

	/**
	 * Format category data
	 *
	 * @param WP_Term $term Term object.
	 * @return array Formatted category data.
	 */
	private function format_category_data( $term ) {
		$parent_name = '';
		if ( $term->parent ) {
			$parent_term = get_term( $term->parent, 'product_cat' );
			if ( $parent_term && ! is_wp_error( $parent_term ) ) {
				$parent_name = $parent_term->name;
			}
		}

		// Get subcategory count
		$subcategories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => false,
			'fields'     => 'count',
		) );

		$subcategory_count = is_wp_error( $subcategories ) ? 0 : $subcategories;

		// Get thumbnail
		$thumbnail_id  = get_term_meta( $term->term_id, 'thumbnail_id', true );
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '';

		return array(
			'id'                => (string) $term->term_id,
			'name'              => $term->name,
			'slug'              => $term->slug,
			'description'       => $term->description,
			'parent_id'         => $term->parent,
			'parent_name'       => $parent_name,
			'product_count'     => $term->count,
			'subcategory_count' => $subcategory_count,
			'thumbnail_url'     => $thumbnail_url,
		);
	}

	/**
	 * Build category tree structure
	 *
	 * @param array    $terms     Term objects.
	 * @param int|null $parent_id Starting parent ID.
	 * @return array Category tree.
	 */
	private function build_category_tree( $terms, $parent_id = null ) {
		$tree = array();

		// Index terms by ID for quick lookup
		$terms_by_id = array();
		foreach ( $terms as $term ) {
			$terms_by_id[ $term->term_id ] = $term;
		}

		// Build tree
		foreach ( $terms as $term ) {
			// If parent_id is specified, only include top-level items that match
			if ( null !== $parent_id ) {
				if ( (int) $term->parent !== (int) $parent_id ) {
					continue;
				}
			} else {
				// For full tree, start with root categories
				if ( $term->parent !== 0 ) {
					continue;
				}
			}

			$node = array(
				'id'            => (string) $term->term_id,
				'name'          => $term->name,
				'slug'          => $term->slug,
				'product_count' => $term->count,
				'children'      => $this->get_category_children( $term->term_id, $terms_by_id ),
			);

			$tree[] = $node;
		}

		return $tree;
	}

	/**
	 * Get children for a category
	 *
	 * @param int   $parent_id    Parent term ID.
	 * @param array $terms_by_id  Terms indexed by ID.
	 * @return array Children nodes.
	 */
	private function get_category_children( $parent_id, $terms_by_id ) {
		$children = array();

		foreach ( $terms_by_id as $term ) {
			if ( (int) $term->parent === (int) $parent_id ) {
				$children[] = array(
					'id'            => (string) $term->term_id,
					'name'          => $term->name,
					'slug'          => $term->slug,
					'product_count' => $term->count,
					'children'      => $this->get_category_children( $term->term_id, $terms_by_id ),
				);
			}
		}

		return $children;
	}

	/**
	 * Extract content from a page/post
	 *
	 * @param WP_Post $post Post object.
	 * @return array Extracted content.
	 */
	private function extract_page_content( $post ) {
		// Get SEO meta
		$meta_description = '';
		$meta_keywords    = '';

		// Try Yoast
		$yoast_desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
		if ( $yoast_desc ) {
			$meta_description = $yoast_desc;
		}

		// Try RankMath
		if ( empty( $meta_description ) ) {
			$rankmath_desc = get_post_meta( $post->ID, 'rank_math_description', true );
			if ( $rankmath_desc ) {
				$meta_description = $rankmath_desc;
			}
		}

		// Extract text content (strip shortcodes and HTML)
		$content = $post->post_content;
		$content = do_shortcode( $content );
		$content = wp_strip_all_tags( $content );
		$content = trim( preg_replace( '/\s+/', ' ', $content ) );

		// Limit content length
		if ( strlen( $content ) > 5000 ) {
			$content = substr( $content, 0, 5000 ) . '...';
		}

		return array(
			'title'            => $post->post_title,
			'meta_description' => $meta_description,
			'meta_keywords'    => $meta_keywords,
			'text_content'     => $content,
			'url'              => get_permalink( $post->ID ),
		);
	}

	/**
	 * Extract content from a product
	 *
	 * @param WC_Product $product Product object.
	 * @return array Extracted content.
	 */
	private function extract_product_content( $product ) {
		$post_id = $product->get_id();

		// Get SEO meta
		$meta_description = '';

		// Try Yoast
		$yoast_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( $yoast_desc ) {
			$meta_description = $yoast_desc;
		}

		// Try RankMath
		if ( empty( $meta_description ) ) {
			$rankmath_desc = get_post_meta( $post_id, 'rank_math_description', true );
			if ( $rankmath_desc ) {
				$meta_description = $rankmath_desc;
			}
		}

		// Fallback to short description
		if ( empty( $meta_description ) ) {
			$meta_description = $product->get_short_description();
		}

		// Build full text content
		$content_parts = array();

		if ( $product->get_name() ) {
			$content_parts[] = 'Product: ' . $product->get_name();
		}

		if ( $product->get_short_description() ) {
			$content_parts[] = 'Short Description: ' . wp_strip_all_tags( $product->get_short_description() );
		}

		if ( $product->get_description() ) {
			$content_parts[] = 'Description: ' . wp_strip_all_tags( $product->get_description() );
		}

		if ( $product->get_price() ) {
			$content_parts[] = 'Price: ' . wc_price( $product->get_price() );
		}

		$content = implode( "\n\n", $content_parts );

		// Limit content length
		if ( strlen( $content ) > 5000 ) {
			$content = substr( $content, 0, 5000 ) . '...';
		}

		return array(
			'title'            => $product->get_name(),
			'meta_description' => wp_strip_all_tags( $meta_description ),
			'meta_keywords'    => '',
			'text_content'     => $content,
			'url'              => $product->get_permalink(),
		);
	}

	/**
	 * Extract content from a category
	 *
	 * @param WP_Term $term Term object.
	 * @return array Extracted content.
	 */
	private function extract_category_content( $term ) {
		// Get SEO meta
		$meta_description = '';

		// Try Yoast
		$yoast_desc = get_term_meta( $term->term_id, '_yoast_wpseo_metadesc', true );
		if ( $yoast_desc ) {
			$meta_description = $yoast_desc;
		}

		// Try RankMath
		if ( empty( $meta_description ) ) {
			$rankmath_desc = get_term_meta( $term->term_id, 'rank_math_description', true );
			if ( $rankmath_desc ) {
				$meta_description = $rankmath_desc;
			}
		}

		// Fallback to description
		if ( empty( $meta_description ) ) {
			$meta_description = $term->description;
		}

		// Build content
		$content = sprintf(
			"Category: %s\n\nDescription: %s\n\nProduct Count: %d",
			$term->name,
			$term->description ?: 'No description',
			$term->count
		);

		return array(
			'title'            => $term->name,
			'meta_description' => wp_strip_all_tags( $meta_description ),
			'meta_keywords'    => '',
			'text_content'     => $content,
			'url'              => get_term_link( $term ),
		);
	}
}
