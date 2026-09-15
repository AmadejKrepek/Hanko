<?php
declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

if (preg_match('#(?:^|/)(?:data|\.env|\.git|docker)(?:/|$)#', $uri)
    || preg_match('#\.(sqlite|sqlite-journal|sqlite-wal)$#', $uri)) {
    http_response_code(403);
    echo 'Dostop zavrnjen.';
    return true;
}

if ($uri !== '/' && is_file($file)) {
    return false;
}

if (is_dir($file) && is_file($file . '/index.php')) {
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
