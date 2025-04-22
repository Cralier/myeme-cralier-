document.addEventListener('DOMContentLoaded', function() {
    // シェアモーダルの制御
    const shareButton = document.querySelector('.share-button');
    const shareModal = document.querySelector('.share-modal');
    const closeModal = document.querySelector('.close-modal');
    const shareButtons = document.querySelectorAll('.share-buttons button');
    const copyLinkButton = document.querySelector('.copy-link');

    if (shareButton && shareModal) {
        // シェアボタンクリック時の処理
        shareButton.addEventListener('click', () => {
            shareModal.style.display = 'flex';
        });

        // モーダルを閉じる処理
        closeModal.addEventListener('click', () => {
            shareModal.style.display = 'none';
        });

        // モーダル外クリックで閉じる
        shareModal.addEventListener('click', (e) => {
            if (e.target === shareModal) {
                shareModal.style.display = 'none';
            }
        });
    }

    // 各シェアボタンの処理
    shareButtons.forEach(button => {
        button.addEventListener('click', () => {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            let shareUrl = '';

            switch (button.className) {
                case 'share-twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'share-facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'share-line':
                    shareUrl = `https://social-plugins.line.me/lineit/share?url=${url}`;
                    break;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
                shareModal.style.display = 'none';
            }
        });
    });

    // リンクをコピーする機能
    if (copyLinkButton) {
        copyLinkButton.addEventListener('click', () => {
            navigator.clipboard.writeText(window.location.href).then(() => {
                const originalText = copyLinkButton.textContent;
                copyLinkButton.textContent = 'コピーしました！';
                setTimeout(() => {
                    copyLinkButton.textContent = originalText;
                }, 2000);
            });
        });
    }

    // 保存ボタンの制御
    const saveButton = document.querySelector('.save-button');
    if (saveButton) {
        saveButton.addEventListener('click', async () => {
            const recipeId = saveButton.dataset.recipeId;
            try {
                const response = await fetch('/wp-json/wp/v2/save-recipe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    body: JSON.stringify({
                        recipe_id: recipeId
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.saved) {
                        saveButton.classList.add('saved');
                        saveButton.querySelector('.action-text').textContent = '保存済み';
                        console.log('保存が成功しました。');
                    } else {
                        saveButton.classList.remove('saved');
                        saveButton.querySelector('.action-text').textContent = '保存する';
                        console.log('保存を解除しました。');
                    }
                } else {
                    const errorData = await response.json();
                    if (response.status === 401) {
                        // 未ログインの場合はログインページにリダイレクト
                        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.href);
                    } else {
                        console.error('保存に失敗しました:', errorData.message);
                    }
                }
            } catch (error) {
                console.error('Error saving recipe:', error);
            }
        });

        // 初期状態の確認
        const checkSavedState = async () => {
            const recipeId = saveButton.dataset.recipeId;
            try {
                const response = await fetch(`/wp-json/wp/v2/check-recipe-saved?recipe_id=${recipeId}`, {
                    headers: {
                        'X-WP-Nonce': wpApiSettings.nonce
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.saved) {
                        saveButton.classList.add('saved');
                        saveButton.querySelector('.action-text').textContent = '保存済み';
                    }
                }
            } catch (error) {
                console.error('Error checking saved state:', error);
            }
        };

        checkSavedState();
    }
}); 