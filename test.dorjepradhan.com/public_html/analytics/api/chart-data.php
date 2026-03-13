<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['super_admin', 'analyst']);

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$typeRows = $pdo->query(
    'SELECT event_type, COUNT(*) AS total_events
     FROM events
     GROUP BY event_type
     ORDER BY total_events DESC, event_type ASC'
)->fetchAll();

$pageRows = $pdo->query(
    'SELECT page_url, COUNT(*) AS total_events
     FROM events
     GROUP BY page_url
     ORDER BY total_events DESC, page_url ASC
     LIMIT 8'
)->fetchAll();

echo json_encode([
    'eventsByType' => [
        'labels' => array_map(static fn(array $row): string => (string) $row['event_type'], $typeRows),
        'values' => array_map(static fn(array $row): int => (int) $row['total_events'], $typeRows),
    ],
    'eventsByPage' => [
        'labels' => array_map(static fn(array $row): string => (string) $row['page_url'], $pageRows),
        'values' => array_map(static fn(array $row): int => (int) $row['total_events'], $pageRows),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
