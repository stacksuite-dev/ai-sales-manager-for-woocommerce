<?php
/**
 * Mock API Data Provider
 *
 * Provides mock data for development and testing when AISALES_MOCK_MODE is enabled.
 * This allows the plugin to function without connecting to the real API.
 *
 * @package AISales_Sales_Manager
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * API Mock class - provides mock responses for development/testing
 */
class AISales_API_Mock {

	/**
	 * Mock connect response (domain-based auth)
	 * New accounts receive 10,000 welcome bonus tokens
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array
	 */
	public static function connect( $email, $domain ) {
		$api_key       = get_option( 'aisales_api_key' );
		$is_new        = false;
		$welcome_bonus = null;

		if ( ! $api_key ) {
			$api_key       = 'wai_mock_' . wp_generate_password( 32, false );
			$is_new        = true;
			$welcome_bonus = 10000;
			update_option( 'aisales_balance', 10000 );
		}

		update_option( 'aisales_api_key', $api_key );
		update_option( 'aisales_user_email', $email );
		update_option( 'aisales_domain', $domain );

		$balance = get_option( 'aisales_balance', 10000 );

		$response = array(
			'message'        => $is_new ? 'Account created successfully' : 'Connected successfully',
			'user_id'        => 1,
			'email'          => $email,
			'domain'         => $domain,
			'api_key'        => $api_key,
			'balance_tokens' => $balance,
			'is_new'         => $is_new,
		);

		if ( $welcome_bonus ) {
			$response['welcome_bonus'] = $welcome_bonus;
		}

		return $response;
	}

	/**
	 * Mock get account response
	 *
	 * @return array
	 */
	public static function get_account() {
		return array(
			'user_id'        => 1,
			'email'          => get_option( 'aisales_user_email', 'demo@example.com' ),
			'balance_tokens' => get_option( 'aisales_balance', 7432 ),
		);
	}

	/**
	 * Mock get usage response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public static function get_usage( $limit, $offset ) {
		$mock_logs = array(
			array(
				'id'            => 1,
				'operation'     => 'content',
				'input_tokens'  => 150,
				'output_tokens' => 95,
				'total_tokens'  => 245,
				'product_id'    => '123',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			),
			array(
				'id'            => 2,
				'operation'     => 'image_generate',
				'input_tokens'  => 200,
				'output_tokens' => 1003,
				'total_tokens'  => 1203,
				'product_id'    => '456',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
			),
			array(
				'id'            => 3,
				'operation'     => 'taxonomy',
				'input_tokens'  => 50,
				'output_tokens' => 39,
				'total_tokens'  => 89,
				'product_id'    => '789',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			),
			array(
				'id'            => 4,
				'operation'     => 'content',
				'input_tokens'  => 180,
				'output_tokens' => 120,
				'total_tokens'  => 300,
				'product_id'    => '101',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
			),
			array(
				'id'            => 5,
				'operation'     => 'image_improve',
				'input_tokens'  => 500,
				'output_tokens' => 450,
				'total_tokens'  => 950,
				'product_id'    => '102',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			),
		);

		return array(
			'logs'       => array_slice( $mock_logs, $offset, $limit ),
			'pagination' => array(
				'total'    => count( $mock_logs ),
				'limit'    => $limit,
				'offset'   => $offset,
				'has_more' => ( $offset + $limit ) < count( $mock_logs ),
			),
		);
	}

	/**
	 * Mock get transactions response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public static function get_transactions( $limit, $offset ) {
		$mock_transactions = array(
			array(
				'id'            => 1,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 10000,
				'description'   => 'Token top-up',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
			),
			array(
				'id'            => 2,
				'type'          => 'usage',
				'amount_tokens' => -245,
				'amount_usd'    => null,
				'balance_after' => 9755,
				'description'   => 'Content generation',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ),
			),
			array(
				'id'            => 3,
				'type'          => 'usage',
				'amount_tokens' => -1203,
				'amount_usd'    => null,
				'balance_after' => 8552,
				'description'   => 'Image generation',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
			),
		);

		return array(
			'transactions' => array_slice( $mock_transactions, $offset, $limit ),
			'pagination'   => array(
				'total'    => count( $mock_transactions ),
				'limit'    => $limit,
				'offset'   => $offset,
				'has_more' => ( $offset + $limit ) < count( $mock_transactions ),
			),
		);
	}

	/**
	 * Mock create checkout response
	 *
	 * @param string $plan Plan identifier.
	 * @return array
	 */
	public static function create_checkout( $plan = '10k' ) {
		$plan_tokens = array(
			'starter_plan'  => 5000,
			'standard_plan' => 10000,
			'pro_plan'      => 25000,
			'business_plan' => 50000,
			'5k'            => 5000,
			'10k'           => 10000,
			'25k'           => 25000,
			'50k'           => 50000,
		);

		$tokens = isset( $plan_tokens[ $plan ] ) ? $plan_tokens[ $plan ] : 10000;

		$current_balance = get_option( 'aisales_balance', 0 );
		update_option( 'aisales_balance', $current_balance + $tokens );

		return array(
			'checkout_url' => admin_url( 'admin.php?page=ai-sales-manager&topup=success' ),
			'session_id'   => 'mock_session_' . time(),
		);
	}

	/**
	 * Mock generate content response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	public static function generate_content( $product_data ) {
		$title = isset( $product_data['product_title'] ) ? $product_data['product_title'] : 'Product';

		self::deduct_tokens( 245 );

		return array(
			'result'      => array(
				'title'             => $title . ' - Premium Quality',
				'description'       => "Discover the exceptional quality of our {$title}. Crafted with meticulous attention to detail, this product combines style with functionality. Perfect for those who appreciate the finer things in life.\n\nKey Features:\n• Premium materials for lasting durability\n• Modern design that complements any setting\n• Carefully crafted for optimal performance\n• Backed by our quality guarantee",
				'short_description' => "Premium {$title} crafted with exceptional quality and modern design. Perfect for discerning customers.",
				'meta_description'  => "Shop our premium {$title}. High-quality materials, modern design, and exceptional value. Free shipping available.",
			),
			'tokens_used' => array(
				'input'  => 150,
				'output' => 95,
				'total'  => 245,
			),
		);
	}

	/**
	 * Mock suggest taxonomy response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	public static function suggest_taxonomy( $product_data ) {
		self::deduct_tokens( 89 );

		return array(
			'suggested_categories' => array(
				'Clothing',
				'Men\'s Fashion',
				'Casual Wear',
			),
			'suggested_tags'       => array(
				'premium',
				'comfortable',
				'stylish',
				'everyday',
				'bestseller',
			),
			'suggested_attributes' => array(
				array(
					'name'   => 'Material',
					'values' => array( 'Cotton', 'Polyester Blend' ),
				),
				array(
					'name'   => 'Fit',
					'values' => array( 'Regular', 'Slim', 'Relaxed' ),
				),
			),
			'tokens_used'          => array(
				'input'  => 50,
				'output' => 39,
				'total'  => 89,
			),
		);
	}

	/**
	 * Mock generate image response
	 *
	 * @param array $product_data Product data.
	 * @return array
	 */
	public static function generate_image( $product_data ) {
		self::deduct_tokens( 1203 );

		return array(
			'image_url'   => self::get_placeholder_image_url(),
			'tokens_used' => array(
				'input'  => 200,
				'output' => 1003,
				'total'  => 1203,
			),
		);
	}

	/**
	 * Mock improve image response
	 *
	 * @param array $image_data Image data.
	 * @return array
	 */
	public static function improve_image( $image_data ) {
		self::deduct_tokens( 950 );

		return array(
			'image_url'   => self::get_placeholder_image_url(),
			'tokens_used' => array(
				'input'  => 500,
				'output' => 450,
				'total'  => 950,
			),
		);
	}

	/**
	 * Get placeholder image URL for mock responses
	 * Uses WooCommerce placeholder if available, otherwise a data URI placeholder
	 *
	 * @return string
	 */
	private static function get_placeholder_image_url() {
		// Use WooCommerce placeholder image if available.
		if ( function_exists( 'wc_placeholder_img_src' ) ) {
			return wc_placeholder_img_src( 'woocommerce_single' );
		}

		// Fallback to inline SVG data URI (no external file needed).
		// Simple gray placeholder with "Mock Image" text.
		$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">';
		$svg .= '<rect fill="#f0f0f0" width="800" height="800"/>';
		$svg .= '<text x="50%" y="50%" font-family="Arial,sans-serif" font-size="32" ';
		$svg .= 'fill="#999" text-anchor="middle" dy=".3em">Mock Image</text>';
		$svg .= '</svg>';

		// Encode SVG as base64 data URI for inline placeholder image.
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Mock generate category content response
	 *
	 * @param array $category_data Category data.
	 * @return array
	 */
	public static function generate_category_content( $category_data ) {
		$name = isset( $category_data['name'] ) ? $category_data['name'] : 'Category';

		self::deduct_tokens( 200 );

		return array(
			'result'      => array(
				'description' => "Discover our exceptional {$name} collection. Carefully curated to bring you the finest products in this category.",
			),
			'tokens_used' => array(
				'input'  => 100,
				'output' => 100,
				'total'  => 200,
			),
		);
	}

	/**
	 * Mock generate SEO content response
	 *
	 * Supports both old format (type, context) and new API format (fix_type, item, issue).
	 *
	 * @param array $data SEO generation data.
	 * @return array Response matching real API format.
	 */
	public static function generate_seo_content( $data ) {
		// Handle new API format.
		if ( isset( $data['fix_type'] ) && isset( $data['item'] ) ) {
			return self::generate_seo_content_v2( $data );
		}

		// Legacy format support.
		$type    = isset( $data['type'] ) ? $data['type'] : 'seo_content';
		$context = isset( $data['context'] ) ? $data['context'] : array();
		$title   = isset( $context['title'] ) ? $context['title'] : 'Content';

		self::deduct_tokens( 100 );

		// Generate appropriate content based on type.
		switch ( $type ) {
			case 'seo_title':
				$content = self::generate_mock_title( $title, $context );
				break;

			case 'seo_meta_description':
				$content = self::generate_mock_meta_description( $title, $context );
				break;

			case 'seo_content':
				$content = self::generate_mock_enhanced_content( $title, $context );
				break;

			case 'seo_keyword':
				$content = self::generate_mock_keyword( $title, $context );
				break;

			default:
				$content = "Optimized content for {$title}";
		}

		return array(
			'content'     => $content,
			'tokens_used' => array(
				'input'  => 50,
				'output' => 50,
				'total'  => 100,
			),
		);
	}

	/**
	 * Generate SEO content v2 (new API format)
	 *
	 * @param array $data Request data with fix_type, item, issue, store_context, requirements.
	 * @return array Response matching real API format.
	 */
	private static function generate_seo_content_v2( $data ) {
		$fix_type = $data['fix_type'];
		$item     = $data['item'];
		$title    = isset( $item['title'] ) ? $item['title'] : 'Content';
		$type     = isset( $item['type'] ) ? $item['type'] : 'content';

		// Map item types for consistency.
		if ( 'product_cat' === $type ) {
			$type = 'category';
		}

		$context = array( 'type' => $type );

		self::deduct_tokens( 100 );

		// Generate appropriate content based on fix type.
		switch ( $fix_type ) {
			case 'title':
				$suggested_value = self::generate_mock_title( $title, $context );
				$explanation     = 'This title is optimized for SEO with a compelling hook and proper length (30-60 characters) for search result display.';
				break;

			case 'meta_description':
				$suggested_value = self::generate_mock_meta_description( $title, $context );
				$explanation     = 'This meta description includes a clear value proposition and call-to-action within the optimal 120-160 character range.';
				break;

			case 'content':
				$suggested_value = self::generate_mock_enhanced_content( $title, $context );
				$explanation     = 'This enhanced content includes proper heading structure, relevant keywords, and informative paragraphs that improve SEO and user engagement.';
				break;

			case 'keyword':
				$suggested_value = self::generate_mock_keyword( $title, $context );
				$explanation     = 'This focus keyword is relevant to the content, has good search potential, and can be naturally incorporated into the title and description.';
				break;

			default:
				$suggested_value = "Optimized content for {$title}";
				$explanation     = 'Generated content to address the SEO issue.';
		}

		// Return response in API format.
		$balance_info = self::get_balance();
		$current_balance = isset( $balance_info['balance'] ) ? $balance_info['balance'] : 5000;

		return array(
			'result'      => array(
				'suggested_value' => $suggested_value,
				'explanation'     => $explanation,
			),
			'tokens_used' => array(
				'input'  => 50,
				'output' => 50,
				'total'  => 100,
			),
			'new_balance' => $current_balance - 100,
		);
	}

	/**
	 * Generate mock SEO title
	 *
	 * @param string $title   Original title.
	 * @param array  $context Context data.
	 * @return string
	 */
	private static function generate_mock_title( $title, $context ) {
		$type = isset( $context['type'] ) ? $context['type'] : 'content';

		$prefixes = array(
			'product'  => array( 'Premium', 'Quality', 'Best-Selling', 'Top-Rated' ),
			'category' => array( 'Shop', 'Explore', 'Discover', 'Browse' ),
			'page'     => array( 'Learn About', 'Discover', 'Your Guide to', '' ),
			'post'     => array( 'How to', 'Guide:', 'Tips for', 'Understanding' ),
		);

		$suffixes = array(
			'product'  => array( '- Free Shipping', '| Shop Now', '- Best Price', '' ),
			'category' => array( 'Collection', 'Products', '| Shop Now', '' ),
			'page'     => array( '', '| Official', '', '' ),
			'post'     => array( '', '- Complete Guide', '| Tips', '' ),
		);

		$prefix_list = isset( $prefixes[ $type ] ) ? $prefixes[ $type ] : array( '' );
		$suffix_list = isset( $suffixes[ $type ] ) ? $suffixes[ $type ] : array( '' );

		$prefix = $prefix_list[ array_rand( $prefix_list ) ];
		$suffix = $suffix_list[ array_rand( $suffix_list ) ];

		$new_title = trim( "{$prefix} {$title} {$suffix}" );

		// Ensure it's within 30-60 characters.
		if ( strlen( $new_title ) > 60 ) {
			$new_title = substr( $new_title, 0, 57 ) . '...';
		}

		return $new_title;
	}

	/**
	 * Generate mock meta description
	 *
	 * @param string $title   Original title.
	 * @param array  $context Context data.
	 * @return string
	 */
	private static function generate_mock_meta_description( $title, $context ) {
		$type = isset( $context['type'] ) ? $context['type'] : 'content';

		$templates = array(
			'product'  => "Discover {$title} - premium quality at great prices. Shop now for fast shipping and excellent customer service. Limited time offer!",
			'category' => "Explore our {$title} collection. Find top-quality products at competitive prices. Free shipping on orders over \$50. Shop now!",
			'page'     => "Learn everything about {$title}. Comprehensive information and resources to help you make informed decisions. Read more.",
			'post'     => "Discover insights about {$title}. Expert tips, guides, and advice to help you succeed. Click to read the full article.",
		);

		$desc = isset( $templates[ $type ] ) ? $templates[ $type ] : "Discover more about {$title}. Quality content and resources for you.";

		// Ensure it's within 120-160 characters.
		if ( strlen( $desc ) > 160 ) {
			$desc = substr( $desc, 0, 157 ) . '...';
		}

		return $desc;
	}

	/**
	 * Generate mock enhanced content
	 *
	 * @param string $title   Original title.
	 * @param array  $context Context data.
	 * @return string
	 */
	private static function generate_mock_enhanced_content( $title, $context ) {
		$type = isset( $context['type'] ) ? $context['type'] : 'content';

		if ( 'product' === $type ) {
			return "Introducing our exceptional {$title} - a premium choice for discerning customers.\n\n" .
				"This carefully crafted product combines quality materials with expert craftsmanship to deliver outstanding performance. " .
				"Whether you're looking for reliability, style, or value, this product exceeds expectations.\n\n" .
				"Key Features:\n" .
				"• Premium quality construction\n" .
				"• Modern, versatile design\n" .
				"• Exceptional durability\n" .
				"• Backed by our satisfaction guarantee\n\n" .
				"Order today and experience the difference quality makes!";
		}

		return "Welcome to our comprehensive guide about {$title}.\n\n" .
			"This content has been carefully created to provide you with valuable information and insights. " .
			"We've compiled expert knowledge and practical advice to help you understand this topic better.\n\n" .
			"Whether you're a beginner or looking to expand your knowledge, you'll find useful information here. " .
			"Explore the sections below to learn more about what makes this topic important and how it can benefit you.";
	}

	/**
	 * Generate mock focus keyword
	 *
	 * @param string $title   Original title.
	 * @param array  $context Context data.
	 * @return string
	 */
	private static function generate_mock_keyword( $title, $context ) {
		// Extract key words from title.
		$words = preg_split( '/\s+/', strtolower( $title ) );
		$words = array_filter( $words, function( $word ) {
			return strlen( $word ) > 3 && ! in_array( $word, array( 'the', 'and', 'for', 'with', 'that', 'this', 'from' ), true );
		} );

		if ( count( $words ) >= 2 ) {
			return implode( ' ', array_slice( array_values( $words ), 0, 2 ) );
		}

		return $title;
	}

	/**
	 * Mock suggest subcategories response
	 *
	 * @param array $category_data Category data.
	 * @return array
	 */
	public static function suggest_subcategories( $category_data ) {
		self::deduct_tokens( 150 );

		return array(
			'subcategories' => array(
				'Featured Items',
				'New Arrivals',
				'Best Sellers',
				'Sale Items',
			),
			'tokens_used'   => array(
				'input'  => 75,
				'output' => 75,
				'total'  => 150,
			),
		);
	}

	/**
	 * Mock generate email template response
	 *
	 * @param array $data Template data.
	 * @return array
	 */
	public static function generate_email_template( $data ) {
		self::deduct_tokens( 300 );

		return array(
			'result' => array(
				'subject' => 'Your order is on its way!',
				'body'    => '<p>Thank you for your order!</p>',
			),
			'tokens_used' => array(
				'input'  => 150,
				'output' => 150,
				'total'  => 300,
			),
		);
	}

	/**
	 * Mock analyze brand response
	 *
	 * @param array $context Store analysis context.
	 * @return array
	 */
	public static function analyze_brand( $context ) {
		self::deduct_tokens( 500 );

		$store_name = isset( $context['store_name'] ) ? $context['store_name'] : get_bloginfo( 'name' );
		$products   = isset( $context['products'] ) ? $context['products'] : array();

		$industry = 'general';
		if ( ! empty( $products ) ) {
			$product_text = strtolower( implode( ' ', wp_list_pluck( $products, 'name' ) ) );
			if ( strpos( $product_text, 'shirt' ) !== false || strpos( $product_text, 'dress' ) !== false ) {
				$industry = 'fashion';
			} elseif ( strpos( $product_text, 'phone' ) !== false || strpos( $product_text, 'laptop' ) !== false ) {
				$industry = 'electronics';
			} elseif ( strpos( $product_text, 'cream' ) !== false || strpos( $product_text, 'beauty' ) !== false ) {
				$industry = 'beauty';
			} elseif ( strpos( $product_text, 'food' ) !== false || strpos( $product_text, 'organic' ) !== false ) {
				$industry = 'food';
			} elseif ( strpos( $product_text, 'furniture' ) !== false || strpos( $product_text, 'decor' ) !== false ) {
				$industry = 'home';
			}
		}

		$audiences = array(
			'fashion'     => 'Style-conscious individuals aged 25-45 who value quality and contemporary fashion trends.',
			'electronics' => 'Tech-savvy consumers aged 20-50 looking for reliable electronics and innovative gadgets.',
			'beauty'      => 'Health and beauty enthusiasts aged 25-55 who prioritize self-care and premium skincare products.',
			'food'        => 'Health-conscious foodies aged 25-60 who appreciate quality ingredients and artisanal products.',
			'home'        => 'Homeowners and design enthusiasts aged 30-55 looking to create comfortable, stylish living spaces.',
			'general'     => 'Quality-focused shoppers aged 25-55 who value reliable products and excellent customer service.',
		);

		$taglines = array(
			'fashion'     => 'Elevate Your Style, Define Your Look',
			'electronics' => 'Innovation at Your Fingertips',
			'beauty'      => 'Discover Your Natural Radiance',
			'food'        => 'Fresh Quality, Delivered to You',
			'home'        => 'Where Comfort Meets Style',
			'general'     => 'Quality Products, Exceptional Service',
		);

		$primary_color = isset( $context['theme_colors']['primary'] ) ? $context['theme_colors']['primary'] : '#7f54b3';
		$text_color    = isset( $context['theme_colors']['text'] ) ? $context['theme_colors']['text'] : '#3c3c3c';
		$bg_color      = isset( $context['theme_colors']['background'] ) ? $context['theme_colors']['background'] : '#f7f7f7';

		return array(
			'suggestions' => array(
				'store_name'      => $store_name,
				'tagline'         => $taglines[ $industry ],
				'business_niche'  => $industry,
				'target_audience' => $audiences[ $industry ],
				'price_position'  => 'value',
				'differentiator'  => 'Personalized shopping experience with expert product recommendations.',
				'pain_points'     => 'Product quality uncertainty, difficult returns, impersonal shopping experiences',
				'brand_tone'      => 'friendly',
				'words_to_avoid'  => 'cheap, discount, budget, basic',
				'promotion_style' => 'moderate',
				'primary_color'   => $primary_color,
				'text_color'      => $text_color,
				'bg_color'        => $bg_color,
				'font_family'     => 'system',
				'brand_values'    => array( 'Quality', 'Trust', 'Service', 'Innovation' ),
			),
			'tokens_used' => array(
				'input'  => 300,
				'output' => 200,
				'total'  => 500,
			),
		);
	}

	/**
	 * Mock get auto top-up settings response
	 *
	 * @return array
	 */
	public static function get_auto_topup_settings() {
		return array(
			'enabled'      => get_option( 'aisales_auto_topup_enabled', false ),
			'threshold'    => get_option( 'aisales_auto_topup_threshold', 1000 ),
			'product_slug' => get_option( 'aisales_auto_topup_product', 'standard_plan' ),
		);
	}

	/**
	 * Mock update auto top-up settings response
	 *
	 * @param array $settings Settings to update.
	 * @return array
	 */
	public static function update_auto_topup_settings( $settings ) {
		if ( isset( $settings['enabled'] ) ) {
			update_option( 'aisales_auto_topup_enabled', $settings['enabled'] );
		}
		if ( isset( $settings['threshold'] ) ) {
			update_option( 'aisales_auto_topup_threshold', $settings['threshold'] );
		}
		if ( isset( $settings['product_slug'] ) ) {
			update_option( 'aisales_auto_topup_product', $settings['product_slug'] );
		}

		return self::get_auto_topup_settings();
	}

	/**
	 * Mock get payment method response
	 *
	 * @return array
	 */
	public static function get_payment_method() {
		$has_method = get_option( 'aisales_mock_payment_method', false );

		if ( ! $has_method ) {
			return array( 'payment_method' => null );
		}

		return array(
			'payment_method' => array(
				'id'        => 'pm_mock_1234567890',
				'brand'     => 'visa',
				'last4'     => '4242',
				'exp_month' => 12,
				'exp_year'  => 2027,
			),
		);
	}

	/**
	 * Mock setup payment method response
	 *
	 * @param string $success_url Success URL.
	 * @return array
	 */
	public static function setup_payment_method( $success_url ) {
		update_option( 'aisales_mock_payment_method', true );

		return array(
			'setup_url'  => add_query_arg( 'payment_setup', 'success', $success_url ),
			'session_id' => 'mock_setup_session_' . time(),
		);
	}

	/**
	 * Mock confirm setup response
	 *
	 * @param string $session_id Session ID.
	 * @return array
	 */
	public static function confirm_setup( $session_id ) {
		return array(
			'confirmed' => true,
			'payment_method' => array(
				'id'        => 'pm_mock_1234567890',
				'brand'     => 'visa',
				'last4'     => '4242',
				'exp_month' => 12,
				'exp_year'  => 2027,
			),
		);
	}

	/**
	 * Mock remove payment method response
	 *
	 * @return array
	 */
	public static function remove_payment_method() {
		delete_option( 'aisales_mock_payment_method' );
		update_option( 'aisales_auto_topup_enabled', false );

		return array( 'removed' => true );
	}

	/**
	 * Mock get plans response
	 *
	 * @return array
	 */
	public static function get_plans() {
		return array(
			'plans' => array(
				array(
					'id'        => 'starter_plan',
					'name'      => 'Starter',
					'tokens'    => 5000,
					'price_usd' => 5.00,
				),
				array(
					'id'        => 'standard_plan',
					'name'      => 'Standard',
					'tokens'    => 10000,
					'price_usd' => 9.00,
				),
				array(
					'id'        => 'pro_plan',
					'name'      => 'Pro',
					'tokens'    => 25000,
					'price_usd' => 20.00,
				),
				array(
					'id'        => 'business_plan',
					'name'      => 'Business',
					'tokens'    => 50000,
					'price_usd' => 35.00,
				),
			),
		);
	}

	/**
	 * Mock get purchases response
	 *
	 * @param int $limit  Limit.
	 * @param int $offset Offset.
	 * @return array
	 */
	public static function get_purchases( $limit, $offset ) {
		$mock_purchases = array(
			array(
				'id'            => 1,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 10000,
				'description'   => 'Token top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
			),
			array(
				'id'            => 2,
				'type'          => 'auto_topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 12500,
				'description'   => 'Auto top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
			),
			array(
				'id'            => 3,
				'type'          => 'topup',
				'amount_tokens' => 10000,
				'amount_usd'    => 900,
				'balance_after' => 17432,
				'description'   => 'Token top-up: 10,000 tokens',
				'created_at'    => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			),
		);

		usort( $mock_purchases, function( $a, $b ) {
			return strtotime( $b['created_at'] ) - strtotime( $a['created_at'] );
		});

		return array(
			'purchases' => array_slice( $mock_purchases, $offset, $limit ),
			'total'     => count( $mock_purchases ),
			'limit'     => $limit,
			'offset'    => $offset,
		);
	}

	/**
	 * Mock create recovery token response
	 *
	 * @param string $email  User email.
	 * @param string $domain Site domain.
	 * @return array
	 */
	public static function create_recovery_token( $email, $domain ) {
		$normalized_domain = preg_replace( '/^www\./i', '', strtolower( trim( $domain ) ) );
		$stored_domain     = get_option( 'aisales_domain', '' );
		$stored_email      = get_option( 'aisales_user_email', '' );
		$stored_normalized = preg_replace( '/^www\./i', '', strtolower( trim( $stored_domain ) ) );

		if ( $stored_normalized !== $normalized_domain || strtolower( trim( $stored_email ) ) !== strtolower( trim( $email ) ) ) {
			return array(
				'success'     => true,
				'has_account' => false,
				'message'     => 'If an account exists with this email and domain, a recovery token has been created',
			);
		}

		$token = 'mock_recovery_' . wp_generate_password( 32, false );

		set_transient( 'aisales_mock_recovery_token', array(
			'token'  => $token,
			'email'  => $email,
			'domain' => $normalized_domain,
		), HOUR_IN_SECONDS );

		return array(
			'success'     => true,
			'has_account' => true,
			'token'       => $token,
			'email'       => $email,
			'expires_at'  => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
		);
	}

	/**
	 * Mock validate recovery token response
	 *
	 * @param string $token Recovery token.
	 * @return array|WP_Error
	 */
	public static function validate_recovery_token( $token ) {
		$stored = get_transient( 'aisales_mock_recovery_token' );

		if ( ! $stored || $stored['token'] !== $token ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid or expired recovery token.', 'ai-sales-manager-for-woocommerce' )
			);
		}

		$new_api_key = 'wai_mock_' . wp_generate_password( 32, false );
		delete_transient( 'aisales_mock_recovery_token' );

		return array(
			'api_key' => $new_api_key,
			'email'   => $stored['email'],
			'message' => __( 'API key recovered successfully.', 'ai-sales-manager-for-woocommerce' ),
		);
	}

	/**
	 * Mock create support draft
	 *
	 * @param array $data Draft payload.
	 * @return array
	 */
	public static function create_support_draft( $data ) {
		return array(
			'draft_id'        => 'draft_' . wp_generate_password( 8, false ),
			'questions'       => array(
				__( 'Which browser version are you using?', 'ai-sales-manager-for-woocommerce' ),
				__( 'Does the issue persist after disabling caching?', 'ai-sales-manager-for-woocommerce' ),
			),
			'ready_to_submit' => false,
		);
	}

	/**
	 * Mock clarify support draft
	 *
	 * @param string $draft_id Draft ID.
	 * @param array  $answers  Answers.
	 * @return array
	 */
	public static function clarify_support_draft( $draft_id, $answers ) {
		return array(
			'draft_id'        => $draft_id,
			'questions'       => array(),
			'ready_to_submit' => true,
		);
	}

	/**
	 * Mock submit support ticket
	 *
	 * @param string $draft_id Draft ID.
	 * @return array
	 */
	public static function submit_support_ticket( $draft_id ) {
		return array(
			'ticket_id' => 'WD-' . wp_rand( 24000, 25999 ),
			'status'    => 'open',
		);
	}

	/**
	 * Mock support ticket list
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public static function get_support_tickets( $filters = array() ) {
		return array(
			'tickets' => array(
				array(
					'id'         => 'WD-24819',
					'title'      => 'Order confirmation email not firing',
					'status'     => 'pending',
					'category'   => 'bug',
					'updated_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
					'preview'    => 'No email is sent after status change. Logs show missing hook.',
				),
				array(
					'id'         => 'WD-24807',
					'title'      => 'AI product rewrite repeats titles',
					'status'     => 'resolved',
					'category'   => 'support',
					'updated_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
					'preview'    => 'Resolved with a prompt update. Please regenerate.',
				),
				array(
					'id'         => 'WD-24792',
					'title'      => 'Feature request: bulk image upscaling',
					'status'     => 'open',
					'category'   => 'feature',
					'updated_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
					'preview'    => 'Would love an option to upscale images in batch.',
				),
			),
		);
	}

	/**
	 * Mock support ticket detail
	 *
	 * @param string $ticket_id Ticket ID.
	 * @return array
	 */
	public static function get_support_ticket( $ticket_id ) {
		return array(
			'id'       => $ticket_id,
			'messages' => array(
				array(
					'author'     => 'user',
					'content'    => 'Order emails stopped sending after plugin update 1.2.0.',
					'created_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-4 hours' ) ),
				),
				array(
					'author'     => 'support',
					'content'    => 'Thanks! Can you confirm if WooCommerce 9.0 is installed?',
					'created_at' => gmdate( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ),
				),
			),
		);
	}

	/**
	 * Mock reply to support ticket
	 *
	 * @param string $ticket_id Ticket ID.
	 * @param string $message   Reply message.
	 * @return array
	 */
	public static function reply_support_ticket( $ticket_id, $message ) {
		return array(
			'ticket_id' => $ticket_id,
			'message'   => $message,
			'sent'      => true,
		);
	}

	/**
	 * Mock support stats
	 *
	 * @return array
	 */
	public static function get_support_stats() {
		return array(
			'open'     => 3,
			'pending'  => 2,
			'resolved' => 14,
			'average'  => '5h',
		);
	}

	/**
	 * Mock get balance response
	 *
	 * @return array
	 */
	public static function get_balance() {
		return array(
			'balance'          => get_option( 'aisales_balance', 7432 ),
			'auto_topup'       => self::get_auto_topup_settings(),
			'payment_method'   => self::get_payment_method()['payment_method'],
			'usage_this_month' => 2500,
		);
	}

	/**
	 * Deduct mock tokens from balance
	 *
	 * @param int $tokens Number of tokens to deduct.
	 */
	private static function deduct_tokens( $tokens ) {
		$balance     = get_option( 'aisales_balance', 7432 );
		$new_balance = max( 0, $balance - $tokens );
		update_option( 'aisales_balance', $new_balance );
	}
}
