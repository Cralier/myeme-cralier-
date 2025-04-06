<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ジャンル選択テスト</title>
  <style>
    body {
      font-family: sans-serif;
      padding: 20px;
    }

    .genre-section {
      border: 1px solid #ddd;
      padding: 16px;
      border-radius: 8px;
      background: #fff8f4;
      max-width: 400px;
    }

    .genre-selected {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 10px;
    }

    .genre-tag {
      border: 1px solid #d0bfae;
      background: #fff;
      color: #856e5a;
      border-radius: 16px;
      padding: 4px 12px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .genre-tag.selected {
      background-color: #f5e8db;
      font-weight: bold;
    }

    .genre-toggle {
      cursor: pointer;
      color: #856e5a;
      background: transparent;
      font-size: 14px;
      margin-left: auto;
      margin-top: 8px;
    }

    .genre-dropdown {
      display: none;
      flex-wrap: wrap;
      gap: 8px;
      background-color: #fdf8f2;
      padding: 10px;
      border-radius: 8px;
      margin-top: 10px;
    }

    .genre-dropdown.open {
      display: flex;
    }
  </style>
</head>
<body>

<div class="genre-section">
  <div class="genre-selected" id="selected-area">
    <!-- 選択済み -->
  </div>
  <span class="genre-toggle" id="toggle-button">… 全て表示</span>

  <div class="genre-dropdown" id="dropdown-area">
    <!-- 全ジャンル -->
    <span class="genre-tag">レジン</span>
    <span class="genre-tag">刺繍</span>
    <span class="genre-tag">革</span>
    <span class="genre-tag">キャンドル</span>
    <span class="genre-tag">ビーズ</span>
    <span class="genre-tag">フェルト</span>
    <span class="genre-tag">フラワー</span>
  </div>
</div>

<script>
  const selectedArea = document.getElementById('selected-area');
  const dropdown = document.getElementById('dropdown-area');
  const toggleButton = document.getElementById('toggle-button');

  // ▼「全て表示」クリックで開く
  toggleButton.addEventListener('click', (e) => {
    e.stopPropagation();
    dropdown.classList.add('open');
  });

  // ▼ ジャンルを選択 or 解除
  dropdown.addEventListener('click', (e) => {
    if (!e.target.classList.contains('genre-tag')) return;

    e.target.classList.toggle('selected');

    // 既に選択済みなら先に削除
    const existing = selectedArea.querySelector(`[data-genre="${e.target.textContent}"]`);
    if (existing) {
      selectedArea.removeChild(existing);
    }

    // 新たに追加（選択中のみ）
    if (e.target.classList.contains('selected')) {
      const tag = e.target.cloneNode(true);
      tag.setAttribute('data-genre', e.target.textContent);
      selectedArea.appendChild(tag);
    }
  });

  // ▼ 外クリックで閉じる
  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && !toggleButton.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
</script>

</body>
</html>