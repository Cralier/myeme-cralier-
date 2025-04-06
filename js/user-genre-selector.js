document.addEventListener('DOMContentLoaded', () => {
  const selectedArea = document.querySelector('.genre-selected');
  const dropdown = document.querySelector('.genre-dropdown');
  const allTags = dropdown.querySelectorAll('.genre-tag');
  const toggleButton = document.getElementById('toggle-button');

  if (!selectedArea || !dropdown || !toggleButton) return;

  // ▼ 初期表示：user_metaで保存されたジャンルを上に移動
  allTags.forEach(tag => {
    if (tag.classList.contains('selected')) {
      const genre = tag.dataset.genre;
      const exists = selectedArea.querySelector(`[data-genre="${genre}"]`);
      if (!exists) {
        const clone = tag.cloneNode(true);
        clone.classList.add('selected');
        clone.setAttribute('data-genre', genre);
        selectedArea.insertBefore(clone, toggleButton);
      }
    }
  });

  // ▼ 下段クリック：選択・上段に追加 or 削除
allTags.forEach(tag => {
    tag.addEventListener('click', () => {
      const genre = tag.dataset.genre;
      const existsInTop = selectedArea.querySelector(`[data-genre="${genre}"]`);
      const lowerTag = dropdown.querySelector(`[data-genre="${genre}"]`);
  
      const isSelected = tag.classList.toggle('selected');
  
      if (isSelected) {
        if (!existsInTop) {
          const clone = tag.cloneNode(true);
          clone.setAttribute('data-genre', genre);
          clone.classList.add('selected');
          selectedArea.insertBefore(clone, toggleButton);
        }
      } else {
        if (existsInTop) selectedArea.removeChild(existsInTop);
      }
  
      saveSelectedGenres();
    });
  });

// ▼ 上段クリックで解除（下段も解除）
selectedArea.addEventListener('click', (e) => {
    const tagEl = e.target;
    if (!tagEl.classList.contains('genre-tag')) return;
  
    const genre = tagEl.dataset.genre;
    const lowerTag = dropdown.querySelector(`[data-genre="${genre}"]`);
  
    // 上から削除
    selectedArea.removeChild(tagEl);
  
    // 下の選択解除
    if (lowerTag) lowerTag.classList.remove('selected');
  
    saveSelectedGenres();
  });

  // ▼ 保存処理
  function saveSelectedGenres() {
    const selected = [...selectedArea.querySelectorAll('.genre-tag')]
      .map(tag => tag.dataset.genre);

    fetch(UserGenreAjax.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'save_user_genres',
        genres: JSON.stringify(selected)
      })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        console.log('ジャンル保存成功');
      } else {
        console.error('ジャンル保存失敗');
      }
    });
  }
});