<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="top-bar">
    <strong>UK Made</strong> &nbsp;·&nbsp; Stabilised Formulations &nbsp;·&nbsp; 12 Month Shelf Life &nbsp;·&nbsp; Research Use Only
</div>

<header class="site-header">
    <div class="header-inner">
        <div class="site-logo">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php else: ?>
                <a href="<?php echo home_url('/'); ?>" class="site-logo-text">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/logo-primed.png" alt="Primed Peptides" style="height:34px;width:auto;display:block;">
                </a>
            <?php endif; ?>
        </div>

        <div class="header-actions">
            <a href="<?php echo wc_get_cart_url(); ?>" class="cart-link">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Cart
                <?php $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
                <?php if ($count > 0): ?>
                    <span class="cart-count"><?php echo $count; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</header>

<nav class="site-nav">
    <div class="nav-inner">
        <a href="<?php echo home_url('/'); ?>">Home</a>
        <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>">All Products</a>
        <a href="<?php echo home_url('/?cat=recovery'); ?>">Recovery & Repair</a>
        <a href="<?php echo home_url('/?cat=cognitive'); ?>">Cognitive & Wellness</a>
        <a href="<?php echo home_url('/?cat=performance'); ?>">Performance</a>
        <a href="<?php echo home_url('/?cat=aesthetic'); ?>">Aesthetic & Skin</a>
        <a href="<?php echo home_url('/?cat=accessories'); ?>">Accessories</a>
        <a href="<?php echo home_url('/articles/'); ?>">Articles</a>
        <a href="<?php echo home_url('/about'); ?>">About</a>
        <a href="<?php echo home_url('/contact'); ?>">Contact</a>
    </div>
</nav>
