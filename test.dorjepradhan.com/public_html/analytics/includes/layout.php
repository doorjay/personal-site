<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function render_header(string $title): void
{
    $flash = flash();
    $user = current_user();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= e(ANALYTICS_APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/analytics.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="page-shell">
    <header class="topbar">
        <div>
            <p class="eyebrow">Analytics reporting platform</p>
            <h1><?= e($title) ?></h1>
        </div>
        <?php if ($user): ?>
            <div class="topbar-actions">
                <span class="welcome">Signed in as <?= e($user['display_name']) ?></span>
                <a class="button button-secondary" href="<?= e(base_url('logout.php')) ?>">Log out</a>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($user): ?>
        <nav class="nav-card">
            <?php if (in_array(current_role(), ['super_admin', 'analyst'], true)): ?>
                <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
                <a href="<?= e(base_url('performance.php')) ?>">Performance</a>
                <a href="<?= e(base_url('behavior.php')) ?>">Behavior</a>
                <a href="<?= e(base_url('system-health.php')) ?>">System Health</a>
                <a href="<?= e(base_url('reports.php')) ?>">Reports Table</a>
                <a href="<?= e(base_url('charts.php')) ?>">Charts</a>
                <a href="<?= e(base_url('report-create.php')) ?>">Create Report</a>
            <?php endif; ?>

            <?php if (current_role() === 'super_admin'): ?>
                <a href="<?= e(base_url('admin-users.php')) ?>">User Management</a>
            <?php endif; ?>

            <a href="<?= e(base_url('saved-reports.php')) ?>">Saved Reports</a>
            <a class="button-secondary" href="/index.html">Back to test site</a>
        </nav>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <main>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
</div>
</body>
</html>
    <?php
}
