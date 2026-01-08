<?php
if (!isset($manuscript_id) || !isset($user_id)) {
    die('Error: manuscript_id and user_id must be set');
}
?>

<link rel="stylesheet" href="includes/comments.css">

<div class="comments-section" id="commentsSection" data-manuscript-id="<?php echo $manuscript_id; ?>">
    <div class="comments-header">
        <h2>
            <img src="assets/comments.png" alt="Comments" class="icon">
            Community Discussion
        </h2>
        <div class="comment-count">
            <span id="commentCount">0</span> comments
        </div>
    </div>

    <div class="new-comment-form">
        <div class="comment-form-header">
            <img src="assets/garden.png" alt="User" class="user-avatar">
            <span class="current-username"><?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <textarea 
            id="newCommentText" 
            class="comment-textarea" 
            placeholder="Share your thoughts about this manuscript... Be kind and constructive! 🌱"
            maxlength="1000"
        ></textarea>
        
        <div class="comment-form-footer">
            <div class="char-count">
                <span id="charCount">0</span>/1000
            </div>
            <button class="btn-post-comment" id="postCommentBtn">
                <img src="assets/comments.png" alt="Post" class="icon">
                Post Comment
            </button>
        </div>
    </div>

    <div class="comments-list" id="commentsList">
        <div class="loading-comments">
            <div class="spinner"></div>
            <p>Loading comments...</p>
        </div>
    </div>
</div>

<template id="commentTemplate">
    <div class="comment-item" data-comment-id="">
        <div class="comment-main">
            <div class="comment-avatar">
                <img src="assets/garden.png" alt="User" class="user-avatar">
            </div>
            
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author"></span>
                    <span class="author-badge">Author</span>
                    <span class="comment-date"></span>
                    <span class="edited-badge">Edited</span>
                </div>
                
                <div class="comment-text"></div>
                
                <div class="comment-actions">
                    <button class="comment-action-btn like-btn" data-action="like">
                        <img src="assets/like.png" alt="Like" class="icon">
                        <span class="like-count">0</span>
                    </button>
                    
                    <button class="comment-action-btn dislike-btn" data-action="dislike">
                        <img src="assets/dislike.png" alt="Dislike" class="icon">
                        <span class="dislike-count">0</span>
                    </button>
                    
                    <button class="comment-action-btn reply-btn">
                        <img src="assets/comments.png" alt="Reply" class="icon">
                        Reply
                    </button>
                    
                    <button class="comment-action-btn edit-btn">
                        <img src="assets/filter.png" alt="Edit" class="icon">
                        Edit
                    </button>
                    
                    <button class="comment-action-btn delete-btn">
                        <img src="assets/dislike.png" alt="Delete" class="icon">
                        Delete
                    </button>
                </div>
                
                <div class="edit-comment-form" style="display: none;">
                    <textarea class="edit-textarea" maxlength="1000"></textarea>
                    <div class="edit-actions">
                        <button class="btn-save-edit">Save</button>
                        <button class="btn-cancel-edit">Cancel</button>
                    </div>
                </div>
                
                <div class="reply-form" style="display: none;">
                    <textarea class="reply-textarea" placeholder="Write your reply..." maxlength="1000"></textarea>
                    <div class="reply-actions">
                        <button class="btn-post-reply">Post Reply</button>
                        <button class="btn-cancel-reply">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="replies-container"></div>
    </div>
</template>

<script src="includes/comments.js"></script>
<script>
    // Initialize comments system
    const currentUserId = <?php echo $user_id; ?>;
    const currentUsername = '<?php echo addslashes($username); ?>';
    const manuscriptAuthorId = <?php echo $manuscript['user_id'] ?? 0; ?>;
    
    initCommentsSystem(<?php echo $manuscript_id; ?>, currentUserId, currentUsername, manuscriptAuthorId);
</script>