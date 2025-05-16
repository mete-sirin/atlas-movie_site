<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$user_id = $_SESSION['uid'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;

if (!$movie_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
    exit;
}

try {
    if ($action === 'add') {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND movie_id = ?");
        $check_stmt->execute([$user_id, $movie_id]);
        $exists = (int)$check_stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo json_encode(['success' => true, 'message' => 'Movie already in favorites']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, movie_id, created_at) VALUES (?, ?, NOW())");
        $result = $stmt->execute([$user_id, $movie_id]);
        
        echo json_encode(['success' => $result, 'message' => $result ? 'Added to favorites' : 'Failed to add to favorites']);
    } 
    elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND movie_id = ?");
        $result = $stmt->execute([$user_id, $movie_id]);
        
        echo json_encode(['success' => $result, 'message' => $result ? 'Removed from favorites' : 'Failed to remove from favorites']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Favorites error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 