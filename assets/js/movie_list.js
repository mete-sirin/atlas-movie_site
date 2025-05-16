let activeListId = null;
let temporaryMovieList = [];
let listName = null;


function showCustomAlert(message) {
  const overlay = document.createElement('div');
  overlay.className = 'overlay';
  
  const alert = document.createElement('div');
  alert.className = 'custom-alert';
  alert.innerHTML = `
    <div class="custom-alert-content">${message}</div>
    <button class="custom-alert-button">OK</button>
  `;
  
  document.body.appendChild(overlay);
  document.body.appendChild(alert);
  
  const button = alert.querySelector('button');
  button.addEventListener('click', () => {
    overlay.remove();
    alert.remove();
  });
}

document.getElementById('search-btn').addEventListener('click', async () => {
  await searchMovies();
});

const input = document.getElementById('movie-search');
input.addEventListener('keydown', async (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    await searchMovies();
  }
});

async function searchMovies() {
  const input = document.getElementById('movie-search');
  const x = input.value.trim();
  if (!x) return;

  const apiKey = '848df3823eaece087b9bd5baf5cb2805';
  const encoded = encodeURIComponent(x);
  const url = `https://api.themoviedb.org/3/search/movie?api_key=${apiKey}&query=${encoded}`;

  const res = await fetch(url);
  const data = await res.json();

  displayResults(data.results);
}

function displayResults(movies) {
  const container = document.getElementById('movieResults');
  container.innerHTML = '';

  movies.forEach(movie => {
    const poster = movie.poster_path
      ? `https://image.tmdb.org/t/p/w200${movie.poster_path}`
      : '/atlas/assets/img/placeholder.jpg';

    const div = document.createElement('div');
    div.classList.add('movie-card');
    div.innerHTML = `
      <img src="${poster}" alt="${movie.title}" />
      <p>
        <a href="movie.php?id=${movie.id}">
          <strong>${movie.title}</strong>
        </a>
      </p>
      <button 
        data-id="${movie.id}" 
        data-title="${movie.title}" 
        data-poster="${poster}">
        Add to list
      </button>
    `;

    container.appendChild(div);

    const addButton = div.querySelector('button');
    addButton.addEventListener('click', () => {
      if (!listName) {
        showCustomAlert("Please create a list first.");
        return;
      }

      const movieId = addButton.dataset.id;
      const title = addButton.dataset.title;
      const poster = addButton.dataset.poster;

      if (!temporaryMovieList.includes(movieId)) {
        temporaryMovieList.push(movieId);

        const mini = document.createElement('img');
        mini.src = poster;
        mini.alt = title;
        mini.style.width = '60px';
        mini.style.height = '90px';
        mini.style.objectFit = 'cover';
        mini.title = title;

        document.getElementById('posterGrid').appendChild(mini);
      }
    });
  });
}

document.getElementById('createListBtn').addEventListener('click', function() {
  if (document.getElementById('listPanel')) return;

  const panel = document.createElement('div');
  panel.id = 'listPanel';
  panel.innerHTML = `
    <h3 class="new-list-header">New List</h3>
    <div class="new-list-form">
      <input type="text" id="listNameInput" placeholder="Enter list name">
    <button id="confirmListBtn">Confirm Name</button>
    </div>
    <div id="listMessage"></div>
  `;
  document.getElementById('listPanelContainer').appendChild(panel);

  document.getElementById('confirmListBtn').addEventListener('click', async () => {
    const nameInput = document.getElementById('listNameInput');
    const name = nameInput.value.trim();
    if (!name) {
      showCustomAlert("Please enter a list name.");
      return;
    }

    listName = name;
    nameInput.disabled = true;
    document.getElementById('confirmListBtn').disabled = true;

    if (!document.getElementById('posterPanel')) {
      const posterPanel = document.createElement('div');
      posterPanel.id = 'posterPanel';
      posterPanel.innerHTML = `
        <div id="posterGrid"></div>
        <button id="finalizeBtn">Finalize and Save List</button>
      `;

      document.getElementById('activeList').appendChild(posterPanel);

      document.getElementById('finalizeBtn').addEventListener('click', async () => {
        if (!listName || temporaryMovieList.length === 0) {
          showCustomAlert("List name or movie list is missing.");
          return;
        }

        try {
          const response = await fetch('../movies/create_list.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: listName,
              movies: temporaryMovieList
            })
          });

          const result = await response.text();
          if (!isNaN(result)) {
            activeListId = result;
            document.getElementById('finalizeBtn').disabled = true;
            document.getElementById('listMessage').innerHTML = `
              <div class="success-message">List saved successfully!</div>
            `;
          } else {
            document.getElementById('listMessage').innerHTML = `
              <div class="error-message">Save failed: ${result}</div>
            `;
          }
        } catch (err) {
          console.error("Error saving list:", err);
          document.getElementById('listMessage').innerHTML = `
            <div class="error-message">Server error on save.</div>
          `;
        }
      });
    }
  });
});

