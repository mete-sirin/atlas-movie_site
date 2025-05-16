<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['uid'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['movie_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['uid'];
$movie_id = (int) $data['movie_id'];
$action = $data['action'];

try {
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO watch_later (user_id, movie_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
        $result = $stmt->execute([$user_id, $movie_id]);
        echo json_encode(['success' => true, 'message' => 'Added to watch later']);
    } 
    elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM watch_later WHERE user_id = ? AND movie_id = ?");
        $result = $stmt->execute([$user_id, $movie_id]);
        echo json_encode(['success' => true, 'message' => 'Removed from watch later']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Watch Later Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 