<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin', 'analyst']);

$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reportId <= 0) {
    http_response_code(404);
    echo 'Report not found.';
    exit;
}

$pdo = db();

$sql = 'SELECT
            r.*,
            s.slug AS section_slug
        FROM reports_saved r
        INNER JOIN sections s ON s.id = r.section_id
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

if (current_role() === 'analyst' && !can_access_section((string) $report['section_slug'])) {
    http_response_code(403);
    require __DIR__ . '/errors/403.php';
    exit;
}

if (current_role() === 'super_admin') {
    $sectionsStmt = $pdo->query('SELECT id, slug, name FROM sections ORDER BY name ASC');
    $sections = $sectionsStmt->fetchAll();
} else {
    $sql = 'SELECT s.id, s.slug, s.name
            FROM sections s
            INNER JOIN user_sections us ON us.section_id = s.id
            WHERE us.user_id = ?
            ORDER BY s.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int) current_user()['id']]);
    $sections = $stmt->fetchAll();
}

$title = (string) $report['title'];
$sectionId = (string) $report['section_id'];
$chartType = (string) $report['chart_type'];
$analystComment = (string) ($report['analyst_comment'] ?? '');
$filtersJson = (string) ($report['filters_json'] ?? '{}');
$isPublished = (int) $report['is_published'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $sectionId = (string) ($_POST['section_id'] ?? '');
    $chartType = trim((string) ($_POST['chart_type'] ?? 'bar'));
    $analystComment = trim((string) ($_POST['analyst_comment'] ?? ''));
    $filtersJson = trim((string) ($_POST['filters_json'] ?? '{}'));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '' || $sectionId === '') {
        $error = 'Title and category are required.';
    } else {
        $allowedSectionIds = array_map(
            static fn(array $row): int => (int) $row['id'],
            $sections
        );

        if (!in_array((int) $sectionId, $allowedSectionIds, true)) {
            $error = 'You cannot move this report into that category.';
        }
    }

    if ($error === null) {
        $sql = 'UPDATE reports_saved
                SET title = ?,
                    section_id = ?,
                    analyst_comment = ?,
                    chart_type = ?,
                    filters_json = ?,
                    is_published = ?
                WHERE id = ?';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            (int) $sectionId,
            $analystComment,
            $chartType,
            $filtersJson,
            $isPublished,
            $reportId,
        ]);

        flash('Saved report updated successfully.', 'success');
        redirect(base_url('report-view.php?id=' . $reportId));
    }
}

render_header('Edit Report');
?>
<section class="card">
    <h2>Edit saved report</h2>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="stack-form">
        <label>
            Title
            <input type="text" name="title" value="<?= e($title) ?>" required>
        </label>

        <label>
            Category
            <select name="section_id" required>
                <option value="">Select a category</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?= (int) $section['id'] ?>" <?= (string) $section['id'] === $sectionId ? 'selected' : '' ?>>
                        <?= e((string) $section['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Chart type
            <select name="chart_type">
                <option value="bar" <?= $chartType === 'bar' ? 'selected' : '' ?>>Bar</option>
                <option value="line" <?= $chartType === 'line' ? 'selected' : '' ?>>Line</option>
                <option value="pie" <?= $chartType === 'pie' ? 'selected' : '' ?>>Pie</option>
            </select>
        </label>

        <label>
            Analyst comment
            <textarea name="analyst_comment" rows="6"><?= e($analystComment) ?></textarea>
        </label>

        <label>
            Filters JSON
            <textarea name="filters_json" rows="6"><?= e($filtersJson) ?></textarea>
        </label>

        <label>
            <input type="checkbox" name="is_published" value="1" <?= $isPublished === 1 ? 'checked' : '' ?>>
            Publish this report
        </label>

        <button class="button" type="submit">Update report</button>
    </form>
</section>
<?php
render_footer();