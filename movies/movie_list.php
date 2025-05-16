<?php require_once "../templates/header.php"; ?>

<div class="search-container">
    <div class="search-wrapper">
<input type="text" id="movie-search" placeholder="Search for movies...">
<button id="search-btn">Search</button>
    </div>
</div>

<div id="page">
    <div id="searchArea">
    <div id="movieResults"></div>
  </div>

    <div id="activeList">
        <button id="createListBtn">
            <span>➕</span>
            Create New List
        </button>
    <div id="listPanelContainer"></div>
    <h2 id="listTitle"></h2>
  </div>
</div>

<script type="module" src="../assets/js/movie_list.js"></script>

