<?php
// 管理メニューに追加
add_action('admin_menu', function() {
  add_management_page(
    '材料データ更新',
    '材料データ更新',
    'manage_options',
    'update-materials',
    'render_material_update_page'
  );
});

// 管理画面の表示内容
function render_material_update_page() {
  if (isset($_POST['update_materials'])) {
    include_once get_template_directory() . '/functions/update-material-tool-json.php';
    echo '<div class="notice notice-success"><p>更新を実行しました。</p></div>';
  }
  ?>
  <div class="wrap">
    <h1>材料・道具データの更新</h1>
    <form method="post">
      <input type="hidden" name="update_materials" value="1">
      <p><button class="button button-primary">スプレッドシートからデータ更新</button></p>
    </form>
  </div>
  <?php
}