document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('material-tool-search-input');
  const dropdown = document.createElement('div');
  dropdown.className = 'autocomplete-dropdown';
  document.body.appendChild(dropdown);

  let materials = [];
  let tools = [];

  // ▼ JSONデータを事前ロード
  async function loadData() {
    try {
      const matRes = await fetch('/wp-content/uploads/autocomplete/materials.json');
      const toolRes = await fetch('/wp-content/uploads/autocomplete/tools.json');
      materials = await matRes.json();
      tools = await toolRes.json();
    } catch (err) {
      console.error('JSON取得失敗:', err);
    }
  }
  loadData();

  // ▼ 入力監視
  searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim();
    if (!query) {
      hideDropdown();
      return;
    }

    const combined = [
      ...materials.map(m => ({ ...m, type: 'material' })),
      ...tools.map(t => ({ ...t, type: 'tool' }))
    ];

    const matched = combined.filter(item =>
      item.name.includes(query)
    ).slice(0, 4);

    renderDropdown(matched);
  });

  // ▼ 候補を表示
  function renderDropdown(items) {
    dropdown.innerHTML = '';
    if (!items.length) return hideDropdown();

    const rect = searchInput.getBoundingClientRect();
    dropdown.style.display = 'block';
    dropdown.style.position = 'absolute';
    dropdown.style.top = `${window.scrollY + rect.bottom + 4}px`;
    dropdown.style.left = `${window.scrollX + rect.left}px`;
    dropdown.style.width = `${rect.width}px`;

    items.forEach(item => {
      const div = document.createElement('div');
      div.className = 'autocomplete-option';
      div.textContent = item.name;
      div.addEventListener('click', () => {
        addItemToArea(item, item.type); // ▼ 材料/道具を判定して追加
        saveItemToUser(item.id, item.type); // ← これを追加！
        searchInput.value = '';
        hideDropdown();
      });
      dropdown.appendChild(div);
    });
  }

  // ▼ ドロップダウン非表示
  function hideDropdown() {
    dropdown.style.display = 'none';
  }

  // ▼ 材料・道具欄へ追加（既存のHTML構造に準拠）
  function addItemToArea(item, type) {
    const wrapper = (type === 'material')
      ? document.getElementById('ingredients-wrapper')
      : document.getElementById('tools-wrapper');

      // すでにあるこの関数の中に追記でOK
      const hint = document.getElementById(type === 'material' ? 'material-hint' : 'tool-hint');
      if (hint) hint.remove(); // ← 追加時にヒントを削除


    const div = document.createElement('div');
    div.className = `${type}-row recipe-sortable-item`;

    div.innerHTML = `
      <div class="handle ui-sortable-handle">☰</div>
      <input type="text" name="${type}_name[]" value="${item.name}" class="${type}-name-input" />
      <input type="url" name="${type}_url[]" value="${item.URL || ''}" class="${type}-url-input" />
      <button type="button" class="remove-${type}">削除</button>
    `;

    wrapper.appendChild(div);
    updateSortable(wrapper);
  }

  // ▼ 並び替え初期化
  function updateSortable(wrapper) {
    if (typeof jQuery === 'undefined' || typeof jQuery(wrapper).sortable !== 'function') return;
    jQuery(wrapper).sortable({
      handle: '.handle',
      items: '.recipe-sortable-item',
      tolerance: 'pointer'
    });
  }

  // ▼ 削除処理
  document.addEventListener('click', (e) => {
    if (e.target.matches('.remove-material') || e.target.matches('.remove-tool')) {
      const row = e.target.closest('.recipe-sortable-item');
      if (row) row.remove();
    }
  });

  // ▼ 外クリックで閉じる
  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
      hideDropdown();
    }
  });
});

function saveItemToUser(id, type) {
  fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action: 'save_user_item_data',
      list_type: 'user_item_history', // 履歴用
      items: JSON.stringify([{ id, type }])
    })
  })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      console.warn('履歴保存に失敗', data);
    }
  });
}