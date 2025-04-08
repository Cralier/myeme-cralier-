<?php
// update-material-tool-json.php

// スプレッドシートの情報
$apiKey = 'AIzaSyCEMkLzAtnZ863m92SUQkaGGjd98t2fALk';
$spreadsheetId = '1QtRdqMRBrBKOgorRZBibc1bqk8nB-W5iHcC2LLu_iDw';
$baseUrl = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/";

function fetchSheetData($sheetName, $apiKey, $baseUrl) {
    $url = $baseUrl . urlencode($sheetName) . '?key=' . $apiKey;
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (!isset($data['values']) || count($data['values']) < 2) return [];

    $headers = $data['values'][0];
    $rows = array_slice($data['values'], 1);
    $result = [];

    foreach ($rows as $row) {
        $entry = [];
        foreach ($headers as $i => $key) {
            $entry[$key] = $row[$i] ?? '';
        }
        $result[] = $entry;
    }

    return $result;
}

$materials = fetchSheetData('材料', $apiKey, $baseUrl);
$tools     = fetchSheetData('道具', $apiKey, $baseUrl);

// 保存先（必要に応じてパス調整）
$saveDir = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/autocomplete';
if (!is_dir($saveDir)) mkdir($saveDir, 0755, true);

file_put_contents("$saveDir/materials.json", json_encode($materials, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents("$saveDir/tools.json", json_encode($tools, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "✅ JSONファイルを更新しました\n";
// 取得結果の確認ログ（一時追加）
error_log(print_r($material_data, true));
