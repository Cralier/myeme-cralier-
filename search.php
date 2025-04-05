<?php get_header(); ?>

<main class="recipe-container">
    <h1>「<?php echo get_search_query(); ?>」の検索結果</h1>

    <?php if (have_posts()) : ?>
        <ul class="recipe-list">
            <?php while (have_posts()) : the_post(); ?>
                <li>
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p>該当するレシピが見つかりませんでした。</p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>