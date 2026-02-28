<?php
declare(strict_types=1);

$allowedOrigins = [
  'https://test.dorjepradhan.com',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true))
{
  header("Access-Control-Allow-Origin: {$origin}");
  header('Vary: Origin');
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
{
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?? '', true);

if (!is_array($data))
{
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit;
}

$config = require __DIR__ . '/../config.php';

$dsn = sprintf(
  'mysql:host=%s;dbname=%s;charset=utf8mb4',
  $config['db_host'],
  $config['db_name']
);

try
{
  $pdo = new PDO(
    $dsn,
    $config['db_user'],
    $config['db_pass'],
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
}
catch (Throwable $e)
{
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'DB connect failed']);
  exit;
}

// Session cookie (simple + effective)
$cookieName = 'aid';
$sessionKey = $_COOKIE[$cookieName] ?? '';

if (!is_string($sessionKey) || strlen($sessionKey) < 16)
{
  $sessionKey = bin2hex(random_bytes(16));
  setcookie($cookieName, $sessionKey, [
    'expires' => time() + (60 * 60 * 24 * 30),
    'path' => '/',
    'secure' => true,
    'httponly' => false,
    'samesite' => 'Lax',
  ]);
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Upsert session
$pdo->beginTransaction();

$sessionId = null;

// Try fetch existing
$stmt = $pdo->prepare('SELECT id FROM sessions WHERE session_key = ? LIMIT 1');
$stmt->execute([$sessionKey]);
$row = $stmt->fetch();

if ($row)
{
  $sessionId = (int)$row['id'];

  $upd = $pdo->prepare('UPDATE sessions SET user_agent = COALESCE(?, user_agent), ip_address = COALESCE(?, ip_address), last_seen = CURRENT_TIMESTAMP WHERE id = ?');
  $upd->execute([$userAgent, $ip, $sessionId]);
}
else
{
  $ins = $pdo->prepare('INSERT INTO sessions (session_key, user_agent, ip_address) VALUES (?, ?, ?)');
  $ins->execute([$sessionKey, $userAgent, $ip]);
  $sessionId = (int)$pdo->lastInsertId();
}

// Insert events (support single event or array)
$events = $data['events'] ?? null;

if (!is_array($events))
{
  $events = [$data]; // allow posting a single event object
}

$insEv = $pdo->prepare('
  INSERT INTO events (session_id, event_type, page_url, element, value, extra)
  VALUES (?, ?, ?, ?, ?, ?)
');

$inserted = 0;

foreach ($events as $ev)
{
  if (!is_array($ev)) { continue; }

  $type = $ev['event_type'] ?? '';
  $url = $ev['page_url'] ?? '';

  if (!is_string($type) || $type === '') { continue; }
  if (!is_string($url) || $url === '') { continue; }

  $element = isset($ev['element']) && is_string($ev['element']) ? $ev['element'] : null;
  $value = isset($ev['value']) && is_string($ev['value']) ? $ev['value'] : null;

  $extra = null;
  if (isset($ev['extra']))
  {
    $extra = json_encode($ev['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  $insEv->execute([$sessionId, $type, $url, $element, $value, $extra]);
  $inserted += 1;
}

$pdo->commit();

echo json_encode(['ok' => true, 'session_key' => $sessionKey, 'inserted' => $inserted]);