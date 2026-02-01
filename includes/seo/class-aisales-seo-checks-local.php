<?php
/**
 * SEO Local Checks
 *
 * Performs free local SEO checks that don't require API calls.
 * Checks titles, meta descriptions, content length, images, etc.
 *
 * @package AISales_Sales_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * SEO Local Checks class
 */
class AISales_SEO_Checks_Local {

	/**
	 * Optimal title length range
	 */
	const TITLE_MIN_LENGTH = 30;
	const TITLE_MAX_LENGTH = 60;

	/**
	 * Optimal meta description length range
	 */
	const META_DESC_MIN_LENGTH = 120;
	const META_DESC_MAX_LENGTH = 160;

	/**
	 * Minimum content word count
	 */
	const MIN_CONTENT_WORDS = 100;

	/**
	 * Check a WooCommerce product for SEO issues
	 *
	 * @param WC_Product $product The product to check.
	 * @return array Array of issues found.
	 */
	public function check_product( $product ) {
		$issues = array();

		// 1. Title length check.
		$title = $product->get_name();
		if ( strlen( $title ) < self::TITLE_MIN_LENGTH ) {
			$issues[] = array(
				'check'         => 'title_length',
				'severity'      => 'warning',
				'title'         => __( 'Title too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current length, 2: minimum length */
					__( 'Title is %1$d characters. Optimal length is %2$d-%3$d characters.', 'stacksuite-sales-manager-for-woocommerce' ),
					strlen( $title ),
					self::TITLE_MIN_LENGTH,
					self::TITLE_MAX_LENGTH
				),
				'current_value' => $title,
			);
		} elseif ( strlen( $title ) > self::TITLE_MAX_LENGTH ) {
			$issues[] = array(
				'check'         => 'title_length',
				'severity'      => 'warning',
				'title'         => __( 'Title too long', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current length, 2: maximum length */
					__( 'Title is %1$d characters. May be truncated in search results (max %2$d).', 'stacksuite-sales-manager-for-woocommerce' ),
					strlen( $title ),
					self::TITLE_MAX_LENGTH
				),
				'current_value' => $title,
			);
		}

		// 2. Meta description check.
		$meta_desc = $this->get_product_meta_description( $product );
		if ( empty( $meta_desc ) ) {
			$issues[] = array(
				'check'         => 'meta_description_missing',
				'severity'      => 'critical',
				'title'         => __( 'Missing meta description', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'No meta description set. Search engines will auto-generate one.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		} elseif ( strlen( $meta_desc ) < self::META_DESC_MIN_LENGTH ) {
			$issues[] = array(
				'check'         => 'meta_description_length',
				'severity'      => 'warning',
				'title'         => __( 'Meta description too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current length, 2: minimum length */
					__( 'Meta description is %1$d characters. Optimal is %2$d-%3$d.', 'stacksuite-sales-manager-for-woocommerce' ),
					strlen( $meta_desc ),
					self::META_DESC_MIN_LENGTH,
					self::META_DESC_MAX_LENGTH
				),
				'current_value' => $meta_desc,
			);
		} elseif ( strlen( $meta_desc ) > self::META_DESC_MAX_LENGTH ) {
			$issues[] = array(
				'check'         => 'meta_description_length',
				'severity'      => 'warning',
				'title'         => __( 'Meta description too long', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current length, 2: maximum length */
					__( 'Meta description is %1$d characters. May be truncated (max %2$d).', 'stacksuite-sales-manager-for-woocommerce' ),
					strlen( $meta_desc ),
					self::META_DESC_MAX_LENGTH
				),
				'current_value' => $meta_desc,
			);
		}

		// 3. Image alt tags check.
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$alt_text = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( empty( $alt_text ) ) {
				$issues[] = array(
					'check'         => 'image_alt_missing',
					'severity'      => 'warning',
					'title'         => __( 'Missing image alt text', 'stacksuite-sales-manager-for-woocommerce' ),
					'description'   => __( 'Product image has no alt text. Important for accessibility and SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
					'current_value' => '',
				);
			}
		} else {
			$issues[] = array(
				'check'         => 'image_missing',
				'severity'      => 'critical',
				'title'         => __( 'No product image', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Product has no featured image. Images improve conversions and SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 4. Content length check.
		$description   = $product->get_description();
		$short_desc    = $product->get_short_description();
		$content       = $description . ' ' . $short_desc;
		$word_count    = str_word_count( wp_strip_all_tags( $content ) );

		if ( $word_count < self::MIN_CONTENT_WORDS ) {
			$issues[] = array(
				'check'         => 'content_thin',
				'severity'      => 'critical',
				'title'         => __( 'Thin content', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current word count, 2: minimum word count */
					__( 'Only %1$d words. Add more content (at least %2$d words) for better SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
					$word_count,
					self::MIN_CONTENT_WORDS
				),
				'current_value' => sprintf(
					/* translators: %d: word count */
					__( '%d words', 'stacksuite-sales-manager-for-woocommerce' ),
					$word_count
				),
			);
		}

		// 5. Heading structure check (if long description has content).
		if ( ! empty( $description ) && strlen( $description ) > 500 ) {
			if ( ! preg_match( '/<h[1-6][^>]*>/i', $description ) ) {
				$issues[] = array(
					'check'         => 'heading_structure',
					'severity'      => 'warning',
					'title'         => __( 'No headings in description', 'stacksuite-sales-manager-for-woocommerce' ),
					'description'   => __( 'Long descriptions should use headings (H2, H3) for better readability.', 'stacksuite-sales-manager-for-woocommerce' ),
					'current_value' => '',
				);
			}
		}

		// 6. Internal links check.
		$has_internal_links = preg_match( '/<a[^>]+href=["\'][^"\']*' . preg_quote( home_url(), '/' ) . '[^"\']*["\'][^>]*>/i', $description );
		if ( ! empty( $description ) && strlen( $description ) > 300 && ! $has_internal_links ) {
			$issues[] = array(
				'check'         => 'internal_links',
				'severity'      => 'warning',
				'title'         => __( 'No internal links', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Consider adding internal links to related products or categories.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 7. Price check.
		if ( empty( $product->get_price() ) && ! $product->is_type( 'variable' ) ) {
			$issues[] = array(
				'check'         => 'price_missing',
				'severity'      => 'warning',
				'title'         => __( 'No price set', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Products without prices may not appear in Google Shopping.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 8. SKU check.
		if ( empty( $product->get_sku() ) ) {
			$issues[] = array(
				'check'         => 'sku_missing',
				'severity'      => 'warning',
				'title'         => __( 'No SKU set', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'SKUs help with inventory management and can improve product structured data.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		return $issues;
	}

	/**
	 * Check a product category for SEO issues
	 *
	 * @param WP_Term $term The category term to check.
	 * @return array Array of issues found.
	 */
	public function check_category( $term ) {
		$issues = array();

		// 1. Title/name length check.
		if ( strlen( $term->name ) < 3 ) {
			$issues[] = array(
				'check'         => 'title_length',
				'severity'      => 'warning',
				'title'         => __( 'Category name too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Category names should be descriptive for better SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => $term->name,
			);
		}

		// 2. Description check.
		if ( empty( $term->description ) ) {
			$issues[] = array(
				'check'         => 'description_missing',
				'severity'      => 'critical',
				'title'         => __( 'No category description', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Add a description to help search engines understand this category.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		} elseif ( str_word_count( wp_strip_all_tags( $term->description ) ) < 30 ) {
			$issues[] = array(
				'check'         => 'description_short',
				'severity'      => 'warning',
				'title'         => __( 'Category description too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Add more content to the category description (at least 30 words).', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => $term->description,
			);
		}

		// 3. Thumbnail check.
		$thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( empty( $thumbnail_id ) ) {
			$issues[] = array(
				'check'         => 'thumbnail_missing',
				'severity'      => 'warning',
				'title'         => __( 'No category image', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Category images improve visual appeal and can appear in search results.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 4. Empty category check.
		if ( 0 === $term->count ) {
			$issues[] = array(
				'check'         => 'empty_category',
				'severity'      => 'warning',
				'title'         => __( 'Empty category', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'This category has no products. Consider adding products or removing it.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '0 products',
			);
		}

		// 5. Slug check.
		if ( preg_match( '/[0-9]+/', $term->slug ) && ! preg_match( '/[a-z]+/', $term->slug ) ) {
			$issues[] = array(
				'check'         => 'slug_numeric',
				'severity'      => 'warning',
				'title'         => __( 'Non-descriptive URL slug', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Use descriptive words in the URL slug instead of just numbers.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => $term->slug,
			);
		}

		return $issues;
	}

	/**
	 * Check a page for SEO issues
	 *
	 * @param WP_Post $page The page to check.
	 * @return array Array of issues found.
	 */
	public function check_page( $page ) {
		$issues = array();

		// 1. Title length check.
		$title = $page->post_title;
		if ( strlen( $title ) < self::TITLE_MIN_LENGTH ) {
			$issues[] = array(
				'check'         => 'title_length',
				'severity'      => 'warning',
				'title'         => __( 'Page title too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current length, 2: minimum length, 3: maximum length */
					__( 'Title is %1$d characters. Optimal is %2$d-%3$d.', 'stacksuite-sales-manager-for-woocommerce' ),
					strlen( $title ),
					self::TITLE_MIN_LENGTH,
					self::TITLE_MAX_LENGTH
				),
				'current_value' => $title,
			);
		}

		// 2. Meta description check.
		$meta_desc = $this->get_post_meta_description( $page->ID );
		if ( empty( $meta_desc ) ) {
			$issues[] = array(
				'check'         => 'meta_description_missing',
				'severity'      => 'critical',
				'title'         => __( 'Missing meta description', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Add a meta description for better search result snippets.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 3. Content length check.
		$content    = $page->post_content;
		$word_count = str_word_count( wp_strip_all_tags( $content ) );
		if ( $word_count < self::MIN_CONTENT_WORDS ) {
			$issues[] = array(
				'check'         => 'content_thin',
				'severity'      => 'critical',
				'title'         => __( 'Thin content', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => sprintf(
					/* translators: 1: current word count, 2: minimum word count */
					__( 'Only %1$d words. Add more content (at least %2$d words).', 'stacksuite-sales-manager-for-woocommerce' ),
					$word_count,
					self::MIN_CONTENT_WORDS
				),
				'current_value' => sprintf(
					/* translators: %d: word count */
					__( '%d words', 'stacksuite-sales-manager-for-woocommerce' ),
					$word_count
				),
			);
		}

		// 4. Featured image check.
		if ( ! has_post_thumbnail( $page->ID ) ) {
			$issues[] = array(
				'check'         => 'featured_image_missing',
				'severity'      => 'warning',
				'title'         => __( 'No featured image', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Featured images improve social sharing and visual appeal.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 5. H1 check.
		if ( ! preg_match( '/<h1[^>]*>/i', $content ) ) {
			// This is usually fine as the title becomes H1, but flag for review.
			// Actually, most themes handle this, so we'll skip this check.
		}

		// 6. Heading structure check.
		if ( strlen( $content ) > 1000 && ! preg_match( '/<h[2-6][^>]*>/i', $content ) ) {
			$issues[] = array(
				'check'         => 'heading_structure',
				'severity'      => 'warning',
				'title'         => __( 'No subheadings', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Long content should use subheadings (H2, H3) for better structure.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 7. Internal links check.
		$has_internal_links = preg_match( '/<a[^>]+href=["\'][^"\']*' . preg_quote( home_url(), '/' ) . '[^"\']*["\'][^>]*>/i', $content );
		if ( strlen( $content ) > 500 && ! $has_internal_links ) {
			$issues[] = array(
				'check'         => 'internal_links',
				'severity'      => 'warning',
				'title'         => __( 'No internal links', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Add internal links to improve site navigation and SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 8. Outbound links check.
		$has_outbound_links = preg_match( '/<a[^>]+href=["\']https?:\/\/(?!' . preg_quote( wp_parse_url( home_url(), PHP_URL_HOST ), '/' ) . ')[^"\']+["\'][^>]*>/i', $content );
		// Outbound links are good but not critical.

		// 9. Image alt tags check.
		preg_match_all( '/<img[^>]+>/i', $content, $images );
		if ( ! empty( $images[0] ) ) {
			$images_without_alt = 0;
			foreach ( $images[0] as $img ) {
				if ( ! preg_match( '/alt=["\'][^"\']+["\']/i', $img ) ) {
					++$images_without_alt;
				}
			}
			if ( $images_without_alt > 0 ) {
				$issues[] = array(
					'check'         => 'image_alt_missing',
					'severity'      => 'warning',
					'title'         => __( 'Images missing alt text', 'stacksuite-sales-manager-for-woocommerce' ),
					'description'   => sprintf(
						/* translators: %d: number of images */
						__( '%d images are missing alt text.', 'stacksuite-sales-manager-for-woocommerce' ),
						$images_without_alt
					),
					'current_value' => '',
				);
			}
		}

		return $issues;
	}

	/**
	 * Check a blog post for SEO issues
	 *
	 * @param WP_Post $post The post to check.
	 * @return array Array of issues found.
	 */
	public function check_post( $post ) {
		// Posts have similar checks to pages, plus some extras.
		$issues = $this->check_page( $post );

		// Additional blog post specific checks.

		// 1. Category check.
		$categories = get_the_category( $post->ID );
		if ( empty( $categories ) || ( 1 === count( $categories ) && 'Uncategorized' === $categories[0]->name ) ) {
			$issues[] = array(
				'check'         => 'category_missing',
				'severity'      => 'warning',
				'title'         => __( 'No category assigned', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Assign a relevant category to improve organization and SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => '',
			);
		}

		// 2. Tags check (optional, not critical).

		return $issues;
	}

	/**
	 * Check store settings for SEO issues
	 *
	 * @return array Array of issues found.
	 */
	public function check_store_settings() {
		$issues = array();

		// 1. Permalink structure check.
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) || '/?p=%post_id%' === $permalink_structure ) {
			$issues[] = array(
				'check'       => 'permalinks',
				'severity'    => 'critical',
				'title'       => __( 'Plain permalinks', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Using plain permalinks. Switch to "Post name" for better SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		// 2. Search engine visibility check.
		if ( '1' === get_option( 'blog_public' ) ) {
			// Good - visible to search engines.
		} else {
			$issues[] = array(
				'check'       => 'search_visibility',
				'severity'    => 'critical',
				'title'       => __( 'Search engines blocked', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Your site is set to discourage search engines. Uncheck this in Settings > Reading.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		// 3. SSL/HTTPS check.
		if ( ! is_ssl() ) {
			$issues[] = array(
				'check'       => 'ssl',
				'severity'    => 'critical',
				'title'       => __( 'Not using HTTPS', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Your site is not using HTTPS. This affects SEO rankings and security.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		// 4. XML Sitemap check (basic check).
		$sitemap_url = home_url( '/sitemap.xml' );
		$response    = wp_remote_head( $sitemap_url, array( 'timeout' => 5 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Also check for sitemap_index.xml (Yoast style).
			$sitemap_index_url = home_url( '/sitemap_index.xml' );
			$response2         = wp_remote_head( $sitemap_index_url, array( 'timeout' => 5 ) );
			if ( is_wp_error( $response2 ) || 200 !== wp_remote_retrieve_response_code( $response2 ) ) {
				$issues[] = array(
					'check'       => 'sitemap',
					'severity'    => 'warning',
					'title'       => __( 'No XML sitemap found', 'stacksuite-sales-manager-for-woocommerce' ),
					'description' => __( 'Consider adding an XML sitemap to help search engines index your site.', 'stacksuite-sales-manager-for-woocommerce' ),
				);
			}
		}

		// 5. Robots.txt check.
		$robots_url = home_url( '/robots.txt' );
		$response   = wp_remote_head( $robots_url, array( 'timeout' => 5 ) );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$issues[] = array(
				'check'       => 'robots_txt',
				'severity'    => 'warning',
				'title'       => __( 'No robots.txt found', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Add a robots.txt file to guide search engine crawlers.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		// 6. Site title and tagline check.
		$site_title   = get_bloginfo( 'name' );
		$site_tagline = get_bloginfo( 'description' );
		if ( empty( $site_title ) ) {
			$issues[] = array(
				'check'       => 'site_title',
				'severity'    => 'critical',
				'title'       => __( 'No site title', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Set a site title in Settings > General for better branding and SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}
		if ( empty( $site_tagline ) || 'Just another WordPress site' === $site_tagline ) {
			$issues[] = array(
				'check'       => 'site_tagline',
				'severity'    => 'warning',
				'title'       => __( 'Default or missing tagline', 'stacksuite-sales-manager-for-woocommerce' ),
				'description' => __( 'Set a custom tagline in Settings > General to describe your site.', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		return $issues;
	}

	/**
	 * Check homepage for SEO issues
	 *
	 * @return array Array of issues found.
	 */
	public function check_homepage() {
		$issues = array();

		// Get homepage ID.
		$front_page_id = get_option( 'page_on_front' );
		$show_on_front = get_option( 'show_on_front' );

		// 1. Site title check.
		$site_title = get_bloginfo( 'name' );
		if ( strlen( $site_title ) < 5 ) {
			$issues[] = array(
				'check'         => 'homepage_title',
				'severity'      => 'warning',
				'title'         => __( 'Site title too short', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Your site title should be descriptive for better SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => $site_title,
			);
		}

		// 2. Meta description check.
		$meta_desc = get_bloginfo( 'description' );
		if ( 'page' === $show_on_front && $front_page_id ) {
			$page_meta = $this->get_post_meta_description( $front_page_id );
			if ( ! empty( $page_meta ) ) {
				$meta_desc = $page_meta;
			}
		}

		if ( empty( $meta_desc ) || 'Just another WordPress site' === $meta_desc ) {
			$issues[] = array(
				'check'         => 'homepage_meta_description',
				'severity'      => 'critical',
				'title'         => __( 'Missing or default homepage meta description', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Set a unique meta description for your homepage.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => $meta_desc,
			);
		}

		// 3. Static page vs posts check.
		if ( 'posts' === $show_on_front ) {
			$issues[] = array(
				'check'         => 'homepage_static',
				'severity'      => 'warning',
				'title'         => __( 'Homepage shows latest posts', 'stacksuite-sales-manager-for-woocommerce' ),
				'description'   => __( 'Consider using a static homepage for better control over SEO and content.', 'stacksuite-sales-manager-for-woocommerce' ),
				'current_value' => __( 'Latest posts', 'stacksuite-sales-manager-for-woocommerce' ),
			);
		}

		// 4. Homepage content check (if static page).
		if ( 'page' === $show_on_front && $front_page_id ) {
			$page = get_post( $front_page_id );
			if ( $page ) {
				$word_count = str_word_count( wp_strip_all_tags( $page->post_content ) );
				if ( $word_count < 150 ) {
					$issues[] = array(
						'check'         => 'homepage_content',
						'severity'      => 'warning',
						'title'         => __( 'Thin homepage content', 'stacksuite-sales-manager-for-woocommerce' ),
						'description'   => __( 'Homepage has limited text content. Add more to improve SEO.', 'stacksuite-sales-manager-for-woocommerce' ),
						'current_value' => sprintf(
							/* translators: %d: word count */
							__( '%d words', 'stacksuite-sales-manager-for-woocommerce' ),
							$word_count
						),
					);
				}
			}
		}

		// 5. Heading check (basic - just check if H1 exists anywhere).
		// Most themes handle this automatically via site title.

		return $issues;
	}

	/**
	 * Get meta description for a product (checks Yoast, RankMath, AIOSEO)
	 *
	 * @param WC_Product $product The product.
	 * @return string Meta description.
	 */
	private function get_product_meta_description( $product ) {
		$post_id = $product->get_id();

		// Check Yoast SEO.
		$meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Check RankMath.
		$meta = get_post_meta( $post_id, 'rank_math_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Check All in One SEO.
		$meta = get_post_meta( $post_id, '_aioseo_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Fallback to short description.
		$short_desc = $product->get_short_description();
		if ( ! empty( $short_desc ) ) {
			return wp_trim_words( wp_strip_all_tags( $short_desc ), 25, '...' );
		}

		return '';
	}

	/**
	 * Get meta description for a post/page (checks Yoast, RankMath, AIOSEO)
	 *
	 * @param int $post_id The post ID.
	 * @return string Meta description.
	 */
	private function get_post_meta_description( $post_id ) {
		// Check Yoast SEO.
		$meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Check RankMath.
		$meta = get_post_meta( $post_id, 'rank_math_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Check All in One SEO.
		$meta = get_post_meta( $post_id, '_aioseo_description', true );
		if ( ! empty( $meta ) ) {
			return $meta;
		}

		// Fallback to excerpt.
		$post = get_post( $post_id );
		if ( $post && ! empty( $post->post_excerpt ) ) {
			return wp_trim_words( wp_strip_all_tags( $post->post_excerpt ), 25, '...' );
		}

		return '';
	}
}
