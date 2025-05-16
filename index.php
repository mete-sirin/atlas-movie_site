<?php
session_start();
$login_flag=false;
require_once 'includes/db.php';
require_once 'templates/header.php'; 
$apiKey = '848df3823eaece087b9bd5baf5cb2805';
$url = "https://api.themoviedb.org/3/movie/popular?api_key=$apiKey&language=en-US&page=1";
$response = file_get_contents($url);
$data = json_decode($response, true);
?>

<div class="main-container">
    <div class="search-section">
        <form method="GET" action="search.php" class="search-form">
            <input type="text" name="query" placeholder="Search for a movie..." required>
            <button type="submit">Search</button>
        </form>

        <?php if (isset($_SESSION['uid'])): ?>
            <p class="welcome-message">Welcome back, <?= htmlspecialchars($_SESSION['user']); ?>!</p> 
        <?php else: ?>
            <div class="auth-links">
                <p>You are not logged in.</p>
                <a href="auth/login.php" class="btn btn-primary">Login</a>
                <p>Don't have an account? Create now!</p>
                <a href="auth/register.php" class="btn btn-secondary">Register</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="filters-section">
        <h2>Popular Movies</h2>
        <div class="filters-container">
            <div class="filter-group">
                <label for="genre-select">Genres:</label>
                <select id="genre-select" multiple>
                    <option value="" disabled>Loading genres...</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="year-from">Year:</label>
                <div class="year-range">
                    <input type="number" id="year-from" min="1900" max="2024" placeholder="From">
                    <span>to</span>
                    <input type="number" id="year-to" min="1900" max="2024" placeholder="To">
                </div>
            </div>

            <div class="filter-group">
                <label for="sort-by">Sort By:</label>
                <select id="sort-by">
                    <option value="popularity.desc">Popularity (High to Low)</option>
                    <option value="popularity.asc">Popularity (Low to High)</option>
                    <option value="vote_average.desc">Rating (High to Low)</option>
                    <option value="vote_average.asc">Rating (Low to High)</option>
                    <option value="release_date.desc">Release Date (Newest)</option>
                    <option value="release_date.asc">Release Date (Oldest)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="movie-list"></div>
</div>

<style>
.main-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.search-section {
    margin-bottom: 30px;
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.search-form input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.search-form button {
    padding: 10px 20px;
    background: #1a1a1a;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.search-form button:hover {
    background: #333;
}

.filters-section {
    margin-bottom: 30px;
}

.filters-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(to right, #1a1a1a, #2d2d2d);
    border-radius: 8px;
    color: white;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.filter-group select, .filter-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #444;
    border-radius: 4px;
    background: #333;
    color: white;
}

.filter-group select[multiple] {
    height: 100px;
}

.year-range {
    display: flex;
    gap: 10px;
    align-items: center;
}

.year-range input {
    width: 100px;
}

.year-range span {
    color: #999;
}

.movie-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.movie-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s;
    aspect-ratio: 2/3;
}

.movie-card:hover {
    transform: scale(1.05);
}

.movie-poster {
    position: relative;
    height: 100%;
}

.movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.movie-info-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 15px;
    background: linear-gradient(transparent, rgba(0,0,0,0.8));
    color: white;
    opacity: 0;
    transition: opacity 0.2s;
}

.movie-card:hover .movie-info-overlay {
    opacity: 1;
}

.movie-info-overlay h3 {
    margin: 0 0 5px 0;
    font-size: 1rem;
}

.movie-year {
    margin: 0;
    font-size: 0.9rem;
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

.loading-indicator {
    grid-column: 1 / -1;
    text-align: center;
    padding: 20px;
    color: #666;
}

.welcome-message {
    margin: 10px 0;
    font-size: 1.1rem;
    color: #333;
}

.auth-links {
    margin-top: 20px;
}

.auth-links p {
    margin: 5px 0;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    transition: background 0.2s;
}

.btn-primary {
    background: #1a1a1a;
    color: white;
}

.btn-secondary {
    background: #333;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}
</style>

<script src="/atlas/assets/js/movie_filters.js"></script>

<?php require_once 'templates/footer.php'; ?>
