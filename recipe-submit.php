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
    $form_ingredients = json_decode(get_post_meta($draft_post_id, 'ingredients', true), true) ?: [];
    $form_steps = explode("【手順", $post->post_content);
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

  // 作り方ステップを本文に保存
  if (!empty($_POST['steps_text'])) {
      $instructions = '';
      foreach ($_POST['steps_text'] as $index => $step) {
          $instructions .= "【手順 ".($index + 1)."】\n" . sanitize_textarea_field($step) . "\n\n";
      }
      wp_update_post([
          'ID' => $post_id,
          'post_content' => $instructions
      ]);
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
      update_post_meta($post_id, 'ingredients', wp_json_encode($ingredients));
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
  <h1>レシピを投稿する</h1>

  <!-- 投稿フォーム -->
  <form method="post" class="recipe-form" enctype="multipart/form-data">

    <!-- ▼▼▼投稿の下書きが自動送信される hidden input ▼▼▼ -->
    <?php if ($editing_post): ?>
  <input type="hidden" name="draft_post_id" value="<?php echo esc_attr($editing_post->ID); ?>">
  <?php endif; ?>
    <input type="hidden" name="submit_type" id="submit_type" value="">
    <!-- ▲▲▲ hidden input 終了 ▲▲▲ -->

    <!-- 画像アップロード（アイキャッチ） -->
    <label for="recipe_image" class="image-upload-area" id="image-preview">
      <p>作品の写真をのせる</p>
    </label>
    <input type="file" name="recipe_image" id="recipe_image" accept="image/*" onchange="previewImage(event)" hidden>

    <!-- レシピタイトル -->
    <label for="recipe_title">レシピ名:</label>
    <input type="text" name="recipe_title" value="<?php echo $form_title ?? ''; ?>" required>

    <hr class="step-separator">

  <!-- ▼ 材料リスト -->
  <section id="ingredients-section">
    <h2>材料</h2>
    <div id="ingredients-wrapper">
      <!-- 材料ブロックはJSで追加 -->
    </div>
    <button type="button" id="add-ingredient">＋ 材料を追加する</button>
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

<!-- プレビュー表示エリア -->
<div id="preview-section" style="display: none;">
  <h2>プレビュー</h2>
  <div class="preview-box">
  <p><strong>レシピ名:</strong> <span id="preview-title"></span></p>

  <h3>材料</h3>
  <ul id="preview-ingredients"></ul>

  <h3>作り方</h3>
  <ol id="preview-steps"></ol>
  </div>

  <h3>作成時間</h3>
  <p><strong>調理時間:</strong><span id="preview-time"></span> 分</p>
  <!-- ✅ 戻るだけでOKなボタン -->
  <button type="button" id="back-to-form">編集に戻る</button>
</div>


<?php get_footer(); ?>
<!-- jQuery本体 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- jQuery UI（sortableの本体） -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- jQuery UI Touch Punch（スマホ用補助ライブラリ）※必ずjQuery UIの後 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<!-- 最後に自作の recipe-sortable.js（あなたのJS） -->
<script src="path/to/recipe-sortable.js"></script>