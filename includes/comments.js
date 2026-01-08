// Comments System JavaScript for Garden of Words

let manuscriptId, currentUserId, currentUsername, manuscriptAuthorId;
let comments = [];

function initCommentsSystem(mId, uId, uName, authorId) {
    manuscriptId = mId;
    currentUserId = uId;
    currentUsername = uName;
    manuscriptAuthorId = authorId;
    
    setupEventListeners();
    loadComments();
}

// Setup Event Listeners
function setupEventListeners() {
    const newCommentText = document.getElementById('newCommentText');
    const postCommentBtn = document.getElementById('postCommentBtn');
    const charCount = document.getElementById('charCount');
    
    // Only set up listeners if elements exist (they won't for manuscript authors)
    if (!newCommentText || !postCommentBtn) {
        return;
    }
    
    // Character counter
            newCommentText.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            charCount.classList.remove('warning', 'danger');
            if (length > 900) {
                charCount.classList.add('danger');
            } else if (length > 800) {
                charCount.classList.add('warning');
            }
        });
    }
    
    // Post comment button
    postCommentBtn.addEventListener('click', postComment);


// Load Comments
async function loadComments() {
    const commentsList = document.getElementById('commentsList');
    
    try {
        const response = await fetch(`api/comments.php?action=get&manuscript_id=${manuscriptId}`);
        const data = await response.json();
        
        if (data.success) {
            comments = data.comments;
            updateCommentCount(comments.length);
            renderComments();
        } else {
            showError('Failed to load comments');
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        commentsList.innerHTML = '<div class="no-comments">Failed to load comments. Please refresh the page.</div>';
    }
}

// Render Comments
function renderComments() {
    const commentsList = document.getElementById('commentsList');
    
    if (comments.length === 0) {
        commentsList.innerHTML = `
            <div class="no-comments">
                <img src="assets/comments.png" alt="No comments">
                <p>No comments yet. Be the first to share your thoughts!</p>
            </div>
        `;
        return;
    }
    
    // Organize comments into parent-child structure
    const topLevelComments = comments.filter(c => !c.parent_comment_id);
    
    commentsList.innerHTML = '';
    topLevelComments.forEach(comment => {
        const commentElement = createCommentElement(comment);
        commentsList.appendChild(commentElement);
        
        // Add replies
        const replies = comments.filter(c => c.parent_comment_id == comment.id);
        if (replies.length > 0) {
            const repliesContainer = commentElement.querySelector('.replies-container');
            replies.forEach(reply => {
                repliesContainer.appendChild(createCommentElement(reply));
            });
        }
    });
}

// Create Comment Element
function createCommentElement(comment) {
    const template = document.getElementById('commentTemplate');
    const clone = template.content.cloneNode(true);
    const commentDiv = clone.querySelector('.comment-item');
    
    // Set comment data
    commentDiv.dataset.commentId = comment.id;
    commentDiv.querySelector('.comment-author').textContent = comment.username;
    commentDiv.querySelector('.comment-text').textContent = comment.comment_text;
    commentDiv.querySelector('.comment-date').textContent = formatDate(comment.created_at);
    commentDiv.querySelector('.like-count').textContent = comment.like_count;
    commentDiv.querySelector('.dislike-count').textContent = comment.dislike_count;
    
    // Mark if comment is by manuscript author
    if (comment.user_id == manuscriptAuthorId) {
        commentDiv.classList.add('is-author');
    }
    
    // Mark if edited
    if (comment.is_edited == 1) {
        commentDiv.classList.add('is-edited');
    }
    
    // Mark if user's own comment
    if (comment.user_id == currentUserId) {
        commentDiv.classList.add('is-own');
    }
    
    // Set user reaction
    if (comment.user_reaction) {
        const btn = commentDiv.querySelector(`.${comment.user_reaction}-btn`);
        if (btn) btn.classList.add('active');
    }
    
    // Setup action buttons
    setupCommentActions(commentDiv, comment);
    
    return commentDiv;
}

// Setup Comment Action Buttons
function setupCommentActions(commentDiv, comment) {
    const commentId = comment.id;
    
    // Like button
    const likeBtn = commentDiv.querySelector('.like-btn');
    likeBtn.addEventListener('click', () => reactToComment(commentId, 'like'));
    
    // Dislike button
    const dislikeBtn = commentDiv.querySelector('.dislike-btn');
    dislikeBtn.addEventListener('click', () => reactToComment(commentId, 'dislike'));
    
    // Reply button (authors CAN reply to comments on their own work)
    const replyBtn = commentDiv.querySelector('.reply-btn');
    const replyForm = commentDiv.querySelector('.reply-form');
    const replyTextarea = commentDiv.querySelector('.reply-textarea');
    const postReplyBtn = commentDiv.querySelector('.btn-post-reply');
    const cancelReplyBtn = commentDiv.querySelector('.btn-cancel-reply');
    
    replyBtn.addEventListener('click', () => {
        replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        if (replyForm.style.display === 'block') {
            replyTextarea.focus();
        }
    });
    
    postReplyBtn.addEventListener('click', () => postReply(commentId, replyTextarea));
    cancelReplyBtn.addEventListener('click', () => {
        replyForm.style.display = 'none';
        replyTextarea.value = '';
    });
    
    // Edit button
    if (comment.user_id == currentUserId) {
        const editBtn = commentDiv.querySelector('.edit-btn');
        const editForm = commentDiv.querySelector('.edit-comment-form');
        const editTextarea = commentDiv.querySelector('.edit-textarea');
        const saveEditBtn = commentDiv.querySelector('.btn-save-edit');
        const cancelEditBtn = commentDiv.querySelector('.btn-cancel-edit');
        const commentText = commentDiv.querySelector('.comment-text');
        
        editBtn.addEventListener('click', () => {
            editForm.style.display = 'block';
            editTextarea.value = comment.comment_text;
            commentText.style.display = 'none';
            editTextarea.focus();
        });
        
        saveEditBtn.addEventListener('click', () => editComment(commentId, editTextarea.value, commentDiv));
        cancelEditBtn.addEventListener('click', () => {
            editForm.style.display = 'none';
            commentText.style.display = 'block';
        });
    }
    
    // Delete button
    if (comment.user_id == currentUserId) {
        const deleteBtn = commentDiv.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', () => deleteComment(commentId));
    }
}

// Post New Comment
async function postComment() {
    const textarea = document.getElementById('newCommentText');
    const commentText = textarea.value.trim();
    const btn = document.getElementById('postCommentBtn');
    
    if (!commentText) {
        showError('Please write a comment');
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Posting...';
    
    const formData = new FormData();
    formData.append('action', 'post');
    formData.append('manuscript_id', manuscriptId);
    formData.append('comment_text', commentText);
    
    try {
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            textarea.value = '';
            document.getElementById('charCount').textContent = '0';
            await loadComments();
            showSuccess('Comment posted!');
        } else {
            showError(data.message || 'Failed to post comment');
        }
    } catch (error) {
        console.error('Error posting comment:', error);
        showError('Failed to post comment');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<img src="assets/comments.png" alt="Post" class="icon"> Post Comment';
    }
}

// Post Reply
async function postReply(parentCommentId, textarea) {
    const commentText = textarea.value.trim();
    
    if (!commentText) {
        showError('Please write a reply');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'post');
    formData.append('manuscript_id', manuscriptId);
    formData.append('comment_text', commentText);
    formData.append('parent_comment_id', parentCommentId);
    
    try {
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            textarea.value = '';
            textarea.closest('.reply-form').style.display = 'none';
            await loadComments();
            showSuccess('Reply posted!');
        } else {
            showError(data.message || 'Failed to post reply');
        }
    } catch (error) {
        console.error('Error posting reply:', error);
        showError('Failed to post reply');
    }
}

// Edit Comment
async function editComment(commentId, newText, commentDiv) {
    if (!newText.trim()) {
        showError('Comment cannot be empty');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('comment_id', commentId);
    formData.append('comment_text', newText);
    
    try {
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const commentText = commentDiv.querySelector('.comment-text');
            const editForm = commentDiv.querySelector('.edit-comment-form');
            
            commentText.textContent = newText;
            commentText.style.display = 'block';
            editForm.style.display = 'none';
            commentDiv.classList.add('is-edited');
            
            // Update in comments array
            const comment = comments.find(c => c.id == commentId);
            if (comment) {
                comment.comment_text = newText;
                comment.is_edited = 1;
            }
            
            showSuccess('Comment updated!');
        } else {
            showError(data.message || 'Failed to update comment');
        }
    } catch (error) {
        console.error('Error editing comment:', error);
        showError('Failed to update comment');
    }
}

// Delete Comment
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('comment_id', commentId);
    
    try {
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadComments();
            showSuccess('Comment deleted');
        } else {
            showError(data.message || 'Failed to delete comment');
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        showError('Failed to delete comment');
    }
}

// React to Comment
async function reactToComment(commentId, reaction) {
    const formData = new FormData();
    formData.append('action', 'react');
    formData.append('comment_id', commentId);
    formData.append('reaction', reaction);
    
    try {
        const response = await fetch('api/comments.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            const commentDiv = document.querySelector(`[data-comment-id="${commentId}"]`);
            if (commentDiv) {
                commentDiv.querySelector('.like-count').textContent = data.like_count;
                commentDiv.querySelector('.dislike-count').textContent = data.dislike_count;
                
                // Update active states
                commentDiv.querySelector('.like-btn').classList.toggle('active', data.user_reaction === 'like');
                commentDiv.querySelector('.dislike-btn').classList.toggle('active', data.user_reaction === 'dislike');
            }
            
            // Update in comments array
            const comment = comments.find(c => c.id == commentId);
            if (comment) {
                comment.like_count = data.like_count;
                comment.dislike_count = data.dislike_count;
                comment.user_reaction = data.user_reaction;
            }
        } else {
            showError(data.message || 'Failed to react to comment');
        }
    } catch (error) {
        console.error('Error reacting to comment:', error);
        showError('Failed to react to comment');
    }
}

// Update Comment Count
function updateCommentCount(count) {
    const countElement = document.getElementById('commentCount');
    if (countElement) {
        countElement.textContent = count;
    }
}

// Format Date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined 
    });
}

// Show Success Message
function showSuccess(message) {
    // You can implement a toast notification here
    // For now, just console log
    console.log('Success:', message);
    
    // Simple alert version (replace with better UI later)
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #66bb6a, #43a047);
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 20px rgba(67, 160, 71, 0.4);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Show Error Message
function showError(message) {
    console.error('Error:', message);
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #f44336, #d32f2f);
        color: white;
        padding: 15px 25px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 20px rgba(244, 67, 54, 0.4);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS animations for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);