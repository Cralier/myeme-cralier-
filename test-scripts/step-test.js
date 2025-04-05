document.addEventListener('DOMContentLoaded', () => {
    const stepContainer = document.getElementById('steps-container');
    const addBtn = document.getElementById('add-step');
  
    // 並び替え有効化
    $(stepContainer).sortable({
      handle: '.handle',
      placeholder: 'step-placeholder',
      cancel: 'input,textarea,button,label',
      tolerance: 'pointer'
    });
  
    addBtn.addEventListener('click', () => {
      const count = stepContainer.querySelectorAll('.step-item').length + 1;
  
      const step = document.createElement('div');
      step.className = 'step-item';
      step.innerHTML = `
        <div class="step-header">
          <span class="handle">≡</span>
          <span class="step-label">手順 ${count}</span>
          <button type="button" class="remove-step">削除</button>
        </div>
        <textarea placeholder="手順の説明を入力"></textarea>
      `;
      stepContainer.appendChild(step);
      updateStepLabels();
    });
  
    stepContainer.addEventListener('click', e => {
      if (e.target.classList.contains('remove-step')) {
        e.target.closest('.step-item').remove();
        updateStepLabels();
      }
    });
  
    function updateStepLabels() {
      const steps = stepContainer.querySelectorAll('.step-item');
      steps.forEach((step, index) => {
        const label = step.querySelector('.step-label');
        if (label) label.textContent = `手順 ${index + 1}`;
      });
    }
  });