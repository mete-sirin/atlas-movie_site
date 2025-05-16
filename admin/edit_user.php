<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    echo "User ID missing.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE id = ?");
    $stmt->execute([$username, $email, $is_admin, $id]);

    header('Location: admin.php');
    exit;
}
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <style>
    body { font-family: Arial; padding: 20px; }
    form { max-width: 400px; margin-top: 20px; }
    label { display: block; margin: 12px 0 4px; }
    input[type="text"], input[type="email"] { width: 100%; padding: 6px; }
    button { margin-top: 12px; padding: 8px 14px; }
  </style>
</head>
<body>

  <h2>Edit User</h2>

  <form method="POST">
    <input type="hidden" name="id" value="<?= $user['id'] ?>">

    <label for="username">Username</label>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

    <label for="email">Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>
      <input type="checkbox" name="is_admin" <?= $user['is_admin'] ? 'checked' : '' ?>>
      Is Admin
    </label>

    <button type="submit">Save Changes</button>
  </form>

</body>
</html>
