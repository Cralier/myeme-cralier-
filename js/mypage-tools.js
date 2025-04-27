document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('material-tool-search-input');
  const dropdown = document.createElement('div');
  dropdown.className = 'autocomplete-dropdown';
  document.body.appendChild(dropdown);

  let currentTab = 'all';
  let savedItems = [];
  let materials = [];
  let tools = [];
  let allItemsById = {};
  let savedIds = [];
  let tabData = { all: [], tools: [], materials: [] };

  // ▼ タブ切り替えUIのイベント
  document.querySelectorAll('.tool-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.tool-tab-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      currentTab = this.dataset.tab;
      renderTabList();
    });
  });

  // ▼ タブごとのリスト描画
  function renderTabList() {
    const allWrapper = document.getElementById('materials-wrapper');
    const toolsWrapper = document.getElementById('tools-wrapper');
    const materialsWrapper = document.getElementById('materials-only-wrapper');
    allWrapper.style.display = 'none';
    toolsWrapper.style.display = 'none';
    materialsWrapper.style.display = 'none';
    allWrapper.innerHTML = '';
    toolsWrapper.innerHTML = '';
    materialsWrapper.innerHTML = '';
    if (currentTab === 'all') {
      allWrapper.style.display = '';
      tabData.all.forEach(item => addItemToDOM(item, allWrapper));
    } else if (currentTab === 'tools') {
      toolsWrapper.style.display = '';
      tabData.tools.forEach(item => addItemToDOM(item, toolsWrapper));
    } else if (currentTab === 'materials') {
      materialsWrapper.style.display = '';
      tabData.materials.forEach(item => addItemToDOM(item, materialsWrapper));
    }
  }

  // ▼ JSONデータを事前ロード
  async function loadData() {
    try {
      const matRes = await fetch('/wp-content/uploads/autocomplete/materials.json');
      const toolRes = await fetch('http://cralier.local/wp-content/uploads/autocomplete/tools.json');
      console.log('fetch: /wp-content/uploads/autocomplete/materials.json');
      console.log('fetch: /wp-content/uploads/autocomplete/tools.json');
      console.log('toolRes.url:', toolRes.url);
      const matText = await matRes.text();
      const toolText = await toolRes.text();
      console.log('tools.jsonの生テキスト:', toolText);
      materials = JSON.parse(matText);
      tools = JSON.parse(toolText);
      console.log('materials.jsonから:', materials);
      console.log('tools.jsonから:', tools);
      allItemsById = {};
      [...materials, ...tools].forEach(item => {
        if (item.id) allItemsById[item.id] = item;
      });
    } catch (err) {
      console.error('JSON取得失敗:', err);
    }
  }
  // 先にデータロード
  loadData().then(() => {
    console.log('tools:', tools);
    console.log('allItemsById:', allItemsById);
    // ▼ ページロード時に保存済みIDリストを取得して表示
    fetch(MypageToolsAjax.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'get_user_tool_data',
        _ajax_nonce: MypageToolsAjax.nonce
      })
    })
      .then(res => res.json())
      .then(data => {
        tabData = {
          all: Array.isArray(data.data?.all) ? data.data.all : [],
          tools: Array.isArray(data.data?.tools) ? data.data.tools : [],
          materials: Array.isArray(data.data?.materials) ? data.data.materials : []
        };
        savedIds = tabData.all.map(i => i.id);
        savedItems = tabData.all;
        console.log('保存済みID:', savedIds);
        renderTabList();
      });
  });

  // ▼ 入力監視
  searchInput?.addEventListener('input', () => {
    const query = searchInput.value.trim();
    if (!query) {
      hideDropdown();
      return;
    }

    // tools配列のnameが空やスペースで始まっていないか確認
    tools.forEach(item => {
      if (!item.name || /^\s*$/.test(item.name)) {
        console.log('toolsのnameが空またはスペース:', item);
      }
    });

    const combined = [
      ...materials.map(m => ({ ...m, type: 'material' })),
      ...tools.map(t => ({ ...t, type: 'tool' }))
    ];

    const matched = combined.filter(item =>
      item.name && item.name.toLowerCase().includes(query.toLowerCase())
    ).slice(0, 6);

    renderDropdown(matched);
  });

  // ▼ 候補を表示
  function renderDropdown(items) {
    console.log('ドロップダウン候補:', items);
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
      div.dataset.id = item.id || '';
      div.dataset.type = item.type;
      div.dataset.url = item.URL || item.url || '';
      div.dataset.image = item.Image || item.image || '';
      dropdown.appendChild(div);
    });
  }

  // ▼ ドロップダウン非表示
  function hideDropdown() {
    dropdown.style.display = 'none';
  }

  // ▼ 候補クリックで追加＆保存（idのみ送信）
  dropdown.addEventListener('click', function(e) {
    if (e.target.classList.contains('autocomplete-option')) {
      e.stopPropagation();
      let id = e.target.dataset.id || '';
      id = id.toString();
      console.log('追加しようとしているID:', id);
      if (!id || !allItemsById[id]) {
        alert('IDが不正です。');
        return;
      }
      if (savedIds.includes(id)) {
        alert('すでに追加済です。');
        searchInput.value = '';
        hideDropdown();
        return;
      }
      // 無限追加: 既存ID配列＋新IDで保存
      const idsToSave = [...savedIds, id];
      fetch(MypageToolsAjax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'save_user_tool_ids',
          ids: JSON.stringify(idsToSave),
          _ajax_nonce: MypageToolsAjax.nonce
        })
      })
      .then(res => {
        if (!res.ok) throw new Error('サーバーエラー: ' + res.status);
        return res.json();
      })
      .then(data => {
        // 保存後に再取得
        fetch(MypageToolsAjax.ajax_url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'get_user_tool_data',
            _ajax_nonce: MypageToolsAjax.nonce
          })
        })
        .then(res => res.json())
        .then(data => {
          tabData = {
            all: Array.isArray(data.data?.all) ? data.data.all : [],
            tools: Array.isArray(data.data?.tools) ? data.data.tools : [],
            materials: Array.isArray(data.data?.materials) ? data.data.materials : []
          };
          savedIds = tabData.all.map(i => i.id);
          savedItems = tabData.all;
          console.log('保存済みID:', savedIds);
          renderTabList();
        });
      })
      .catch(err => {
        alert('通信エラー: ' + err.message);
        console.error('fetchエラー', err);
      });
      searchInput.value = '';
      hideDropdown();
    }
  });

  // ▼ DOMに追加する関数（重複判定はidで）
  function addItemToDOM(item, wrapper) {
    if (!wrapper) return;
    const exists = Array.from(wrapper.querySelectorAll('.tool-material-row-horizontal')).some(div => {
      return div.dataset.id === item.id;
    });
    if (exists) {
      console.log('すでにDOMに存在');
      return;
    }
    const div = document.createElement('div');
    div.className = 'tool-material-row-horizontal';
    div.dataset.id = item.id || '';
    div.innerHTML = `
      <div class="tmr-inner">
        <div class="tmr-handle">≡</div>
        <div class="tmr-info">
          <div class="tmr-name">${item.name}</div>
          ${item.url ? `<div class="tmr-url"><a href="${item.url}" target="_blank">${item.url}</a></div>` : ''}
        </div>
        <div class="tmr-img">
          <img src="${item.image || '/wp-content/themes/mytheme/images/placeholder.jpg'}" alt="${item.name}">
        </div>
      </div>
    `;
    wrapper.appendChild(div);
  }

  // ▼ 外クリックでドロップダウンを閉じる
  document.addEventListener('click', (e) => {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
      hideDropdown();
    }
  });
}); 