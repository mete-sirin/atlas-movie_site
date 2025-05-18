<?php
session_start();
require_once '../templates/header.php';
require_once '../includes/db.php';
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    echo "<p>No movie ID provided.</p>";
    exit;
}

$movie_id = (int) $_GET['id'];


$movie = getTMDBData("/movie/$movie_id", [
    'language' => 'en-US',
    'append_to_response' => 'keywords,translations'
]);


$credits = getTMDBData("/movie/$movie_id/credits");


$videos = getTMDBData("/movie/$movie_id/videos");


$providers = getTMDBData("/movie/$movie_id/watch/providers");


$details = getTMDBData("/movie/$movie_id", [
    'language' => 'en-US',
    'append_to_response' => 'keywords,release_dates,alternative_titles'
]);


$translations = getTMDBData("/movie/$movie_id/translations");

$additional_descriptions = [];
if (isset($translations['translations'])) {
    foreach ($translations['translations'] as $translation) {
        if ($translation['iso_639_1'] === 'en' && 
            !empty($translation['data']['overview']) && 
            $translation['data']['overview'] !== $movie['overview'] && 
            strlen($translation['data']['overview']) > strlen($movie['overview'])) {
            $additional_descriptions[] = $translation['data']['overview'];
        }
    }
}

$extended_description = '';
if (!empty($additional_descriptions)) {
    usort($additional_descriptions, function($a, $b) {
        return strlen($b) - strlen($a);
    });
    $extended_description = $additional_descriptions[0];
}

$trailer = null;
if (isset($videos['results']) && !empty($videos['results'])) {
    foreach ($videos['results'] as $video) {
        if ($video['type'] === 'Trailer' && $video['site'] === 'YouTube') {
            $trailer = $video;
            break;
        }
    }
    if (!$trailer && $videos['results'][0]['site'] === 'YouTube') {
        $trailer = $videos['results'][0];
    }
}

if (!$movie || isset($movie['status_code'])) {
    echo "<p>Movie not found.</p>";
    exit;
}

$director = null;
$writer = null;
$producer = null;
if (isset($credits['crew'])) {
    foreach ($credits['crew'] as $crew) {
        if ($crew['job'] === 'Director' && !$director) {
            $director = $crew;
        }
        if (($crew['job'] === 'Screenplay' || $crew['job'] === 'Writer') && !$writer) {
            $writer = $crew;
        }
        if ($crew['job'] === 'Producer' && !$producer) {
            $producer = $crew;
        }
    }
}

$isFavorited = false;
if (isset($_SESSION['uid'])) {
    $uid = $_SESSION['uid'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$uid, $movie_id]);
    $isFavorited = (int)$stmt->fetchColumn() > 0;
}

$inWatchLater = false;
if (isset($_SESSION['uid'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM watch_later WHERE user_id = ? AND movie_id = ?");
    $stmt->execute([$uid, $movie_id]);
    $inWatchLater = (int)$stmt->fetchColumn() > 0;
}
?>

<div class="movie-detail">
    <div class="poster">
        <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : '/atlas/assets/img/placeholder.jpg' ?>" 
             alt="<?= htmlspecialchars($movie['title']) ?>">
        
        <?php if (isset($_SESSION['uid'])): ?>
            <div class="movie-actions">
                <button id="favorite-btn" class="<?= $isFavorited ? 'favorited' : '' ?>" data-movie-id="<?= $movie_id ?>">
                    <span class="heart-icon">❤</span>
                    <span class="btn-text"><?= $isFavorited ? 'Remove from Favorites' : 'Add to Favorites' ?></span>
                </button>
                
                <button id="watch-later-btn" class="<?= $inWatchLater ? 'in-watchlist' : '' ?>" data-movie-id="<?= $movie_id ?>">
                    <span class="clock-icon">⌚</span>
                    <span class="btn-text"><?= $inWatchLater ? 'Remove from Watch Later' : 'Add to Watch Later' ?></span>
                </button>
                
                <a href="../movies/movie_list.php" class="list-btn">
                    <span class="list-icon">+</span>
                    <span class="btn-text">Add to List</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="info">
        <div class="title-section">
            <h1><?= htmlspecialchars($movie['title']) ?></h1>
            <?php if ($movie['tagline']): ?>
                <h3 class="tagline"><?= htmlspecialchars($movie['tagline']) ?></h3>
            <?php endif; ?>
        </div>

        <div class="key-info">
            <p><strong>Release Date:</strong> <?= date('F j, Y', strtotime($movie['release_date'])) ?></p>
            <p><strong>Runtime:</strong> <?= $movie['runtime'] ?> minutes</p>
            <p><strong>Genres:</strong> <?= implode(', ', array_map(fn($g) => $g['name'], $movie['genres'])) ?></p>
            
            <?php if ($director): ?>
                <p><strong>Director:</strong> <?= htmlspecialchars($director['name']) ?></p>
            <?php endif; ?>
            
            <?php if ($writer): ?>
                <p><strong>Writer:</strong> <?= htmlspecialchars($writer['name']) ?></p>
            <?php endif; ?>
            
            <p><strong>Language(s):</strong>
                <?= implode(', ', array_map(fn($l) => $l['english_name'], $movie['spoken_languages'])) ?>
            </p>

            <div class="ratings">
                <div class="tmdb-rating">
                    <strong>TMDb Rating:</strong>
                    <span class="rating-value"><?= number_format($movie['vote_average'], 1) ?></span>
                    <span class="rating-count">(<?= number_format($movie['vote_count']) ?> votes)</span>
                </div>
            </div>
        </div>

        <div class="overview">
            <h3>Overview</h3>
            <p><?= $movie['overview'] ?></p>
            <?php if ($extended_description): ?>
            <details class="extended-description">
                <summary>Extended Description</summary>
                <p><?= htmlspecialchars($extended_description) ?></p>
            </details>
            <?php endif; ?>
        </div>

        <?php if (isset($details['keywords']['keywords']) && !empty($details['keywords']['keywords'])): ?>
        <details class="trivia-section">
            <summary>Movie Details & Trivia</summary>
            <div class="trivia-content">
                <?php if ($movie['budget'] > 0 || $movie['revenue'] > 0): ?>
                <div class="financial-info">
                    <?php if ($movie['budget'] > 0): ?>
                    <p><strong>Budget:</strong> $<?= number_format($movie['budget']) ?></p>
                    <?php endif; ?>
                    <?php if ($movie['revenue'] > 0): ?>
                    <p><strong>Box Office:</strong> $<?= number_format($movie['revenue']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($movie['production_companies'])): ?>
                <div class="production-info">
                    <p><strong>Production Companies:</strong> 
                        <?= implode(', ', array_map(fn($c) => $c['name'], $movie['production_companies'])) ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if (!empty($details['keywords']['keywords'])): ?>
                <div class="keywords-cloud">
                    <p><strong>Themes & Keywords:</strong></p>
                    <div class="keyword-tags">
                        <?php foreach ($details['keywords']['keywords'] as $keyword): ?>
                        <span class="keyword-tag"><?= htmlspecialchars($keyword['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($details['alternative_titles']['titles'])): ?>
                <div class="alternative-titles">
                    <p><strong>Alternative Titles:</strong></p>
                    <ul class="alt-titles-list">
                        <?php 
                        $alt_titles = array_slice($details['alternative_titles']['titles'], 0, 5);
                        foreach ($alt_titles as $title): 
                        ?>
                        <li><?= htmlspecialchars($title['title']) ?> (<?= strtoupper($title['iso_3166_1']) ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </details>
        <?php endif; ?>

        <?php if (isset($providers['results']['US'])): ?>
        <details class="streaming-services">
            <summary>Where to Watch</summary>
            <?php if (isset($providers['results']['US']['flatrate'])): ?>
            <div class="stream-section">
                <h4>Stream</h4>
                <div class="provider-list">
                    <?php foreach ($providers['results']['US']['flatrate'] as $provider): ?>
                        <div class="provider">
                            <img src="https://image.tmdb.org/t/p/original<?= $provider['logo_path'] ?>" 
                                 alt="<?= htmlspecialchars($provider['provider_name']) ?>"
                                 title="<?= htmlspecialchars($provider['provider_name']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($providers['results']['US']['rent'])): ?>
            <div class="stream-section">
                <h4>Rent</h4>
                <div class="provider-list">
                    <?php foreach ($providers['results']['US']['rent'] as $provider): ?>
                        <div class="provider">
                            <img src="https://image.tmdb.org/t/p/original<?= $provider['logo_path'] ?>" 
                                 alt="<?= htmlspecialchars($provider['provider_name']) ?>"
                                 title="<?= htmlspecialchars($provider['provider_name']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($providers['results']['US']['buy'])): ?>
            <div class="stream-section">
                <h4>Buy</h4>
                <div class="provider-list">
                    <?php foreach ($providers['results']['US']['buy'] as $provider): ?>
                        <div class="provider">
                            <img src="https://image.tmdb.org/t/p/original<?= $provider['logo_path'] ?>" 
                                 alt="<?= htmlspecialchars($provider['provider_name']) ?>"
                                 title="<?= htmlspecialchars($provider['provider_name']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </details>
        <?php endif; ?>

        <?php if ($movie['homepage']): ?>
            <p><a href="<?= $movie['homepage'] ?>" target="_blank" class="official-link">Official Website</a></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($trailer): ?>
<div class="trailer-section">
    <h2>Trailer</h2>
    <div style="max-width: 400px; margin: 0 auto;">
        <div style="position: relative; padding-top: 56.25%;">
        <iframe 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
                src="https://www.youtube.com/embed/<?= $trailer['key'] ?>?rel=0&modestbranding=1" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isset($credits['cast']) && !empty($credits['cast'])): ?>
<div class="cast-section">
    <h2>Cast</h2>
    <div class="cast-grid">
        <?php 
        $top_cast = array_slice($credits['cast'], 0, 8);
        foreach ($top_cast as $actor): 
        ?>
            <div class="cast-card">
                <div class="cast-image">
                    <?php if ($actor['profile_path']): ?>
                        <img src="https://image.tmdb.org/t/p/w185<?= $actor['profile_path'] ?>" 
                             alt="<?= htmlspecialchars($actor['name']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <span>No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="cast-info">
                    <h4><?= htmlspecialchars($actor['name']) ?></h4>
                    <p><?= htmlspecialchars($actor['character']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div id="movie-container" data-movie-id="<?= $movie_id ?>"></div>

<div class="reviews-section">
    <h2>Reviews</h2>
    <h3 id="average-rating">Atlas User Rating: <span class="loading">Loading...</span></h3>

    <div id="review-list" class="review-list" data-movie-id="<?= $movie_id ?>">
        <div class="loading-spinner">Loading reviews...</div>
    </div>

    <?php if (isset($_SESSION['uid'])): ?>
    <details class="review-form-container">
        <summary>Write a Review</summary>
          <form id="review-form" data-user-id="<?= $_SESSION['uid'] ?>">
            <div class="form-group">
                <label>Rating:</label>
                <div class="star-rating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-rating="<?= $i ?>">☆</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="selected-rating" value="" required>
            </div>
            
            <div class="form-group">
                <label for="review-comment">Your Review:</label>
                <textarea name="comment" id="review-comment" placeholder="Share your thoughts about this movie" required></textarea>
            </div>
            
            <button type="submit" class="submit-btn">Submit Review</button>
          </form>
    </details>
    <?php else: ?>
      <p class="login-prompt">Please <a href="../auth/login.php">log in</a> to write a review.</p>
    <?php endif; ?>
</div>

<style>
body {
    padding: 0 15px;
    max-width: 1400px;
    margin: 0 auto;
}

.movie-detail {
    display: flex;
    margin-bottom: 30px;
    gap: 20px;
    background: linear-gradient(to bottom, #1a1a1a, #2d2d2d);
    padding: 20px;
    color: white;
    border-radius: 15px;
}

@media (max-width: 768px) {
    .movie-detail {
        flex-direction: column;
    }
}

.poster {
    position: relative;
    flex-shrink: 0;
}

.poster img {
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    max-width: 250px;
    width: 100%;
}

.title-section {
    margin-bottom: 15px;
}

.title-section h1 {
    margin: 0;
    font-size: 2.2rem;
    color: #ffffff;
}

.tagline {
    color: #9e9e9e;
    font-style: italic;
    margin: 8px 0;
    font-weight: normal;
    font-size: 1.1rem;
}

.key-info {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.key-info p {
    margin: 8px 0;
}

.ratings {
    margin: 15px 0;
    padding: 12px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.tmdb-rating {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating-value {
    font-size: 1.2em;
    color: #ffd700;
    font-weight: bold;
}

.rating-count {
    color: #9e9e9e;
    font-size: 0.9em;
}

.overview {
    margin: 15px 0;
}

.overview h3 {
    color: #ffffff;
    margin-bottom: 8px;
    font-size: 1.3rem;
}

.overview p {
    line-height: 1.5;
    color: #e0e0e0;
}

.trailer-section {
    margin: 30px 0;
    padding: 0 15px;
}

.trailer-section h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.cast-section {
    margin: 30px 0;
    padding: 0 15px;
}

.cast-section h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.cast-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 15px;
    margin-top: 15px;
    padding: 0 5px;
}

.cast-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.cast-card:hover {
    transform: translateY(-5px);
}

.cast-image {
    position: relative;
    padding-top: 150%;
    background: #f0f0f0;
}

.cast-image img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e0e0;
    color: #666;
}

.cast-info {
    padding: 10px;
    text-align: center;
}

.cast-info h4 {
    margin: 0;
    font-size: 0.9em;
    color: #333;
}

.cast-info p {
    margin: 5px 0 0;
    font-size: 0.8em;
    color: #666;
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

details {
    margin: 15px 0;
    border-radius: 8px;
    overflow: hidden;
}

details summary {
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.1);
    cursor: pointer;
    user-select: none;
    position: relative;
    list-style: none;
    font-weight: 600;
    border-radius: 8px;
}

details summary::-webkit-details-marker {
    display: none;
}

details summary::after {
    content: "+";
    position: absolute;
    right: 15px;
    transition: transform 0.3s;
}

details[open] summary::after {
    transform: rotate(45deg);
}

details[open] summary {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    margin-bottom: 0;
}

.extended-description {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.extended-description > p {
    padding: 15px;
    margin: 0;
}

.trivia-section {
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.trivia-content {
    padding: 15px;
    display: grid;
    gap: 15px;
}

.streaming-services {
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.streaming-services .stream-section {
    padding: 15px;
    margin-bottom: 10px;
}

.streaming-services h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1rem;
    color: #e0e0e0;
}

.provider-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.provider {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.reviews-section {
    margin: 30px 0;
    padding: 0 15px;
}

.reviews-section h2 {
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.reviews-section h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
}

.review-list {
    margin-bottom: 20px;
}

.review-form-container {
    margin-top: 20px;
    background: #f5f5f5;
    border-radius: 8px;
}

.review-form-container form {
    padding: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.star-rating {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
}

.star-rating .star {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ccc;
    transition: color 0.2s;
}

.star-rating .star.selected,
.star-rating .star:hover {
    color: #ffc107;
}

textarea#review-comment {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-height: 100px;
    resize: vertical;
}

.submit-btn {
    background: var(--primary-color, #2c3e50);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

.submit-btn:hover {
    background: var(--hover-color, #34495e);
}

.login-prompt {
    text-align: center;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 8px;
}

.login-prompt a {
    color: var(--secondary-color, #3498db);
    text-decoration: none;
    font-weight: 500;
}

.login-prompt a:hover {
    text-decoration: underline;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const favoriteBtn = document.getElementById('favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const movieId = this.dataset.movieId;
            const isFavorited = this.classList.contains('favorited');
            const action = isFavorited ? 'remove' : 'add';
            
            fetch('../users/handle_favorites.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=${action}&movie_id=${movieId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('favorited');
                    this.querySelector('.btn-text').textContent = isFavorited ? 'Add to Favorites' : 'Remove from Favorites';
                    this.querySelector('.heart-icon').style.color = isFavorited ? '' : '#ff4444';
                } else {
                    alert(data.message || 'Failed to update favorite status');
                }
            })
            .catch(err => {
                console.error('Error toggling favorite:', err);
                alert('An error occurred while updating favorite status');
            });
        });
    }
});
</script>

<script src="/atlas/assets/js/notifications.js"></script>
<script src="/atlas/assets/js/movie_ratings.js"></script>
<script src="../assets/js/watch_later.js"></script>

<?php require_once '../templates/footer.php'; ?>
