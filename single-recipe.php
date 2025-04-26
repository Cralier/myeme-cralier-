<?php get_header(); ?>

<main class="recipe-single-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article class="recipe-article">
            <!-- メインビジュアル -->
            <div class="recipe-main-visual">
            <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large', ['class' => 'recipe-main-image']); ?>
                <?php endif; ?>
            </div>

            <!-- レシピ情報ヘッダー -->
            <div class="recipe-header">
                <h1 class="recipe-title"><?php the_title(); ?></h1>
                <div class="recipe-meta">
                    <div class="recipe-author">
                        <?php
                        $author_id = get_the_author_meta('ID');
                        $author_avatar = get_avatar($author_id, 40);
                        $author_name = get_the_author();
                        ?>
                        <a href="<?php echo esc_url(home_url('/mypage/' . get_the_author_meta('user_nicename'))); ?>" class="author-link">
                            <?php echo $author_avatar; ?>
                            <span class="author-name"><?php echo esc_html($author_name); ?></span>
                        </a>
                    </div>
                    <div class="recipe-actions">
                        <?php
                        $is_saved = false;
                        if (is_user_logged_in()) {
                            $user_id = get_current_user_id();
                            $saved_recipes = get_user_meta($user_id, 'saved_recipes', true);
                            if (is_array($saved_recipes)) {
                                $is_saved = in_array(get_the_ID(), $saved_recipes);
                            }
                        }
                        ?>
                        <button class="action-button save-button <?php echo $is_saved ? 'saved' : ''; ?>" data-recipe-id="<?php the_ID(); ?>">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/favorite_button.png" alt="保存" class="action-icon">
                            <span class="action-text"><?php echo $is_saved ? '保存済み' : '保存する'; ?></span>
                        </button>
                        <button class="action-button share-button">
                            <img src="<?php echo get_template_directory_uri(); ?>/images/share_button.png" alt="共有" class="action-icon">
                            <span class="action-text">共有</span>
                        </button>
                    </div>
                </div>
                <div class="recipe-description">
                    <?php echo nl2br(get_post_meta(get_the_ID(), 'recipe_description', true)); ?>
                </div>
            </div>

            <!-- 材料セクション -->
            <section class="recipe-materials">
                <h2>材料・道具</h2>
                <div class="materials-container">
                    <?php
                    $materials = get_post_meta(get_the_ID(), 'materials', true);
                    if ($materials && is_array($materials)) :
                        foreach ($materials as $material) : ?>
                            <div class="material-item">
                                <span class="material-name"><?php echo esc_html($material['name']); ?></span>
                                <?php if (!empty($material['url'])) : ?>
                                    <a href="<?php echo esc_url($material['url']); ?>" class="material-link" target="_blank">購入する</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
                <div class="tools-container">
                    <?php
                    $tools = get_post_meta(get_the_ID(), 'tools', true);
                    if ($tools && is_array($tools)) :
                        foreach ($tools as $tool) : ?>
                            <div class="tool-item">
                                <span class="tool-name"><?php echo esc_html($tool['name']); ?></span>
                                <?php if (!empty($tool['url'])) : ?>
                                    <a href="<?php echo esc_url($tool['url']); ?>" class="tool-link" target="_blank">購入する</a>
            <?php endif; ?>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </section>

            <!-- 作り方セクション -->
            <section class="recipe-steps">
            <h2>作り方</h2>
                <?php
                $steps = get_post_meta(get_the_ID(), 'steps', true);
                if ($steps && is_array($steps)) :
                    foreach ($steps as $index => $step) : ?>
                        <div class="step-item">
                            <div class="step-number">STEP <?php echo $index + 1; ?></div>
                            <?php if (!empty($step['image'])) : ?>
                                <div class="step-image">
                                    <img src="<?php echo esc_url($step['image']); ?>" alt="ステップ<?php echo $index + 1; ?>の画像">
                                </div>
                            <?php endif; ?>
                            <div class="step-description">
                                <?php echo nl2br(esc_html($step['description'])); ?>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </section>

            <!-- コメントセクション -->
            <div class="recipe-comments">
                <h3>コメント/質問</h3>
                <?php if (is_user_logged_in()): ?>
                    <form id="recipe-comment-form" data-recipe-id="<?php echo get_the_ID(); ?>">
                        <textarea placeholder="コメント/質問する" required></textarea>
                        <button type="submit" class="submit-comment">投稿する</button>
                    </form>
                <?php else: ?>
                    <div class="login-prompt">
                        コメントするには<a href="<?php echo wp_login_url(get_permalink()); ?>">ログイン</a>してください
                    </div>
                <?php endif; ?>
                <div class="comments-list">
                    <!-- コメントはJavaScriptで動的に読み込まれます -->
                </div>
            </div>
        </article>
    <?php endwhile; endif; ?>
</main>

<!-- シェアモーダル -->
<div class="share-modal" style="display: none;">
    <div class="share-modal-content">
        <h3>レシピをシェア</h3>
        <div class="share-buttons">
            <button class="share-twitter">Twitter</button>
            <button class="share-facebook">Facebook</button>
            <button class="share-line">LINE</button>
            <button class="copy-link">リンクをコピー</button>
        </div>
        <button class="close-modal">閉じる</button>
    </div>
</div>

<script>
    var recipeCommentsData = {
        rest_url: '<?php echo esc_url_raw(rest_url('recipe/v1/')); ?>',
        nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
        current_user_id: '<?php echo get_current_user_id(); ?>'
    };
</script>

<?php get_footer(); ?>