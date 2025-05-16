<?php
require_once '../templates/header.php';
require_once '../includes/db.php';
require_once 'friendship.php';


if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = $_SESSION['uid'];
$profile_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : $uid;

$st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$st->execute([$profile_id]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>User not found.</p>";
    exit;
}
?>

<?php
$friends_stmt = $pdo->prepare("
    SELECT u.id, u.username
    FROM friendships f
    JOIN users u ON 
        (u.id = f.friend_id AND f.user_id = ?) OR
        (u.id = f.user_id AND f.friend_id = ?)
    WHERE f.status = 'accepted'
");
$friends_stmt->execute([$profile_id, $profile_id]);
$friends = $friends_stmt->fetchAll(PDO::FETCH_ASSOC);


$favorites_stmt = $pdo->prepare("
    SELECT f.movie_id, f.created_at
    FROM favorites f
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 6
");
$favorites_stmt->execute([$profile_id]);
$favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);


$watch_later = [];
if ($profile_id == $uid) {
    $watch_later_stmt = $pdo->prepare("
        SELECT w.movie_id, w.added_at
        FROM watch_later w
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
        LIMIT 6
    ");
    $watch_later_stmt->execute([$uid]);
    $watch_later = $watch_later_stmt->fetchAll(PDO::FETCH_ASSOC);
}


$reviews_stmt = $pdo->prepare("
    SELECT r.movie_id, r.rating, r.review, r.created_at
    FROM reviews r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviews_stmt->execute([$profile_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);


$lists_stmt = $pdo->prepare("
    SELECT id, name, created_at
    FROM movie_lists
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$lists_stmt->execute([$profile_id]);
$movie_lists = $lists_stmt->fetchAll(PDO::FETCH_ASSOC);


$apiKey = '848df3823eaece087b9bd5baf5cb2805';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1><?= htmlspecialchars($user['username']) ?>'s Profile</h1>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Joined:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
    </div>

    <?php
    if ($profile_id !== $uid) {
        echo '<div id="friend-controls" class="profile-actions">';
        echo getFriendshipButton($pdo, $uid, $profile_id);
        echo '</div>';
    }
    ?>

    <div class="profile-sections">
        <div class="profile-section favorites-section">
            <div class="section-header">
                <h2>Favorite Movies</h2>
                <a href="favorites.php?id=<?= $profile_id ?>" class="btn btn-outline-primary">View All</a>
            </div>
            <div class="favorites-grid">
                <?php if (empty($favorites)): ?>
                    <p class="no-content">
                        <?php if ($profile_id == $uid): ?>
                            No favorite movies yet. Browse movies and click the heart icon to add them to your favorites!
                        <?php else: ?>
                            No favorite movies yet.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($favorites as $favorite): 
                        $movie_id = $favorite['movie_id'];
                        $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey&language=en-US";
                        $response = @file_get_contents($url);
                        $movie = json_decode($response, true);
                        
                        if ($movie && !isset($movie['status_code'])):
                    ?>
                        <div class="movie-item">
                            <a href="../movies/movie.php?id=<?= $movie_id ?>" class="movie-link">
                                <div class="movie-poster">
                                    <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w200' . $movie['poster_path'] : '/atlas/assets/img/placeholder.jpg' ?>" 
                                         alt="<?= htmlspecialchars($movie['title']) ?>" 
                                         title="<?= htmlspecialchars($movie['title']) ?>">
                                    <div class="movie-info-overlay">
                                        <h4><?= htmlspecialchars($movie['title']) ?></h4>
                                        <p class="movie-year"><?= substr($movie['release_date'], 0, 4) ?></p>
                                        <div class="movie-rating">
                                            <span class="star">★</span>
                                            <span class="rating-value"><?= number_format($movie['vote_average'], 1) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php 
                        endif;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($profile_id == $uid): ?>
        <div class="profile-section watch-later-section">
            <div class="section-header">
                <h2>Watch Later</h2>
                <a href="../movies/watch_later_list.php" class="btn btn-outline-primary">View All</a>
            </div>
            <div class="watch-later-grid">
                <?php if (empty($watch_later)): ?>
                    <p class="no-content">
                        No movies in watch later list. Browse movies and click the clock icon to add them to your watch later list!
                    </p>
                <?php else: ?>
                    <?php foreach ($watch_later as $item): 
                        $movie_id = $item['movie_id'];
                        $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey&language=en-US";
                        $response = @file_get_contents($url);
                        $movie = json_decode($response, true);
                        
                        if ($movie && !isset($movie['status_code'])):
                    ?>
                        <div class="movie-item">
                            <a href="../movies/movie.php?id=<?= $movie_id ?>" class="movie-link">
                                <div class="movie-poster">
                                    <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w200' . $movie['poster_path'] : '/atlas/assets/img/placeholder.jpg' ?>" 
                                         alt="<?= htmlspecialchars($movie['title']) ?>" 
                                         title="<?= htmlspecialchars($movie['title']) ?>">
                                    <div class="movie-info-overlay">
                                        <h4><?= htmlspecialchars($movie['title']) ?></h4>
                                        <p class="movie-year"><?= substr($movie['release_date'], 0, 4) ?></p>
                                        <div class="movie-rating">
                                            <span class="star">★</span>
                                            <span class="rating-value"><?= number_format($movie['vote_average'], 1) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php 
                        endif;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="profile-section reviews-section">
            <div class="section-header">
            <h2>Recent Reviews</h2>
                <a href="reviews.php?id=<?= $profile_id ?>" class="btn btn-outline-primary">View All Reviews</a>
            </div>
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p class="no-content">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): 
                        $movie_id = $review['movie_id'];
                        $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey&language=en-US";
                        $response = @file_get_contents($url);
                        $movie = json_decode($response, true);
                        
                        if ($movie && !isset($movie['status_code'])):
                    ?>
                        <div class="review-item">
                            <div class="review-header">
                                <a href="../movies/movie.php?id=<?= $movie_id ?>">
                                    <?= htmlspecialchars($movie['title']) ?>
                                </a>
                                <div class="rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-body">
                                <?= nl2br(htmlspecialchars(substr($review['review'], 0, 150))) ?>
                                <?= (strlen($review['review']) > 150) ? '...' : '' ?>
                            </div>
                            <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                        </div>
                    <?php 
                        endif;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-section">
            <h2>Friends</h2>
            <ul class="friends-list">
                <?php if (empty($friends)): ?>
                    <li>No friends yet.</li>
                <?php else: ?>
                    <?php foreach ($friends as $friend): ?>
                        <li>
                            <a href="profile.php?id=<?= $friend['id'] ?>">
                                <?= htmlspecialchars($friend['username']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="profile-section lists-section">
            <div class="section-header">
                <h2>Movie Lists</h2>
            </div>
            <?php if (empty($movie_lists)): ?>
                <p class="no-content">No movie lists yet.</p>
            <?php else: ?>
                <ul class="lists-list">
                    <?php foreach ($movie_lists as $list): ?>
                        <li>
                            <a href="../movies/view_list.php?id=<?= $list['id'] ?>">
                                <?= htmlspecialchars($list['name']) ?>
                            </a>
                            <span class="list-date"><?= date('M j, Y', strtotime($list['created_at'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="profile-links">
                <a href="movie_list_profile.php?id=<?= $user['id'] ?>" class="btn btn-outline-primary">View All Lists</a>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.profile-actions {
    margin-bottom: 20px;
}

.profile-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

@media (max-width: 768px) {
    .profile-sections {
        grid-template-columns: 1fr;
    }
}

.profile-section {
    margin-bottom: 30px;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    margin: 0;
}

.favorites-section, .reviews-section {
    grid-column: 1 / -1;
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
    color: white;
}

.favorites-section h2, .reviews-section h2,
.favorites-section a, .reviews-section a {
    color: white;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.movie-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    aspect-ratio: 2/3;
}

.movie-item:hover {
    transform: scale(1.05);
}

.movie-link {
    display: block;
    height: 100%;
    text-decoration: none;
    color: white;
}

.movie-poster {
    position: relative;
    height: 100%;
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.movie-info-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    opacity: 0;
    transition: opacity 0.2s;
}

.movie-item:hover .movie-info-overlay {
    opacity: 1;
}

.movie-info-overlay h4 {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    font-weight: bold;
}

.movie-year {
    margin: 0;
    font-size: 0.8rem;
    opacity: 0.8;
}

.movie-rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
}

.star {
    color: #ffd700;
}

.rating-value {
    font-size: 0.9rem;
}

.no-content {
    text-align: center;
    padding: 30px;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    margin: 0;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 15px;
}

.review-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 15px;
    transition: transform 0.2s;
    border: none;
}

.review-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.review-header a {
    font-weight: bold;
    color: white;
    text-decoration: none;
}

.review-header a:hover {
    text-decoration: underline;
}

.rating {
    display: flex;
}

.star {
    color: #aaa;
    font-size: 18px;
    margin-right: 2px;
}

.star.filled {
    color: #ffc107;
}

.review-body {
    margin-bottom: 10px;
    font-size: 0.9em;
    color: #e0e0e0;
    line-height: 1.5;
}

.review-date {
    font-size: 0.8em;
    color: #aaa;
    text-align: right;
}

.friends-list {
    padding-left: 20px;
}

.friends-list li {
    margin-bottom: 10px;
}

.friends-list a {
    color: #333;
    text-decoration: none;
}

.friends-list a:hover {
    text-decoration: underline;
}

.view-all {
    text-align: center;
    margin-top: 15px;
}

.profile-links {
    margin-top: 20px;
}

.btn {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: 1px solid #6c757d;
}

.btn-success {
    background-color: #28a745;
    color: white;
    border: 1px solid #28a745;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: 1px solid #dc3545;
}

.btn-outline-primary {
    background-color: transparent;
    color: #007bff;
    border: 1px solid #007bff;
}

.btn-outline-danger {
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.btn:hover {
    opacity: 0.85;
}

.lists-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.lists-list li {
    margin-bottom: 10px;
    padding: 10px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.lists-list a {
    color: #333;
    text-decoration: none;
    font-weight: bold;
}

.lists-list a:hover {
    text-decoration: underline;
}

.list-date {
    font-size: 0.8em;
    color: #888;
}
</style>

<?php require_once '../templates/footer.php'; ?> 

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.sendFriendAction = function (action, targetId) {
        const btn = document.getElementById(`friend-btn-${targetId}`);

        fetch('../users/friendship.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&target_id=${targetId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && btn) {
                if (data.new_status === 'add') {
                    btn.textContent = 'Add Friend';
                    btn.setAttribute('onclick', `sendFriendAction('add', ${targetId})`);
                    btn.className = 'btn btn-primary';
                } else if (data.new_status === 'cancel') {
                    btn.textContent = 'Cancel Request';
                    btn.setAttribute('onclick', `sendFriendAction('cancel', ${targetId})`);
                    btn.className = 'btn btn-secondary';
                } else if (data.new_status === 'remove') {
                    btn.textContent = 'Remove Friend';
                    btn.setAttribute('onclick', `sendFriendAction('remove', ${targetId})`);
                    btn.className = 'btn btn-outline-danger';
                }
            }
        })
        .catch(console.error);
    }
});
</script>
