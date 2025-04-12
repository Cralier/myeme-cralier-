<script>
    let draftId = "<?php echo esc_js($draft_id); ?>";
    console.log("Draft ID:", draftId);
</script>

<?php

/*
Template Name: レシピ投稿フォーム
*/

$current_user = wp_get_current_user();
$draft_id = get_draft_id($current_user->ID);
echo "<!-- Debug: Draft ID = " . esc_html($draft_id) . " -->";
?>

<?php
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

// フォーム表示用の初期変数
$form_title = '';
$form_cooking_time = '';
$form_ingredients = [];
$form_tools = [];
$form_steps = [];
$editing_post = null;

$draft_post_id = isset($_GET['draft_post_id']) ? intval($_GET['draft_post_id']) : 0;

if ($draft_post_id) {
    $post = get_post($draft_post_id);

    if (!$post) {
        wp_die('投稿が見つかりません。');
    }

    if ($post->post_type !== 'recipe') {
        wp_die('レシピ投稿ではありません。');
    }

    $recipe_author = get_post_meta($post->ID, 'recipe_author', true);
    if (intval($recipe_author) !== get_current_user_id()) {
        wp_die('この下書きを編集する権限がありません。');
    }

    if (!in_array(get_post_status($post->ID), ['draft', 'publish'])) {
        wp_die('この投稿は編集できません。');
    }

    $editing_post = $post;
    $form_title = esc_attr($post->post_title);
    $form_cooking_time = get_post_meta($draft_post_id, 'cooking_time', true);
    $form_toolss = json_decode(get_post_meta($draft_post_id, 'tools', true), true) ?: [];
    $form_steps = json_decode(get_post_meta($draft_post_id, 'steps', true), true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current_user_id = get_current_user_id();
  $post_status = isset($_POST['save_as_draft']) ? 'draft' : 'publish';

  $post_data = array(
      'post_title'   => sanitize_text_field($_POST['recipe_title'] ?? ''),
      'post_content' => '', // 作り方を後で追加
      'post_status'  => $post_status,
      'post_type'    => 'recipe',
      'post_author'  => $current_user_id,
  );

  if ($draft_post_id && get_post_field('post_author', $draft_post_id) == $current_user_id) {
    $post_data['ID'] = $draft_post_id;
  }


  $post_id = wp_insert_post($post_data); // ← これで上書きになる

  // 画像をアイキャッチとして保存
  if (!empty($_FILES['recipe_image']['tmp_name'])) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('recipe_image', $post_id);
    if (!is_wp_error($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
    }
  }

  // ▼ 作品説明（自由記載）を保存
  if (isset($_POST['recipe_description'])) {
    update_post_meta($post_id, 'recipe_description', sanitize_textarea_field($_POST['recipe_description']));
  }
  // 作り方ステップを post_meta に保存（配列形式で JSON化）
  if (!empty($_POST['steps_text'])) {
    $steps_array = array_map('sanitize_textarea_field', $_POST['steps_text']);
    update_post_meta($post_id, 'steps', json_encode($steps_array, JSON_UNESCAPED_UNICODE));
  }

  // 調理時間・材料などのメタ情報も保存（任意）
  if (isset($_POST['cooking_time'])) {
      update_post_meta($post_id, 'cooking_time', sanitize_text_field($_POST['cooking_time']));
  }

  if (!empty($_POST['ingredient_name'])) {
      $ingredients = [];
      foreach ($_POST['ingredient_name'] as $index => $name) {
          $ingredients[] = [
              'select' => sanitize_text_field($_POST['ingredient_select'][$index] ?? ''),
              'name'   => sanitize_text_field($name),
              'url'    => esc_url_raw($_POST['ingredient_url'][$index] ?? ''),
          ];
      }
      update_post_meta($post_id, 'ingredients', json_encode($ingredients, JSON_UNESCAPED_UNICODE));
  }

  // ▼ 道具の保存
  if (!empty($_POST['tool_name'])) {
    $tools = [];
    foreach ($_POST['tool_name'] as $index => $name) {
        $tools[] = [
            'name' => sanitize_text_field($name),
            'url'  => esc_url_raw($_POST['tool_url'][$index] ?? ''),
        ];
    }
    update_post_meta($post_id, 'tools', json_encode($tools, JSON_UNESCAPED_UNICODE));
  }

  // 投稿完了後マイページへリダイレクト
  $user = wp_get_current_user();
  // 投稿後にマイページへリダイレクト（通知付き）
  $user = wp_get_current_user();
  if (isset($_POST['publish'])) {
    wp_redirect(home_url('/mypage/' . $user->user_login . '/?submitted=1'));
    exit;
  } elseif (isset($_POST['save_as_draft'])) {
    wp_redirect(home_url('/mypage/' . $user->user_login . '/?drafted=1'));
    exit;
  }
  }
get_header(); 
?>

<!-- -->

<script>
document.addEventListener('DOMContentLoaded', () => {
    const draftBtn = document.querySelector('button[name="save_as_draft"]');
    const titleInput = document.querySelector('input[name="recipe_title"]');
    const timeInput = document.querySelector('input[name="cooking_time"]');
  
    if (draftBtn && titleInput && timeInput) {
      draftBtn.addEventListener('click', () => {
        // 下書き保存時のみ required を一時的に無効化、Bodyの中のInputにRequiredを追加する場合、こちらも追加する必要あり
        titleInput.removeAttribute('required');
      });
    }
  });
 </script>



<!-- レシピ投稿フォームのメイン部分 -->
<main class="recipe-container">

  <!-- 投稿フォーム -->
  <form method="post" class="recipe-form" enctype="multipart/form-data">

  <!-- ▼▼▼投稿の下書きが自動送信される hidden input ▼▼▼ -->
  <?php if ($editing_post): ?>
    <input type="hidden" name="draft_post_id" value="<?php echo esc_attr($editing_post->ID); ?>">
  <?php endif; ?>
  <input type="hidden" name="submit_type" id="submit_type" value="">
  <!-- ▲▲▲ hidden input 終了 ▲▲▲ -->

<!-- アイキャッチ画像アップロード -->
<div class="featured-image-block">
  <!-- アップロード前の表示（カメラアイコン） -->
  <label for="recipe_image" class="image-upload-area" id="upload-prompt" style="display: block;">
    <img 
      src="<?php echo get_template_directory_uri(); ?>/images/upload-photo-icon.png" 
      alt="" 
      style="width: 60px; opacity: 0.6; border: none;">
    <p style="margin-top: 10px; font-weight: bold; color: #8b7f6d; font-size: 16px;">作品の写真のせる</p>
  </label>

  <!-- アップロード後のプレビュー画像もクリックで再選択 -->
  <label for="recipe_image" id="image-preview" class="image-preview-area" style="display: none;">
    <?php if ($editing_post): ?>
      <?php $thumb_url = get_the_post_thumbnail_url($editing_post->ID, 'medium'); ?>
      <?php if ($thumb_url): ?>
        <img 
          src="<?php echo esc_url($thumb_url); ?>" 
          alt="" 
          style="max-width: 100%; height: auto; border: none; outline: none; box-shadow: none; border-radius: 6px; object-fit: cover; display: block; margin: 0 auto;">
      <?php endif; ?>
    <?php endif; ?>
  </label>

  <!-- input要素 -->
  <input 
    type="file" 
    name="recipe_image" 
    id="recipe_image" 
    accept="image/*" 
    onchange="previewImage(event)" 
    hidden>
</div>

    <!-- レシピタイトル -->
    <label for="recipe_title"></label>
    <input type="text" name="recipe_title" value="<?php echo $form_title ?? ''; ?>" required  placeholder="作品名">

    <!-- ハンドメイドジャンル -->
    <?php
    require_once get_template_directory() . '/functions/genre-ui.php';
    $editing_post_id = $_GET['draft_post_id'] ?? null;
    $selected_genres = [];

    if ($editing_post_id) {
        $selected_genres = get_post_meta($editing_post_id, 'handmade_genres', true);
        if (!is_array($selected_genres)) $selected_genres = [];
    }
    render_handmade_genre_selector($selected_genres);
    ?>

    <!-- ▼▼▼ 自由記載欄（作品の説明やポイントなど） ▼▼▼  phpの前は絶対に改行してはいけない！！！！！！！最初から4文字スペースが入ってしまうため、こだわりポイント〜が表示されなくなる。-->
    <label for="recipe_description"></label>
    <textarea
    name="recipe_description"
    id="recipe_description"
    rows="4"
    placeholder="こだわりポイント、作品のコンセプト、作ったきっかけ など自由にご記入ください"
    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; margin-top: 8px;"><?php echo esc_textarea(get_post_meta($draft_post_id, 'recipe_description', true)); ?></textarea>
    <hr class="step-separator">

    <!-- ▼ 材料・道具 検索 -->
    <section id="material-tool-search-section">
    <h2>材料・道具を追加</h2>
    <input type="text" id="material-tool-search-input" placeholder="材料・道具名を入力" />
  </section>

  <section id="ingredients-section">
    <h3>材料</h3>
    <div id="ingredients-wrapper" class="ui-sortable">
    <p class="auto-added-note" id="material-hint">自動で追加されます</p>
      <!-- ここにJSで材料が挿入される -->
    </div>
  </section>

  <section id="tools-section">
    <h3>道具</h3>
    <div id="tools-wrapper" class="ui-sortable">
      <p class="auto-added-note" id="tool-hint">自動で追加されます</p>
    <!-- ここにJSで道具が挿入される -->
  </div>
</section>

<hr class="step-separator">

    <!-- ▼ 作り方ステップ（手順＋画像） -->
    <section id="steps-wrapper">
      <h2>作り方</h2>
      <div id="steps-container">
        <!-- 手順ブロック（初期表示） -->
        <div class="step-item">
          <div class="step-header">
            <span class="handle">≡</span>
            <span class="step-label">手順 1</span>
            <button type="button" class="remove-step">この手順を削除</button>
          </div>
          <textarea name="steps_text[]" placeholder="作り方の説明を記入してください"></textarea>
          <div class="image-drop-area">
            <input type="file" name="steps_image[]" class="step-image-input">
            <div class="image-preview"></div>
          </div>
        </div>
        </div>
      <button type="button" id="add-step">＋作り方を追加</button>
    </section>

<hr class="step-separator">

  <!-- 調理時間 -->
  <label for="cooking_time">調理時間 (分):</label>
  <input type="number" name="cooking_time" value="<?php echo $form_cooking_time ?? ''; ?>">


<hr class="step-separator">

<!-- プレビューボタン -->
<div class="submit-buttons">
  <button type="button" id="toggle-preview" class="btn-preview">プレビューを見る</button>
</div>
<div class="submit-buttons">
  <button type="submit" name="save_as_draft" value="1" class="btn-draft">下書きに保存する</button>
  <button type="submit" name="publish" value="1" class="btn-publish">投稿する</button>
</div>

</form>

<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function(){
    const preview = document.getElementById('image-preview');
    const prompt = document.getElementById('upload-prompt');
    if (prompt) prompt.style.display = 'none'; // ← アップロード前ラベル非表示に
    preview.innerHTML = `<img src="${reader.result}" style="max-width: 100%; height: auto; border-radius: 6px; object-fit: contain;">`;
    preview.style.display = 'block'; // ← プレビュー表示に切り替え
  };
  if (event.target.files[0]) {
    reader.readAsDataURL(event.target.files[0]);
  }
}
</script>

<!-- プレビュー表示エリア -->
<div id="preview-section" style="display: none;">
  <h2>プレビュー</h2>
  <div class="preview-box">

  <h3>写真</h3>
  <div id="preview-image" style="margin-bottom: 16px;">

    <p><strong>レシピ名:</strong> <span id="preview-title"></span></p>

    <h3>作品の説明</h3>
    <p id="preview-description"></p>

    <h3>材料</h3>
    <ul id="preview-ingredients"></ul>

    <h3>作り方</h3>
    <ol id="preview-steps"></ol>
  </div>

  <h3>作成時間</h3>
  <p><strong>調理時間:</strong><span id="preview-time"></span> 分</p>

  <button type="button" id="back-to-form">編集に戻る</button>
</div>


<?php get_footer(); ?>
<!-- jQuery本体 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- jQuery UI（sortableの本体） -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- jQuery UI Touch Punch（スマホ用補助ライブラリ）※必ずjQuery UIの後 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

