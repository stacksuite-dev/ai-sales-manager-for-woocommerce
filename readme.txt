=== AI Sales Manager for WooCommerce ===
Contributors: stacksuite
Tags: woocommerce, ai, product management, content generation, image generation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.4.0
WC requires at least: 8.0
WC tested up to: 9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WooCommerce product management. Generate content, suggest tags, and create product images with AI.

== Description ==

AI Sales Manager for WooCommerce brings the power of AI to your WooCommerce store. Enhance your product catalog with:

* **AI Agent Chat** - Interactive chat interface to manage products and categories with AI assistance
* **Content Generation** - Improve product descriptions, generate from titles, or SEO optimize existing content
* **Category Management** - Generate category descriptions, SEO meta, and subcategory suggestions
* **Taxonomy Suggestions** - Get AI-suggested tags, categories, and product attributes
* **Image Generation** - Create product images from descriptions
* **Image Improvement** - Enhance existing product images

= Features =

* Pay-per-use pricing ($9 for 10,000 tokens)
* Native WordPress admin UI integration
* AI Agent chat interface with product/category context
* Product editor sidebar tools
* Category editor integration
* Store context configuration for personalized AI responses
* Usage tracking and history
* Secure domain-based authentication
* Yoast SEO and RankMath compatibility

= Requirements =

* WooCommerce 8.0 or higher
* PHP 8.0 or higher
* WordPress 6.0 or higher

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-sales-manager-for-woocommerce/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce is installed and active
4. Go to AI Sales Manager in the admin menu
5. Enter your email to connect your store (domain-based authentication)
6. Top up your token balance
7. Start using AI tools on your products or try the AI Agent chat!

== Frequently Asked Questions ==

= How much does it cost? =

$9 for 10,000 tokens. Different operations use different amounts:

* Content generation: ~200 tokens
* Taxonomy suggestions: ~100 tokens
* Image generation: ~1,000 tokens
* Category content: ~150 tokens

= Is my data secure? =

Yes. We use industry-standard encryption and your product data is not stored on our servers after processing.

= Can I use this with any WooCommerce theme? =

Yes, the plugin works with any properly coded WooCommerce theme.

= What happens when I uninstall the plugin? =

When you delete the plugin through WordPress, all plugin data is removed including your API key, balance cache, and store context settings. Your account on the AI Sales Manager service remains intact - you can reconnect anytime using the same email.

= Does it work with Yoast SEO or RankMath? =

Yes! When you generate SEO content for categories, the plugin automatically saves meta titles and descriptions in formats compatible with both Yoast SEO and RankMath.

= What is the AI Agent? =

The AI Agent is an interactive chat interface where you can have conversations with AI to manage your products and categories. Select a product or category, then ask the AI to improve descriptions, suggest tags, optimize for SEO, and more.

= Why do I need WooCommerce? =

This plugin is specifically designed for WooCommerce stores. It integrates with WooCommerce products, categories, and taxonomies. The plugin will not activate without WooCommerce installed.

== External Services ==

This plugin connects to the AI Sales Manager SaaS platform (https://ai-sales-manager-for-woocommerce.store) for:

* AI content generation and processing
* Token balance and account management
* Billing and checkout

Data sent: Product titles, descriptions, and category information for AI processing.
Data is NOT stored after processing is complete.

* [Privacy Policy](https://stacksuite.dev/privacy)
* [Terms of Service](https://stacksuite.dev/terms)

By using the AI features, you agree to these terms.

== Screenshots ==

1. Dashboard with balance and usage overview
2. AI Agent chat interface
3. Product editor AI tools sidebar
4. Category editor AI tools
5. Content generation result modal
6. Usage history table
7. Store context configuration

== Changelog ==

= 1.4.0 =
* Added: `Requires Plugins: woocommerce` header for WordPress 6.5+ plugin dependencies
* Fixed: Inline scripts now use wp_add_inline_script() per WordPress coding standards
* Fixed: Removed external placeholder image URLs (via.placeholder.com) - now uses WooCommerce placeholders
* Improved: Full WordPress Plugin Review compliance for wp.org submission
* Added: SEO Checker page for store-wide SEO auditing
* Added: Batch processing page for bulk product operations
* Added: Widgets & Shortcodes system with social proof, conversion, and discovery widgets
* Added: Marketing website with multi-language support (EN, ES, FR, DE, PT, ZH-CN, ZH-TW)

= 1.3.0 =
* Added: Modular AJAX handler architecture for better code organization
* Added: Mock API system for development and testing (AISALES_MOCK_MODE)
* Added: Tool catalog for AI agent capabilities
* Added: Brand settings page for store branding configuration
* Added: Support ticket system with AI-powered draft analysis
* Added: Abandoned cart tracking and recovery emails
* Added: Mail provider configuration (native WP, SMTP, SendGrid, Mailgun, Postmark)
* Improved: Chat page with wizard-based task selection flow
* Improved: Entity panels for product and category editing

= 1.2.0 =
* Changed: Renamed all code prefixes from 'wooai' to 'aisales' for WordPress.org trademark compliance
* Changed: Renamed class files from 'class-wooai-*' to 'class-aisales-*'
* Removed: Deprecated email/password login and register handlers
* Improved: Replaced direct database queries with WordPress API functions (WP_Query, delete_metadata)
* Improved: Tab navigation now uses filter_input() for cleaner superglobal access
* Improved: Uninstall cleanup now uses WordPress functions instead of direct SQL
* Fixed: All WordPress Plugin Check warnings resolved
* Added: GPLv2 license file included in distribution

= 1.1.0 =
* Added: AI Agent chat interface for interactive product and category management
* Added: Category content generation (descriptions, SEO meta, subcategory suggestions)
* Added: Store context configuration for personalized AI responses
* Added: WooCommerce dependency check with admin notice
* Added: Yoast SEO and RankMath compatibility for category SEO meta
* Added: Domain-based authentication (simplified connection flow)
* Added: uninstall.php for proper cleanup on plugin deletion
* Improved: Code quality and reduced duplication in AJAX handlers
* Deprecated: Email/password authentication (use domain-based auth instead)
* Fixed: Control flow in security verification methods

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.4.0 =
WordPress Plugin Review compliance update. Adds SEO Checker, Widgets system, Batch processing, and Marketing Agent capabilities. Fixes inline scripts and external image references for wp.org submission.

= 1.3.0 =
Major feature update with modular AJAX architecture, abandoned cart recovery, mail provider configuration, and AI-powered support system.

= 1.2.0 =
Code refactoring for WordPress.org compliance. All 'wooai' prefixes renamed to 'aisales'. No functionality changes - existing settings will need to be reconfigured after update.

= 1.1.0 =
Major update with AI Agent chat interface, category management, and improved code quality. Domain-based authentication is now the recommended connection method.

= 1.0.0 =
Initial release of AI Sales Manager for WooCommerce.
