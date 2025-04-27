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
  searchInput?.addEventListener('input', () => {
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
        addItemToArea(item, item.type);
        saveItemToUser(item.id, item.type);
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

  // ▼ 材料・道具欄へ追加
  function addItemToArea(item, type) {
    const targetId = type === 'material' ? 'materials-wrapper' : 'tools-wrapper';
    const wrapper = document.getElementById(targetId);
    if (!wrapper) return;

    const hint = document.getElementById(`${type}-hint`);
    if (hint) hint.remove();

    const div = document.createElement('div');
    div.className = `${type}-row recipe-sortable-item`;

    div.innerHTML = `
      <div class="handle ui-sortable-handle">☰</div>
      <div class="input-wrapper">
        <input type="text" name="${type}_name[]" value="${item.name}" class="${type}-name-input" placeholder="${type === 'material' ? '材料名を入力' : '道具名を入力'}">
        <input type="url" name="${type}_url[]" value="${item.URL || ''}" class="${type}-url-input" placeholder="URL（任意）">
      </div>
      <div class="step-actions">
        <button type="button" class="step-menu-toggle">⋯</button>
        <div class="step-menu" style="display: none;">
      <button type="button" class="remove-${type}">削除</button>
        </div>
      </div>
    `;

    wrapper.appendChild(div);
    setupItemListeners(div);
    updateSortable(wrapper);
  }

  // ▼ アイテムのイベントリスナー設定
  function setupItemListeners(item) {
    const menuToggle = item.querySelector('.step-menu-toggle');
    const menu = item.querySelector('.step-menu');
    const removeButton = item.querySelector('.remove-material, .remove-tool');

    if (menuToggle && menu) {
      menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = menu.style.display === 'block';
        // 他のメニューを閉じる
        document.querySelectorAll('.step-menu').forEach(m => {
          if (m !== menu) m.style.display = 'none';
        });
        menu.style.display = isVisible ? 'none' : 'block';
      });
    }

    if (removeButton) {
      removeButton.addEventListener('click', () => {
        item.remove();
        const wrapper = item.closest('#materials-wrapper, #tools-wrapper');
        const type = wrapper.id === 'materials-wrapper' ? 'material' : 'tool';
        
        if (wrapper.children.length === 0) {
          const hint = document.createElement('p');
          hint.className = 'auto-added-note';
          hint.id = `${type}-hint`;
          hint.textContent = '自動で追加されます';
          wrapper.appendChild(hint);
        }
      });
    }
  }

  // ▼ 並び替え初期化
  function updateSortable(wrapper) {
    if (typeof $ === 'undefined') return;

    $(wrapper).sortable({
      handle: '.handle',
      placeholder: 'sortable-placeholder',
      tolerance: 'pointer'
    });
  }

  // ▼ 外クリックで閉じる
  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
      hideDropdown();
    }
    if (!e.target.closest('.step-menu-toggle')) {
      document.querySelectorAll('.step-menu').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });
});

function saveItemToUser(id, type) {
  fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      action: 'save_user_item_data',
      list_type: 'user_item_history',
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