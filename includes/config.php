<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Ljubljana');

function env_string(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return (string) $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('SITE_NAME', env_string('HANKO_SITE_NAME', 'Hanko Apartmaji'));
define('SITE_TAGLINE', 'Počitnice, kjer se čas umiri.');
define('SITE_LOCATION', env_string('HANKO_SITE_LOCATION', 'Hanko Residence, Gorenjska, Slovenija'));
define('SITE_PHONE', env_string('HANKO_SITE_PHONE', '+386 40 123 456'));
define('SITE_EMAIL', env_string('HANKO_SITE_EMAIL', 'info@hanko-apartmaji.si'));
define('ADMIN_NOTIFY_EMAIL', env_string('HANKO_ADMIN_EMAIL', SITE_EMAIL));

define('ROOT_PATH', dirname(__DIR__));
define('DATA_PATH', rtrim(env_string('HANKO_DATA_PATH', ROOT_PATH . '/data'), '/'));
define('DB_PATH', DATA_PATH . '/hanko.sqlite');
define('MAIL_LOG_PATH', DATA_PATH . '/mail');

$forcedBase = rtrim(env_string('HANKO_BASE_URL', ''), '/');
if ($forcedBase !== '') {
    define('BASE_URL', $forcedBase);
} else {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
    if (str_contains($scriptDir, '/admin')) {
        $scriptDir = preg_replace('#/admin.*$#', '', $scriptDir) ?? '';
    }
    if (str_contains($scriptDir, '/api')) {
        $scriptDir = preg_replace('#/api.*$#', '', $scriptDir) ?? '';
    }
    define('BASE_URL', $scriptDir === '' || $scriptDir === '/' ? '' : $scriptDir);
}

define('SMTP_ENABLED', env_bool('HANKO_SMTP_ENABLED', false));
define('SMTP_HOST', env_string('HANKO_SMTP_HOST', 'smtp.example.com'));
define('SMTP_PORT', (int) env_string('HANKO_SMTP_PORT', '587'));
define('SMTP_USER', env_string('HANKO_SMTP_USER', ''));
define('SMTP_PASS', env_string('HANKO_SMTP_PASS', ''));
define('SMTP_SECURE', env_string('HANKO_SMTP_SECURE', 'tls'));
define('SMTP_FROM_NAME', env_string('HANKO_SMTP_FROM_NAME', SITE_NAME));

define('DEFAULT_ADMIN_USER', env_string('HANKO_ADMIN_USER', 'admin'));
define('DEFAULT_ADMIN_PASS', env_string('HANKO_ADMIN_PASS', 'HankoAdmin2026!'));
define('SHOW_ADMIN_HINT', env_bool('HANKO_SHOW_ADMIN_HINT', false));

function base_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function slovenian_date(string $date): string
{
    $months = [
        1 => 'januar', 2 => 'februar', 3 => 'marec', 4 => 'april',
        5 => 'maj', 6 => 'junij', 7 => 'julij', 8 => 'avgust',
        9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
    ];
    $dt = new DateTimeImmutable($date);
    return (int) $dt->format('j') . '. ' . $months[(int) $dt->format('n')] . ' ' . $dt->format('Y');
}

function money_eur(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}
