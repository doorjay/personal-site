<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/layout.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_login();

$reportId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($reportId <= 0) {
    http_response_code(404);
    require __DIR__ . '/errors/404.php';
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
    require __DIR__ . '/errors/404.php';
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

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

$title = (string) $report['title'];
$sectionName = (string) $report['section_name'];
$authorName = (string) $report['author_name'];
$chartType = (string) $report['chart_type'];
$status = (int) $report['is_published'] === 1 ? 'Published' : 'Draft';
$createdAt = (string) $report['created_at'];
$updatedAt = (string) $report['updated_at'];
$analystComment = nl2br(e((string) ($report['analyst_comment'] ?? '')));
$filtersJson = e((string) ($report['filters_json'] ?? '{}'));

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exported Report</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #1c2434;
            font-size: 12px;
            line-height: 1.5;
            margin: 24px;
        }

        h1, h2 {
            margin: 0 0 12px;
        }

        .meta,
        .section {
            border: 1px solid #d7ddea;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .label {
            font-weight: bold;
        }

        pre {
            background: #f5f7fb;
            border: 1px solid #d7ddea;
            border-radius: 8px;
            padding: 12px;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <h1>{$title}</h1>

    <div class="meta">
        <p><span class="label">Category:</span> {$sectionName}</p>
        <p><span class="label">Author:</span> {$authorName}</p>
        <p><span class="label">Chart type:</span> {$chartType}</p>
        <p><span class="label">Status:</span> {$status}</p>
        <p><span class="label">Created:</span> {$createdAt}</p>
        <p><span class="label">Updated:</span> {$updatedAt}</p>
    </div>

    <div class="section">
        <h2>Analyst Comment</h2>
        <p>{$analystComment}</p>
    </div>

    <div class="section">
        <h2>Saved Report Configuration</h2>
        <pre>{$filtersJson}</pre>
    </div>
</body>
</html>
HTML;

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfOutput = $dompdf->output();

$exportsDir = __DIR__ . '/exports';
$fileSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
$fileSlug = trim((string) $fileSlug, '-');
if ($fileSlug === '') {
    $fileSlug = 'report';
}

$fileName = sprintf(
    '%s-%d-%s.pdf',
    $fileSlug,
    $reportId,
    date('Ymd-His')
);

$absolutePath = $exportsDir . '/' . $fileName;
$publicPath = 'exports/' . $fileName;

file_put_contents($absolutePath, $pdfOutput);

$insertSql = 'INSERT INTO report_exports (report_id, exported_by, file_path)
              VALUES (?, ?, ?)';

$insertStmt = $pdo->prepare($insertSql);
$insertStmt->execute([
    $reportId,
    (int) current_user()['id'],
    $publicPath,
]);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
echo $pdfOutput;
exit;