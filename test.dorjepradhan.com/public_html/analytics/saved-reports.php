<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_login();

$pdo = db();
$user = current_user();
$role = current_role();

if ($role === 'viewer') {
    $sql = 'SELECT
                r.id,
                r.title,
                r.analyst_comment,
                r.chart_type,
                r.is_published,
                r.created_at,
                r.updated_at,
                s.name AS section_name,
                s.slug AS section_slug,
                u.display_name AS author_name
            FROM reports_saved r
            INNER JOIN sections s ON s.id = r.section_id
            INNER JOIN admin_users u ON u.id = r.created_by
            WHERE r.is_published = 1
            ORDER BY r.updated_at DESC, r.id DESC';
    $stmt = $pdo->query($sql);
} else {
    $sql = 'SELECT
                r.id,
                r.title,
                r.analyst_comment,
                r.chart_type,
                r.is_published,
                r.created_at,
                r.updated_at,
                s.name AS section_name,
                s.slug AS section_slug,
                u.display_name AS author_name
            FROM reports_saved r
            INNER JOIN sections s ON s.id = r.section_id
            INNER JOIN admin_users u ON u.id = r.created_by';

    if ($role === 'analyst') {
        $sections = allowed_sections();

        if ($sections === []) {
            $reports = [];
            render_header('Saved Reports');
            ?>
            <section class="card">
                <h2>Saved reports</h2>
                <p>No allowed sections are assigned to your analyst account yet.</p>
            </section>
            <?php
            render_footer();
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($sections), '?'));
        $sql .= " WHERE s.slug IN ($placeholders) ORDER BY r.updated_at DESC, r.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($sections);
    } else {
        $sql .= ' ORDER BY r.updated_at DESC, r.id DESC';
        $stmt = $pdo->query($sql);
    }
}

$reports = $stmt->fetchAll();

render_header('Saved Reports');
?>
<section class="card">
    <h2>Saved reports</h2>
    <p>View saved reports by category. Viewers can only access published reports.</p>

    <?php if (!$reports): ?>
        <p>No saved reports yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Open</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= e((string) $report['title']) ?></td>
                            <td><?= e((string) $report['section_name']) ?></td>
                            <td><?= e((string) $report['author_name']) ?></td>
                            <td><?= (int) $report['is_published'] === 1 ? 'Published' : 'Draft' ?></td>
                            <td><?= e((string) $report['updated_at']) ?></td>
                            <td>
                                <a href="<?= e(base_url('report-view.php?id=' . (int) $report['id'])) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
render_footer();