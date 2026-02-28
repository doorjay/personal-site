<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Normalize: allow /api or /api/
if ($path === '/api') { $path = '/api/'; }
if ($path === '/api/' || $path === '/api/index.php')
{
  echo json_encode([
    'ok' => true,
    'routes' => [
      '/api/summary?minutes=60',
      '/api/events?limit=50',
      '/api/sessions?limit=25',
    ],
  ]);
  exit;
}
try
{
  if ($path === '/api/summary' || $path === '/api/summary/')
  {
    $minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 60;
    if ($minutes <= 0) { $minutes = 60; }
    if ($minutes > 10080) { $minutes = 10080; } // cap at 7 days

    $pdo = db();

    // Unique sessions seen in window
    $stmt1 = $pdo->prepare("
      SELECT COUNT(DISTINCT s.id) AS sessions
      FROM sessions s
      JOIN events e ON e.session_id = s.id
      WHERE e.ts >= (NOW() - INTERVAL ? MINUTE)
    ");
    $stmt1->execute([$minutes]);
    $sessions = (int)($stmt1->fetch()['sessions'] ?? 0);

    // Counts per event type
    $stmt2 = $pdo->prepare("
      SELECT event_type, COUNT(*) AS count
      FROM events
      WHERE ts >= (NOW() - INTERVAL ? MINUTE)
      GROUP BY event_type
      ORDER BY count DESC
    ");
    $stmt2->execute([$minutes]);
    $byType = $stmt2->fetchAll();

    echo json_encode([
      'ok' => true,
      'window_minutes' => $minutes,
      'unique_sessions' => $sessions,
      'events_by_type' => $byType,
    ]);
    exit;
  }

  if ($path === '/api/events' || $path === '/api/events/')
  {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    if ($limit <= 0) { $limit = 50; }
    if ($limit > 500) { $limit = 500; }

    $pdo = db();

    $stmt = $pdo->prepare("
      SELECT e.ts, e.event_type, e.page_url, e.element, e.value, e.extra, s.session_key
      FROM events e
      JOIN sessions s ON s.id = e.session_id
      ORDER BY e.id DESC
      LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
      'ok' => true,
      'limit' => $limit,
      'events' => $stmt->fetchAll(),
    ]);
    exit;
  }

  if ($path === '/api/sessions' || $path === '/api/sessions/')
  {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    if ($limit <= 0) { $limit = 25; }
    if ($limit > 200) { $limit = 200; }

    $pdo = db();

    $stmt = $pdo->prepare("
      SELECT
        s.id,
        s.session_key,
        s.first_seen,
        s.last_seen,
        s.ip_address,
        SUBSTRING(s.user_agent, 1, 120) AS user_agent_short,
        (SELECT COUNT(*) FROM events e WHERE e.session_id = s.id) AS event_count
      FROM sessions s
      ORDER BY s.last_seen DESC
      LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
      'ok' => true,
      'limit' => $limit,
      'sessions' => $stmt->fetchAll(),
    ]);
    exit;
  }

  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'Not found']);
}
catch (Throwable $e)
{
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error']);
}