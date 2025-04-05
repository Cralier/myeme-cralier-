<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php bloginfo('name'); ?></title>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
</head>
<body>
    <header>
        <h1><?php bloginfo('name'); ?></h1>
        <nav><?php wp_nav_menu(array('theme_location' => 'primary')); ?></nav>
    </header>

    <main>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article>
                <h2><?php the_title(); ?></h2>
                <div><?php the_content(); ?></div>
            </article>
        <?php endwhile; endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
    </footer>
</body>
</html>

<?php get_header(); ?>
