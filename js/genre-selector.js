console.log('genre-selector.js loaded');

document.addEventListener('DOMContentLoaded', () => {
    const selectedArea = document.querySelector('.genre-selected');
    const dropdown = document.querySelector('.genre-dropdown');
    const toggleButton = document.getElementById('toggle-button');
    const hiddenInput = document.getElementById('handmade_genres_input');
  
    if (!selectedArea || !dropdown || !toggleButton || !hiddenInput) return;
  
    // ▼ トグル開閉
    toggleButton.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdown.classList.toggle('open');
      toggleButton.textContent = dropdown.classList.contains('open') ? '閉じる' : '… 全て表示';
    });
  
    // ▼ ジャンル選択（dropdown内のタグのみ反応）
    dropdown.addEventListener('click', (e) => {
        if (!e.target.classList.contains('genre-tag')) return;
    
        const genre = e.target.textContent;
    
        // トグル選択
        e.target.classList.toggle('selected');
    
        const existing = selectedArea.querySelector(`[data-genre="${genre}"]`);
        if (e.target.classList.contains('selected')) {
        // 上にまだなければ追加
        if (!existing) {
            const tag = e.target.cloneNode(true);
            tag.setAttribute('data-genre', genre);
            tag.classList.add('selected'); // 明示的に selected に
            selectedArea.appendChild(tag);
        }
        } else {
        // 選択解除されたら、上から削除
        if (existing) {
            selectedArea.removeChild(existing);
        }
        }
    
        // hidden input 更新
        const selectedGenres = [...document.querySelectorAll('.genre-dropdown .genre-tag.selected')].map(tag => tag.textContent);
        hiddenInput.value = selectedGenres.join(',');
    });
  
    // ▼ 上段（選択済み）をクリック → 解除して下に戻す
    selectedArea.addEventListener('click', (e) => {
        // 処理を無効化する（必要なら console.log などデバッグ用に残してもOK）
        e.preventDefault();
      });
    
    // ▼ 外クリック or 上部エリアクリック → 閉じる
    document.addEventListener('click', (e) => {
      if (
        !dropdown.contains(e.target) &&
        !toggleButton.contains(e.target) &&
        !selectedArea.contains(e.target)
      ) {
        dropdown.classList.remove('open');
        toggleButton.textContent = '… 全て表示';
      }
    });
  });