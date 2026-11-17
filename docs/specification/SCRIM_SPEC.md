# Scrim — Interactive Recorded Coding Lesson (Full Functional Specification)

## 1. Definisi dan lokasi produk

**Scrim** adalah jenis **lesson di dalam Course**, bukan pemutar video YouTube, bukan OCR frame video, bukan tombol pause yang menampilkan snippet. Pemilik platform ingin pengalaman serupa konsep interactive screencast: pengajar merekam perubahan kode dan suara sehingga pengguna melihat aktivitas editor sebagai rekaman yang dapat dihentikan; ketika berhenti pengguna dapat mengubah kode aktual, menyalin, menjalankan, menyimpannya; ketika play kembali, rekaman kembali ke versi asli pengajar.

Lokasi UI learner: `Course > Module > Lesson(type=scrim)`. Lokasi UI authoring: `Admin/Instructor > Course Editor > Module > Lesson > Scrim Studio`. **DILARANG** menambahkan Scrim/Scrim Studio sebagai menu publik sendiri.

## 2. Bentuk rekaman

Scrim record menyimpan:
- proyek awal: file tree, file contents, language/run template, editor theme, viewport;
- audio narasi pengajar (opsional transcript/caption);
- timeline editor: inserts, deletes, replacements, cursor, selection, active file, editor scroll, optional show/hide diagnostics, optional terminal output yang sudah dibersihkan;
- timestamp monotonic (ms sejak start), checkpoints/snapshots berkala; recording version/schema_version, metadata duration/content hash;
- pilihan event voice pause/resume & markers/chapters; **tidak harus menyimpan video MP4**. Bila ada screen/video tambahan, itu media sekunder bukan representasi utama kode.

Prefer serializable deterministic operations, bukan mengandalkan frame/perubahan DOM visual. Monaco `onDidChangeModelContent`, cursor and selection events dan active-file changes dapat diterjemahkan menjadi event record tersendiri, disinkronkan via performance clock. Dokumentasikan mapping & posisi editor agar unicode/surrogate pairs tidak merusak replay.

**Contoh bentuk event stream (ilustratif):**

```json
{
  "scrim_id": "sample-id",
  "schema_version": 1,
  "initial_files": [{"path":"/src/main.js","language":"javascript","content":""}],
  "duration_ms": 54000,
  "events": [
    {"t": 500,"seq":1,"type":"file_open","path":"/src/main.js"},
    {"t": 1500,"seq":2,"type":"text_insert","path":"/src/main.js","offset":0,"text":"const name = 'Dunia';"},
    {"t": 2400,"seq":3,"type":"selection","path":"/src/main.js","start":6,"end":10}
  ]
}
```

Semua file/events yang di-upload tervalidasi dan dibatasi ukuran, depth, ekstensi bahasa, rate, MIME, escape path. Jangan mempercayai event dari browser sebagai trusted HTML/JS.

## 3. Scrim Studio — instruktur/admin

Alur utama:
1. Admin membuat course, modul, lalu memilih `Tambah Materi > Scrim Interaktif`.
2. Scrim Studio editor menyiapkan starter project, bahasa, file layout, runtime, tema dan durasi target.
3. Instructor menyetujui permission mikrofon, dapat merekam editor activity/audio; timer, record/pause/stop, review, re-record segmen, chapter markers, preview subtitles, publish.
4. Recording file/event chunks di-buffer local lalu upload resumable; jangan kehilangan seluruh rekaman jika jaringan putus. Kegagalan permission mic menghasilkan event-only scrim dengan transcript/voiceover opsional.
5. Preview harus sama dengan learner playback; instruktur dapat mengedit/re-record scene atau memulai revision baru (published revision immutable).
6. Record revision dapat ditandai `draft`, `processing`, `ready_for_review`, `published`, `archived`; poster image optional untuk listing lesson.
7. Aksesibilitas: captions/transcript manual atau upload; tidak wajib otomatis (paid transcription tidak dibolehkan sebagai dependency inti).
8. Editor tools: file explorer, tab, syntax highlighting, timeline scrubber, display captured event count, audio waveform opsional local, recording health indicators, import/export Scrim JSON+media.

## 4. Player — learner

Player terdiri dari timeline player (play/pause/seek/speed), audio narasi, reconstructed Monaco editor, file explorer, optional console/preview, subtitle/transcript, note/bookmark, button copy/save fork.

### State machine

`LOADING -> READY -> PLAYING -> PAUSED_ORIGINAL -> PRACTICING_FORK -> RESUMING_ORIGINAL -> PLAYING -> ENDED`

- On `PLAY`: events original diterapkan berdasarkan time + ordered sequence; sinkronisasi dengan **audio clock**, review/seek from latest checkpoint.
- On `PAUSE`: berhenti menerapkan instructor changes; current reconstructed document/selection frozen. Pengguna bisa select/copy, edit/hapus/tambah kode.
- On first edit while pause: buat **learner fork di memori** yang disalin dari original state pada timestamp tersebut. Instructor event store tidak berubah.
- On `RUN`: jalankan fork melalui browser sandbox dari runtime yang didukung; output terisolasi, tidak mengubah instructor timeline.
- On `SAVE MY VERSION`: simpan fork per akun/project entitlement (guest: local session only), dengan timestamp provenance.
- On `RESUME`: pengguna bisa pilih `Kembali ke rekaman pengajar` (default) yang menghentikan fork editing dan mengembalikan original frame pada waktu pause; lalu replays event dari asli. Simpan fork hanya bila user memilih menyimpan atau autosave dengan persetujuan sesuai UI; jangan diam-diam mencampur edit learner ke instructor.
- On `SEEK` to any timestamp: load nearest previous checkpoint and apply subsequent events deterministically sampai t; output sama dengan pemutaran dari awal; sync audio seek.
- On `ENDED`: tombol replay; no YouTube related thumbnails because it is own scrim format. Completed lesson follows explicit course rules, not merely opened recording.

### Behavior example

Instructor record contains `const x = 10` at 00:25 and changes to 15 at 00:35. Learner pauses at 00:25, changes local code to `const x = 99`, runs it, then hits resume. Instructor at 00:25 still has `const x = 10` and continues changing to `const x = 15` by 00:35, regardless of learner fork. Learner may later reopen saved fork if they saved it.

## 5. Synchronization / durability / scale

- Store event monotonic `t` and `seq`, audio file start offset; reconstruct from checkpoints (e.g. every 15–30 sec or after 100–500 events, adjustable) rather than replaying all from 0 on seek. Measure memory/time on lower-end devices.
- Separate event chunks from binary audio files; content checksum and version. Upload in chunks with retry/backoff/idempotency keys, then server commits an immutable recording revision only when full chunks and checksums verified.
- Ensure replay ordering across files & unicode edits; deterministic seeking; user fork references recording revision and from_t_ms for stable behavior after author updates.
- Use Laravel private file storage: lesson entitlement required to fetch published audio/event chunks/learner fork. Public demo scrim may have public chunks. Avoid broadcasting private source snippets via static webroot.
- Allow instructor to **duplicate** recording as new revision (not editing already published originals in place), and keep old revisions accessible for previously saved forks if retention permits.
- Implement progress resume: last_t_ms, source_revision, learner last active fork, optional speed/subtitle pref.

## 6. Runtime and security

- Editor code is untrusted. Browser-level run: HTML/CSS/JS sandbox iframe/Worker; Python via Pyodide; SQL via SQLite WASM; R via WebR where supported. Do **not** call `eval` in main application origin or run PHP/node/python commands on shared hosting server.
- Transcripts, code text and audio accessibility. Very long sessions have capacity/time per course limits. Compression + snapshots to reduce bandwidth.
- No paid API required for essential recorder/player. Avoid dependency on Scrimba proprietary code/content; build independent concept and engine; review OSS licensing.

## 7. API / operations suggested

- Instructor: `POST /api/v1/courses/{course}/lessons/{lesson}/scrim/recordings`, `POST /.../{scrim}/chunks`, `POST /.../finalize`, `POST /.../publish`, `GET /.../preview` with instructor ownership.
- Learner: `GET /api/v1/lessons/{lesson}/scrim/manifest`, time-scoped signed private asset access; `POST /api/v1/scrim-forks`, `PUT /api/v1/scrim-forks/{fork}`, `GET /api/v1/scrim-forks/{fork}` with learner ownership.
- Server accepts payload strictly within configured max bytes/event count/duration. Streaming chunk authorization cannot leak files from private course.

## 8. Acceptance scenarios (must actually test)

1. Recording two code edits, pause/resume instructor, finalizes into stored revision, replays output identical to original.
2. Pause at 25 sec, edit code, run and copy, resume -> original from 25 sec, no learner edits leak into timeline.
3. Save fork, refresh page -> learner can reopen their fork, instructor original unchanged.
4. Seek forwards/backwards repeatedly -> reconstructed source deterministic; no duplicate/out-of-order events.
5. Multi-file switching and Unicode insert/delete replay correctly.
6. Audio drift acceptable/within specified tolerance and player slows/pause/resume correctly; no audio privilege issues when mic denied.
7. Unauthenticated user cannot fetch premium scrim chunks; learner A cannot open fork of learner B.
8. Canceled upload, unsupported input, malformed event, missing chunk, network retry all produce recoverable error and **never corrupt published revision**.
9. Keyboard and screen reader can access controls, copy, save, and pause.
10. Performance on representative low-memory mobile; if run runtime unsupported, editing still works with clear unsupported status.
