<?php
/* このテンプレートは /mypage/{ユーザー名} 用に動作します */
get_header();

// クエリ変数からユーザー情報を取得
$user_login = get_query_var('user_mypage');
$user = get_user_by('login', $user_login);

// ユーザーが存在しない場合
if (!$user) {
    echo '<main class="mypage-container">';
    echo '<h2>指定されたユーザーが存在しません。</h2>';
    echo '</main>';
    get_footer();
    exit;
}

// 自分以外のマイページを見ようとした場合（セキュリティ保護）
if (get_current_user_id() !== $user->ID) {
    wp_redirect(home_url('/login/'));
    exit;
}
?>


<script>
  if (performance.navigation.type === 2) {
    // 戻るボタンで来た場合 → 強制リロード
    window.location.reload();
  }
</script>

<main class="mypage-container">
    <?php if (isset($_GET['submitted'])): ?>
    <div class="notice success">投稿が完了しました！</div>
    <?php elseif (isset($_GET['drafted'])): ?>
    <div class="notice draft">下書きとして保存しました。</div>
    <?php endif; ?>

    <h2>こんにちは、<?php echo esc_html($user->display_name); ?> さん</h2>
    <p>こちらはあなた専用のマイページです。</p>

    <section class="saved-section">
        <h3>保存したレシピや材料（※今後の実装）</h3>
        <p>ここに保存機能やおすすめ表示などを追加予定</p>
    </section>

    <section class="user-recipes">
    <h3>あなたのレシピ一覧</h3>

    <?php
    $current_user_id = get_current_user_id();

    $args = array(
        'author' => $current_user_id,
        'post_type' => 'recipe',
        'post_status' => array('publish', 'draft'),
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => -1
    );

    $recipes = get_posts($args);

    if (!empty($recipes)) {
        foreach ($recipes as $recipe) {
            $status = get_post_status($recipe->ID);
            $label = ($status === 'draft') ? '【下書き】' : '';
            $edit_url = home_url('/submit-recipe/?draft_post_id=' . $recipe->ID);
            $view_url = get_permalink($recipe->ID);
            $thumbnail = get_the_post_thumbnail($recipe->ID, 'medium');
        
            echo '<article class="user-recipe horizontal-box">';
            
            echo '<div class="recipe-text">';
            echo '<h4>' . esc_html($label . get_the_title($recipe)) . '</h4>';
            echo '<p>' . esc_html(wp_trim_words($recipe->post_content, 20)) . '</p>';
            echo '<div class="button-group">';
            echo '<a class="btn btn-edit" href="' . esc_url($edit_url) . '">編集する</a>';
            if ($status === 'publish') {
                echo '<a class="btn btn-view" href="' . esc_url($view_url) . '" target="_blank">公開ページを見る</a>';
            }
            echo '</div>';
            echo '</div>'; // .recipe-text
        
            echo '<div class="recipe-thumbnail">';
            echo $thumbnail ?: '<div class="no-image">No Image</div>';
            echo '</div>';
        
            echo '</article>';
        }
    } else {
        echo '<p>まだ投稿がありません。</p>';
    }
    ?>

<style>
    /*マイページの編集と下書きボタン*/
    .button-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
    }

    .button-group .btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    background-color: #ff6600;
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
    transition: background-color 0.2s ease;
    }

    .button-group .btn:hover {
    background-color: #e65c00;
    }

    .button-group .btn-view {
    background-color: #444;
    }

    .button-group .btn-view:hover {
    background-color: #222;
    }
</style>

<style>
    /*レシピ投稿の表示*/
    .user-recipe.horizontal-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 15px;
    background: #fff;
    }

    .recipe-text {
    flex: 1;
    margin-right: 15px;
    }

    .recipe-text h4 {
    margin: 0 0 5px;
    font-size: 18px;
    }

    .recipe-text p {
    font-size: 14px;
    color: #333;
    }

    .recipe-thumbnail {
    width: 120px;
    height: 120px;
    overflow: hidden;
    border-radius: 6px;
    background: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    }

    .recipe-thumbnail img {
    max-width: 100%;
    height: auto;
    }

    .no-image {
    font-size: 12px;
    color: #888;
    }

    .button-group {
    margin-top: 10px;
    display: flex;
    gap: 10px;
    }

    .btn {
    padding: 8px 14px;
    border-radius: 5px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    }

    .btn-edit {
    background-color: #ff6600;
    color: white;
    }

    .btn-view {
    background-color: #333;
    color: white;
    }
</style>

    <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-button">ログアウト</a>
</main>

<?php get_footer(); ?>