<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['manuscript_id']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

$manuscript_id = (int)$input['manuscript_id'];
$action = $input['action']; // 'like' or 'dislike'

// Validate action
if (!in_array($action, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit();
}

// Check if manuscript exists
$check_manuscript = "SELECT id, user_id FROM manuscripts WHERE id = $manuscript_id";
$result = mysqli_query($conn, $check_manuscript);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'error' => 'Manuscript not found']);
    exit();
}

$manuscript_data = mysqli_fetch_assoc($result);
$owner_id = $manuscript_data['user_id'];

// Prevent users from reacting to their own manuscripts
if ($owner_id == $user_id) {
    echo json_encode(['success' => false, 'error' => 'Cannot react to your own manuscript']);
    exit();
}

// Check if user already reacted
$check_reaction = "SELECT reaction FROM manuscript_reactions 
                   WHERE manuscript_id = $manuscript_id AND user_id = $user_id";
$existing_reaction = mysqli_query($conn, $check_reaction);

if (!$existing_reaction) {
    echo json_encode(['success' => false, 'error' => 'Database error checking reaction']);
    exit();
}

if (mysqli_num_rows($existing_reaction) > 0) {
    $current_reaction = mysqli_fetch_assoc($existing_reaction)['reaction'];
    
    // If clicking the same reaction, remove it (toggle off)
    if ($current_reaction == $action) {
        $delete_query = "DELETE FROM manuscript_reactions 
                        WHERE manuscript_id = $manuscript_id AND user_id = $user_id";
        if (!mysqli_query($conn, $delete_query)) {
            echo json_encode(['success' => false, 'error' => 'Database error deleting reaction']);
            exit();
        }
        $user_reaction = null;
    } else {
        // Change to the other reaction
        $update_query = "UPDATE manuscript_reactions 
                        SET reaction = '$action', created_at = NOW()
                        WHERE manuscript_id = $manuscript_id AND user_id = $user_id";
        if (!mysqli_query($conn, $update_query)) {
            echo json_encode(['success' => false, 'error' => 'Database error updating reaction']);
            exit();
        }
        $user_reaction = $action;
    }
} else {
    // Add new reaction
    $insert_query = "INSERT INTO manuscript_reactions (manuscript_id, user_id, reaction, created_at) 
                    VALUES ($manuscript_id, $user_id, '$action', NOW())";
    if (!mysqli_query($conn, $insert_query)) {
        echo json_encode(['success' => false, 'error' => 'Database error inserting reaction']);
        exit();
    }
    $user_reaction = $action;
}

// Get updated counts
$like_count_query = "SELECT COUNT(*) as count FROM manuscript_reactions 
                     WHERE manuscript_id = $manuscript_id AND reaction = 'like'";
$like_result = mysqli_query($conn, $like_count_query);
if (!$like_result) {
    echo json_encode(['success' => false, 'error' => 'Database error getting like count']);
    exit();
}
$like_count = mysqli_fetch_assoc($like_result)['count'];

$dislike_count_query = "SELECT COUNT(*) as count FROM manuscript_reactions 
                        WHERE manuscript_id = $manuscript_id AND reaction = 'dislike'";
$dislike_result = mysqli_query($conn, $dislike_count_query);
if (!$dislike_result) {
    echo json_encode(['success' => false, 'error' => 'Database error getting dislike count']);
    exit();
}
$dislike_count = mysqli_fetch_assoc($dislike_result)['count'];

// Return success response
echo json_encode([
    'success' => true,
    'like_count' => $like_count,
    'dislike_count' => $dislike_count,
    'user_reaction' => $user_reaction
]);
?>