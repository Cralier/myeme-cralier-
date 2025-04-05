<?php
/* Template Name: ユーザーログイン */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['user_email'])) {
    if (!isset($_POST['user_login_nonce']) || !wp_verify_nonce($_POST['user_login_nonce'], 'user_login')) {
        get_header();
        echo "<p>セキュリティチェックに失敗しました。</p>";
        get_footer();
        exit;
    }

    $creds = array(
        'user_login'    => sanitize_user($_POST['user_email']),
        'user_password' => sanitize_text_field($_POST['user_password']),
        'remember'      => true
    );

    $user = wp_signon($creds, false);

    if (!is_wp_error($user)) {
        // ここでまだ出力されていない状態なのでリダイレクトが成功する
        wp_redirect(home_url('/mypage/' . $user->user_login . '/'));
        exit;
    } else {
        get_header();
        echo "<p style='color:red;'>ログインエラー: " . esc_html($user->get_error_message()) . "</p>";
        get_footer();
        exit;
    }
}

get_header(); // フォームだけを表示する通常表示用
?>

<main class="login-container">
    <h1>LINEログイン</h1>
    <a href="<?php echo esc_url(home_url('/wp-login.php?action=wordpress_social_login&provider=LINE')); ?>" class="line-login-button">
        LINEでログイン
    </a>
</main>

<main class="login-container">
    <h2>ログイン</h2>
    <form method="post" class="login-form">
        <?php wp_nonce_field('user_login', 'user_login_nonce'); ?>
        <label for="user_email">ユーザー名:</label>
        <input type="text" name="user_email" required>

        <label for="user_password">パスワード:</label>
        <input type="password" name="user_password" required>

        <button type="submit">ログイン</button>
    </form>
</main>

<?php get_footer(); ?>