<?php
require_once '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('get_friendship_status')) {
    function get_friendship_status($uid, $fid, $pdo) {
        $stmt = $pdo->prepare("SELECT user_id, friend_id, status FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->execute([$uid, $fid, $fid, $uid]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['uid']) || !isset($_POST['action']) || !isset($_POST['target_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $user_id = $_SESSION['uid'];
    $target_id = (int) $_POST['target_id'];
    action_dispatch($_POST['action'], $user_id, $target_id, $pdo);
}

function action_dispatch(string $action, int $user_id, int $target_id, PDO $pdo) {
    switch ($action) {
        case 'add':
            add_friend($user_id, $target_id, $pdo);
            break;
        case 'cancel':
        case 'decline':
            delete_friendship($user_id, $target_id, $pdo);
            break;
        case 'accept':
            accept_friend($user_id, $target_id, $pdo);
            break;
        case 'remove':
            delete_friendship($user_id, $target_id, $pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
}

function add_friend($uid, $fid, $pdo) {
    $stmt = $pdo->prepare("SELECT 1 FROM friendships WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$uid, $fid]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')")
            ->execute([$uid, $fid]);
    }
    echo json_encode(['success' => true, 'new_status' => 'cancel']);
}

function delete_friendship($uid, $fid, $pdo) {
    $pdo->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)")
        ->execute([$uid, $fid, $fid, $uid]);
    echo json_encode(['success' => true, 'new_status' => 'add']);
}

function accept_friend($uid, $fid, $pdo) {
    $pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE user_id = ? AND friend_id = ?")
        ->execute([$fid, $uid]);
    echo json_encode(['success' => true, 'new_status' => 'remove']);
}


function getFriendshipButton($pdo, $user_id, $profile_id) {

    $friendship = get_friendship_status($user_id, $profile_id, $pdo);
    
    
    $button = '<button id="friend-btn-' . $profile_id . '" onclick="sendFriendAction(\'add\', ' . $profile_id . ')" class="btn btn-primary">Add Friend</button>';
    
    if ($friendship) {
        
        if ($friendship['status'] == 'accepted') {
            
            $button = '<button id="friend-btn-' . $profile_id . '" onclick="sendFriendAction(\'remove\', ' . $profile_id . ')" class="btn btn-outline-danger">Remove Friend</button>';
        } else if ($friendship['status'] == 'pending') {
            
            if ($friendship['user_id'] == $user_id) {
                
                $button = '<button id="friend-btn-' . $profile_id . '" onclick="sendFriendAction(\'cancel\', ' . $profile_id . ')" class="btn btn-secondary">Cancel Request</button>';
            } else {
              
                $button = '
                <button id="friend-btn-' . $profile_id . '" onclick="sendFriendAction(\'accept\', ' . $profile_id . ')" class="btn btn-success">Accept</button>
                <button id="friend-btn-decline-' . $profile_id . '" onclick="sendFriendAction(\'decline\', ' . $profile_id . ')" class="btn btn-danger">Decline</button>';
            }
        }
    }
    
    return $button;
}
