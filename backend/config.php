<?php
// backend/config.php

define('NEXTCLOUD_URL', 'https://drive2.xhost.co.in/remote.php/dav/files/tr8y01cf');
define('NEXTCLOUD_USER', 'tr8y01cf');
define('NEXTCLOUD_PASS', 'ja9ia-FWjYB-7Hg5a-zJfTY-LF5J7');

// Default folder in Nextcloud for movies (will be created if doesn't exist)
define('MOVIES_FOLDER', 'Movies');

// Production Domain Base URL
$host = $_SERVER['HTTP_HOST'] ?? 'movies.mybhiwani.in';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    || (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'movies.mybhiwani.in'));
$protocol = $isHttps ? "https://" : "http://";
define('BASE_API_URL', $protocol . $host);

// CORS Headers for API accessibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Range, X-Requested-With');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
