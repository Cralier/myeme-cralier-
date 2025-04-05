<?php
/* Template Name: ユーザー登録 */
get_header(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name     = sanitize_user($_POST['user_name'] ?? '');
    $user_email    = sanitize_email($_POST['user_email'] ?? '');
    $user_password = $_POST['user_password'] ?? '';

    // バリデーションチェック
    if (empty($user_name) || empty($user_email) || empty($user_password)) {
        echo "<p>すべての項目を入力してください。</p>";
    } elseif (strlen($user_password) < 6) {
        echo "<p>パスワードは6文字以上で入力してください。</p>";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $user_name)) {
        echo "<p>ユーザー名には半角英数字（アンダースコア可）を使用してください。</p>";
    } else {
        $userdata = array(
            'user_login' => $user_name,
            'user_email' => $user_email,
            'user_pass'  => $user_password,
            'role'       => 'subscriber'
        );

        $user_id = wp_insert_user($userdata);

        if (!is_wp_error($user_id)) {
            // 自動メール送信
            wp_mail(
                $user_email,
                '【クラリエ】アカウント登録完了のお知らせ',
                "{$user_name} 様\n\nアカウントの作成が完了しました。\n\n以下のリンクからログインしてください：\n" . home_url('/login/')
            );

            // 自動リダイレクト
            wp_redirect(home_url('/login/'));
            exit;
        } else {
            echo "<p>エラー: " . $user_id->get_error_message() . "</p>";
        }
    }
}
?>

<main class="register-container">
    <h2>アカウント作成</h2>
    <form method="post" class="register-form">
        <label for="user_name">ユーザー名（半角英数字）:</label>
        <input type="text" name="user_name" required value="<?php echo esc_attr($_POST['user_name'] ?? ''); ?>">

        <label for="user_email">メールアドレス:</label>
        <input type="email" name="user_email" required value="<?php echo esc_attr($_POST['user_email'] ?? ''); ?>">

        <label for="user_password">パスワード:</label>
        <input type="password" name="user_password" required>

        <button type="submit">アカウントを作成する</button>
    </form>
</main>

<?php get_footer(); ?>