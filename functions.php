<?php
// テーマの基本設定
function mytheme_setup() {
    add_theme_support('title-tag'); // タイトルを自動表示
    add_theme_support('post-thumbnails'); // アイキャッチ画像
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'mytheme'),
    ));
}
add_action('after_setup_theme', 'mytheme_setup');

// ▼カスタム投稿タイプ「レシピ」
function create_custom_post_type() {
    register_post_type('recipe',
        array(
            'labels' => array(
                'name' => __('レシピ', 'mytheme'),
                'singular_name' => __('レシピ', 'mytheme'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'author'),
            'menu_icon' => 'dashicons-carrot',
            'rewrite' => array('slug' => 'recipe'),
        )
    );
}
add_action('init', 'create_custom_post_type');

// ▼レシピメタボックス
function add_recipe_meta_boxes() {
    add_meta_box('recipe_details', 'レシピ情報', 'recipe_meta_box_callback', 'recipe', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_recipe_meta_boxes');

function recipe_meta_box_callback($post) {
    $cooking_time = get_post_meta($post->ID, 'cooking_time', true);
    $materials = get_post_meta($post->ID, 'materials', true);
    $instructions = get_post_meta($post->ID, 'instructions', true);
    ?>
    <label for="cooking_time">調理時間（分）:</label>
    <input type="text" id="cooking_time" name="cooking_time" value="<?php echo esc_attr($cooking_time); ?>" style="width:100%;"><br><br>

    <label for="materials">材料:</label>
    <textarea id="materials" name="materials" style="width:100%;"><?php echo esc_textarea($materials); ?></textarea><br><br>

    <label for="instructions">作り方:</label>
    <textarea id="instructions" name="instructions" style="width:100%;"><?php echo esc_textarea($instructions); ?></textarea>
    <?php
}

function save_recipe_meta($post_id) {
    if (isset($_POST['handmade_genres'])) {
        $genres = explode(',', sanitize_text_field($_POST['handmade_genres']));
        update_post_meta($post_id, 'handmade_genres', $genres);
    }
    if (isset($_POST['cooking_time'])) {
        update_post_meta($post_id, 'cooking_time', sanitize_text_field($_POST['cooking_time']));
    }
    if (isset($_POST['materials'])) {
        update_post_meta($post_id, 'materials', sanitize_textarea_field($_POST['materials']));
    }
    if (isset($_POST['instructions'])) {
        update_post_meta($post_id, 'instructions', sanitize_textarea_field($_POST['instructions']));
    }
}
add_action('save_post', 'save_recipe_meta');

// Insert the function to save the logged-in user's ID to a custom field
function save_post_author_meta($post_id) {
    // 自動保存時は処理しない
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // 投稿タイプを限定（例：recipe のみ）
    if (get_post_type($post_id) !== 'recipe') return;

    // ログインユーザーのみ処理
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        update_post_meta($post_id, 'recipe_author', $user_id);
    }
}
add_action('save_post', 'save_post_author_meta');

// ▼ リダイレクト（ログイン成功後に /mypage/ユーザー名/ へ）
function redirect_to_user_mypage($redirect_to, $request, $user) {
    if (!is_wp_error($user)) {
        return home_url('/mypage/' . $user->user_login . '/');
    }
    return $redirect_to;
}

// ▼ /mypage/ユーザー名/ を user-mypage.php に対応させる
function add_mypage_rewrite_rule() {
    add_rewrite_rule('^mypage/([^/]+)/?$', 'index.php?user_mypage=$matches[1]', 'top');
}
add_action('init', 'add_mypage_rewrite_rule');

// ▼ クエリ変数 "user_mypage" を WordPress に認識させる
function add_mypage_query_var($vars) {
    $vars[] = 'user_mypage';
    return $vars;
}
add_filter('query_vars', 'add_mypage_query_var');

function load_user_mypage_template($template) {
    $user_login = get_query_var('user_mypage');
    if ($user_login) {
        $custom_template = get_template_directory() . '/user-mypage.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_user_mypage_template');

// Add function to enqueue jQuery UI and custom sortable script
function enqueue_custom_scripts() {
    // jQuery UI を CDN から読み込み
    wp_enqueue_script('jquery-ui-sortable');

    // 並び替え用のカスタムスクリプトを読み込み
    wp_enqueue_script('recipe-sortable', get_template_directory_uri() . '/js/recipe-sortable.js', array('jquery', 'jquery-ui-sortable'), null, true);

    // ▼自動削除JSを読み込み
    wp_enqueue_script(
    'recipe-autodelete',
    get_template_directory_uri() . '/js/recipe-autodelete.js',
    array(), null, true
    );
  
    // ▼JSに admin-ajax.php のURLを渡す
    wp_localize_script('recipe-autodelete', 'recipeAutoDelete', array(
    'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');

function my_enqueue_scripts() {
    wp_enqueue_script('jquery'); // jQuery本体
    wp_enqueue_script('jquery-ui-sortable'); // jQuery UI sortable
  }add_action('wp_enqueue_scripts', 'my_enqueue_scripts');

  
  
  
  function create_new_draft() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です。']);
        return;
    }

    $current_user_id = get_current_user_id(); // ← この取得はOK

    $post_data = array(
        'post_title'  => '',
        'post_status' => 'draft',
        'post_type'   => 'recipe',
        'post_author' => $current_user_id // ← これが欠けているとNG
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        wp_send_json_success(['post_id' => $post_id]);
    } else {
        wp_send_json_error(['message' => '下書きの作成に失敗しました。']);
    }
}

add_action('wp_ajax_create_new_draft', 'create_new_draft');
add_action('wp_ajax_nopriv_create_new_draft', 'create_new_draft'); // 未ログインでも動作するようにする場合 

function get_draft_id($user_id) {
    global $wpdb;
    
    // ユーザーの最新の下書きを取得
    $draft_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_status = 'draft' AND post_author = %d ORDER BY post_date DESC LIMIT 1",
            $user_id
        )
    );

    return $draft_id ? $draft_id : 0; // もし下書きがなければ 0 を返す
}

//▼プレビュー表示用
function enqueue_recipe_preview_script() {
    wp_enqueue_script(
      'recipe-preview',
      get_template_directory_uri() . '/js/recipe-preview.js',
      array(), // 依存スクリプトがあればここに（例：['jquery']）
      null,
      true // フッターで読み込む
    );
  }
  add_action('wp_enqueue_scripts', 'enqueue_recipe_preview_script');

//何も記入せずにブラウザバックした場合などに投稿が自動削除される
add_action('wp_ajax_auto_delete_draft', function () {
    if (!is_user_logged_in()) exit;
  
    $draft_id = intval($_POST['draft_post_id'] ?? 0);
    if (!$draft_id) exit;
  
    $post = get_post($draft_id);
    if (!$post || $post->post_type !== 'recipe') exit;
  
    $author_id = get_post_meta($post->ID, 'recipe_author', true);
    if ((int)$author_id !== get_current_user_id()) exit;
  
    if ($post->post_status === 'draft') {
        // ① タイトルチェック
        $title_empty = empty(trim($post->post_title));
      
        // ② 材料チェック
        $materials = get_post_meta($draft_id, 'materials', true);
        $materials_array = json_decode($materials, true);
        $materials_empty = empty(array_filter($materials_array ?? [], function ($i) {
            return !empty($i['name']);
        }));
      
        // ③ 作り方チェック
        $steps = get_post_meta($draft_id, 'steps', true);
        $steps_array = json_decode($steps, true);
        $steps_empty = empty(array_filter($steps_array ?? [], function ($s) {
            return !empty($s);
        }));
      
        // すべて未入力の場合のみ削除
        if ($title_empty && $materials_empty && $steps_empty) {
          wp_delete_post($draft_id, true);
        }
    }
  
    exit;
  });


function enqueue_genre_selector_script() {
    if (is_page('submit-recipe')) {
      wp_enqueue_script(
        'genre-selector.js',
        get_template_directory_uri() . '/js/genre-selector.js',
        [],
        null,
        true
      );
    }
  }
  add_action('wp_enqueue_scripts', 'enqueue_genre_selector_script');

//検索時の自動表示の追加
function enqueue_autocomplete_scripts() {
    if (is_page('submit-recipe')) {
      wp_enqueue_script(
        'autocomplete-materials',
        get_template_directory_uri() . '/js/autocomplete-materials.js',
        [],
        null,
        true
      );
      wp_enqueue_style(
        'autocomplete-style',
        get_template_directory_uri() . '/css/autocomplete.css'
      );
    }
    if (get_query_var('mypage_tools')) {
      wp_enqueue_script(
        'mypage-tools',
        get_template_directory_uri() . '/js/mypage-tools.js',
        [],
        null,
        true
      );
      wp_enqueue_style(
        'autocomplete-style',
        get_template_directory_uri() . '/css/autocomplete.css'
      );
      wp_localize_script('mypage-tools', 'MypageToolsAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mypage_tools_nonce')
      ]);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_autocomplete_scripts');


  function enqueue_mypage_genre_assets() {
    if (get_query_var('user_mypage')) {
        wp_enqueue_style('genre-selector-style', get_template_directory_uri());

        wp_enqueue_script(
            'genre-selector-js',
            get_template_directory_uri() . '/js/genre-selector.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'user-genre-selector',
            get_template_directory_uri() . '/js/user-genre-selector.js',
            [],
            null,
            true
        );

        wp_localize_script('user-genre-selector', 'UserGenreAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_mypage_genre_assets');


function save_user_genres() {
    // ユーザーがログインしていることを確認
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です']);
        return;
    }

    $user_id = get_current_user_id();

    // POSTされたジャンルを取得
    $genres = isset($_POST['genres']) ? json_decode(stripslashes($_POST['genres']), true) : [];

    // 配列であることを確認し、保存
    if (is_array($genres)) {
        update_user_meta($user_id, 'my_handmade_genres', $genres);
        wp_send_json_success(['message' => '保存しました']);
    } else {
        wp_send_json_error(['message' => '不正なデータ']);
    }
}

add_action('wp_ajax_save_user_genres', 'save_user_genres');
add_action('wp_ajax_nopriv_save_user_genres', 'save_user_genres');

function save_handmade_genres_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'recipe') return;
    
    if (isset($_POST['handmade_genres'])) {
        $genres = is_array($_POST['handmade_genres']) 
            ? array_map('sanitize_text_field', $_POST['handmade_genres'])
            : array(sanitize_text_field($_POST['handmade_genres']));
        update_post_meta($post_id, 'handmade_genres', $genres);
    }
}
add_action('save_post', 'save_handmade_genres_meta');

require_once get_template_directory() . '/functions/admin-update-materials.php';


add_filter('show_admin_bar', '__return_false');

function mytheme_scripts() {
    // ... existing enqueue scripts ...

    // レシピ詳細ページのスクリプト
    if (is_singular('recipe')) {
        wp_enqueue_script('recipe-detail', get_template_directory_uri() . '/js/recipe-detail.js', array('jquery'), '1.0', true);
        wp_localize_script('recipe-detail', 'wpApiSettings', array(
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
}
add_action('wp_enqueue_scripts', 'mytheme_scripts');

// レシピ保存用のREST APIエンドポイントを登録
function register_recipe_save_endpoints() {
    register_rest_route('wp/v2', '/save-recipe', array(
        'methods' => 'POST',
        'callback' => 'handle_save_recipe',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));

    register_rest_route('wp/v2', '/check-recipe-saved', array(
        'methods' => 'GET',
        'callback' => 'check_recipe_saved',
        'permission_callback' => function () {
            return is_user_logged_in();
        }
    ));
}
add_action('rest_api_init', 'register_recipe_save_endpoints');

// レシピ保存処理
function handle_save_recipe($request) {
    $recipe_id = $request->get_param('recipe_id');
    $user_id = get_current_user_id();
    
    if (!$recipe_id || !$user_id) {
        return new WP_Error('invalid_data', 'Invalid recipe ID or user ID', array('status' => 400));
    }

    // ユーザーの保存済みレシピを取得
    $saved_recipes = get_user_meta($user_id, 'saved_recipes', true);
    if (!is_array($saved_recipes)) {
        $saved_recipes = array();
    }

    // レシピが既に保存されているか確認
    $is_saved = in_array($recipe_id, $saved_recipes);
    
    if ($is_saved) {
        // 保存済みの場合は削除
        $saved_recipes = array_diff($saved_recipes, array($recipe_id));
        $message = 'Recipe removed from saved items';
        $is_now_saved = false;
    } else {
        // 未保存の場合は追加
        $saved_recipes[] = $recipe_id;
        $message = 'Recipe saved successfully';
        $is_now_saved = true;
    }

    // ユーザーメタを更新
    update_user_meta($user_id, 'saved_recipes', array_values($saved_recipes));

    return array(
        'success' => true,
        'message' => $message,
        'saved' => $is_now_saved
    );
}

// レシピが保存済みかチェック
function check_recipe_saved($request) {
    $recipe_id = $request->get_param('recipe_id');
    $user_id = get_current_user_id();
    
    if (!$recipe_id || !$user_id) {
        return new WP_Error('invalid_data', 'Invalid recipe ID or user ID', array('status' => 400));
    }

    $saved_recipes = get_user_meta($user_id, 'saved_recipes', true);
    if (!is_array($saved_recipes)) {
        $saved_recipes = array();
    }

    return array(
        'saved' => in_array($recipe_id, $saved_recipes)
    );
}

// ジャンルURLマッピングを読み込み
require_once get_template_directory() . '/functions/genre-urls.php';

// レシピのパーマリンク構造を変更
function custom_recipe_post_link($post_link, $post) {
    if ($post->post_type === 'recipe') {
        $genres = get_post_meta($post->ID, 'handmade_genres', true);
        if (!empty($genres) && is_array($genres)) {
            $first_genre = $genres[0];
            // genre-urls.phpのマッピングを使用
            $genre_slug = get_url_slug_from_genre($first_genre);
            return home_url("/recipe/{$genre_slug}/{$post->ID}/");
        } else {
            return home_url("/recipe/uncategorized/{$post->ID}/");
        }
    }
    return $post_link;
}
add_filter('post_type_link', 'custom_recipe_post_link', 10, 2);

// カスタムリライトルールを追加
function add_custom_rewrite_rules() {
    // 英語スラッグのURLルール
    add_rewrite_rule(
        '^recipe/([^/]+)/([0-9]+)/?$',
        'index.php?post_type=recipe&p=$matches[2]',
        'top'
    );
    
    // 未分類のURLルール
    add_rewrite_rule(
        '^recipe/uncategorized/([0-9]+)/?$',
        'index.php?post_type=recipe&p=$matches[1]',
        'top'
    );
}
add_action('init', 'add_custom_rewrite_rules');

// 古いURLパターンを新しいURLにリダイレクト
function redirect_old_recipe_urls() {
    if (is_singular('recipe')) {
        $post_id = get_the_ID();
        $current_url = $_SERVER['REQUEST_URI'];
        $correct_url = get_permalink($post_id);
        
        // 現在のURLが正しいURLと異なる場合はリダイレクト
        if ($current_url !== parse_url($correct_url, PHP_URL_PATH)) {
            wp_redirect($correct_url, 301);
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_old_recipe_urls');

// パーマリンク設定が変更された時にリライトルールをフラッシュ
function flush_rules_on_permalink_change() {
    flush_rewrite_rules();
}
add_action('permalink_structure_changed', 'flush_rules_on_permalink_change');

// コメント機能用のテーブルを作成
function create_recipe_comments_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'recipe_comments';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        recipe_id bigint(20) NOT NULL,
        user_id bigint(20) NOT NULL,
        parent_id bigint(20) DEFAULT 0,
        comment_text text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY recipe_id (recipe_id),
        KEY user_id (user_id),
        KEY parent_id (parent_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_recipe_comments_table');

// コメント投稿用のエンドポイントを登録
function register_recipe_comment_endpoints() {
    register_rest_route('recipe/v1', '/comments', array(
        'methods' => 'POST',
        'callback' => 'handle_recipe_comment',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));

    register_rest_route('recipe/v1', '/comments/(?P<recipe_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_recipe_comments',
        'permission_callback' => '__return_true'
    ));

    // 削除エンドポイントを追加
    register_rest_route('recipe/v1', '/comments/(?P<comment_id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_recipe_comment',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
}
add_action('rest_api_init', 'register_recipe_comment_endpoints');

// コメント投稿処理
function handle_recipe_comment($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipe_comments';
    
    $params = $request->get_json_params();
    $recipe_id = isset($params['recipe_id']) ? intval($params['recipe_id']) : 0;
    $comment_text = isset($params['comment_text']) ? sanitize_textarea_field($params['comment_text']) : '';
    $parent_id = isset($params['parent_id']) ? intval($params['parent_id']) : 0;
    $user_id = get_current_user_id();

    if (!$recipe_id || !$comment_text) {
        return new WP_Error('invalid_data', 'Invalid recipe ID or comment text', array('status' => 400));
    }

    // 親コメントが存在するか確認（返信の場合）
    if ($parent_id > 0) {
        $parent_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d AND recipe_id = %d",
            $parent_id,
            $recipe_id
        ));
        
        if (!$parent_exists) {
            return new WP_Error('invalid_parent', 'Parent comment does not exist', array('status' => 400));
        }
    }

    // 日本時間でタイムスタンプを生成
    $timezone = new DateTimeZone('Asia/Tokyo');
    $datetime = new DateTime('now', $timezone);
    $current_time = $datetime->format('Y-m-d H:i:s');

    $result = $wpdb->insert(
        $table_name,
        array(
            'recipe_id' => $recipe_id,
            'user_id' => $user_id,
            'parent_id' => $parent_id,
            'comment_text' => $comment_text,
            'created_at' => $current_time
        ),
        array('%d', '%d', '%d', '%s', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Could not save comment', array('status' => 500));
    }

    // 新しいコメントのデータを取得
    $comment = get_recipe_comment($wpdb->insert_id);

    return rest_ensure_response(array(
        'success' => true,
        'comment' => $comment
    ));
}

// コメント取得処理
function get_recipe_comments($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipe_comments';
    $recipe_id = $request['recipe_id'];

    if (!$recipe_id) {
        return new WP_Error('invalid_data', 'Invalid recipe ID', array('status' => 400));
    }

    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_login 
         FROM $table_name c 
         LEFT JOIN $wpdb->users u ON c.user_id = u.ID 
         WHERE c.recipe_id = %d 
         ORDER BY c.created_at DESC",
        $recipe_id
    ));

    if ($comments === null) {
        return rest_ensure_response(array(
            'success' => true,
            'comments' => array()
        ));
    }

    $formatted_comments = array_map(function($comment) {
        return array(
            'id' => intval($comment->id),
            'recipe_id' => intval($comment->recipe_id),
            'user_id' => intval($comment->user_id),
            'user_name' => $comment->display_name ?: $comment->user_login,
            'parent_id' => intval($comment->parent_id),
            'comment_text' => $comment->comment_text,
            'created_at' => $comment->created_at,
            'user_avatar' => get_avatar_url($comment->user_id)
        );
    }, $comments);

    return rest_ensure_response(array(
        'success' => true,
        'comments' => $formatted_comments
    ));
}

// 単一コメント取得
function get_recipe_comment($comment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipe_comments';

    $comment = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, u.display_name, u.user_login 
         FROM $table_name c 
         LEFT JOIN $wpdb->users u ON c.user_id = u.ID 
         WHERE c.id = %d",
        $comment_id
    ));

    if (!$comment) {
        return null;
    }

    return array(
        'id' => intval($comment->id),
        'recipe_id' => intval($comment->recipe_id),
        'user_id' => intval($comment->user_id),
        'user_name' => $comment->display_name ?: $comment->user_login,
        'parent_id' => intval($comment->parent_id),
        'comment_text' => $comment->comment_text,
        'created_at' => $comment->created_at,
        'user_avatar' => get_avatar_url($comment->user_id)
    );
}

// コメント用のスクリプトとスタイルを読み込み
function enqueue_recipe_comment_assets() {
    if (is_singular('recipe')) {
        wp_enqueue_script(
            'recipe-comments',
            get_template_directory_uri() . '/js/recipe-comments.js',
            array('jquery'),
            null,
            true
        );

        wp_localize_script('recipe-comments', 'recipeCommentsData', array(
            'rest_url' => rest_url('recipe/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'current_user_id' => get_current_user_id(),
            'user_avatar' => get_avatar_url(get_current_user_id())
        ));

        wp_enqueue_style(
            'recipe-comments-style',
            get_template_directory_uri() . '/css/recipe-comments.css'
        );
    }
}
add_action('wp_enqueue_scripts', 'enqueue_recipe_comment_assets');

// コメント画像のアップロード処理
function handle_comment_image_upload() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です。']);
        return;
    }

    if (!isset($_FILES['images'])) {
        wp_send_json_error(['message' => '画像がアップロードされていません。']);
        return;
    }

    $uploaded_urls = [];
    $files = $_FILES['images'];
    
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    foreach ($files['name'] as $key => $value) {
        if ($files['error'][$key] === 0) {
            $file = array(
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error'    => $files['error'][$key],
                'size'     => $files['size'][$key]
            );

            $upload_overrides = array('test_form' => false);
            $uploaded = wp_handle_upload($file, $upload_overrides);

            if (!isset($uploaded['error'])) {
                $uploaded_urls[] = $uploaded['url'];
            }
        }
    }

    if (empty($uploaded_urls)) {
        wp_send_json_error(['message' => '画像のアップロードに失敗しました。']);
        return;
    }

    wp_send_json_success(['image_urls' => $uploaded_urls]);
}
add_action('wp_ajax_upload_comment_images', 'handle_comment_image_upload');

// コメント削除処理
function delete_recipe_comment($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'recipe_comments';
    $comment_id = $request['comment_id'];
    $current_user_id = get_current_user_id();

    // コメントの所有者を確認
    $comment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $comment_id,
        $current_user_id
    ));

    if (!$comment) {
        return new WP_Error('unauthorized', 'You are not authorized to delete this comment', array('status' => 403));
    }

    // 返信コメントも削除
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE id = %d OR parent_id = %d",
        $comment_id,
        $comment_id
    ));

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Comment deleted successfully'
    ));
}

// mypage/ユーザー名/tools 用リライトルール
add_action('init', function() {
    add_rewrite_rule(
        '^mypage/([^/]+)/tools/?$',
        'index.php?user_mypage=$matches[1]&mypage_tools=1',
        'top'
    );
});

// クエリ変数追加
add_filter('query_vars', function($vars) {
    $vars[] = 'user_mypage';
    $vars[] = 'mypage_tools';
    return $vars;
});

// テンプレート振り分け
add_action('template_include', function($template) {
    if (get_query_var('mypage_tools')) {
        $custom = locate_template('mypage-tools.php');
        if ($custom) return $custom;
    }
    return $template;
});

add_action('wp_ajax_save_user_item_data', function() {
    check_ajax_referer('mypage_tools_nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です']);
        return;
    }
    $user_id = get_current_user_id();
    $items = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : [];
    if (!is_array($items)) $items = [];
    $saved = get_user_meta($user_id, 'mypage_tools', true);
    if (!is_array($saved)) $saved = [];
    foreach ($items as $item) {
        // id重複を防ぐ
        $exists = false;
        foreach ($saved as $s) {
            if (isset($s['id']) && $s['id'] === ($item['id'] ?? '')) {
                $exists = true;
                break;
            }
        }
        if (!$exists && isset($item['id']) && $item['id'] !== '') {
            $saved[] = [
                'id'    => $item['id'],
                'name'  => $item['name'] ?? '',
                'url'   => $item['url'] ?? '',
                'image' => $item['image'] ?? '',
                'type'  => $item['type'] ?? ''
            ];
        }
    }
    // 空要素・nullを除去
    $saved = array_values(array_filter($saved, function($v) {
        return is_array($v) && isset($v['id']) && $v['id'] !== '';
    }));
    error_log('保存前: ' . print_r($saved, true));
    $result = update_user_meta($user_id, 'mypage_tools', $saved);
    error_log('update_user_meta result: ' . print_r($result, true));
    $latest = get_user_meta($user_id, 'mypage_tools', true);
    if (!is_array($latest)) $latest = [];
    error_log('保存後: ' . print_r($latest, true));
    wp_send_json_success(['message' => '保存しました', 'items' => $latest]);
});

add_action('wp_ajax_get_user_item_data', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です']);
        return;
    }
    $user_id = get_current_user_id();
    $items = get_user_meta($user_id, 'mypage_tools', true);
    if (!is_array($items)) $items = [];
    // 空要素・nullを除去
    $items = array_values(array_filter($items, function($v) {
        return is_array($v) && isset($v['id']) && $v['id'] !== '';
    }));
    error_log('取得: ' . print_r($items, true));
    wp_send_json_success(['items' => $items]);
});

// usermeta保存・取得テスト用ショートコード
add_shortcode('usermeta_test', function() {
    if (!is_user_logged_in()) return 'ログインしてください';
    $user_id = get_current_user_id();
    if (isset($_GET['save_test'])) {
        $test = [['id' => 'test', 'name' => 'テスト']];
        update_user_meta($user_id, 'mypage_tools', $test);
        return '保存しました';
    }
    $data = get_user_meta($user_id, 'mypage_tools', true);
    return '<pre>' . print_r($data, true) . '</pre>';
});

// ユーザーの道具・材料IDのみ保存
add_action('wp_ajax_save_user_tool_ids', function() {
    check_ajax_referer('mypage_tools_nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です']);
        return;
    }
    $user_id = get_current_user_id();
    $ids = isset($_POST['ids']) ? json_decode(stripslashes($_POST['ids']), true) : [];
    if (!is_array($ids)) $ids = [];
    update_user_meta($user_id, 'mypage_tools_ids', $ids);
    wp_send_json_success(['message' => '保存しました']);
});

// ユーザーの道具・材料IDから詳細情報を返す
add_action('wp_ajax_get_user_tool_data', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'ログインが必要です']);
        return;
    }
    $user_id = get_current_user_id();
    $ids = get_user_meta($user_id, 'mypage_tools_ids', true);
    if (!is_array($ids)) $ids = [];
    $tools_items = [];
    $materials_items = [];

    $materials = json_decode(file_get_contents(ABSPATH . 'wp-content/uploads/autocomplete/materials.json'), true);
    $tools = json_decode(file_get_contents(ABSPATH . 'wp-content/uploads/autocomplete/tools.json'), true);

    $materials_map = [];
    foreach ($materials ?: [] as $item) {
        if (isset($item['id'])) $materials_map[(string)$item['id']] = $item;
    }
    $tools_map = [];
    foreach ($tools ?: [] as $item) {
        if (isset($item['id'])) $tools_map[(string)$item['id']] = $item;
    }

    foreach ($ids as $id) {
        $sid = (string)$id;
        if (isset($tools_map[$sid])) {
            $tools_items[] = [
                'id'    => $sid,
                'name'  => $tools_map[$sid]['name'] ?? '',
                'url'   => $tools_map[$sid]['URL'] ?? $tools_map[$sid]['url'] ?? '',
                'image' => $tools_map[$sid]['Image'] ?? $tools_map[$sid]['image'] ?? '',
                'type'  => 'tool',
            ];
        }
        if (isset($materials_map[$sid])) {
            $materials_items[] = [
                'id'    => $sid,
                'name'  => $materials_map[$sid]['name'] ?? '',
                'url'   => $materials_map[$sid]['URL'] ?? $materials_map[$sid]['url'] ?? '',
                'image' => $materials_map[$sid]['Image'] ?? $materials_map[$sid]['image'] ?? '',
                'type'  => 'material',
            ];
        }
    }
    // 「全て」はtools_items＋materials_items（ID重複なし前提で単純合体）
    $all_items = array_merge($tools_items, $materials_items);

    wp_send_json_success([
        'tools' => $tools_items,
        'materials' => $materials_items,
        'all' => $all_items
    ]);
});