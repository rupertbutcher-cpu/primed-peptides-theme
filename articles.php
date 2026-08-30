<?php
/**
 * Articles hub for primedpeptides.co.uk
 *
 * Added 2026-08-30. Built as theme-defined content rendered through virtual
 * pages (template_redirect), NOT as WordPress posts - same technique and same
 * reason as the /coa/ page: there's no WP-admin or REST write access on this
 * site, so real posts can't be created programmatically. Keeping the content
 * here means it's version-controlled in git and deploys with the theme.
 *
 * Editorial rules these articles follow, deliberately:
 *  - Nothing about dosing, reconstitution volumes, administration, or protocols.
 *    These are RUO products ("not for human or veterinary use") and a usage
 *    guide would contradict that disclaimer. Checked a real UK competitor
 *    (peptonline.com) before writing - they omit the same things for the same
 *    reason. Topics here are chemistry, handling, and quality/verification.
 *  - No efficacy or health claims. Where research exists it's described as
 *    "studied for", matching the product-page house style already in use.
 *  - Every factual claim is either general biochemistry or something already
 *    published on this site (99%+ purity, HPLC testing, 12-month shelf life).
 *
 * If WP-admin access ever becomes available, these are straightforward to
 * migrate into real posts - the structure maps 1:1 onto title/slug/excerpt/
 * category/content.
 */

defined('ABSPATH') || exit;

function primed_articles() {
    return [
        'what-are-research-peptides' => [
            'title'    => 'What Are Research Peptides?',
            'category' => 'Peptide Basics',
            'excerpt'  => 'A plain-English primer on what peptides actually are, how they differ from proteins, and what "research grade" means.',
            'read'     => '4 min read',
            'date'     => '2026-08-30',
            'body'     => <<<'HTML'
<p>Peptides are short chains of amino acids &mdash; the same building blocks that make up proteins &mdash; joined together by peptide bonds. The distinction between a peptide and a protein is essentially one of length. Chains of roughly two to fifty amino acids are generally called peptides; longer chains fold into the more complex structures we call proteins.</p>

<p>That short length matters. Because peptides are small and structurally simple compared to proteins, they can be manufactured synthetically with a high degree of precision, and their sequence can be verified analytically. This is what makes them useful in a laboratory setting: a researcher can order a specific, known sequence and have reasonable confidence they are working with exactly that.</p>

<h2>Naturally occurring vs. synthetic</h2>

<p>Many peptides occur naturally in the body, where they act as signalling molecules &mdash; instructions passed between cells. Insulin, for example, is a peptide hormone. Research peptides are synthetic versions of these naturally occurring sequences, or novel sequences designed to resemble them, produced in a laboratory rather than extracted from biological material.</p>

<p>Synthesis is typically done by solid-phase peptide synthesis, a process that builds the chain one amino acid at a time on a solid support. The finished chain is then cleaved from the support and purified &mdash; and it's that purification step that separates a well-made research peptide from a poor one.</p>

<h2>What "research grade" actually means</h2>

<p>Research grade is not a formal regulatory classification, which is precisely why it's worth understanding what a supplier means by it. In practice it should mean the material has been synthesised to a stated sequence, purified, and analytically verified &mdash; with documentation to back that up.</p>

<p>The meaningful questions to ask any supplier are: what purity standard is claimed, how is it measured, and can you see the evidence for the specific batch you received? A supplier who can answer all three with a batch-specific Certificate of Analysis is telling you something concrete. One who cannot is asking you to take their word for it.</p>

<p>At Primed Peptides, every batch is tested by an independent third-party laboratory for purity and identity before release. You can <a href="/coa/">request the Certificate of Analysis for your batch</a> at any time.</p>

<h2>Research use only</h2>

<p>All products supplied by Primed Peptides are intended for laboratory and research use only &mdash; not for human or veterinary use. This isn't boilerplate: research peptides are not medicines, have not been through the approval process a medicine goes through, and are sold on that explicit basis.</p>
HTML,
        ],

        'how-to-store-research-peptides' => [
            'title'    => 'How to Store Research Peptides',
            'category' => 'Handling &amp; Storage',
            'excerpt'  => 'Temperature, light, and freeze-thaw cycles all affect peptide stability. Here is what actually degrades them, and how to avoid it.',
            'read'     => '3 min read',
            'date'     => '2026-08-30',
            'body'     => <<<'HTML'
<p>Peptides are, chemically speaking, fairly delicate. The peptide bonds holding the chain together can be broken down by heat, moisture, light, and repeated temperature cycling. Poor storage is one of the most common reasons a material that was genuinely high-purity on arrival stops behaving consistently in the lab.</p>

<h2>The main things that cause degradation</h2>

<ul>
<li><strong>Heat.</strong> Elevated temperature accelerates essentially every degradation pathway. This is the single biggest factor within your control.</li>
<li><strong>Moisture.</strong> Water enables hydrolysis, which cleaves the peptide bond. This is why peptides are typically supplied in a dry, freeze-dried (lyophilised) form or in a stabilised formulation &mdash; and why letting a cold container warm up in humid air, allowing condensation to form inside it, is worth avoiding.</li>
<li><strong>Light.</strong> UV exposure can degrade certain amino acid residues, particularly tryptophan, tyrosine, and cysteine.</li>
<li><strong>Repeated freeze-thaw cycles.</strong> Each cycle introduces mechanical and chemical stress. Cycling a material repeatedly is considerably worse than holding it at a stable temperature.</li>
</ul>

<h2>Practical handling</h2>

<p>Keep material in a cool, dry place away from direct sunlight, and follow the specific storage conditions printed on your product's packaging &mdash; these vary by format, and the label is the authority for the product in your hand.</p>

<p>If a container has been refrigerated, allowing it to reach room temperature before opening reduces the chance of condensation forming inside it. Minimising the number of times a container is opened, and the length of time it sits open, limits both moisture ingress and light exposure.</p>

<p>Primed Peptides cartridges use a stabilised formulation with a 12-month shelf life when stored as directed, rather than shipping as a raw lyophilised powder requiring preparation.</p>

<h2>A note on what this article does not cover</h2>

<p>This is handling and storage guidance only. Primed Peptides products are supplied for laboratory and research use only, not for human or veterinary use, and we do not publish preparation, dosing, or administration guidance.</p>
HTML,
        ],

        'understanding-certificate-of-analysis' => [
            'title'    => 'How to Read a Certificate of Analysis',
            'category' => 'Quality &amp; Testing',
            'excerpt'  => 'A COA is only useful if you know what its numbers mean. A walkthrough of the fields that actually matter.',
            'read'     => '4 min read',
            'date'     => '2026-08-30',
            'body'     => <<<'HTML'
<p>A Certificate of Analysis (COA) is the document that turns a supplier's purity claim into something you can check. It records what a specific batch was tested for, by what method, and what the results were. A claim of "99% pure" printed on a website means very little on its own; the same claim backed by a batch-specific COA means considerably more.</p>

<h2>The fields worth reading</h2>

<p><strong>Batch or lot number.</strong> This is the most important field, because it's what ties the document to the material actually in your hand. A COA that doesn't correspond to your batch number is describing something else.</p>

<p><strong>Purity, and the method used.</strong> Purity is most commonly determined by High-Performance Liquid Chromatography (HPLC), which separates the components of a sample so they can be measured individually. The result is usually expressed as a percentage representing the target peptide's share of the total. A well-presented COA includes the chromatogram itself, not just the final number &mdash; the shape of the trace shows how cleanly the material separated.</p>

<p><strong>Identity confirmation.</strong> Purity tells you the sample is largely one substance. It doesn't, by itself, tell you that substance is the right one. Identity is typically confirmed by mass spectrometry, which measures molecular weight and compares it against the theoretical weight for the stated sequence. A COA showing both purity and identity is answering two genuinely different questions.</p>

<p><strong>Test date.</strong> Testing is a snapshot. A COA from the point of release describes the material as it left the lab &mdash; how it has been stored since then is a separate matter, which is why <a href="/articles/how-to-store-research-peptides/">storage conditions</a> affect what you actually end up working with.</p>

<h2>Independence matters</h2>

<p>There's a meaningful difference between a manufacturer testing its own output and an independent third-party laboratory doing so. Both can be honest, but only one removes the conflict of interest entirely. It's a fair question to ask any supplier.</p>

<p>Every batch of Primed Peptides material is tested by an independent third-party laboratory for purity and identity before release. To request the COA for your batch, email <a href="mailto:info@primedpeptides.co.uk">info@primedpeptides.co.uk</a> with your product name and batch number, or see the <a href="/coa/">Certificate of Analysis page</a>.</p>
HTML,
        ],

        'what-does-99-percent-purity-mean' => [
            'title'    => 'What Does 99% Purity Actually Mean?',
            'category' => 'Quality &amp; Testing',
            'excerpt'  => 'The number is widely quoted and rarely explained. What HPLC measures, and what the remaining 1% consists of.',
            'read'     => '3 min read',
            'date'     => '2026-08-30',
            'body'     => <<<'HTML'
<p>"99%+ purity" appears on almost every research peptide sold. It's worth understanding what the figure is actually measuring, because the phrase is more specific than it looks &mdash; and because a purity number without a stated method behind it is not really a claim at all.</p>

<h2>What HPLC measures</h2>

<p>Purity is normally determined by High-Performance Liquid Chromatography. A sample is dissolved and pushed through a column packed with a material that different compounds travel through at different rates. Because components separate as they pass through, each emerges at a different time and registers as a distinct peak on the resulting chromatogram.</p>

<p>The purity percentage is derived from the area under those peaks: the target peptide's peak area as a proportion of the total peak area. So "99% pure" means, in effect, that 99% of what the detector saw was the target compound.</p>

<h2>What the remaining 1% typically is</h2>

<p>The remainder is usually not contamination in the dramatic sense. In a synthetic peptide it's most often closely related by-products of the synthesis itself &mdash; chains missing a single amino acid (deletion sequences), chains where a protecting group wasn't fully removed, or small amounts of residual solvent and counter-ions such as acetate from the purification process.</p>

<p>This is also why identity testing matters alongside purity. HPLC tells you how much of the sample is one dominant compound; it takes mass spectrometry to confirm that compound is the sequence you ordered.</p>

<h2>Reading the number honestly</h2>

<p>A purity figure is only as good as three things: the method it was measured by, whether the testing was independent, and whether it applies to your specific batch rather than to a representative sample from some earlier production run. A supplier who publishes all three is giving you something checkable.</p>

<p>Primed Peptides material is tested to a minimum 99% purity standard by an independent third-party laboratory, per batch, with identity confirmed alongside it. See <a href="/articles/understanding-certificate-of-analysis/">how to read a Certificate of Analysis</a> for what to look for in the documentation itself.</p>
HTML,
        ],
    ];
}

/**
 * Renders both /articles/ (index) and /articles/<slug>/ (single article) as
 * virtual pages. Same approach as /coa/ - see the note at the top of this file.
 */
add_action('template_redirect', function() {
    $path = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path !== '/articles' && strpos($path, '/articles/') !== 0) return;

    $articles = primed_articles();
    $slug     = ($path === '/articles') ? '' : substr($path, strlen('/articles/'));

    // Unknown slug under /articles/ - let WordPress serve its real 404 rather
    // than rendering an empty page that returns 200 to a search engine.
    if ($slug !== '' && !isset($articles[$slug])) return;

    status_header(200);
    global $wp_query;
    $wp_query->is_404 = false;

    get_header();
    echo '<div class="woocommerce-page"><div class="woocommerce articles-wrap">';

    if ($slug === '') {
        $cats = [];
        foreach ($articles as $a) $cats[$a['category']] = true;

        echo '<h1>Articles</h1>';
        echo '<p class="articles-intro">Straightforward explainers on peptide chemistry, handling, and how quality is actually verified.</p>';

        echo '<div class="articles-filter"><button type="button" class="af-btn is-active" data-cat="all">All</button>';
        foreach (array_keys($cats) as $cat) {
            echo '<button type="button" class="af-btn" data-cat="' . esc_attr($cat) . '">' . $cat . '</button>';
        }
        echo '</div>';

        echo '<div class="articles-grid">';
        foreach ($articles as $s => $a) {
            echo '<a class="article-card" href="' . esc_url(home_url('/articles/' . $s . '/')) . '" data-cat="' . esc_attr($a['category']) . '">'
               . '<span class="article-cat">' . $a['category'] . '</span>'
               . '<h2>' . esc_html($a['title']) . '</h2>'
               . '<p>' . esc_html($a['excerpt']) . '</p>'
               . '<span class="article-meta">' . esc_html($a['read']) . '</span>'
               . '</a>';
        }
        echo '</div>';
        ?>
        <script>
        (function () {
          var btns = document.querySelectorAll('.af-btn');
          var cards = document.querySelectorAll('.article-card');
          btns.forEach(function (b) {
            b.addEventListener('click', function () {
              btns.forEach(function (x) { x.classList.remove('is-active'); });
              b.classList.add('is-active');
              var cat = b.getAttribute('data-cat');
              cards.forEach(function (c) {
                c.style.display = (cat === 'all' || c.getAttribute('data-cat') === cat) ? '' : 'none';
              });
            });
          });
        })();
        </script>
        <?php
    } else {
        $a = $articles[$slug];
        echo '<article class="article-single">'
           . '<a class="article-back" href="' . esc_url(home_url('/articles/')) . '">&larr; All articles</a>'
           . '<span class="article-cat">' . $a['category'] . '</span>'
           . '<h1>' . esc_html($a['title']) . '</h1>'
           . '<span class="article-meta">' . esc_html($a['read']) . '</span>'
           . '<div class="article-body">' . $a['body'] . '</div>'
           . '<p class="article-ruo"><strong>Research Use Only.</strong> All products sold by Primed Peptides are intended for laboratory and research use only, not for human or veterinary use.</p>'
           . '</article>';
    }

    echo '</div></div>';
    get_footer();
    exit;
});

// Real <title> and meta description for both the hub and each article.
add_filter('document_title_parts', function($parts) {
    $path = untrailingslashit(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path === '/articles') {
        $parts['title'] = 'Articles';
    } elseif (strpos($path, '/articles/') === 0) {
        $articles = primed_articles();
        $slug = substr($path, strlen('/articles/'));
        if (isset($articles[$slug])) $parts['title'] = $articles[$slug]['title'];
    }
    return $parts;
});

/**
 * Put the virtual pages into wp-sitemap.xml.
 *
 * Added 2026-08-30, immediately after building them. WordPress generates its
 * sitemap from real database posts/pages, so /coa/, /faq/, /articles/ and the
 * individual articles were completely absent from it - invisible to Google
 * except by following nav links. Given the articles hub exists specifically
 * for search discovery on a brand-new domain with no backlinks, that gap
 * defeated most of the point.
 *
 * Registers a custom provider, which appears as wp-sitemap-primedpages-1.xml
 * inside the existing sitemap index. Anonymous class defined inside init
 * because WP_Sitemaps_Provider isn't loaded when this file is required.
 */
add_action('init', function() {
    if (!function_exists('wp_register_sitemap_provider') || !class_exists('WP_Sitemaps_Provider')) {
        return;
    }
    wp_register_sitemap_provider('primedpages', new class extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name        = 'primedpages';
            $this->object_type = 'page';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = [
                home_url('/coa/'),
                home_url('/faq/'),
                home_url('/articles/'),
            ];
            foreach (array_keys(primed_articles()) as $slug) {
                $urls[] = home_url('/articles/' . $slug . '/');
            }
            return array_map(function($u) { return ['loc' => $u]; }, $urls);
        }
        public function get_max_num_pages($object_subtype = '') {
            return 1;
        }
    });
}, 20);

// Registering a new sitemap provider adds a rewrite rule, and without a flush
// WordPress serves the homepage HTML at wp-sitemap-primedpages-1.xml instead
// of the XML (confirmed live 2026-08-30 - the index listed the file but the
// file itself returned the theme's homepage). Runs once, then self-disables.
// Priority 30 so it lands after the provider registration at 20.
add_action('init', function() {
    if (get_option('primed_sitemap_flushed_v2') !== 'yes') {
        flush_rewrite_rules();
        update_option('primed_sitemap_flushed_v2', 'yes');
    }
}, 30);
