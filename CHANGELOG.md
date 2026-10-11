# Changelog

## [v2.11.3] — 2026-10-11

### Changed
- **Public releases-only distribution.** The Windows build now ships from a
  dedicated public repo as a GitHub Release — the exe plus a `web-captions.zip`
  for the optional phone-caption relay. The release flow gained a privacy gate
  and a public-mirror step. Internal developer comments and changelog notes that
  named test-sermon speakers were generalized; no functional change to the app.

## [v2.11.2] — 2026-10-10

### Changed
- **A Help button at the right end of the top bar opens the quick start guide** (`QUICKSTART.pdf`,
  served at `/help` and bundled in the Windows build). The **A+** large-captions
  button is removed for now to make room.
- **The quick start guide leads with the single .exe**, which needs no Python:
  put it in a folder with a one-line `.env` beside it. Python 3.12 is only for
  running from the source folder (`Start Church Translate.bat`). `deploy.py`'s
  generated DEPLOY.md now lists Soniox as the only required key.
- **The guide shows what `.env` looks like**: a drawn Notepad window with
  made-up keys (name, `=`, key; a blank line means off) and the Save-as
  settings, after an explanation of what the file is and why it stays private.

### Fixed
- **A `.env` saved by Notepad with a byte-order mark lost its first key**: the
  mark glued itself to the key's name, so `SONIOX_API_KEY` was never found.
  The server and the launcher now read the file as `utf-8-sig`, and the exe's
  launcher also finds a file Notepad saved as `.env.txt`.

## [v2.11.1] — 2026-10-10

### Added
- **Cost box** (top right): this run's estimated spend across every API, with a
  per-API breakdown on hover. Token counts come from each provider's own usage
  block (translators and the Router); Soniox audio, voice characters and ear
  audio are what was sent. List prices live in `config/prices.json`
  (`scripts/costs.py`). Checked live: a cached `gpt-6-sol-fast` caption reads
  ~5.3K of its ~5.3K prompt tokens from cache (~$0.002 a caption), and the
  Router's sent-on calls stay cached too.
- **Quick start guide**, `QUICKSTART.pdf` at the top of the folder: a
  seven-page tutorial with screenshots for someone who has never seen the app,
  from getting a Soniox key to the Router, ears, costs and troubleshooting
  (source and screenshot script in `docs/quickstart/`).

### Changed
- **A Soniox key alone works out of the box.** A model whose provider has no key
  is greyed out (Claude included), and a saved pick without one falls to the
  first model with a key, else to **none, fast**. The Windows launcher asks for
  the Soniox key first and lets every other key be skipped (it used to demand an
  Anthropic key); `launcher.py` likewise requires only `SONIOX_API_KEY`.
- **Tooltips are one line** (19,000 characters of them down to ~3,700); the
  measurements they carried live in README, QUICKSTART and `validation/`.
- README and QUICKSTART: the Router, extra ears, costs, the Pace word ranges,
  and which keys do what.
## [v2.11.0] — 2026-10-10

Minor-version milestone rolling up the Router, the extra ears, and the Pace
parser shipped across v2.10.17–v2.10.23, plus the ProPresenter ON/OFF signal
from v2.10.16. No new runtime behaviour of its own — it marks the feature set as
a release and brings the documentation (README, QUICKSTART) in line with it.

### Highlights since v2.10.16
- **The Router.** One translator (the Model menu's) does the captioning; a fast
  check reads each finished segment and only *sends on* the doubtful ones — a
  likely mis-hearing, a shaky translation, or a cuss word — for a closer look,
  keeping Soniox's own reading for the rest. Each caption carries a router chip
  and a who-translated label, and the check's latency shows in the STT panel and
  the Debug waterfall. The offensive-words check is narrowed to actual swearing
  (Bible/preaching language passes, ~24%→~16% of a sermon sent on). The whole
  check runs under one deadline (`CT_ROUTER_TIMEOUT_MS`, default 1200 ms); past
  it, Soniox's text stands. `CT_ROUTER_MODEL` can pin the checker separately.
- **Extra ears.** Up to three more realtime recognizers (ElevenLabs, OpenAI,
  Gemini Live) hear the same audio as *witnesses*. When the Router sends a
  segment on as a likely mis-hearing, each ear's reading of those words goes to
  the translator as a clue; that segment waits briefly — until each ear has the
  last word and one past it, at most `CT_EAR_WAIT_MS` after the seal (default
  700 ms; a **Wait for ears** slider). 20→26 of 27 meaning-changing sermon
  mis-hears corrected at +0.7 s, no harm on controls; the normal pipeline never
  waits. Gemini Live hands over cleanly at its ~10-min session limit; ear
  readings show under their row once final, never mid-word; numbers align as
  words. `CT_EARS=0` hides and disables them all.
- **Pace is the parser.** The classifier cut is the only rule and each Pace stop
  is a word range over it (Pronto ~3–7, Moderato 7–10, Lento 10–14), plus a
  far-left **Soniox** stop that cuts on Soniox's punctuation alone. The
  experimental Cut rule menu is gone (candidates stay replayable in the lab).
  English function words no longer trigger an unmarked cut.
- **ProPresenter box ON/OFF signal** (`CT_PROPRESENTER_SIGNAL`). The Message can
  drive a prop/layer's visibility instead of carrying caption text, firing once
  per on/off edge.

### Changed
- Documentation (README, QUICKSTART) updated for all of the above: a new
  "Catching mis-hears — the Router and extra ears" section, Pace-as-parser
  throughout, every Cut rule menu reference removed, and the cut-rule roadmap
  item closed.

## [v2.10.23] — 2026-10-10

### Fixed
- **The parser no longer cuts English after "the", "of", "my", "will"…** The
  classifier's vocabulary is Romanian, and it learned to hold off after de, la,
  pe, o, un, but not after their English counterparts. On an English sermon
  (Francis Chan, 15 min, every cut hand-graded) Moderato and Lento cut clean
  only 89.8% and 91.2% ("on my | face", "you will | stretch") against ~97% on
  Romanian. It now never cuts at an unmarked gap after a `NO_END` word or an
  English auxiliary, modal, subject pronoun, "that" or "this": Pronto /
  Moderato / Lento 96.5 / 89.8 / 91.2% -> 99.1 / 93.0 / 95.3%. Romanian is
  unchanged, and so are piece sizes and latency.
  `validation/results/2026-10-10-english-pace/`.

### Added
- **A "Soniox" stop at the left of the Pace bar**: cut on Soniox's punctuation
  alone (the Pace rule, no classifier), for a speaker Soniox punctuates well.
  English sermon, hand-graded: every cut clean, 8 words a piece, 1.86 / 4.36 s
  to the translator (classifier Pronto 99.1% with the fix above, 7 words,
  1.74 / 3.99 s). Roman: 96.7% clean, 7 words, 1.59 / 3.37 s, the same as
  Pronto. On a speaker Soniox leaves unpunctuated, pieces run long (up to 24
  words). A saved Pace moves one stop to the right, so it stays the same stop.

## [v2.10.22] — 2026-10-10

### Changed
- **The Pace bar is the parser; the Cut rule menu is gone.** The classifier cut
  (the old menu's Default) is the only rule, and each Pace stop is now a word
  range for it: Pronto about 3-7 words a piece, Moderato 7-10, Lento 10-14 (a
  minimum, then a bar that drops word by word, and a firm cut at 18 / 16 / 21
  words at the best gap so far). Before, the classifier ran with fixed numbers
  and all three stops cut the same pieces (median 6 words on the first sermon,
  8 on the second). Replayed on both sermons: median 6 / 9 / 13 words, about 90% of
  cuts where an interpreter would cut at every stop (unchanged), 1.5 / 2.0 /
  2.6 s median to the translator on the first. Hard caps at the edge of each range
  hit it more often but cut clean only 78-86%. Without the classifier weights
  the assembler falls back to the punctuation rule.
  `validation/results/2026-10-10-pace-words/`.
- A pause no longer ships a piece shorter than the stop's minimum.
- **Wait for ears** is greyed out unless the Router is on (only the Router's
  sent-on segments ever wait).
- Removed from the menu with it: Soniox segments, Soniox segments + classifier
  (they remain replayable in the lab harness).

## [v2.10.21] — 2026-10-10

### Fixed
- **An ear's reading is no longer taken as whole while it is still cut off
  mid-word.** An ear counted as done once any two letters of the packet's last
  word showed up, so an ElevenLabs partial like "Domnului Iis" (for "Domnului
  Iisus Hristos") or "un princip" went to the translator at the seal, unmarked,
  and the Wait for ears slider never came into play. In 26-32% of packets the
  reading sent at a 1.5 s cap was not the ear's settled one (OpenAI live 14%).
  An ear is now done once it has the last word *and a word past it*
  (`witness_align.packet_state`). A stretch short of that goes without "…" only
  if it reaches the last word and has stood unchanged 0.4 s (a pause, or the end
  of the wait).
- **Numbers line up.** Soniox writes "versetul 8", "100%", the ears "versetul
  opt", "sută la sută"; those packets never lined up to their end, so the wait
  ran to its cap and the reading went with "…". Digits are now compared as
  Romanian words (0-9999, "%" as "la sută").
- Whole sermon replay (6vj8kywk4pQ, 1,842 packets, 27 real mis-hears; any ear
  holding the right word): 23 -> 25 at a 0.7 s cap; 1.0 s and 1.5 s held it no
  more often (24). Translator end to end (gpt-6-sol-fast, 0.7 s): one more
  mis-hear right ("Nu lași morții…", 4 of 5 reruns vs 0), the rest the same.
  The wait on a "misheard" send now mostly runs to the cap (median 0.4 -> 0.7
  s). **Set Wait for ears back to 0.7 s** if it was raised.
  `validation/results/2026-10-10-ear-done/`.

## [v2.10.20] — 2026-10-09

### Changed
- **Gemini Live hands over to a new session instead of being cut off.**
  Gemini ends a Live session at ~10 minutes and says so 50 s ahead (`goAway`,
  measured on the 2026-10-09 sermon: at 540 s, cut at ~590 s with a 1008, then a
  ~1 s reconnect). The ear now opens the next session as soon as the warning
  comes, moves the audio over at the next packet seal (no word split between
  sessions), tells the old one no more audio follows and closes it after a 3 s
  grace in which it delivers its last words. Generic in `ears.Ear`
  (`handover`, `end_audio()`); only Gemini asks for it today.
- **The extra ears' text no longer streams on the page.** Each ear's reading of
  a row appears under it once final (the ear has reached the row's last word);
  the live line, the "…" placeholders and the server's per-partial relay of
  every ear's running text (a websocket message per partial, beside Soniox's)
  are gone. A row sent on by the Router still shows exactly what the
  translator was given, tagged "sent".

### Fixed
- `wet_ink_harness.js` stubs `selectedEars()`: every scenario had failed since
  the ears' packet code started asking which ears are ticked (now 19 clean, 4
  known limits).

## [v2.10.19] — 2026-10-09

### Changed
- **The Router's offensive-words check is narrowed to cuss words.** It used to
  send on anything remotely offensive or sensitive, Bible words included -- 148
  of 1,842 segments (8%) of a sermon on Lucifer and pride, none of them a cuss
  word. It now asks about swear words, expletives ("damn", "what the hell"),
  vulgarity and slurs, with Bible and preaching words named as fine. On the
  2026-10-08 profanity set: 8 of 8 cuss lines still flagged, 0 of 10
  Bible-language lines (the broad check flagged all 18); on the sermon: 0 of
  the 148, 0 of 200 others. Segments sent on: 23.8% -> 15.7% of the sermon.
  It still catches 23 of its 27 real mis-hears (was 24; the one lost had only
  been sent on for the word "naked").
- **Wait for ears is a slider** (Extra ears row, 0-1.5 s, default the server's
  `CT_EAR_WAIT_MS`), greyed out while no ear is ticked, and saved with the other
  settings. **Only a segment the Router sends on as a likely mis-hearing waits**:
  every case the wait helped on the sermon was one; a segment sent on for a bad
  translation or a cuss word takes the ears' readings as they stand.

### Not built
- A "two of three ears agree against Soniox" Router trigger: read when the
  Router's check returns, it added 105 segments sent on (+5.7%) for one more
  mis-hear (`validation/scripts/ear_sermon_consensus.py`).

## [v2.10.18] — 2026-10-09

### Changed
- **A segment the Router sends on waits (briefly) for the extra ears.** Its
  translation used to take each ear's reading as it stood the moment the check
  came back (~0.15 s after the seal), often before the ear had heard the word
  that mattered. `/translate` now takes `ear_wait_ms` -- sent by the page for
  the Router's sent-on segments only -- and waits until each ear has reached the
  segment's last word, at most `CT_EAR_WAIT_MS` (default 700) after the seal,
  then lines up any ear still short once more on its newest text. Measured on a
  whole 82-minute sermon (27 meaning-changing Soniox mis-hears, gpt-6-sol-fast):
  20 right without ears, 21 with all three ears as of +0.15 s, 25 at +0.4 s, 26
  at +0.7 s; no harm on 50 control segments. Live: waits of 0.00-0.57 s. The
  normal pipeline is unchanged (it never waits).
- The packet threads re-align each ear every 0.2 s, or four times as long as
  its last alignment took (was a flat 0.4 s): prompt for ~5-word Router
  packets, light for long rows.

### Added
- `validation/results/2026-10-09-ear-sermon/`: which extra ears complement
  Soniox on a whole sermon. Gemini Live is the strongest ear (16 of 27 at
  +0.4 s, 20 at +1 s) and the least noisy; ElevenLabs and OpenAI add the rest
  (all three: 25 at +0.7 s). Committing ears at each seal makes all of them
  worse, which also rules out timestamp alignment (only committed ElevenLabs
  gives word times). The Router already sends on 24 of the 27.

## [v2.10.17] — 2026-10-09

### Fixed
- **Router context no longer overwrites a later segment.** A sent-on
  segment's new English replaced whatever was last in the Router's context
  when its translation came back -- by then usually a later segment -- so that
  segment vanished from the context and the sent-on one appeared twice. Each
  segment now updates its own entry.
- **`.env`: a blank value followed by a comment is blank.** `KEY=   # note`
  was read as `# note`, which on a copy of `.env.example` switched ProPresenter
  ON with the comment as its URL. The Windows launcher, `model_pins.py` and
  `deploy_captions.py` did not strip inline comments at all, and the launcher's
  values win over the server's -- so a filled-in `CT_PROPRESENTER_URL=... # ...`
  reached the server with the comment attached. All four readers now follow
  the same rule.
- **An abandoned `/translate` stops speaking.** When the page aborted a call
  (a stall before "final", the audio deadline after it), the voice worker went
  on synthesizing -- and recording -- the sentences it had been handed, a
  resend then spoke them again, and the worker thread never exited. It now
  stops after the chunk in progress (checked offline with a fake translator
  and voice: 9 chunks after an abort before, 1 now).
- **Extra ears:** the queued-audio count is updated under a lock (three
  threads touch it; a drifting count would make every packet seal throw away
  unsent audio), and the OpenAI ear's item list is guarded at a reconnect.
- **Router:** the two checks share one deadline instead of waiting for each
  in turn.

### Changed
- **Ear alignment costs a fraction of the CPU.** Lining an ear's text up with
  a packet skips windows that cannot beat the best so far (difflib's upper
  bounds), giving identical results on 1,500 random cases at 2.5-4x the speed
  on a Pi 5 (25-word packet: 185 -> 53 ms), and each ear is re-aligned at most
  every 0.4 s instead of 0.2 s. Three ears had been enough to keep a core busy
  under the GIL the Soniox relay shares.
- `.env.example` lists the seven settings the code read but it did not
  (`CT_ROUTER_MODEL`, `CT_SONIOX_MODEL`, `CT_EL_TTS_MODEL`, `CT_UNSURE_BELOW`,
  `CT_LOG_MT_INPUT`, `CT_PORT`, `CT_OPEN`).

## [v2.10.16] — 2026-10-07

### Added
- **The ProPresenter Message can now be a pure ON/OFF signal for the
  translation box, instead of carrying caption text.** With the web-object
  page (`/projector`) doing the actual on-screen captioning, the Message is
  free for a different job: set `CT_PROPRESENTER_SIGNAL=1` and Áron triggers
  it with a fixed `.` the moment the box should be showing, and **clears** it
  the moment it should be hidden -- ticking/unticking the **ProPresenter**
  toggle, or **Clear wall**. Whatever ProPresenter-side automation reacts to
  that (a prop/layer bound to the Message's active/cleared state, a macro's
  own Trigger/Clear actions) is wired up in ProPresenter itself; Áron only
  flips the flag. A local `ppOn` flag dedupes sends so the signal fires once
  per on/off edge, not once per caption -- unlike the old text-carrying path,
  which re-sent on every finalized caption because the content itself kept
  changing.
  Off by default (`CT_PROPRESENTER_SIGNAL=0`): without it the Message keeps
  carrying real caption text exactly as before, so the Message-only fallback
  path (no web object; the Message itself is the visible caption) is
  unaffected. Verified both modes offline (Node): signal mode sends exactly
  one trigger across a tick plus five captions and exactly one clear on
  untick; legacy mode still sends the real text on every caption, unchanged
  from before this existed.

## [v2.10.15] — 2026-10-07

### Changed
- **Docs: the ProPresenter web-object page is now the recommended path, the
  Message API the fallback.** README and `.env.example` led with the plain-text
  ProPresenter Message API and buried the `/projector` web-object page as an
  "Or:" alternative underneath -- backwards from which one actually works
  better in practice: the web object renders Áron's own page directly, so it
  gets the full typewriter reveal and dimmed-sentence highlighting that a
  Message (plain text only) can never show. Swapped the order in both files so
  the better path is what a new reader finds first; the Message API is still
  fully documented, now as the explicit fallback for a ProPresenter that can't
  host a web object. No functional change -- `CT_PROJECTOR_*` and
  `CT_PROPRESENTER_*` behave exactly as before.
- Fixed a stale `/#projector` URL (the old hash-route, from before the page
  became its own `/projector` endpoint) in a `.env.example` comment and in a
  `server.py` comment -- both now point at the real route.

### Changed
- Version bump from v2.10.13 to v2.10.14
- Built standalone Windows executable


## [v2.10.13] — 2026-10-02

### Changed
- Version bump from v2.10.12 to v2.10.13
- Built standalone Windows executable


## [v2.10.12] — 2026-09-30

### Changed
- Version bump from v2.10.11 to v2.10.12
- Built standalone Windows executable


## [v2.10.11] — 2026-09-26

### Changed
- Version bump from v2.10.10 to v2.10.11
- Built standalone Windows executable


## [v2.10.10] — 2026-09-24

### Changed
- Version bump from v2.10.9 to v2.10.10
- Built standalone Windows executable


## [Unreleased]

### Changed
- **The extra ears (ElevenLabs, Gemini Live, OpenAI live) are switched off.** Tried
  live, they trail Soniox and were not worth their cost. One server flag,
  `CT_EARS` (unset = off), is the whole switch: off, `/caps` lists no ears, so the
  page shows no ear checkboxes, no "Give it the ears' readings", no *Best* preset
  and no grey per-packet lines, and the stream and `/translate` ignore any ear
  fields a stale page sends. Nothing is removed (`scripts/ears.py`,
  `scripts/witness_align.py`, the per-packet slicing); `CT_EARS=1` brings it all
  back, with the operator's saved ear choices as they were. The Ears presets menu
  goes with them (it returns with `CT_EARS=1`).
- **"Translator hears the caption" is tied to gemini-3.6-flash.** Ticking it selects
  that model (the one measured to hear the caption) from whatever was chosen;
  choosing any other model unticks it, and a saved tick on another model is cleared
  on load. The box is greyed only when no `GEMINI_API_KEY` is set.

### Added
- **`claude-haiku-5-5` in the Model picker** (released 2026-10-07). Runs with
  thinking pinned off (sent explicitly) and without `temperature`, which it
  rejects. Measured the same hour against Haiku 4.5: 0.60 s to the first word
  against 0.43, 142/158 on the aided batteries against 145, 105/136 idioms
  unaided against 111 -- offered, not the default. Soniox's confidence hint is
  not sent to it (`NO_UNSURE_HINT`): it fell from 12 to 9 of 22 planted
  mis-hears with it and copied bracketed Romanian words into the English.
  `validation/results/2026-10-07-haiku-5-5/`.
- **Ears: what the translator gets, as three switches and three presets.** A new
  Ears row under the Model menu. *Translator hears the caption* sends Gemini
  models each caption's own audio (below). *Extra ears* (ElevenLabs Scribe v2
  Realtime, Gemini 3.5 Transcribe Live, OpenAI gpt-live-transcribe; each shown
  when its key is set) run beside Soniox on the same audio, through a queue that
  drops rather than waits, reconnecting on their own; their live text shows
  under Soniox's live line in the Original column. *Give it the ears' readings*
  sends the translator each ear's reading of the caption (lined up with it, and
  only once the ear has heard its last word, `scripts/witness_align.py`); the
  readings given stay under the source line. Soniox still decides the cuts, the
  timing and the text. Presets, as measured on 47 real mis-hears: **Best** --
  gemini-3.6-flash hears + ElevenLabs's reading, 44/42/43 fixed, no right word
  lost, ~1.1 s; **Repair** -- hears alone, 39-41; **Fastest** -- Soniox's text to
  gpt-6-sol (fast tier), 36-38, ~0.8 s. Readings to a translator that cannot
  hear gain 2-3 and lose 1-3 right words, so no preset does that. Providers are
  small classes in `scripts/ears.py`.
- **A translator that hears the caption** (the Ears row's switch; first built as a
  model option, `gemini-3.6-flash + hears the caption`, which a saved setting
  still maps to). Soniox still decides the cuts, the timing and the text; the
  server keeps the last 90 s of the audio it already relays to Soniox, and each
  caption goes to Gemini 3.6 Flash with its own stretch of audio (0.2 s before its
  first word to its last word, on Soniox's clock) and a note that the text may hold
  mis-heard words. Each stream has an id, so a caption is never paired with
  another stream's audio; no clip means the same model on the text alone. If
  Gemini has no first word within 2.5 s (`CT_HEARS_FIRST_S`) the caption goes to
  a text-only translator on another provider (`CT_HEARS_FALLBACK`, gpt-6-sol-fast
  by default). Lab: 41 of 47 real mis-hears against 35-36 for text alone;
  live test: Soniox's "În ziua de Ierusalim" came out "On the day of Pentecost"
  at 1.1 s (`validation/results/2026-10-07-three-witnesses/`). A longer audio
  window heard more in the lab but is not offered: with little history Gemini
  translated all 20 s onto one caption.
- **Soniox's confidence reaches the translator.** The page keeps each token's
  confidence and sends one score per word (its least confident token) with the
  segment. The server brackets words under 0.5 (`CT_UNSURE_BELOW`, 0 turns it
  off) in the current turn only, with one instruction: a bracketed word may be
  right or a mis-hear of a similar-sounding word; keep it if it fits. History,
  the replay guard and the recorder keep the plain text. On
  `validation/scripts/unsure_lab.py` (22 planted sound-alike mis-hears in real
  sermon segments) it raised Haiku 12 → 16, Sonnet 5.5 14 → 18, GPT-6 Luna
  10 → 14, and talked no model out of any of 16 low-scored words that were right
  (`validation/results/2026-10-05-confidence-hints/`).
- **`gpt-6-sol (fast tier)` in the Model picker** (`gpt-6-sol-fast`: gpt-6-sol,
  reasoning none, OpenAI's premium fast service tier). 0.8 s to the first word;
  20/22 of the planted mis-hears unaided, 21/22 with the hint, the best of the
  fast models.
- **Caption mark for thinking models.** Sonnet 5.5 (and the lab's Opus 5.5)
  open the caption with `⟫`, and anything before it is dropped and logged, after
  reasoning reached the wall in a service. It does not catch reasoning written
  after a first caption (seen once in 82 lab calls); gpt-6-sol at reasoning
  none showed neither.

### Changed
- The mis-hear prompt line no longer says "reason it out" (it now says "work out
  which"), since thinking models took it as an invitation to reason in the reply.
- **Translation prompt: a word that breaks the sentence is a slip.** New
  headed paragraph: when one transcribed word clashes with the grammar or
  plain sense of its sentence (wrong number/ending, or a near-sounding real
  word), translate what the speaker evidently said; claims, names, numbers
  and references that fit their sentence are never touched. Prompted by the
  communion "trupuri" and "before the Lady" (Domnul→doamnei) slips. Over
  131 cases × 4 samples: Sonnet 5.5 216/216 on the homophone battery,
  36/36 on English real-word slips (was 32), 0 broken controls (was 6);
  3.8 Flash clears every "Doamnei" fragment (was 12/16); neutral for Haiku
  and GPT-6 Luna, which still say "Lady" on a bare fragment. A broader
  "thought for thought" paragraph was tested and bought nothing.
  `validation/results/2026-10-04-*`.
- **ElevenLabs keeps a running context.** Each clip carries `previous_text`
  and the request ids of up to 3 earlier finished clips, so a caption cut
  mid-sentence continues the voice instead of starting cold. Restarts on a
  voice, model or language change or after 5 minutes of quiet; v3 refuses
  context and gets none; a refused context is retried without it. First byte
  +~10 ms (v4 Turbo). The input-streaming WebSocket was the other route, but
  it rejects v4 Turbo, v4 and v3.
- **Translation prompt: full stops where Soniox runs sentences together.**
  Inside a segment, a complete sentence that Soniox ran on with a comma now
  gets a full stop; the transcriber's false stops are still dropped, and the
  segment's end is still left open unless plainly finished. On the second sermon the
  wall's p90 sentence drops from 74 to 55 words with no rise in false stops;
  Brie is unchanged; no added latency. Quotations are closed where the quoted
  words end. A separate LLM punctuation pass on Soniox's stream was tested
  and not used: accurate, but 3+ s later than Soniox's own periods. Details:
  `validation/results/2026-10-01-punctuation/`.
- **Model picker: `claude-sonnet-5-5` replaces `claude-sonnet-5`, `gpt-6-luna`
  replaces `gpt-5.2`.**
  - Sonnet 5.5 (effort low): 0.81 s to the first word, 1.06 s a caption, and
    the most unaided Romanian of any fast model (122/136 idioms).
  - Luna (effort none): 0.68 s / 0.79 s, 88% against GPT-5.2's 85%.
  - Haiku stays the default (0.51 s first word).
  - A saved pick of either old model opens as its successor, and the server
    still accepts both old ids.
  - Grok 4.7 (3.2 s first word) and the Cloudflare open models (35–55% of the
    idioms) were measured and are not offered.
  - Details: `validation/results/2026-10-01-fast-models/`; probe:
    `validation/scripts/fast_model_probe.py`.

### Added
- **none, fast is spoken.** With Speak on, Soniox's own translation is now
  voiced through a new `/speak-stream` route and the same ordered audio
  timeline as LLM captions (with Replay and website audio). Before, nothing
  was spoken in that mode.
- **Cut rule: Soniox segments + classifier** (experimental). The later of
  Default's cut and a Soniox segment end, keeping a Soniox end only where the
  source shows a mark or the classifier rates the gap >= 0.3. Replayed at Pronto
  through the page's code: Roman 1.33 / 2.67 s median / p90 at 95.7% clean
  (Default 1.45 / 3.07 s, 96.8%; Soniox segments 1.30 / 2.39 s, 92.2%); the second sermon,
  whom Soniox barely punctuates, 2.22 / 4.87 s (Default 3.17 / 6.53 s; Soniox
  segments 1.44 / 3.01 s at about Default's quality, hand labels +-12 pt).
  Picking either Soniox rule mid-service reconnects Soniox with translation on.

### Removed
- **Live draft and Anchor to draft.** Soniox's grey provisional translation no
  longer reaches the projector, the networked projector, ProPresenter or the
  operator's transcript, so nothing on the wall rewords itself under the reader:
  each caption types out once, from the model, in whole words. Soniox is now
  asked to translate only when something reads it -- **none, fast**, where its
  translation is the caption, and the **Soniox segments** cut rules, which use
  only where each of its runs begins -- and never shows it provisionally (it
  adds Soniox cost, and with wet ink the draft bought no speed:
  `validation/results/2026-09-24-soniox-tradeoff/`, latency unchanged). Gone with
  it: the draft ledger, the draft→final morph, the projector's draft takeover,
  the "first ink" figure and `CT_DRAFT_MAX_RATIO`. Replays of the assembler and
  of **none, fast** produce identical output before and after. `/translate`
  still accepts a `draft` anchor for research scripts; the page never sends one.

### Added
- **Eleven v4 and v4 Turbo in the ElevenLabs Model menu** (`eleven_v4`,
  `eleven_v4_turbo`, released 2026-09-28): ElevenLabs' newest voices, 90+
  languages, closer clones, and another language spoken with a native accent
  rather than the speaker's. Both take Stability and Similarity only: the API
  accepts Style and Speed but ignores them (speed 0.7 and 1.2 gave clips of
  identical length), so those knobs hide while either is picked and the request
  carries just the two. $0.08 and $0.04/1k characters, both 72% off until
  12 Oct 2026.
- **Mic → wall latency, per word, in the Translation header.** It measures
  from a word being spoken (its Soniox audio end, the lab's per-word
  definition) to its translation being drawn on this machine's projector
  window. The projector sends back a receipt for each word it draws, so the
  typewriter and line hold are counted rather than modelled. The header shows
  p50 · p90 · avg over the last 2 minutes. Debug shows the same next to
  since-Clear figures and splits the total into mic → caption plus wall reveal,
  so the parts should add up. With Live draft on it adds **first ink**: the
  draft's words, where they came first. Words that were captioned but never
  drawn are counted as not shown. With no local projector open, the header
  falls back to mic → caption and labels it that way. Clear and Clear wall
  both reset the figures, and both first log the since-Clear summary.
- **Soniox translation gets a stage figure** (source final → its translation
  final). It shows in the MT box under "none, fast", which had no numbers
  before, and in Debug whenever the draft lane runs.

- **Cut rule menu: "Pace · no cut at the newest word" (`pacefix`).** At
  Pronto the run-on backstop could cut *inside* the word still arriving: the
  newest word's pause is the silence so far, not a real gap, so a cut landed
  mid-word ("se înf | înfumureze", "Dum | Dumnezeu"), 15 times in a
  37-minute sermon from a speaker Soniox barely punctuates. The entry bars
  that word from the backstop's paused cut: 15 splits → 0, mid-thought cuts
  51% → 44% (Opus-judged), +0.2 s median. It is a Pace override
  (`CUT_RULES.pacefix = {pace:{lastPaused:false}}`), so it needs no weights and
  is never greyed out. The 12-word `pro12` entry splits 27 words on the same
  sermon; the same guard takes it to 0.
  `validation/results/2026-09-22-pronto-runon/`.
- **"none, fast" in the Model menu: Soniox's own translation, one final segment
  at a time.** Soniox translates in segments of about five words and delivers
  each segment's translation in one message, so it goes on the wall the moment
  it lands, beside its own source, and never changes: no grey tentative words,
  no waiting for a sentence end. Replayed on a 40-minute sermon, per word from
  spoken to shown: median 1.28 s, p90 2.33 s (sentence captions under "none":
  4.36 / 7.93 s; Pronto + Haiku ~2.2 / ~4.2 s). The price is Soniox's
  translation: 2 of 21 Romanian idioms right against Haiku's 16.
  `validation/results/2026-09-24-soniox-tradeoff/`.

### Changed
- **v4 Turbo is the default ElevenLabs model** (was v2), in the page and for
  `CT_EL_TTS_MODEL`. Two minutes of the Roman sermon run through the whole app
  (Soniox, Haiku, ElevenLabs; 33 identical sentences per model): speech reached
  the browser a median 1.03 s after dispatch against v2's 1.90 s (p90 1.39 vs
  2.31 s; v4 1.54 / 1.98 s), at half v2's price (a seventh until 12 Oct). It
  speaks the same text about 13% slower (111 s vs 97 s) and has no Speed
  setting. A browser that saved another model keeps it until the operator
  picks again.
- **ElevenLabs speaks from its streaming endpoint**, so a line starts playing
  while the rest is still being generated. First audio for a 115-character
  caption, measured from the Pi (before -> after): v4 Turbo 1.3 -> 0.25 s,
  v4 2.4 -> 0.7 s, v3 4.9 -> 0.65 s, flash 0.36 -> 0.19 s, v2 1.5 -> 1.5 s.
- The ElevenLabs Model menu shows today's rates: v2 and v3 $0.08/1k
  characters, flash and turbo v2.5 $0.04 (were $0.10 and $0.05).
- **A stuck translation is dropped instead of waited for.** A call used to hold
  the pipeline for up to 20 s before it was retried, up to four times, so one
  provider hang could freeze the wall for about 87 s. In a church a frozen wall
  is worse than a missing sentence.
  - **Stalled:** a call with no new text for 4 s is abandoned and sent once
    more, immediately. A stuck request usually answers fresh in under a second.
    If the resend stalls too, that batch is dropped and the next one goes.
  - **Worst case:** 8 s of still wall.
  - **Still streaming at 15 s:** dropped without a resend.
  - **Why 4 s:** on 977 recorded sermon calls, every call that was going to
    answer promptly showed its first text within 2.8 s. The only slower two
    took 7.8 s and 10.3 s, and those were stalls.
  - **Tested** in the page against a scripted model:
    - Stalls once: the caption landed 4.6 s after the unit was queued.
    - Stalls twice: dropped at 8.3 s, and the next sentence went 0.3 s later.
    - An abandoned attempt never reaches the model's context, even when it
      finishes on the server afterwards.
  - The operator's row shows "dropped so the captions keep moving". The log
    says `translator stalled (4.0s) — resending once` or `— dropped`.
- **Debug's "Mouth → audio" row is now "Unit end → audio".** It was always
  timed from when the unit's last token *arrived*, so it left out the STT lag
  and the wait of every word before the last. Mic → wall is the real
  mouth-to-room figure now.

### Removed
- **The slow "none" entry left the Model menu.** Of Soniox's two no-model
  entries only **none, fast** stays (1.3 s a word, final text only); **none**
  (tentative words in grey, captions at sentence ends, 4.4 s) was never the one
  to use live. A saved "none" opens as "none, fast". Its code stays, for
  `wet_ink_harness.js replay-native` and the soniox-tradeoff study.

### Fixed
- **The projector store only moves forward.** The page posts captions to the
  server store without waiting, and posts can overtake each other on the way.
  Under load, a sentence's last partial landed after its own final 1 time in
  300. The store kept the partial, so every projector reading the feed (remote
  ones and the local window's poll) trimmed the sentence back for good.
  - **The rule:** every post now carries its place in send order, and the store
    drops any post older than what it already holds.
  - **What it covers:**
    - A late partial never replaces its final.
    - A sentence arriving after its successor is dropped rather than shown out
      of order.
    - A stale draft or pace never replaces a newer one.
    - Nothing sent before a Clear comes back after it.
    - A Clear that lands late wipes only what was sent before it.
  - **Segment ids** now count up from the page's load time instead of `s1`. A
    reloaded operator page used to reuse ids that projectors had already
    retired, and projectors ignore retired ids, so they skipped new captions.
  - **Tested:**
    - Out-of-order posts in every one of those cases.
    - The load race again: 0 of 300.
- **Snowballing captions: each row restating the rows before it.** Root cause
  of the accumulating translations logged on 2026-08-13 as "not identified",
  found from the server console of a 2026-09-25 session (Grok 4.20, en→ro,
  Live draft off). Three steps:
  1. **Trigger.** The model re-translated its previous caption in front of the
     new words. It does this when that caption ended mid-clause ("…what we mean
     by the West — this is" + "the hot take…") or when the new unit is a bare
     predicate ("is something that lasts…"). This is the "stitch the captions
     together" instruction over-applied.
  2. **Amplifier.** The server stored the raw reply, restatement included, as
     that turn of the rolling history. After two such turns every reply restated
     everything since, longer each time.
  3. **Freeze.** Once replies passed the runaway guard (>240c and >4×), they
     were no longer stored. That left the poisoned turns in the history with
     nothing new to push them out, so every caption for up to 5 minutes carried
     the same restated prefix. The client's sentence-level de-dup could trim
     only the complete sentences of it.

  Replaying that session through Grok with the old server logic reproduced the
  snowball in every run where the triggers landed.

  **Fix.** The server now judges the head of each reply against the recent
  captions before anything sees it (`translate_claude.ReplayGuard`). A head
  that replays them verbatim (6+ words, ending where a caption ended, with
  enough left to translate the source) is cut from the caption, the projector,
  the voice and the history alike. Streaming holds only until that is decided,
  which for an ordinary reply is its third word. The history now holds exactly
  what the room was shown. The runaway guard clears the history instead of
  just skipping the one reply, and trips earlier (3× + 60c).

  **Tested:**
  - The live session: every restatement is cut to exactly its new words.
  - Grok, natural reruns: the trigger fired in 3 of 4 and was cut every time,
    with 0 snowballs. The old logic snowballed in 1 of 4.
  - 2,421 recorded Haiku captions: nothing cut.

  An earlier cut rule clipped three of the Haiku captions. All three were
  genuine repeats in different words, which is why the "enough left" test and
  the 6-word minimum are there. A cut logs `translation restated N word(s) of
  earlier captions` in the page and `[mt ] replay:` on the server console.
- **A refrain said twice is captioned twice.** The duplicate guard dropped any
  unit whose text matched one of the last 8 units sent, so a short refrain the
  cut rule makes into its own unit ("Mărit să fie numele Domnului.", "Mai este
  har. Mai este har.") went uncaptioned the second time. The guard now also
  compares when the words were spoken. It drops a match only if the new words
  start before the earlier copy's last word ended, meaning the same audio sent
  twice. It still drops on text alone when there is no word timing (the
  per-segment path).
- **The translator no longer turns an everyday word into a church word.**
  Live, a pastor mocking a retiree off "to Hawaii … on a cruise or something"
  came out "to the Cross, or something": Soniox wrote "Cruz", and the model
  repaired it toward the setting, although Hawaii and the retirement line were
  in its history two and four turns back. Every example in the mis-hear rule
  runs from an everyday word to a religious one, so
  `config/translation_prompt.txt` now has THE SETTING DOES NOT CHOOSE THE
  MEANING beside THE SPEAKER OWNS THE FACTS: the lines before a word decide
  it, and the setting chooses between wordings of a meaning, never the meaning.
  On that passage, 8 samples per model (`validation/results/2026-09-24-homophone-prior/`):
  Grok 4.20 "the Cross" 7/8 → 1/8, Sonnet never; no regression on the
  mis-hear, speaker-owns-the-facts or odd-but-real controls. **Haiku is not
  fixed**: "the Cross" 8/8 under every prompt and history depth tried, including
  none. Gemini 3.8 Flash says "cruise" 8/8 without the change.
- **Under "none", source and translation rows drifted apart.** Soniox
  translates in segments (a source run, then its translation), but the page cut
  the caption at any sentence end of the translation and sealed the source row
  through its own last sentence end, independently -- once a row swallowed a
  second sentence, every later caption sat one row off. Each translation run is
  now paired with the source finalised before it, and captions are committed at
  pair boundaries: 54 of 54 captions beside their own source on a replayed
  interview, against 28 before. `validation/results/2026-09-24-native-align/`.
- **Replay latencies were 1.0 s high.** The replay harness's clock starts at
  1000 ms and its outputs did not subtract it, so every absolute latency from
  `wet_ink_harness.js replay` (the Pace sweeps, the cut-rule tables, the Pace
  tooltip) read a second late. Rule-to-rule comparisons were unaffected. The
  tooltip's figures are corrected.

### Changed
- **Moderato and Lento cut a run-on at 28 words** (were 32 and 42). Swept
  against the chunk gold through the page's own code: Moderato is unchanged in
  practice (its cap never fired above 28), and Lento reaches the translator
  1.7 s sooner at the median and 5.3 s sooner at p90 at the same share of clean
  cuts. Below 28 both lose clean cuts fast. `validation/results/2026-09-24-pace-caps/`.

### Added
- **Captions stitch together across packets.** Pronto cuts mid-sentence, and
  the translator sees only its packet plus the replies before it. The prompt
  now says so in a few model-agnostic lines ("STITCH THE CAPTIONS TOGETHER"):
  read each segment as the continuation of the last caption, and let
  punctuation and capitals carry through -- a continued clause starts in lower
  case. On the display side one rule, nothing inferred: a caption that starts
  with a lower-case letter continues the sentence, so the previous caption's
  plain full stop is removed (its own column, the projector, the website).
  Measured with GPT-5.2 on the Brie sermon (`validation/scripts/seam_probe.py`):
  a full stop followed by a lower-case start is 0.4% of seams; the commoner
  flaw is a capital after a caption left open.
- **An experimental Cut rule menu** beside Pace (defaults to "Pace": nothing
  changes unless a rule is picked). "Classifier · ships on a mark" cuts as soon
  as a marked word arrives and lets a small classifier (`app/cut_models.json`,
  served at `/cut-models`) decide every other gap; "Pronto · 12-word cap" and a
  12-word-cap classifier variant trade some clean share for a lower p90. Use
  with Pace = Pronto. Replays and the research behind them:
  `validation/HANDOFF-cut-rule.md`.

### Fixed
- **The run-on backstop could orphan a preposition from its object.** Rule 3
  (`PACE.runon`, 3ddb771) has two branches: cut at the last word the speaker
  paused `Clause pause` after, else at exactly `runon` words. Only the second
  stepped back over a word no sentence ends on, so the guard was on the branch
  that runs last and off the branch that runs first. On the interview capture,
  the one place the backstop fires on real speech, the speaker paused 540 ms
  after "for" -- reaching for the noun, not taking a breath -- and the cut went
  "…their law available for ‖ the overseers, if you will", leaving the
  translator a dangling preposition and starting the next packet on a bare noun
  phrase. Both branches now share the step-back. Two units differ on that
  capture; unit count, fragment count and latency (2.79 s median / 5.04 s p90)
  are unchanged, and Moderato and Lento replay byte-identical on both captures
  in the repo -- the backstop never fires there.
  `validation/results/2026-09-21-pronto-backstop/`.

  Graded on the same replay, Pronto puts 10 of 111 boundaries mid-thought
  (9.0 %; 11 before this fix). The backstop is one of them. The other ten are
  rule 2, and the write-up above records what the evidence says about them:
  the silence behind a mid-thought cut (median 300 ms) is *longer* than behind
  a good one (180 ms), so at Pronto's 300 ms threshold a pause does not
  distinguish a breath from a hesitation. Not changed here; it wants a capture
  of a real sermon to measure on.

## [v2.10.9] — 2026-09-22

### Changed
- Version bump from v2.10.8 to v2.10.9
- Built standalone Windows executable


## [v2.10.8] — 2026-09-22

### Changed
- Version bump from v2.10.7 to v2.10.8
- Built standalone Windows executable


## [v2.10.7] — 2026-09-21

### Fixed
- **Clear wall could put the cleared line straight back on a networked
  projector.** The v2.10.6 delta throttle holds a segment's latest text in a
  150 ms timer; Clear posted its wipe but left that timer running, so the held
  delta landed after the clear, the store re-added the segment under the new
  epoch, and the remote wall drew the partial line again. Nothing then
  updated it (every later cast for that id is dead to the wall, its final
  included), so it sat there until the store's one-minute prune. Clear now
  cancels every pending delta and forgets the dedupe memory before it posts.

### Changed
- **The delta throttle is leading-edge, matching the live-draft path.** It
  was trailing-edge: every delta, the first word of a sentence included,
  waited the full 150 ms before leaving. A delta that arrives with its
  segment quiet for 150 ms now goes out at once, and only one inside that
  window waits for its end -- the same rate cap, minus a fixed 150 ms on the
  networked wall.

## [v2.10.6] — 2026-09-21

### Fixed
- **Networked projector could freeze mid-service while everything else kept
  moving** -- the operator's own screen, TTS, and the website relay all
  looked fine, but a `/projector` page on another machine (e.g. the
  ProPresenter PC) polling `/caption-feed` would sit on the same `since=`
  revision for many seconds at a stretch. Root cause: `castSeg`'s delta path
  posted to `/caption` on every raw stream chunk, completely unthrottled and
  with no check for unchanged text -- unlike the live-draft path
  (`netCapDraft`, already throttled to 150ms) right beside it. With several
  sentences translating concurrently this could burst far more fire-and-forget
  fetches at the browser than its per-origin connection queue could drain,
  starving later, more important sends (including a segment's own `done:true`
  final) behind a growing pile of stale ones -- a backlog that only grew
  worse the longer a service ran, never on its own catching up.

  `castSeg`'s network send is now routed through `netCapSeg`: per-segment
  throttled (150ms, matching the draft path) and deduped against the last
  text actually sent for that id, so unchanged or superseded deltas cost
  nothing. A `done:true` final always bypasses the throttle, cancels any
  pending delta timer for that id, and sends immediately -- the room's actual
  commit is never the one left waiting behind a busy queue. The BroadcastChannel
  cast (same-machine, effectively free) is untouched.

## [v2.10.5] — 2026-09-20

### Changed
- Version bump from v2.10.4 to v2.10.5
- Built standalone Windows executable


## [v2.10.4] — 2026-09-19

### Changed
- Version bump from v2.10.3 to v2.10.4
- Built standalone Windows executable


## [v2.10.3] — 2026-09-16

### Changed
- Version bump from v2.10.2 to v2.10.3
- Built standalone Windows executable


## [v2.10.2] — 2026-09-15

### Changed
- Version bump from v2.10.1 to v2.10.2
- Built standalone Windows executable


## [v2.10.1] — 2026-09-15

### Changed
- Version bump from v2.10.0 to v2.10.1
- Built standalone Windows executable


## [v2.10.0] — 2026-09-15

### Changed
- Version bump from v2.9.16 to v2.10.0
- Built standalone Windows executable


## [Unreleased]

### Added
- **A run-on with no punctuation at all ships anyway** (the Pace bar's
  `runon`, in words: Pronto 24 / Moderato 32 / Lento 42). Every rule in
  `sxCut` needed a mark from Soniox — a period for a sentence, a clause mark
  for a run-on — and for a speaker who never pauses Soniox stops punctuating
  altogether: a 110-word paragraph arrived without one comma, nothing could
  fire, and it sat until the speaker stopped, then landed on the wall as one
  block. Past the run-on length the cut now goes at the last word the
  speaker paused Clause pause after (the same token-time evidence the clause
  rule reads), else at exactly that many words, stepped back over a word no
  sentence ends on. On the 40-minute sermon (longest units 26 / 29 / 39
  words) Moderato and Lento replay byte-identical, and Pronto touches one
  unit: a 26-word Exodus quote now splits 14 + 12 at the breath the speaker
  took. It exists for the speaker the sermon didn't have.
  `wet_ink_harness.js` takes it as the pace string's fifth number or `RUNON=`.
- **The speaker owns the facts** (translator prompt, a THE SPEAKER OWNS THE
  FACTS paragraph beside ADD NOTHING). Gemini Flash Lite captioned "în
  capitolul 17, versetul 6" as "in chapter 11, verse 23" and "Evanghelia după
  Luca" as "the Gospel of Mark" — it knew where the mountain saying lives and
  straightened the preacher's record. The prompt now says a wrong chapter,
  verse, book, author, number, date or claim is captioned exactly as said,
  and draws the line against the mis-hear rule explicitly: a mis-hear is
  about SOUND (a word close in sound to the one intended), and "17" does not
  sound like "11". Checking the preacher against the Bible is the
  congregation's job.
- **A caption expires after a minute** (`CT_PROJECTOR_STALE_S`, default 60; 0
  keeps everything until Clear, as before). The projector store kept its last
  40 segments until Clear, so a projector switched on at the start of the
  service opened on the sound check from twenty minutes earlier, and one
  opened after a break on the last words before it. The feed now drops any
  segment, or the draft, older than the horizon before serving it, and bumps
  its revision when something aged out so a projector already polling hears
  about it. The strip's own 15-second idle fade is unchanged; this is about
  what a projector is handed when it joins.
- **Clear wall** (header, beside Project; key W): wipes what the room is
  reading — the projector strip, the web wall and ProPresenter — and nothing
  else. The transcript stays, listening continues. For the stale tail of the
  last service standing on the screen when the projector comes up, or a bad
  line that needs to go now. Two things would have put the wiped text straight
  back and both are stopped: the batch in flight keeps streaming deltas under
  its segment id, so every id issued so far is dead to the wall from that
  moment (an operator's later edit of one of those rows included — a line
  cleared stays cleared), and the retained live draft is reset, as Clear
  already does. Clear gained the same in-flight gate.
- **One colour on the wall.** The projector strip used to hold the sentence
  being read at 95% and the ones before it at 50%; every word is now the same
  slight off-white (#f2f2ee, full opacity). The dim class and its variable stay
  wired at the live value, so the old two-tone look is one number away.
- **The translator punctuates by meaning, not by Soniox.** The prompt told the
  model to translate a fragment as a fragment, and it did — period included:
  "Și interpretarea este destul de." came back "And the interpretation is
  quite.", "smochinul. Și toți copacii," as "the fig tree. And all the trees,",
  and a quotation Soniox closed at the first pause ("nu va trece neamul
  acesta" până se vor întâmpla…) kept its mark in the wrong place. The Pace
  bar's faster stops hand the model exactly these — a chain of clauses, each
  with the transcriber's guess at a sentence end on it — so
  `config/translation_prompt.txt` now has a PUNCTUATE BY MEANING paragraph
  beside ADD NOTHING: the source's full stops, capitals and quotation marks are
  guesses, never copied; an unfinished clause ends open, a period that splits
  one thought is dropped and the next word not capitalised, and quotation
  marks close where the quoted words end (for Scripture, the end of the
  verse). Punctuation and casing only; the fragment rule stands. Measured on
  the rows that showed it: Haiku and Grok 4.20 fix all three (8/8 samples on
  Haiku for the open ending and the moved quotation mark), Flash-Lite the
  first two; complete sentences keep their full stops. The rule was first
  tried as a bullet in the mis-hear list, where Haiku ignored it entirely —
  placement and a heading were what made it take.
- The projector settings — the wall's reveal pace and line hold, and the Web and
  ProPresenter outputs when the server has them — moved from the header row into
  a **Projector** sub-row of the Translation panel, beside the model that feeds
  them. The header keeps the Project button. They still apply live.
- **The Pace bar re-measured on a 40-minute sermon; the cut rule reads the
  evidence Soniox sends.** Every Soniox token carries the audio time it
  covers, and the assembler used to throw that away and cut on punctuation
  alone: a run-on with no period shipped at whatever comma came last once it
  passed the Pace length, which split lists ("fluids, proteins, | and waste")
  and numbers ("300, | 400 year") as readily as clauses. Now `sxBounds` lists
  every punctuated word of the pending text with the pause the speaker left
  after it (next word's start minus this word's end, the next word looked for
  in the tentative tail too), and the run-on cut goes to the LAST clause mark
  with at least the stop's clause pause of silence behind it; only past twice
  the Pace length is the last mark taken regardless. A period after a word no
  sentence ends on ("și.", "And.", "de.") counts as a clause mark, not a
  sentence end. Then the sweep (`validation/results/2026-09-18-cut-sweep-roman/`):
  a 40-minute Romanian sermon streamed to Soniox once per endpoint silence,
  every configuration replayed through the app's own assembler on each, and
  every one of ~3,500 cut boundaries read by Opus 5 and labelled sentence end /
  clause end / mid-thought. What it found: the mid-thought rate is set by
  Soniox's endpoint silence, not by the client-side numbers. Pronto's 700 ms
  made Soniox close a sentence at every half-second breath and punctuate it,
  and those periods are indistinguishable from real ones by anything in the
  stream — 13% of Pronto's cuts landed mid-thought; the same client rule on a
  1300 ms stream, 5%, for 0.2 s. The other lever the sweep found was wet
  ink's hold on the newest tentative word, a 700 ms constant meant to keep a
  half-arrived word out of the translator: it cost 0.5 s per word, and with
  the one systematic fragment handled — a Romanian number split at its
  thousands separator, "120" then "." then ".000", which the cut rule had been
  reading as a sentence end — a zero hold leaves 0 to 7 words in 40 minutes
  translated as first heard and corrected in the transcript afterwards. The
  bar's stops are now (floor / run-on length / clause pause / Soniox
  endpoint), per-word median spoken → handed to the translator and cuts judged
  mid-thought on the sermon: **Pronto** 20 / 20 / 300 / 1300 — 2.6 s, 13%,
  cuts at every paused breath and ships forty three-word units in 40 minutes,
  the speed stop; **Moderato** 25 / 60 / 900 / 1300 — 3.3 s, 6% (the old
  Pronto was 3.5 s / 14%); **Lento** 40 / 100 / 1200 / 2200 — 3.8 s, 2%,
  whole sentences (the old Lento, 70 / 260, was 5.3 s / 1% and piled up). The
  Clause pause, Veto and Hard knobs that existed for a day are folded in: veto
  off and Hard ×1.5 / ×3 measured within a cut or two of the defaults, wet ink
  off cost ~2 s per word for nothing. `cut_sweep.py` runs the grid and the
  judge; `cut_quality.py` annotates a run's cuts with the pause and mark at
  each; `segment_lab.py` is the one-capture arm comparison.
- **Model "none — Soniox live translation only"**: no LLM call at all. Soniox's
  own translation tokens are cast to the projector as they come and each
  complete sentence of the final translation is committed as the caption
  (transcript, projector, web, ProPresenter; nothing spoken). Picking it locks
  Live draft on (it is the feed) and greys Anchor to draft and Wet ink
  (nothing to anchor, nothing to cut). First in the picker: it is the off position of every
  model below it. The source column is sealed at Soniox's translation cadence
  under it — a sentence at a time, the moment its translation lands — so the
  two columns pair row for row instead of interleaving at the assembler's
  rhythm. The control arm for judging
  the LLM lanes — and it graded 8.7 / 7.7 on the interview clip, level with
  the Gemini baseline, 0.4 s after each sentence closes; its tokens were all
  final (no draft in the "live draft"), so it never revises.
- **Asking Soniox for a translation makes it finalize the source four seconds
  sooner** (`validation/results/2026-09-17-stream-dispatch/README.md`,
  addendum). Same audio, translation in the handshake: every word's
  finalization lag fell from 4.85 s median to 1.15 s, a sentence's last word
  from 4.6 s to 0.35 s; reproduced on a second capture. The shipped assembler
  path inherits it with the Live draft box on: caption latency 5.35 s → 1.17 s,
  per-word (spoken → on screen) 9.2 s → 4.9 s, same quality. The earlier
  stream-lab write-ups measured against draft-off captures.
- `capture_soniox_stream.py --translate-to <lang>`: capture with Soniox's live
  translation interleaved, the way the app's draft lane receives it.

### Changed
- **Stage panels line up.** Three layout helpers inside a stage box: a
  label/select ledger (`.fields`) so the Text-to-speech Output, Speech, Voice
  and Model dropdowns share one left and one right edge; equal-column knobs
  (`.knobs`) so Stability/Style/Speed sit in aligned columns;
  and dashed sub-rows (`.row`) for voice capture and recording, so the
  clone controls no longer spill into the expressivity sliders.

### Removed
- **Stream mode, its Patience knob and Lookahead** — built, measured and
  removed within this cycle, never released. The idea: no sentence assembler;
  everything the recognizer had heard and not yet captioned, tentative words
  included, went to the model as it arrived and the model captioned whatever
  phrase it was already sure of, the projector joining the pieces into one
  flowing line. Two days of labs on a five-minute interview
  (`validation/results/2026-09-16-stream-lab/`,
  `2026-09-17-stream-dispatch/`, blind Sonnet grades) found its corner —
  gemini-3.8-flash · Eager · three words of lookahead: 9.0 / 7.0, 2.95 s per
  word — and also that the room reads phrase-sized takes with seams, while the
  same clip through the assembler with Soniox's draft in the handshake reads
  whole sentences at about 3 s a word. Judged on the screen: the takes and the
  churn bother the eye more than the second saved is worth, and every packet
  was one more thing that could fail. Gone with it: `POST /stream-translate`,
  `scripts/stream_translate.py`, `scripts/stream_variants.py`, the three lab
  scripts, and the Heavy anchor (a "keep the draft unless overtly wrong" rule
  measured at 3–4 % churn against 17–30 % for the cosmetic anchor, at a
  visible cost in the final's quality — the same verdict). The tree as last
  shipped with them is tagged `archive/stream-mode`. What the campaign left
  behind stays: the finalization-lag finding, Live draft making Soniox commit
  four seconds sooner, model "none", and Wet ink, which is the assembler
  taking the one thing Stream mode had right.
- **The Endpoint hurry and Endpoint sensitivity knobs.** Both keep their
  measured defaults (hurry 0, sensitivity 0.0 — `endpoint_lab.py`,
  2026-09-15: 0 is the only hurry safe for a Sunday, sensitivity measured
  flat); the server still takes both in the Soniox hello frame, so a knob is a
  two-line change if one is ever wanted again.
- `validation/scripts/stream_lab.py`: sandbox for the "no assembler" idea — the
  finalized Soniox stream is sent to the translator every 0.5 s and the model
  decides when an idea is complete (snapshot packets, one call in flight,
  prefix consumption, drop over stale). Run against the shipped cut rule on a
  93 s synthesised sermon (`validation/captures/sermon-synth-tts.jsonl`) with
  `grok-4.20-non-reasoning` and `gemini-3.5-flash-lite`: neither beats the
  shipped rule — Grok does not gate at all (1 wait in 50 calls, burst-sized
  shards), Gemini gates but waits a whole finalization burst to be sure
  (+1.2 s). What the lab did find: on continuous speech Soniox finalizes a word
  4.9 s (max 6.4 s) after first hearing it, tentative words are 98 % stable at
  1.5 s, and committing them at 1.5 s takes the shipped path from 6.4 s to
  2.1 s speaker-to-screen (3 of 257 words changed under the caption). Write-up
  and every run: `validation/results/2026-09-16-stream-lab/README.md`.
- **Unaided knowledge, per model** (`validation/results/2026-09-17-glossary-dependence/`,
  written into `model-comparison.md` §B2). `idiom_mining` on the 136-candidate
  pool with an empty glossary: `gemini-3.8-flash` knows 107, misses 11;
  Flash-Lite 97/20; `gpt-5.2` 91/21; the Groks 88–90/38–40; Sonnet 89/29;
  **Haiku 70/61** — the default model knows the least Romanian by a wide
  margin, and church register is 20/20 for every model, so the spread is all
  idioms and regionalisms. `glossary_dependence` on the twelve glossary-backed
  figurative cases, aided vs unaided, every picker model: the glossary buys
  nothing measurable for any of them (deltas within ±0.7), and eleven of the
  twelve entries are known 3/3 unaided by all seven — including *păpușoi*,
  marked load-bearing in August. The mined entries remain earned for Haiku;
  for the Geminis and gpt-5.2 they are closer to ballast. `glossary_dependence`
  now defaults to the picker line-up and gates on every provider's key.
- **Every translator model measured on one day, one network, one prompt** —
  `validation/results/model-comparison.md`, raw outputs in
  `validation/results/2026-09-16-model-sweep/`. Four batteries
  (`figurative_stress` 61, `homophone_stress` 66, `context_stress` 31,
  `effort_sweep` 11), 11 configurations, ~1,500 calls from the operator
  machine, with Haiku cached for the first time since 2026-09-13.

  | config | call time (median) | auto-pass /158 | hard cases wrong |
  |---|---|---|---|
  | `grok` 4.20 | **0.54s** | 149 | 0 |
  | `gemini:lite` | 0.56s | 149 | 0 |
  | `grok:4.3` pinned | 0.61s | 146 | 0 |
  | `gpt:nano` 5.4 | 0.68s | 133 | **4** |
  | `haiku` | 0.70s | 146 | 0 |
  | `gpt:5.2` | 0.78s | 148 | 0 |
  | `gpt:mini` | 1.10s | 145 | 0 |
  | `gemini:3.8` | 1.29s (max 5.25s) | **154** | 0 |
  | `sonnet:low` | 1.41s | **153** | 0 |

  What it settles: Haiku stays the default (no wrong answers, every failure
  guarded); Sonnet `low` is the quality pick and the numbers say why (66/66
  homophones, 18/18 clitics; `medium`/`high` add nothing, `high` has a 3.2s
  tail); Gemini 3.8 Flash matches Sonnet at Flash-Lite cost but with the worst
  tail in the table; Grok 4.20 is the fastest model and loses nothing to Haiku;
  GPT-5.2 replaces 5-mini as the GPT worth picking; and **GPT-5.4 nano should
  not run a Sunday** — 4 of 11 STT-shaped hard cases wrong ("at peace with God
  *in our sin*", "The *profit* Isaiah"), 12/18 clitics, 12/21 idioms. The
  earlier, scattered measurements are kept at the end of the document.

- **Two more translator models: `gpt-5.2` and `grok-4.3`.** Both found by
  probing the providers' current catalogues on the app's own prompt
  (2026-09-16, three sentences each, streaming, median time to first character,
  hidden reasoning tokens):

  | model | effort | first char | reasoning tokens | |
  |---|---|---|---|---|
  | `gpt-5.4-nano` | none | 0.90s | 0 | shipped |
  | **`gpt-5.2`** | none | 0.93s | 0 | **added** — the newest GPT-5 that fits a caption |
  | `gpt-5-nano` | minimal | 1.15s | 0 | older than 5.4-nano, no faster |
  | `gpt-5-mini` | minimal | 1.19s | 0 | shipped |
  | `gpt-5` | minimal | 1.26s | 0 | |
  | `gpt-5.5` | none | 2.50s | 0 | not offered: 2.5s to the first word |
  | `grok-4.3` | *default* | 8.8s | 376 | |
  | `grok-4.3` | low | 10.4s | 475 | |
  | **`grok-4.3`** | **none** | **1.0s** | **0** | **added, pinned** |
  | `grok-4.5` | none | *400* | | floor is `low`: 2.3s, 81 tokens — not offered |
  | `grok-4.6` | none | *400* | | not offered |

  From Grok 4.3 on, xAI ships one id per model and a `reasoning_effort` field
  instead of the separate `…-non-reasoning` id 4.20 has — the Gemini shape —
  so `translate_xai.PINNED_EFFORT` sends `none` on every 4.3 request, the
  catalogue classifier offers a pinned model as non-reasoning, and
  `_note_usage` warns once if reasoning tokens ever come back (the same guard
  Gemini has on `thoughtsTokenCount`). `gpt-5.2` rejects `minimal` and is
  pinned to `none` in `translate_openai.REASONING_EFFORT`. Both are in
  `config/model_pins.json` and in every battery (`gpt:5.2`, `grok:4.3`).
  Through the app: `grok-4.3` 0.55s to final, 10 completion tokens, cache
  warming as 4.20's does; `gpt-5.2` 0.7–0.9s, cache hit.

- **`validation/` — one home for everything that measures the app.** The
  thirteen batteries that lived among the runtime modules in `scripts/`
  (`figurative_stress`, `homophone_stress`, `cut_lab`, `endpoint_lab`,
  `idiom_mining`, `effort_sweep`, `reasoning_soak`, …) moved to
  `validation/scripts/`, with `validation/results/` for committed runs and
  write-ups and `validation/captures/` for the streams the labs replay.
  `validation/README.md` indexes every battery by the question it asks. Runtime
  code stays in `scripts/` and the batteries import it from there, so a battery
  always measures what ships; the Windows build bundles `scripts/` and not this
  folder, so it is slimmer for it. A `validation` *branch* was considered and
  rejected: the batteries import the live translator modules, so a branch would
  rot or become a standing merge chore, and its results would be invisible from
  `master`.

  Nine scripts and three result files that were stranded on unmerged branches
  came in with the move: from `claude/translation-benchmark-harness` (2026-08-30)
  the bidirectional `quality_stress` corpus, `latency_sweep`,
  `quality_by_config`, `spill_probe`, and the wetranslate.live comparison
  (`compare_wetranslate` + `build_stress_audio` + `wetranslate_run.js`); from
  `claude/aron-google-stt-romanian` (2026-09-04) the Gemini live-translate bench
  and its verdict (`results/gemini-live-romanian.md`: translates well, captions
  badly) with both graded runs; from `claude/bilingual-mode-and-model-pins`
  (2026-08-13) `integration_stress`. `docs/publishing-handoff.md` came off
  `claude/haiku-prompt-caching-check` (2026-09-01), whose LICENSE and README
  proposals for a public release were left for a deliberate decision — see the
  `archive/haiku-prompt-caching-check-txpobx` tag.

### Changed
- **Models are named as their APIs spell them, everywhere.** The picker,
  the stage chip, the Diagnostics `MT [en] …` line, the server's `[mt ]` line,
  `config/model_pins.json` and the validation tables now all say
  `claude-haiku-4-5`, `gpt-5.2`, `grok-4.20-0309-non-reasoning`,
  `gemini-3.8-flash` — no more "Haiku 4.5 (fast)" in one place, `haiku` in
  another and `Claude · Haiku 4.5` in a third. The battery config names follow
  (`claude-sonnet-5:low` rather than `sonnet:low`), so a column in a results
  table is the same string as the option in the picker.
- **`gpt-5-mini` and `gpt-5.4-nano` retired from the picker.** The one-day
  sweep put `gpt-5.2` ahead of 5-mini on every count and 0.3s faster, and
  5.4-nano got 4 of 11 STT-shaped hard cases wrong. `gpt-5.2` is the one
  OpenAI model offered and the OpenAI default; a saved pick of either retired
  id bounces to `claude-haiku-4-5` with a Diagnostics line, the same way a
  Grok id that left the catalogue does. Their effort pins stay in
  `translate_openai.REASONING_EFFORT` so a battery naming them still runs.
- **Branch cleanup.** Every branch and stash was tagged `archive/<name>` before
  deletion, so `git checkout archive/<name>` recovers any of it; 16 merged
  branches, 4 salvaged ones, the pre-rewrite backup and 3 August stashes are
  gone from the branch list. Two things did not merge and are only in the
  archive tags: the draft-lane obscenity gate from
  `archive/bilingual-mode-and-model-pins` (never shipped; a product decision,
  and a re-port against today's `index.html`), and the v3.0.0 public-release
  cut on `archive/haiku-prompt-caching-check-txpobx`.

- **Haiku 4.5's prompt cache: found where the 4096 went.** `count_tokens` on the
  cached prefix (prompt + glossary): 4208 tokens before "Remove the condense
  back-edge" on 2026-09-13, **4086** after it and today — the ⏩ paragraph was
  122 tokens and the floor is 4096. Ten tokens short. Not fixed here: the
  glossary decides what earns a slot (`validation/scripts/idiom_mining.py`).

- **The prompt-cache light now works for every translator, and the latency
  panel has a `Prompt cache` row.** OpenAI and xAI both return
  `prompt_tokens_details.cached_tokens` on the usage chunk the app already asks
  for (`stream_options.include_usage`) and were printing it to the console;
  each backend now also keeps it per thread (`last_cache_stats()`, the same
  shape Claude and Gemini use) and the server classifies it like the others, so
  the light on the Translation header answers for all four. The row sits under
  `Mouth → audio`:

  ```
  Prompt cache       off  67% hit  x3     4320 prompt tokens at full price
  ```

  last call's state, the hit rate over the same 30-call window the averages
  use, the count, and what the last call actually paid for. A miss is a cost
  the latency numbers never show, and the light alone only ever shows the last
  call. The cache block carries a new `prompt` field -- the whole prompt on
  every provider (Anthropic's `input_tokens` excludes the cached part; the
  other three count it in) -- so "read of prompt" reads the same everywhere.

  What a miss means differs by provider, and the tooltip and the warning now
  say so: Claude's cache is explicit, so a miss is the prefix under the model's
  minimum, permanently, and is warned at once; OpenAI's and xAI's are automatic
  and free to populate (never `write`), so the first miss of a session is
  normal and only the second in a row is warned. Measured on the way in, two
  calls per backend on the live prompt:

  | backend | call 1 | call 2 |
  |---|---|---|
  | `gpt-5.4-nano` | off, 3825 | hit, 2816 of 3864 |
  | `grok-4.20-0309-non-reasoning` | hit, 128 of 4031 | hit, 128 -- then 1408, then 3968 of 4031 by the 6th call: xAI warms its cache one step per call |
  | `gemini-3.5-flash-lite` | hit, 3767 of 3895 | hit (built at startup by `warm()`) |
  | `claude-sonnet-5` | write, 5569 | hit, 5569 of 5828 |
  | **`claude-haiku-4-5`** | **off, 4108** | **off, 4143** |

  The last line is the finding: the default model is not caching. Haiku 4.5's
  floor is 4096 tokens for the cached prefix; the prefix cleared it by ~100
  tokens on 2026-08-30 (see `translate_claude._build_request`) and has been
  trimmed under since, so every Haiku caption pays full input price, silently.
  The Claude miss tooltip used to suggest a trim, which is the wrong direction;
  it now says the prefix must grow past 4096 (earned glossary entries,
  `scripts/idiom_mining.py`) or the model be Sonnet 5, whose floor is 1024.

### Fixed
- `validation/scripts/endpoint_lab.py` ran `synthesize.py` from its own folder,
  which stopped being where `synthesize.py` lives when the batteries moved to
  `validation/`.
- **Haiku 4.5 caches again.** Four earned idioms in `config/glossary.tsv`
  (re-mined 2026-09-16 with `idiom_mining.py`, Haiku 4.5, 3 runs — each
  missed 3/3 unaided, and each the kind of thing that gets said: *a spăla
  putina*, *a-și pune pofta-n cui*, *a-i pune capac*, *a da colțul*, whose
  literal "turned the corner" inverts the meaning) take the cached prefix from
  4086 to **4167** tokens one-way (4280 bilingual), 71 over the 4096 floor.
  Live: `write 4161` on the first call, `hit 4161` on the second — the first
  Haiku cache hit since the condense paragraph came out on 2026-09-13.

- **Reverted v2.10.0's short-sentence exemption: one anti-fragment floor for
  every soft cut again.** The exemption let a complete sentence under the floor
  ship on an `endpoint` cut, on the premise that Soniox's `<end>` is acoustic
  evidence, independent of the punctuation, that the speaker stopped. Live the
  same day (Psalm 49-50 read aloud, Endpoint hurry 3) it was not independent:
  the `<end>` for the pause **between verses** arrived after Soniox had already
  finalised the **first word of the next verse**, punctuated as a one-word
  sentence, and sat right behind it in the same message (`held 0ms` in the log,
  the 0.8-1.6s "waiting for the pause" being the silence *before* it). The rule
  shipped that word alone -- `Cheamă.` -> "Call.", `Mănânci.` -> "I eat.",
  `Ascultă.`, `Israele.`, `Jertfele.`, `Nu.`, `și.` -- and the rest of each verse
  followed headless ("Me in the day of trouble, and I will deliver you").
  Twelve in 2.5 minutes at hurry 3, two in 3.5 minutes at the default; every
  one sentence-initial, which is the signature of a late endpoint sweeping in
  the next word rather than of a pause. The fragments also fed the exact-match
  dispatch guard (a re-read verse was dropped as "already dispatched") and the
  draft ledger (`draft debt expired` on most units).

  Held under the floor, the word rides with its own sentence and the spurious
  period costs the translator nothing. `scripts/captures/psalm50-hurry3.jsonl`
  (new) is a reconstruction shaped from that log; `cut_lab.py` replays it at
  Moderato as `Mănânci. Eu carnea taurilor? Oare beau Eu sângele țapilor?` and
  `Cheamă. Mă în ziua necazului, ...` whole under the restored rule, and as
  `Mănânci.` / `Cheamă.` alone under the reverted one. The price is what the
  lab measured in v2.10.0: a short liturgical sentence followed at once by a
  long one waits for it (1.4s median at Moderato); followed by silence it
  still ships on the hardidle backstop. The Endpoint hurry tooltip now says
  that at 3 the `<end>` lands after the Moderato idle timer has already fired,
  so 0 stays the validated default.
- **A projector switched on mid-service no longer replays the sermon so far.**
  The `/caption-feed` poll showed the last three segments on joining and left
  the rest out -- but only the three it showed were recorded as applied, and
  the store returns every segment it holds (40) on every change. So on the
  first new packet after joining, the other 37 were "new" to the page: they
  were appended after the live text and typed out at catch-up speed, in order.
  Reproduced with 40 segments in the store: one new packet after joining put
  history sentences 2 through 37 on the wall over 25 seconds (108 repaints).
  The page still starts from the last `CT_PROJECTOR_LINES` segments plus the
  live draft, but everything before them is now *retired* -- the same state a
  segment reaches when it falls off the top: ignored for good, an operator's
  later edit to one of those rows included. Same 40-segment store, same new
  packet: the wall shows the three context sentences, then the new one, and
  nothing else.
- **The projector no longer goes blank during speech and then dumps a wall of
  text at the first pause.** In a host that pauses `requestAnimationFrame` for a
  page it treats as hidden -- ProPresenter's web object, an OBS browser source
  (`document.hidden` is true there) -- the strip's typewriter never ran, and the
  watchdog added in v2.9.15 to cover exactly that case could be starved: it
  revealed the backlog only when "nothing painted for 350ms", but a draft
  update counted as a paint, and Soniox sends one every few hundred ms while
  someone is speaking. Reproduced with a 15-sentence stream (drafts every
  200ms, finals 1.5s behind) against the page with rAF paused:

  | | first word on the wall | reveal cadence | most spans in the DOM at once |
  |---|---|---|---|
  | before | 10.0s (the first pause) | one dump of 128 spans, ~14 lines, the older 11 fading over 0.7s | 128 |
  | after | 0.30s | one word per 101ms (median), never more than 3 lines | 59 |

  From the room that was: nothing while the preacher talks, then a pile of
  lines landing at once and collapsing. The watchdog is gone (the dwell-aware
  form it took in v2.10.1 included: a per-line hold no longer needs telling
  apart from a stall, because there is no stall). Every typewriter
  step is now scheduled twice -- a `requestAnimationFrame` and a 100ms timer --
  and whichever fires first runs it and cancels the other, so a paused rAF
  costs nothing: the wall paces at the same 10 words a second either way. In a
  normal browser rAF always wins and the timer is never seen. A fallback that
  *is* the typewriter cannot be starved by traffic.
- **A complete short sentence no longer waits for the next one.** `sxCut` applied
  a single anti-fragment floor (`minUnitChars()`, 40 chars at Moderato) to both
  kinds of cut. That floor exists to stop a **comma** cut shipping a fragment,
  because a comma cut is the app's *guess* about where a clause ends. A terminal
  is not a guess — Soniox punctuated it — and conflating the two put the cost
  entirely on short sentences, which in a service means the liturgical ones:
  "Amin." (5 chars), "Să ne rugăm." (12), "Slavă Domnului!" (15), "Har și pace
  vouă." (17) are all under the floor.

  Held, such a sentence does not merely wait out a timer — it is glued to
  whatever is said **next** and rides to the projector on the *next* sentence's
  endpoint. Replaying a real 10-sentence capture (`scripts/cut_lab.py`, new):

  | Pace | units | projector arrival sooner (median) | worst case |
  |---|---|---|---|
  | Pronto | 9 → 10 | 1.10s | 5.99s |
  | Moderato | 6 → 10 | 1.40s | 6.21s |
  | Lento | 4 → 10 | 3.44s | 8.82s |

  A terminal cut is now exempt from the floor when `reason === 'endpoint'` **and
  the span is one sentence** — when Soniox's own detector independently agrees
  the speaker stopped, and the punctuation is not the over-punctuation Soniox
  sometimes produces. Requiring **all** of that is what makes it safe rather
  than merely fast:

  - The danger case is a spurious terminal mid-utterance, and Soniox produces
    them: "Dumnezeu este bun si mila Lui tine in veac" came back as "Dumnezeu
    este bun. **Și, mila.** Lui tine in veac". At Pronto's 700ms endpoint an
    `<end>` *does* land on one of those invented periods, so the endpoint alone
    is not enough — an earlier cut of this shipped "Dumnezeu este bun. Și,
    mila." as a unit. Two terminals inside a sub-floor span is the signature of
    over-punctuation and never of speech (nobody says two sentences in 28
    characters), so requiring exactly one restores the protection the floor had
    been providing at Moderato and Lento, at no cost to the win.
  - On a hesitant speaker (0.9s pauses mid-sentence) the replay is **byte
    identical** between the two rules, which is what a change that cannot fire
    without a terminal should do.
  - An `idle` flush still respects the floor; only the endpoint is trusted.
  - Audited over five captures (liturgical, rapid, mixed, hesitant, run-on) at
    all three Pace stops with a detector validated against a deliberately broken
    rule: **zero** units cut mid-clause on a soft boundary. The only mid-sentence
    cuts the assembler makes are the comma relief valve, which at Pronto splits a
    long run-on (1.67 units per spoken sentence) and at Moderato and Lento never
    fires.

  `scripts/cut_lab.py` replays a capture through a faithful port of today's
  `sxCut` and A/Bs a candidate against it. It supersedes `tune_segmentation.py`,
  which still models the older two-buffer segmenter rather than today's
  append-only buffer and monotonic cursor.

### Added
- **Soniox endpoint tuning, on the Speech-to-text panel.** Soniox exposes three
  endpoint controls and the app was sending one. `scripts/endpoint_lab.py` (new)
  measures what each actually does: it synthesises Romanian with Soniox TTS,
  trims each clip so the last sample really is the last sound, lays the
  sentences out with a known gap, streams at real-time pace, and times every
  `<end>` against those known marks. Measured over 6 sentences x 3 runs on
  `stt-rt-v5`:

  | setting | endpoint latency (end of speech -> `<end>`) |
  |---|---|
  | `max_endpoint_delay_ms` 700 / 1300 / 2200 | 0.34s / 0.32s / 0.31s |
  | `endpoint_latency_adjustment_level` 0/1/2/3 | 0.31s / **0.21s** / 0.15s / 1.70s |
  | `endpoint_sensitivity` −0.5 / 0 / +0.5 / +1 | 0.33s / 0.32s / 0.32s / 0.31s |

  Three findings, all surprises:

  - **The one knob the app was sending does nothing.** `max_endpoint_delay_ms`
    is a *ceiling* (default 2000ms) and the detector fires around 300ms, nowhere
    near it — so the Pace bar's pause value, which is wired to it, never reaches
    Soniox's behaviour. Pace still earns its keep on the browser-side idle
    timers; it just is not what Soniox is doing.
  - **`endpoint_latency_adjustment_level=1` is a real ~100ms win**, and the
    spread *tightens* with it (0.19–0.45s → 0.11–0.30s), with no extra endpoints
    and an unchanged transcript.
  - **Levels 2 and 3 contradict their own documentation** ("higher values reduce
    endpoint latency more aggressively"). Level 3 is not broken though, it is
    *patient*: on a clip with a 0.9s pause inside each sentence it is the only
    level that does not split there (0 wrong splits, against 3 of 3 at levels 0,
    1 and 2). The axis is fast-vs-patient, not fast-vs-broken. Note the
    splitting already happens at today's default, so level 1 adds no new risk.

  `endpoint_sensitivity` measured flat, which is a limit of the clip rather than
  a verdict on the knob: digital silence after clean TTS gives the detector
  nothing ambiguous to be sensitive about, and that is precisely what it is for.
  Both knobs are therefore on the panel — defaulting to today's behaviour, so
  nothing changes until the operator moves them — to be settled in a real room
  rather than guessed at from a lab clip. They are connect-time settings, so the
  UI says they apply on the next Start, and the server names a non-default
  setting in its `[stream] Soniox RT open` line so one Sunday can be compared
  with another.

- **The latency panel now splits the assembler's bill.** `tail` (the silence
  waited out after the speaker stopped) was computed in `flushSonioxThought`
  from the start and never recorded, which left the arguable half invisible —
  `Assembler hold` mixes the speaker still talking, which nothing can fix, with
  waiting to be sure they had stopped, which is exactly what these knobs move. A
  `dispatched by` tally sits under it: `endpoint` means Soniox's `<end>` won the
  race and tuning it will move the caption, while `idle` means the browser's
  backstop got there first and the knobs are being overruled by Pace.
- **Google Gemini as a fourth translator backend** (`scripts/translate_gemini.py`),
  same interface, same system prompt, same caption-safety hardening as the
  Claude / OpenAI / xAI backends — switching to it in the Model dropdown changes
  only the model. Needs `GEMINI_API_KEY`; without it the options are greyed out
  and a saved pick bounces back to the Claude default, as with the other
  providers. Two models are offered, chosen by measurement rather than by
  catalogue order:

  | model | TTFT p50 | TTFT p95 | figurative /61 | homophone /66 | ro-clitic /18 |
  |---|---|---|---|---|---|
  | `gemini-3.5-flash-lite` (default) | 0.54s | 0.67s | 57 / 57 / 56 | 63 / 65 / 64 | 16 / 17 / 16 |
  | `gemini-3.8-flash` | 0.96s | 2.17s | 56 / 56 / 58 | 65 / 64 / 66 | 18 / 17 / 18 |

  Flash-Lite is the faster of the two, and its p95 is the tightest part of the
  result — the tail is what a congregation actually notices. On figurative
  language the two are indistinguishable (that battery moves ±2 cases run to
  run).

  These numbers are **Gemini-internal**: they were measured from a cloud
  container with only a Gemini key present, so they rank the two Gemini models
  against each other and are not comparable with the Claude/GPT/Grok figures
  recorded elsewhere in this file, which were taken on the venue network. Re-run
  `scripts/figurative_stress.py` with every key set to compare providers.

  The one axis where the difference held in **every** run is `ro-clitic`:
  recovering the hyphen Romanian STT drops (*va dat* → *v-a dat*, *mai ascultat*
  → *m-ai ascultat*). 3.8 Flash averaged 17.7/18 to Flash-Lite's 16.3/18, and
  Flash-Lite's misses change the tense — "The Lord *will give* you a new heart"
  for what was said as *has given*. That is the most STT-relevant failure mode
  this app has, which is what earns 3.8 Flash its place as the slower, steadier
  pick when the room's audio is rough.

  `gemini-3.5-flash` was measured and deliberately **not** offered — slower than
  Flash-Lite (0.77s p50) and no better on the battery, so choosing it could only
  cost the operator something.

- **Gemini model pins** (`config/model_pins.json`) with a `gemini` provider
  fingerprint in `scripts/model_pins.py`. The pinned field that matters is
  `thinking_level`, for the reason below.

- **Explicit prompt caching for Gemini**, on by default. The shared system
  prompt is uploaded once as a `cachedContents` object and referenced by handle,
  instead of being re-sent and re-billed on every caption:

  | | prompt tokens | served from cache | p50 |
  |---|---|---|---|
  | inline `system_instruction` | 3893 | 0 | 0.83s |
  | `cachedContent` handle | 3893 | **3872 (99.5%)** | 0.83s |

  Note the latency is **unchanged** — this is a cost feature, not a
  caption-latency one. Knobs: `CT_GEMINI_CACHE=0` disables it,
  `CT_GEMINI_CACHE_TTL` sets the lifetime (default 3600s, minimum 120s; a bad
  value falls back to the default rather than stopping the app from starting).

  It is built to be impossible to lose a caption to:
  - The cache is created on a **background thread**, so no caption ever waits
    for one. `warm()` starts it at server startup, so the first caption of a
    service is already a hit.
  - Every failure falls back to sending the prompt inline — the pre-caching
    behaviour, which always works. A stale handle (403) is retried inline
    in-place and the handle dropped so the next call rebuilds.
  - A prompt under the API's 1024-token minimum (measured: 1023 fails, 1024
    passes) is marked permanently un-cacheable and never retried, so an
    operator who guts the glossary gets a slightly larger bill, not an error.
  - Caches are keyed by **model + prompt hash**, so the two Gemini models and a
    bilingual session's `pair` prompt each get their own rather than colliding.

  Cached objects bill storage while they live, so they are also cleaned up:
  `atexit` deletes what the process built on a clean shutdown or Ctrl-C. That
  does **not** cover SIGTERM or a pulled plug (measured — the object survives),
  so each cache is stamped with a `displayName` of its model + prompt hash and a
  later run **adopts** a still-live match instead of building a duplicate. Net
  effect: restarting the app repeatedly during setup leaves one cache, not one
  per run. An adopted cache is never deleted on exit, since another machine on
  the same key may still be using it; its TTL is the backstop.

- **The Translation panel's cache light now works for Gemini**, not just Claude.
  Both report exact per-call counters, so `hit` / `off` is a real measurement
  rather than a guess; OpenAI and xAI still leave it dark. Gemini never shows
  `write` — its cache is built by a separate request, so no single caption is
  the one that pays to create it.

### Changed
- **Pace bar: "Mid" is now "Moderato"**, so all three stops are consistent
  tempo terms (Pronto / Moderato / Lento). Display only — the setting persists
  as a numeric index, so saved settings are unaffected.

### Notes
- **Every Gemini 3.x model reasons by default**, and unlike Grok there is no
  separate non-reasoning model id to pin: the same id is a reasoning or a
  non-reasoning model depending on one request field. Measured on a
  one-sentence translation, the defaults spend ~490 hidden tokens before the
  first word (`gemini-3.6-flash`: 4.1s). Every request therefore pins
  `thinkingLevel` explicitly, and `thoughtsTokenCount` is checked on every
  response — a non-zero value warns once per model. This is the grok-4.3
  mistake (v2.7.0) with a different spelling, and it is guarded the same way.
  The floor is **per model** and not guessable from the version number: 3.5/3.6
  Flash accept `minimal`, while 3.7/3.8 Flash reject it with a 400 and floor at
  `low`. An unknown model tries `minimal`, falls back once, and remembers.
- **A floor is not a guarantee — for one of the two.** The difference is
  *margin*, not luck. Sweeping thinking levels on one input (thought tokens):

  | | minimal | low | medium | high |
  |---|---|---|---|---|
  | `gemini-3.5-flash-lite` | 0 | 0 | 2797 | 3964 |
  | `gemini-3.8-flash` | *(400 error)* | 438 | 994 | 1643 |

  Flash-Lite is not incapable of reasoning — asked at `medium` it reasons hard.
  It is pinned **two levels below its own threshold**, so the pin has room to be
  wrong in. 3.8 Flash has none: `minimal` is refused outright, leaving its floor
  sitting *on* its reasoning threshold. `LEAKY_FLOOR` records which models are
  in that position so the log tells expected-occasional thinking (a quiet
  `NOTE`) apart from a pin that actually broke (a `WARNING`) — an alarm that
  cries wolf on known behaviour is one the operator learns to ignore.

- **Soak-tested for reasoning spill** (`scripts/reasoning_soak.py`, new).
  `gemini-3.5-flash-lite` at `minimal`: **1600 calls, zero thought tokens** —
  1200 with the prompt cache on, 400 with it off, across nine stress conditions
  built from 293 real sentences in the repo's own batteries (plain,
  three-sentence, comma run-on, mid-clause fragment, diacritic- and
  hyphen-stripped STT damage, six-turn history, draft anchor, bilingual pair,
  and the fixed repeated input the first leak was caught on). 95% upper bound on
  the leak rate, by the rule of three: **0.19%**.

  The harness runs `gemini-3.8-flash` as a **positive control** and refuses to
  report a reassuring zero if that control comes back clean — a negative result
  is only worth having if the detector is shown to detect. It caught the control
  leaking 13 times in 180 calls (7.2%), 8 of 20 on the repeated input alone.

  What this does *not* prove is that Google will keep `minimal` meaning what it
  means today — 3.7 and 3.8 Flash have already had that level taken away. That
  is a catalogue risk rather than a per-call one, which is what the pins file
  and the warn-once check are for.
- **Gemini's implicit cache never fires for this workload** — over six
  back-to-back calls with a byte-identical ~3.9K-token prefix,
  `cachedContentTokenCount` stayed 0. Why is not something the API explains, and
  it is recorded as an observation rather than guessed at. It is the reason the
  caching below is explicit rather than automatic.
- **The translation-specialised Gemini model is not usable here.**
  `gemini-3.5-live-translate-preview` exists, but its
  `supportedGenerationMethods` is `["bidiGenerateContent"]` only — it is a Live
  API speech-to-speech model reached over a WebSocket, not a text translator.
  It would be a fourth *engine* alongside Soniox+LLM+TTS, not a fourth entry in
  this picker.


## [v2.9.16] — 2026-09-13

### Changed
- Version bump from v2.9.15 to v2.9.16
- Built standalone Windows executable


## [v2.9.15] — 2026-09-13

### Changed
- Version bump from v2.9.14 to v2.9.15
- Built standalone Windows executable


## [v2.9.14] — 2026-09-13

### Changed
- Version bump from v2.9.13 to v2.9.14
- Built standalone Windows executable


## [v2.9.13] — 2026-09-13

### Changed
- Version bump from v2.9.12 to v2.9.13
- Built standalone Windows executable


## [v2.9.12] — 2026-09-13

### Changed
- Version bump from v2.9.11 to v2.9.12
- Built standalone Windows executable


## [v2.9.11] — 2026-09-13

### Changed
- Version bump from v2.9.10 to v2.9.11
- Built standalone Windows executable


## [v2.9.10] — 2026-09-13

### Changed
- Version bump from v2.9.9 to v2.9.10
- Built standalone Windows executable


## [v2.9.9] — 2026-09-13

### Changed
- Version bump from v2.9.8 to v2.9.9
- Built standalone Windows executable


## [v2.9.8] — 2026-09-13

### Changed
- Version bump from v2.9.7 to v2.9.8
- Built standalone Windows executable


## [v2.9.7] — 2026-09-13

### Changed
- Version bump from v2.9.6 to v2.9.7
- Built standalone Windows executable


## [v2.9.6] — 2026-09-12

### Changed
- Version bump from v2.9.5 to v2.9.6
- Built standalone Windows executable


## [v2.9.5] — 2026-09-11

### Changed
- Version bump from v2.9.4 to v2.9.5
- Built standalone Windows executable


## [v2.9.4] — 2026-09-11

### Added
- **Version banner at startup.** The server now prints `[app] Church Translate
  v<version>` as its first line, so which build is running is unambiguous from the
  console (previously the only tell was whether optional features logged their
  own status). The version is read from `version.txt`, which is now bundled into
  the PyInstaller build so the frozen exe reports it too.

## [v2.9.3] — 2026-09-10

### Added
- **Typewriter / rolling projection for ProPresenter.** With
  `CT_PROPRESENTER_TYPE=word`, each finalized caption is revealed a few words at a
  time into a fixed-height, bottom-aligned lower third instead of replacing the
  whole line at once; as new words type in, older lines roll off the top.
  ProPresenter has no typewriter of its own, so the broker re-sends the Message
  token as the text grows. A single reveal thread does the timing and **adapts to
  its backlog** — a growing queue reveals more words per step and a large backlog
  snaps ahead, so the caption never lags the room — and it suppresses redundant
  frames. New knobs (all optional; default `off` leaves the original whole-line
  behaviour untouched):
  - `CT_PROPRESENTER_TYPE` — `off` | `word`
  - `CT_PROPRESENTER_TYPE_MS` — milliseconds between reveals (default 120)
  - `CT_PROPRESENTER_TYPE_WORDS` — words revealed per step (default 1)
  - `CT_PROPRESENTER_LINES` — visible caption lines / rolling-window height (default 2)
  - `CT_PROPRESENTER_COLS` — approx. characters per line, for wrapping (default 42)

  In ProPresenter, `word` mode expects the Message's text box to be
  `CT_PROPRESENTER_LINES` lines tall, vertically bottom-aligned, with "Shrink text
  to fit" off and the theme's transitions set to none (rapid re-triggers would
  otherwise re-run the build animation and flicker).

## [v2.9.2] — 2026-09-09

### Changed
- Version bump from v2.9.1 to v2.9.2
- Built standalone Windows executable


## [v2.9.1] — 2026-09-09

### Fixed
- **ProPresenter projection failed with "No connection adapters were found"
  when the `.env` value carried an inline comment.** `.env.example` annotates
  each key with a trailing `# ...` note (e.g. `CT_PROPRESENTER_URL=... #
  http://<ip>:<port>`), but the `.env` loader only skipped lines that *began*
  with `#` -- so a value filled in ahead of that note swallowed the whole
  comment, turning the URL into `presenter...:1025          # http://...` and
  the message name into `Placeholder # the Message's name...`. The loader now
  strips an inline comment (a `#` preceded by whitespace) from unquoted values,
  while a quoted value keeps a literal `#`.
- **A ProPresenter URL given as a bare `host:port` now works.** `requests`
  needs a scheme or it raises "No connection adapters were found", so
  `CT_PROPRESENTER_URL` without `://` now defaults to the `http://` the local
  ProPresenter API speaks.

## [v2.9.0] — 2026-09-08

### Changed
- Version bump from v2.8.0 to v2.9.0
- Built standalone Windows executable


## [v2.8.0] — 2026-08-31

### Changed
- Version bump from v2.7.0 to v2.8.0
- Built standalone Windows executable


## [Unreleased]

### Fixed
- **A complete short sentence no longer waits for the next one.** `sxCut` applied
  a single anti-fragment floor (`minUnitChars()`, 40 chars at Moderato) to both
  kinds of cut. That floor exists to stop a **comma** cut shipping a fragment,
  because a comma cut is the app's *guess* about where a clause ends. A terminal
  is not a guess — Soniox punctuated it — and conflating the two put the cost
  entirely on short sentences, which in a service means the liturgical ones:
  "Amin." (5 chars), "Să ne rugăm." (12), "Slavă Domnului!" (15), "Har și pace
  vouă." (17) are all under the floor.

  Held, such a sentence does not merely wait out a timer — it is glued to
  whatever is said **next** and rides to the projector on the *next* sentence's
  endpoint. Replaying a real 10-sentence capture (`scripts/cut_lab.py`, new):

  | Pace | units | projector arrival sooner (median) | worst case |
  |---|---|---|---|
  | Pronto | 9 → 10 | 1.10s | 5.99s |
  | Moderato | 6 → 10 | 1.40s | 6.21s |
  | Lento | 4 → 10 | 3.44s | 8.82s |

  A terminal cut is now exempt from the floor when `reason === 'endpoint'` **and
  the span is one sentence** — when Soniox's own detector independently agrees
  the speaker stopped, and the punctuation is not the over-punctuation Soniox
  sometimes produces. Requiring **all** of that is what makes it safe rather
  than merely fast:

  - The danger case is a spurious terminal mid-utterance, and Soniox produces
    them: "Dumnezeu este bun si mila Lui tine in veac" came back as "Dumnezeu
    este bun. **Și, mila.** Lui tine in veac". At Pronto's 700ms endpoint an
    `<end>` *does* land on one of those invented periods, so the endpoint alone
    is not enough — an earlier cut of this shipped "Dumnezeu este bun. Și,
    mila." as a unit. Two terminals inside a sub-floor span is the signature of
    over-punctuation and never of speech (nobody says two sentences in 28
    characters), so requiring exactly one restores the protection the floor had
    been providing at Moderato and Lento, at no cost to the win.
  - On a hesitant speaker (0.9s pauses mid-sentence) the replay is **byte
    identical** between the two rules, which is what a change that cannot fire
    without a terminal should do.
  - An `idle` flush still respects the floor; only the endpoint is trusted.
  - Audited over five captures (liturgical, rapid, mixed, hesitant, run-on) at
    all three Pace stops with a detector validated against a deliberately broken
    rule: **zero** units cut mid-clause on a soft boundary. The only mid-sentence
    cuts the assembler makes are the comma relief valve, which at Pronto splits a
    long run-on (1.67 units per spoken sentence) and at Moderato and Lento never
    fires.

  `scripts/cut_lab.py` replays a capture through a faithful port of today's
  `sxCut` and A/Bs a candidate against it. It supersedes `tune_segmentation.py`,
  which still models the older two-buffer segmenter rather than today's
  append-only buffer and monotonic cursor.

### Added
- **Soniox endpoint tuning, on the Speech-to-text panel.** Soniox exposes three
  endpoint controls and the app was sending one. `scripts/endpoint_lab.py` (new)
  measures what each actually does: it synthesises Romanian with Soniox TTS,
  trims each clip so the last sample really is the last sound, lays the
  sentences out with a known gap, streams at real-time pace, and times every
  `<end>` against those known marks. Measured over 6 sentences x 3 runs on
  `stt-rt-v5`:

  | setting | endpoint latency (end of speech -> `<end>`) |
  |---|---|
  | `max_endpoint_delay_ms` 700 / 1300 / 2200 | 0.34s / 0.32s / 0.31s |
  | `endpoint_latency_adjustment_level` 0/1/2/3 | 0.31s / **0.21s** / 0.15s / 1.70s |
  | `endpoint_sensitivity` −0.5 / 0 / +0.5 / +1 | 0.33s / 0.32s / 0.32s / 0.31s |

  Three findings, all surprises:

  - **The one knob the app was sending does nothing.** `max_endpoint_delay_ms`
    is a *ceiling* (default 2000ms) and the detector fires around 300ms, nowhere
    near it — so the Pace bar's pause value, which is wired to it, never reaches
    Soniox's behaviour. Pace still earns its keep on the browser-side idle
    timers; it just is not what Soniox is doing.
  - **`endpoint_latency_adjustment_level=1` is a real ~100ms win**, and the
    spread *tightens* with it (0.19–0.45s → 0.11–0.30s), with no extra endpoints
    and an unchanged transcript.
  - **Levels 2 and 3 contradict their own documentation** ("higher values reduce
    endpoint latency more aggressively"). Level 3 is not broken though, it is
    *patient*: on a clip with a 0.9s pause inside each sentence it is the only
    level that does not split there (0 wrong splits, against 3 of 3 at levels 0,
    1 and 2). The axis is fast-vs-patient, not fast-vs-broken. Note the
    splitting already happens at today's default, so level 1 adds no new risk.

  `endpoint_sensitivity` measured flat, which is a limit of the clip rather than
  a verdict on the knob: digital silence after clean TTS gives the detector
  nothing ambiguous to be sensitive about, and that is precisely what it is for.
  Both knobs are therefore on the panel — defaulting to today's behaviour, so
  nothing changes until the operator moves them — to be settled in a real room
  rather than guessed at from a lab clip. They are connect-time settings, so the
  UI says they apply on the next Start, and the server names a non-default
  setting in its `[stream] Soniox RT open` line so one Sunday can be compared
  with another.

- **The latency panel now splits the assembler's bill.** `tail` (the silence
  waited out after the speaker stopped) was computed in `flushSonioxThought`
  from the start and never recorded, which left the arguable half invisible —
  `Assembler hold` mixes the speaker still talking, which nothing can fix, with
  waiting to be sure they had stopped, which is exactly what these knobs move. A
  `dispatched by` tally sits under it: `endpoint` means Soniox's `<end>` won the
  race and tuning it will move the caption, while `idle` means the browser's
  backstop got there first and the knobs are being overruled by Pace.
- **`scripts/idiom_mining.py` + `config/idiom_candidates.tsv`** - a sieve for
  the question "can the glossary reach the 4096-token cache floor without
  padding?". 136 candidate Romanian idioms, regionalisms and church-register
  terms, each with a test sentence, run through the translator with an **empty
  glossary**; only the ones that come out wrong have any claim on a slot.

  First run (Haiku 4.5, 3 runs each): **69 known, 7 mixed, 60 missed - 67 earn
  a place**, costing 1189 tokens. That puts the prompt at ~3454 bilingual,
  **642 short** of 4096, needing roughly 36 more earned entries (~73 more
  candidates at the current 49% yield). So the floor is reachable on earned
  entries alone - but see the caveat below.

  The sharpest result is a negative one: **church register scored 20/20 known.**
  Haiku renders neprihanire, partasie, faradelege, nadejde, osanda, ispravnic
  and ravna correctly with no help at all, including Cornilescu's distinctive
  vocabulary. The category that feels most obviously worth glossing is worth
  nothing. The value is in idioms (43/78 missed outright) and regional
  vocabulary (17/38) - papusoi came back as "scarecrow", curechi as "cucumbers",
  perje as "I picked feathers for a magpie".

  **Earned is not the same as frequent.** The sieve proves a model gets an
  expression wrong, not that a preacher will ever say it. Entries beyond the
  first ~67 are progressively rarer idioms, so marginal value keeps falling
  even though every entry still passes the test. Reaching the floor by adding
  entries optimises for the cache, not for the captions.

  Results are per-model: `--models` re-mines for whichever translator is
  actually in use, since these are Haiku's gaps specifically.

### Changed
- **A fourth rung on the drift ladder.** Spoken output now plays at **1.4x**
  once it falls more than `RATE3_AT` (16s) behind the speaker, above the
  existing 1.1x (>4s) and 1.25x (>10s) tiers. The gap it fills: between 10s and
  the 25s skip-ahead there was only 1.25x, which claws back 0.25s per second
  spoken -- a full minute to recover the 15s band, long enough that a fast
  speaker outruns it and the skip fires, losing the text rather than hurrying
  it. At 1.4x the same backlog drains in ~38s idle, and ~84s rather than ~120s
  against a speaker still adding to it.

  This is `playbackRate` on the output element, pitch-preserved and
  client-side, so it is unconstrained by the TTS provider's own speed limit --
  the Speed slider stops at 1.20x because that is ElevenLabs' documented
  ceiling for `voice_settings.speed`, and that knob is a fixed synthesis rate
  for every sentence rather than a response to falling behind. Behaviour below
  16s of drift is unchanged.

## [v2.7.0] — 2026-08-30

### Changed
- Version bump from v2.5.0 to v2.7.0
- Built standalone Windows executable


## [v2.5.0] — 2026-08-30

### Changed
- Version bump from v2.4.0 to v2.5.0
- Built standalone Windows executable


## [v2.4.0] — 2026-08-25

### Changed
- Version bump from v2.3.0 to v2.4.0
- Built standalone Windows executable


## [v2.3.0] — 2026-08-24

### Changed
- Version bump from v2.2.0 to v2.3.0
- Built standalone Windows executable


## [Unreleased]

### Fixed
- **A complete short sentence no longer waits for the next one.** `sxCut` applied
  a single anti-fragment floor (`minUnitChars()`, 40 chars at Moderato) to both
  kinds of cut. That floor exists to stop a **comma** cut shipping a fragment,
  because a comma cut is the app's *guess* about where a clause ends. A terminal
  is not a guess — Soniox punctuated it — and conflating the two put the cost
  entirely on short sentences, which in a service means the liturgical ones:
  "Amin." (5 chars), "Să ne rugăm." (12), "Slavă Domnului!" (15), "Har și pace
  vouă." (17) are all under the floor.

  Held, such a sentence does not merely wait out a timer — it is glued to
  whatever is said **next** and rides to the projector on the *next* sentence's
  endpoint. Replaying a real 10-sentence capture (`scripts/cut_lab.py`, new):

  | Pace | units | projector arrival sooner (median) | worst case |
  |---|---|---|---|
  | Pronto | 9 → 10 | 1.10s | 5.99s |
  | Moderato | 6 → 10 | 1.40s | 6.21s |
  | Lento | 4 → 10 | 3.44s | 8.82s |

  A terminal cut is now exempt from the floor when `reason === 'endpoint'` **and
  the span is one sentence** — when Soniox's own detector independently agrees
  the speaker stopped, and the punctuation is not the over-punctuation Soniox
  sometimes produces. Requiring **all** of that is what makes it safe rather
  than merely fast:

  - The danger case is a spurious terminal mid-utterance, and Soniox produces
    them: "Dumnezeu este bun si mila Lui tine in veac" came back as "Dumnezeu
    este bun. **Și, mila.** Lui tine in veac". At Pronto's 700ms endpoint an
    `<end>` *does* land on one of those invented periods, so the endpoint alone
    is not enough — an earlier cut of this shipped "Dumnezeu este bun. Și,
    mila." as a unit. Two terminals inside a sub-floor span is the signature of
    over-punctuation and never of speech (nobody says two sentences in 28
    characters), so requiring exactly one restores the protection the floor had
    been providing at Moderato and Lento, at no cost to the win.
  - On a hesitant speaker (0.9s pauses mid-sentence) the replay is **byte
    identical** between the two rules, which is what a change that cannot fire
    without a terminal should do.
  - An `idle` flush still respects the floor; only the endpoint is trusted.
  - Audited over five captures (liturgical, rapid, mixed, hesitant, run-on) at
    all three Pace stops with a detector validated against a deliberately broken
    rule: **zero** units cut mid-clause on a soft boundary. The only mid-sentence
    cuts the assembler makes are the comma relief valve, which at Pronto splits a
    long run-on (1.67 units per spoken sentence) and at Moderato and Lento never
    fires.

  `scripts/cut_lab.py` replays a capture through a faithful port of today's
  `sxCut` and A/Bs a candidate against it. It supersedes `tune_segmentation.py`,
  which still models the older two-buffer segmenter rather than today's
  append-only buffer and monotonic cursor.

### Added
- **Soniox endpoint tuning, on the Speech-to-text panel.** Soniox exposes three
  endpoint controls and the app was sending one. `scripts/endpoint_lab.py` (new)
  measures what each actually does: it synthesises Romanian with Soniox TTS,
  trims each clip so the last sample really is the last sound, lays the
  sentences out with a known gap, streams at real-time pace, and times every
  `<end>` against those known marks. Measured over 6 sentences x 3 runs on
  `stt-rt-v5`:

  | setting | endpoint latency (end of speech -> `<end>`) |
  |---|---|
  | `max_endpoint_delay_ms` 700 / 1300 / 2200 | 0.34s / 0.32s / 0.31s |
  | `endpoint_latency_adjustment_level` 0/1/2/3 | 0.31s / **0.21s** / 0.15s / 1.70s |
  | `endpoint_sensitivity` −0.5 / 0 / +0.5 / +1 | 0.33s / 0.32s / 0.32s / 0.31s |

  Three findings, all surprises:

  - **The one knob the app was sending does nothing.** `max_endpoint_delay_ms`
    is a *ceiling* (default 2000ms) and the detector fires around 300ms, nowhere
    near it — so the Pace bar's pause value, which is wired to it, never reaches
    Soniox's behaviour. Pace still earns its keep on the browser-side idle
    timers; it just is not what Soniox is doing.
  - **`endpoint_latency_adjustment_level=1` is a real ~100ms win**, and the
    spread *tightens* with it (0.19–0.45s → 0.11–0.30s), with no extra endpoints
    and an unchanged transcript.
  - **Levels 2 and 3 contradict their own documentation** ("higher values reduce
    endpoint latency more aggressively"). Level 3 is not broken though, it is
    *patient*: on a clip with a 0.9s pause inside each sentence it is the only
    level that does not split there (0 wrong splits, against 3 of 3 at levels 0,
    1 and 2). The axis is fast-vs-patient, not fast-vs-broken. Note the
    splitting already happens at today's default, so level 1 adds no new risk.

  `endpoint_sensitivity` measured flat, which is a limit of the clip rather than
  a verdict on the knob: digital silence after clean TTS gives the detector
  nothing ambiguous to be sensitive about, and that is precisely what it is for.
  Both knobs are therefore on the panel — defaulting to today's behaviour, so
  nothing changes until the operator moves them — to be settled in a real room
  rather than guessed at from a lab clip. They are connect-time settings, so the
  UI says they apply on the next Start, and the server names a non-default
  setting in its `[stream] Soniox RT open` line so one Sunday can be compared
  with another.

- **The latency panel now splits the assembler's bill.** `tail` (the silence
  waited out after the speaker stopped) was computed in `flushSonioxThought`
  from the start and never recorded, which left the arguable half invisible —
  `Assembler hold` mixes the speaker still talking, which nothing can fix, with
  waiting to be sure they had stopped, which is exactly what these knobs move. A
  `dispatched by` tally sits under it: `endpoint` means Soniox's `<end>` won the
  race and tuning it will move the caption, while `idle` means the browser's
  backstop got there first and the knobs are being overruled by Pace.
- **Voice capture can now drop the speaker's accent.** A clone reproduces
  accent along with timbre, so a Romanian speaker cloned into English sounded
  like a Romanian speaking English — faithful to the person, wrong for a service
  translating *into* English. Soniox offers no lever for it; ElevenLabs does, so
  soundcheck capture now works on **either** backend and gains a **Neutral
  accent** option on ElevenLabs.
  - Ticking it clones the take, then remixes the clone towards native
    pronunciation in the **current target language** (`POST
    /v1/text-to-voice/{id}/remix`), keeps the remix and deletes the raw clone,
    which would otherwise hold a voice slot sounding wrong. Adds about a minute,
    once, at soundcheck — nothing changes in the live path.
  - **Strength** (light / medium / strong) is how far the remix may travel from
    the recording. Both ends are failure modes — light leaves the accent in,
    strong can stop sounding like the speaker — so it is three named steps
    rather than a raw slider, and the tooltip says to recapture and step up if a
    take lands wrong.
  - The remix is conditioned on a demo script as well as the description, and
    that script is rendered into the target language first via the translator
    the app already runs. ElevenLabs' own `auto_generate_text` writes it in
    **English** even when the description asks for a native Spanish voice
    (measured against the live API) — which conditions the remix to speak
    English with a Spanish accent, the exact thing being removed. It stays as
    the fallback when the translator is unreachable.
  - A failed accent pass is **not** an error: the plain clone is kept and the
    app says the voice carries the speaker's own accent, rather than throwing
    away a take the speaker already stood there and recorded.
  - The reference clip is uploaded with `remove_background_noise` on, so the
    clone is the speaker rather than the room the soundcheck happened in.
  - Deleting a voice that is already gone counts as success. ElevenLabs reports
    that as a **400** with status `voice_does_not_exist`, not a 404, so the
    status code alone can't be trusted — the first cut showed the operator an
    error for a delete that had done exactly what they asked.
  - The ElevenLabs voice picker now marks captured voices (`cloned` and
    `generated` categories) and floats them above the library, matching how the
    Soniox picker already behaved; **Test** and **Delete** follow the pick on
    both backends, and the voice list cache is dropped after a capture or delete
    so a new voice appears immediately.
- **The service can be saved off — audio, timings and text together.** Every
  clip the TTS stage produces is now kept as it streams, alongside the sentence
  it speaks, the source line it was translated from and when it was spoken.
  **⭳ Audio** (`A`) downloads one `.zip` holding `audio.mp3` (the whole service
  as one file), `captions.srt` / `.vtt` timed to it, `segments.json` mapping
  every clip to its text, its position in the audio *and* its wall-clock time,
  and a side-by-side `transcript.html`.
  - Buffered **server-side**, where the mp3 bytes already are: recording them
    costs one reference rather than a second copy of the traffic, and a reloaded
    or crashed tab no longer takes the recording with it.
  - Clip lengths are summed from the mp3's **own frame headers** rather than
    divided out of a nominal bitrate, so a subtitle cue lands on the words even
    if a provider ever ships VBR. Concatenated frames are a valid mp3, so saving
    is a join — nothing is re-encoded and no decoder runs.
  - Two clocks per clip, because they answer different questions: seconds into
    the saved file, and when it was actually spoken. The gap between them grows
    all service, since the file holds only the spoken parts.
  - **Auto-save on exit** (ticked by default): closing the window beacons a
    save, and the server also writes the buffer out on its own way down, so a
    window closed with the server running and a server stopped without the
    window are both covered. Controls live in the **3 · Text-to-speech** panel
    (*Record speech*, *Save now*, *Discard*); `CT_RECORD`, `CT_RECORD_AUTOSAVE`,
    `CT_RECORD_DIR` and `CT_RECORD_MAX_MB` set the defaults.
  - `/translate`'s `audio_chunk` lines now name the sentence they speak on the
    seq-0 line, which is what lines a clip up with its words — a caption is a
    whole batch, a clip is one sentence of it. In condensed mode the two differ,
    and Replay's tooltip now says what will actually be spoken.

### Changed
- **The translator now avoids unintended double meanings.** Where a faithful
  rendering allows more than one natural wording, the prompt picks the one that
  cannot be heard as innuendo: Romanian *"am lucrat la sapă"* is literally *"I
  worked with a hoe"*, while *"I worked the fields"* says exactly the same thing
  and cannot be laughed at. Explicitly a choice **between equally faithful
  wordings** — not a licence to soften, blur or drop meaning — and it leaves the
  existing rule untouched: blunt scriptural language stays exactly as blunt as
  Scripture is. The point is that nothing becomes suggestive that wasn't, not
  that anything suggestive gets cleaned up.
- **Spoken output starts sooner, on both backends.** Four things were making
  every clip slower than it needed to be, none of them the providers' fault:
  - The first sentence of a service paid a full DNS + TCP + TLS handshake
    (~0.6s measured) before synthesis even began, because nothing had contacted
    the TTS host yet. Pressing **Start** now opens that connection in the
    background, while the speaker is still clearing their throat.
  - The server kept *two* connection pools to `api.elevenlabs.io` — one for
    synthesis, one for the credit/voice polling. The polling was keeping the
    wrong one warm. They are now a single pool, so the 30-second credit check
    doubles as a keep-alive for the connection the next clip actually speaks
    over.
  - All synthesis shared one global mutex, which bought no ordering (clips are
    already kept in order per request) but did make the first sentence of one
    segment wait for the last sentence of the previous one — and let a Replay
    tap stall the live voice outright. Up to three clips may now be in flight
    at once (`CT_TTS_MAX_INFLIGHT`).
  - Each clip was decoded from the provider's PCM into float32 and then
    re-encoded back to identical 16-bit PCM before being sent. The audio now
    travels from the provider to the browser without being decoded at all.
- **Spoken output starts while the sentence is still being generated.** Soniox's
  endpoint is a *realtime* one: the first audio arrives about a third of a second
  in, and it keeps generating at ~1.2× the speed the words are spoken. We were
  reading the whole response before playing any of it, which threw that away —
  a long sentence sat silent for ~11 seconds waiting for audio whose first bytes
  had been ready since 0.4s. Audio is now relayed to the browser in slices as it
  arrives, and playback begins once a third of a second is in hand. Measured on a
  full sentence through the live pipeline: **first word at 3.0s instead of 8.4s.**
  Because generation outruns playback, the buffer only grows from there, so the
  saving is not paid back later in stalls.
  - Spoken audio is now **mp3** rather than raw PCM end to end. Playing partial
    audio in the browser means MediaSource, and MediaSource requires a real
    container — it will not take WAV. mp3 also cuts the audio bandwidth to the
    browser by about two thirds.
  - Sentences are appended to **one** long-lived audio element instead of a new
    one per clip, so they run together on a single continuous timeline. The gap
    that used to fall between sentences is gone, and the chosen output device is
    routed once per service rather than once per sentence.
  - The catch-up ladder (×1.1 / ×1.25 / ⏩ condense / skip-ahead) is unchanged and
    still pitch-preserved; drift is now measured from the playback buffer
    directly. Browsers that can't stream mp3 through MediaSource fall back
    automatically to the previous per-sentence clip playback.
- **A stalled TTS connection can no longer freeze spoken output.** The cloud
  calls had a single 30-second budget covering both connect and generation, so
  a connection that never established held a synthesis slot for the full half
  minute. Connect now gets 3 seconds of that; generation keeps the rest.

### Fixed
- **The translator no longer waits for the voice to finish.** `/translate` sent
  its `final` line only after synthesizing the whole batch, and the browser holds
  one batch at a time until `final` — so a request occupied the pipeline for as
  long as its speech took to generate. Soniox generates at roughly realtime and a
  translation takes about as long to say as the original, so each batch cost
  about as much time as it bought: measured at **7.0s held per call against 0.9s
  of actual model time**, with the room 13s behind and falling further. The
  caption is now sent the moment the model finishes and the audio streams after
  it, so the next translation runs while the current one is still being spoken.
  Model calls stay strictly sequential, so the rolling sermon context is still
  built in spoken order — only the audio tails overlap.
  - Because those tails overlap, audio can no longer go straight to the player:
    two translations would interleave mid-sentence on the single timeline. Each
    batch now takes a ticket at dispatch and only the oldest open ticket may
    append, so speech stays in spoken order however the responses arrive.
  - A request now has two halves with different failure rules. Before `final`
    there is no translation and the existing retry is the recovery; after it the
    caption is already on the wall, the projector and the transcript, so a
    failure costs that batch its voice and nothing else — retrying would
    re-translate and re-speak a sentence the room has already read.
  - Replay appears a beat after the caption it belongs to, since it needs the
    finished clips.
- **Condensed speech now triggers during the backlog it exists to relieve.** The
  ⏩ request keyed off the playback buffer alone, but under a backlog the unspoken
  words are sitting in the translate queue, not the buffer — so the buffer stayed
  under the 10s trigger while the room fell 13s behind, and condense fired zero
  times in a service that needed it throughout. It now counts queued-but-not-yet-
  translated speech as well. The catch-up ladder still keys off the buffer, as it
  must: playback rate and skip-ahead can only act on audio that exists.
- **A failed translation can no longer stop the translator for the rest of the
  service.** `translating` — the flag that lets one batch out at a time — was
  released *after* the try/catch rather than inside the `finally`, so it was
  only cleared if the error handler itself also ran cleanly. That handler paints
  into a transcript cell and settles the draft ledger, neither of which is
  guaranteed to survive (Clear can remove the cell out from under it). One throw
  there closed the gate permanently: speech-to-text kept filling the Original
  column, every translation stayed blank, nothing was logged, and only Stop/Start
  brought it back. Released in the `finally` now, on every exit path.
- **The translator's backlog is now visible instead of inferred.** The
  Translation box shows how many units are queued behind the current call, in
  warn colour past three, and the event log says once per backlog whether a call
  is genuinely out (and for how long) or the gate is shut with nothing in
  flight — the difference between "falling behind" and "stopped", which look
  identical from the room.
- **A retried translation keeps its projector segment.** The retry dropped the
  segment id, so the caption strip appended a second copy of the text instead of
  replacing the failed partial — which its own comment said it would do.
- **Gate music switched off no longer strands the pipeline.** Unchecking the box
  while the *detector* was holding a song left the mode stuck on Music with the
  Soniox socket closed — and with the detector barred from moving it, nothing
  left could reopen it, so captions stopped until someone noticed the badge.
  Turning the gate off now releases a detector-placed hold; a hold the operator
  set by hand is an instruction and stays put. Switching it back on re-arms the
  dwell timers, so the first automatic move rests on fresh evidence rather than
  a verdict that ripened while nothing was acting on it.

### Added
- **The transcript shows which line is being spoken.** A ♪ and a green bar mark
  the translation currently coming out of the loudspeaker, and the mark walks
  down the rows as playback advances. The captions run ahead of the voice by
  design, so this is the one thing in the window that says how far back the room
  actually is — the drift readout gives that in seconds, this gives it in
  sentences, which is the unit an operator can act on. Each batch's speech is
  mapped to its position on the audio timeline as it is appended, so the mark
  follows the loudspeaker rather than the order the translations arrived in.
  The same ♪ turns **amber** when the voice has finished that line and has
  nothing else to say — caught up, waiting on the next sentence — so an empty
  pipeline is visible at a glance instead of reading as "still reciting". Both
  marks come off once the session is idle.
- **Latency for every stage.** Until now the translator was the only part of the
  pipe that reported a number, so "the voice is behind" had no answer beyond
  guesswork. Each stage box now carries its own average along the bottom, and
  **Debug** shows the full table — five figures, each with the last value beside
  a rolling average over the last 30 samples:
  - **STT lag** — how far behind the room the transcript is: the gap between
    audio leaving the microphone and Soniox's final token for it coming back.
    Measured against the audio actually sent rather than the wall clock, so a
    music hold can't inflate it.
  - **Assembler hold** — first word of a unit to the moment it's sent for
    translation. This is the one delay that is *chosen* rather than incurred
    (waiting for a whole sentence), and the event log now breaks it into time
    the speaker was still talking versus time spent confirming they'd stopped —
    which is what the **Sentence pause** slider actually moves.
  - **Translate** — first word back, with the whole reply alongside it.
  - **Voice 1st byte** — the TTS provider's own answer time, measured on the
    server so it can be told apart from time spent queued behind other clips
    (shown separately when it isn't zero, which means `CT_TTS_MAX_INFLIGHT` is
    too low for the speaker).
  - **Mouth → audio** — the total, from the speaker's last word to the first
    sound out, with **→ caption** alongside for the projector's half (the only
    one that still reports with **Speak** off).

  The stage boxes show averages only, and only once a stage has produced one: a
  figure that moves several times a second is something to watch in Debug, not
  something to read at a glance mid-service. Averages are windowed rather than
  cumulative so a change made at the desk shows up within a minute instead of
  being buried under the hour before it, and **Clear** resets them along with the
  captions — the two are one gesture, and an average carried across it would be
  describing a run nobody is looking at any more. **Copy** now includes the
  table, so a service can be reported as figures rather than impressions.
- **Soniox as a second text-to-speech backend, alongside ElevenLabs.** A new
  **Speech** dropdown switches between them live, mid-service; only backends
  with a key behind them are offered, so the control disappears entirely when
  there's just one. Soniox reuses the `SONIOX_API_KEY` already required for
  speech-to-text — if captions work, spoken output now works too, with nothing
  further to configure — and brings 70 built-in voices across 63 languages,
  covering every target language the app offers.
  Cost is the reason to care: Soniox bills **per generated hour** (~$0.70/hr)
  rather than per character, which works out roughly **4–8× cheaper** than
  ElevenLabs at normal speaking rates and stops a wordy translation from
  costing more than a terse one.
- **"Tighten pauses"** (Soniox `reduce_silence`) shortens the gaps between
  words — measured ~8–15% shorter audio, which also trims how far spoken output
  drifts behind the live speaker.
- **Soundcheck voice capture.** With Soniox selected, **Capture voice** records
  a 30-second reference take (stop early at any point) and turns it into a
  voice the translation is then spoken in — so a Romanian rendering can carry
  the speaker's own voice. Measured end to end at ~3 seconds from stopping the
  recording to the voice being usable, and cross-lingual: a voice captured
  speaking English goes on to speak Romanian, Spanish or Korean at the same
  latency as a built-in voice. **Test** speaks a line in the current target
  language before you commit to it; **Delete** removes a capture.

  Deliberately a pre-service step rather than something that happens
  automatically to whoever is talking:
  - A capture reproduces **pace, accent and breathing**, not just tone, so it
    wants one clean take of one speaker — a quiet room, no music, no
    congregation — which is exactly what a soundcheck is and a live service
    isn't.
  - It should be **the speaker's own decision**. This crosses from captioning
    what someone said to making their voice say words they didn't say, in a
    language they may not speak; if a translation goes wrong it now sounds like
    they personally said it. Worth agreeing beforehand, including whether the
    congregation is told.
  - Takes that are too short (<5s) or near-silent are refused **before** upload,
    since a bad capture still costs one of the 20 slots.
  - Soniox allows **20 captured voices per organisation** (not per project), so
    deletion is offered in-app rather than left to their console, and a capture
    that fails to become usable is cleaned up rather than left as dead weight.

### Changed
- The ElevenLabs model picker now lists each model's per-character cost, and
  marks `turbo` as deprecated in favour of `flash` (functionally equivalent,
  lower latency) per ElevenLabs' own guidance.
- The TTS stage chip names the backend actually speaking, instead of always
  claiming ElevenLabs.

### Notes
- Existing setups are unaffected: ElevenLabs remains the default when its key
  is present, and every ElevenLabs control behaves exactly as before. The
  stability/style knobs are hidden under Soniox, which has no equivalent —
  speed is the only control the two backends share.
- Unlike ElevenLabs, Soniox does not infer the language from the text, so the
  app now sends the active target language with each request. A replayed line
  carries the language it was *originally* translated into, so replay still
  speaks correctly after the target picker has moved on.

## [v2.2.0] — 2026-08-23

**The translation can now be read on any phone, anywhere — and a worship set
no longer costs a thing.**

### Added
- **Stream captions to your own website.** An opt-in relay puts the finalized
  translation on a public page people open on their phones from anywhere, not
  just the in-room projector. Tick the new **Web** toggle and each committed
  caption (and any hand-edit) is forwarded — through this server, so the secret
  token never reaches a browser and there is no CORS — to a small PHP endpoint
  on your own web host; **Clear** wipes the remote wall too. Text only, about a
  second behind the spoken word, and live only during a service. Best-effort:
  if the web host is unreachable the in-room captions and projector are
  unaffected. Shipped with drop-in files for a shared PHP host such as Bluehost,
  which cannot run WebSockets, so the viewer polls a tiny JSON feed (~1/second)
  instead — see `web/captions/`. Needs `CT_PUBLISH_URL` + `CT_PUBLISH_TOKEN`.
- **Music gate (YAMNet).** The room's audio is classified on-device, and when
  it hears music the whole pipeline holds — no speech-to-text, no translation,
  no voice, nothing projected — so a worship set costs nothing. The level-bar
  indicator is a manual override (click it, or press 1 / 2) and the detector
  waits five seconds before arguing back. YAMNet is vendored, so the
  classification needs no cloud call.

### Fixed
- `deploy.py` no longer aborts with a `UnicodeEncodeError` on a native Windows
  (cp1252) console — its progress output forced UTF-8, like the server does.

## [v2.1.0] — 2026-08-23

**A mistranslation can now be fixed on the wall in seconds, a third
translator (xAI Grok) joins the picker, and a silently-remapped model is
caught by its own latency.**

### Added
- **Click-to-edit translations.** Cloud MT gets a name, a number or a turn
  of phrase wrong now and then; rather than let it stand in front of the
  room, the operator clicks the settled translation line, corrects the text,
  and presses Enter (Esc cancels, click-away commits). The fix propagates
  everywhere the translation lives — the transcript row, the HTML export
  (both read the cell's text node) and the projector strip (re-cast under the
  same segment id, so it replaces the wrong text in place rather than
  appending a second copy) — and Replay is rebound to speak the corrected
  words instead of the stale clips. Only *finalized* target cells become
  editable, so an edit can never race the draft→final morph; the source
  (heard) column is never editable — it stays the faithful record of what was
  said. Latency / coalesce / language badges survive an edit, and an edited
  line carries a gold left-border.
- **xAI Grok as a third translator backend**, alongside Claude and GPT-5
  mini/nano. Routed purely by model name and sharing Claude's prompt and
  hardening; the picker is built from xAI's *live* catalogue (served on
  `/caps`) so it follows their month-to-month turnover instead of rotting.
  Needs `XAI_API_KEY`, or the option is hidden. Reasoning models are refused
  on the live path — their thinking lands entirely on time-to-first-word,
  which is one latency a caption cannot spend.
- **Model-pin drift detection.** Each pinned model records what it was when
  last validated plus a latency baseline; at startup the identities are
  re-checked, and at runtime a rolling median that runs well past the pinned
  baseline raises a one-time warning. A silent remap onto a slower model is
  invisible to a catalogue check but cannot hide from the median — so it is
  caught before a Sunday rather than during one. Advisory throughout: shown
  on `/caps`, never changing which model is used.
- **Glossary-dependence stress test** (`scripts/glossary_dependence.py`) for
  measuring how much a translation leans on the pinned glossary.

## [v2.0] — 2026-08-13

**Live draft: the translation is on the wall about a second after it is
spoken.** Soniox now translates while it transcribes, so the projector shows a
draft almost immediately and the LLM paves over it with the proper translation
as each sentence lands — the room reads continuously instead of waiting out the
gap between a sentence ending and its translation appearing.

Around that: **bilingual services** (tick two languages, and each sentence goes
into whichever one is *not* being spoken, direction following the speaker),
a caption pipeline where **duplication is structurally impossible** rather than
merely guarded against, **four translator providers** to choose between, and
**drift detection** on the pinned models so a silently-swapped model is caught
before a Sunday rather than during one.

### Changed
- **The three stage panels collapse to their own headers.** Input device,
  translator model, voice and knobs are *setup* — chosen before a service and
  untouched during one — but they occupied the top third of the operator's
  screen throughout. Collapsed, each box shrinks to a 33px sliver showing just
  its heading and its status light, so the row answers the only question that
  stays live (is each stage connected?) in a tenth of the height.

  The header **is** the control, rather than a separate ribbon above it: a
  ribbon repeating `1 · Speech-to-text / 2 · Translation / 3 · Text-to-speech`
  over boxes with those same three headings was the same words twice. Keeping
  the headers also keeps the thirds — the titles stay spread across the row on
  the boxes' own flex geometry instead of clustering at the left of a ribbon.

  One drawer, not three: any of the three headers toggles the whole row, and
  hovering one lights all three so that reads as intended rather than as a bug.
  Per-box collapse was rejected because `.stages` stretches its boxes to equal
  height, so one box open and two shut renders as two tall empty boxes.
  Open/closed persists in `ct_settings`, defaulting to closed.

### Removed
- **Fast dispatch is gone** — checkbox, setting, and the whole speculation path.
  It existed to cut latency by translating Soniox's tentative text before
  finalization; the live draft lane now covers that, and it was the only feature
  that required reconciling revisable text (and the source of the duplicate-
  caption bug below). Non-final tokens are now **display only**: they render as
  the grey tail on the live row, are thrown away and rebuilt on every packet, and
  never reach the translator. Nothing is carried across packets, so there is
  nothing to reconcile. Net −4.2 KB of `app/index.html`.

### Fixed
- **The translator no longer finishes the speaker's sentences.** Grok 4.2 was
  observed rendering `"Noi ne gândim la o perdea cum vedem noi la geamurile
  noastre,"` as *"We think of a curtain like we see on our windows, a little
  curtain there that stays, but the veil in the temple was a very thick and heavy
  curtain that separated the Holy Place from the Most Holy Place."* — an entire
  explanation the preacher never gave, put on the wall and read aloud as though
  he had. Segments arrive mid-thought, so the model is handed incomplete clauses
  constantly and the licence to "translate what the speaker MEANT" was being
  over-extended into supplying what he was *about* to say.

  `config/translation_prompt.txt` gains an **ADD NOTHING** paragraph: translate
  the fragment as a fragment and stop where it stops; never finish the sentence,
  continue the argument, or supply the explanation, definition, background, or
  Scripture reference that was coming — even when the passage is familiar and
  even when the result reads unfinished, because the next segment completes it
  and no one in the room can tell an invented clause from a spoken one.
  Verified on four completion-inviting fragments across Grok 4.2 and Haiku 4.5
  (all now stop at the source's end), and regression-checked that it causes no
  *under*-translation: complete sentences, idioms-by-meaning, TTS unit expansion,
  Scripture-reference expansion, and mis-hear recovery all still behave.

  Note this deliberately changes the system prompt for **all** sessions — it is
  no longer byte-identical to what the draft-anchoring, glossary-dependence, and
  effort-sweep baselines were measured against. Re-run those before comparing to
  older numbers. The prompt is now 2152 tokens one-way / 2265 bilingual (from
  1884 / 1997), still well under Haiku 4.5's 4096-token cache floor.
- **The stream assembler is now an append-only transcript plus a monotonic
  cursor, which makes duplication structurally impossible.** Root cause of the
  repeated captions, found by reading Soniox's token contract against the code:

  > "Final tokens are sent only once and never repeated." Non-final tokens are
  > resent in full each packet and "may change, disappear, or be replaced."

  Fast dispatch committed a sentence from the *tentative* text, then reconciled
  the finalized re-emission by **counting sentence terminals**. But the count is
  a property of text the protocol explicitly allows to be revised. When Soniox
  finalized the same audio with different punctuation — `…jertfit, …născut.`
  (one terminal) becoming `…jertfit. Care …născut.` (two) — the skip stopped in
  the wrong place and released already-dispatched text back into the buffer,
  where it was sent again as the head of the next unit. That is exactly the
  observed spill: one row's translation ending in the next row's opening words.

  The assembler now keeps one authoritative string (`sxFinal`, only appended to)
  and one cursor into it (`sxSent`, only increased). Dispatch is always "slice
  `[sxSent, cut)`, then set `sxSent = cut`", so a range cannot be emitted twice —
  the cursor has already moved past it. Speculative (tentative) dispatch is
  reconciled **by position**, rounding forward to a word boundary, so a revision
  drops a word rather than repeating one. `sxCut()` is the only code that decides
  a cut and `flushSonioxThought()` the only code that moves the cursor, so
  "dispatched exactly once" is checkable by reading two functions.

  This replaces `absorbSentences` / `skipAbsorb` / `trimSkippedForDisplay` and
  the two mutable buffers they shuffled text between. Verified against the exact
  failing revision, both revision directions, and 300 randomized runs across both
  dispatch paths with punctuation changing between guess and finalization: zero
  overlaps, zero dropped characters.
- **Duplicate captions are dropped rather than shown.** A service produced rows
  whose translations accumulated — each successive row restated the previous
  row's text and added to it — while the Original column stayed correctly split.
  Two guards now enforce the house rule that a gap beats a repeat:
  `alreadyDispatched()` refuses to queue source text byte-identical to something
  recently sent (which would cost a duplicated caption *and* duplicated speech),
  and `dropCommittedPrefix()` strips leading sentences from an arriving
  translation when they merely repeat a caption already on screen. Both reuse the
  staleness test the draft drain already relies on, and both refuse to remove the
  last sentence, so the genuinely new tail can never be lost.

  **The root cause is not identified.** The dispatcher was exercised against
  word-by-word finals, `<end>` boundaries, interleaved partials, the idle and
  hard-idle backstops, and Fast dispatch with punctuation revised between the
  tentative and final text; every path produced disjoint units. A polluted draft
  anchor and a populated rolling history were each tested against the live model
  and neither reproduced the restatement. So these are guards, not a fix, and
  they log loudly (`caption de-duplicated`, `unit dropped`) precisely so a
  recurrence arrives with evidence attached.

  **Known limit:** TTS audio is synthesized per sentence *during* the response
  stream, so a duplicated sentence may already have been queued for speech before
  the caption-level guard can see it. The screen and the projector are protected;
  the speaker is not. Closing that needs the de-duplication to move server-side,
  ahead of synthesis.

### Changed
- **One language control replaces the source and target menus.** A single picker
  in the top bar, and the NUMBER of languages ticked is the mode: tick one to
  translate anything spoken into that language (what the June redesign gave us,
  unchanged), tick two for a bilingual service. Ticking a third replaces the
  oldest, so a third click reads as "swap that one out" rather than as a click
  that did nothing. There is no source picker any more — Soniox identifies the
  source perfectly well on its own, so naming it was friction that bought
  nothing. A single tick deliberately sends NO hint: in one-way mode that
  language is the *target*, and hinting it would bias transcription toward the
  one language the speaker is least likely to be using.
- **Direction gets two controls over one piece of state.** `Swap` is an action
  (set the direction now); `Auto` is a mode (may the engine move it by itself?).
  They are not alternatives — pressing Swap while Auto is on is a *correction*,
  and Auto keeps tracking from the corrected position, so an operator fixing a
  bad flip mid-sermon has nothing to remember to switch back on. With Auto off,
  Swap is the only thing that ever moves the direction. Both are inert on a
  single language. This also fills a real gap: the flip rule's known cost (a
  standalone quoted verse flips for one unit) previously had no operator remedy.

  The picker's face follows the same split: it states the **configuration**, not
  the live direction. With Auto on, direction is observed state rather than
  something the operator set, so an arrow there would dress a picker up as a
  direction control — it reads "English + Romanian". With Auto off the direction
  *is* the configuration, so it reads "Romanian → English". The column headings
  stay bare ("Original" / "Translation") for the same reason: which language sits
  in which column is fixed, but which way the pair runs changes per speaker, and
  a heading claiming otherwise would contradict the rows beneath it. Per-line
  detected language is still shown on each line's own badge, which is the honest
  per-packet answer.

### Removed
- **Grok reasoning models are no longer offered**, and are refused server-side if
  a saved setting still names one. The reason is latency, not quality: reasoning
  happens *before* the first token, so the whole cost lands on the caption's
  time-to-first-word with the room watching a blank screen. They were previously
  listed with a "reasons — slower" label, but that is not a trade an operator
  would knowingly take mid-service, so the honest thing is to not offer it.
  Rejection is based on *positive* knowledge — ids the catalogue showed as
  reasoning, remembered rather than discarded, plus the name shape — so an xAI
  outage (empty catalogue) never turns into "your model choice is ignored".

### Added
- **Model pins (`config/model_pins.json`, `scripts/model_pins.py`).** A pinned
  model id fails in two ways and only one is loud: *hard retirement* (the id
  404s — self-announcing) and *silent remap* (the id still works, still returns
  200, still returns good translations, but is served by a different model).
  The second already happened here, which is why an existence check is not
  enough. So the pin records a *fingerprint* — the properties the app actually
  depends on — and startup reports drift in any of them, generalising
  `translate_xai.check_default_model()` to all three providers. Pins are
  deliberately aliases (`claude-haiku-4-5`, not the dated id), so each provider
  is asked about one id rather than handed a list to search: an alias never
  appears in a catalogue listing, and `served_as` drift is precisely the signal
  wanted. `--adopt` re-records from the live catalogues and refuses to adopt an
  absent model, since recording "absent" as the baseline would make the check
  pass forever on a model that isn't there. An unreachable provider reports
  `unknown`, never `drifted` — a check that cries wolf is one you learn to
  ignore.
- **Runtime latency alarm.** A rolling per-model median of translate latency,
  compared against that model's pinned baseline rather than a fixed threshold
  (Sonnet is legitimately slower than Haiku; one global number would either cry
  wolf or say nothing). This is the runtime half of the same idea: a silent
  remap onto a reasoning model is invisible to a catalogue check but cannot hide
  a doubled time-to-first-word from a median. Warns once per model, surfaces as
  a chip beside the model name, and — when no baseline is pinned — reports the
  observed median instead, so a normal service tells you what to pin.
- Bilingual mode: tick two languages in the picker described above. Each unit is
  translated into whichever of the pair is *not* currently being spoken, so a
  Romanian sermon captions into English and an English speaker taking over
  captions into Romanian, with no operator intervention. Both hints go to Soniox
  together, and the live draft lane switches to Soniox's own `two_way` mode — so
  a direction change never re-handshakes the socket, only a change of *mode* or
  of the pair does.

  **Direction is sticky, and moves on evidence rather than elapsed time.** A unit
  inherits the active direction unless it overturns it, which needs the unit to
  be ≥ `SONIOX_MIN_CHARS` (40) *and* ≥ 80% one language by token count — or, after
  a silence of ≥ 2.5s, purity alone, since a pause is what a handover to a new
  speaker actually looks like. That split is deliberate: an interjection is
  *embedded*, so it makes its unit mixed and arrives with no pause in front of it,
  while a handover is a whole unit after a gap. Both are readable immediately, so
  neither costs latency to detect — a time-based bar would have bought stability
  by leaving half the room with no translation for its whole duration, which is
  exactly the handover case. Wrong flips are made *cheap* rather than rare: the
  rule is symmetric, so the next qualifying unit flips straight back, and a
  standalone quoted verse in the other language costs one unit of column churn.
  A 2s guard after any flip stops a stray unit oscillating the direction.

  Consequences handled: language identification is now switched on whenever a
  pair is in play (the engine reads per-token `language`; a single pinned hint
  still leaves it off, exactly as before); the translation worker batches only
  units that share a target, since one call carries one target; history is pooled
  across both directions on the pair key so a handover doesn't halve the sermon's
  context, with each replayed turn tagged by *its own* target rather than the
  current one; the Grok prompt-cache `x-grok-conv-id` follows the same key, which
  it is documented to track; and a `two_way` draft that disagrees with the sticky
  direction is dropped rather than anchored, since the draft rule cannot absorb an
  anchor in the wrong language. One-way sessions are unaffected — the system
  prompt they send is byte-identical to before (verified against `HEAD`), which is
  what keeps the existing A/B measurements honest.
- Disfluencies are dropped from the translation (`config/translation_prompt.txt`,
  in the section that already handles speech-to-text artifacts). Soniox transcribes
  hesitation sounds faithfully and they were carried through into the caption,
  where they are an eyesore on screen and are also read aloud by TTS. Covers "uh",
  "um", "er", "erm", "mm", "ah" and the Romanian "ăă"/"îî"/"mmm" wherever they
  appear, plus the stammered restart of a word or phrase, of which one clean copy
  is kept ("they live—they live in the era" → "they live in the era"). Deliberate
  repetition for emphasis is content and stays — "Holy, holy, holy", "Truly, truly
  I say to you", a phrase repeated across a sentence for rhetorical effect — which
  matters in preaching, where the two look identical to a naive filter.
  Applies to every translation, anchored or not: the draft-anchoring rule says
  explicitly that the draft carries no authority over this, so a draft that keeps
  its fillers (Soniox does) does not drag them into the final. Verified on two
  disfluent pairs in both arms: fillers and the stammer gone in all four outputs.
  Not added to `_DEFAULT_PROMPT`, which is a reduced emergency fallback for a
  missing config file rather than a mirror of it.
- "Anchor to draft" checkbox (Translation stage, needs Live draft): the draft the
  room is already reading is sent to the model with the segment, asking it to keep
  that wording wherever keeping it costs no quality — so fewer words move when the
  draft resolves into the final. Every word the two translations agree on is a word
  that does not move, and stillness is what makes a caption readable.
  Measured A/B on five Romanian→English pairs, same model, `history=None` on both
  arms so the only variable is the prompt: words of the final that already exist in
  the draft rose 54% → 68%, and words already in the SAME SLOT — the number that
  actually decides screen motion — rose **24% → 54%**. One pair landed a perfect
  15/15 in-slot match, i.e. nothing moved at all; the line only cooled from grey to
  solid.
  The instruction states plainly that matching is COSMETIC and that translation
  quality always takes precedence — including a clause forbidding the draft from
  pulling the model away from the phrasing a well-known passage should have. That
  precedence is not free and is not meant to be: it costs a few points of alignment
  (an earlier, softer wording scored 72%/58% but rendered Titus 2:11 as "Because
  the grace of God…" against the unanchored arm's "For the grace of God…"). With
  the stronger wording that pair now returns byte-identical text on both arms.
  One residual: Matthew 6:21 still opens "Because where your treasure is…" when
  anchored, where the unanchored arm gives the more familiar "For where…". Meaning
  is unaffected and no output was wrong in either arm.
  Anchoring also *prevented* a hallucination — asked for a shortened Philippians
  4:6 the unanchored arm padded the verse back out from memory ("by prayer and
  petition, with thanksgiving", none of it in the source) while the anchored arm
  stayed faithful to what was actually said.
  The instruction rides in the USER turn beside the draft rather than in the system
  prompt: it cannot then exist without the draft it refers to, the cached system
  prefix is untouched, and with the box off the request body is byte-identical to
  what it was before the feature existed — which is what makes the A/B honest
  (verified across all four checkbox combinations: `draft` is absent from the body
  unless BOTH boxes are on). It costs ~90 tokens on an anchored call and nothing
  otherwise. Shared by all three translator backends through one `anchored_turn()`
  helper in `translate_claude`, so the prompt cannot drift between providers.
  The draft handed over is a TRUNCATED PREFIX as often as not — Soniox's
  translation lags its own transcription, so at dispatch the tail clause may not be
  translated at all — so the instruction explicitly releases it: whatever the draft
  does not reach carries no constraint, and where it stops is not the end of the
  segment. Both truncated test pairs translated their unreached tails normally.
  It is captured in `queueUnit()`, not `markDraftRow()`: markDraftRow runs after
  queueUnit and freezes the span, and queueUnit calls pumpTranslate() synchronously,
  so the request can be in flight before the next statement runs. A coalesced
  batch joins its units' drafts the same way it joins their sources.
  The anchor is POLICED before it is sent, and refused rather than repaired. The
  draft lane can legitimately still hold the PREVIOUS unit's translation when the
  next unit is dispatched: Soniox lags its own transcription, so when a
  zero-terminal unit (one cut at a comma — what an under-punctuating speaker
  produces all service) is credited, the fragment path force-drops the buffer by
  the LLM's volume while Soniox has not finished emitting that unit's translation,
  and the trailing part arrives into an empty buffer as the NEW row's share. For
  the grey column that trade is benign, as the changelog says — the morph wipes it
  a second later and nothing is kept. As an anchor it is not: the model is told to
  reuse that wording, so it reproduces the previous row's sentence verbatim at the
  head of this one, and unlike the grey artifact that lands in the transcript, the
  HTML export and the spoken audio. Observed live within minutes of enabling it.
  Two guards, both logged as warnings so a drop is visible in Diagnostics rather
  than silent: the draft is refused if its first four words are an in-order
  subsequence of the last committed translation (normalised word keys, so it
  survives Soniox's dash and punctuation noise), and refused if it is more than
  twice the length of the source unit (a fail-safe salvage, or a drain that never
  happened). Verified against the observed spill: caught, while the clean draft for
  that same row still anchors normally. Known residual: a leftover of one to three
  words is shorter than the probe and slips through — a far smaller spill than the
  whole-sentence case, and the model generally absorbs it.
  A missing anchor costs a little screen motion; a spilled one costs duplicated
  sermon text in three places that keep it. Same principle as the draft column's
  own bounds — delete rather than spill.
- "Live draft" checkbox (Translation stage): Soniox translates while it
  transcribes (`translation: {type: one_way}` on the realtime socket), and the
  projector shows that draft within about a second of the words being spoken.
  The LLM still translates each assembled unit properly and **paves over** the
  draft as each unit lands. The main window shows the same draft as grey text in
  the Translation column of the live row — the mirror of the grey provisional
  tokens on the left — and the projector shows it on the strip. It renders as an
  element, not text, so `cellText()` keeps it out of the HTML export; the draft
  is never spoken and never becomes transcript content.
  The two lanes carry no shared ids, so they are reconciled with a
  sentence-count ratchet (the same shape as the Fast-dispatch skip logic): when
  the LLM commits a unit of N sentences, N sentences retire off the front of the
  draft. If the draft holds fewer than N it retires whole; if it drifts long a
  cap trims it; if the LLM fails outright the draft is *kept*, so the room sees
  a translation instead of the blank a failed batch leaves today. While the
  draft lane is on, streamed LLM deltas are no longer cast to the projector —
  the final lands as one clean overwrite, so no line is ever printed twice.
  Off by default; toggling it (or the target language) mid-session
  re-handshakes the Soniox socket without disturbing the mic.
  The draft is not painted as it arrives — Soniox emits it in bursts, which read
  as popping. A typesetter reveals it left to right one character at a time on a
  `requestAnimationFrame` loop, at a rate proportional to the backlog so a fast
  speaker is typed out faster rather than queuing an ever-growing delay behind
  the audio (measured: ~0.4s behind at ordinary speech, ~0.5s at fast speech, and
  a 43-character sentence lands in ~0.85s). A frame gap over 500ms means the tab
  was suspended, so the backlog snaps in rather than typing out stale text. The
  projector strip receives whole words only — it animates per word and would
  restart its fade on every character — while the main window gets the character
  glide.
  When the LLM's translation lands the draft is wiped entirely rather than
  retired sentence by sentence: the lanes don't stay aligned in practice (Soniox
  merges and splits sentences differently, and its not-yet-final tokens belong to
  no sentence at all), so scraps kept dangling into the next row. Draft text is
  now attributed to rows by COUNTING sentences rather than by cutting the buffer
  at a position: `xlOwed` tracks units that have been dispatched but whose
  translation the draft still owes to rows that are no longer live, and the main
  window renders only what follows that many complete sentences — rendering
  nothing while fewer exist. So a new row starts blank in both cells, the source
  populates first, and the translation follows once it is actually that row's.
  A positional mark taken at dispatch does *not* work and was tried first:
  Soniox's translation lags its own transcription, so at the instant a unit is
  dispatched its translation may not have been emitted at all, arrives after the
  mark, and renders in the next row — the exact staleness being fixed.
  `xlDue` records units the LLM has since translated; their draft sentence is
  dropped opportunistically, so if Soniox has not caught up the debt stays
  outstanding and is paid the moment the sentence completes. A length fail-safe
  stops an unpunctuated run from blanking the column indefinitely, and logs a
  warning when it fires so drift is visible rather than silent.
  Both counters are measured in sentence TERMINALS, not units, which is the
  correctness argument: the draft can only pay its debt in sentence ends, so a
  debt booked per unit is one the draft may be unable to settle. A unit cut at a
  comma (`SONIOX_COMMA_CHARS`, common with an under-punctuating speaker) or an
  unpunctuated tail from a hard flush contains no terminal and neither will its
  translation — booked as one unit it left the live row waiting for a sentence
  end that was never coming, so the draft column simply went blank until the
  fail-safe gave up. Such a unit books zero debt and is settled by TIME instead,
  using the only self-timing signal available — the LLM's own answer: the live row
  renders nothing while any fragment is outstanding (`xlHold`), and when the LLM
  returns, whatever the draft has accumulated by then is dropped wholesale as that
  fragment's translation. Dropping rather than merely hiding it also keeps the
  buffer bounded, since draining is driven by sentence ends and a fragment stream
  never produces any. Measured over 150-unit soaks in four regimes (all
  punctuated, all comma-cut, mixed, two-sentence units): no stale text and the
  buffer fully drains in every one. Two leaks closed alongside: a
  batch the translator gives up on is forgiven (it was never credited, so its
  debt blocked the column permanently), and a credit is clamped to the
  outstanding debt so an in-flight batch returning after a reset cannot drain the
  live row's own sentences. The retained buffer is also hard-bounded — the drain
  only removes what the LLM credits, so a Soniox that splits sentences more
  finely than the assembler dispatches them accumulated a surplus (~11 characters
  per unit at a 2:1 split) that would eventually trail long-committed text on the
  projector.

- The LLM's translation no longer replaces the draft, it RESOLVES into it. The
  row being sealed now keeps the draft it was showing (frozen in place, still
  grey) instead of the span moving on to the next row and leaving the cell empty
  until the LLM answered — that empty second was the flash. When the translation
  arrives the two are aligned word by word (normalised keys, prefix/suffix + LCS):
  agreed words stay in their exact pixels and merely cool from grey to solid,
  moved words glide to their new slot, superseded words dissolve where they
  stand, and genuinely new words fade in last, each phase staggered left to right
  so the line resolves in reading order (measured on a real pair: 7 of 11 words
  held their pixels). It is drawn as an absolutely-positioned overlay measured
  with a `Range` over the real text nodes and thrown away when it finishes, so
  the cell's settled state stays the plain text node it always was — resting
  layout, wrapping and spacing are untouched, unlike an earlier attempt that
  rendered captions as word spans permanently and read as jumpier than what it
  replaced. Teardown is on a timer rather than an animation event, so a window
  backgrounded mid-morph can't be returned to with the overlay still up. Skipped
  entirely under `prefers-reduced-motion` or while hidden.

### Fixed
- Already-committed sentences went on stacking up in front of the live row's own
  draft text — observed with two of them, 218 characters of grey, before the words
  that actually belonged to that row. Counting alone cannot retire them: a debt
  that EXPIRES (Soniox lagging past `XL_DUE_TTL`) forgives its count *before*
  Soniox has emitted the text that debt existed to skip, so that text arrives into
  a zero-debt buffer and renders as the live row's own. Dropping text at expiry
  cannot help — it is not there yet.
  So the draft is now also retired by CONTENT: a complete leading sentence whose
  words the last three commits already cover has been superseded — the LLM has said
  it, in better words, in a row above — and is binned. Word-overlap (70%) rather
  than equality, because the two lanes never word things identically; a paraphrase
  of a commit is retired where an exact-prefix test would miss it.
  What makes an aggressive drain safe here is that only COMPLETE leading sentences
  are ever considered. The live row's in-progress text is the trailing incomplete
  fragment, so this can never eat it, and a too-eager drop costs a moment of
  shorter grey draft while the LLM's translation — the authoritative one — is
  untouched either way. Verified on the observed buffer (218c → 55c, leaving
  exactly the row's own words) and against six cases that must NOT drop: an
  undispatched completed sentence of new content, a trailing fragment, no commits
  recorded yet, a sub-5-word sentence, and text sharing vocabulary with a commit
  but saying something new.
  The commit memory is shared with the anchor policing added above, so there is one
  record of what has already been said (`recentCommits`, replacing the single
  `lastCommittedTgt`); anchor behaviour is unchanged, re-verified.
- The draft→final morph never actually ran on a whole sentence, so the final
  appeared to flash over the draft and the row had to be read twice. `setTgtLine`
  was called on every streamed delta, and `morphCellTo` drops the frozen draft on
  its first run — deltas are raw token chunks, so the morph fired against a
  ~3-character first chunk and every later delta was a bare `textContent` write.
  Measured over a stubbed token stream: the cell collapsed to 3 characters ("And")
  and retyped the sentence from nothing, in ~17 repaints. The main window now
  follows the rule the projector cast already did — while a frozen draft stands in
  the cell, deltas are not painted, and the translation lands once as a single
  word-aligned morph out of the draft (verified: 2 render states, not 17). The
  guard is the frozen span itself rather than the checkbox, so a row with nothing
  to preserve — draft lane off, or a coalesced batch's later rows — streams as
  ever (verified unchanged: 13 progressive repaints).
- A puddle of stale draft text built up in the live row's translation cell after a
  few minutes, overflowing it and painting over the rows below. `capDraftBuffer`
  bounds the RETAINED buffer at `XL_BUFFER_MAX` (600c), but with no debt
  outstanding `rowDraftFrom()` returns 0 — so the row rendered the whole retained
  buffer, including the ordinary surplus from Soniox splitting sentences more
  finely than the assembler dispatches them (~11 characters a unit). Measured: 600
  characters in a 327px-tall cell, against 99px after the fix. Retaining text is
  cheap and showing it is not, so the two limits are now separate: `XL_ROW_MAX`
  (220c) bounds what the live row may DISPLAY, always the tail, cut at a sentence
  boundary where one falls inside the limit and at a word boundary otherwise. That
  also covers a long unpunctuated run, which yields no terminal to drain at and
  would fill the cell on its own. A welcome side effect is that the frozen draft is
  bounded too, so the morph never aligns a 600-character overlay.
  Both fail-safes in `rowDraftFrom()` used to answer drift by RELEASING the buffer
  (`xlOwed=0; return 0`), which put everything retained on screen at once — and the
  front of the buffer is exactly the stale part, the text the unmet debt was
  waiting to skip past, most of it already committed to rows above. They now
  discard the front and keep only the tail (verified: buffer permanently trimmed
  520c → 176c, every counter reset, `xlShown` clamped). Deleting draft text is
  always the safe direction: it is provisional, never spoken, and never reaches the
  transcript or the HTML export.
  `XL_BUFFER_MAX` deliberately stays well above `XL_OWED_MAX` and is now commented
  as such — capping the buffer at or below it would make both fail-safes
  unreachable and a stuck debt would blank the column for ever. That is why the
  clamp is on display rather than on the buffer.
- The morph overlay is clipped to its own cell (`overflow:hidden`). Its word spans
  are placed from a measurement of the PREVIOUS text, so a tall draft resolving
  into a short final left absolutely-positioned words painting over the row below.
  Clipped, a mis-measured overlay costs a word, never a spill.
- Two draft-lane stalls, both of which looked like Soniox dying: the live column
  went blank, returned briefly, then stalled again.
  - Debts are booked per unit at dispatch but were settled against the batch's
    JOINED source. A coalesced batch holding a fragment *and* a sentence has a
    terminal in its joined text, so it took the sentence path only and the
    fragment's hold was never released — the column then stayed blank until the
    length fail-safe gave up, and re-stuck on the next coalesced batch. Settling
    is now per unit.
  - A debt the draft can never meet (it yields fewer sentence ends than the source
    booked — Soniox merging two spoken sentences into one is enough) went on
    skipping text belonging to LATER rows. The drain is now bounded by the volume
    the LLM actually returned: the first sentence a debt settles always goes
    through however long it runs, but once a debt has settled something, anything
    beyond the committed volume is a later row's and the debt is written off on
    the spot. A 2.5s TTL backstops the case where no sentence arrives at all.
    Verified across five 60-unit regimes (punctuated, comma-cut, Soniox merging,
    Soniox splitting, mixed) plus coalesced mixed batches: no blank rows and no
    stale rows in any of them, with lag protection still intact.
- Committing a translation drained the draft buffer but never repainted, so the
  projector kept showing text it had just superseded until the next Soniox token
  arrived — which at a pause is exactly when no token comes. Visible as the strip
  briefly holding a committed sentence twice. The projector
  still receives the whole continuous stream, so its draft-to-committed hand-off
  is untouched, and paying a debt never disturbs the live row's text (no blink).
  The single draft span is also swept whenever it is built or relocated, so a
  reference orphaned by code mutating those cells cannot strand old text.
- Transcript rows reserve two lines each and grow in rather than appearing. The
  floor is unconditional — a live row's height used to track its text as the
  draft typed out (1 line → 2 → back to 1 when a shorter final landed), and since
  rows are prepended every resize shoved the whole transcript. Rows can still grow
  past two lines but never shrink. New rows animate their top margin from
  −height to 0 over 220ms, so the rows below are pushed down smoothly instead of
  being displaced in a single frame (margin, not height: `min-height` overrides an
  animated height, which would pin the row at the floor for half the animation).
  Skipped while the window is hidden, and cancelled on `visibilitychange`, since
  a frameless window would otherwise hold a row collapsed at its first keyframe.
- "Fast dispatch" checkbox (Speech-to-text stage): sends a completed sentence to
  the translator as soon as it appears in Soniox's tentative (not-yet-final)
  text, instead of waiting for finalization — lower STT→translation latency.
  Same dispatch rules as before (sentence delimited by terminal punctuation,
  same minimum size); the finalized re-emission of an already-dispatched
  sentence is swallowed automatically so nothing is translated twice, with a
  fail-open safety valve that keeps text rather than ever dropping it.
- OpenAI GPT-5 mini and GPT-5.4 nano as alternative translator models
  (`scripts/translate_openai.py`), selectable in the Model dropdown when
  `OPENAI_API_KEY` is set in `.env`. Same system prompt, glossary, rolling
  history, and caption-safety hardening as the Claude path — only the provider
  changes; reasoning effort pinned to each model's lowest for latency
  (gpt-5-mini → `minimal`; gpt-5.4-nano → `none`, which rejects `minimal`).
- GPT-5 configs (`gpt:mini` / `gpt:nano`) in all four test batteries
  (`effort_sweep.py`, `homophone_stress.py`, `figurative_stress.py`,
  `context_stress.py`) via a shared `mt_router.py`, plus `openai_diag.py` —
  a one-command live check of connectivity, prompt caching (visible
  `cached_tokens` per call), HTTP connection persistence, and a 6-case idiom
  smoke test. The OpenAI path now reports token usage (incl. cache hits) on
  every call and drains streamed responses so the shared session's keep-alive
  connection is actually reused.

### Fixed
- Claude translator paid a fresh TCP+TLS handshake for nearly every caption:
  the Anthropic SDK's default connection pool expires idle connections after
  5 s, but caption segments typically arrive 5–20 s apart. The shared client
  now uses a 2-minute keepalive, and both translator backends pre-open their
  TLS connection at server startup (`warm()`) so the first caption of a
  service doesn't pay client construction + handshake either.


## [v1.5.0] — 2026-07-03

### Changed
- Version bump from v1.1.0 to v1.1.0
- Built standalone Windows executable


## [v1.1.0] — 2026-07-03

### Changed
- Version bump from v1.1.1 to v1.1.0
- Built standalone Windows executable


## [v1.1.1] — 2026-07-03

### Changed
- Version bump from v1.1.0 to v1.1.1
- Built standalone Windows executable


## [v1.1.2] — 2026-07-03

### Changed
- Version bump from v1.1.1 to v1.1.2
- Built standalone Windows executable


## [v1.1.1] — 2026-07-03

### Changed
- Version bump from v1.1.0 to v1.1.1
- Built standalone Windows executable


All notable changes to Church Translate.

## [1.1.0] - Initial Release

### Added
- Real-time speech translation for church services
- Soniox multilingual speech-to-text (60+ languages)
- Claude Haiku 4.5 / Sonnet 5 translation
- Optional ElevenLabs text-to-speech
- In-browser Silero VAD for voice activity detection
- WASAPI audio device support (any Windows microphone, line-in, loopback)
- Live caption UI with language detection
- System prompt aware of Church/Scripture context
- Adaptive thinking for translation ambiguity resolution
- Idle auto-stop to prevent credit drain
- Dark/light theme toggle
- Large-caption projection mode
- Credit meter and usage tracking

### Technical
- Cloud-only pipeline (no local models)
- Flask server + Anthropic/Soniox/ElevenLabs APIs
- Built with Python 3.12+
- Deployable as single Windows .exe (PyInstaller)
