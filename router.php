<?php
// Dados privados nunca são entregues pelo servidor, mesmo em prévia local.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (str_starts_with((string)$path, '/data/') || $path === '/server.js') { http_response_code(404); exit; }
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && str_starts_with(realpath($file), realpath(__DIR__ . '/public'))) return false;
require __DIR__ . '/index.php';
