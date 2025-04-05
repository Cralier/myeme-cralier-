<form role="search" method="get" id="searchform" action="<?php echo home_url('/'); ?>">
    <input type="text" name="s" id="s" placeholder="レシピを検索..." />
    <input type="hidden" name="post_type" value="recipe" />
    <button type="submit">🔍</button>
</form>