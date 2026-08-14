<?php
/**
 * Job-tracker ingest API — how the 6 AM scheduled run files its finds.
 *
 * GET  ?k=<key>            → the tracked list (company, role, url, status),
 *                            which doubles as the run's skip list: anything
 *                            already here doesn't get re-surfaced.
 * POST ?k=<key> + JSON     → insert a new application as status "found",
 *                            with the drafted cover-letter body attached.
 *                            Duplicates (same URL, or same company+role)
 *                            are refused, so re-runs are harmless.
 *
 * The key lives in config.php ('jobtracker_key'), which never enters git —
 * unlike the read-only jobwatch key, this endpoint writes, so its secret
 * can't sit in a public repo. If the config key is missing, the endpoint
 * plays dead (404), same as a wrong key.
 */

declare(strict_types=1);

define('APP_DIR', is_dir(__DIR__ . '/../../app') ? __DIR__ . '/../../app' : __DIR__ . '/../app');

require APP_DIR . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$key = config()['jobtracker_key'] ?? '';
if ($key === '' || !hash_equals($key, (string) ($_GET['k'] ?? ''))) {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

/* GET with no payload → the tracked list. GET with ?add=<base64 JSON> →
   file an application. The GET filing path exists because the scheduled
   run's sandbox can only reach this site through a read-only fetch proxy:
   its GETs arrive, its POSTs never leave. Same validation either way. */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['add'] ?? '') === '') {
    $rows = db()->query('SELECT company, role, url, status FROM applications ORDER BY id DESC')->fetchAll();
    echo json_encode(['tracked' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Accept base64url, and undo the +→space mangling URL decoding does
    // to standard base64.
    $b64 = strtr(str_replace(' ', '+', (string) $_GET['add']), '-_', '+/');
    $raw = base64_decode($b64, true);
    if ($raw === false) {
        http_response_code(400);
        echo json_encode(['error' => 'add must be base64-encoded JSON']);
        exit;
    }
    // Payloads may be gzipped before encoding: the scheduled run's fetch
    // proxy caps URL length, and letters don't fit raw. Sniff the magic.
    if (str_starts_with($raw, "\x1f\x8b")) {
        $raw = @gzdecode($raw) ?: '';
    }
    $in = json_decode($raw, true);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = json_decode((string) file_get_contents('php://input'), true);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}
if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['error' => 'body must be JSON']);
    exit;
}

$field = fn(string $name, int $max) => mb_substr(trim((string) ($in[$name] ?? '')), 0, $max);
$company = $field('company', 190);
$role    = $field('role', 190);
$comp    = $field('comp', 190);
$remote  = $field('remote', 190);
$url     = $field('url', 500);
$notes   = $field('notes', 5000);
$letter  = $field('letter', 20000);

if ($company === '' || $role === '') {
    http_response_code(422);
    echo json_encode(['error' => 'company and role are required']);
    exit;
}

/* Refuse duplicates: same URL (when given), or same company + role. */
if ($url !== '') {
    $stmt = db()->prepare('SELECT id FROM applications WHERE url = ? LIMIT 1');
    $stmt->execute([$url]);
    if ($stmt->fetch()) { echo json_encode(['added' => false, 'reason' => 'url already tracked']); exit; }
}
$stmt = db()->prepare('SELECT id FROM applications WHERE company = ? AND role = ? LIMIT 1');
$stmt->execute([$company, $role]);
if ($stmt->fetch()) { echo json_encode(['added' => false, 'reason' => 'company+role already tracked']); exit; }

$stmt = db()->prepare(
    'INSERT INTO applications (company, role, comp, remote, url, status, applied_on, notes, letter)
     VALUES (?,?,?,?,?, "found", NULL, ?, ?)'
);
$stmt->execute([$company, $role, $comp, $remote, $url, $notes, $letter]);

echo json_encode(['added' => true, 'id' => (int) db()->lastInsertId()]);
