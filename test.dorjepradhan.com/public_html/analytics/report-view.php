<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_login();

$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reportId <= 0) {
    http_response_code(404);
    echo 'Report not found.';
    exit;
}

$pdo = db();

$sql = 'SELECT
            r.*,
            s.name AS section_name,
            s.slug AS section_slug,
            u.display_name AS author_name
        FROM reports_saved r
        INNER JOIN sections s ON s.id = r.section_id
        INNER JOIN admin_users u ON u.id = r.created_by
        WHERE r.id = ?
        LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    echo 'Report not found.';
    exit;
}

$role = current_role();

if ($role === 'viewer' && (int) $report['is_published'] !== 1) {
    http_response_code(403);
    require __DIR__ . '/errors/403.php';
    exit;
}

if ($role === 'analyst' && !can_access_section((string) $report['section_slug'])) {
    http_response_code(403);
    require __DIR__ . '/errors/403.php';
    exit;
}

render_header('View Report');
?>
<section class="card">
    <h2><?= e((string) $report['title']) ?></h2>
    <p><strong>Category:</strong> <?= e((string) $report['section_name']) ?></p>
    <p><strong>Author:</strong> <?= e((string) $report['author_name']) ?></p>
    <p><strong>Chart type:</strong> <?= e((string) $report['chart_type']) ?></p>
    <p><strong>Status:</strong> <?= (int) $report['is_published'] === 1 ? 'Published' : 'Draft' ?></p>
    <p><strong>Created:</strong> <?= e((string) $report['created_at']) ?></p>
    <p><strong>Updated:</strong> <?= e((string) $report['updated_at']) ?></p>
</section>

<section class="card">
    <h2>Analyst comment</h2>
    <p><?= nl2br(e((string) ($report['analyst_comment'] ?? ''))) ?></p>
</section>

<section class="card">
    <h2>Saved report configuration</h2>
    <pre><?= e((string) ($report['filters_json'] ?? '{}')) ?></pre>
</section>

<section class="card">
    <?php if (in_array($role, ['super_admin', 'analyst'], true)): ?>
        <a class="button" href="<?= e(base_url('report-edit.php?id=' . (int) $report['id'])) ?>">Edit report</a>
    <?php endif; ?>

    <a class="button" href="<?= e(base_url('report-export.php?id=' . (int) $report['id'])) ?>">Export PDF</a>
</section>

<?php
render_footer();