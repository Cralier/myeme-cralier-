<?php
/* Template Name: テストページ */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

// 投稿編集用の初期値
$form_title = '';
$editing_post = null;

// クエリパラメータから draft_post_id を取得
$draft_post_id = isset($_GET['draft_post_id']) ? intval($_GET['draft_post_id']) : 0;

if ($draft_post_id && get_post_status($draft_post_id) === 'draft') {
    $post = get_post($draft_post_id);

    // 投稿者が現在のユーザーか確認
    if ($post && $post->post_author === get_current_user_id()) {
        $editing_post = $post;
        $form_title = esc_attr($post->post_title);
    } else {
        wp_die('この下書きを編集する権限がありません。');
    }
}

// 投稿処理（保存）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_user_id = get_current_user_id();
    $post_title = sanitize_text_field($_POST['recipe_title']);

    $post_data = [
        'post_title'   => $post_title,
        'post_content' => '',
        'post_status'  => 'draft',
        'post_type'    => 'recipe',
        'post_author'  => $current_user_id,
    ];

    if (!empty($_POST['draft_post_id'])) {
        $post_data['ID'] = intval($_POST['draft_post_id']);
    }

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        echo '<div>保存に成功しました（ID: ' . $post_id . '）</div>';
    }
}

get_header();
?>

<main class="container">
    <h1>テスト投稿ページ</h1>
    <form method="post">
        <input type="hidden" name="draft_post_id" value="<?php echo esc_attr($editing_post->ID ?? ''); ?>">
        <label>タイトル: <input type="text" name="recipe_title" value="<?php echo $form_title; ?>"></label>
        <button type="submit">下書き保存</button>
    </form>
</main>

<?php get_footer(); ?>