<footer>
    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
</footer>
<?php wp_footer(); ?>
</body>

<!-- summit-recipeを変更したら遷移先を変更可能 -->
<a href="#" class="floating-button" id="create-recipe">
    投稿作成
</a>

<script>
document.getElementById("create-recipe").addEventListener("click", function(event) {
    event.preventDefault(); // デフォルトの遷移を防ぐ

    fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=create_new_draft"
    })
    .then(response => response.json())
    .then(data => {
    console.log("Ajaxレスポンス:", data); // デバッグ用

    // data.data.post_id を正しく参照
    if (data.success && data.data && data.data.post_id) {
       // これに修正してみてください（直接URLを文字列で書く）
       const url = "/submit-recipe/?draft_post_id=" + data.data.post_id;
       window.location.assign(url);
    } else {
        alert("エラーが発生しました。再試行してください。");
    }
})
})
</script>
