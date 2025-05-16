<?php
session_start();
require_once '../includes/db.php';


if (!isset($_SESSION['uid'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You must be logged in to edit lists']);
        exit;
    }
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['uid'];
$list_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['list_id']) ? (int) $_POST['list_id'] : 0);

if (!$list_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid list ID']);
        exit;
    }
    echo "<p>Invalid list ID.</p>";
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM movie_lists WHERE id = ?");
$stmt->execute([$list_id]);
$list_owner = $stmt->fetchColumn();

if ($list_owner !== $user_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this list']);
        exit;
    }
    header('Location: ../users/movie_list_profile.php?id=' . $list_owner);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    ob_start();
    
    require_once '../includes/db.php';
    
    header('Content-Type: application/json');
    
   
    if (isset($_POST['action']) && $_POST['action'] === 'delete_movie') {
        $list_id = isset($_POST['list_id']) ? (int)$_POST['list_id'] : 0;
        $movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $success = false;

        if ($list_id && $movie_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM movie_list_items WHERE list_id = ? AND movie_id = ?");
                $success = $stmt->execute([$list_id, $movie_id]);
            } catch (Exception $e) {
               
                error_log("Delete movie error: " . $e->getMessage());
            }
        }
        
        
        ob_end_clean();
        echo json_encode(['success' => $success]);
        exit;
    }
    
    
    if (isset($_POST['action']) && $_POST['action'] === 'add_movie') {
        $list_id = isset($_POST['list_id']) ? (int)$_POST['list_id'] : 0;
        $movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
        $success = false;
        $message = '';

        if ($list_id && $movie_id) {
            try {
               
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM movie_list_items WHERE list_id = ? AND movie_id = ?");
                $checkStmt->execute([$list_id, $movie_id]);
                $exists = (int)$checkStmt->fetchColumn() > 0;
                
                if ($exists) {
                    $message = 'Movie already exists in this list';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO movie_list_items (list_id, movie_id) VALUES (?, ?)");
                    $success = $stmt->execute([$list_id, $movie_id]);
                }
            } catch (Exception $e) {
                
                error_log("Add movie error: " . $e->getMessage());
                $message = 'Database error occurred';
            }
        } else {
            $message = 'Invalid list ID or movie ID';
        }
        
        
        ob_end_clean();
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
}


require_once '../includes/db.php';
require_once '../templates/header.php';

$st = $pdo->prepare("SELECT * FROM movie_lists WHERE id = ?");
$st->execute([$list_id]);
$list = $st->fetch(PDO::FETCH_ASSOC);

if (!$list) {
    echo "<p>List not found.</p>";
    exit;
}


$st = $pdo->prepare("SELECT movie_id FROM movie_list_items WHERE list_id = ?");
$st->execute([$list_id]);
$movie_ids = $st->fetchAll(PDO::FETCH_COLUMN);


$apiKey = '848df3823eaece087b9bd5baf5cb2805';
$movies = [];

foreach ($movie_ids as $id) {
    $url = "https://api.themoviedb.org/3/movie/$id?api_key=$apiKey&language=en-US";
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['id'])) {
            $movies[] = $data;
        }
    }
}
?>

<link rel="stylesheet" href="../assets/css/movie_lists.css">

<div class="page-header">
    <h1><?= htmlspecialchars($list['name']) ?></h1>
    <p>Created on <?= date('F j, Y', strtotime($list['created_at'])) ?></p>
</div>

<div class="movie-list">
    <?php if (empty($movies)): ?>
        <div class="empty-state">
            <p>No movies in this list yet.</p>
            <p>Click the "Add Movie" card to start building your list!</p>
        </div>
    <?php endif; ?>
    
    <?php foreach ($movies as $movie): ?>
        <div class="movie-card">
            <div class="poster-container">
                <?php if ($movie['poster_path']): ?>
                    <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" 
                         alt="<?= htmlspecialchars($movie['title']) ?>"
                         loading="lazy">
                <?php else: ?>
                    <div class="no-poster">
                        <span><?= htmlspecialchars($movie['title']) ?></span>
                    </div>
                <?php endif; ?>
                <button class="remove-icon" 
                        data-id="<?= $movie['id'] ?>" 
                        data-title="<?= htmlspecialchars($movie['title']) ?>"
                        title="Remove <?= htmlspecialchars($movie['title']) ?>">
                    🗑
                </button>
            </div>
            <p><strong><?= htmlspecialchars($movie['title']) ?></strong></p>
        </div>
    <?php endforeach; ?>
    
    <div class="movie-card add-card" id="openAddModal" title="Add a new movie to the list">
        <div>
            <span>+</span>
            <p><strong>Add Movie</strong></p>
        </div>
    </div>
</div>


<div class="modal-overlay" id="modalOverlay"></div>

<div id="addMovieModal">
    <div class="modal-header">
        <div class="modal-title">Add Movie to List</div>
        <button class="close-button" onclick="closeModal()" title="Close">&times;</button>
    </div>
    
    <div class="search-container">
        <input type="text" 
               id="movieSearchInput" 
               placeholder="Search for a movie..." 
               autocomplete="off"
               aria-label="Search for a movie">
               
        <div id="searchResults">
            <div class="empty-results">
                <p>Type to search for movies</p>
                <p class="hint">Search by title, year, or keywords</p>
            </div>
        </div>
    </div>
</div>


<div id="confirmDialog">
    <div class="confirm-title">Remove Movie</div>
    <div class="confirm-message" id="confirmMessage"></div>
    <div class="confirm-buttons">
        <button class="confirm-btn confirm-cancel" id="cancelDelete">Cancel</button>
        <button class="confirm-btn confirm-ok" id="confirmDelete">Remove</button>
    </div>
</div>

<script src="../assets/js/edit.js"></script>

<?php require_once '../templates/footer.php'; ?>
