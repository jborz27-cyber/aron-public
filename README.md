# Áron — live any-language captions

> "He shall speak for you to the people, and he shall be your mouth,"
> Exodus 4:16

Real-time speech translation for a church service. Speech is captured,
transcribed, translated, and shown as **live captions**. The operator picks
**one target language** — any of ~29 supported languages — and Soniox
auto-detects whatever language is actually being spoken, so any speaker in any
supported language gets captioned into that single chosen target, with
optional spoken output.

Everything runs in the **cloud** — there are no local models to install and no
special hardware. The app ships as a **single Windows `.exe`** that needs no
Python; you bring a couple of API keys.

```
speech ─► VAD chunk / Soniox stream ─► [STT] ──► text ─► [translate] ─► captions
         (browser)                     Soniox         Claude              (chosen target)
                                        (cloud, any                        (+ TTS: ElevenLabs
                                         source language)                   or Soniox)
```

Source language is auto-detected per segment (or pinned as a hint, for a bit
more accuracy when only one language will be spoken); the target is fixed for
the whole session via the **Translate into** picker. The two caption panels
have fixed roles — left is always **Original** (whatever's heard, in whatever
language), right is always **Translation** (in the chosen target) — so a
listener just watches the Translation column regardless of who's speaking or
in what language.

> **This is the releases repo.** It carries the built Windows app and its
> documentation. Each version is a [GitHub Release](../../releases) with
> `church-translate.exe` and `.env.example` attached.

## Download and run (Windows)

1. Go to **[Releases](../../releases)** and download, from the latest release:
   - `church-translate.exe`
   - `.env.example`
2. Put both in the same folder. Copy `.env.example` to **`.env`** and paste in
   your keys (see below). A `.env` with just one line — your Soniox key — is
   enough to start.
3. **Double-click `church-translate.exe`.** A console window opens (the server
   log), your browser opens to `http://localhost:5000`, pick a target language
   and click **Start**.
4. **Stop:** close the console window.

No installation, no Python, no separate downloads — the runtime and all
dependencies are inside the exe (~80–120 MB). Windows 10/11 64-bit.

First-time setup and operator tuning: **[QUICKSTART.md](QUICKSTART.md)**.

### Keys (in `.env`)

You need a **Soniox** key, which alone covers speech-to-text, translation
(Soniox's own, the **none, fast** model) and spoken output. Add an LLM key
(Anthropic, OpenAI, Google or xAI) for better translation, and an OpenAI key
for the Router. Every key but Soniox is optional.

```
SONIOX_API_KEY=...        # required — STT, its own translation, and voice   (console.soniox.com)
ANTHROPIC_API_KEY=...     # optional — Claude translators                    (console.anthropic.com)
OPENAI_API_KEY=...        # optional — GPT translators, the Router, an ear   (platform.openai.com)
GEMINI_API_KEY=...        # optional — Gemini translators, the Gemini ear
XAI_API_KEY=...           # optional — Grok translators                      (console.x.ai)
ELEVENLABS_API_KEY=...    # optional — ElevenLabs spoken output              (elevenlabs.io)
```

Every option is documented in **[`.env.example`](.env.example)**.

## Models in the pipeline

| Stage | Service | Notes |
|---|---|---|
| Speech-to-text | **Soniox** (realtime) | Multilingual — auto-detects any of 60+ languages, or pin a source-language hint for slightly better accuracy. Streams continuously and does its own phrase endpointing (the low-latency default); falls back automatically to per-segment (Silero VAD) POSTs if the streaming socket fails. Returns punctuation. Needs `SONIOX_API_KEY`. |
| Translation | **`claude-haiku-4-5`** (default) / **`claude-sonnet-5-5`**, and optional **OpenAI**, **xAI**, **Google Gemini** models — every model is named in the app exactly as its API spells it | Selectable per request from the UI's **Model** dropdown: Haiku for speed, Sonnet 5.5 for stronger contextual inference (homophones, word-boundary mistakes, proper-name recovery) at higher latency/cost. Three more backends join behind the **same system prompt and caption-safety hardening**, each shown only when its key is set: **OpenAI** `gpt-6-luna` (`OPENAI_API_KEY`), **xAI** `grok-4.20-non-reasoning` and `grok-4.3` (`XAI_API_KEY`), and **Google Gemini** `gemini-3.5-flash-lite` (default) or `gemini-3.8-flash` (`GEMINI_API_KEY`). One Church/Scripture-aware system prompt works for any target language; each segment carries a `→Language:` tag (e.g. `→Spanish:`) and a rolling window of recent segments — kept per target language — as context. Claude needs `ANTHROPIC_API_KEY` (billed per-token, separate from a Claude.ai Pro subscription); every LLM key is optional — with none, the app captions with **none, fast**. One picker entry calls no model at all: **`none, fast`** makes Soniox's own live translation the caption. |
| Text-to-speech | **ElevenLabs** or **Soniox** | Optional ("Speak" toggle), and interchangeable — the **Speech** dropdown switches backend live, and only backends with a key behind them are offered. Both return 24 kHz PCM directly and speak whichever target language is active.<br>**ElevenLabs** — one multilingual voice (`CT_EL_VOICE`) speaks whatever language the text is in. Bills **per character** ($0.04–0.08/1k). Knobs: stability / style / speed, plus the EL model (v4 Turbo by default; v2 / flash / turbo / v3 / v4 also offered). Supports **soundcheck voice capture**, and is the only backend that can remix the speaker's native accent out of the clone — see below.<br>**Soniox** — reuses `SONIOX_API_KEY`, so if STT works, spoken output needs nothing further; 70 built-in voices, 63 languages. Bills **per generated hour** (~$0.70/hr, roughly 4–8× cheaper at normal speaking rates). Also supports **soundcheck voice capture**, though its clones keep the speaker's accent — see below. |

## Design principles

**Translation quality outranks latency.** This is a *captioning* system, not a
chat box — a faithful, whole-sentence translation a beat late beats a fast but
fractured one. Wherever the two trade off, the pipeline waits for the better
result, and the latency cost is made explicit (a knob, a default) rather than
silently spent:

- The **sentence assembler** decides where Soniox's running transcript is cut
  into packets for the translator. It prefers a whole sentence; failing that, a
  clause mark the speaker actually paused after; and a run-on that Soniox never
  punctuates is cut anyway at a word count. The **Pace** bar is the operator's
  lever on this trade, and it *is* the parser: a small classifier scores every
  word gap, and each Pace stop is the word range it cuts within — **Pronto**
  (~3–7 words a piece), **Moderato** (7–10, the default), **Lento** (10–14),
  plus a far-left **Soniox** stop that cuts on Soniox's punctuation alone. The
  translator is told packets arrive mid-thought and stitches them.
- Captions always keep the **full** translation; only the optional spoken-audio
  channel ever speeds up or skips ahead, and only when it falls behind the speaker.

**Stages only talk forwards.** STT hands sentences to the translator; the
translator hands text to the caption, the projector and TTS; TTS hands audio to
the player. No stage reads a later stage's state to decide what to do.

When adding a feature, prefer the option that yields the better caption.

**Soniox's own translation (no model).** The top entry of the Model picker,
**none, fast**, skips the LLM and captions Soniox's live translation directly —
final text one segment (~6 words) at a time, the moment it lands (median ~1.3 s
spoken-to-shown). The trade is quality: on a battery of Romanian figurative
speech Soniox got 2 of 21 idioms right against Haiku's 16; on plain preaching it
is close. With **Speak** on, **none, fast** captions are spoken too.

## Catching mis-hears — the Router and extra ears (optional)

Two accuracy features sit around the translator, both off the hot path, both
aimed at the same failure: Soniox mishearing a word in a way that changes the
meaning. Neither slows the normal pipeline — it never waits on them.

**The Router.** With it on, one translator does the captioning, and a fast check
reads each finished segment and only *sends on* the doubtful ones — a likely
mis-hearing, a shaky translation, or a cuss word — for a closer look, keeping
Soniox's own reading for the rest. Each caption carries a small **router chip**
and a who-translated label. The offensive-words check is narrowed to actual
swearing — Bible and preaching language passes. The whole check runs under one
deadline (`CT_ROUTER_TIMEOUT_MS`, default 1200 ms); past it, Soniox's text stands.

**Extra ears.** Alongside Soniox, up to three more realtime recognizers can hear
the same audio — ElevenLabs, OpenAI, and Gemini Live — not to caption, but as
*witnesses*. When the Router sends a segment on as a likely mis-hearing, each
ear's reading of those same words goes to the translator as a clue to what was
actually said. On a whole 82-minute sermon with 27 meaning-changing mis-hears,
the translator got 20 right with no ears and 26 with all three. `CT_EARS=0`
hides and disables the lot.

## What it costs

The box in the top-right corner is this server run's estimated spend across
every API, at list prices (hover for the breakdown). Rough hourly costs: Soniox
speech-to-text $0.12, Soniox voice $0.70, a cached fast caption ~$0.002, ears
$0.39–1.02 each. Prompt caching is on for every translator.

## Speaking in the speaker's own voice (optional)

**Capture voice** records a 30-second reference take and speaks the translation
in that voice — including in languages the speaker doesn't know. A voice
captured in English will speak Romanian, Spanish or Korean at the same latency
as a built-in voice. Available on either speech backend, but they do not offer
the same thing:

| | Soniox | ElevenLabs |
|---|---|---|
| Clone | The speaker exactly as captured | The speaker exactly as captured |
| Accent | Carried into every language, always | Optional **Neutral accent** pass remixes it towards native pronunciation in the target language |
| Ready in | ~3 seconds | ~4 seconds, or ~40 seconds with the accent pass |
| Ceiling | 20 captured voices per **organisation** | By ElevenLabs plan |

Do it at **soundcheck**, not mid-service, and **ask the speaker first** — it
makes their voice say words they did not say, in a language they may not speak,
and a mistranslation will sound like they personally said it. Their call, not
the operator's.

## Keeping the service (audio + timed transcript)

Every clip the app speaks is kept as it is generated, so the whole service can
be saved off afterwards. **⭳ Audio** (or `A`) downloads one `.zip` with the
spoken translation as one `audio.mp3`, `captions.srt`/`.vtt` timed to it, a
`segments.json` map of every clip, and a side-by-side `transcript.html`. The
clips pile up in the **local server**, not the browser tab, so a reload never
costs you the recording, and none of it leaves the machine. Controls and the
`CT_RECORD*` settings are in the **3 · Text-to-speech** panel and `.env.example`.

## On the web — captions, live voice, and an archive (optional)

The service doesn't have to stay in the room. Tick **Web** in the top bar and
each finalized translation is streamed to a page on **your own web host**, so
people read it on their phones from anywhere — a QR code in the bulletin is
enough. It's a server-to-server relay: the operator app POSTs each caption to
your host, phones poll it about once a second. The publish token stays on the
server; the operator app never goes on the public internet, and your host only
ever sees finalized caption text. With TTS on, the same stream can carry the
*spoken* translation (a **Listen** button on the phone page), and **Clear**
rolls each service into a dated archive.

The PHP relay you deploy to your host ships as **`web-captions.zip`**, attached
to each [release](../../releases) — unzip it on your PHP host and follow the
`README.md` inside. Nothing here is on by default: with the **Web** toggle off,
the app behaves exactly as before.

## Projecting to ProPresenter (optional)

If ProPresenter drives your auditorium screen, point a ProPresenter **web
object** at Áron's own caption strip at `http://<operator-ip>:5000/projector` —
it renders the full typewriter reveal, the rolling window, and dimmed older
sentences. Size and place it for a lower-third with the `CT_PROJECTOR_*` knobs
in `.env.example`. If your ProPresenter can't host a web object, Áron can
instead push the live translation into a ProPresenter **Message** over its local
network API (`CT_PROPRESENTER_*`). Both are documented in `.env.example`.

## Config (editable, no code)

Alongside the exe the app reads two files it bundles with sensible defaults; drop
your own copies beside the exe to override:

- `translation_prompt.txt` — the translator's system prompt. Reasons from the
  model's own knowledge first (standard biblical names, common idioms,
  homophone/word-boundary recovery).
- `glossary.tsv` — **supplementary** names and idioms, in two `## NAMES` /
  `## IDIOMS` sections. The models already know standard biblical names and
  common idioms; add entries only for your congregation's own rare/local names
  or regional phrasing.

## Cloud design notes

**Credits — how we avoid draining them.** STT bills per minute of audio, TTS per
character. The VAD is the credit firewall: only detected *speech* segments (or
Soniox's own endpointing) get billed, so silence costs nothing. **Idle stop**
auto-stops after N minutes of no speech. **TTS only when Speak is on**, cached
per line. Retries are capped on the paid stages so a flaky network can't loop on
billed calls.

**Caveats before the church relies on it.** Cost is per-character (TTS) and
per-minute (STT) — continuous Sunday services add up, so price it against
expected hours before committing. Fully cloud means a network blip kills
captions mid-sermon; there is no offline fallback. It sends sermon audio to
third parties (Soniox / ElevenLabs / Anthropic) — likely fine for a church, but
a conscious decision.

## Licence

Áron is [MIT licensed](LICENSE) — use it, adapt it, run it in your own
congregation. If it helps your church, that's the point.

The app bundles third-party voice-activity-detection and model files (ONNX
Runtime Web, `@ricky0123/vad-web`, Silero VAD, YAMNet — all MIT / Apache-2.0),
which keep their own notices: see [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
The cloud services the app calls are governed by their own terms, and you bring
your own API keys, so usage of those is billed to you.
