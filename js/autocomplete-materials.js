document.addEventListener('DOMContentLoaded', () => {
    const nameInputs = document.querySelectorAll('.ingredient-name'); // ←変更！
  
    nameInputs.forEach(input => {
      let dropdown;
  
      input.addEventListener('input', async () => {
        const query = input.value.trim();
        if (!query) {
          closeDropdown();
          return;
        }
  
        try {
          const res = await fetch('/wp-content/uploads/autocomplete/materials.json');
          if (!res.ok) throw new Error('JSON取得失敗');
          const data = await res.json();
  
          const matched = data.filter(item => item.name.includes(query));
          showDropdown(matched, input);
        } catch (err) {
          console.error('補完エラー:', err);
        }
      });
  
      input.addEventListener('blur', () => {
        setTimeout(() => closeDropdown(), 200);
      });
  
      function showDropdown(items, inputEl) {
        closeDropdown();
        if (!items.length) return;
  
        dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
  
        items.forEach(item => {
          const option = document.createElement('div');
          option.className = 'autocomplete-option';
          option.textContent = item.name;
          option.addEventListener('click', () => {
            inputEl.value = item.name;
  
            const row = inputEl.closest('.ingredient-item'); // ←ここもクラス名に合わせる
            if (row) {
              const urlInput = row.querySelector('.ingredient-url');
              if (urlInput && item.URL) {
                urlInput.value = item.URL;
              }
            }
  
            closeDropdown();
          });
  
          dropdown.appendChild(option);
        });
  
        const wrapper = inputEl.closest('.ingredient-item');
        if (wrapper) {
          wrapper.appendChild(dropdown);
        }
      }
  
      function closeDropdown() {
        if (dropdown) {
          dropdown.remove();
          dropdown = null;
        }
      }
    });
  });