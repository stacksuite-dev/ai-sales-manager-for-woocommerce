# StackSuite Sales Manager for WooCommerce - Developer Notes

## Project Structure

```
plugin/
├── stacksuite-sales-manager-for-woocommerce.php  # Main plugin file
├── includes/                              # PHP classes
│   ├── ajax/                              # AJAX handlers (modular)
│   │   ├── class-aisales-ajax-base.php    # Abstract base class
│   │   ├── class-aisales-ajax-loader.php  # Loader/initializer
│   │   ├── class-aisales-ajax-auth.php    # Auth & account handlers
│   │   ├── class-aisales-ajax-billing.php # Billing & payment handlers
│   │   ├── class-aisales-ajax-ai.php      # AI generation handlers
│   │   ├── class-aisales-ajax-products.php# Product & category handlers
│   │   ├── class-aisales-ajax-email.php   # Email template handlers
│   │   ├── class-aisales-ajax-support.php # Support ticket handlers
│   │   ├── class-aisales-ajax-brand.php   # Brand settings handlers
│   │   ├── class-aisales-ajax-store.php   # Store context handlers
│   │   └── class-aisales-ajax-seo.php     # SEO checker handlers
│   ├── seo/                               # SEO checker subsystem
│   │   ├── class-aisales-seo-checks-local.php  # Free local checks
│   │   ├── class-aisales-seo-checks-api.php    # API-powered checks
│   │   └── class-aisales-seo-fixer.php    # AI fix logic
│   ├── widgets/                           # Widgets subsystem
│   │   └── class-aisales-widgets-page.php
│   ├── class-aisales-admin-settings.php
│   ├── class-aisales-email-page.php
│   ├── class-aisales-brand-page.php
│   ├── class-aisales-seo-checker-page.php # SEO checker page
│   ├── class-aisales-seo-analyzer.php     # SEO analysis engine
│   └── ...
├── templates/                             # PHP templates
│   ├── admin-widgets-page.php
│   ├── admin-email-page.php
│   ├── admin-brand-page.php
│   ├── admin-seo-checker-page.php         # SEO checker template
│   └── ...
├── assets/
│   ├── css/                               # Stylesheets
│   │   ├── admin.css                      # Main admin styles
│   │   ├── shared-components.css          # Reusable components
│   │   ├── widgets-page.css
│   │   ├── email-page.css
│   │   ├── seo-checker-page.css           # SEO checker styles
│   │   └── chat/                          # Modular chat styles
│   └── js/                                # JavaScript
│       ├── admin.js                       # Main admin JS
│       ├── widgets-page.js
│       ├── email-page.js
│       ├── seo-checker-page.js            # SEO checker JS
│       └── chat/                          # Modular chat JS
```

## Git Repository

Git is initialized in the `plugin/` subfolder, not the project root.

```bash
cd plugin && git status
```

## Widgets System Architecture

### Widget Types

Widgets are divided into two types:

1. **Shortcodes** (`'type' => 'shortcode'`)
   - Pure shortcodes that users place manually in content/templates
   - No toggle switch - if you don't use the shortcode, it does nothing
   - Examples: `[aisales_total_sold]`, `[aisales_countdown]`, `[aisales_bestsellers]`

2. **Features** (`'type' => 'feature'`)
   - Auto-inject functionality that runs automatically when enabled
   - Has enable/disable toggle in the admin UI
   - Examples:
     - `recent_purchase` - Auto-display popup notifications site-wide
     - `live_viewers` - Auto-display visitor count on product pages
     - `shipping_bar` - Auto-display on cart page

### Widget Definitions

Located in `includes/widgets/class-aisales-widgets-page.php`:

```php
'widget_key' => array(
    'name'        => __( 'Widget Name', 'stacksuite-sales-manager-for-woocommerce' ),
    'description' => __( 'Description', 'stacksuite-sales-manager-for-woocommerce' ),
    'shortcode'   => 'aisales_shortcode_name',
    'category'    => 'social_proof', // social_proof, conversion, discovery
    'type'        => 'shortcode',    // shortcode or feature
    'icon'        => 'dashicons-icon-name',
    'attributes'  => array( ... ),
    'preview'     => array( ... ),
),
```

### Categories

- `social_proof` - Build trust (Sales Counter, Recent Purchase, Live Viewers, etc.)
- `conversion` - Optimize sales (Shipping Bar, Countdown Timer, Price Drop, etc.)
- `discovery` - Product discovery (Best Sellers, Trending, Recently Viewed, etc.)

## CSS Architecture

### Design System Variables

Defined in `assets/css/admin.css`:

```css
--aisales-primary: #2271b1;
--aisales-success: #00a32a;
--aisales-warning: #dba617;
--aisales-danger: #d63638;
--aisales-text-primary: #1d2327;
--aisales-border-light: #dcdcde;
/* ... etc */
```

### Shared Components (`shared-components.css`)

Reusable UI components:
- `.aisales-balance-indicator` - Token balance pill (used in headers)
- `.aisales-store-context-btn` - Store context button
- `.aisales-header-btn` - Generic header button style

### Page-Specific Styles

Each admin page has its own CSS file:
- `widgets-page.css` - Widgets & Shortcodes page
- `email-page.css` - Email Templates page
- `brand-page.css` - Brand Settings page

### Badge Patterns

For custom badges that look like `.aisales-balance-indicator` but don't share its JS behavior, create standalone classes:

```css
/* Example: widgets-page.css */
.aisales-widgets-active-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--aisales-space-2, 6px);
  padding: var(--aisales-space-2, 6px) var(--aisales-space-4, 12px);
  background: var(--aisales-bg-white, #fff);
  border: 1px solid var(--aisales-success, #00a32a);
  border-radius: var(--aisales-radius-full, 9999px);
  /* ... */
}
```

## AJAX Architecture

AJAX handlers are organized into domain-specific classes in `includes/ajax/`:

### Handler Classes

| Class | Domain | Handlers |
|-------|--------|----------|
| `AISales_Ajax_Auth` | Authentication | connect, get_balance, recovery |
| `AISales_Ajax_Billing` | Payments | topup, auto_topup, payment_method |
| `AISales_Ajax_AI` | AI Generation | generate_content, suggest_taxonomy, images |
| `AISales_Ajax_Products` | Products/Categories | update fields, thumbnails, batch |
| `AISales_Ajax_Email` | Email Templates | templates, wizard, test emails |
| `AISales_Ajax_Support` | Support Tickets | draft, submit, list, upload |
| `AISales_Ajax_Brand` | Brand Settings | save settings, AI analysis |
| `AISales_Ajax_Store` | Store Context | sync, balance, tool data |
| `AISales_Ajax_SEO` | SEO Checker | run_scan, generate_fix, apply_fix |

### Creating New AJAX Handlers

1. Extend `AISales_Ajax_Base`
2. Implement `register_actions()` method
3. Use helper methods from base class

```php
class AISales_Ajax_Example extends AISales_Ajax_Base {
    protected function register_actions() {
        $this->add_action( 'my_action', 'handle_my_action' );
    }

    public function handle_my_action() {
        $this->verify_request();
        $value = $this->get_post( 'field_name', 'text' );
        // ... handle request
        $this->success( array( 'result' => $value ) );
    }
}
```

### Base Class Helpers

- `verify_request()` - Check nonce and capability
- `get_post( $key, $type, $default )` - Get sanitized POST value
- `require_post( $key, $type, $error )` - Get required POST value
- `success( $data )` - Send JSON success response
- `error( $message, $data )` - Send JSON error response
- `handle_api_result( $result )` - Handle API response with error checking
- `get_validated_product( $id )` - Get and validate WC_Product
- `get_validated_category( $id )` - Get and validate product category

## JavaScript Patterns

### AJAX Calls

All AJAX actions use the `aisales_` prefix:

### Localized Script Data

```php
wp_localize_script( 'aisales-widgets-page', 'aisalesWidgets', array(
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'aisales_nonce' ),
    // ... other data
) );
```

## Admin Pages

### Page Hook Names

Used for conditional asset loading:
- `toplevel_page_ai-sales-manager` - Main settings
- `ai-sales-manager_page_ai-sales-agent` - AI Agent/Chat
- `ai-sales-manager_page_ai-sales-emails` - Email Templates
- `ai-sales-manager_page_ai-sales-brand` - Brand Settings
- `ai-sales-manager_page_ai-sales-widgets` - Widgets & Shortcodes
- `ai-sales-manager_page_ai-sales-seo-checker` - SEO Checker

### Header Pattern

All admin pages follow consistent header structure:
```php
<header class="aisales-{page}-header">
    <div class="aisales-{page}-header__left">
        <span class="aisales-{page}-header__title">
            <span class="dashicons dashicons-{icon}"></span>
            <?php esc_html_e( 'Page Title', 'stacksuite-sales-manager-for-woocommerce' ); ?>
        </span>
        <span class="aisales-{page}-header__subtitle">...</span>
    </div>
    <div class="aisales-{page}-header__right">
        <!-- Badge or action buttons -->
    </div>
</header>
```

## SEO Checker System

The SEO Checker provides comprehensive store-wide SEO auditing.

### Architecture

- **`AISales_SEO_Checker_Page`** - Admin page class (menu priority 26)
- **`AISales_SEO_Analyzer`** - Core analysis engine coordinating checks
- **`AISales_SEO_Checks_Local`** - Free local checks (no API tokens)
- **`AISales_SEO_Checks_API`** - Advanced API-powered checks (uses tokens)
- **`AISales_SEO_Fixer`** - AI-powered fix generation and application

### Content Types Scanned

- Products (8 checks)
- Categories (5 checks)
- Pages (9 checks)
- Blog Posts (10 checks)
- Store Settings (6 checks)
- Homepage (5 checks)

### Local Checks (Free)

| Check | Description |
|-------|-------------|
| `title_length` | Title 30-60 characters optimal |
| `meta_description_missing` | Meta description required |
| `meta_description_length` | 120-160 characters optimal |
| `image_alt_missing` | Alt text for accessibility/SEO |
| `content_thin` | Minimum 100 words |
| `heading_structure` | H2/H3 for long content |
| `internal_links` | Link to related content |

### Data Storage

```php
// Scan results (WordPress options)
'aisales_seo_scan_results' => [
    'scan_date'       => '2026-01-22 15:45:00',
    'overall_score'   => 68,
    'scores'          => [...],  // Per-category scores
    'issues'          => [...],  // Issue counts
    'detailed_issues' => [...],  // Full issue list
]

// Per-item cache (post/term meta)
'_aisales_seo_score'      => 85
'_aisales_seo_last_check' => '2026-01-22 15:45:00'
```

### Score Colors

- 90-100: `--aisales-success` (Excellent)
- 70-89: `--aisales-primary` (Good)
- 50-69: `--aisales-warning` (Needs Work)
- 0-49: `--aisales-danger` (Critical)
