<?php
// Church Translate — archived-service replay (key-gated). Reads one session from
// ARCHIVE_DIR/<stamp>/session.json and lets you read it back with its captions
// and, where present, the spoken clips. Also the ONLY way the archived clips are
// served: the archive folder is htaccess-denied, so ?clip=<id> streams the mp3
// through here after the key check. Requires the same ARCHIVE_KEY as archive.php.

require __DIR__ . '/config.php';
if (!defined('ARCHIVE_DIR')) define('ARCHIVE_DIR', __DIR__ . '/archive');

$key = (string) ($_GET['k'] ?? '');
$configured = defined('ARCHIVE_KEY') ? (string) ARCHIVE_KEY : '';
$placeholder = ($configured === '' || $configured === 'CHANGE_ME_TO_A_DIFFERENT_LONG_RANDOM_STRING');
if ($placeholder || !hash_equals($configured, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo $placeholder ? "Archive is not configured (set ARCHIVE_KEY in config.php)." : "Not authorized.";
    exit;
}

// The service folder. Sanitised hard — it indexes a directory.
$stamp = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_GET['s'] ?? ''));
$base  = ARCHIVE_DIR . '/' . $stamp;
if ($stamp === '' || !is_dir($base)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "No such service.";
    exit;
}

// --- clip byte server: ?clip=<id> streams one archived mp3 --------------------
if (isset($_GET['clip'])) {
    $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['clip']);
    $path = $base . '/clips/' . $id . '.mp3';
    if ($id === '' || !is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=3600');
    header('Accept-Ranges: none');
    readfile($path);
    exit;
}

$m = json_decode((string) @file_get_contents($base . '/session.json'), true);
if (!is_array($m)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Service manifest unreadable.";
    exit;
}
$k = rawurlencode($key);
$segs = array_values(array_filter($m['segs'] ?? [], fn($s) => trim((string)($s['text'] ?? '')) !== ''));
$when = date('D, M j, Y · g:i A', (int) ($m['started'] ?? 0));

function h($s){ return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= h($when) ?> — archive</title>
<style>
  :root{ --bg:#0b0d10; --panel:#111418; --text:#f2f4f7; --dim:#9aa4b2; --accent:#6ba7ff;
         --live:#ffffff; --line:rgba(128,128,128,.18); }
  @media (prefers-color-scheme: light){
    :root{ --bg:#f6f7f9; --panel:#ffffff; --text:#14181d; --dim:#5b6572; --accent:#2f6fed; --live:#0b0d10; }
  }
  *{ box-sizing:border-box; }
  html,body{ margin:0; }
  body{ background:var(--bg); color:var(--text); min-height:100dvh;
        font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        -webkit-font-smoothing:antialiased; }
  header{ position:sticky; top:0; background:var(--bg); z-index:2;
          padding:.85rem 1.1rem; padding-top:calc(.85rem + env(safe-area-inset-top));
          border-bottom:1px solid var(--line); display:flex; align-items:center; gap:.7rem; }
  .back{ color:var(--dim); text-decoration:none; font-size:1.4rem; line-height:1; }
  .ttl{ font-weight:600; font-size:1rem; }
  .sub{ color:var(--dim); font-size:.82rem; }
  .play{ margin-left:auto; border:1px solid var(--accent); color:var(--accent); background:transparent;
         font:inherit; font-size:.9rem; font-weight:600; padding:.35rem .8rem; border-radius:999px;
         cursor:pointer; -webkit-tap-highlight-color:transparent; }
  .play.on{ background:var(--accent); color:#fff; }
  .play[disabled]{ opacity:.4; cursor:default; }
  main{ padding:1rem 1.1rem 3rem; max-width:760px; margin:0 auto;
        display:flex; flex-direction:column; gap:.55rem; }
  .seg{ display:flex; gap:.6rem; align-items:flex-start; color:var(--text);
        font-size:clamp(1.1rem,3.3vw,1.35rem); line-height:1.5; overflow-wrap:anywhere;
        padding:.35rem .4rem; border-radius:.5rem; transition:background .25s; }
  .seg.playing{ background:color-mix(in srgb, var(--accent) 16%, transparent); }
  .seg .pin{ flex:0 0 auto; width:1.7rem; height:1.7rem; margin-top:.1rem; border-radius:50%;
             border:1px solid var(--line); background:transparent; color:var(--accent);
             font-size:.85rem; cursor:pointer; display:inline-flex; align-items:center;
             justify-content:center; -webkit-tap-highlight-color:transparent; }
  .seg .pin.mute{ visibility:hidden; }
  .empty{ color:var(--dim); }
</style>
</head>
<body>
  <header>
    <a class="back" href="archive.php?k=<?= h($k) ?>" title="All services">‹</a>
    <div>
      <div class="ttl"><?= h($when) ?></div>
      <div class="sub"><?= count($segs) ?> lines<?= !empty($m['clips']) ? ' · has audio' : '' ?></div>
    </div>
    <button class="play" id="playAll" type="button"<?= empty($m['clips']) ? ' disabled title="No audio in this service"' : '' ?>>▶ Play all</button>
  </header>
  <main id="list">
  <?php if (!$segs): ?>
    <p class="empty">This service has no captions.</p>
  <?php else: foreach ($segs as $s):
      $hasAudio = !empty($s['audio']);
      $id = h($s['id'] ?? '');
  ?>
    <div class="seg" data-id="<?= $id ?>"<?= $hasAudio ? ' data-audio="1"' : '' ?>>
      <button class="pin<?= $hasAudio ? '' : ' mute' ?>" type="button" title="Play this line"<?= $hasAudio ? '' : ' tabindex="-1" aria-hidden="true"' ?>>▶</button>
      <span class="txt"><?= h($s['text']) ?></span>
    </div>
  <?php endforeach; endif; ?>
  </main>
<script>
// Sequential playback over the archived clips, in caption order. One reused
// <audio>; each clip is fetched through session.php?clip=<id> (the archive files
// aren't directly reachable). "Play all" runs the service start to finish; a line's
// ▶ starts from that line. The current line is highlighted and scrolled into view.
(function(){
  const CLIP = <?= json_encode('session.php?s=' . rawurlencode($stamp) . '&k=' . $k . '&clip=', JSON_UNESCAPED_SLASHES) ?>;
  const rows = Array.from(document.querySelectorAll('.seg[data-audio="1"]'));
  const playAll = document.getElementById('playAll');
  let audio = null, idx = -1;

  function ensure(){
    if(audio) return audio;
    audio = new Audio();
    audio.addEventListener('ended', () => playFrom(idx + 1));
    audio.addEventListener('error', () => playFrom(idx + 1));   // skip a bad clip
    return audio;
  }
  function highlight(row){
    document.querySelectorAll('.seg.playing').forEach(e => e.classList.remove('playing'));
    if(row){ row.classList.add('playing');
             row.scrollIntoView({block:'center', behavior:'smooth'}); }
  }
  function stop(){
    if(audio){ try{ audio.pause(); }catch(e){} }
    idx = -1; highlight(null);
    playAll.classList.remove('on'); playAll.textContent = '▶ Play all';
  }
  function playFrom(i){
    if(i < 0 || i >= rows.length){ stop(); return; }
    idx = i;
    const row = rows[i];
    highlight(row);
    playAll.classList.add('on'); playAll.textContent = '⏸ Stop';
    ensure().src = CLIP + encodeURIComponent(row.dataset.id);
    audio.play().catch(() => {});   // gesture lost — leave it highlighted, silent
  }
  playAll.addEventListener('click', () => {
    if(playAll.classList.contains('on')) stop();
    else playFrom(0);
  });
  rows.forEach((row, i) => {
    row.querySelector('.pin').addEventListener('click', () => {
      if(idx === i && playAll.classList.contains('on')) stop();
      else playFrom(i);
    });
  });
})();
</script>
</body>
</html>
