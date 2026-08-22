<?php get_header(); ?>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="hero-eyebrow">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
            UK Made · Research Grade
        </div>
        <h1>Premium Peptides.<br>Proven Performance.</h1>
        <p>Stabilised formulations with 12 month shelf life. Batch-tested for purity and potency. Trusted by researchers across the UK.</p>
        <div class="hero-cta">
            <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>" class="btn btn-primary">Browse Products</a>
            <a href="<?php echo home_url('/about'); ?>" class="btn btn-outline">About Us</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <strong>99%+</strong>
                <span>Purity Guaranteed</span>
            </div>
            <div class="hero-stat">
                <strong>12mo</strong>
                <span>Shelf Life</span>
            </div>
            <div class="hero-stat">
                <strong>UK</strong>
                <span>Made & Tested</span>
            </div>
            <div class="hero-stat">
                <strong>14</strong>
                <span>Products</span>
            </div>
        </div>
    </div>
</section>

<!-- Trust Bar -->
<div class="trust-bar">
    <div class="trust-bar-inner">
        <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Batch Tested
        </div>
        <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            Stabilised Formulations
        </div>
        <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            12 Month Shelf Life
        </div>
        <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            UK Made & Shipped
        </div>
        <div class="trust-item">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Secure Checkout
        </div>
    </div>
</div>

<!-- Products -->
<section class="products-section">
    <div class="container">
        <div class="section-head">
            <h2>All Products</h2>
            <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>">View all &rarr;</a>
        </div>

        <div class="cat-pills">
            <button class="cat-pill active" data-cat="all">All</button>
            <button class="cat-pill" data-cat="recovery-repair">Recovery & Repair</button>
            <button class="cat-pill" data-cat="cognitive-wellness">Cognitive & Wellness</button>
            <button class="cat-pill" data-cat="performance-body-composition">Performance</button>
            <button class="cat-pill" data-cat="aesthetic-skin-support">Aesthetic & Skin</button>
            <button class="cat-pill" data-cat="hormonal-support">Hormonal Support</button>
            <button class="cat-pill" data-cat="accessories">Accessories</button>
        </div>

        <div class="products-grid">
            <?php
            $product_visibility_terms = wc_get_product_visibility_term_ids();
            $args = [
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'tax_query'      => [
                    [
                        'taxonomy' => 'product_visibility',
                        'field'    => 'term_taxonomy_id',
                        'terms'    => $product_visibility_terms['exclude-from-catalog'],
                        'operator' => 'NOT IN',
                    ],
                ],
            ];
            $products = new WP_Query($args);

            if ($products->have_posts()):
                while ($products->have_posts()): $products->the_post();
                    $product = wc_get_product(get_the_ID());
                    if (!$product) continue;
                    $cats = wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'names']);
                    $cat_slug = !empty($cats) ? sanitize_title($cats[0]) : '';
            ?>
            <div class="product-card" data-cat="<?php echo esc_attr($cat_slug); ?>">
                <a href="<?php the_permalink(); ?>" class="product-card-link" aria-label="<?php the_title_attribute(); ?>"></a>
                <div class="product-image">
                    <span class="product-badge">UK Made</span>
                    <?php $image_id = (int) get_post_meta(get_the_ID(), '_thumbnail_id', true); ?>
                    <?php if ($image_id): ?>
                        <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                    <?php else:
                        $sku = $product ? $product->get_sku() : '';
                        $name = strtolower(get_the_title());
                        if (strpos($sku, 'ACC-002') !== false || strpos($name, 'case') !== false) {
                            $img = 'product-case.svg';
                        } elseif (strpos($sku, 'ACC-001') !== false || strpos($name, 'metal pen') !== false || strpos($name, 'reusable') !== false) {
                            $img = 'product-pen.svg';
                        } elseif (strpos($name, 'vial') !== false) {
                            $img = 'product-vial.svg';
                        } else {
                            $img = 'product-cartridge.svg';
                        }
                    ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/images/<?php echo $img; ?>" alt="<?php the_title_attribute(); ?>" style="max-height:180px;object-fit:contain;">
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <?php if (!empty($cats)): ?>
                        <div class="product-cat-label"><?php echo esc_html($cats[0]); ?></div>
                    <?php endif; ?>
                    <h3 class="product-name"><?php the_title(); ?></h3>
                    <p class="product-desc"><?php echo wp_trim_words(get_the_excerpt(), 14); ?></p>
                    <div class="product-footer">
                        <span class="product-price"><?php echo $product->get_price_html(); ?></span>
                        <button class="add-to-cart-btn add_to_cart_button ajax_add_to_cart"
                            data-product_id="<?php echo get_the_ID(); ?>"
                            data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                            data-quantity="1">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>
</section>

<!-- Purity Banner -->
<div class="purity-banner">
    <div class="container">
        <h2>99%+ Purity. Every Batch.</h2>
        <p>All Primed Peptides products are third-party tested and come with certificates of analysis. UK made to the highest research standards.</p>
    </div>
</div>

<?php get_footer(); ?>
