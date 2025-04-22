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

<main class="mypage-container">
    <?php if (isset($_GET['submitted'])): ?>
    <div class="notice success">投稿が完了しました！</div>
    <?php elseif (isset($_GET['drafted'])): ?>
    <div class="notice draft">下書きとして保存しました。</div>
    <?php endif; ?>

    <!-- プロフィールセクション -->
    <section class="profile-section">
        <div class="profile-image">
            <?php
            $avatar = get_avatar($user->ID, 120);
            echo $avatar ? $avatar : '<img src="' . get_template_directory_uri() . '/images/default-avatar.png" alt="プロフィール画像">';
            ?>
        </div>
        <h2><?php echo esc_html($user->display_name); ?></h2>
        <p class="profile-description">
            <?php
            $description = get_user_meta($user->ID, 'description', true);
            echo $description ? esc_html($description) : '紹介文が入ります。紹介文が入ります。紹介文が入ります。紹介文が入ります。紹介文が入ります。紹介文が入ります。';
            ?>
        </p>
        <a href="#" class="edit-profile-button">登録情報を編集</a>
    </section>

    <!-- タブナビゲーション -->
    <div class="profile-tabs">
        <button class="profile-tab active" data-tab="works">作品</button>
        <button class="profile-tab" data-tab="parts">パーツ</button>
    </div>

    <!-- 保存セクション -->
    <section class="saved-section">
        <div class="section-header">
            <h3>保存</h3>
            <a href="#">すべて見る</a>
        </div>
        <div class="works-grid">
            <?php
            // ユーザーの保存済みレシピを取得
            $saved_recipes = get_user_meta($user->ID, 'saved_recipes', true);
            
            if (!empty($saved_recipes) && is_array($saved_recipes)) {
                $args = array(
                    'post_type' => 'recipe',
                    'post__in' => $saved_recipes,
                    'posts_per_page' => 8,
                    'post_status' => 'publish'
                );
                
                $saved_query = new WP_Query($args);
                
                if ($saved_query->have_posts()) {
                    while ($saved_query->have_posts()) {
                        $saved_query->the_post();
                        echo '<a href="' . get_permalink() . '" class="work-item">';
                        if (has_post_thumbnail()) {
                            echo get_the_post_thumbnail(get_the_ID(), 'medium');
                        } else {
                            echo '<img src="' . get_template_directory_uri() . '/images/placeholder.jpg" alt="保存した作品">';
                        }
                        echo '</a>';
                    }
                    wp_reset_postdata();
                } else {
                    echo '<p>保存した作品はありません。</p>';
                }
            } else {
                echo '<p>保存した作品はありません。</p>';
            }
            ?>
        </div>
    </section>

    <!-- あなたの作品セクション -->
    <section class="user-works-section">
        <div class="section-header">
            <h3>あなたの作品</h3>
            <a href="#">すべて見る</a>
        </div>
        <div class="works-grid">
            <?php
            $args = array(
                'author' => $user->ID,
                'post_type' => 'recipe',
                'post_status' => array('publish', 'draft'),
                'posts_per_page' => 8
            );

            $recipes = get_posts($args);

            if (!empty($recipes)) {
                foreach ($recipes as $recipe) {
                    $status_class = $recipe->post_status === 'draft' ? 'draft-item' : '';
                    $link = $recipe->post_status === 'draft' 
                        ? home_url('/submit-recipe/?draft_post_id=' . $recipe->ID)
                        : get_permalink($recipe->ID);
                    
                    echo '<a href="' . esc_url($link) . '" class="work-item ' . $status_class . '">';
                    if (has_post_thumbnail($recipe->ID)) {
                        echo get_the_post_thumbnail($recipe->ID, 'medium');
                    } else {
                        echo '<img src="' . get_template_directory_uri() . '/images/placeholder.jpg" alt="作品サムネイル">';
                    }
                    if ($recipe->post_status === 'draft') {
                        echo '<div class="draft-label">下書き</div>';
                    }
                    echo '</a>';
                }
            } else {
                echo '<p>まだ投稿がありません。</p>';
            }
            ?>
        </div>
    </section>

    <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-button">ログアウト</a>
</main>

<script>
// タブ切り替えの処理
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.profile-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // 全てのタブから active クラスを削除
            tabs.forEach(t => t.classList.remove('active'));
            // クリックされたタブに active クラスを追加
            this.classList.add('active');
            
            // ここにタブの切り替え処理を追加
            const tabName = this.dataset.tab;
            // 必要に応じて対応するコンテンツを表示/非表示
        });
    });
});
</script>

<?php get_footer(); ?>
