<?php
/**
 * Site-wide FAQ for primedpeptides.co.uk (/faq/)
 *
 * Added 2026-08-30. Premium has a real FAQ page; this site had none - only the
 * per-product accordion added the same day, which deliberately covers only
 * product-level questions. This one answers the site-level things a first-time
 * buyer actually asks: payment, delivery, returns, and what RUO means for them.
 *
 * Virtual page via template_redirect, same technique and same reason as /coa/
 * and /articles/ - no WP-admin or REST write access on this site.
 *
 * Editorial rules, same as the rest of the site:
 *  - No dosing, reconstitution, administration or "how long until results"
 *    content. Premium's FAQ answers those because it's a consumer brand; this
 *    site is Research Use Only and answering them here would contradict the
 *    disclaimer carried on every product page.
 *  - The "can I buy these for personal use" question is answered honestly
 *    rather than dodged - it's the question this category gets most, and a
 *    straight answer is better for trust than a vague one.
 *  - Every factual answer restates something already published on this site
 *    (99%+ purity, third-party testing, £4.95/free over £100, 14-day returns,
 *    Tracked 24, PayPal/card). Nothing invented.
 */

defined('ABSPATH') || exit;

function primed_faq_sections() {
    return [
        'Ordering &amp; Delivery' => [
            [
                'How quickly do you dispatch?',
                'Orders placed before 2pm are dispatched the same day, sent Royal Mail Tracked 24.',
            ],
            [
                'How much is delivery?',
                '&pound;4.95 within the UK, and <strong>free on orders over &pound;100</strong>.',
            ],
            [
                'Do you ship outside the UK?',
                'Not at the moment &mdash; we currently ship within the United Kingdom only.',
            ],
            [
                'How do I pay?',
                'PayPal or credit/debit card at checkout. You don\'t need a PayPal account to pay by card.',
            ],
            [
                'Can I return an order?',
                'Yes &mdash; claims must be raised within 14 days of delivery by emailing <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> with your order number. We can\'t accept returns on opened or used items under any circumstances, as these are research materials requiring controlled storage. Full detail on our <a href="/refund_returns/">returns page</a>.',
            ],
        ],

        'Quality &amp; Testing' => [
            [
                'Is your material third-party tested?',
                'Every batch is produced with a Certificate of Analysis confirming purity (minimum 99%) and identity before release. Independent third-party verification through Janoshik Analytical &mdash; a laboratory entirely separate from manufacturing &mdash; is available on request for any batch.',
            ],
            [
                'What does "99%+ purity" actually mean?',
                'It\'s the target compound\'s share of the sample as measured by HPLC. We\'ve written a fuller explanation of what that figure does and doesn\'t tell you in <a href="/articles/what-does-99-percent-purity-mean/">this article</a>.',
            ],
            [
                'How do I get the Certificate of Analysis for my batch?',
                'Scan the QR code on your product, or visit our <a href="/coa/">Certificate of Analysis page</a>. If your batch isn\'t listed yet, email <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> with the product name and batch number and we\'ll send it directly.',
            ],
            [
                'What does "stabilised cartridge" mean?',
                'Our material ships as a stabilised formulation in a cartridge, designed for a 12-month shelf life when stored as directed, rather than as a raw lyophilised powder.',
            ],
            [
                'How should I store it?',
                'Cool, dry, away from direct sunlight, and follow the specific instructions on your product\'s packaging. There\'s more detail on what actually degrades peptides in <a href="/articles/how-to-store-research-peptides/">this article</a>.',
            ],
        ],

        'Research Use Only' => [
            [
                'What does "Research Use Only" mean?',
                'All products sold by Primed Peptides are supplied for laboratory and research use only &mdash; not for human or veterinary use. They are not medicines, have not been through the approval process a medicine goes through, and are sold on that explicit basis.',
            ],
            [
                'Can I buy these for personal use?',
                'No. Our material is sold for laboratory and research use only. We don\'t supply it for personal use, and we don\'t provide dosing, preparation or administration guidance of any kind.',
            ],
            [
                'Do you give advice on combining products?',
                'No &mdash; for the same reason as above. Our written material covers what these compounds are, how to store them, and how their quality is verified, not how to use them.',
            ],
            [
                'Do I need to be over 18 to order?',
                'Yes. You must be 18 or over to purchase from this website.',
            ],
        ],
    ];
}

add_action('template_redirect', function() {
    $path = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path !== '/faq') return;

    status_header(200);
    global $wp_query;
    $wp_query->is_404 = false;

    get_header();
    ?>
    <div class="woocommerce-page"><div class="woocommerce articles-wrap">
        <h1>Frequently Asked Questions</h1>
        <p class="articles-intro">Ordering, delivery, how our material is tested, and what Research Use Only means. Anything not covered here &mdash; just <a href="/contact/">get in touch</a>.</p>

        <?php foreach (primed_faq_sections() as $section => $items): ?>
            <div class="faq-section">
                <h2><?php echo $section; ?></h2>
                <?php foreach ($items as $qa): ?>
                    <details>
                        <summary><?php echo $qa[0]; ?></summary>
                        <p><?php echo $qa[1]; ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <p class="article-ruo" style="margin-top:40px;"><strong>Research Use Only.</strong> All products sold by Primed Peptides are intended for laboratory and research use only, not for human or veterinary use.</p>
    </div></div>
    <?php
    get_footer();
    exit;
});

add_filter('document_title_parts', function($parts) {
    $path = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path === '/faq') $parts['title'] = 'FAQ';
    return $parts;
});
