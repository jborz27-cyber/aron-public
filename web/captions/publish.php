<?php
// Church Translate — publish endpoint (operator -> Bluehost).
//
// The operator app's Flask server POSTs one finalized caption here (server to
// server, so the token never touches a browser and there is no CORS). Body is
// JSON: {"id","text","done"} to add/update a segment, or {"action":"clear"} to
// wipe the wall between services. Auth: X-Publish-Token header must equal
// PUBLISH_TOKEN.
//
// Store (captions.json): {"epoch","rev","segs":[{"id","text","done"}]}.
//   epoch  bumped on clear, so the viewer knows to reset its screen
//   rev    monotonic; every change bumps it, so feed.php can short-circuit polls
//   segs   last MAX_SEGS segments, in order; an edit updates the segment in place
//          under the same id (never a duplicate).

require __DIR__ . '/config.php';
// Tolerate a config.php written before audio/archive existed: fall back to sane
// defaults so publishing never fatals on a missing constant. Only ARCHIVE_KEY has
// no safe default — the archive pages refuse to serve until it is set.
if (!defined('CLIPS_DIR'))     define('CLIPS_DIR', __DIR__ . '/clips');
if (!defined('ARCHIVE_DIR'))   define('ARCHIVE_DIR', __DIR__ . '/archive');
if (!defined('KEEP_SESSIONS')) define('KEEP_SESSIONS', 20);

// Recursively delete a directory tree. Used only to prune archived services
// older than KEEP_SESSIONS; never touches anything outside ARCHIVE_DIR.
function ct_rmtree($dir) {
    if (!is_dir($dir)) { @unlink($dir); return; }
    foreach (scandir($dir) as $e) {
        if ($e === '.' || $e === '..') continue;
        $p = $dir . '/' . $e;
        is_dir($p) ? ct_rmtree($p) : @unlink($p);
    }
    @rmdir($dir);
}

// Roll a finished service into ARCHIVE_DIR/<YYYY-MM-DD_HHMM>/ so it can be browsed
// and replayed later: a session.json manifest plus the session's clips, moved out
// of the live clips folder. Called on Clear, before the store is reset. Empty
// services (no captions) are skipped. Best-effort: an archive hiccup must not stop
// the wall being cleared, so failures are swallowed.
function ct_archive_session($store) {
    try {
        $segs = $store['segs'] ?? [];
        if (!is_array($segs) || count($segs) === 0) return;   // nothing spoken — skip
        $epoch = (int) ($store['epoch'] ?? 0);
        if ($epoch <= 0) return;

        if (!is_dir(ARCHIVE_DIR)) {
            @mkdir(ARCHIVE_DIR, 0755, true);
            // The archive is served only through session.php (key-gated); deny any
            // direct download of the manifests and clips inside it.
            @file_put_contents(ARCHIVE_DIR . '/.htaccess',
                "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
        // Folder name is the service's start, host-local. A rare second service in
        // the same minute gets a -2, -3… suffix rather than clobbering the first.
        $stamp = date('Y-m-d_Hi', $epoch);
        $dest  = ARCHIVE_DIR . '/' . $stamp;
        for ($i = 2; is_dir($dest); $i++) $dest = ARCHIVE_DIR . '/' . $stamp . '-' . $i;
        if (!@mkdir($dest, 0755, true)) return;

        // Move the session's clips (if any) alongside the manifest, and note which
        // segments have audio so the replay page knows what to offer.
        $withAudio = 0;
        $src = CLIPS_DIR . '/' . $epoch;
        if (is_dir($src)) {
            @mkdir($dest . '/clips', 0755, true);
            foreach (scandir($src) as $e) {
                if ($e === '.' || $e === '..') continue;
                if (@rename($src . '/' . $e, $dest . '/clips/' . $e) && substr($e, -4) === '.mp3') $withAudio++;
            }
            ct_rmtree($src);
        }

        $manifest = [
            'stamp'    => $stamp,
            'started'  => $epoch,
            'ended'    => (int) ($store['t1'] ?? $epoch),
            'duration' => max(0, (int) ($store['t1'] ?? $epoch) - $epoch),
            'segs'     => array_values($segs),
            'count'    => count($segs),
            'clips'    => $withAudio,
        ];
        @file_put_contents($dest . '/session.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Prune the oldest services beyond KEEP_SESSIONS.
        $all = glob(ARCHIVE_DIR . '/*', GLOB_ONLYDIR) ?: [];
        if (count($all) > KEEP_SESSIONS) {
            sort($all);   // stamps sort chronologically
            foreach (array_slice($all, 0, count($all) - KEEP_SESSIONS) as $old) ct_rmtree($old);
        }
    } catch (\Throwable $e) {
        // swallow — clearing the wall must always succeed
    }
}

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$token = $_SERVER['HTTP_X_PUBLISH_TOKEN'] ?? ($body['token'] ?? '');
if (!hash_equals(PUBLISH_TOKEN, (string) $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'bad token']);
    exit;
}

$fp = fopen(STORE_FILE, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'store not writable']);
    exit;
}
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$store = json_decode($raw, true);
if (!is_array($store) || !isset($store['segs'])) {
    $store = ['epoch' => time(), 'rev' => 0, 't1' => time(), 'segs' => []];
}

$action = $body['action'] ?? 'seg';
if ($action === 'clear') {
    ct_archive_session($store);   // roll the finished service off to the archive first
    $store = ['epoch' => time(), 'rev' => $store['rev'] + 1, 't1' => time(), 'segs' => []];
} else {
    $id = (string) ($body['id'] ?? '');
    if ($id === '') {
        flock($fp, LOCK_UN);
        fclose($fp);
        http_response_code(400);
        echo json_encode(['error' => 'missing id']);
        exit;
    }
    $text = (string) ($body['text'] ?? '');
    if (mb_strlen($text) > 2000) $text = mb_substr($text, 0, 2000);  // display text; never long
    $done = !empty($body['done']);

    $found = false;
    foreach ($store['segs'] as &$s) {                 // edit-in-place by id
        if (($s['id'] ?? null) === $id) {
            $s['text'] = $text;
            $s['done'] = $done;
            $found = true;
            break;
        }
    }
    unset($s);
    if (!$found) $store['segs'][] = ['id' => $id, 'text' => $text, 'done' => $done];
    if (count($store['segs']) > MAX_SEGS) {
        $store['segs'] = array_slice($store['segs'], -MAX_SEGS);
    }
    $store['rev']++;
    $store['t1'] = time();   // last activity — the archive uses it for duration
}

rewind($fp);
ftruncate($fp, 0);
fwrite($fp, json_encode($store, JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['ok' => true, 'rev' => $store['rev'], 'epoch' => $store['epoch']]);
