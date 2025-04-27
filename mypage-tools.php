<?php
/* マイページ > あなたの材料・道具 一覧ページ */
get_header();

// ユーザーがログインしているか確認
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
?>
<main class="mypage-container">
    <div class="profile-tabs">
        <a href="<?php echo home_url('/mypage/' . $current_user->user_login); ?>" class="tab-btn">作品</a>
        <a href="#" class="tab-btn active">パーツ</a>
    </div>

    <section class="tools-section">
        <h2 class="tools-title">あなたの材料・道具</h2>
        <div class="search-container">
            <input type="text" id="material-tool-search-input" class="search-input" placeholder="材料や道具を検索">
        </div>
        <div class="tool-material-tabs" style="margin: 16px 0; display: flex; gap: 12px; justify-content: center;">
            <button class="tool-tab-btn active" data-tab="all">全て</button>
            <button class="tool-tab-btn" data-tab="tools">道具</button>
            <button class="tool-tab-btn" data-tab="materials">材料</button>
        </div>
        <div id="materials-wrapper" class="tools-list-wrapper"></div>
        <div id="tools-wrapper" class="tools-list-wrapper" style="display:none;"></div>
        <div id="materials-only-wrapper" class="tools-list-wrapper" style="display:none;"></div>
    </section>
</main>


<style>
.search-section {
    margin-bottom: 30px;
}

.search-container {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.search-input {
    width: 100%;
    padding: 12px 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-top: 5px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
}

.search-result-item {
    padding: 10px 20px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.work-item {
    position: relative;
}

.item-type {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.item-name {
    position: absolute;
    bottom: 10px;
    left: 10px;
    right: 10px;
    background: rgba(0,0,0,0.6);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
}

.tools-list-wrapper {
    margin-top: 16px;
}
.tool-material-row-horizontal {
    background: #f7f0e7;
    border: 1.5px solid #bda588;
    border-radius: 12px;
    margin-bottom: 16px;
    padding: 0;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.tmr-inner {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 12px 12px 12px 0;
}
.tmr-handle {
    font-size: 22px;
    color: #bda588;
    margin: 0 16px 0 12px;
    cursor: grab;
    flex-shrink: 0;
}
.tmr-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.tmr-name {
    font-weight: bold;
    font-size: 16px;
    color: #333;
    margin-bottom: 4px;
    word-break: break-all;
}
.tmr-url {
    font-size: 13px;
    color: #a67c52;
    word-break: break-all;
}
.tmr-url a {
    color: #a67c52;
    text-decoration: underline;
}
.tmr-img {
    width: 56px;
    height: 56px;
    border-radius: 8px;
    overflow: hidden;
    margin-left: 16px;
    flex-shrink: 0;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.tmr-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}
</style>

<?php get_footer(); ?> 