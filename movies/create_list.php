<?php
session_start();
require '../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'] ?? '';
$movie_ids = $data['movies'] ?? [];

if (!isset($_SESSION['uid'])) {
    http_response_code(403); 
    echo "Access denied: you must be logged in.";
    exit;
}

$user_id = $_SESSION['uid'];

if (!$name) {
    http_response_code(400);
    echo "Missing list name";
    exit;
}

$stmt = $pdo->prepare("INSERT INTO movie_lists (user_id, name) VALUES (?, ?) RETURNING id");
$stmt->execute([$user_id, $name]);
$list_id = $stmt->fetchColumn();

if (!empty($movie_ids) && is_array($movie_ids)) {
    $stmt = $pdo->prepare("INSERT INTO movie_list_items (list_id, movie_id) VALUES (?, ?)");
    foreach ($movie_ids as $movie_id) {
        $stmt->execute([$list_id, $movie_id]);
    }
}

echo $list_id;
