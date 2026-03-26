<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ==================== GET COMMENTS ====================
if ($action === 'get') {
    $manuscript_id = intval($_GET['manuscript_id'] ?? 0);
    
    if (!$manuscript_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid manuscript ID']);
        exit();
    }
    
    
    $query = "
        SELECT 
            c.*,
            u.username,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction = 'like') as like_count,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction = 'dislike') as dislike_count,
            (SELECT reaction FROM comment_reactions WHERE comment_id = c.id AND user_id = $user_id) as user_reaction,
            (SELECT COUNT(*) FROM manuscript_comments WHERE parent_comment_id = c.id) as reply_count
        FROM manuscript_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.manuscript_id = $manuscript_id
        ORDER BY c.created_at ASC
    ";
    
    $result = mysqli_query($conn, $query);
    $comments = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $comments[] = $row;
    }
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    exit();
}

// ==================== POST COMMENT ====================
if ($action === 'post') {
    $manuscript_id = intval($_POST['manuscript_id'] ?? 0);
    $comment_text = trim($_POST['comment_text'] ?? '');
    $parent_comment_id = !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
    
    if (!$manuscript_id || empty($comment_text)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    if (strlen($comment_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Comment too long (max 1000 characters)']);
        exit();
    }
    
    // Verify manuscript exists
    $check = mysqli_query($conn, "SELECT id, user_id FROM manuscripts WHERE id = $manuscript_id");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(['success' => false, 'message' => 'Manuscript not found']);
        exit();
    }
    
    $manuscript = mysqli_fetch_assoc($check);
    $author_id = $manuscript['user_id'];
    
    // Prevent authors from commenting on their own manuscripts
    if ($user_id == $author_id && !$parent_comment_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot comment on your own manuscript']);
        exit();
    }
    
    // Insert comment
    $comment_text_escaped = mysqli_real_escape_string($conn, $comment_text);
    $parent_sql = $parent_comment_id ? $parent_comment_id : 'NULL';
    
    $insert = "INSERT INTO manuscript_comments (manuscript_id, user_id, parent_comment_id, comment_text) 
               VALUES ($manuscript_id, $user_id, $parent_sql, '$comment_text_escaped')";
    
    if (mysqli_query($conn, $insert)) {
        $comment_id = mysqli_insert_id($conn);
        
        // Create notification for manuscript author (if not commenting on own manuscript)
        if ($user_id != $author_id) {
            $notif_type = $parent_comment_id ? 'reply' : 'comment';
            mysqli_query($conn, "
                INSERT INTO comment_notifications (user_id, comment_id, manuscript_id, notification_type)
                VALUES ($author_id, $comment_id, $manuscript_id, '$notif_type')
            ");
        }
        
        if ($parent_comment_id) {
            $parent_query = mysqli_query($conn, "SELECT user_id FROM manuscript_comments WHERE id = $parent_comment_id");
            if ($parent_row = mysqli_fetch_assoc($parent_query)) {
                $parent_author = $parent_row['user_id'];
                if ($parent_author != $user_id && $parent_author != $author_id) {
                    mysqli_query($conn, "
                        INSERT INTO comment_notifications (user_id, comment_id, manuscript_id, notification_type)
                        VALUES ($parent_author, $comment_id, $manuscript_id, 'reply')
                    ");
                }
            }
        }
        
        // Get the new comment with user info
        $get_comment = "
            SELECT 
                c.*,
                u.username,
                0 as like_count,
                0 as dislike_count,
                NULL as user_reaction,
                0 as reply_count
            FROM manuscript_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = $comment_id
        ";
        
        $result = mysqli_query($conn, $get_comment);
        $comment = mysqli_fetch_assoc($result);
        
        echo json_encode(['success' => true, 'comment' => $comment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
    }
    exit();
}

// ==================== EDIT COMMENT ====================
if ($action === 'edit') {
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    if (!$comment_id || empty($comment_text)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    if (strlen($comment_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Comment too long (max 1000 characters)']);
        exit();
    }
    
    $check = mysqli_query($conn, "SELECT user_id FROM manuscript_comments WHERE id = $comment_id");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit();
    }
    
    $comment = mysqli_fetch_assoc($check);
    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }
    
    
    $comment_text_escaped = mysqli_real_escape_string($conn, $comment_text);
    $update = "UPDATE manuscript_comments 
               SET comment_text = '$comment_text_escaped', is_edited = TRUE 
               WHERE id = $comment_id";
    
    if (mysqli_query($conn, $update)) {
        echo json_encode(['success' => true, 'message' => 'Comment updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
    }
    exit();
}

// ==================== DELETE COMMENT ====================
if ($action === 'delete') {
    $comment_id = intval($_POST['comment_id'] ?? 0);
    
    if (!$comment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        exit();
    }
    
    
    $check = mysqli_query($conn, "SELECT user_id FROM manuscript_comments WHERE id = $comment_id");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit();
    }
    
    $comment = mysqli_fetch_assoc($check);
    if ($comment['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit();
    }
    
    
    if (mysqli_query($conn, "DELETE FROM manuscript_comments WHERE id = $comment_id")) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
    exit();
}

// ==================== REACT TO COMMENT ====================
if ($action === 'react') {
    $comment_id = intval($_POST['comment_id'] ?? 0);
    $reaction = $_POST['reaction'] ?? '';
    
    if (!$comment_id || !in_array($reaction, ['like', 'dislike'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    
    $check = mysqli_query($conn, "
        SELECT reaction FROM comment_reactions 
        WHERE comment_id = $comment_id AND user_id = $user_id
    ");
    
    if (mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        
        if ($existing['reaction'] === $reaction) {
            mysqli_query($conn, "
                DELETE FROM comment_reactions 
                WHERE comment_id = $comment_id AND user_id = $user_id
            ");
            $new_reaction = null;
        } else {
            mysqli_query($conn, "
                UPDATE comment_reactions 
                SET reaction = '$reaction' 
                WHERE comment_id = $comment_id AND user_id = $user_id
            ");
            $new_reaction = $reaction;
        }
    } else {
        // Add new reaction
        mysqli_query($conn, "
            INSERT INTO comment_reactions (comment_id, user_id, reaction) 
            VALUES ($comment_id, $user_id, '$reaction')
        ");
        $new_reaction = $reaction;
    }
    
    // Get updated counts
    $counts = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT 
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = $comment_id AND reaction = 'like') as like_count,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = $comment_id AND reaction = 'dislike') as dislike_count
        FROM manuscript_comments WHERE id = $comment_id
    "));
    
    echo json_encode([
        'success' => true,
        'like_count' => $counts['like_count'],
        'dislike_count' => $counts['dislike_count'],
        'user_reaction' => $new_reaction
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>