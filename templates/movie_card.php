<div class="movie-card">
  <img src="<?= $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w200' . $movie['poster_path'] : '/atlas/assets/img/placeholder.jpg' ?>" 
       alt="<?= htmlspecialchars($movie['title']) ?>">
  <a href="/atlas/movies/movie.php?id=<?= $movie['id'] ?>">
  <h3><?= htmlspecialchars($movie['title']) ?></h3>
  </a>
  <p><?= substr($movie['overview'], 0, 100) ?>...</p>
</div>
