<?php
// Church Translate — archive index (key-gated). Lists past services rolled off
// by publish.php on Clear. NOT public: a valid ARCHIVE_KEY must be supplied as
// ?k=... (the archive holds spoken content from services, so the link is meant
// to be shared deliberately, not indexed). Each row links to session.php, which
// is the only thing that reads the (htaccess-denied) archived files.

require __DIR__ . '/config.php';
if (!defined('ARCHIVE_DIR')) define('ARCHIVE_DIR', __DIR__ . '/archive');

// Refuse until a real key is configured, and on any mismatch. A constant-time
// compare so the key can't be probed a character at a time.
$key = (string) ($_GET['k'] ?? '');
$configured = defined('ARCHIVE_KEY') ? (string) ARCHIVE_KEY : '';
$placeholder = ($configured === '' || $configured === 'CHANGE_ME_TO_A_DIFFERENT_LONG_RANDOM_STRING');
if ($placeholder || !hash_equals($configured, $key)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo $placeholder ? "Archive is not configured (set ARCHIVE_KEY in config.php)." : "Not authorized.";
    exit;
}
$k = rawurlencode($key);

// Gather sessions, newest first. Each is a folder with a session.json manifest.
$rows = [];
foreach (glob(ARCHIVE_DIR . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
    $m = json_decode((string) @file_get_contents($dir . '/session.json'), true);
    if (!is_array($m)) continue;
    $rows[] = $m + ['_dir' => basename($dir)];
}
usort($rows, fn($a, $b) => ($b['started'] ?? 0) <=> ($a['started'] ?? 0));

function h($s){ return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function dur_txt($secs){
    $secs = max(0, (int) $secs);
    $m = intdiv($secs, 60); $s = $secs % 60;
    return $m >= 60 ? sprintf('%dh %02dm', intdiv($m, 60), $m % 60) : ($m ? "{$m}m {$s}s" : "{$s}s");
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Service archive</title>
<style>
  :root{ --bg:#0b0d10; --panel:#111418; --text:#f2f4f7; --dim:#9aa4b2; --accent:#6ba7ff;
         --line:rgba(128,128,128,.18); }
  @media (prefers-color-scheme: light){
    :root{ --bg:#f6f7f9; --panel:#ffffff; --text:#14181d; --dim:#5b6572; --accent:#2f6fed; }
  }
  *{ box-sizing:border-box; }
  html,body{ margin:0; }
  body{ background:var(--bg); color:var(--text); min-height:100dvh;
        font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
        -webkit-font-smoothing:antialiased; }
  header{ padding:1rem 1.1rem; padding-top:calc(1rem + env(safe-area-inset-top));
          border-bottom:1px solid var(--line); }
  h1{ margin:0; font-size:1.15rem; }
  .sub{ color:var(--dim); font-size:.9rem; margin-top:.15rem; }
  main{ padding:.6rem 1.1rem 2rem; max-width:720px; margin:0 auto; }
  a.row{ display:flex; align-items:baseline; gap:.6rem; text-decoration:none; color:inherit;
         padding:.85rem .2rem; border-bottom:1px solid var(--line); }
  a.row:hover{ color:var(--accent); }
  .when{ font-weight:600; font-size:1.02rem; }
  .meta{ margin-left:auto; color:var(--dim); font-size:.85rem; white-space:nowrap; }
  .empty{ color:var(--dim); padding:2rem .2rem; }
  .snip{ color:var(--dim); font-size:.9rem; margin-top:.15rem; overflow:hidden;
         text-overflow:ellipsis; white-space:nowrap; max-width:100%; }
</style>
</head>
<body>
  <header>
    <h1>Service archive</h1>
    <div class="sub"><?= count($rows) ?> service<?= count($rows) === 1 ? '' : 's' ?> saved</div>
  </header>
  <main>
  <?php if (!$rows): ?>
    <p class="empty">No services archived yet. A service is saved when the operator presses Clear at the end.</p>
  <?php else: foreach ($rows as $r):
      $stamp = $r['stamp'] ?? $r['_dir'];
      $when  = date('D, M j, Y · g:i A', (int) ($r['started'] ?? 0));
      $first = '';
      foreach ($r['segs'] ?? [] as $sg){ if (trim((string)($sg['text'] ?? '')) !== ''){ $first = $sg['text']; break; } }
  ?>
    <a class="row" href="session.php?s=<?= h(rawurlencode($stamp)) ?>&k=<?= h($k) ?>">
      <span>
        <span class="when"><?= h($when) ?></span>
        <?php if ($first !== ''): ?><div class="snip"><?= h(mb_strimwidth($first, 0, 90, '…')) ?></div><?php endif; ?>
      </span>
      <span class="meta">
        <?= (int) ($r['count'] ?? count($r['segs'] ?? [])) ?> lines<?php
          if (!empty($r['clips'])) echo ' · 🔊'; ?> · <?= h(dur_txt($r['duration'] ?? 0)) ?>
      </span>
    </a>
  <?php endforeach; endif; ?>
  </main>
</body>
</html>
