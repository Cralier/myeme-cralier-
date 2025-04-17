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
$form_tools = json_decode(get_post_meta($draft_post_id, 'tools', true), true) ?: [];
$form_materials = json_decode(get_post_meta($draft_post_id, 'materials', true), true) ?: [];
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
    $form_materials = json_decode(get_post_meta($draft_post_id, 'materials', true), true) ?: [];
    $form_tools = json_decode(get_post_meta($draft_post_id, 'tools', true), true) ?: [];
    $form_steps = json_decode(get_post_meta($draft_post_id, 'steps', true), true) ?: [];
    $step_image_urls = json_decode(get_post_meta($draft_post_id, 'steps_image_urls', true), true) ?: [];
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
  $step_image_urls = [];

  if (!empty($_FILES['steps_image']['name'][0])) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';
  
      foreach ($_FILES['steps_image']['name'] as $index => $name) {
          if (!empty($_FILES['steps_image']['tmp_name'][$index])) {
              $file_array = [
                  'name'     => $_FILES['steps_image']['name'][$index],
                  'type'     => $_FILES['steps_image']['type'][$index],
                  'tmp_name' => $_FILES['steps_image']['tmp_name'][$index],
                  'error'    => $_FILES['steps_image']['error'][$index],
                  'size'     => $_FILES['steps_image']['size'][$index]
              ];
  
              // 添付ファイルとしてアップロード
              $attachment_id = media_handle_sideload($file_array, $post_id);
              if (!is_wp_error($attachment_id)) {
                  $step_image_urls[] = wp_get_attachment_url($attachment_id);
              } else {
                  $step_image_urls[] = ''; // エラー時は空で埋める
              }
          } else {
              $step_image_urls[] = ''; // 画像が空の場合
          }
      }
  
      // 保存
      update_post_meta($post_id, 'steps_image_urls', json_encode($step_image_urls, JSON_UNESCAPED_UNICODE));
  }



  if (!empty($_POST['steps_text'])) {
    $steps_array = array_map('sanitize_textarea_field', $_POST['steps_text']);
    update_post_meta($post_id, 'steps', json_encode($steps_array, JSON_UNESCAPED_UNICODE));
  }

  // 調理時間・材料などのメタ情報も保存（任意）
  if (isset($_POST['cooking_time'])) {
      update_post_meta($post_id, 'cooking_time', sanitize_text_field($_POST['cooking_time']));
  }

  if (!empty($_POST['material_name'])) {
      $materials = [];
      foreach ($_POST['material_name'] as $index => $name) {
          $materials[] = [
              'select' => sanitize_text_field($_POST['material_select'][$index] ?? ''),
              'name'   => sanitize_text_field($name),
              'url'    => esc_url_raw($_POST['material_url'][$index] ?? ''),
          ];
      }
      update_post_meta($post_id, 'materials', json_encode($materials, JSON_UNESCAPED_UNICODE));
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
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            const preview = document.getElementById('image-preview');
            const prompt = document.getElementById('upload-prompt');
            const img = document.createElement('img');
            img.src = '<?php echo esc_url($thumb_url); ?>';
            img.style.maxWidth = '100%';
            img.style.borderRadius = '6px';
            img.style.objectFit = 'contain';
            preview.appendChild(img);
            preview.style.display = 'block';
            if (prompt) prompt.style.display = 'none';
          });
        </script>
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

  <section id="materials-section">
  <h3>材料</h3>
  <div id="materials-wrapper" class="ui-sortable">
    <?php if (empty($form_materials)): ?>
      <p class="auto-added-note" id="material-hint">自動で追加されます</p>
    <?php endif; ?>

    <?php foreach ($form_materials as $material): ?>
      <div class="material-row recipe-sortable-item">
        <div class="handle ui-sortable-handle">☰</div>
        <div class="input-wrapper">
          <input type="text" name="material_name[]" value="<?php echo esc_attr($material['name'] ?? ''); ?>" class="material-name-input" placeholder="材料名を入力" />
          <input type="url" name="material_url[]" value="<?php echo esc_url($material['url'] ?? ''); ?>" class="material-url-input" placeholder="材料のURL（任意）" />
        </div>
        <div class="step-actions">
          <button type="button" class="step-menu-toggle">⋯</button>
          <div class="step-menu" style="display: none;">
            <button type="button" class="remove-material">削除</button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section id="tools-section">
  <h3>道具</h3>
  <div id="tools-wrapper" class="ui-sortable">
    <?php if (empty($form_tools)): ?>
      <p class="auto-added-note" id="tool-hint">自動で追加されます</p>
    <?php endif; ?>

    <?php foreach ($form_tools as $tool): ?>
      <div class="tool-row recipe-sortable-item">
        <div class="handle ui-sortable-handle">☰</div>
        <div class="input-wrapper">
          <input type="text" name="tool_name[]" value="<?php echo esc_attr($tool['name'] ?? ''); ?>" class="tool-name-input" placeholder="道具名を入力" />
          <input type="url" name="tool_url[]" value="<?php echo esc_url($tool['url'] ?? ''); ?>" class="tool-url-input" placeholder="道具のURL（任意）" />
        </div>
        <div class="step-actions">
          <button type="button" class="step-menu-toggle">⋯</button>
          <div class="step-menu" style="display: none;">
            <button type="button" class="remove-tool">削除</button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<hr class="step-separator">

    <!-- ▼ 作り方ステップ（手順＋画像） -->
    <section id="steps-wrapper">
      <h2>作り方</h2>
      <div id="steps-container">
        <?php
          $form_steps = json_decode(get_post_meta($draft_post_id, 'steps', true), true) ?: [];
          $form_images = json_decode(get_post_meta($draft_post_id, 'steps_image_urls', true), true) ?: [];

          if (!empty($form_steps)) {
            foreach ($form_steps as $index => $text) {
              $image_url = $form_images[$index] ?? '';

              echo '<div class="step-item">';
              echo '<div class="step-header">';
              echo '<span class="handle">≡</span>';
              echo '<span class="step-label">手順 ' . ($index + 1) . '</span>';
              echo '<div class="step-actions">';
              echo '<button type="button" class="step-menu-toggle">⋯</button>';
              echo '<div class="step-menu" style="display: none;">';

              echo '<button type="button" class="remove-step">作り方を削除</button>';
              echo '</div></div></div>'; // step-header 終了

              echo '<div class="step-content">';
              echo '<textarea name="steps_text[]">' . esc_textarea($text) . '</textarea>';
              echo '<div class="image-drop-area">';
              echo '<label class="image-upload-label">';
              echo '<input type="file" name="steps_image[]" class="step-image-input" accept="image/*">';
              echo '<div class="image-preview">';
              if (!empty($image_url)) {
                echo '<img src="' . esc_url($image_url) . '" />';
              } else {
                echo '<img src="' . get_template_directory_uri() . '/images/upload-photo-icon.png" />';
              }
              echo '</div>';
              echo '</label></div>';
              echo '</div>'; // step-content 終了
              echo '</div>'; // step-item 終了
            }
          } else {
            // ステップが1件もないときの初期表示
            echo '<div class="step-item">';
            echo '<div class="step-header">';
            echo '<span class="handle">≡</span>';
            echo '<span class="step-label">手順 1</span>';
            echo '<div class="step-actions">';
            echo '<button type="button" class="step-menu-toggle">⋯</button>';
            echo '<div class="step-menu" style="display: none;">';
            echo '<button type="button" class="remove-step">作り方を削除</button>';
            echo '</div></div></div>';

            echo '<div class="step-content">';
            echo '<textarea name="steps_text[]" placeholder="作り方の説明を記入してください"></textarea>';
            echo '<div class="image-drop-area">';
            echo '<label class="image-upload-label">';
            echo '<input type="file" name="steps_image[]" class="step-image-input" accept="image/*">';
            echo '<div class="image-preview">';
            if (!empty($image_url)) {
              echo '<img src="' . esc_url($image_url) . '" />';
            } else {
              echo '<img src="' . get_template_directory_uri() . '/images/upload-photo-icon.png" />';
            }
            echo '</div>';
            echo '</label></div>';
            echo '</div></div>';
          }
        ?>
      </div>
      <button type="button" id="add-step">＋作り方を追加</button>
    </section>

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

<script>
document.addEventListener('DOMContentLoaded', () => {
  const imageInputs = document.querySelectorAll('.step-image-input');

  imageInputs.forEach(input => {
    input.addEventListener('change', (event) => {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function () {
        const preview = event.target.closest('.image-drop-area').querySelector('.image-preview');
        preview.innerHTML = `<img src="${reader.result}" style="max-width:100%; border-radius: 6px; object-fit: contain;">`;
      };
      reader.readAsDataURL(file);
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // 「…」ボタンの開閉処理
  document.querySelectorAll('.step-menu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      e.stopPropagation(); // 他のクリックイベントを防ぐ
      const menu = toggle.closest('.step-actions').querySelector('.step-menu');
      const isOpen = menu.style.display === 'block';
      document.querySelectorAll('.step-menu').forEach(m => m.style.display = 'none');
      menu.style.display = isOpen ? 'none' : 'block';
    });
  });

  // 外部クリックでメニュー閉じる
  document.addEventListener('click', () => {
    document.querySelectorAll('.step-menu').forEach(menu => {
      menu.style.display = 'none';
    });
  });
});
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
    <ul id="preview-materials"></ul>

    <h3>作り方</h3>
    <ol id="preview-steps"></ol>
  </div>

  <button type="button" id="back-to-form">編集に戻る</button>
</div>


<?php get_footer(); ?>
<!-- jQuery本体 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- jQuery UI（sortableの本体） -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- jQuery UI Touch Punch（スマホ用補助ライブラリ）※必ずjQuery UIの後 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

