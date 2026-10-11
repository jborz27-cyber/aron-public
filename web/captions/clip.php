<?php
// Church Translate — clip endpoint (operator -> Bluehost). Companion to
// publish.php: where that carries one caption's TEXT, this carries the same
// sentence's spoken mp3, so viewers can tap "Listen" and hear the service live.
//
// The operator app POSTs here server-to-server (token in X-Publish-Token, so it
// never touches a browser). Body is JSON: {"id","audio_b64"} — the segment id
// matching a caption already published via publish.php, and the mp3 bytes
// base64-encoded. We file the clip under the LIVE session's epoch (read from the
// caption store, so text and audio always land in the same session) and flag
// that segment audio:true so feed.php tells viewers a clip is ready.
//
// Best-effort by design: any failure returns a JSON error the operator just
// logs. A missed clip costs one unspoken sentence, never a caption.

require __DIR__ . '/config.php';
// Tolerate a config.php written before audio existed: fall back to sane defaults
// so uploading a clip never fatals on a missing constant.
if (!defined('CLIPS_DIR'))      define('CLIPS_DIR', __DIR__ . '/clips');
if (!defined('CLIP_MAX_BYTES')) define('CLIP_MAX_BYTES', 4 * 1024 * 1024);

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

// id: the caption segment this audio belongs to. Sanitised hard — it becomes a
// filename, so allow only the app's own id alphabet and nothing path-like.
$id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($body['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing id']);
    exit;
}

$audio = base64_decode((string) ($body['audio_b64'] ?? ''), true);
if ($audio === false || $audio === '') {
    http_response_code(400);
    echo json_encode(['error' => 'bad audio']);
    exit;
}
if (strlen($audio) > CLIP_MAX_BYTES) {
    http_response_code(413);
    echo json_encode(['error' => 'clip too large']);
    exit;
}

// The session this clip belongs to is whatever the caption store's epoch says
// right now: publish.php stamps a fresh epoch on every Clear, so reading it here
// files the audio under the same service as its text with no extra bookkeeping.
$epoch = 0;
if (is_file(STORE_FILE)) {
    $d = json_decode((string) file_get_contents(STORE_FILE), true);
    if (is_array($d) && isset($d['epoch'])) $epoch = (int) $d['epoch'];
}
if ($epoch <= 0) $epoch = time();   // no session open yet — shouldn't happen, but keep the bytes

$dir = CLIPS_DIR . '/' . $epoch;
if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
    http_response_code(500);
    echo json_encode(['error' => 'clip dir not writable']);
    exit;
}

// Write atomically: a viewer must never fetch a half-written mp3.
$path = $dir . '/' . $id . '.mp3';
$tmp  = $path . '.' . getmypid() . '.tmp';
if (file_put_contents($tmp, $audio) === false || !@rename($tmp, $path)) {
    @unlink($tmp);
    http_response_code(500);
    echo json_encode(['error' => 'clip not written']);
    exit;
}

// Flag the segment audio:true in the store (LOCK_EX, same as publish.php, so the
// two writers serialise) and bump rev so feed.php pushes the change and viewers
// learn the clip is ready. If the segment isn't present yet the file still
// stands; the flag simply isn't set (the text-first ordering makes this rare).
$flagged = false;
$fp = fopen(STORE_FILE, 'c+');
if ($fp) {
    flock($fp, LOCK_EX);
    $store = json_decode(stream_get_contents($fp), true);
    if (is_array($store) && isset($store['segs'])) {
        foreach ($store['segs'] as &$s) {
            if (($s['id'] ?? null) === $id) { $s['audio'] = true; $flagged = true; break; }
        }
        unset($s);
        if ($flagged) {
            $store['rev'] = ($store['rev'] ?? 0) + 1;
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($store, JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}

echo json_encode(['ok' => true, 'epoch' => $epoch, 'id' => $id, 'flagged' => $flagged]);
