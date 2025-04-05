<?php get_header(); ?>

<main>
    <h1>レシピ一覧</h1>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <?php if (has_post_thumbnail()) : ?>
                <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
            <?php endif; ?>
            <div><?php the_excerpt(); ?></div>
        </article>
    <?php endwhile; endif; ?>
</main>

<?php get_footer(); ?>