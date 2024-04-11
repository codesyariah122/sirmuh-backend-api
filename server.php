<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicPath = __DIR__ . '/public';

// Jika file atau direktori ada di direktori public, kirim langsung
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

// Letakkan di sini aturan tambahan jika diperlukan

// Jika tidak ada yang sesuai, kirim ke public/index.php
require_once $publicPath . '/index.php';