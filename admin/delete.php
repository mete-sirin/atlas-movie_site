<?php
require_once '../includes/db.php';


if (isset($_GET['id'])) {
    $id = $_GET['id'];
    echo "Attempting to delete ID: $id<br>"; 
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    echo "Deleted rows: " . $stmt->rowCount(); 
    http_response_code(200);
}

?>
