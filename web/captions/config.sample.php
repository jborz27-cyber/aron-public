<?php
// Church Translate — live-caption relay config.
//
// Copy this file to config.php (same folder) and set a long random token.
// The SAME token must be set as CT_PUBLISH_TOKEN in the operator app's .env,
// so only the operator's server can publish captions. feed.php (read) needs no
// token — captions are public to viewers by design.
//
// config.php is server-side PHP: its contents are never sent to a browser, and
// .htaccess denies direct download as a second line of defence.

const PUBLISH_TOKEN = 'CHANGE_ME_TO_A_LONG_RANDOM_STRING';

// Where the caption store lives. Kept next to these scripts; .htaccess blocks
// direct download. PHP must be able to create/write it (the folder needs to be
// writable, typically dir 0755 and PHP creates the file 0644).
const STORE_FILE = __DIR__ . '/captions.json';

// Keep at most this many recent segments (a late-joining phone still sees
// recent context; the viewer only shows the last few on screen).
const MAX_SEGS = 200;

// ---- Spoken audio (optional) ------------------------------------------------
// When the operator app streams the translated VOICE too, each finalized
// sentence's mp3 is POSTed to clip.php (same token as above) and stored under
// CLIPS_DIR/<epoch>/<id>.mp3. Viewers can then tap "Listen" to hear the service
// live, ~a sentence behind the text. Purely additive: with audio off, nothing
// here is touched and the page stays text-only.
const CLIPS_DIR = __DIR__ . '/clips';   // live session's clips (public, like captions)
const CLIP_MAX_BYTES = 4 * 1024 * 1024; // reject anything larger than one sentence

// ---- Archive (optional) -----------------------------------------------------
// On Clear (a service ending), the finished session -- its captions and, if any,
// its clips -- is rolled into ARCHIVE_DIR/<YYYY-MM-DD_HHMM>/ so past services can
// be browsed and replayed. The archive is NOT publicly listed: archive.php and
// session.php require ARCHIVE_KEY in the URL (?k=...), and .htaccess denies
// direct access to the stored files, which are served only through session.php.
const ARCHIVE_DIR = __DIR__ . '/archive';
const KEEP_SESSIONS = 20;               // prune older services beyond this many
// A long random string, DIFFERENT from PUBLISH_TOKEN. Anyone with this key can
// browse every archived service, so treat the archive link as unlisted-but-shared.
// Generate one with: php -r "echo bin2hex(random_bytes(16));"
const ARCHIVE_KEY = 'CHANGE_ME_TO_A_DIFFERENT_LONG_RANDOM_STRING';
