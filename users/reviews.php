<?php
require_once '../includes/db.php';
session_start();


if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['uid'];
$profile_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : $user_id;

require_once '../templates/header.php';


$st = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$st->execute([$profile_id]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>User not found.</p>";
    require_once '../templates/footer.php';
    exit;
}

// Get all reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.movie_id, r.rating, r.review, r.created_at
    FROM reviews r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$profile_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

$apiKey = '848df3823eaece087b9bd5baf5cb2805';
?>

<div class="reviews-container">
    <div class="reviews-header">
        <h1><?= htmlspecialchars($user['username']) ?>'s Movie Reviews</h1>
        <a href="profile.php?id=<?= $profile_id ?>" class="back-link">Back to Profile</a>
    </div>
    
    <?php if (empty($reviews)): ?>
        <div class="no-reviews">
            <p>No reviews yet.</p>
            <?php if ($profile_id == $user_id): ?>
                <p>Watch movies and add your reviews to share your thoughts!</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="reviews-list">
            <?php 
            foreach ($reviews as $review): 
                $movie_id = $review['movie_id'];
                $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey&language=en-US";
                $response = @file_get_contents($url);
                $movie = json_decode($response, true);
                
                if ($movie && !isset($movie['status_code'])):
            ?>
                <div class="review-card">
                    <div class="review-movie">
                        <div class="movie-poster">
                            <a href="../movies/movie.php?id=<?= $movie_id ?>">
                                <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w200' . $movie['poster_path'] : '/atlas/assets/img/placeholder.jpg' ?>" 
                                     alt="<?= htmlspecialchars($movie['title']) ?>">
                            </a>
                        </div>
                        <div class="movie-details">
                            <h3><a href="../movies/movie.php?id=<?= $movie_id ?>"><?= htmlspecialchars($movie['title']) ?></a></h3>
                            <p class="movie-info"><?= substr($movie['release_date'], 0, 4) ?> • 
                               <?= implode(', ', array_map(fn($g) => $g['name'], array_slice($movie['genres'], 0, 3))) ?></p>
                        </div>
                    </div>
                    <div class="review-content">
                        <div class="review-header">
                            <div class="review-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="review-date"><?= date('F j, Y', strtotime($review['created_at'])) ?></div>
                        </div>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($review['review'])) ?>
                        </div>
                        <?php if ($profile_id == $user_id): ?>
                            <div class="review-actions">
                                <button class="delete-review" data-movie-id="<?= $movie_id ?>">Delete Review</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    <?php endif; ?>
</div>

<style>
.reviews-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.reviews-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.back-link {
    display: inline-block;
    padding: 8px 15px;
    background-color: #f8f9fa;
    color: #333;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.back-link:hover {
    background-color: #e9ecef;
}

.no-reviews {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.review-card {
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.review-movie {
    display: flex;
    background-color: #f8f9fa;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.movie-poster {
    flex-shrink: 0;
    margin-right: 15px;
}

.movie-poster img {
    width: 80px;
    border-radius: 4px;
}

.movie-details {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.movie-details h3 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.movie-details a {
    color: #333;
    text-decoration: none;
}

.movie-details a:hover {
    text-decoration: underline;
}

.movie-info {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
}

.review-content {
    padding: 15px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.review-rating {
    display: flex;
}

.star {
    color: #aaa;
    font-size: 18px;
}

.star.filled {
    color: #ffc107;
}

.review-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.review-text {
    line-height: 1.5;
    margin-bottom: 15px;
}

.review-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
}

.delete-review {
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
}

.delete-review:hover {
    background-color: #dc3545;
    color: white;
}

@media (max-width: 576px) {
    .review-movie {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .movie-poster {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .movie-poster img {
        width: 120px;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .review-date {
        margin-top: 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  
    const deleteButtons = document.querySelectorAll('.delete-review');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const movieId = this.dataset.movieId;
            const reviewCard = this.closest('.review-card');
            
            if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                fetch('../movies/movie_reviews.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&movie_id=${movieId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        reviewCard.remove();
                        
                        
                        const remainingCards = document.querySelectorAll('.review-card');
                        if (remainingCards.length === 0) {
                            const list = document.querySelector('.reviews-list');
                            if (list) {
                                const container = document.querySelector('.reviews-container');
                                list.remove();
                                
                                const noReviews = document.createElement('div');
                                noReviews.className = 'no-reviews';
                                noReviews.innerHTML = `
                                    <p>No reviews yet.</p>
                                    <p>Watch movies and add your reviews to share your thoughts!</p>
                                `;
                                container.appendChild(noReviews);
                            }
                        }
                    } else {
                        alert('Failed to delete review: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    console.error('Error deleting review:', err);
                    alert('An error occurred while trying to delete the review');
                });
            }
        });
    });
});
</script>

<?php require_once '../templates/footer.php'; ?> 