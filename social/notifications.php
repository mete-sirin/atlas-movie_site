<?php
session_start();
require_once '../templates/header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['uid'];

// Handle friend request actions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $friend_id = (int)($_POST['friend_id'] ?? 0);
    
    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
        $success = $stmt->execute([$friend_id, $user_id]);
        echo json_encode(['success' => $success]);
    } elseif ($action === 'decline') {
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
        $success = $stmt->execute([$friend_id, $user_id]);
        echo json_encode(['success' => $success]);
    }
    exit;
}

// Get pending friend requests
$requests_stmt = $pdo->prepare("
    SELECT f.*, u.username, u.email
    FROM friendships f
    JOIN users u ON u.id = f.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$requests_stmt->execute([$user_id]);
$friend_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="notifications-container">
    <h1>Notifications</h1>
    
    <div class="notifications-section">
        <h2>Friend Requests</h2>
        <?php if (empty($friend_requests)): ?>
            <p class="no-notifications">No pending friend requests</p>
        <?php else: ?>
            <div class="friend-requests">
                <?php foreach ($friend_requests as $request): ?>
                    <div class="friend-request-card" data-friend-id="<?= $request['user_id'] ?>">
                        <div class="request-info">
                            <h3><?= htmlspecialchars($request['username']) ?></h3>
                            <p class="request-time">Sent <?= time_elapsed_string($request['created_at']) ?></p>
                        </div>
                        <div class="request-actions">
                            <button class="accept-btn" onclick="handleRequest('accept', <?= $request['user_id'] ?>)">Accept</button>
                            <button class="decline-btn" onclick="handleRequest('decline', <?= $request['user_id'] ?>)">Decline</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.notifications-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.friend-request-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.friend-request-card:last-child {
    border-bottom: none;
}

.friend-request-card:hover {
    background-color: #f8f9fa;
}

.request-info {
    flex-grow: 1;
}

.request-text h3 {
    margin: 0;
    color: #333;
    font-size: 1.1em;
}

.request-time {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.request-actions {
    display: flex;
    gap: 10px;
}

.accept-btn, .decline-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
}

.accept-btn {
    background-color: #28a745;
    color: white;
}

.accept-btn:hover {
    background-color: #218838;
}

.decline-btn {
    background-color: #dc3545;
    color: white;
}

.decline-btn:hover {
    background-color: #c82333;
}

.no-notifications {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}
</style>

<script>
function handleRequest(action, friendId) {
    fetch('notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&friend_id=${friendId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the request card from the UI
            const card = document.querySelector(`[data-friend-id="${friendId}"]`);
            card.remove();
            
            // Check if there are any remaining requests
            const remainingCards = document.querySelectorAll('.friend-request-card');
            if (remainingCards.length === 0) {
                const section = document.querySelector('.friend-requests');
                section.innerHTML = '<p class="no-notifications">No pending friend requests</p>';
            }
            
            // Update the notification badge in the header
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                const currentCount = parseInt(badge.textContent);
                if (currentCount > 1) {
                    badge.textContent = currentCount - 1;
                } else {
                    badge.remove();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the request');
    });
}

// Helper function to format dates
function time_elapsed_string($datetime, $full = false) {
    $now = new Date();
    $past = new Date($datetime);
    $diff = Math.floor((now - past) / 1000);

    if ($diff < 60) {
        return 'just now';
    } else if ($diff < 3600) {
        return Math.floor($diff / 60) + ' minutes ago';
    } else if ($diff < 86400) {
        return Math.floor($diff / 3600) + ' hours ago';
    } else if ($diff < 604800) {
        return Math.floor($diff / 86400) + ' days ago';
    } else {
        return past.toLocaleDateString();
    }
}
</script>

<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

require_once '../templates/footer.php';
?> 