<?php
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

if (is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}

if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

http_response_code(404);
echo 'Stran ni najdena.';
return true;
