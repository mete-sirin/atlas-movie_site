<?php
require_once 'templates/header.php'; 
$query = urlencode($_GET['query']);
$apiKey = '848df3823eaece087b9bd5baf5cb2805';
$url = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=$query";

$response = file_get_contents($url);
$data = json_decode($response, true);
$results = $data['results'];
?>

<div class="container">
    <div class="search-results-section">
        <div class="search-bar-container">
            <form action="search.php" method="GET" class="search-form">
                <div class="search-input-group">
                    <input 
                        type="text" 
                        name="query" 
                        value="<?= htmlspecialchars($_GET['query']) ?>" 
                        placeholder="Search movies..." 
                        class="search-input"
                        required
                    >
                    <button type="submit" class="search-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Search
                    </button>
                </div>
            </form>
        </div>

        <div class="search-header">
  <h1>Search Results for "<?php echo htmlspecialchars($_GET['query']); ?>"</h1>
            <p class="results-count"><?= count($results) ?> movies found</p>
        </div>

  <?php if (!empty($results)): ?>
            <div class="movies-grid">
      <?php foreach ($results as $movie): ?>
                    <div class="movie-card">
                        <a href="movies/movie.php?id=<?= $movie['id'] ?>" class="movie-link">
                            <div class="movie-poster">
                                <img 
                                    src="<?= $movie['poster_path'] 
                                        ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] 
                                        : 'assets/img/placeholder.jpg' ?>" 
                                    alt="<?= htmlspecialchars($movie['title']) ?>"
                                    loading="lazy"
                                >
                                <div class="movie-info-overlay">
                                    <h3><?= htmlspecialchars($movie['title']) ?></h3>
                                    <p class="movie-year"><?= substr($movie['release_date'], 0, 4) ?></p>
                                    <div class="movie-rating">
                                        <span class="star">★</span>
                                        <span class="rating-value"><?= number_format($movie['vote_average'], 1) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
      <?php endforeach; ?>
            </div>
  <?php else: ?>
            <div class="no-results">
                <p>No movies found matching your search.</p>
                <p>Try adjusting your search terms or browse our movie collection.</p>
                <a href="index.php" class="btn btn-primary">Browse Movies</a>
            </div>
  <?php endif; ?>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.search-results-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.search-bar-container {
    margin-bottom: 30px;
}

.search-form {
    width: 100%;
}

.search-input-group {
    display: flex;
    gap: 10px;
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
}

.search-input {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #eee;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.search-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-button:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
}

.search-button svg {
    width: 18px;
    height: 18px;
}

.search-header {
    margin-bottom: 30px;
    border-top: 1px solid #eee;
    padding-top: 30px;
}

.search-header h1 {
    color: var(--primary-color);
    font-size: 2rem;
    margin: 0 0 10px 0;
}

.results-count {
    color: #666;
    margin: 0;
}

.movies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

.movie-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.movie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.movie-link {
    text-decoration: none;
    color: inherit;
}

.movie-poster {
    position: relative;
    aspect-ratio: 2/3;
    overflow: hidden;
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.movie-card:hover .movie-poster img {
    transform: scale(1.05);
}

.movie-info-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.movie-card:hover .movie-info-overlay {
    opacity: 1;
}

.movie-info-overlay h3 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.movie-year {
    margin: 0 0 5px 0;
    font-size: 0.9rem;
    opacity: 0.8;
}

.movie-rating {
    display: flex;
    align-items: center;
    gap: 5px;
}

.star {
    color: #ffd700;
}

.rating-value {
    font-size: 0.9rem;
}

.no-results {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
}

.no-results p {
    margin: 0 0 15px 0;
    color: #666;
}

.no-results p:first-child {
    font-size: 1.2rem;
    color: #333;
    font-weight: 500;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .search-results-section {
        padding: 20px;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-button {
        width: 100%;
        justify-content: center;
    }
    
    .search-header h1 {
        font-size: 1.5rem;
    }
    
    .movies-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
}
</style>

<?php require_once 'templates/footer.php'; ?>
