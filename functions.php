
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
    $ingredients = get_post_meta($post->ID, 'ingredients', true);
    $instructions = get_post_meta($post->ID, 'instructions', true);
    ?>
    <label for="cooking_time">調理時間（分）:</label>
    <input type="text" id="cooking_time" name="cooking_time" value="<?php echo esc_attr($cooking_time); ?>" style="width:100%;"><br><br>

    <label for="ingredients">材料:</label>
    <textarea id="ingredients" name="ingredients" style="width:100%;"><?php echo esc_textarea($ingredients); ?></textarea><br><br>

    <label for="instructions">作り方:</label>
    <textarea id="instructions" name="instructions" style="width:100%;"><?php echo esc_textarea($instructions); ?></textarea>
    <?php
}

function save_recipe_meta($post_id) {
    if (isset($_POST['cooking_time'])) {
        update_post_meta($post_id, 'cooking_time', sanitize_text_field($_POST['cooking_time']));
    }
    if (isset($_POST['ingredients'])) {
        update_post_meta($post_id, 'ingredients', sanitize_textarea_field($_POST['ingredients']));
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
        $ingredients = get_post_meta($draft_id, 'ingredients', true);
        $ingredients_array = json_decode($ingredients, true);
        $ingredients_empty = empty(array_filter($ingredients_array ?? [], function ($i) {
            return !empty($i['name']);
        }));
      
        // ③ 作り方チェック
        $steps = get_post_meta($draft_id, 'steps', true);
        $steps_array = json_decode($steps, true);
        $steps_empty = empty(array_filter($steps_array ?? [], function ($s) {
            return !empty($s);
        }));
      
        // すべて未入力の場合のみ削除
        if ($title_empty && $ingredients_empty && $steps_empty) {
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