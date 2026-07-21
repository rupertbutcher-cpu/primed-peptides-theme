<?php get_header(); ?>

<div class="woocommerce-page">
    <div class="woocommerce">
        <?php
        while (have_posts()) {
            the_post();
            the_content();
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>
