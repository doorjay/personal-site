<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

const ANALYTICS_APP_NAME = 'Analytics Backend';
const ANALYTICS_BASE_URL = '/analytics';

function app_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [
        'db_host' => getenv('ANALYTICS_DB_HOST') ?: '127.0.0.1',
        'db_name' => getenv('ANALYTICS_DB_NAME') ?: 'analytics',
        'db_user' => getenv('ANALYTICS_DB_USER') ?: '',
        'db_pass' => getenv('ANALYTICS_DB_PASS') ?: '',
        'auth_table' => 'admin_users',
    ];

    if ($config['db_user'] === '' || $config['db_pass'] === '')
    {
        throw new RuntimeException('Database credentials are not configured.');
    }

    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim(ANALYTICS_BASE_URL, '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash(?string $message = null, string $type = 'info'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type,
        ];

        return null;
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
