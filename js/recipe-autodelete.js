document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form.recipe-form');
    const draftId = document.querySelector('input[name="draft_post_id"]')?.value;
    let hasSubmitted = false;
  
    if (!draftId) return;
  
    if (form) {
      form.addEventListener('submit', () => {
        hasSubmitted = true;
      });
    }
  
    window.addEventListener('beforeunload', () => {
      if (!hasSubmitted) {
        navigator.sendBeacon(
          recipeAutoDelete.ajax_url,
          new URLSearchParams({
            action: 'auto_delete_draft',
            draft_post_id: draftId
          })
        );
      }
    });
  });