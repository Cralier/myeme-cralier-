<?php
/* Template Name: ユーザー情報編集 */
get_header();

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile_nonce']) && wp_verify_nonce($_POST['edit_profile_nonce'], 'edit_profile')) {
    $display_name = sanitize_text_field($_POST['display_name'] ?? '');
    $region = sanitize_text_field($_POST['region'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $user_email = sanitize_email($_POST['user_email'] ?? '');

    if (empty($display_name)) {
        $errors[] = '表示名は必須です。';
    }
    if (empty($user_email) || !is_email($user_email)) {
        $errors[] = '有効なメールアドレスを入力してください。';
    }

    // 画像アップロード処理
    if (empty($errors) && isset($_FILES['profile_avatar']) && !empty($_FILES['profile_avatar']['tmp_name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $file = $_FILES['profile_avatar'];
        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (!isset($upload['error'])) {
            update_user_meta($current_user->ID, 'profile_avatar', esc_url_raw($upload['url']));
        } else {
            $errors[] = '画像のアップロードに失敗しました: ' . esc_html($upload['error']);
        }
    }

    if (empty($errors)) {
        wp_update_user([
            'ID' => $current_user->ID,
            'display_name' => $display_name,
            'user_email' => $user_email,
        ]);
        update_user_meta($current_user->ID, 'region', $region);
        update_user_meta($current_user->ID, 'description', $description);
        $success = true;
        // 最新情報を取得
        $current_user = wp_get_current_user();
    }
}

$region = get_user_meta($current_user->ID, 'region', true);
$description = get_user_meta($current_user->ID, 'description', true);

// プロフィール画像URL取得
$profile_avatar_url = get_user_meta($current_user->ID, 'profile_avatar', true);
$avatar_url = $profile_avatar_url ? $profile_avatar_url : get_avatar_url($current_user->ID, ['size'=>120]);

// メールアドレスの値をフォームに反映
$user_email_value = '';
if (!empty($_POST['user_email'])) {
    // POST値があればそれを優先
    $user_email_value = esc_attr($_POST['user_email']);
} else {
    // それ以外は現在のユーザー情報
    $user_email_value = esc_attr($current_user->user_email);
}
?>
<main class="mypage-container">
    <h2>プロフィール編集</h2>
    <?php if ($success): ?>
        <div class="notice success">プロフィールを更新しました。</div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="notice draft">
            <?php foreach ($errors as $error) echo esc_html($error) . '<br>'; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="profile-edit-form" enctype="multipart/form-data">
        <?php wp_nonce_field('edit_profile', 'edit_profile_nonce'); ?>
        <div class="profile-image profile-image-edit" onclick="document.getElementById('profile_avatar').click();">
            <div class="avatar-hint">画像をタップして変更</div>
            <img src="<?php echo esc_url($avatar_url); ?>" alt="プロフィール画像" id="avatarPreview" />
            <input type="file" name="profile_avatar" id="profile_avatar" accept="image/*" style="display:none;">
        </div>
        <label>表示名<br>
            <input type="text" name="display_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
        </label>
        <label>ID<br>
            <input type="text" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
        </label>
        <label>メールアドレス（非公開）<br>
            <input type="email" name="user_email" value="<?php echo $user_email_value; ?>" required>
        </label>
        <label>自己紹介<br>
            <textarea name="description" rows="4"><?php echo esc_textarea($description); ?></textarea>
        </label>
        <div class="profile-edit-buttons">
            <button type="submit" class="profile_save_button">更新</button>
            <a href="<?php echo home_url('/mypage/' . $current_user->user_login . '/'); ?>" class="btn-draft">キャンセル</a>
        </div>
    </form>
</main>
<script>
document.getElementById('profile_avatar').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(ev) {
            var img = document.getElementById('avatarPreview');
            if (img) img.src = ev.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>
<?php get_footer(); ?> 