document.getElementById('openAddModal').addEventListener('click', () => {
  document.getElementById('addMovieModal').style.display = 'block';
  document.getElementById('modalOverlay').style.display = 'block';
});

function closeModal() {
  document.getElementById('addMovieModal').style.display = 'none';
  document.getElementById('modalOverlay').style.display = 'none';
  document.getElementById('movieSearchInput').value = '';
  resetSearchResults();
}

function resetSearchResults() {
  const container = document.getElementById('searchResults');
  container.innerHTML = '<div class="empty-results">Type to search for movies</div>';
}

document.getElementById('modalOverlay').addEventListener('click', closeModal);

let currentMovieElement = null;
let pendingDeleteMovieId = null;

function showConfirmDialog(movieId, movieTitle, buttonElement) {
  currentMovieElement = buttonElement;
  pendingDeleteMovieId = movieId;
  
  document.getElementById('confirmMessage').textContent = 
    `Are you sure you want to remove "${movieTitle}" from your list?`;
  
  document.getElementById('confirmDialog').style.display = 'block';
  document.getElementById('modalOverlay').style.display = 'block';
  
  const dialog = document.getElementById('confirmDialog');
  dialog.classList.add('shake-animation');
  
  setTimeout(() => {
    dialog.classList.remove('shake-animation');
  }, 500);
}

function closeConfirmDialog() {
  document.getElementById('confirmDialog').style.display = 'none';
  document.getElementById('modalOverlay').style.display = 'none';
  currentMovieElement = null;
  pendingDeleteMovieId = null;
}

document.getElementById('cancelDelete').addEventListener('click', closeConfirmDialog);

document.getElementById('confirmDelete').addEventListener('click', async () => {
  if (!pendingDeleteMovieId) return;
  
  const listId = new URLSearchParams(window.location.search).get('id');
  
  try {
    const res = await fetch('/atlas/movies/edit_movie_lists.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `action=delete_movie&list_id=${listId}&movie_id=${pendingDeleteMovieId}`
    });

    if (!res.ok) {
      throw new Error(`Server returned ${res.status}: ${res.statusText}`);
    }
    
    const contentType = res.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
    }
    
    const result = await res.json();
    if (result.success) {
      if (currentMovieElement) {
        currentMovieElement.closest('.movie-card').remove();
        notifications.show('Movie removed from list', 'success');
      }
    } else {
      if (result.message === 'You must be logged in to edit lists') {
        window.location.href = '/atlas/auth/login.php';
      } else if (result.message === 'You do not have permission to edit this list') {
        notifications.show('You do not have permission to edit this list', 'error');
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        notifications.show(result.message || 'Failed to remove movie from list', 'error');
      }
    }
  } catch (err) {
    console.error('Delete request failed:', err);
    notifications.show('Server error occurred', 'error');
  } finally {
    closeConfirmDialog();
  }
});


const searchCache = new Map();
let currentSearchController = null;

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

const handleSearch = debounce(async function(query) {
    const container = document.getElementById('searchResults');
    const searchInput = document.getElementById('movieSearchInput');
    
    if (!query) {
        resetSearchResults();
        return;
    }

  
    if (searchCache.has(query)) {
        renderSearchResults(searchCache.get(query));
        return;
    }

  
    if (currentSearchController) {
        currentSearchController.abort();
    }

   
    currentSearchController = new AbortController();
    const signal = currentSearchController.signal;

  
    container.innerHTML = `
        <div class="loading">
            <div class="loading-spinner"></div>
            <div>Searching...</div>
        </div>
    `;

    try {
        const apiKey = '848df3823eaece087b9bd5baf5cb2805';
        const res = await fetch(
            `https://api.themoviedb.org/3/search/movie?api_key=${apiKey}&query=${encodeURIComponent(query)}`,
            { signal }
        );
        
        if (!res.ok) {
            throw new Error(`Server returned ${res.status}`);
        }

        const data = await res.json();
        
        
        searchCache.set(query, data.results);
        
      
        if (searchInput.value.trim() === query) {
            renderSearchResults(data.results);
        }
    } catch (err) {
        if (err.name === 'AbortError') {
            return; 
        }
        console.error('Search error:', err);
        container.innerHTML = `
            <div class="error">
                <div>Error searching for movies</div>
                <div class="error-details">${err.message}</div>
            </div>
        `;
    }
}, 500); 

function renderSearchResults(results) {
    const container = document.getElementById('searchResults');
    
    if (!results || results.length === 0) {
        container.innerHTML = '<div class="no-results">No movies found</div>';
        return;
    }

   
    const fragment = document.createDocumentFragment();
    
    results.slice(0, 5).forEach(movie => {
        const movieCard = document.createElement('div');
        movieCard.className = 'search-movie-item';
        
        const imgSrc = movie.poster_path 
            ? `https://image.tmdb.org/t/p/w92${movie.poster_path}` 
            : '../assets/images/no-poster.png';
        
        
        movieCard.innerHTML = `
            <img src="${imgSrc}" 
                 alt="${movie.title}" 
                 class="search-movie-poster"
                 loading="lazy">
            <div class="search-movie-info">
                <div class="search-movie-title">${movie.title}</div>
                <div class="search-movie-year">
                    ${movie.release_date ? new Date(movie.release_date).getFullYear() : 'Unknown'}
                </div>
            </div>
        `;
        
        movieCard.onclick = async () => {
            const listId = new URLSearchParams(window.location.search).get('id');
            
            try {
                movieCard.classList.add('loading');
                const res = await fetch('/atlas/movies/edit_movie_lists.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_movie&list_id=${listId}&movie_id=${movie.id}`
                });
                
                if (!res.ok) {
                    throw new Error(`Server returned ${res.status}`);
                }
                
                const result = await res.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    if (result.message === 'You must be logged in to edit lists') {
                        window.location.href = '/atlas/auth/login.php';
                    } else if (result.message === 'You do not have permission to edit this list') {
                        notifications.show('You do not have permission to edit this list', 'error');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        throw new Error(result.message || 'Failed to add movie');
                    }
                }
            } catch (err) {
                console.error('Add movie request failed:', err);
                notifications.show(err.message, 'error');
            } finally {
                movieCard.classList.remove('loading');
            }
        };
        
        fragment.appendChild(movieCard);
    });
    
   
    container.innerHTML = '';
    container.appendChild(fragment);
}


document.getElementById('movieSearchInput').addEventListener('input', function(e) {
    const query = this.value.trim();
    handleSearch(query);
});

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.remove-icon').forEach(button => {
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const movieId = button.dataset.id;
      const movieTitle = button.dataset.title || 'this movie';
      
      showConfirmDialog(movieId, movieTitle, button);
    });
  });
});
