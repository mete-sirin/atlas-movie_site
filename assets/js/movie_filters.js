const apiKey = '848df3823eaece087b9bd5baf5cb2805';
let currentPage = 1;
let isLoading = false;
let totalPages = 0;
let moviesPerPage = 50;
let activeFilters = {
    genres: [],
    yearFrom: '',
    yearTo: '',
    sortBy: 'popularity.desc'
};


document.addEventListener('DOMContentLoaded', async () => {
    await loadGenres();
    setupEventListeners();
    
    loadPopularMovies();
});

async function loadGenres() {
    try {
        const response = await fetch(`https://api.themoviedb.org/3/genre/movie/list?api_key=${apiKey}&language=en-US`);
        const data = await response.json();
        const genreSelect = document.getElementById('genre-select');
        
        data.genres.forEach(genre => {
            const option = document.createElement('option');
            option.value = genre.id;
            option.textContent = genre.name;
            genreSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading genres:', error);
    }
}

function setupEventListeners() {
    
    document.getElementById('genre-select').addEventListener('change', function(e) {
        const selectedOptions = Array.from(this.selectedOptions).map(option => option.value);
        activeFilters.genres = selectedOptions;
        resetAndReload();
    });

    
    document.getElementById('year-from').addEventListener('change', function(e) {
        activeFilters.yearFrom = this.value;
        resetAndReload();
    });

    document.getElementById('year-to').addEventListener('change', function(e) {
        activeFilters.yearTo = this.value;
        resetAndReload();
    });

    
    document.getElementById('sort-by').addEventListener('change', function(e) {
        activeFilters.sortBy = this.value;
        resetAndReload();
    });
}

function hasActiveFilters() {
    return activeFilters.genres.length > 0 || 
           activeFilters.yearFrom !== '' || 
           activeFilters.yearTo !== '' || 
           activeFilters.sortBy !== 'popularity.desc';
}

async function loadPopularMovies() {
    if (isLoading) return;
    isLoading = true;
    
    try {
        const movieList = document.querySelector('.movie-list');
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.textContent = 'Loading popular movies...';
        movieList.appendChild(loadingIndicator);

        const url = `https://api.themoviedb.org/3/movie/popular?api_key=${apiKey}&language=en-US&page=${currentPage}`;
        const response = await fetch(url);
        const data = await response.json();
        
        loadingIndicator.remove();
        
        if (data.results && data.results.length > 0) {
            totalPages = data.total_pages;
            movieList.innerHTML = ''; 
            
            data.results.forEach(movie => {
                const movieCard = createMovieCard(movie);
                movieList.appendChild(movieCard);
            });

           
            createPagination(data.total_pages);
        }
    } catch (error) {
        console.error('Error loading popular movies:', error);
    } finally {
        isLoading = false;
    }
}

async function loadFilteredMovies() {
    if (isLoading) return;
    isLoading = true;
    
    try {
        const movieList = document.querySelector('.movie-list');
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.textContent = 'Loading movies...';
        movieList.appendChild(loadingIndicator);

        let url = `https://api.themoviedb.org/3/discover/movie?api_key=${apiKey}&language=en-US&page=${currentPage}`;
        
        
        if (activeFilters.genres.length > 0) {
            url += `&with_genres=${activeFilters.genres.join(',')}`;
        }
        if (activeFilters.yearFrom) {
            url += `&primary_release_date.gte=${activeFilters.yearFrom}-01-01`;
        }
        if (activeFilters.yearTo) {
            url += `&primary_release_date.lte=${activeFilters.yearTo}-12-31`;
        }
        url += `&sort_by=${activeFilters.sortBy}`;

        const response = await fetch(url);
        const data = await response.json();
        
        loadingIndicator.remove();
        
        if (data.results && data.results.length > 0) {
            totalPages = data.total_pages;
            movieList.innerHTML = ''; 
            
            data.results.forEach(movie => {
                const movieCard = createMovieCard(movie);
                movieList.appendChild(movieCard);
            });

            createPagination(data.total_pages);
        }
    } catch (error) {
        console.error('Error loading movies:', error);
    } finally {
        isLoading = false;
    }
}

function createMovieCard(movie) {
    const card = document.createElement('div');
    card.className = 'movie-card';
    
    const posterPath = movie.poster_path 
        ? `https://image.tmdb.org/t/p/w500${movie.poster_path}`
        : '/atlas/assets/img/placeholder.jpg';
    
    card.innerHTML = `
        <a href="movies/movie.php?id=${movie.id}" class="movie-link">
            <div class="movie-poster">
                <img src="${posterPath}" alt="${movie.title}" loading="lazy">
                <div class="movie-info-overlay">
                    <h3>${movie.title}</h3>
                    <p class="movie-year">${movie.release_date ? movie.release_date.substring(0, 4) : 'N/A'}</p>
                    <div class="movie-rating">
                        <span class="star">★</span>
                        <span class="rating-value">${movie.vote_average.toFixed(1)}</span>
                    </div>
                </div>
            </div>
        </a>
    `;
    
    return card;
}

function createPagination(totalPages) {
    
    totalPages = Math.min(totalPages, 500);
    
    const container = document.querySelector('.movie-list');
    const paginationDiv = document.createElement('div');
    paginationDiv.className = 'pagination';
    
   
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    
    if (startPage > 1) {
        paginationDiv.appendChild(createPageButton(1));
        if (startPage > 2) {
            paginationDiv.appendChild(createEllipsis());
        }
    }
    
    
    for (let i = startPage; i <= endPage; i++) {
        paginationDiv.appendChild(createPageButton(i));
    }
    
   
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationDiv.appendChild(createEllipsis());
        }
        paginationDiv.appendChild(createPageButton(totalPages));
    }
    
    container.appendChild(paginationDiv);
}

function createPageButton(pageNum) {
    const button = document.createElement('button');
    button.textContent = pageNum;
    button.className = pageNum === currentPage ? 'page-button active' : 'page-button';
    button.addEventListener('click', () => {
        if (pageNum !== currentPage) {
            currentPage = pageNum;
            if (hasActiveFilters()) {
                loadFilteredMovies();
            } else {
                loadPopularMovies();
            }
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    return button;
}

function createEllipsis() {
    const span = document.createElement('span');
    span.className = 'page-ellipsis';
    span.textContent = '...';
    return span;
}

function resetAndReload() {
    currentPage = 1;
    if (hasActiveFilters()) {
        loadFilteredMovies();
    } else {
        loadPopularMovies();
    }
} 