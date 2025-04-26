jQuery(document).ready(function($) {
    const recipeId = $('#recipe-comment-form').data('recipe-id');
    const commentsList = $('.comments-list');
    const commentForm = $('#recipe-comment-form');
    const currentUserId = recipeCommentsData.current_user_id; // WordPressから現在のユーザーIDを取得

    // コメントリストのスタイルを設定
    commentsList.css({
        'display': 'flex',
        'flex-direction': 'column'
    });

    // コメント一覧を読み込む
    function loadComments() {
        $.ajax({
            url: `${recipeCommentsData.rest_url}comments/${recipeId}`,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', recipeCommentsData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    renderComments(response.comments);
                }
            },
            error: function(xhr, status, error) {
                console.error('コメントの読み込みに失敗しました:', error);
            }
        });
    }

    // コメントをレンダリング
    function renderComments(comments) {
        const mainComments = comments.filter(c => !c.parent_id)
            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        
        // 現在表示中の返信セクションのIDを保存
        const visibleReplies = new Set();
        $('.comment-replies:visible').each(function() {
            const commentId = $(this).closest('.comment-thread').find('.comment').first().data('comment-id');
            visibleReplies.add(commentId);
        });
        
        commentsList.empty();
        mainComments.forEach(comment => {
            const commentHtml = createCommentHtml(comment);
            const commentElement = $('<div>').addClass('comment-thread');
            commentElement.append(commentHtml);

            const allReplies = getAllReplies(comments, comment.id);
            // 以前表示されていた場合は表示状態を維持
            const repliesContainer = $('<div>').addClass('comment-replies');
            if (!visibleReplies.has(comment.id)) {
                repliesContainer.hide();
            }
            
            if (allReplies.length > 0) {
                const toggleButton = $(`
                    <button class="toggle-replies">
                        <span class="toggle-text">${visibleReplies.has(comment.id) ? '返信を非表示' : '返信を表示'}</span>
                        <span class="reply-count">(${allReplies.length})</span>
                    </button>
                `);
                commentElement.append(toggleButton);

                // 返信コメントを新しい順に追加
                const repliesWrapper = $('<div>').addClass('replies-wrapper');
                allReplies.forEach(reply => {
                    const replyHtml = createCommentHtml(reply);
                    repliesWrapper.prepend(replyHtml);
                });
                repliesContainer.append(repliesWrapper);
            }

            // 返信フォームを最後に追加
            const replyFormHtml = `
                <div class="reply-form-wrapper">
                    <form class="reply-form" data-parent-id="${comment.id}">
                        <textarea placeholder="返信を入力" required></textarea>
                        <div class="reply-actions">
                            <button type="submit" class="submit-reply">返信する</button>
                        </div>
                    </form>
                </div>
            `;
            repliesContainer.append(replyFormHtml);
            
            commentElement.append(repliesContainer);
            commentsList.prepend(commentElement);
        });
    }

    // 特定のコメントに対する全ての返信を取得する関数（返信の返信も含む）
    function getAllReplies(comments, parentId) {
        const directReplies = comments.filter(c => c.parent_id === parentId)
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at)); // 返信は新しい順

        let allReplies = [...directReplies];
        directReplies.forEach(reply => {
            const nestedReplies = getAllReplies(comments, reply.id);
            allReplies = allReplies.concat(nestedReplies);
        });

        return allReplies;
    }

    // コメントのHTMLを生成
    function createCommentHtml(comment) {
        const date = new Date(comment.created_at + '+09:00');
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        let timeAgo;
        if (diffInSeconds < 60) {
            timeAgo = `${diffInSeconds}秒前`;
        } else if (diffInSeconds < 3600) {
            timeAgo = `${Math.floor(diffInSeconds / 60)}分前`;
        } else if (diffInSeconds < 86400) {
            timeAgo = `${Math.floor(diffInSeconds / 3600)}時間前`;
        } else if (diffInSeconds < 2592000) {
            timeAgo = `${Math.floor(diffInSeconds / 86400)}日前`;
        } else if (diffInSeconds < 31536000) {
            timeAgo = `${Math.floor(diffInSeconds / 2592000)}ヶ月前`;
        } else {
            timeAgo = `${Math.floor(diffInSeconds / 31536000)}年前`;
        }

        // コメントテキストの改行を<br>タグに変換し、エスケープ処理を行う
        const escapedText = comment.comment_text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        // 改行の数をカウント
        const lineCount = (escapedText.match(/\n/g) || []).length + 1;
        
        // テキストの長さと行数をチェック
        const isLongText = escapedText.length > 75 || lineCount > 5;
        
        // 表示用のテキストを準備
        let displayText, fullText;
        if (isLongText) {
            // 改行を<br>に変換
            fullText = escapedText.replace(/\n/g, '<br>');
            
            // 省略表示用のテキスト（最初の5行まで）
            let shortText = escapedText;
            if (lineCount > 4) {
                shortText = escapedText.split('\n').slice(0, 4).join('\n');
            } else {
                shortText = escapedText.substring(0, 75);
            }
            displayText = shortText.replace(/\n/g, '<br>') + '...';
        } else {
            displayText = escapedText.replace(/\n/g, '<br>');
        }

        // 自分のコメントの場合のみ削除メニューを表示
        const deleteMenu = comment.user_id === parseInt(currentUserId) ? `
            <div class="comment-menu">
                <button class="comment-menu-toggle"><span>⋮</span></button>
                <div class="comment-menu-dropdown" style="display: none;">
                    <button class="delete-comment" data-comment-id="${comment.id}">コメントを削除</button>
                </div>
            </div>
        ` : '';

        // 返信ボタンを表示（親コメントまたは最下層の返信の場合）
        const replyButton = !comment.parent_id || comment.is_deepest_reply ? `
            <div class="comment-actions">
                <button class="reply-button" data-comment-id="${comment.id}">返信</button>
            </div>
        ` : '';

        // コメント本文のHTML
        const commentContentHtml = isLongText ? `
            <div class="comment-content">
                <p class="short-text">${displayText}</p>
                <p class="full-text" style="display: none;">${fullText}</p>
                <button class="read-more">続きを読む</button>
            </div>
        ` : `
            <div class="comment-content">
                <p>${displayText}</p>
            </div>
        `;

        return `
            <div class="comment" data-comment-id="${comment.id}" data-created-at="${comment.created_at}">
                <div class="comment-header">
                    <div class="user-info">
                        <img src="${comment.user_avatar}" alt="${comment.user_name}" class="user-avatar">
                        <span class="user-name">${comment.user_name}</span>
                    </div>
                    <div class="comment-header-right">
                        <span class="comment-date">${timeAgo}</span>
                        ${deleteMenu}
                    </div>
                </div>
                ${commentContentHtml}
                ${replyButton}
            </div>
        `;
    }

    // コメントを投稿
    commentForm.on('submit', function(e) {
        e.preventDefault();
        const commentText = $(this).find('textarea').val();
        if (!commentText.trim()) return;

        const submitButton = $(this).find('.submit-comment');
        submitButton.prop('disabled', true);

        submitComment(commentText, 0);
    });

    // コメントをサーバーに送信
    function submitComment(text, parentId = 0) {
        $.ajax({
            url: `${recipeCommentsData.rest_url}comments`,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', recipeCommentsData.nonce);
                xhr.setRequestHeader('Content-Type', 'application/json');
            },
            data: JSON.stringify({
                recipe_id: recipeId,
                comment_text: text,
                parent_id: parentId
            }),
            success: function(response) {
                if (response.success) {
                    // コメント投稿成功後、最新のコメント一覧を再読み込み
                    loadComments();
                    
                    // フォームをリセット
                    commentForm.find('textarea').val('');
                }
            },
            error: function(xhr, status, error) {
                console.error('コメントの投稿に失敗しました:', error);
                alert('コメントの投稿に失敗しました。もう一度お試しください。');
            },
            complete: function() {
                commentForm.find('.submit-comment').prop('disabled', false);
            }
        });
    }

    // 返信フォームを表示（この関数は不要になるため削除）
    commentsList.off('click', '.reply-button');

    // 返信ボタンクリック時の処理を変更
    commentsList.on('click', '.reply-button', function() {
        const $comment = $(this).closest('.comment');
        const $commentThread = $comment.closest('.comment-thread');
        const $repliesContainer = $commentThread.find('.comment-replies');
        
        // 返信コンテナを表示
        $repliesContainer.show();
        
        // トグルボタンのテキストを更新
        const $toggleButton = $commentThread.find('.toggle-replies');
        if ($toggleButton.length) {
            $toggleButton.find('.toggle-text').text('返信を非表示');
        }
        
        // 返信フォームのテキストエリアにフォーカス
        $repliesContainer.find('textarea').focus();
    });

    // キャンセルボタンの処理を削除（キャンセルボタンがなくなるため）
    commentsList.off('click', '.cancel-reply');

    // 返信を送信
    commentsList.on('submit', '.reply-form', function(e) {
        e.preventDefault();
        const text = $(this).find('textarea').val();
        const parentId = $(this).data('parent-id');
        
        if (text.trim()) {
            submitComment(text, parentId);
            // フォームをリセット
            $(this).find('textarea').val('');
            // フォームにフォーカスを戻す
            $(this).find('textarea').focus();
        }
    });

    // メニュートグルの処理
    commentsList.on('click', '.comment-menu-toggle', function(e) {
        e.stopPropagation();
        const dropdown = $(this).siblings('.comment-menu-dropdown');
        $('.comment-menu-dropdown').not(dropdown).hide();
        dropdown.toggle();
    });

    // ドロップダウンメニュー以外をクリックした時に閉じる
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.comment-menu').length) {
            $('.comment-menu-dropdown').hide();
        }
    });

    // コメント削除の処理
    commentsList.on('click', '.delete-comment', function() {
        if (!confirm('このコメントを削除してもよろしいですか？')) {
            return;
        }

        const commentId = $(this).data('comment-id');
        
        $.ajax({
            url: `${recipeCommentsData.rest_url}comments/${commentId}`,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', recipeCommentsData.nonce);
            },
            success: function(response) {
                if (response.success) {
                    loadComments(); // コメント一覧を再読み込み
                }
            },
            error: function(xhr, status, error) {
                console.error('コメントの削除に失敗しました:', error);
                alert('コメントの削除に失敗しました。もう一度お試しください。');
            }
        });
    });

    // 返信の表示/非表示を切り替え
    commentsList.on('click', '.toggle-replies', function() {
        const $button = $(this);
        const $replies = $button.siblings('.comment-replies');
        const isVisible = $replies.is(':visible');
        
        $replies.slideToggle(200);
        $button.find('.toggle-text').text(isVisible ? '返信を表示' : '返信を非表示');
    });

    // 続きを読むボタンのクリックイベント
    commentsList.on('click', '.read-more', function() {
        const $content = $(this).closest('.comment-content');
        const $shortText = $content.find('.short-text');
        const $fullText = $content.find('.full-text');
        const $button = $(this);

        if ($shortText.is(':visible')) {
            $shortText.hide();
            $fullText.show();
            $button.text('閉じる');
        } else {
            $shortText.show();
            $fullText.hide();
            $button.text('続きを読む');
        }
    });

    // 初期読み込み
    loadComments();
}); 