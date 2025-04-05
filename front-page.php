<?php get_header(); ?>

<h1>こちらは、トップ画像。ここにピックアップの画像が入る、このトップページはfront-page.phpで編集可能。</h1>

<main>
    <h1></h1>
    <p></p>

    <h2>最新の記事</h2>
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