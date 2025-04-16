document.addEventListener('DOMContentLoaded', () => {

    // ▼ 並び替えを初期化（材料・作り方）
    function initSortable() {
        if ($) {
          $('#materials-wrapper').sortable({
            handle: '.handle',
            placeholder: 'sortable-placeholder',
            tolerance: 'pointer'
          });

          $('#tools-wrapper').sortable({
            handle: '.handle',
            placeholder: 'sortable-placeholder',
            tolerance: 'pointer'
          });
          
          $('#steps-container').sortable({
            handle: '.handle',
            items: '> .step-item',
            placeholder: 'sortable-placeholder',
            cancel: 'input,textarea,button,label',
            tolerance: 'pointer',
            update: function () {
              updateStepLabels(); // 並び替え後にラベル更新
            }
          });
        }
      }
  

// ▼ 材料セクション（シンプル版）
const wrapper = document.getElementById('materials-wrapper');
const addBtn = document.getElementById('add-material');

function creatematerialItem() {
  const item = document.createElement('div');
  item.className = 'material-item';

  const handle = document.createElement('div');
  handle.className = 'handle';
  handle.textContent = '≡';

  const nameInput = document.createElement('input');
  nameInput.type = 'text';
  nameInput.name = 'material_name[]';
  nameInput.className = 'material-name';
  nameInput.placeholder = '材料名を入力';

  const urlInput = document.createElement('input');
  urlInput.type = 'url';
  urlInput.name = 'material_url[]';
  urlInput.className = 'material-url';
  urlInput.placeholder = '材料のURL（任意）';

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'remove-material';
  deleteBtn.textContent = '削除';
  deleteBtn.addEventListener('click', () => {
    if (document.querySelectorAll('.material-item').length > 1) {
      item.remove();
    }
  });

  item.appendChild(handle);
  item.appendChild(nameInput);
  item.appendChild(urlInput);
  item.appendChild(deleteBtn);

  return item;
}

if (wrapper && addBtn) {
  // 初期化
  wrapper.innerHTML = '';
  wrapper.appendChild(creatematerialItem());

  addBtn.addEventListener('click', () => {
    wrapper.appendChild(creatematerialItem());
    initSortable();
  });
}

// ▼ 道具セクション（材料と同じ構造）
const toolsWrapper = document.getElementById('tools-wrapper');
const addToolBtn = document.getElementById('add-tool');
function createToolItem() {
  const item = document.createElement('div');
  item.className = 'tool-item';

  const handle = document.createElement('div');
  handle.className = 'handle';
  handle.textContent = '≡';

  const nameInput = document.createElement('input');
  nameInput.type = 'text';
  nameInput.name = 'tool_name[]';
  nameInput.className = 'tool-name';
  nameInput.placeholder = '道具名を入力';

  const urlInput = document.createElement('input');
  urlInput.type = 'url';
  urlInput.name = 'tool_url[]';
  urlInput.className = 'tool-url';
  urlInput.placeholder = '道具のURL（任意）';

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'remove-tool';
  deleteBtn.textContent = '削除';
  deleteBtn.addEventListener('click', () => {
    if (document.querySelectorAll('.tool-item').length > 1) {
      item.remove();
    }
  });

  item.appendChild(handle);
  item.appendChild(nameInput);
  item.appendChild(urlInput);
  item.appendChild(deleteBtn);

  return item;
}

// 初期化処理
if (toolsWrapper && addToolBtn) {
  toolsWrapper.innerHTML = '';
  toolsWrapper.appendChild(createToolItem());

  addToolBtn.addEventListener('click', () => {
    toolsWrapper.appendChild(createToolItem());
    initSortable();
  });
}
  
    // ▼ 作り方ステップ追加処理
    const stepContainer = document.getElementById('steps-container');
    const addStep = document.getElementById('add-step');
  
    if (addStep && stepContainer) {
      addStep.addEventListener('click', () => {
        const count = stepContainer.querySelectorAll('.step-item').length + 1;
      
        const step = document.createElement('div');
        step.className = 'step-item';
        step.innerHTML = `
          <div class="step-header">
            <span class="handle">≡</span>
            <span class="step-label">手順 ${count}</span>
            <div class="step-actions">
              <button type="button" class="step-menu-toggle">⋯</button>
              <div class="step-menu" style="display: none;">
                <button type="button" class="remove-step">作り方を削除</button>
              </div>
            </div>
          </div>
          <div class="step-content">
            <textarea name="steps_text[]" placeholder="作り方の説明を記入してください"></textarea>
            <div class="image-drop-area">
              <label class="image-upload-label">
                <input type="file" name="steps_image[]" class="step-image-input" accept="image/*">
                <div class="image-preview">
                  <img src="${window.uploadIconUrl || '/wp-content/themes/mytheme/images/upload-photo-icon.png'}" />
                </div>
              </label>
            </div>
          </div>
        `;
      
        stepContainer.appendChild(step);
        updateStepLabels();
        initSortable();

        // 新しく追加されたステップの画像プレビュー機能を初期化
        const newImageInput = step.querySelector('.step-image-input');
        if (newImageInput) {
          newImageInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function () {
              const preview = event.target.closest('.image-drop-area').querySelector('.image-preview');
              preview.innerHTML = `<img src="${reader.result}" style="max-width:100%; border-radius: 6px; object-fit: contain;">`;
            };
            reader.readAsDataURL(file);
          });
        }

        // 新しく追加されたステップの削除機能を初期化
        const removeStepBtn = step.querySelector('.remove-step');
        if (removeStepBtn) {
          removeStepBtn.addEventListener('click', () => {
            step.remove();
            updateStepLabels();
          });
        }
      });

      // 既存のステップの削除機能を初期化
      document.querySelectorAll('.remove-step').forEach(btn => {
        btn.addEventListener('click', () => {
          const step = btn.closest('.step-item');
          if (step) {
            step.remove();
            updateStepLabels();
          }
        });
      });

      // ▼ 「⋯」クリックでメニュー開閉
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('step-menu-toggle')) {
    const menu = e.target.nextElementSibling;
    if (menu) menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    e.stopPropagation();
  } else {
    // メニュー以外クリックで全閉じ
    document.querySelectorAll('.step-menu').forEach(menu => menu.style.display = 'none');
  }
});
  
      function updateStepLabels() {
        const steps = stepContainer.querySelectorAll('.step-item');
        steps.forEach((step, index) => {
          const label = step.querySelector('.step-label');
          if (label) label.textContent = `手順 ${index + 1}`;
        });
      }
    }
    
  
    // ▼ セレクト以外クリックで全ドロップダウン閉じる
    document.addEventListener('click', () => {
      document.querySelectorAll('.custom-select-dropdown').forEach(d => d.style.display = 'none');
    });
  });
  
