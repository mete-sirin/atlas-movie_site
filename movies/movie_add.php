<?php
session_start();
require '../includes/db.php';

$list_id = $_POST['list_id'] ?? '';
$movie_id = $_POST['movie_id'] ?? '';

if (!isset($_SESSION['uid'])) {
    http_response_code(403); 
    echo "Access denied: You must be logged in.";
    exit;
}

$user_id = $_SESSION['uid'];

if (!$list_id || !$movie_id) {
    http_response_code(400); 
    echo "Missing list ID or movie ID.";
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM movie_lists WHERE id = ? AND user_id = ?");
$stmt->execute([$list_id, $user_id]);
$validList = $stmt->fetch();

if (!$validList) {
    http_response_code(403);
    echo "Invalid or unauthorized list.";
    exit;
}

$stmt = $pdo->prepare("INSERT INTO movie_list_items (list_id, movie_id) VALUES (?, ?)");
$stmt->execute([$list_id, $movie_id]);

echo "Movie added successfully.";
?>
