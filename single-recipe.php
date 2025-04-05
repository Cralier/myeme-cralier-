<?php get_header(); ?>

<main class="recipe-container">
    <?php if (have_posts()) : the_post(); ?>
        <article>
            <h1><?php the_title(); ?></h1>

            <?php if (has_post_thumbnail()) : ?>
                <div class="recipe-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>

            <h2>調理時間: <?php echo get_post_meta(get_the_ID(), 'cooking_time', true); ?> 分</h2>

            <h2>材料</h2>
            <p><?php echo nl2br(esc_html(get_post_meta(get_the_ID(), 'ingredients', true))); ?></p>

            <h2>作り方</h2>
            <p><?php echo nl2br(esc_html(get_post_meta(get_the_ID(), 'instructions', true))); ?></p>
        </article>
    <?php endif; ?>
</main>

<?php get_footer(); ?>