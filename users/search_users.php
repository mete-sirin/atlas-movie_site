<?php
require_once '../templates/header.php';
require_once '../includes/db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: ../auth/login.php');
    exit;
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = [];

if ($search_term) {
    $stmt = $pdo->prepare("
        SELECT id, username 
        FROM users 
        WHERE username LIKE ? 
        AND id != ?
        ORDER BY username
        LIMIT 20
    ");
    $stmt->execute(['%' . $search_term . '%', $_SESSION['uid']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container">
    <div class="search-section">
        <h1>Find Users</h1>
        <form method="GET" action="" class="search-form">
            <div class="search-input-group">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search_term) ?>" 
                    placeholder="Search users..." 
                    class="search-input"
                    autocomplete="off"
                >
                <button type="submit" class="search-button">Search</button>
            </div>
</form>

        <div class="search-results">
            <?php if ($search_term): ?>
                <?php if (empty($users)): ?>
                    <p class="no-results">No users found matching "<?= htmlspecialchars($search_term) ?>"</p>
                <?php else: ?>
                    <div class="users-grid">
<?php foreach ($users as $user): ?>
                            <div class="user-card">
                                <div class="user-info">
                                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                                    <div class="user-actions">
                                        <a href="profile.php?id=<?= $user['id'] ?>" class="btn btn-primary">View Profile</a>
                                    </div>
                                </div>
                            </div>
<?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.search-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.search-section h1 {
    margin: 0 0 30px 0;
    color: var(--primary-color);
    font-size: 2rem;
}

.search-form {
    margin-bottom: 30px;
}

.search-input-group {
    display: flex;
    gap: 10px;
    max-width: 600px;
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
    padding: 12px 25px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-button:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.user-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.user-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.user-info h3 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.2rem;
}

.user-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s ease;
    text-align: center;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
}

.no-results {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 8px;
    color: #6c757d;
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .search-input-group {
        flex-direction: column;
    }
    
    .search-button {
        width: 100%;
    }
    
    .users-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../templates/footer.php'; ?>

