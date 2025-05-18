<?php
if (!isset($env)) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    if (!$env) {
        die('❌ Could not load environment variables. Please make sure .env file exists.');
    }
}

$TMDB_API_KEY = $env['TMDB_API_KEY'] ?? null;

if (!$TMDB_API_KEY) {
    die('❌ Missing TMDB API key in .env file.');
}

define('TMDB_API_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500');

function getTMDBData($endpoint, $params = []) {
    global $TMDB_API_KEY;
    
    $params['api_key'] = $TMDB_API_KEY;
    $query = http_build_query($params);
    $url = TMDB_API_BASE_URL . $endpoint . '?' . $query;
    
    $response = @file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}
?> 