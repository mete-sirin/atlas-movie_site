<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);


ob_start();

require_once '../includes/db.php';
session_start();


header('Content-Type: application/json');

try {
   
    error_log("movie_reviews.php accessed. Method: " . $_SERVER['REQUEST_METHOD'] . ", movie_id: " . ($_GET['movie_id'] ?? 'none'));

    
    if (!isset($_SESSION['uid']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        throw new Exception('User not logged in');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['movie_id'])) {
            throw new Exception('Missing movie ID');
        }
        
        $movie_id = (int)$_GET['movie_id'];
        $show_friends_only = isset($_GET['friends_only']) && $_GET['friends_only'] === 'true';
        $current_user_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
        
        try {
            if ($show_friends_only && $current_user_id) {
               
                $stmt = $pdo->prepare("
                    SELECT r.rating, r.review, r.created_at, u.username, u.id as user_id,
                           CASE WHEN r.user_id = ? THEN true ELSE false END as is_own_review
                    FROM reviews r
                    JOIN users u ON r.user_id = u.id
                    JOIN friendships f ON 
                        (f.user_id = ? AND f.friend_id = r.user_id) OR
                        (f.friend_id = ? AND f.user_id = r.user_id)
                    WHERE r.movie_id = ? AND f.status = 'accepted'
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $movie_id]);
            } else {
               
                $stmt = $pdo->prepare("
                    SELECT r.rating, r.review, r.created_at, u.username, u.id as user_id,
                           CASE WHEN r.user_id = ? THEN true ELSE false END as is_own_review
                    FROM reviews r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.movie_id = ?
                    ORDER BY r.created_at DESC
                ");
                $stmt->execute([$current_user_id ?? 0, $movie_id]);
            }
            
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            $avg_stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating FROM reviews WHERE movie_id = ?");
            $avg_stmt->execute([$movie_id]);
            $avg_rating = round($avg_stmt->fetchColumn(), 1);
            
            
            $user_review = null;
            if ($current_user_id) {
                $user_stmt = $pdo->prepare("
                    SELECT rating, review, created_at 
                    FROM reviews 
                    WHERE user_id = ? AND movie_id = ?
                ");
                $user_stmt->execute([$current_user_id, $movie_id]);
                $user_review = $user_stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'average_rating' => $avg_rating,
                'count' => count($reviews),
                'user_review' => $user_review
            ]);
            exit;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception('Failed to fetch reviews');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['uid'])) {
            throw new Exception('Must be logged in to review');
        }
        
        $user_id = $_SESSION['uid'];
        $action = isset($_POST['action']) ? $_POST['action'] : 'add';
        $movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        
        if (!$movie_id) {
            throw new Exception('Missing movie ID');
        }
        
        if ($action === 'add') {
            $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
            $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
            
            if (!$rating || $rating < 1 || $rating > 5 || empty($comment)) {
                throw new Exception('Invalid rating or missing comment');
            }
            
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND movie_id = ?");
            $check_stmt->execute([$user_id, $movie_id]);
            $exists = (int)$check_stmt->fetchColumn() > 0;
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, review = ?, created_at = NOW() WHERE user_id = ? AND movie_id = ?");
                $result = $stmt->execute([$rating, $comment, $user_id, $movie_id]);
                $message = 'Review updated successfully';
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$user_id, $movie_id, $rating, $comment]);
                $message = 'Review added successfully';
            }
            
            if (!$result) {
                throw new Exception('Database operation failed');
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }
        elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_id = ? AND movie_id = ?");
            $result = $stmt->execute([$user_id, $movie_id]);
            
            if (!$result) {
                throw new Exception('Failed to delete review');
            }
            
            echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
            exit;
        } else {
            throw new Exception('Invalid action');
        }
    }
} catch (Exception $e) {
    
    ob_clean();
    
    error_log("Review error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} catch (PDOException $e) {
    
    ob_clean();
    
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
    exit;
}
