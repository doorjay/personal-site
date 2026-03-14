<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_role(['super_admin', 'analyst']);

$pdo = db();
$role = current_role();

if ($role === 'super_admin') {
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

$title = '';
$sectionId = '';
$chartType = 'bar';
$analystComment = '';
$filtersJson = '{"source":"events","view":"summary"}';
$isPublished = 0;
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
            $error = 'You cannot create a report in that category.';
        }
    }

    if ($error === null) {
        $sql = 'INSERT INTO reports_saved
                (title, section_id, created_by, analyst_comment, chart_type, filters_json, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            (int) $sectionId,
            (int) current_user()['id'],
            $analystComment,
            $chartType,
            $filtersJson,
            $isPublished,
        ]);

        flash('Saved report created successfully.', 'success');
        redirect(base_url('saved-reports.php'));
    }
}

render_header('Create Report');
?>
<section class="card">
    <h2>Create saved report</h2>

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

        <label class="checkbox-row">
            <span>Publish this report</span>
            <input type="checkbox" name="is_published" value="1" <?= $isPublished === 1 ? 'checked' : '' ?>>
        </label>

        <button class="button" type="submit">Save report</button>
    </form>
</section>
<?php
render_footer();