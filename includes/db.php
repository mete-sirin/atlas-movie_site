<?php
$env = parse_ini_file(__DIR__ . '/../.env');

if (!$env) {
    die('❌ Could not load environment variables. Please make sure .env file exists.');
}

$host     = $env['DB_HOST'] ?? null;
$db       = $env['DB_NAME'] ?? null;
$user     = $env['DB_USER'] ?? null;
$password = $env['DB_PASS'] ?? null;

if (!$host || !$db || !$user || !$password) {
    die('❌ Missing required database configuration in .env file.');
}

try {
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
?>
