<?php
session_start();
require_once '../templates/header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['uid'];
$apiKey = '848df3823eaece087b9bd5baf5cb2805';


$friends_stmt = $pdo->prepare("
    SELECT u.id, u.username
    FROM friendships f
    JOIN users u ON (u.id = f.friend_id AND f.user_id = ?) 
        OR (u.id = f.user_id AND f.friend_id = ?)
    WHERE f.status = 'accepted'
");
$friends_stmt->execute([$user_id, $user_id]);
$friends = $friends_stmt->fetchAll(PDO::FETCH_ASSOC);
$friend_ids = array_map(fn($friend) => $friend['id'], $friends);
$friend_ids[] = $user_id; 

$activities_stmt = $pdo->prepare("
    (SELECT 
        'review' as type,
        r.user_id,
        r.movie_id,
        r.rating,
        r.review as comment,
        r.created_at,
        u.username
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.user_id IN (" . str_repeat('?,', count($friend_ids)-1) . "?)
    )
    UNION ALL
    (SELECT 
        'favorite' as type,
        f.user_id,
        f.movie_id,
        NULL as rating,
        NULL as comment,
        f.created_at,
        u.username
    FROM favorites f
    JOIN users u ON u.id = f.user_id
    WHERE f.user_id IN (" . str_repeat('?,', count($friend_ids)-1) . "?)
    )
    UNION ALL
    (SELECT 
        'list' as type,
        ml.user_id,
        NULL as movie_id,
        NULL as rating,
        ml.name as comment,
        ml.created_at,
        u.username
    FROM movie_lists ml
    JOIN users u ON u.id = ml.user_id
    WHERE ml.user_id IN (" . str_repeat('?,', count($friend_ids)-1) . "?)
    )
    ORDER BY created_at DESC
    LIMIT 50
");


$params = array_merge($friend_ids, $friend_ids, $friend_ids);
$activities_stmt->execute($params);
$activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="social-feed-container">
    <h1>Social Feed</h1>
    
    <?php if (empty($friends)): ?>
        <div class="no-friends">
            <p>You haven't added any friends yet!</p>
            <a href="../users/search_users.php" class="btn btn-primary">Find Friends</a>
        </div>
    <?php elseif (empty($activities)): ?>
        <div class="no-activities">
            <p>No recent activities from your friends</p>
        </div>
    <?php else: ?>
        <div class="activity-feed">
            <?php foreach ($activities as $activity): 
               
                $movie = null;
                if ($activity['movie_id']) {
                    $url = "https://api.themoviedb.org/3/movie/{$activity['movie_id']}?api_key=$apiKey&language=en-US";
                    $response = @file_get_contents($url);
                    $movie = json_decode($response, true);
                }
            ?>
                <div class="activity-card">
                    <div class="activity-header">
                        <div class="activity-info">
                            <strong><?= htmlspecialchars($activity['username']) ?></strong>
                            <?php
                            switch($activity['type']) {
                                case 'review':
                                    echo " reviewed ";
                                    break;
                                case 'favorite':
                                    echo " added to favorites ";
                                    break;
                                case 'list':
                                    echo " created a new list: ";
                                    break;
                            }
                            ?>
                            <?php if ($movie): ?>
                                <a href="../movies/movie.php?id=<?= $activity['movie_id'] ?>"><?= htmlspecialchars($movie['title']) ?></a>
                            <?php elseif ($activity['type'] === 'list'): ?>
                                <span class="list-name"><?= htmlspecialchars($activity['comment']) ?></span>
                            <?php endif; ?>
                            <span class="activity-time"><?= time_elapsed_string($activity['created_at']) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($activity['type'] === 'review'): ?>
                        <div class="activity-content">
                            <div class="review-rating">
                                Rating: <?= str_repeat('★', $activity['rating']) . str_repeat('☆', 5-$activity['rating']) ?>
                            </div>
                            <p class="review-comment"><?= htmlspecialchars($activity['comment']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($movie && $activity['type'] !== 'list'): ?>
                        <div class="movie-preview">
                            <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w200' . $movie['poster_path'] : '../assets/img/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($movie['title']) ?>">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.social-feed-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.activity-feed {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.activity-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.activity-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.activity-info {
    flex-grow: 1;
}

.activity-time {
    display: block;
    color: #666;
    font-size: 0.9em;
    margin-top: 5px;
}

.activity-content {
    margin: 15px 0;
}

.review-rating {
    color: #ffd700;
    font-size: 1.2em;
    margin-bottom: 10px;
}

.review-comment {
    color: #333;
    line-height: 1.5;
}

.movie-preview {
    margin-top: 15px;
}

.movie-preview img {
    border-radius: 4px;
    max-width: 150px;
}

.list-name {
    font-weight: 500;
    color: #2c3e50;
}

.no-friends, .no-activities {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 8px;
    margin-top: 20px;
}

.btn-primary {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    margin-top: 15px;
}

.btn-primary:hover {
    background: #0056b3;
}
</style>

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