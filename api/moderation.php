<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ==================== DELETE COMMENT (MODERATION) ====================
if ($action === 'delete_moderation') {
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    if (!$comment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit();
    }
    
    // Verify that the user owns the manuscript this comment is on
    $check_query = "
        SELECT mc.id, mc.manuscript_id, m.user_id as manuscript_author_id
        FROM manuscript_comments mc
        JOIN manuscripts m ON mc.manuscript_id = m.id
        WHERE mc.id = $comment_id
    ";
    
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit();
    }
    
    $comment = mysqli_fetch_assoc($result);
    
    // Check if user is the manuscript author (has moderation rights)
    if ($comment['manuscript_author_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authorized to moderate this comment']);
        exit();
    }
    
    // Delete the comment (cascades to replies and reactions)
    if (mysqli_query($conn, "DELETE FROM manuscript_comments WHERE id = $comment_id")) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
    exit();
}

// ==================== GET COMMENT DETAILS ====================
if ($action === 'get_comment') {
    $comment_id = intval($_GET['comment_id'] ?? 0);
    
    if (!$comment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit();
    }
    
    // Get comment with manuscript ownership check
    $query = "
        SELECT 
            mc.*,
            u.username,
            m.title as manuscript_title,
            m.user_id as manuscript_author_id,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = mc.id AND reaction = 'like') as like_count,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = mc.id AND reaction = 'dislike') as dislike_count
        FROM manuscript_comments mc
        JOIN users u ON mc.user_id = u.id
        JOIN manuscripts m ON mc.manuscript_id = m.id
        WHERE mc.id = $comment_id
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit();
    }
    
    $comment = mysqli_fetch_assoc($result);
    
    // Verify user has moderation rights
    if ($comment['manuscript_author_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }
    
    echo json_encode(['success' => true, 'comment' => $comment]);
    exit();
}

// ==================== BULK DELETE ====================
if ($action === 'bulk_delete') {
    $comment_ids = $_POST['comment_ids'] ?? [];
    
    if (!is_array($comment_ids) || empty($comment_ids)) {
        echo json_encode(['success' => false, 'message' => 'No comments selected']);
        exit();
    }
    
    $deleted = 0;
    $failed = 0;
    
    foreach ($comment_ids as $comment_id) {
        $comment_id = intval($comment_id);
        
        // Verify ownership
        $check = mysqli_query($conn, "
            SELECT m.user_id 
            FROM manuscript_comments mc
            JOIN manuscripts m ON mc.manuscript_id = m.id
            WHERE mc.id = $comment_id
        ");
        
        if (mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            if ($row['user_id'] == $user_id) {
                if (mysqli_query($conn, "DELETE FROM manuscript_comments WHERE id = $comment_id")) {
                    $deleted++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        } else {
            $failed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'failed' => $failed,
        'message' => "Deleted $deleted comment(s)"
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>