<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Normalize: allow /api or /api/
if ($path === '/api') { $path = '/api/'; }

function route_id(string $path, string $prefix): ?int
{
  // Matches /api/events/123 or /api/events/123/
  $pattern = '#^' . preg_quote($prefix, '#') . '/(\d+)/?$#';
  if (preg_match($pattern, $path, $m))
  {
    return (int)$m[1];
  }
  return null;
}

function json_body(): array
{
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

// -------------------- EVENTS --------------------
$eventId = route_id($path, '/api/events');

if ($path === '/api/events' || $path === '/api/events/')
{
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'GET')
  {
    // Optional query params
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    if ($limit <= 0) { $limit = 100; }
    if ($limit > 500) { $limit = 500; }

    $stmt = $pdo->prepare("
      SELECT id, session_id, event_type, page_url, ts, element, value, extra
      FROM events
      ORDER BY id DESC
      LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['ok' => true, 'events' => $stmt->fetchAll()]);
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST')
  {
    $body = json_body();

    $session_id = isset($body['session_id']) ? (int)$body['session_id'] : 0;
    $event_type = isset($body['event_type']) ? (string)$body['event_type'] : '';
    $page_url   = isset($body['page_url']) ? (string)$body['page_url'] : '';

    if ($session_id <= 0 || $event_type === '' || $page_url === '')
    {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'Required: session_id, event_type, page_url']);
      exit;
    }

    $element = isset($body['element']) ? (string)$body['element'] : null;
    $value   = isset($body['value']) ? (string)$body['value'] : null;
    $extra   = isset($body['extra']) ? json_encode($body['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare("
      INSERT INTO events (session_id, event_type, page_url, element, value, extra)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$session_id, $event_type, $page_url, $element, $value, $extra]);

    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

if ($eventId !== null)
{
  $pdo = db();

  if ($_SERVER['REQUEST_METHOD'] === 'GET')
  {
    $stmt = $pdo->prepare("
      SELECT id, session_id, event_type, page_url, ts, element, value, extra
      FROM events
      WHERE id = ?
      LIMIT 1
    ");
    $stmt->execute([$eventId]);
    $row = $stmt->fetch();

    if (!$row)
    {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Not found']);
      exit;
    }

    echo json_encode(['ok' => true, 'event' => $row]);
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'PUT')
  {
    $body = json_body();

    // Allow updating only these fields (ts left unchanged)
    $fields = [];
    $params = [];

    foreach (['event_type', 'page_url', 'element', 'value', 'extra'] as $k)
    {
      if (array_key_exists($k, $body))
      {
        $fields[] = "{$k} = ?";
        if ($k === 'extra')
        {
          $params[] = ($body['extra'] === null) ? null : json_encode($body['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        else
        {
          $params[] = ($body[$k] === null) ? null : (string)$body[$k];
        }
      }
    }

    if (count($fields) === 0)
    {
      http_response_code(400);
      echo json_encode(['ok' => false, 'error' => 'No fields to update']);
      exit;
    }

    $params[] = $eventId;

    $sql = "UPDATE events SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['ok' => true, 'updated' => $stmt->rowCount()]);
    exit;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'DELETE')
  {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);

    if ($stmt->rowCount() === 0)
    {
      http_response_code(404);
      echo json_encode(['ok' => false, 'error' => 'Not found']);
      exit;
    }

    echo json_encode(['ok' => true, 'deleted' => 1]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

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