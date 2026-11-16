<?php
declare(strict_types=1);

$root = __DIR__ . DIRECTORY_SEPARATOR . 'www';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = realpath($root . str_replace('/', DIRECTORY_SEPARATOR, $path));
$realRoot = realpath($root);

if ($file && $realRoot && str_starts_with($file, $realRoot . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}

readfile($root . DIRECTORY_SEPARATOR . 'index.html');
