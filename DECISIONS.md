# Keputusan implementasi

- Target folder pengguna adalah `D:\laragon\www\Teras Ngulik`; `catatan.txt` dipertahankan. Pengembangan dilakukan di staging lalu disalin tanpa menghapus berkas pengguna.
- Laravel 13/PHP8.3 dan React/Inertia digunakan sesuai acuan. SQLite dipakai untuk menjalankan lokal tanpa mengubah database Laragon lain; MySQL tetap target deployment dan kompatibilitas migrasinya perlu diuji terpisah.
- Katalog seed selalu contoh. Metrik dashboard berasal dari aktivitas database; tidak ada pembayaran sukses palsu atau sertifikat hasil seed.
- XP dan Bara memakai ledger terpisah. XP tidak dapat ditukar. Penerbitan sertifikat dan reward kelulusan dilakukan server setelah syarat terpenuhi.
- Scrim merekam snapshot perubahan editor bertimestamp, memiliki checkpoint dan timeline instruktur yang terpisah dari fork pelajar. Audio opsional.
- Playground menggunakan iframe opaque-origin tanpa allow-same-origin. PHP tidak menjalankan kode arbitrer. Runtime WASM tambahan memerlukan CDN, bukan layanan komputasi server.
- Integrasi provider tidak diaktifkan tanpa kredensial. Tidak ada simulasi pembayaran yang ditampilkan sebagai transaksi sungguhan.
- Konten forum akun anak dibatasi; profil anak privat. Belum ada workflow persetujuan orang tua lengkap, sehingga fitur sosial anak tidak diaktifkan.
- Konten publik dan program dikelola lewat CMS generik bertipe. Workflow editorial/organisasi kompleks di spesifikasi tidak seluruhnya diwakili CMS generik; lihat status.
