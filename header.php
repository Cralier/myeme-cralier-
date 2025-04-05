<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?></title>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
    <?php wp_head(); ?>
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <!-- ロゴ（トップページへ戻る） -->
        <a href="<?php echo home_url(); ?>" class="logo">
            <img src="<?php echo get_template_directory_uri(); ?>/images/logo.png" alt="会社ロゴ">
        </a>

        <!-- 検索バー -->
        <div class="search-bar">
            <?php get_search_form(); ?>
        </div>

        <!-- 右側エリア（カートアイコン＋ハンバーガーメニュー） -->
        <div class="header-right">
            <div class="cart-icon">
                <img src="<?php echo get_template_directory_uri(); ?>/images/cart-icon.png" alt="カート">
            </div>

            <!-- ハンバーガーメニュー -->
            <div class="hamburger-menu" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</header>

<!-- ハンバーガーメニューの中身 -->
<nav class="mobile-menu" id="mobile-menu">
    <ul>
        <li><a href="#">クラリエ ショップ</a></li>
        <li><a href="<?php echo home_url('/recipe/'); ?>">レシピ一覧</a></li>
        <li><a href="#">レシピランキング</a></li>
        <li><a href="#">レシピ保存ランキング</a></li>
        <li><a href="#">クリエイターランキング</a></li>
        <li><a href="#">ジャンル一覧</a></li>
        <li><button class="login-button">ログイン・新規登録</button></li>
        <li><button class="notice-button">お知らせ</button></li>
        <li><button class="feedback-button">意見を送る</button></li>
    </ul>
</nav>

<?php wp_nav_menu(array(
    'theme_location' => 'primary',
    'container'      => false
)); ?>