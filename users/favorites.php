<?php
session_start();
require_once '../templates/header.php';
require_once '../includes/db.php';


if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['uid'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    
    if (!$movie_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid movie ID']);
        exit;
    }
    
    
    if ($action === 'add') {
        try {
           
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND movie_id = ?");
            $check_stmt->execute([$user_id, $movie_id]);
            $exists = (int)$check_stmt->fetchColumn() > 0;
            
            if ($exists) {
                echo json_encode(['success' => true, 'message' => 'Movie already in favorites']);
                exit;
            }
            
            
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, movie_id, created_at) VALUES (?, ?, NOW())");
            $result = $stmt->execute([$user_id, $movie_id]);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Added to favorites' : 'Failed to add to favorites']);
        } catch (PDOException $e) {
            error_log("Favorites error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
        exit;
    }
    
    elseif ($action === 'remove') {
        try {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND movie_id = ?");
            $result = $stmt->execute([$user_id, $movie_id]);
            
            echo json_encode(['success' => $result, 'message' => $result ? 'Removed from favorites' : 'Failed to remove from favorites']);
        } catch (PDOException $e) {
            error_log("Favorites error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
        exit;
    }
    exit;
}


$profile_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : $user_id;


$st = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$st->execute([$profile_id]);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>User not found.</p>";
    require_once '../templates/footer.php';
    exit;
}


$favorites_stmt = $pdo->prepare("
    SELECT movie_id, created_at 
    FROM favorites 
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$favorites_stmt->execute([$profile_id]);
$favorites = $favorites_stmt->fetchAll(PDO::FETCH_ASSOC);

$apiKey = '848df3823eaece087b9bd5baf5cb2805';
?>

<div class="favorites-container">
    <div class="favorites-header">
        <h1><?= htmlspecialchars($user['username']) ?>'s Favorite Movies</h1>
        <a href="profile.php?id=<?= $profile_id ?>" class="back-link">Back to Profile</a>
    </div>
    
    <?php if (empty($favorites)): ?>
        <div class="no-favorites">
            <p>No favorite movies yet.</p>
            <?php if ($profile_id == $user_id): ?>
                <p>Browse movies and click the heart icon to add them to your favorites!</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="favorites-grid">
            <?php 
            foreach ($favorites as $favorite): 
                $movie_id = $favorite['movie_id'];
                $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey&language=en-US";
                $response = @file_get_contents($url);
                $movie = json_decode($response, true);
                
                if ($movie && !isset($movie['status_code'])):
            ?>
                <div class="movie-card">
                    <div class="poster-container">
                        <a href="../movies/movie.php?id=<?= $movie_id ?>">
                            <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w300' . $movie['poster_path'] : '../assets/img/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($movie['title']) ?>">
                            <div class="movie-info">
                                <h3><?= htmlspecialchars($movie['title']) ?></h3>
                                <p class="release-date"><?= substr($movie['release_date'], 0, 4) ?></p>
                                <div class="rating"><?= number_format($movie['vote_average'], 1) ?> / 10</div>
                            </div>
                        </a>
                        <?php if ($profile_id == $user_id): ?>
                            <button class="remove-favorite" data-movie-id="<?= $movie_id ?>">❌</button>
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
.favorites-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.favorites-header {
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

.no-favorites {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.movie-card {
    transition: transform 0.2s;
}

.movie-card:hover {
    transform: translateY(-5px);
}

.poster-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.poster-container img {
    width: 100%;
    display: block;
}

.movie-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 15px;
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
}

.poster-container:hover .movie-info {
    opacity: 1;
}

.movie-info h3 {
    margin: 0 0 5px 0;
    font-size: 1rem;
}

.release-date, .rating {
    font-size: 0.9rem;
    margin-top: 5px;
}

.rating {
    font-weight: bold;
    color: #ffc107;
}

.remove-favorite {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: rgba(0,0,0,0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 14px;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s;
}

.poster-container:hover .remove-favorite {
    opacity: 1;
}

.remove-favorite:hover {
    background-color: rgba(220, 53, 69, 0.9);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const removeButtons = document.querySelectorAll('.remove-favorite');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const movieId = this.dataset.movieId;
            const movieCard = this.closest('.movie-card');
            
            if (confirm('Are you sure you want to remove this movie from your favorites?')) {
                fetch('favorites.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&movie_id=${movieId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        movieCard.remove();
                        
                        
                        const remainingCards = document.querySelectorAll('.movie-card');
                        if (remainingCards.length === 0) {
                            const grid = document.querySelector('.favorites-grid');
                            if (grid) {
                                const container = document.querySelector('.favorites-container');
                                grid.remove();
                                
                                const noFavorites = document.createElement('div');
                                noFavorites.className = 'no-favorites';
                                noFavorites.innerHTML = `
                                    <p>No favorite movies yet.</p>
                                    <p>Browse movies and click the heart icon to add them to your favorites!</p>
                                `;
                                container.appendChild(noFavorites);
                            }
                        }
                    } else {
                        alert('Failed to remove from favorites: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error removing favorite:', err);
                    alert('An error occurred while trying to remove the movie from favorites');
                });
            }
        });
    });
});
</script>

<?php require_once '../templates/footer.php'; ?> 