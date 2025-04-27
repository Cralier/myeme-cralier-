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
    <div class="notice success" id="post-success-message">投稿が完了しました！</div>
    <script>
      // 表示後、URLから ?submitted=1 を消す
      if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('submitted');
        window.history.replaceState(null, '', url);
      }
    </script>
    <?php elseif (isset($_GET['drafted'])): ?>
    <div class="notice draft" id="post-draft-message">下書きとして保存しました。</div>
    <script>
      if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('drafted');
        window.history.replaceState(null, '', url);
      }
    </script>
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
        <button class="tab-btn active" data-tab="works">作品</button>
        <button class="tab-btn" data-tab="parts">パーツ</button>
    </div>

    <!-- 作品タブの内容 -->
    <div class="tab-content tab-works active">
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
                        ?>
                        <div class="work-item <?php echo $status_class; ?>" data-recipe-id="<?php echo esc_attr($recipe->ID); ?>">
                            <a href="<?php echo esc_url($link); ?>">
                                <?php if (has_post_thumbnail($recipe->ID)) {
                                    echo get_the_post_thumbnail($recipe->ID, 'medium');
                                } else {
                                    if ($recipe->post_status === 'draft') {
                                        echo '<img src="' . get_template_directory_uri() . '/images/draft_sub_picture.png" alt="下書きサムネイル">';
                                    } else {
                                        echo '<img src="' . get_template_directory_uri() . '/images/placeholder.jpg" alt="作品サムネイル">';
                                    }
                                } ?>
                            </a>
                            <div class="work-menu-wrapper efit_button-wrapper">
                                <button class="work-menu-toggle efit_button" type="button">
                                    <span class="work-menu-circle">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/edit_post.png" alt="編集" class="work-menu-icon">
                                    </span>
                                </button>
                                <div class="work-menu-dropdown" style="display:none;">
                                    <button class="work-edit-btn" data-recipe-id="<?php echo esc_attr($recipe->ID); ?>">編集</button>
                                    <button class="work-delete-btn" data-recipe-id="<?php echo esc_attr($recipe->ID); ?>">削除</button>
                                </div>
                            </div>
                            <?php if ($recipe->post_status === 'draft') : ?>
                                <div class="draft-label-center">下書き</div>
                            <?php endif; ?>
                        </div>
                        <?php
        }
    } else {
        echo '<p>まだ投稿がありません。</p>';
    }
    ?>
            </div>
        </section>
    </div>

    <!-- パーツタブの内容 -->
    <div class="tab-content tab-parts" style="display:none;">
        <section class="saved-section">
            <div class="section-header">
                <h3>保存</h3>
                <a href="#">すべて見る</a>
            </div>
            <div class="works-grid">
                <?php
                // ユーザーの保存済みパーツを取得
                $saved_parts = get_user_meta($user->ID, 'saved_parts', true);
                
                if (!empty($saved_parts) && is_array($saved_parts)) {
                    foreach ($saved_parts as $part) {
                        echo '<div class="work-item"><img src="' . esc_url($part) . '" alt="保存したパーツ"></div>';
    }
                } else {
                    echo '<p>保存したパーツはありません。</p>';
    }
                ?>
            </div>
        </section>
        <section class="user-materials-section">
            <div class="section-header">
                <h3>あなたの材料・道具</h3>
                <a href="http://localhost:10003/mypage/cralier01/tools/">すべて見る</a>
            </div>
            <div class="works-grid">
                <?php
                // ユーザーの保存済みIDリストを取得
                $mypage_tools_ids = get_user_meta($user->ID, 'mypage_tools_ids', true);
                if (!empty($mypage_tools_ids) && is_array($mypage_tools_ids)) {
                    // マスターデータを読み込み
                    $materials = @json_decode(@file_get_contents(ABSPATH . 'wp-content/uploads/autocomplete/materials.json'), true) ?: [];
                    $tools = @json_decode(@file_get_contents(ABSPATH . 'wp-content/uploads/autocomplete/tools.json'), true) ?: [];
                    $all_items_by_id = [];
                    foreach (array_merge($materials, $tools) as $item) {
                        if (!empty($item['id'])) {
                            $all_items_by_id[(string)$item['id']] = $item;
                        }
                    }
                    $display_ids = array_slice($mypage_tools_ids, 0, 6);
                    foreach ($display_ids as $id) {
                        $item = isset($all_items_by_id[$id]) ? $all_items_by_id[$id] : null;
                        $img = !empty($item['Image']) ? esc_url($item['Image']) : (!empty($item['image']) ? esc_url($item['image']) : get_template_directory_uri() . '/images/placeholder.jpg');
                        echo '<div class="work-item">';
                        echo '<img src="' . $img . '" alt="材料・道具" style="width:100%;height:auto;">';
                        echo '</div>';
                    }
                } else {
                    echo '<p>保存した材料・道具はありません。</p>';
                }
                ?>
            </div>
            <div class="tools-add-button-wrapper">
                <a href="http://localhost:10003/mypage/cralier01/tools/" class="tools_add_button">＋追加する</a>
            </div>
        </section>
    </div>

    <a href="<?php echo wp_logout_url(home_url('/login/')); ?>" class="logout-button">ログアウト</a>
</main>

<script>
jQuery(function($){
    // タブ切り替え
    $('.tab-btn').on('click', function(){
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        var tab = $(this).data('tab');
        $('.tab-content').hide();
        $('.tab-' + tab).show();
    });
    // メニューのトグル
    $('.works-grid').on('click', '.work-menu-toggle', function(e){
        e.stopPropagation();
        var $dropdown = $(this).siblings('.work-menu-dropdown');
        $('.work-menu-dropdown').not($dropdown).hide();
        $dropdown.toggle();
    });
    // 編集ボタン
    $('.works-grid').on('click', '.work-edit-btn', function(){
        var recipeId = $(this).data('recipe-id');
        window.location.href = '/submit-recipe/?draft_post_id=' + recipeId;
    });
    // 削除ボタン
    $('.works-grid').on('click', '.work-delete-btn', function(){
        var recipeId = $(this).data('recipe-id');
        if(confirm('本当にこの作品を削除しますか？')){
            $.ajax({
                url: '/wp-json/wp/v2/recipe/' + recipeId,
                method: 'DELETE',
                beforeSend: function(xhr){
                    xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                },
                success: function(){
                    $('.work-item[data-recipe-id="'+recipeId+'"]').fadeOut(300, function(){$(this).remove();});
                },
                error: function(){
                    alert('削除に失敗しました');
                }
            });
        }
    });
    // 外側クリックでメニュー閉じる
    $(document).on('click', function(){
        $('.work-menu-dropdown').hide();
    });
});
</script>

<?php get_footer(); ?>
