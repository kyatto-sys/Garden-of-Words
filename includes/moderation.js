// Moderation Dashboard JavaScript for Garden of Words

// Select All Comments
function selectAll() {
    const checkboxes = document.querySelectorAll('.comment-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
}

// Delete Selected Comments
async function deleteSelected() {
    const checkboxes = document.querySelectorAll('.comment-checkbox:checked');
    const commentIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (commentIds.length === 0) {
        showError('Please select at least one comment to delete');
        return;
    }
    
    const confirmMsg = `Are you sure you want to delete ${commentIds.length} comment${commentIds.length > 1 ? 's' : ''}? This action cannot be undone.`;
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    // Delete each comment
    let successCount = 0;
    let failCount = 0;
    
    for (const commentId of commentIds) {
        const success = await deleteCommentById(commentId, false);
        if (success) {
            successCount++;
            // Remove from UI
            const row = document.querySelector(`input[value="${commentId}"]`)?.closest('.comment-row');
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            failCount++;
        }
    }
    
    if (successCount > 0) {
        showSuccess(`Successfully deleted ${successCount} comment${successCount > 1 ? 's' : ''}`);
    }
    
    if (failCount > 0) {
        showError(`Failed to delete ${failCount} comment${failCount > 1 ? 's' : ''}`);
    }
    
    // Refresh page after a delay if all successful
    if (failCount === 0) {
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
}

// Delete Single Comment
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        return;
    }
    
    const success = await deleteCommentById(commentId, true);
    
    if (success) {
        showSuccess('Comment deleted successfully');
        
        // Remove from UI with animation
        const row = document.querySelector(`input[value="${commentId}"]`)?.closest('.comment-row');
        if (row) {
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                row.remove();
                // Check if no comments left
                if (document.querySelectorAll('.comment-row').length === 0) {
                    window.location.reload();
                }
            }, 300);
        }
    } else {
        showError('Failed to delete comment');
    }
}

// Delete Comment by ID (helper function)
async function deleteCommentById(commentId, showNotification = true) {
    const formData = new FormData();
    formData.append('action', 'delete_moderation');
    formData.append('comment_id', commentId);
    
    try {
        const response = await fetch('api/moderation.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            return true;
        } else {
            if (showNotification) {
                showError(data.message || 'Failed to delete comment');
            }
            return false;
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        if (showNotification) {
            showError('Failed to delete comment');
        }
        return false;
    }
}

// View Comment in Context
function viewComment(commentId) {
    // Get the manuscript ID from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const manuscriptId = urlParams.get('manuscript_id');
    
    if (manuscriptId) {
        window.open(`read.php?id=${manuscriptId}#comment-${commentId}`, '_blank');
    }
}

// Show Success Message
function showSuccess(message) {
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
        font-family: 'Quicksand', sans-serif;
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
        font-family: 'Quicksand', sans-serif;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add CSS animations
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
    
    .comment-row {
        transition: all 0.3s ease;
    }
`;
document.head.appendChild(style);