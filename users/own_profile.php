<?php
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../templates/header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = $_SESSION['uid'];
$profile_id = $uid;

$st = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$st->execute([$profile_id]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>User not found.</p>";
    exit;
}


$favorites_stmt = $pdo->prepare("
    SELECT f.movie_id, f.created_at
    FROM favorites f
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 6
");
$favorites_stmt->execute([$profile_id]);
$favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);


$reviews_stmt = $pdo->prepare("
    SELECT r.movie_id, r.rating, r.review, r.created_at
    FROM reviews r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 3
");
$reviews_stmt->execute([$profile_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);


$watch_later_stmt = $pdo->prepare("
    SELECT w.movie_id, w.added_at
    FROM watch_later w
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
    LIMIT 6
");
$watch_later_stmt->execute([$uid]);
$watch_later = $watch_later_stmt->fetchAll(PDO::FETCH_ASSOC);


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


$requests_stmt = $pdo->prepare("
    SELECT u.id, u.username
    FROM friendships f
    JOIN users u ON u.id = f.user_id
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$requests_stmt->execute([$uid]);
$incoming_requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="profile-container">
    <div class="profile-header">
        <h1><?= htmlspecialchars($user['username']) ?>'s Profile</h1>
        <div class="profile-info">
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Joined:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        </div>
    </div>

    <div class="profile-sections">
        <div class="profile-section favorites-section">
            <div class="section-header">
                <h2>Favorite Movies</h2>
                <a href="favorites.php?id=<?= $profile_id ?>" class="btn btn-outline-primary">View All</a>
            </div>
            <div class="favorites-grid">
                <?php if (empty($favorites)): ?>
                    <p class="no-content">
                        No favorite movies yet. Browse movies and click the heart icon to add them to your favorites!
                    </p>
                <?php else: ?>
                    <?php foreach ($favorites as $favorite): 
                        $movie_id = $favorite['movie_id'];
                        $movie = getTMDBData("/movie/$movie_id", ['language' => 'en-US']);
                        
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
                        $movie = getTMDBData("/movie/$movie_id", ['language' => 'en-US']);
                        
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

        <div class="profile-section friends-section">
            <div class="section-header">
                <h2>Friends</h2>
                <a href="../users/search_users.php" class="btn btn-primary">Add Friends</a>
            </div>
            <?php if (!empty($incoming_requests)): ?>
                <div class="friend-requests">
                    <h3>Friend Requests</h3>
                    <ul class="requests-list">
                        <?php foreach ($incoming_requests as $req): ?>
                            <li class="request-item">
                                <span class="username"><?= htmlspecialchars($req['username']) ?></span>
                                <div class="request-actions">
                                    <button id="friend-btn-accept-<?= $req['id'] ?>" 
                                            onclick="sendFriendAction('accept', <?= $req['id'] ?>)"
                                            class="btn btn-success btn-sm">Accept</button>
                                    <button id="friend-btn-decline-<?= $req['id'] ?>" 
                                            onclick="sendFriendAction('decline', <?= $req['id'] ?>)"
                                            class="btn btn-danger btn-sm">Decline</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="friends-list">
                <?php if (empty($friends)): ?>
                    <p class="no-content">No friends yet. Use the Add Friends button to find people!</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($friends as $friend): ?>
                            <li class="friend-item">
                                <span class="username"><?= htmlspecialchars($friend['username']) ?></span>
                                <a href="profile.php?id=<?= $friend['id'] ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-section lists-section">
            <div class="section-header">
                <h2>Movie Lists</h2>
                <a href="../movies/movie_list.php" class="btn btn-primary">Create New List</a>
            </div>
            <div class="lists-actions">
                <a href="movie_list_profile.php" class="btn btn-outline-primary">View All Lists</a>
            </div>
        </div>

        <div class="profile-section reviews-section">
            <div class="section-header">
                <h2>Reviews</h2>
                <a href="reviews.php" class="btn btn-primary">View All Reviews</a>
            </div>
            <div class="reviews-preview">
                <?php if (empty($reviews)): ?>
                    <p class="no-content">No reviews yet. Start watching and reviewing movies!</p>
                <?php else: ?>
                    <div class="recent-reviews">
                        <?php foreach ($reviews as $review): 
                            $movie_id = $review['movie_id'];
                            $movie = getTMDBData("/movie/$movie_id", ['language' => 'en-US']);
                            
                            if ($movie && !isset($movie['status_code'])):
                        ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <a href="../movies/movie.php?id=<?= $movie_id ?>" class="movie-title">
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
                        endforeach; 
                        ?>
                    </div>
                <?php endif; ?>
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
    padding: 20px;
    background: linear-gradient(to right, #1a1a1a, #2d2d2d);
    color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-header h1 {
    margin: 0 0 15px 0;
    font-size: 2rem;
}

.profile-info p {
    margin: 5px 0;
    opacity: 0.9;
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
    background: white;
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
    font-size: 1.5rem;
}

.favorites-section, .watch-later-section {
    grid-column: 1 / -1;
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
    color: white;
}

.favorites-section h2, .watch-later-section h2 {
    color: white;
}

.favorites-grid, .watch-later-grid {
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

.friends-section {
    background: white;
}

.friend-requests {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.friend-requests h3 {
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    color: #333;
}

.requests-list, .friends-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.request-item, .friend-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.request-item:last-child, .friend-item:last-child {
    border-bottom: none;
}

.request-actions {
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-outline-primary {
    background-color: transparent;
    border-color: #007bff;
    color: #007bff;
}

.btn:hover {
    opacity: 0.9;
}

.lists-section {
    background: white;
}

.lists-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.reviews-section {
    grid-column: 1 / -1;
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
    color: white;
}

.reviews-section h2 {
    color: white;
}

.reviews-preview {
    margin-top: 20px;
}

.recent-reviews {
    display: grid;
    gap: 20px;
}

.review-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 15px;
    transition: transform 0.2s;
}

.review-item:hover {
    transform: translateY(-2px);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.movie-title {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.movie-title:hover {
    text-decoration: underline;
}

.rating {
    display: flex;
}

.star {
    color: #aaa;
    font-size: 18px;
}

.star.filled {
    color: #ffc107;
}

.review-body {
    line-height: 1.5;
    margin-bottom: 10px;
}

.review-date {
    color: #9e9e9e;
    font-size: 0.9rem;
    text-align: right;
}
</style>

<script src="/atlas/assets/js/friends.js"></script>







