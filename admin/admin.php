<?php
session_start();
require_once '../includes/db.php';
require_once '../templates/header.php';



$stmt = $pdo->query('SELECT id, username, email, is_admin, created_at FROM users ORDER BY id ASC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      padding: 8px 12px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #f2f2f2;
    }
    button {
      padding: 5px 10px;
      margin: 0 2px;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <h1>Admin Page</h1>
  <p>Welcome, <?= htmlspecialchars($_SESSION['user']) ?>!</p>

  <h2>Registered Users</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Admin</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
          <td><?= $user['created_at'] ?></td>
          <td>
              <button class="delete-user" data-id="<?= $user['id'] ?>">Delete</button>
              <form method="GET" action="edit_user.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= $user['id'] ?>">
              <button type="submit">Edit</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div id="confirmModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
  <div style="background:white; padding:20px; margin:100px auto; width:300px; border-radius:8px; text-align:center;">
    <p>Are you sure you want to delete this user?</p>
    <button id="confirmYes">Yes</button>
    <button id="confirmNo">Cancel</button>
  </div>
</div>



</body>
</html>

<script>
let userIdToDelete = null;

document.querySelectorAll('.delete-user').forEach(button => {
    button.addEventListener('click', () => {
        userIdToDelete = button.getAttribute('data-id');
        document.getElementById('confirmModal').style.display = 'block';
        button.dataset.row = button.closest('tr'); 
    });
});

document.getElementById('confirmYes').addEventListener('click', () => {
    if (userIdToDelete) {
        fetch(`delete.php?id=${userIdToDelete}`)
            .then(response => {
                if (response.ok) {
                    document.querySelector(`button[data-id="${userIdToDelete}"]`).closest('tr').remove();
                } else {
                    alert('Failed to delete user.');
                }
                document.getElementById('confirmModal').style.display = 'none';
                userIdToDelete = null;
            });
    }
});

document.getElementById('confirmNo').addEventListener('click', () => {
    document.getElementById('confirmModal').style.display = 'none';
    userIdToDelete = null;
});
</script>


