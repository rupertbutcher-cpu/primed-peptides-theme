<?php
defined('ABSPATH') || exit;

// Site Title was left on the WordPress default placeholder ("My WordPress") - fixes
// the <title> tag shown on every page, browser tab, and search results.
add_filter('pre_option_blogname', fn() => 'Primed Peptides');

// Google Search Console ownership verification (added 2026-08-29 - neither peptide
// site was indexed by Google at all before this).
add_action('wp_head', function() {
    echo '<meta name="google-site-verification" content="gmk6izxBJhMIBI91ZxunLAZs7JzQHgQuOzJTZjHP6Sk" />' . "\n";
});

// No meta description existed anywhere on the site - Google was generating every
// search snippet automatically. Product pages use the real short description;
// static pages get a specific one; everything else falls back to a site-wide line.
add_action('wp_head', function() {
    $desc = 'UK-made research peptides, third-party tested to 99%+ purity. Research Use Only.';
    if (is_product()) {
        // global $product isn't populated yet this early in wp_head - look it up directly
        $prod = wc_get_product(get_queried_object_id());
        if ($prod) {
            $raw = wp_strip_all_tags($prod->get_short_description() ?: $prod->get_description());
            if ($raw) $desc = wp_trim_words($raw, 30, '...');
        }
    } elseif (is_front_page()) {
        $desc = 'Primed Peptides supplies UK-made research peptides - BPC-157, TB-500, NAD+, Semax and more. Third-party tested, Research Use Only.';
    } elseif (is_page('about')) {
        $desc = 'About Primed Peptides: UK-based supplier of research-grade peptides, independently tested for purity.';
    } elseif (is_page('contact')) {
        $desc = 'Contact Primed Peptides for research peptide orders and enquiries.';
    } elseif (is_page('terms-conditions')) {
        $desc = 'Terms and conditions for ordering research peptides from Primed Peptides.';
    } elseif (is_page('privacy-policy')) {
        $desc = 'Privacy policy for Primed Peptides - how we handle your data.';
    } elseif (is_page('refund_returns')) {
        $desc = 'Shipping and returns policy for Primed Peptides orders.';
    }
    echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
}, 1);

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
    wp_enqueue_style('primed-style', get_stylesheet_uri(), [], '1.0.4');
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

// Force the homepage product grid to sort by menu_order. WooCommerce hooks
// pre_get_posts at priority 10 (WC_Query::product_query) and overrides
// orderby on ANY post_type=product query - including this theme's own
// secondary WP_Query in index.php - based on the store's global default
// sorting option, silently ignoring the orderby/order set directly in the
// query args. Running at priority 20 (after WooCommerce's own hook) makes
// this the one that actually sticks, without touching the main query.
add_action('pre_get_posts', function($query) {
    if (!is_admin() && !$query->is_main_query() && $query->get('post_type') === 'product') {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
}, 20);

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

// Bank-transfer checkout had zero supporting copy anywhere - explains the process
// once, at the point of purchase, rather than leaving a first-time buyer to guess.
add_action('woocommerce_single_product_summary', function() {
    echo '<div class="product-delivery-notice" style="margin-top:8px;">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Orders are paid by bank transfer — place your order, we\'ll email transfer details, and it ships once payment clears (usually same day for UK transfers).
    </div>';
}, 29);

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
        'danny' => [
            'name' => 'Danny',
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

// ── Refer-a-Friend program (automatic, 15% both sides) ──
// Different from the Danny/Tom partner referral system above — this is for every ordinary
// customer, so they can refer their own friends and both get rewarded. Rupert (2026-08-19):
// "something for them to give to their customers saying refer a friend an they both get some
// kind of incentive" — real money on both sides of every successful referral, kept boundable:
// 15% is meaningfully smaller than DANNY100/TOM100's 100%, each customer's own referral code
// caps at 10 uses, and each reward coupon is single-use and locked to the referrer's own email.
define('PRIMED_REFERRAL_MAP_OPTION', 'primed_referral_map');

function primed_get_referral_map() {
    return get_option(PRIMED_REFERRAL_MAP_OPTION, []);
}

function primed_find_referral_owner($code) {
    $map  = primed_get_referral_map();
    $code = strtoupper($code);
    foreach ($map as $email => $owned_code) {
        if (strtoupper($owned_code) === $code) return $email;
    }
    return null;
}

function primed_generate_unique_code($prefix) {
    do {
        $code = $prefix . strtoupper(bin2hex(random_bytes(3)));
    } while (wc_get_coupon_id_by_code($code));
    return $code;
}

// Creates a real WooCommerce coupon (15% off) and returns its post ID.
function primed_create_referral_coupon($code, $usage_limit, $owner_email, $restrict_to_owner = false) {
    $coupon_id = wp_insert_post([
        'post_title'  => $code,
        'post_status' => 'publish',
        'post_type'   => 'shop_coupon',
    ]);
    if (is_wp_error($coupon_id) || !$coupon_id) return false;

    update_post_meta($coupon_id, 'discount_type', 'percent');
    update_post_meta($coupon_id, 'coupon_amount', '15');
    update_post_meta($coupon_id, 'individual_use', 'yes');
    update_post_meta($coupon_id, 'usage_limit', $usage_limit);
    update_post_meta($coupon_id, '_primed_referral_owner', $owner_email);
    if ($restrict_to_owner) {
        update_post_meta($coupon_id, 'customer_email', [$owner_email]);
    }
    return $coupon_id;
}

// Give every customer their own shareable referral code, once — either on their first paid
// order, or (2026-08-19) via the /refer/ card-scan landing page for people who haven't
// ordered yet at all. $source controls the email greeting so it reads correctly either way;
// the code/coupon/tracking logic is identical regardless of how someone got their code.
// ── Branded HTML email helper for referral emails ──
// Real issue found 2026-08-19: these emails were plain wp_mail() with no headers, so they
// bypassed WooCommerce's own From name/address settings entirely (WooCommerce's own order
// emails already send as "Primed Peptides <info@primedpeptides.co.uk>" - a separate fix made
// 2026-08-18 - but wp_mail() only picks that up if told to; left alone it falls back to
// WordPress core's own default, "WordPress <wordpress@$domain>"). Fixed by setting the same
// From header explicitly, and switched to a real HTML template with the actual logo instead
// of a bare plain-text message.
function primed_referral_email_html($heading, $body_html) {
    $logo_url = get_stylesheet_directory_uri() . '/images/logo-primed.png';
    ob_start();
    ?><!DOCTYPE html>
    <html>
    <body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:32px 16px;">
            <tr><td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:480px;width:100%;">
                    <tr><td style="background:#14181f;padding:28px 24px;text-align:center;">
                        <a href="https://primedpeptides.co.uk/" style="display:inline-block;">
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Primed Peptides" width="200" style="display:block;margin:0 auto;border:0;">
                        </a>
                    </td></tr>
                    <tr><td style="padding:32px 28px 8px;">
                        <h1 style="margin:0 0 16px;font-size:22px;color:#14181f;font-family:Arial,Helvetica,sans-serif;"><?php echo esc_html($heading); ?></h1>
                        <?php echo $body_html; ?>
                    </td></tr>
                    <tr><td style="padding:0 28px 28px;text-align:center;">
                        <a href="https://primedpeptides.co.uk/product/nad-cartridge-1000mg/" style="display:inline-block;">
                            <img src="https://primedpeptides.co.uk/wp-content/uploads/2026/08/nad_plus.png" alt="NAD+ Cartridge" width="220" style="display:block;margin:0 auto;border:0;border-radius:8px;">
                        </a>
                    </td></tr>
                    <tr><td style="padding:20px 28px;background:#f8f9fb;border-top:1px solid #eee;">
                        <p style="margin:0;font-size:12px;color:#8a92a3;">Primed Peptides — research-grade peptides, COA-verified every batch.</p>
                    </td></tr>
                </table>
            </td></tr>
        </table>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function primed_send_referral_email($to, $subject, $heading, $body_html) {
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Primed Peptides <info@primedpeptides.co.uk>',
    ];
    wp_mail($to, $subject, primed_referral_email_html($heading, $body_html), $headers);
}

// Real gap found 2026-08-19 (Rupert): the button used to link to the referrer's OWN referral
// URL - clicking it just took the referrer themself to the site, doing nothing useful, since
// they're not the one who's supposed to visit that link. What actually helps is a way to send
// the invite to a friend. Emails can't run interactive forms/JS, so a mailto: link (opens the
// referrer's own email client with the invite pre-written, just needing a friend's address
// typed into To:) is the right mechanism here - no new page/endpoint needed, and it's genuinely
// Rupert's own email sending it, not Primed emailing someone who never gave their address.
// Real follow-up ask (2026-08-19, Rupert): the mailto: draft was plain text, not the branded
// template - wanted the friend to actually receive "the same email" as the referrer. A button
// inside an email can't run a form itself, so it links to a small on-site page
// (?invite=1&code=X) where the referrer types a friend's address and the SITE sends the invite,
// reusing primed_referral_email_html() so it's genuinely the same branded template - not a
// second one to keep in sync by hand.
function primed_referral_code_box($code) {
    $link       = "https://primedpeptides.co.uk/?ref={$code}";
    $invite_url = "https://primedpeptides.co.uk/?invite=1&code=" . rawurlencode($code);
    return
        '<div style="background:#e8f0fc;border:1px solid #c9dbf5;border-radius:8px;padding:18px;text-align:center;margin:0 0 24px;">' .
            '<div style="font-size:12px;color:#4a7fd4;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Your referral code</div>' .
            '<div style="font-size:22px;font-weight:700;color:#14181f;letter-spacing:0.02em;">' . esc_html($code) . '</div>' .
        '</div>' .
        '<div style="text-align:center;margin:0 0 14px;">' .
            '<a href="' . esc_url($invite_url) . '" style="display:inline-block;background:#76b3f8;color:#0d1117;text-decoration:none;font-weight:600;padding:12px 28px;border-radius:6px;font-size:15px;">Email a friend</a>' .
        '</div>' .
        '<div style="text-align:center;">' .
            '<span style="font-size:13px;color:#8a92a3;">Or share your link: </span>' .
            '<a href="' . esc_url($link) . '" style="font-size:13px;color:#4a7fd4;">' . esc_html($link) . '</a>' .
        '</div>';
}

function primed_ensure_referral_code($email, $source = 'order') {
    $map = primed_get_referral_map();
    if (isset($map[$email])) return $map[$email];

    $code = primed_generate_unique_code('REFER-');
    if (!primed_create_referral_coupon($code, 10, $email, false)) return null;

    $map[$email] = $code;
    update_option(PRIMED_REFERRAL_MAP_OPTION, $map);

    $greeting = $source === 'card'
        ? "Thanks for connecting with Primed Peptides!"
        : "Thanks for your order!";

    $body = '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#333;">' . esc_html($greeting) . '</p>' .
        '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#333;">Refer a friend and you\'ll both save. Share your code or link below — they get 15% off their first order, and once it\'s confirmed, we\'ll email you a fresh 15% off code of your own. No limit on how many friends you refer.</p>' .
        primed_referral_code_box($code);

    primed_send_referral_email(
        $email,
        'Refer a friend to Primed Peptides — you both get 15% off',
        'Refer a friend, you both get 15% off',
        $body
    );

    return $code;
}

// ── ?getref=1 landing page — the card-scan entry point (no purchase needed) ──
// A QR-code business card handed out by anyone (not just Danny/Tom) links here. No real
// WordPress Page exists for this — the API can't create one (POST to /wp/v2/pages is
// blocked). Tried a clean /refer/ path first via template_redirect, but this site's
// .htaccess/permalink setup doesn't route that through to WordPress at all (real 404 straight
// from the webserver, confirmed via X-Proxy-Cache: MISS — not a caching artifact, genuinely
// never reached PHP). A query param on the root URL always reaches index.php regardless of
// permalink config, same reasoning the existing ?ref= cookie system already relies on.
add_action('template_redirect', function() {
    if (!isset($_GET['getref'])) return;
    // Real incident 2026-08-19: SiteGround's server-level Dynamic Cache (independent of
    // WordPress, no WP Admin control — see the site-caching section in project memory) cached
    // this page on its first hit and kept serving that stale copy through a later content
    // update, with no query-string variance to bust it. nocache_headers() is WordPress's own
    // helper for exactly this — sends Cache-Control: no-cache/no-store etc. — but SiteGround's
    // Dynamic Cache is known to ignore standard cache headers from origin (that's the whole
    // reason it needs a manual purge), so this is a best-effort future-proofing, not a
    // guaranteed fix — a manual SiteGround purge may still be needed after any further edit.
    nocache_headers();
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Refer a Friend — Primed Peptides</title>
        <style>
            body { background:#14181f; color:#fff; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif; margin:0; padding:40px 20px; display:flex; min-height:100vh; align-items:center; justify-content:center; }
            .box { max-width:420px; text-align:center; }
            .wordmark { margin:0 0 34px; }
            .wordmark img { width:340px; max-width:90%; height:auto; display:block; margin:0 auto; }
            h1 { font-size:26px; margin:0 0 10px; }
            p { color:#b7c2d1; font-size:15px; line-height:1.5; margin:0 0 26px; }
            form { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; }
            input { padding:12px 14px; border-radius:6px; border:1px solid #333; min-width:240px; font-size:15px; }
            button { background:#76b3f8; color:#0d1117; border:none; padding:12px 22px; border-radius:6px; cursor:pointer; font-weight:600; font-size:15px; }
            #msg { margin-top:18px; font-size:14px; color:#9fd0ff; }
            a.home { display:inline-block; margin-top:30px; color:#6b7280; font-size:13px; }
        </style>
    </head>
    <body>
        <div class="box">
            <?php
            // Cache-busted via filemtime — the image itself is served with a 1-year
            // Cache-Control, so anyone who already loaded a since-replaced version (real
            // incident 2026-08-19: an early logo export had a white fringe on its edges,
            // fixed by re-exporting with a proper colour-unmix instead of a hard alpha
            // threshold) would otherwise keep seeing the old file until it aged out on its
            // own. This makes any future replacement bust automatically, no manual ?v= bump.
            $logo_path = get_stylesheet_directory() . '/images/logo-primed.png';
            $logo_url  = get_stylesheet_directory_uri() . '/images/logo-primed.png';
            if (file_exists($logo_path)) $logo_url .= '?v=' . filemtime($logo_path);
            ?>
            <p class="wordmark"><img src="<?php echo esc_url( $logo_url ); ?>" alt="Primed Peptides"></p>
            <h1>Refer a friend, you both get 15% off</h1>
            <p>Enter your email to get your own personal referral link. Share it with friends — when they order, you both save.</p>
            <form id="f">
                <input type="email" name="email" required placeholder="you@example.com">
                <button type="submit">Get my link</button>
            </form>
            <div id="msg"></div>
            <a class="home" href="https://primedpeptides.co.uk/">← Back to Primed Peptides</a>
        </div>
        <script>
        document.getElementById('f').addEventListener('submit', function (e) {
            e.preventDefault();
            var email = this.email.value;
            var msg = document.getElementById('msg');
            msg.textContent = 'Sending…';
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=primed_referral_card_signup&email=' + encodeURIComponent(email)
            }).then(function (r) { return r.json(); }).then(function (d) {
                msg.textContent = d.data.message;
            }).catch(function () {
                msg.textContent = 'Something went wrong — please try again.';
            });
        });
        </script>
    </body>
    </html>
    <?php
    exit;
});

add_action('wp_ajax_primed_referral_card_signup', 'primed_referral_card_signup');
add_action('wp_ajax_nopriv_primed_referral_card_signup', 'primed_referral_card_signup');
function primed_referral_card_signup() {
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!$email || !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }

    $code = primed_ensure_referral_code($email, 'card');
    if (!$code) {
        wp_send_json_error(['message' => 'Something went wrong — please try again in a moment.']);
    }

    wp_send_json_success([
        'message' => "You're set! Your link: primedpeptides.co.uk/?ref={$code} — we've also emailed it to you.",
        'code'    => $code,
    ]);
}

// ── ?invite=1&code=X — lets a referrer send a friend the genuine branded email directly ──
// Reuses primed_referral_email_html() so the friend's invite is really the same template as
// the referrer's own email, not a second one to keep visually in sync by hand.
add_action('template_redirect', function() {
    if (!isset($_GET['invite']) || empty($_GET['code'])) return;
    nocache_headers();
    $code = sanitize_text_field(wp_unslash($_GET['code']));
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invite a Friend — Primed Peptides</title>
        <style>
            body { background:#14181f; color:#fff; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif; margin:0; padding:40px 20px; display:flex; min-height:100vh; align-items:center; justify-content:center; }
            .box { max-width:420px; text-align:center; }
            .wordmark { margin:0 0 34px; }
            .wordmark img { width:340px; max-width:90%; height:auto; display:block; margin:0 auto; }
            h1 { font-size:24px; margin:0 0 10px; }
            p { color:#b7c2d1; font-size:15px; line-height:1.5; margin:0 0 26px; }
            form { display:flex; gap:8px; flex-wrap:wrap; justify-content:center; }
            input { padding:12px 14px; border-radius:6px; border:1px solid #333; min-width:240px; font-size:15px; }
            button { background:#76b3f8; color:#0d1117; border:none; padding:12px 22px; border-radius:6px; cursor:pointer; font-weight:600; font-size:15px; }
            #msg { margin-top:18px; font-size:14px; color:#9fd0ff; }
            a.home { display:inline-block; margin-top:30px; color:#6b7280; font-size:13px; }
        </style>
    </head>
    <body>
        <div class="box">
            <?php
            $logo_path = get_stylesheet_directory() . '/images/logo-primed.png';
            $logo_url  = get_stylesheet_directory_uri() . '/images/logo-primed.png';
            if (file_exists($logo_path)) $logo_url .= '?v=' . filemtime($logo_path);
            ?>
            <p class="wordmark"><img src="<?php echo esc_url( $logo_url ); ?>" alt="Primed Peptides"></p>
            <h1>Send your friend the offer</h1>
            <p>Enter their email and we'll send them the same invite you got — 15% off their first order.</p>
            <form id="f">
                <input type="email" name="friend_email" required placeholder="friend@example.com">
                <button type="submit">Send invite</button>
            </form>
            <div id="msg"></div>
            <a class="home" href="https://primedpeptides.co.uk/">← Back to Primed Peptides</a>
        </div>
        <script>
        document.getElementById('f').addEventListener('submit', function (e) {
            e.preventDefault();
            var friendEmail = this.friend_email.value;
            var msg = document.getElementById('msg');
            msg.textContent = 'Sending…';
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=primed_send_friend_invite&code=<?php echo rawurlencode($code); ?>&friend_email=' + encodeURIComponent(friendEmail)
            }).then(function (r) { return r.json(); }).then(function (d) {
                msg.textContent = d.data.message;
            }).catch(function () {
                msg.textContent = 'Something went wrong — please try again.';
            });
        });
        </script>
    </body>
    </html>
    <?php
    exit;
});

add_action('wp_ajax_primed_send_friend_invite', 'primed_send_friend_invite');
add_action('wp_ajax_nopriv_primed_send_friend_invite', 'primed_send_friend_invite');
function primed_send_friend_invite() {
    $code         = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';
    $friend_email = isset($_POST['friend_email']) ? sanitize_email(wp_unslash($_POST['friend_email'])) : '';
    if (!$friend_email || !is_email($friend_email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }
    $owner_email = $code ? primed_find_referral_owner($code) : null;
    if (!$owner_email) {
        wp_send_json_error(['message' => 'That referral link looks invalid — please use the link from your own email.']);
    }
    // No self-invite - the referrer can't use this to top up their own reward count.
    if (strtolower($friend_email) === strtolower($owner_email)) {
        wp_send_json_error(['message' => "That's your own email — send it to a friend instead!"]);
    }

    $body = '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#333;">A friend thought you\'d like this — use their code below for 15% off your first order at Primed Peptides.</p>' .
        primed_referral_code_box($code);

    primed_send_referral_email(
        $friend_email,
        "You've been invited to Primed Peptides — 15% off",
        "You've been invited! 15% off your first order",
        $body
    );

    wp_send_json_success(['message' => "Sent! We've emailed {$friend_email} your offer."]);
}

// Reward the original referrer once a friend's order using their code is confirmed paid.
function primed_reward_referrer_if_applicable($order) {
    if ($order->get_meta('_primed_referral_rewarded')) return;

    foreach ($order->get_coupon_codes() as $used_code) {
        $owner_email = primed_find_referral_owner($used_code);
        if (!$owner_email) continue;
        if (strtolower($owner_email) === strtolower($order->get_billing_email())) continue; // no self-reward

        $reward_code = primed_generate_unique_code('THANKS-');
        if (!primed_create_referral_coupon($reward_code, 1, $owner_email, true)) continue;

        $reward_body = '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#333;">Someone you referred to Primed Peptides just placed an order. As a thank you, here\'s a fresh 15% off code for your own next order — just enter it at checkout.</p>' .
            '<div style="background:#e8f0fc;border:1px solid #c9dbf5;border-radius:8px;padding:18px;text-align:center;margin:0 0 24px;">' .
                '<div style="font-size:12px;color:#4a7fd4;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Your reward code</div>' .
                '<div style="font-size:22px;font-weight:700;color:#14181f;letter-spacing:0.02em;">' . esc_html($reward_code) . '</div>' .
            '</div>' .
            '<div style="text-align:center;">' .
                '<a href="https://primedpeptides.co.uk/" style="display:inline-block;background:#76b3f8;color:#0d1117;text-decoration:none;font-weight:600;padding:12px 28px;border-radius:6px;font-size:15px;">Shop now</a>' .
            '</div>';

        primed_send_referral_email(
            $owner_email,
            "Your friend just ordered — here's your 15% off",
            "Your friend just ordered!",
            $reward_body
        );
        $order->add_order_note("Referral reward issued to {$owner_email} ({$reward_code}) for referring this order.");
        $order->update_meta_data('_primed_referral_rewarded', 1);
        $order->save();
        break; // only reward once per order, even if somehow more than one referral code applied
    }
}

add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status, $order) {
    if (!in_array($new_status, ['processing', 'completed'], true)) return;
    if (in_array($old_status, ['processing', 'completed'], true)) return; // already handled on an earlier transition

    $email = $order->get_billing_email();
    if ($email) primed_ensure_referral_code($email);

    primed_reward_referrer_if_applicable($order);
}, 10, 4);

// Block a customer using their own referral code on their own order — WooCommerce's documented
// way to invalidate a coupon via this filter is to return false (shows its own generic error);
// returning false rather than throwing keeps this on the well-supported path.
add_filter('woocommerce_coupon_is_valid', function($valid, $coupon) {
    $owner = $coupon->get_meta('_primed_referral_owner');
    if (!$owner) return $valid;
    $checkout_email = WC()->customer ? WC()->customer->get_billing_email() : '';
    if ($checkout_email && strtolower($checkout_email) === strtolower($owner)) {
        return false;
    }
    return $valid;
}, 10, 2);

// ── Newsletter signup — 10% off first order (WELCOME10 coupon) ──
// Adds the email to the real Mailchimp audience (list 8848d51eea, "Primed Peptides") via
// PUT /members/{hash} (upsert — safe to submit the same email twice). If the visitor arrived
// via a partner/affiliate ?ref= link, tags the subscriber with who referred them, so Danny/Tom's
// business-card referrals show up in Mailchimp even for people who sign up without ordering yet.
// Real key lives in wp-config.php on the server, never in this git-tracked file.
if (!defined('PRIMED_MAILCHIMP_KEY')) {
    define('PRIMED_MAILCHIMP_KEY', '');
}
define('PRIMED_MAILCHIMP_LIST', '8848d51eea');

add_action('wp_ajax_primed_newsletter_signup', 'primed_newsletter_signup');
add_action('wp_ajax_nopriv_primed_newsletter_signup', 'primed_newsletter_signup');
function primed_newsletter_signup() {
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!$email || !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }
    if (!PRIMED_MAILCHIMP_KEY) {
        wp_send_json_error(['message' => 'Something went wrong — please try again in a moment.']);
    }

    $dc   = explode('-', PRIMED_MAILCHIMP_KEY)[1];
    $hash = md5(strtolower($email));
    $tags = ['Website Signup'];

    $ref = isset($_COOKIE['primed_ref']) ? sanitize_text_field($_COOKIE['primed_ref']) : '';
    if ($ref) {
        $config = primed_referral_config();
        if (isset($config[$ref])) {
            $tags[] = 'Referred by ' . $config[$ref]['name'];
        }
    }

    $response = wp_remote_request(
        "https://{$dc}.api.mailchimp.com/3.0/lists/" . PRIMED_MAILCHIMP_LIST . "/members/{$hash}",
        [
            'method'  => 'PUT',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('anystring:' . PRIMED_MAILCHIMP_KEY),
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode([
                'email_address' => $email,
                'status_if_new' => 'subscribed',
                'tags'          => $tags,
            ]),
            'timeout' => 15,
        ]
    );

    if (is_wp_error($response) || !in_array(wp_remote_retrieve_response_code($response), [200, 201], true)) {
        wp_send_json_error(['message' => 'Something went wrong — please try again in a moment.']);
    }

    // Real gap found 2026-08-20 (Rupert): WELCOME10 only ever appeared once, in the on-page
    // success message — if someone didn't screenshot it, it was gone for good. Sends the same
    // branded template the referral/review emails already use, so the code has a permanent home
    // in the subscriber's inbox instead of relying on them to note it down in the moment.
    primed_send_referral_email(
        $email,
        'Here\'s your 10% off code — Primed Peptides',
        "You're in!",
        '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#333;">Thanks for joining our list — here\'s your code for 10% off your first order, whenever you\'re ready.</p>' .
        '<div style="background:#e8f0fc;border:1px solid #c9dbf5;border-radius:8px;padding:18px;text-align:center;margin:0 0 24px;">' .
            '<div style="font-size:12px;color:#4a7fd4;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Your 10% off code</div>' .
            '<div style="font-size:22px;font-weight:700;color:#14181f;letter-spacing:0.02em;">WELCOME10</div>' .
        '</div>'
    );

    wp_send_json_success([
        'message' => "You're in! Use code WELCOME10 at checkout for 10% off your first order.",
        'coupon'  => 'WELCOME10',
    ]);
}

// Dismissible signup bar — shown once per browser after a short delay, never shown again once
// dismissed or subscribed (localStorage, not a hard server-side gate — deliberately simple).
add_action('wp_footer', function() {
    ?>
    <div id="primed-signup-bar" style="display:none;position:fixed;left:0;right:0;bottom:0;z-index:9999;background:#14181f;color:#fff;padding:14px 44px 14px 20px;font-family:inherit;font-size:14px;box-shadow:0 -2px 12px rgba(0,0,0,.2);">
      <div style="max-width:960px;margin:0 auto;display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;">
        <span style="white-space:nowrap;">Get <strong>10% off</strong> your first order — join our list</span>
        <form id="primed-signup-form" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <input type="email" name="email" required placeholder="you@example.com"
                 style="padding:9px 12px;border-radius:5px;border:1px solid #333;min-width:220px;font-size:14px;">
          <button type="submit"
                  style="background:#76b3f8;color:#0d1117;border:none;padding:9px 18px;border-radius:5px;cursor:pointer;font-weight:600;font-size:14px;">
            Get 10% off
          </button>
        </form>
        <span id="primed-signup-msg" style="font-size:13px;color:#9fd0ff;"></span>
      </div>
      <button type="button" aria-label="Dismiss"
              onclick="document.getElementById('primed-signup-bar').style.display='none';localStorage.setItem('primed_signup_dismissed','1');"
              style="position:absolute;right:12px;top:10px;background:none;border:none;color:#888;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
    </div>
    <style>
      @media (max-width: 640px) {
        #primed-signup-bar .primed-row { flex-direction: column; }
      }
    </style>
    <script>
    (function () {
      if (localStorage.getItem('primed_signup_dismissed') || localStorage.getItem('primed_signup_subscribed')) return;
      var bar = document.getElementById('primed-signup-bar');
      setTimeout(function () { bar.style.display = 'block'; }, 4000);
      document.getElementById('primed-signup-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var email = this.email.value;
        var msg = document.getElementById('primed-signup-msg');
        msg.textContent = 'Sending…';
        fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=primed_newsletter_signup&email=' + encodeURIComponent(email)
        }).then(function (r) { return r.json(); }).then(function (d) {
          msg.textContent = d.data.message;
          if (d.success) localStorage.setItem('primed_signup_subscribed', '1');
        }).catch(function () {
          msg.textContent = 'Something went wrong — please try again.';
        });
      });
    })();
    </script>
    <?php
});

// ── Real contact form on the Contact page ──
// The page itself only ever had a bare mailto: link (no form). Injected via a
// content filter rather than editing the page's own content, since there's no
// WP-admin/REST write access available for pages - this needs no page edit at all.
add_filter('the_content', function($content) {
    if (!is_page('contact')) return $content;
    ob_start();
    ?>
    <div style="max-width:480px;margin:24px 0;padding:18px 20px;background:#f6f7f9;border-radius:6px;">
        <p style="margin:0 0 8px;"><strong>Phone:</strong> <a href="tel:+442080643073">020 8064 3073</a></p>
        <p style="margin:0 0 8px;"><strong>Email:</strong> <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a></p>
        <p style="margin:0;"><strong>Registered office:</strong> 71-75 Shelton Street, Covent Garden, London, WC2H 9JQ</p>
    </div>
    <form id="primed-contact-form" style="max-width:480px;margin:32px 0 0;">
        <p style="margin-bottom:14px;">
            <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600;">Name</label>
            <input type="text" name="name" required style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:4px;">
        </p>
        <p style="margin-bottom:14px;">
            <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600;">Email</label>
            <input type="email" name="email" required style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:4px;">
        </p>
        <p style="margin-bottom:14px;">
            <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600;">How did you hear about us? (optional)</label>
            <input type="text" name="source" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:4px;">
        </p>
        <p style="margin-bottom:14px;">
            <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:600;">Message</label>
            <textarea name="message" required rows="5" style="width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:4px;"></textarea>
        </p>
        <button type="submit" style="padding:12px 28px;background:#1c1e2a;color:#fff;border:none;border-radius:4px;font-weight:600;cursor:pointer;">Send message</button>
        <span id="primed-contact-msg" style="display:block;margin-top:10px;font-size:14px;"></span>
    </form>
    <script>
    (function() {
        var form = document.getElementById('primed-contact-form');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var msg = document.getElementById('primed-contact-msg');
            var btn = form.querySelector('button');
            btn.disabled = true;
            msg.textContent = 'Sending...';
            var data = new FormData(form);
            data.append('action', 'primed_contact_form');
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    msg.textContent = d.data.message;
                    btn.disabled = false;
                    if (d.success) form.reset();
                })
                .catch(function() {
                    msg.textContent = 'Something went wrong - please try again or email us directly.';
                    btn.disabled = false;
                });
        });
    })();
    </script>
    <?php
    return $content . ob_get_clean();
});

// ── Real Privacy Policy, replacing WordPress's default comments/cookies template ──
// The page previously showed the unedited WP-core default (blog-comment retention,
// nothing about actual customer/order data) - genuinely misleading for an ecommerce
// site. Replaces the content entirely, same no-page-edit-access pattern as the
// Contact page filter above, rather than trying to edit the underlying WP Page.
add_filter('the_content', function($content) {
    if (!is_page('privacy-policy')) return $content;
    ob_start();
    ?>
    <p><em>Last updated: August 2026</em></p>

    <h2>Who we are</h2>
    <p>This website is operated by Premium Wellness Ltd, trading as Primed Peptides ("we", "us", "our"). Our registered office is 71-75 Shelton Street, Covent Garden, London, WC2H 9JQ. You can contact us at <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> or 020 8064 3073.</p>

    <h2>What information we collect</h2>
    <p>When you place an order or contact us, we collect your name, email address, delivery and billing address, phone number, and order details. If you sign up to our newsletter, we collect your email address. We do not collect or store your payment card details - these are handled directly by our payment provider.</p>

    <h2>How we use your information</h2>
    <p>We use your information to process and fulfil your orders, communicate with you about your order, respond to enquiries, and - only if you've opted in - send you marketing emails about new products and offers. You can unsubscribe from marketing emails at any time using the link in any email we send.</p>

    <h2>Our legal basis for processing</h2>
    <p>We process your order and account information because it's necessary to fulfil a contract with you. We send marketing emails only with your consent. We may process limited information on the basis of legitimate interest, for example to prevent fraud or keep our systems secure.</p>

    <h2>Who we share your information with</h2>
    <p>We share order and delivery details with our warehousing and fulfilment partner and with the courier used to deliver your order (name, address, and contact details only). If you pay by card or PayPal, your payment details are shared directly with our payment provider, who processes them under their own privacy policy. If you subscribe to our newsletter, your email address is held by our email marketing provider. We never sell your personal information to third parties.</p>

    <h2>International transfers</h2>
    <p>Some of the providers we use (for example our email marketing and payment providers) may process data outside the UK. Where this happens, we rely on providers who maintain appropriate safeguards, such as Standard Contractual Clauses.</p>

    <h2>How long we keep your information</h2>
    <p>We keep order and transaction records for as long as required for accounting and tax purposes (normally six years). Marketing contact details are kept until you unsubscribe or ask us to delete them.</p>

    <h2>Cookies</h2>
    <p>We use essential cookies to operate the shopping cart and checkout. If you sign up to our newsletter or dismiss a pop-up, we may also store a small preference cookie so we don't show it again.</p>

    <h2>Your rights</h2>
    <p>Under UK data protection law, you have the right to access the information we hold about you, ask us to correct or delete it, restrict or object to how we use it, and request a copy in a portable format. To exercise any of these rights, contact us at <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a>. You also have the right to complain to the Information Commissioner's Office (ico.org.uk) if you're unhappy with how we've handled your information.</p>

    <h2>Age restriction</h2>
    <p>Our products are intended for use by qualified researchers aged 18 and over, and this website is not intended for use by anyone under 18. We do not knowingly collect personal information from anyone under 18.</p>

    <h2>Changes to this policy</h2>
    <p>We may update this policy from time to time. Any changes will be posted on this page.</p>

    <h2>Contact</h2>
    <p><a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> · 020 8064 3073 · 71-75 Shelton Street, Covent Garden, London, WC2H 9JQ</p>
    <?php
    return ob_get_clean();
});

add_action('wp_ajax_primed_contact_form', 'primed_contact_form_handler');
add_action('wp_ajax_nopriv_primed_contact_form', 'primed_contact_form_handler');
function primed_contact_form_handler() {
    $name    = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email   = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $source  = isset($_POST['source']) ? sanitize_text_field(wp_unslash($_POST['source'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    if (!$name || !$email || !is_email($email) || !$message) {
        wp_send_json_error(['message' => 'Please fill in your name, a valid email, and a message.']);
    }

    $body = "New contact form enquiry from primedpeptides.co.uk\n\n"
          . "Name: {$name}\nEmail: {$email}\n"
          . ($source ? "How they heard about us: {$source}\n" : "")
          . "\nMessage:\n{$message}";

    $sent = wp_mail(
        'info@primedpeptides.co.uk',
        'Contact form enquiry - ' . $name,
        $body,
        ['From: Primed Peptides <info@primedpeptides.co.uk>', 'Reply-To: ' . $email]
    );

    if ($sent) {
        wp_send_json_success(['message' => "Thanks {$name} - we'll get back to you shortly."]);
    } else {
        wp_send_json_error(['message' => 'Something went wrong sending your message - please email us directly at info@primedpeptides.co.uk.']);
    }
}

// ── Trustpilot review link on the completed-order email ──
// Rupert's real, claimed Trustpilot profile: https://uk.trustpilot.com/review/primedpeptides.co.uk
// (verified live 2026-08-19 — the profile itself is real and loads correctly, even though it
// currently shows 0 published reviews despite memory noting one received 2026-08-16; that
// review either never posted, is still in moderation, or went to a different platform — worth
// checking directly on Trustpilot, but doesn't affect whether this link itself is correct).
// Attached to "completed" specifically, not "processing" — asking for a review only makes sense
// once the customer has actually received the product, and completed is the status Rupert/Danny
// set by hand once it's shipped (see the 2026-08-18 fulfillment-process notes in memory).
add_action('woocommerce_email_after_order_table', function($order, $sent_to_admin, $plain_text, $email) {
    if ($sent_to_admin || $plain_text) return;
    if (!$email || $email->id !== 'customer_completed_order') return;
    echo '<p style="margin:24px 0 0;padding:16px;background:#f8f9fb;border-radius:8px;text-align:center;font-family:Arial,Helvetica,sans-serif;">';
    echo 'Enjoying your order? A quick review on Trustpilot really helps us out: ';
    echo '<a href="https://uk.trustpilot.com/review/primedpeptides.co.uk" style="color:#4a7fd4;font-weight:600;">Leave a review</a>';
    echo '</p>';
}, 20, 4);

// ── Automated review-request emails: day 7 + day 21 after order completion ──
// From the original go-live brief (Section 02, Email Marketing & Reviews): "Automated email
// at day 7 and day 21 after purchase asking for a Google/Trustpilot review. Include a 10%
// discount code on next order as incentive." Runs off a daily WP-Cron check against orders
// that hit exactly N days completed today, rather than scheduling a one-off event per order -
// self-heals if the site's down for a day, same reasoning as the ChemResearch monitor's daily
// stock check. Reuses the branded-email helpers already built for the referral system.
add_action('init', function() {
    if (!wp_next_scheduled('primed_review_email_daily_check')) {
        wp_schedule_event(time(), 'daily', 'primed_review_email_daily_check');
    }
});
add_action('primed_review_email_daily_check', 'primed_run_review_request_emails');

function primed_run_review_request_emails() {
    primed_send_review_request_for_stage(
        7,
        '_primed_review_email_7d_sent',
        'How are you finding it?',
        '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#333;">It\'s been a week since your order — hope it\'s going well! If you\'ve got a moment, a quick review really helps other customers trust us.</p>'
    );
    primed_send_review_request_for_stage(
        21,
        '_primed_review_email_21d_sent',
        'Still enjoying it?',
        '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#333;">It\'s been a few weeks now — if you haven\'t already, we\'d really appreciate a quick review. As a thank you, here\'s 10% off your next order.</p>'
    );
}

function primed_send_review_request_for_stage($days_after, $meta_flag, $subject, $intro_html) {
    $target = new DateTime("-{$days_after} days", new DateTimeZone('UTC'));
    $start  = (clone $target)->setTime(0, 0, 0)->getTimestamp();
    $end    = (clone $target)->setTime(23, 59, 59)->getTimestamp();

    $orders = wc_get_orders([
        'status'         => 'completed',
        'date_completed' => $start . '...' . $end,
        'limit'          => -1,
    ]);

    foreach ($orders as $order) {
        if ($order->get_meta($meta_flag)) continue;

        $email = $order->get_billing_email();
        if (!$email) { $order->update_meta_data($meta_flag, 1); $order->save(); continue; }

        // Single-use, locked to this customer's email - matches the pattern already used for
        // THANKS- referral reward coupons above, not a shareable/guessable code.
        $code      = primed_generate_unique_code('REVIEW-');
        $coupon_id = wp_insert_post(['post_title' => $code, 'post_status' => 'publish', 'post_type' => 'shop_coupon']);
        if ($coupon_id && !is_wp_error($coupon_id)) {
            update_post_meta($coupon_id, 'discount_type', 'percent');
            update_post_meta($coupon_id, 'coupon_amount', '10');
            update_post_meta($coupon_id, 'individual_use', 'yes');
            update_post_meta($coupon_id, 'usage_limit', 1);
            update_post_meta($coupon_id, 'customer_email', [$email]);
        }

        $body = $intro_html .
            '<div style="text-align:center;margin:0 0 20px;">' .
                '<a href="https://uk.trustpilot.com/review/primedpeptides.co.uk" style="display:inline-block;background:#76b3f8;color:#0d1117;text-decoration:none;font-weight:600;padding:12px 28px;border-radius:6px;font-size:15px;">Leave a review</a>' .
            '</div>' .
            '<div style="background:#e8f0fc;border:1px solid #c9dbf5;border-radius:8px;padding:18px;text-align:center;">' .
                '<div style="font-size:12px;color:#4a7fd4;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Your 10% off code</div>' .
                '<div style="font-size:22px;font-weight:700;color:#14181f;letter-spacing:0.02em;">' . esc_html($code) . '</div>' .
            '</div>';

        primed_send_referral_email($email, $subject, $subject, $body);

        $order->update_meta_data($meta_flag, 1);
        $order->save();
    }
}

// ── ?calculator=1 — peptide concentration/volume calculator ──
// Rupert saw Mercia Research's own version and wants it built here too (2026-08-19) - first
// piece of a wider "Knowledge Hub" (calculator, FAQ, glossary, blog), starting with this one.
// Same two-mode approach: pre-mixed cartridge (matches our own pen format, no reconstitution
// maths) and a general reconstituted-vial reference mode. Pure client-side JS - no server-side
// dosing logic, no data stored, nothing that could be construed as medical/dosing advice.
// Query-param route, not a path, same reason as ?getref=1/?invite=1 - this site's permalink
// setup doesn't reliably route arbitrary paths through to WordPress.
add_action('template_redirect', function() {
    if (!isset($_GET['calculator'])) return;
    nocache_headers();
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Peptide Calculator — Primed Peptides</title>
        <style>
            body { background:#14181f; color:#fff; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif; margin:0; padding:40px 20px; min-height:100vh; }
            .wrap { max-width:520px; margin:0 auto; }
            .wordmark { display:block; text-align:center; margin:0 0 30px; text-decoration:none; }
            .wordmark img { width:96px; height:auto; display:block; margin:0 auto 14px; }
            h1 { font-size:24px; margin:0 0 6px; text-align:center; }
            .sub { color:#8a92a3; font-size:14px; text-align:center; margin:0 0 30px; }
            .tabs { display:flex; gap:8px; margin-bottom:22px; background:#1c212b; border-radius:8px; padding:4px; }
            .tab { flex:1; text-align:center; padding:10px; border-radius:6px; cursor:pointer; font-size:13.5px; font-weight:600; color:#8a92a3; }
            .tab.active { background:#76b3f8; color:#0d1117; }
            .card { background:#1c212b; border:1px solid #2a3140; border-radius:10px; padding:22px; }
            .field { margin-bottom:16px; }
            label { display:block; font-size:12.5px; color:#8a92a3; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px; }
            input, select { width:100%; padding:11px 13px; border-radius:6px; border:1px solid #333; background:#0d1117; color:#fff; font-size:15px; box-sizing:border-box; }
            .row { display:flex; gap:10px; }
            .row .field { flex:1; }
            .result { margin-top:20px; padding:16px; background:#0d1117; border:1px solid #2a3140; border-radius:8px; }
            .result .label { font-size:11.5px; color:#8a92a3; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:4px; }
            .result .value { font-size:24px; font-weight:700; color:#76b3f8; }
            .result .value.dim { color:#4a5064; }
            .note { font-size:12px; color:#5a6272; margin-top:6px; }
            .disclaimer { margin-top:24px; padding:14px 16px; background:#1c212b; border-left:3px solid #4a5064; border-radius:6px; font-size:12.5px; color:#8a92a3; line-height:1.5; }
            a.home { display:block; text-align:center; margin-top:26px; color:#6b7280; font-size:13px; }
        </style>
    </head>
    <body>
        <div class="wrap">
            <?php
            $logo_path = get_stylesheet_directory() . '/images/logo-primed.png';
            $logo_url  = get_stylesheet_directory_uri() . '/images/logo-primed.png';
            if (file_exists($logo_path)) $logo_url .= '?v=' . filemtime($logo_path);
            ?>
            <a href="https://primedpeptides.co.uk/" class="wordmark"><img src="<?php echo esc_url($logo_url); ?>" alt="Primed Peptides"></a>
            <h1>Peptide Calculator</h1>
            <p class="sub">Concentration &amp; volume reference for research use</p>

            <div class="tabs">
                <div class="tab active" data-mode="cartridge">Pre-Mixed Cartridge</div>
                <div class="tab" data-mode="vial">Reconstituted Vial</div>
            </div>

            <div class="card">
                <div id="mode-cartridge">
                    <p class="note" style="margin:0 0 16px;">For our own pre-mixed liquid cartridges — no reconstitution involved. Enter the total peptide mass and solution volume printed on the cartridge.</p>
                    <div class="row">
                        <div class="field"><label>Total peptide mass (mg)</label><input type="number" id="c-mass" step="any" placeholder="e.g. 20"></div>
                        <div class="field"><label>Solution volume (mL)</label><input type="number" id="c-vol" step="any" placeholder="e.g. 3"></div>
                    </div>
                    <div class="result">
                        <div class="label">Concentration</div>
                        <div class="value dim" id="c-conc">—</div>
                    </div>
                    <div class="field" style="margin-top:18px;">
                        <label>Quantity to calculate volume for</label>
                        <div class="row">
                            <input type="number" id="c-qty" step="any" placeholder="e.g. 250">
                            <select id="c-unit" style="max-width:130px;">
                                <option value="mcg">mcg</option>
                                <option value="mg">mg</option>
                            </select>
                        </div>
                    </div>
                    <div class="result">
                        <div class="label">Volume containing that quantity</div>
                        <div class="value dim" id="c-result">—</div>
                    </div>
                </div>

                <div id="mode-vial" style="display:none;">
                    <p class="note" style="margin:0 0 16px;">General reference for a reconstituted lyophilised vial from any source. Enter the vial's peptide mass and how much water/diluent was added.</p>
                    <div class="row">
                        <div class="field"><label>Vial peptide mass (mg)</label><input type="number" id="v-mass" step="any" placeholder="e.g. 5"></div>
                        <div class="field"><label>Water/diluent added (mL)</label><input type="number" id="v-vol" step="any" placeholder="e.g. 2"></div>
                    </div>
                    <div class="result">
                        <div class="label">Concentration</div>
                        <div class="value dim" id="v-conc">—</div>
                    </div>
                    <div class="field" style="margin-top:18px;">
                        <label>Quantity to calculate volume for</label>
                        <div class="row">
                            <input type="number" id="v-qty" step="any" placeholder="e.g. 250">
                            <select id="v-unit" style="max-width:130px;">
                                <option value="mcg">mcg</option>
                                <option value="mg">mg</option>
                            </select>
                        </div>
                    </div>
                    <div class="result">
                        <div class="label">Volume containing that quantity</div>
                        <div class="value dim" id="v-result">—</div>
                    </div>
                </div>
            </div>

            <div class="disclaimer">
                This tool performs a concentration/volume calculation only. It does not provide dosing recommendations, is not medical advice, and all products are supplied for laboratory research use only — not for human or animal consumption.
            </div>

            <a class="home" href="https://primedpeptides.co.uk/">← Back to Primed Peptides</a>
        </div>

        <script>
        document.querySelectorAll('.tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.tab').forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                var mode = tab.dataset.mode;
                document.getElementById('mode-cartridge').style.display = mode === 'cartridge' ? 'block' : 'none';
                document.getElementById('mode-vial').style.display = mode === 'vial' ? 'block' : 'none';
            });
        });

        function wireMode(prefix) {
            var mass = document.getElementById(prefix + '-mass');
            var vol  = document.getElementById(prefix + '-vol');
            var qty  = document.getElementById(prefix + '-qty');
            var unit = document.getElementById(prefix + '-unit');
            var concOut = document.getElementById(prefix + '-conc');
            var resOut  = document.getElementById(prefix + '-result');

            function recalc() {
                var m = parseFloat(mass.value);
                var v = parseFloat(vol.value);
                if (!(m > 0) || !(v > 0)) {
                    concOut.textContent = '—';
                    concOut.classList.add('dim');
                    resOut.textContent = '—';
                    resOut.classList.add('dim');
                    return;
                }
                var conc = m / v; // mg/mL
                concOut.textContent = conc.toFixed(3) + ' mg/mL';
                concOut.classList.remove('dim');

                var q = parseFloat(qty.value);
                if (!(q > 0)) {
                    resOut.textContent = '—';
                    resOut.classList.add('dim');
                    return;
                }
                var qMg = unit.value === 'mcg' ? q / 1000 : q;
                var volumeNeeded = qMg / conc; // mL
                resOut.textContent = volumeNeeded.toFixed(4) + ' mL';
                resOut.classList.remove('dim');
            }

            [mass, vol, qty, unit].forEach(function (el) { el.addEventListener('input', recalc); });
        }

        wireMode('c');
        wireMode('v');
        </script>
    </body>
    </html>
    <?php
    exit;
});
