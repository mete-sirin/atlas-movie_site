<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

if (isset($_SESSION['uid']) && !isset($_SESSION['is_admin'])) {
    $st = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $st->execute([$_SESSION['uid']]);
    $result = $st->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $_SESSION['is_admin'] = $result['is_admin'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Atlas - Movie Database</title>
    <link rel="stylesheet" href="/atlas/assets/css/style.css">
</head>
<body>
<header>
    <h1>Atlas Movie Site</h1>
    <nav>
        <a href="/atlas/index.php">Home</a>
        <a href="/atlas/users/own_profile.php">Profile</a>
        <a href="/atlas/social/feed.php">Social</a>
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="/atlas/admin/admin.php">Admin Page</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['uid'])): ?>
            <div class="notifications-wrapper">
                <a href="/atlas/social/notifications.php" class="notifications-icon">
                    🔔
                    <?php
                   
                    $notif_stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM friendships 
                        WHERE friend_id = ? AND status = 'pending'
                    ");
                    $notif_stmt->execute([$_SESSION['uid']]);
                    $notif_count = $notif_stmt->fetchColumn();
                    
                    if ($notif_count > 0): ?>
                        <span class="notification-badge"><?= $notif_count ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endif; ?>
        <a href="/atlas/auth/logout.php">Logout</a>
    </nav>
    <hr>
</header>
<main>

<style>
.notifications-wrapper {
    display: inline-block;
    position: relative;
    margin-left: 10px;
}

.notifications-icon {
    text-decoration: none;
    font-size: 1.2em;
    position: relative;
    padding: 5px;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    font-weight: bold;
}
</style>
