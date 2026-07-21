<?php
defined('ABSPATH') || exit;

function primed_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption']);

    register_nav_menus([
        'primary' => 'Primary Menu',
        'footer'  => 'Footer Menu',
    ]);
}
add_action('after_setup_theme', 'primed_setup');

function primed_enqueue() {
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
    wp_enqueue_style('primed-style', get_stylesheet_uri(), [], '1.0.3');
    wp_enqueue_script('primed-main', get_template_directory_uri() . '/assets/js/main.js', ['jquery'], '1.0.1', true);

    if (is_woocommerce() || is_cart() || is_checkout()) {
        wp_enqueue_script('wc-cart-fragments');
    }
}
add_action('wp_enqueue_scripts', 'primed_enqueue');

// Load WooCommerce scripts on the homepage (custom index.php isn't detected as a WC page)
add_filter('woocommerce_is_woocommerce_page', function($is_wc_page) {
    if (is_front_page() || is_home()) return true;
    return $is_wc_page;
});

// Redirect /shop to homepage — homepage already shows all products in the custom grid
add_action('template_redirect', function() {
    if (is_shop()) {
        wp_redirect(home_url('/'), 301);
        exit;
    }
});

// Remove WooCommerce default styles and replace with ours
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

// Change shop columns
add_filter('loop_shop_columns', function() { return 3; });
add_filter('loop_shop_per_page', function() { return 12; });

// Remove WooCommerce breadcrumbs
add_filter('woocommerce_breadcrumb_defaults', function($d) { return array_merge($d, ['delimiter' => ' &rsaquo; ']); });

// Add Certificate of Analysis tab to product pages
add_filter('woocommerce_product_tabs', function($tabs) {
    $tabs['coa'] = [
        'title'    => 'Certificate of Analysis',
        'priority' => 50,
        'callback' => 'primed_coa_tab_content',
    ];
    return $tabs;
});

function primed_coa_tab_content() {
    echo '<h2>Certificate of Analysis</h2>';
    echo '<p>All Primed Peptides products are independently third-party tested for purity and potency. Every batch achieves 99%+ purity.</p>';
    echo '<p>To request a Certificate of Analysis for a specific batch, please email us at <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> with your order number.</p>';
}

// ── Free shipping over £100 — applied in code for reliability ──
add_filter('woocommerce_package_rates', function($rates, $package) {
    $subtotal = WC()->cart ? WC()->cart->get_subtotal() : 0;
    if ($subtotal >= 100) {
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate_id, 'flat_rate') !== false) {
                $rates[$rate_id]->cost   = 0;
                $rates[$rate_id]->label  = 'Royal Mail Tracked 24 (1-2 days) — Free';
                $rates[$rate_id]->taxes  = [];
            }
        }
    }
    return $rates;
}, 10, 2);

// ── Single product page — trust strip + delivery notice ──
add_action('woocommerce_single_product_summary', function() {
    echo '<div class="product-trust-strip">
        <span><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> UK Made</span>
        <span><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> 99%+ Purity</span>
        <span><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg> Batch Tested</span>
        <span><svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> 12 Month Shelf Life</span>
    </div>';
}, 15);

add_action('woocommerce_single_product_summary', function() {
    echo '<div class="product-delivery-notice">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        Tracked 24 delivery — dispatched same day on orders before 2pm
    </div>';
}, 28);

// ── Partner & Affiliate Referral System ──
// Partners: customer pays into partner's bank directly
// Affiliates: customer pays Primed Peptides, order is tagged for commission tracking

function primed_referral_config() {
    return [
        // Partners — add bank details when available
        'rupert' => [
            'name' => 'Rupert Butcher',
            'type' => 'partner',
            'bank' => null, // Uses WooCommerce default bank details
        ],
        'rod' => [
            'name' => 'Rod',
            'type' => 'partner',
            'bank' => null, // Add: ['account_name'=>'', 'account_number'=>'', 'sort_code'=>'', 'bank_name'=>'', 'iban'=>'', 'bic'=>'']
        ],
        'tom' => [
            'name' => 'Tom',
            'type' => 'partner',
            'bank' => null, // Add bank details when available
        ],
        // Affiliates — no bank swap, order gets tagged for commission
        // 'kat' => ['name' => 'Kat', 'type' => 'affiliate', 'bank' => null],
    ];
}

// Store referral in cookie for 30 days when visitor arrives via ?ref=name
add_action('init', function() {
    if (!empty($_GET['ref'])) {
        $ref = sanitize_text_field($_GET['ref']);
        $config = primed_referral_config();
        if (isset($config[$ref])) {
            setcookie('primed_ref', $ref, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            $_COOKIE['primed_ref'] = $ref;
        }
    }
});

// Swap bank details at checkout for partners who have bank details set
add_filter('woocommerce_bacs_accounts', function($accounts) {
    $ref = isset($_COOKIE['primed_ref']) ? sanitize_text_field($_COOKIE['primed_ref']) : '';
    if (!$ref) return $accounts;

    $config = primed_referral_config();
    if (!isset($config[$ref])) return $accounts;

    $person = $config[$ref];
    if ($person['type'] === 'partner' && !empty($person['bank'])) {
        return [$person['bank']];
    }
    return $accounts;
});

// Tag every referred order with who sent the customer
add_action('woocommerce_checkout_order_created', function($order) {
    $ref = isset($_COOKIE['primed_ref']) ? sanitize_text_field($_COOKIE['primed_ref']) : '';
    if (!$ref) return;

    $config = primed_referral_config();
    if (!isset($config[$ref])) return;

    $person = $config[$ref];
    $order->update_meta_data('_primed_ref', $ref);
    $order->update_meta_data('_primed_ref_name', $person['name']);
    $order->update_meta_data('_primed_ref_type', $person['type']);
    $order->add_order_note('Referred by: ' . esc_html($person['name']) . ' [' . esc_html($person['type']) . ']');
    $order->save();
});
