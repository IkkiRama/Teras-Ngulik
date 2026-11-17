# Learning, Scrim, dan browser runtimes

## Implementasi aktual

- Lima akademi; kelas terpublikasi memiliki materi terurut yang dikelompokkan melalui `module_title`. Materi mendukung bacaan, YouTube, Scrim, sumber eksternal, kuis/lab pilihan jawaban, dan tugas yang dinilai pengajar.
- `LearningService` mengotorisasi materi dan akses berlangganan/pembelian. Jawaban benar dan penjelasan tidak dikirim sebelum jawaban kuis dinilai.
- Semua materi wajib harus selesai sebelum sertifikat diterbitkan. Kuis/lab diselesaikan melalui penilaian server, tugas melalui review pengajar. Kelulusan memberi 100 XP dan 25 Bara sekali per pengguna/kelas melalui kunci ledger unik.
- Materi kuis dapat mengatur `time_limit_seconds` (30–10800), `max_attempts` (1–100), dan `pass_score` (1–100) dalam JSON. Sesi dimulai oleh server, token sekali pakai, batas waktu diperiksa kembali ketika dikirim. Memuat ulang tidak mengulang waktu; sesi yang ditinggalkan ikut dihitung sebagai percobaan. Kebijakan ini berlaku untuk kuis/lab dalam course; kuis mandiri tidak dibatasi timer.
- Learning path menyimpan urutan ID kelas secara eksplisit, enrollment jalur, dan memeriksa kelulusan semua pendahulu ketika tombol langkah jalur dibuka. Akses kelas secara mandiri tetap tersedia; prasyarat jalur tidak mengunci katalog umum.
- Tugas menerima PDF/ZIP/TXT/CSV/JSON maksimal 10 MB di penyimpanan privat. Unduhan hanya untuk pemilik atau admin/pengajar kelas; file disajikan sebagai attachment dengan `nosniff`.
- Notebook privat dan ekspor Markdown, kartu review dengan interval adaptif sederhana dan riwayat, proyek akun Free (5) / langganan (50), versi proyek langganan (20), serta permintaan review manual tersedia. Tidak ada reward per klik review/game.

## Scrim

Manifest schema 1 berisi `initial_files`, `duration_ms`, dan event terurut `t`/`seq`. Event `replace` adalah snapshot penuh satu file; `file_open` mengubah tab aktif. `selection` diterima pada schema, tetapi visualisasi selection/cursor editor belum lengkap. Checkpoint dihitung kembali oleh server. Batas per revisi: 10 file, 5.000 event, 2 MB manifest, 60 menit. Rekaman pendek diunggah sekaligus, bukan melalui API chunk resumable.

Studio merekam perubahan teks dan pergantian file menggunakan jam monoton, menyimpan draf editor lokal, dan dapat merekam audio setelah izin mikrofon. Audio disimpan privat, maksimal 25 MB. Revisi terbit disimpan terpisah; menerbitkan revisi baru tidak mengubah rekaman sebelumnya. Pemutar merekonstruksi timeline asli, menghentikan perubahan saat dijeda, dan memisahkan edit latihan. Memutar kembali mengembalikan versi pengajar. Fork akun disimpan sebagai proyek privat beserta lesson/time asal; referensi versi rekaman pada model proyek belum tersedia.

Editor menggunakan textarea dan tab file. Monaco, syntax highlighting lengkap, waveform, scene re-record, unggah chunk/retry lintas sesi, dan import studio belum tersedia. Draf audio belum dipulihkan setelah browser ditutup. Perilaku audio perangkat/mikrofon memerlukan pengujian browser dan izin perangkat yang sesuai.

## Batas sandbox yang nyata

- HTML/CSS/JavaScript DOM berjalan dalam iframe opaque-origin dengan `sandbox="allow-scripts"`, tanpa `allow-same-origin`. CSP internal membatasi jaringan/form. Pesan konsol divalidasi berdasarkan sumber frame dan token; isi pesan ditampilkan sebagai teks React.
- JavaScript console memakai dedicated Worker di dalam iframe. Stop/timeout menghapus dokumen sandbox dan worker; timeout 60 detik. Worker tidak mengeksekusi kode di origin aplikasi.
- HTML dengan JavaScript DOM masih dapat membuat loop tanpa akhir yang membebani proses/tab browser. Tombol Stop bukan jaminan pemulihan ketika event loop browser terkunci; pengguna mungkin harus menutup tab. Jangan gunakan preview DOM sebagai judge berisiko tinggi atau mengklaim isolasi CPU/memori penuh.
- Python, SQL, dan R memakai adapter browser yang memuat versi CDN tetap: Pyodide 0.27.7, sql.js 1.13.0, WebR 0.4.4. Adapter memerlukan koneksi internet, dukungan WASM, dan memori perangkat. Status keberhasilan runtime eksternal harus mengikuti pengujian browser aktual; build/typecheck bukan bukti runtime CDN sudah berjalan. Kegagalan pemuatan ditampilkan sebagai error, tidak dipalsukan sebagai hasil eksekusi.
- Sesi runtime dimulai ulang setiap Run. Paket arbitrer, akses jaringan bebas, prompt/input interaktif, grafis R, VM/container, Stata executable, serta hidden tests server tidak tersedia. Tidak ada kode pengguna yang dijalankan oleh PHP atau shell server.

Referensi implementasi: [Pyodide workers](https://pyodide.org/en/0.27.0/usage/webworker.html), [sql.js API](https://sql.js.org/documentation/global.html), [WebR communication](https://docs.r-wasm.org/webr/v0.6.0/communication.html).

## Verifikasi

`php artisan test --filter=LearningTest` memeriksa otorisasi/draft, expiry subscription, kerahasiaan jawaban, penyelesaian yang tidak dapat dipalsukan, grant reward/sertifikat idempoten, tugas dan file privat, ownership, privasi anak, antifarm review, revisi Scrim, timer/token/jumlah percobaan, dan urutan/prasyarat jalur.

`node --test tests/Unit/scrim-engine.test.mjs` memeriksa fork tidak mengubah rekaman, seek Unicode/multifile, kesetaraan checkpoint, serta event dengan timestamp milidetik yang sama. Ini pengujian engine, bukan pengganti E2E browser.
