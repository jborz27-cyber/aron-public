# Web caption relay (Bluehost / any PHP host)

Streams the **finalized translation** to a public web page so people can read it
on their phones from anywhere — not just the in-room projector. Text only,
~1 second behind the spoken word, and live only while a service is running.

```
operator app (browser)
   → POST /publish            (local Flask server, holds the secret token)
      → POST publish.php       (this folder, on your web host)
         → captions.json       (tiny rolling store)
   viewers' phones  ← poll feed.php ← index.html   (this folder)
```

The operator app never goes on the public internet; your web host only ever
sees finalized caption text. The publish token stays server-side.

## Files

| File | Role |
|------|------|
| `index.html` | The public viewer page people open on their phones. |
| `publish.php` | Receives one caption from the operator (token-protected); archives a service on Clear. |
| `feed.php` | Serves the current captions to viewers (public read, ~1s poll). |
| `clip.php` | Receives one sentence's spoken mp3 (token-protected); optional audio. |
| `archive.php` | Key-gated index of past services. |
| `session.php` | Key-gated replay of one past service (and the only reader of archived files). |
| `config.sample.php` | Template — copy to `config.php` and set the tokens. |
| `.htaccess` | Blocks direct download of `config.php` and the JSON store. |
| `captions.json` | Auto-created rolling store (do not upload; not committed). |
| `clips/` | Auto-created; the live service's spoken clips (public, like captions). |
| `archive/` | Auto-created; past services, each in a `YYYY-MM-DD_HHMM/` folder (served only via `session.php`). |

## Setup (once)

1. **Upload this folder** to your web root, e.g. `public_html/captions/`, so it is
   reachable at `https://yourchurch.org/captions/`. Upload however you like (SFTP,
   cPanel, `scp`) — or, with SSH key access, run `python scripts/deploy_captions.py`
   from the project (configured by `CT_DEPLOY_*` in `.env`; it uploads only the code
   and never overwrites your `config.php` or the live data). See the main README's
   "On the web" section.
2. **Create `config.php`** next to the others (copy `config.sample.php`) and set a
   long random token:
   ```php
   const PUBLISH_TOKEN = 'a-long-random-string-you-generate';
   ```
   Generate one with: `python -c "import secrets; print(secrets.token_urlsafe(32))"`
3. **Point the operator app at it** — in the app's `.env`:
   ```
   CT_PUBLISH_URL=https://yourchurch.org/captions/publish.php
   CT_PUBLISH_TOKEN=<the same token as in config.php>
   ```
   Restart the app. A **Web** checkbox appears in the top bar (only when these are
   set). Tick it to stream; the choice is remembered.
4. **Set the page heading** (optional) — edit `CHURCH_NAME` near the top of
   `index.html`'s script to your church's name.
5. Make sure the folder is writable by PHP so `captions.json` — and, if you use
   audio/archive, the `clips/` and `archive/` subfolders — can be created
   (typically automatic on cPanel/Bluehost, where PHP runs as your user).

## Using it

- Tick **Web** in the operator app before or during a service. Each finalized
  caption — and any hand-edit — appears on the viewer within ~1 second.
- **Clear** in the app wipes the viewer wall too (and rolls the finished service
  into the archive — see below).
- Share `https://yourchurch.org/captions/` (a QR code in the bulletin works well).

## Spoken audio (optional)

If the operator app also has **TTS configured** (`ELEVENLABS_API_KEY` or Soniox),
ticking **Web** streams the translated **voice** as well as the text. Each
finished sentence's mp3 is sent to `clip.php` and stored under
`clips/<epoch>/<id>.mp3`; the viewer page shows a **Listen** button once a service
has audio. Tapping it (the tap is also what lets mobile browsers play sound) hears
the service live, ~a sentence behind the captions. Nothing extra to configure —
the operator app derives the clip endpoint from your `CT_PUBLISH_URL`
automatically. With TTS off, no audio is sent and the page stays text-only.

## Archive (optional)

When the operator presses **Clear** at the end of a service, that service — its
captions and any clips — is rolled into `archive/<YYYY-MM-DD_HHMM>/`
(host-local time) so it can be read back and replayed later. To enable browsing:

1. In `config.php`, set **`ARCHIVE_KEY`** to a long random string **different**
   from `PUBLISH_TOKEN`:
   ```php
   const ARCHIVE_KEY = 'a-different-long-random-string';
   ```
   Generate one with: `php -r "echo bin2hex(random_bytes(16));"`
2. Browse at `https://yourchurch.org/captions/archive.php?k=<ARCHIVE_KEY>`.

The archive is **not publicly listed**: `archive.php`/`session.php` require the
key, and `.htaccess` denies direct access to the stored files (they're served only
through `session.php`). The newest **`KEEP_SESSIONS`** services (default 20) are
kept; older ones are pruned automatically on Clear. A service is archived only
when Clear is pressed, so press Clear at the end of each service.

## Notes & security

- `feed.php` is public with no token — captions are meant to be read by the room.
- `publish.php` requires the token; a request without it is refused (403).
- The relay is best-effort: if the web host is unreachable, the in-room captions
  and projector are unaffected — only the website misses that line.
- Some shared hosts (Bluehost's mod_security) reject the default
  `python-requests` user agent with **HTTP 406**; the app already sends an
  ordinary user agent to avoid this.
- Rotate the token by changing it in **both** `config.php` and `.env`.
