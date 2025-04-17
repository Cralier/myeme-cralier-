document.addEventListener('DOMContentLoaded', () => {
    // DOM elements
    const elements = {
        materialsWrapper: document.getElementById('materials-wrapper'),
        toolsWrapper: document.getElementById('tools-wrapper'),
        materialHint: document.getElementById('material-hint'),
        toolHint: document.getElementById('tool-hint'),
        stepContainer: document.getElementById('steps-container'),
        addStepButton: document.getElementById('add-step'),
        searchInput: document.getElementById('material-tool-search-input')
    };

    // Initialize sortable functionality
    function initSortable() {
        if (typeof $ === 'undefined') return;

        $('#materials-wrapper, #tools-wrapper').sortable({
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
            update: updateStepLabels
        });
    }

    // Create item (material or tool)
    function createItem(type) {
        const item = document.createElement('div');
        const isToolItem = type === 'tool';
        
        item.className = `${type}-row recipe-sortable-item`;
        item.style.cssText = `
            background-color: #F5EBE1;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        `;

        const inputName = isToolItem ? 'tool_name[]' : 'material_name[]';
        const inputUrlName = isToolItem ? 'tool_url[]' : 'material_url[]';
        const placeholder = isToolItem ? '道具名を入力' : '材料名を入力';
        const removeButtonClass = isToolItem ? 'remove-tool' : 'remove-material';

        item.innerHTML = `
            <div class="handle ui-sortable-handle">☰</div>
            <div class="input-wrapper">
                <input type="text" name="${inputName}" class="${type}-name-input" placeholder="${placeholder}">
                <input type="url" name="${inputUrlName}" class="${type}-url-input" placeholder="URL（任意）">
            </div>
            <div class="step-actions">
                <button type="button" class="step-menu-toggle">⋯</button>
                <div class="step-menu" style="display: none;">
                    <button type="button" class="${removeButtonClass}">削除</button>
                </div>
            </div>
        `;

        return item;
    }

    // Add item (material or tool)
    function addItem(type, name = '', url = '') {
        const wrapper = type === 'tool' ? elements.toolsWrapper : elements.materialsWrapper;
        const hint = type === 'tool' ? elements.toolHint : elements.materialHint;

        if (hint) {
            hint.remove();
        }

        const item = createItem(type);
        if (name) {
            item.querySelector(`.${type}-name-input`).value = name;
        }
        if (url) {
            item.querySelector(`.${type}-url-input`).value = url;
        }

        wrapper.appendChild(item);
        updateSortable(wrapper);
    }

    // Add step
    function addStep() {
        const count = elements.stepContainer.querySelectorAll('.step-item').length + 1;
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

        elements.stepContainer.appendChild(step);
        updateStepLabels();
    }

    // Update step labels
    function updateStepLabels() {
        elements.stepContainer.querySelectorAll('.step-item').forEach((step, index) => {
            const label = step.querySelector('.step-label');
            if (label) label.textContent = `手順 ${index + 1}`;
        });
    }

    // Initialize sortable
    function updateSortable(wrapper) {
        if (typeof $ === 'undefined') return;

        $(wrapper).sortable({
            handle: '.handle',
            placeholder: 'sortable-placeholder',
            tolerance: 'pointer'
        });
    }

    // Event delegation for menu toggles and remove buttons
    function setupEventDelegation() {
        // Menu toggle delegation
        document.addEventListener('click', (e) => {
            const menuToggle = e.target.closest('.step-menu-toggle');
            if (menuToggle) {
                e.stopPropagation();
                const menu = menuToggle.nextElementSibling;
                const isVisible = menu.style.display === 'block';
                
                // Close all other menus
                document.querySelectorAll('.step-menu').forEach(m => {
                    if (m !== menu) m.style.display = 'none';
                });
                
                menu.style.display = isVisible ? 'none' : 'block';
                return;
            }

            // Close menus when clicking outside
            if (!e.target.closest('.step-menu')) {
                document.querySelectorAll('.step-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        // Remove button delegation
        document.addEventListener('click', (e) => {
            const removeButton = e.target.closest('.remove-material, .remove-tool, .remove-step');
            if (!removeButton) return;

            const item = removeButton.closest('.material-row, .tool-row, .step-item');
            if (!item) return;

            item.remove();

            if (item.classList.contains('step-item')) {
                updateStepLabels();
            } else {
                const wrapper = item.closest('#materials-wrapper, #tools-wrapper');
                const type = wrapper.id === 'materials-wrapper' ? 'material' : 'tool';
                
                if (wrapper.children.length === 0) {
                    const hint = document.createElement('p');
                    hint.className = 'auto-added-note';
                    hint.id = `${type}-hint`;
                    hint.textContent = '自動で追加されます';
                    wrapper.appendChild(hint);
                }
            }
        });

        // Image input change delegation
        document.addEventListener('change', (e) => {
            if (!e.target.matches('.step-image-input')) return;

            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function() {
                const preview = e.target.closest('.image-drop-area').querySelector('.image-preview');
                preview.innerHTML = `<img src="${reader.result}" style="max-width:100%; border-radius: 6px; object-fit: contain;">`;
            };
            reader.readAsDataURL(file);
        });
    }

    // Initialize
    function init() {
        // Initialize sortable
        initSortable();

        // Setup event delegation
        setupEventDelegation();

        // Add step button
        if (elements.addStepButton) {
            elements.addStepButton.addEventListener('click', addStep);
        }

        // Search input
        if (elements.searchInput) {
            elements.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const searchValue = e.target.value.trim();
                    if (searchValue) {
                        addItem('material', searchValue, '');
                        e.target.value = '';
                    }
                }
            });
        }
    }

    // Start initialization
    init();
});
  
