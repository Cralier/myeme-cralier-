document.addEventListener('DOMContentLoaded', () => {
    const togglePreviewBtn = document.getElementById('toggle-preview');
    const backBtn = document.getElementById('back-to-form');
    const form = document.querySelector('.recipe-form');
    const previewSection = document.getElementById('preview-section');
  
    function updatePreviewContent() {
      const title = document.querySelector('input[name="recipe_title"]').value;
      const ingredients = document.querySelectorAll('input[name="ingredient_name[]"]');
      const steps = document.querySelectorAll('textarea[name="steps_text[]"]');
      const time = document.querySelector('input[name="cooking_time"]').value;
  
      document.getElementById('preview-time').textContent = time;
      document.getElementById('preview-title').textContent = title;
      document.getElementById('preview-description').innerText =
      document.getElementById('recipe_description').value;

      const ingList = document.getElementById('preview-ingredients');
      ingList.innerHTML = '';
      ingredients.forEach(input => {
        if (input.value.trim()) {
          const li = document.createElement('li');
          li.textContent = input.value;
          ingList.appendChild(li);
        }
      });
  
      const stepList = document.getElementById('preview-steps');
      stepList.innerHTML = '';
      steps.forEach(input => {
        if (input.value.trim()) {
          const li = document.createElement('li');
          li.textContent = input.value;
          stepList.appendChild(li);
        }
      });
    }
  
    togglePreviewBtn?.addEventListener('click', () => {
      updatePreviewContent();
      form.style.display = 'none';
      previewSection.style.display = 'block';
    });
  
    backBtn?.addEventListener('click', () => {
      form.style.display = 'block';
      previewSection.style.display = 'none';
    });
  });