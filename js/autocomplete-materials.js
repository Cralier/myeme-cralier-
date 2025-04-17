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
    div.style.cssText = `
      background-color: #F5EBE1;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
    `;

    div.innerHTML = `
      <div class="handle" style="background-color: #e8dcc7; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 6px; cursor: grab;">≡</div>
      <div class="input-wrapper" style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
        <input type="text" name="${type}_name[]" value="${item.name}" class="${type}-name-input" placeholder="${type === 'material' ? '材料名を入力' : '道具名を入力'}" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px;">
        <input type="url" name="${type}_url[]" value="${item.URL || ''}" class="${type}-url-input" placeholder="URL（任意）" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px;">
      </div>
      <div class="step-actions" style="position: relative;">
        <button type="button" class="step-menu-toggle" style="background-color: #bda58b; width: 40px; height: 40px; border: none; border-radius: 6px; color: white; font-size: 20px; cursor: pointer; position: relative;">
          <span class="dots" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 24px; height: 24px;">
            <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 4px; height: 4px; background-color: white; border-radius: 50%; box-shadow: -8px 0 0 white, 8px 0 0 white;"></span>
          </span>
        </button>
        <div class="step-menu" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ccc; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); min-width: 120px; margin-top: 4px; z-index: 1000;">
          <button type="button" class="remove-${type}" style="width: 100%; padding: 8px 12px; text-align: left; background: none; border: none; color: #856E5A; cursor: pointer;">削除</button>
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