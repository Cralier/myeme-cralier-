<?php
// ジャンル名とURLのマッピング
function get_genre_url_mapping() {
    return [
        'レジン' => 'resin',
        '刺繍' => 'embroidery',
        '編み物' => 'knitting',
        'レザークラフト' => 'leathercraft',
        '羊毛フェルト' => 'woolfelt',
        'パッチワーク' => 'patchwork',
        'アイシングクッキー' => 'icing-cookies',
        '水彩画' => 'watercolor-painting',
        'ステンドグラス' => 'stained-glass',
        'タティングレース' => 'tatting-lace',
        'ビーズ' => 'beads',
        'マクラメ' => 'macrame',
        'シーリングスタンプ' => 'sealing-stamp',
        'セルフネイル' => 'diy-nail-art',
        'キャンドル' => 'candle-making',
        'キルト' => 'quilt',
        '粘土' => 'clay-craft',
        '絵画' => 'painting',
        'ドライフラワー' => 'dried-flowers',
        'カルトナージュ' => 'cartonnage',
        '樹脂粘土' => 'resin-clay',
        'フラワーアレンジメント' => 'flower-arrangement',
        'バーバリウム' => 'herbarium',
        'ペーパークラフト' => 'paper-craft',
        'プラ板' => 'shrink-plastic',
        '天然石' => 'natural-stones',
        '草木染め' => 'plant-dyeing',
        '石粉粘土' => 'stone-powder-clay',
        'オーブン粘土' => 'oven-clay',
        '裂き織り' => 'sakiori',
        'フェイクスイーツ' => 'fake-sweets',
        '布絵' => 'fabric-art'
    ];
}

// URLスラッグからジャンル名を取得
function get_genre_name_from_url($url_slug) {
    $mapping = array_flip(get_genre_url_mapping());
    return isset($mapping[$url_slug]) ? $mapping[$url_slug] : false;
}

// ジャンル名からURLスラッグを取得
function get_url_slug_from_genre($genre_name) {
    $mapping = get_genre_url_mapping();
    return isset($mapping[$genre_name]) ? $mapping[$genre_name] : sanitize_title($genre_name);
} 