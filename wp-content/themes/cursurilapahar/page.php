<?php
/**
 * Generic Page Template – Cursuri la Pahar
 */
get_header();
?>

<main class="page-main" style="min-height: 60vh; padding-block: 80px;">
    <div class="container container-narrow">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>
</main>

<?php get_footer(); ?>
