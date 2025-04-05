<?php
/*
Template Name: 画像アップロードテスト
*/
get_header();
?>

<main class="test-container">
  <h1>画像アップロードテスト</h1>

  <form method="post" enctype="multipart/form-data" class="image-test-form">
    <!-- ラベルと連携 -->
    <label for="test_image" class="image-upload-area" style="cursor:pointer; border:2px dashed #ccc; padding:20px; text-align:center;">
      <p>ここをタップして画像を選択</p>
    </label>

    <!-- プレビュー表示 -->
    <div id="image-preview" style="margin-top:10px;"></div>

    <!-- 実ファイル input -->
    <input type="file" name="test_image" id="test_image" accept="image/*" onchange="previewImage(event)" hidden>
  </form>
</main>

<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function(){
    const preview = document.getElementById('image-preview');
    preview.innerHTML = `<img src="${reader.result}" style="max-width: 100%; border-radius: 6px;">`;
  };
  if (event.target.files[0]) {
    reader.readAsDataURL(event.target.files[0]);
  }
}
</script>

<?php get_footer(); ?>