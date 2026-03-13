<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_attempt(string $username, string $password): bool
{
    $sql = 'SELECT id, username, password_hash, display_name, role
            FROM admin_users
            WHERE username = ? AND is_active = 1
            LIMIT 1';

    $stmt = db()->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $allowedSections = [];

    if ($user['role'] === 'analyst') {
        $sectionSql = 'SELECT s.slug
                       FROM sections s
                       INNER JOIN user_sections us ON us.section_id = s.id
                       WHERE us.user_id = ?
                       ORDER BY s.slug ASC';

        $sectionStmt = db()->prepare($sectionSql);
        $sectionStmt->execute([(int) $user['id']]);
        $allowedSections = array_column($sectionStmt->fetchAll(), 'slug');
    }

    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'role' => $user['role'],
        'sections' => $allowedSections,
    ];

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }

    flash('Please log in to view the analytics backend.', 'error');
    redirect(base_url('login.php'));
}

function current_role(): ?string
{
    return $_SESSION['auth_user']['role'] ?? null;
}

function has_role(string $role): bool
{
    return current_role() === $role;
}

function require_role(array $roles): void
{
    require_login();

    if (in_array(current_role(), $roles, true)) {
        return;
    }

    http_response_code(403);
    require __DIR__ . '/../errors/403.php';
    exit;
}

function allowed_sections(): array
{
    return $_SESSION['auth_user']['sections'] ?? [];
}

function can_access_section(string $slug): bool
{
    $role = current_role();

    if ($role === 'super_admin') {
        return true;
    }

    if ($role === 'viewer') {
        return false;
    }

    if ($role !== 'analyst') {
        return false;
    }

    return in_array($slug, allowed_sections(), true);
}

function require_section(string $slug): void
{
    require_login();

    if (can_access_section($slug)) {
        return;
    }

    http_response_code(403);
    require __DIR__ . '/../errors/403.php';
    exit;
}