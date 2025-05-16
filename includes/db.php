<?php
$env = parse_ini_file(__DIR__ . '/../.env');

$host     = $env['DB_HOST'];
$db       = $env['DB_NAME'];
$user     = $env['DB_USER'];
$password = $env['DB_PASS'];

try {
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$db", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
    exit;
}
?>
