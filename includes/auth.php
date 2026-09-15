<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
        exit;
    }
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_user'] = $username;
    return true;
}

function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_user']);
    session_regenerate_id(true);
}
