<?php
session_start();
require_once "../includes/db.php";
require_once '../templates/header.php';     

if (!isset($_SESSION['uid'])) {
    header('Location:../auth/login.php');
    exit;
}

$uid = $_SESSION['uid'];
$profile_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : $uid;

$list_query = $pdo->prepare("SELECT * FROM movie_lists WHERE user_id = ?");
$list_query->execute([$profile_id]);
$user_list_info = $list_query->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.lists-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.list-summary {
    border: 1px solid #ccc;
    padding: 20px;
    border-radius: 10px;
    margin: 15px 0;
    background: white;
}

.poster-thumbnails {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.poster-thumbnails img {
    width: 100px;
    height: 150px;
    object-fit: cover;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.poster-thumbnails img:hover {
    transform: scale(1.05);
}

.list-title {
    margin-top: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.list-date {
    font-size: 0.9em;
    color: #666;
    margin: 5px 0;
}
</style>

<div class="lists-container">
<?php
if (!$user_list_info && $profile_id == $uid) {
    echo '<p>You have no lists! <a href="../movies/movie_list.php">Create one now</a>.</p>';
} elseif (!$user_list_info) {
    echo "<p>This user has no lists.</p>";
} else {
    foreach ($user_list_info as $list) {
        echo '<div class="list-summary">';
        echo '<h3 class="list-title">';
        if ($profile_id == $uid) {
            echo '<a href="../movies/edit_movie_lists.php?id=' . $list['id'] . '" style="text-decoration: none; color: #2c3e50;">' . htmlspecialchars($list['name']) . '</a>';
        } else {
            echo '<span>' . htmlspecialchars($list['name']) . '</span>';
        }
        echo '</h3>';
        echo '<p class="list-date">Created on ' . htmlspecialchars($list['created_at']) . '</p>';
        echo '<div class="poster-thumbnails">';

        $movie_list_items_query = $pdo->prepare("SELECT movie_id FROM movie_list_items WHERE list_id = ?");
        $movie_list_items_query->execute([$list['id']]);
        $movie_items = $movie_list_items_query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($movie_items as $row) {
            $movie_id = $row['movie_id'];
            $apiKey = '848df3823eaece087b9bd5baf5cb2805';
            $url = "https://api.themoviedb.org/3/movie/$movie_id?api_key=$apiKey";

            $response = @file_get_contents($url);
            if ($response === false) {
                continue;
            }

            $data = json_decode($response, true);
            if (isset($data['poster_path'], $data['title'])) {
                $poster = $data['poster_path']
                    ? 'https://image.tmdb.org/t/p/w200' . $data['poster_path']
                    : '/atlas/assets/img/placeholder.jpg';

                echo '<img src="' . $poster . '" alt="' . htmlspecialchars($data['title']) . '" title="' . htmlspecialchars($data['title']) . '">';
            }
        }

        echo '</div>'; 
        echo '</div>'; 
    }
}
?>
</div>

<?php require_once '../templates/footer.php'; ?>
