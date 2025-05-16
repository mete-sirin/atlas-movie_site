<?php
session_start();
require_once '../templates/header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$uid = $_SESSION['uid'];
$apiKey = '848df3823eaece087b9bd5baf5cb2805';


$stmt = $pdo->prepare("
    SELECT w.movie_id, w.added_at 
    FROM watch_later w 
    WHERE w.user_id = ? 
    ORDER BY w.added_at DESC
");
$stmt->execute([$uid]);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="watchlist-container">
    <div class="watchlist-header">
        <h1>My Watch Later List</h1>
        <p class="movie-count"><?= count($movies) ?> movies in your list</p>
    </div>

    <?php if (empty($movies)): ?>
        <div class="empty-list">
            <p>Your watch later list is empty.</p>
            <a href="../index.php" class="btn btn-primary">Browse Movies</a>
        </div>
    <?php else: ?>
        <div class="movie-grid">
            <?php foreach ($movies as $movie):
                $url = "https://api.themoviedb.org/3/movie/{$movie['movie_id']}?api_key=$apiKey&language=en-US";
                $response = @file_get_contents($url);
                $movieData = json_decode($response, true);
                
                if ($movieData && !isset($movieData['status_code'])):
            ?>
                <div class="movie-card" data-movie-id="<?= $movie['movie_id'] ?>">
                    <div class="movie-poster">
                        <img src="<?= $movieData['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $movieData['poster_path'] : '../assets/img/placeholder.jpg' ?>" 
                             alt="<?= htmlspecialchars($movieData['title']) ?>">
                        <div class="movie-actions">
                            <button class="remove-btn" onclick="removeFromWatchLater(<?= $movie['movie_id'] ?>)">
                                <span class="remove-icon">×</span>
                            </button>
                        </div>
                        <div class="movie-info">
                            <h3><?= htmlspecialchars($movieData['title']) ?></h3>
                            <p class="added-date">Added <?= date('M j, Y', strtotime($movie['added_at'])) ?></p>
                            <div class="rating">
                                <span class="star">★</span>
                                <span class="rating-value"><?= number_format($movieData['vote_average'], 1) ?></span>
                            </div>
                            <a href="movie.php?id=<?= $movie['movie_id'] ?>" class="view-btn">View Details</a>
                        </div>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.watchlist-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.watchlist-header {
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(to right, #1a1a1a, #2d2d2d);
    color: white;
    border-radius: 8px;
}

.watchlist-header h1 {
    margin: 0;
    font-size: 2rem;
}

.movie-count {
    margin: 10px 0 0;
    opacity: 0.8;
}

.movie-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.movie-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.movie-card:hover {
    transform: translateY(-5px);
}

.movie-poster {
    position: relative;
    aspect-ratio: 2/3;
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.movie-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.2s;
}

.movie-card:hover .movie-actions {
    opacity: 1;
}

.remove-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #e74c3c;
    transition: background 0.2s;
}

.remove-btn:hover {
    background: white;
}

.movie-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
}

.movie-info h3 {
    margin: 0;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.added-date {
    font-size: 0.8rem;
    opacity: 0.8;
    margin: 5px 0;
}

.rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 5px 0;
}

.star {
    color: #ffd700;
}

.view-btn {
    display: inline-block;
    padding: 5px 10px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.8rem;
    transition: background 0.2s;
}

.view-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.empty-list {
    text-align: center;
    padding: 40px;
    background: #f5f5f5;
    border-radius: 8px;
}

.empty-list p {
    margin: 0 0 20px;
    color: #666;
}

.btn-primary {
    display: inline-block;
    padding: 10px 20px;
    background: #1a1a1a;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #333;
}
</style>

<script>
async function removeFromWatchLater(movieId) {
    if (!confirm('Remove this movie from your watch later list?')) return;
    
    try {
        const response = await fetch('handle_watch_later.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                movie_id: movieId,
                action: 'remove'
            })
        });

        const data = await response.json();
        
        if (data.success) {
            const movieCard = document.querySelector(`.movie-card[data-movie-id="${movieId}"]`);
            movieCard.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                movieCard.remove();
                updateMovieCount();
            }, 300);
        } else {
            alert(data.message || 'Failed to remove movie');
        }
    } catch (error) {
        console.error('Error removing movie:', error);
        alert('Failed to remove movie');
    }
}

function updateMovieCount() {
    const count = document.querySelectorAll('.movie-card').length;
    const countElement = document.querySelector('.movie-count');
    countElement.textContent = `${count} movies in your list`;
    
    if (count === 0) {
        const container = document.querySelector('.watchlist-container');
        container.innerHTML = `
            <div class="watchlist-header">
                <h1>My Watch Later List</h1>
                <p class="movie-count">0 movies in your list</p>
            </div>
            <div class="empty-list">
                <p>Your watch later list is empty.</p>
                <a href="../index.php" class="btn btn-primary">Browse Movies</a>
            </div>
        `;
    }
}
</script>

<?php require_once '../templates/footer.php'; ?> 