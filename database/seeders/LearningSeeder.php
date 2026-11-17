<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }
        $academies = [
            ['Programming Fundamentals', 'programming-fundamentals', 'Dari rasa penasaran menjadi pola pikir komputasional. Pelajari logika, algoritma, dan kode pertamamu.', '#BCECD6', 'terminal'],
            ['Data Analytics & Data Science', 'data-analytics', 'Ubah data menjadi cerita dan keputusan. Mulai dari spreadsheet, SQL, statistik, hingga Python.', '#B9A7F7', 'chart'],
            ['Cybersecurity', 'cybersecurity', 'Bangun kebiasaan digital yang aman dan pahami pertahanan sistem melalui simulasi terarah.', '#FFD2BC', 'shield'],
            ['Web & Application Development', 'web-development', 'Bikin ide yang bisa diklik. Pelajari HTML, CSS, JavaScript, dan aplikasi yang berguna.', '#DCE8FF', 'globe'],
            ['Finance, Econometrics & Research', 'finance-research', 'Baca angka dengan kritis. Pahami literasi keuangan, pemodelan, dan penelitian yang dapat direproduksi.', '#FFE7A0', 'sparkles'],
        ];
        foreach ($academies as [$name,$slug,$description,$color,$icon]) {
            Academy::updateOrCreate(['slug' => $slug], compact('name', 'description', 'color', 'icon'));
        }
        $specs = [
            ['Logika dulu, baru koding', 'logika-dulu-baru-koding', 'programming-fundamentals', 'Kenali algoritma, variabel, dan percabangan lewat tantangan kecil.', 'free', 0, 55, '#BCECD6',
                "Algoritma adalah urutan langkah yang jelas untuk menyelesaikan masalah. Resep memasak, petunjuk arah, dan kode komputer sama-sama memakai urutan.\n\nBayangkan kamu membuat teh: siapkan gelas, masukkan teh, tuangkan air, lalu aduk. Komputer memerlukan instruksi yang sama jelasnya.\n\nVariabel menyimpan nilai yang dapat dipakai ulang. Contoh JavaScript: const jumlahBuku = 3; console.log(jumlahBuku).\n\nLatihan: tulis lima langkah untuk menyiapkan tas sekolah. Bisakah orang lain mengikuti tanpa bertanya?",
                [['prompt' => 'Apa fungsi algoritma?', 'options' => ['Menyimpan gambar', 'Memberi langkah penyelesaian yang jelas', 'Mempercepat internet', 'Mengganti perangkat keras'], 'correct' => [1], 'explanation' => 'Algoritma menjelaskan langkah yang terurut dan tidak ambigu.'], ['prompt' => 'Nilai yang dapat digunakan kembali dalam program disimpan sebagai…', 'options' => ['Variabel', 'Monitor', 'Folder', 'Kursor'], 'correct' => [0], 'explanation' => 'Variabel memberi nama pada suatu nilai agar dapat dirujuk kembali.']]],
            ['SQL: ngobrol dengan data', 'sql-ngobrol-dengan-data', 'data-analytics', 'Dari SELECT pertama sampai menemukan insight sederhana dari tabel.', 'free', 0, 80, '#B9A7F7',
                "SQL adalah bahasa untuk membaca dan mengolah data dalam tabel. Setiap baris mewakili catatan, setiap kolom mewakili atribut.\n\nSELECT nama, kota FROM peserta; memilih dua kolom. WHERE menyeleksi baris: SELECT * FROM peserta WHERE kota = 'Bandung';\n\nCOUNT menghitung jumlah baris. GROUP BY mengelompokkan sebelum perhitungan. Contoh: SELECT kota, COUNT(*) AS jumlah FROM peserta GROUP BY kota;\n\nLatihan: buka SQL Playground. Buat tabel buku(judul TEXT, tahun INTEGER), masukkan dua baris, lalu pilih buku setelah tahun 2020.",
                [['prompt' => 'Klausa untuk menyaring baris adalah…', 'options' => ['ORDER', 'WHERE', 'TABLE', 'COLUMN'], 'correct' => [1], 'explanation' => 'WHERE membatasi baris yang memenuhi kondisi.'], ['prompt' => 'COUNT(*) digunakan untuk…', 'options' => ['Menghapus tabel', 'Mengurutkan teks', 'Menghitung baris', 'Mengganti kolom'], 'correct' => [2], 'explanation' => 'COUNT(*) menghitung banyak baris pada hasil query.']]],
            ['Jadi lebih aman di dunia digital', 'aman-di-dunia-digital', 'cybersecurity', 'Kenali phishing, kata sandi, dan cara melindungi akunmu.', 'free', 0, 45, '#FFD2BC',
                "Phishing adalah upaya menipu seseorang agar menyerahkan kredensial atau informasi sensitif. Pengirim dapat meniru merek terpercaya.\n\nPeriksa domain asli, bukan hanya nama tampilan. Contoh: login.bank-contoh.evil.invalid milik evil.invalid, bukan bank-contoh.\n\nGunakan kata sandi unik dan pengelola kata sandi. Aktifkan autentikasi dua faktor; passkey lebih tahan phishing jika tersedia. Jangan membagikan OTP.\n\nLatihan defensif: periksa tiga email contoh buatan sendiri dan tandai permintaan mendesak, tautan mencurigakan, dan lampiran tak dikenal. Tidak perlu mengunjungi situs berbahaya.",
                [['prompt' => 'Saat ada pesan meminta OTP akunmu, tindakan terbaik adalah…', 'options' => ['Segera mengirimkannya', 'Menolak dan memeriksa kanal resmi', 'Membagikannya di grup', 'Mengganti nama akun'], 'correct' => [1], 'explanation' => 'OTP adalah rahasia autentikasi dan tidak boleh dibagikan.'], ['prompt' => 'Kata sandi sebaiknya…', 'options' => ['Sama untuk semua akun', 'Berisi nama sendiri saja', 'Unik untuk setiap akun', 'Dibagikan kepada teman'], 'correct' => [2], 'explanation' => 'Kata sandi unik mencegah kebocoran satu layanan membahayakan akun lain.']]],
            ['Website pertamamu, dari nol', 'website-pertamamu', 'web-development', 'Belajar HTML dan CSS sambil langsung mengubah kode. Ide kecilmu siap punya tempat di web.', 'free', 0, 90, '#DCE8FF',
                "HTML memberi struktur pada halaman. CSS mengatur tampilannya. JavaScript menambahkan perilaku interaktif.\n\nMulai dari elemen semantik: <main> untuk konten utama, <h1> untuk judul utama, dan <button> untuk aksi. Hindari membuat semua elemen menjadi div.\n\nCSS memilih elemen dan mengatur propertinya. Contoh: h1 { color: #2F55D4; }. Jaga kontras warna dan ukuran huruf agar terbaca.\n\nPada materi berikutnya, jeda rekaman kode. Ubah judul dan warna lalu jalankan. Saat rekaman dilanjutkan, versi pengajar akan kembali.",
                [['prompt' => 'Elemen untuk judul utama halaman adalah…', 'options' => ['<p>', '<h1>', '<img>', '<span>'], 'correct' => [1], 'explanation' => 'h1 mengidentifikasi judul utama secara semantik.'], ['prompt' => 'CSS terutama digunakan untuk…', 'options' => ['Struktur data', 'Menyimpan sandi', 'Mengatur tampilan halaman', 'Membuat domain'], 'correct' => [2], 'explanation' => 'CSS mengatur warna, ukuran, posisi, dan tata letak.'], ['prompt' => 'Aksi interaktif sebaiknya memakai…', 'options' => ['button', 'div tanpa atribut', 'gambar tanpa teks', 'spasi kosong'], 'correct' => [0], 'explanation' => 'Button menyediakan dukungan keyboard dan semantik aksi.']]],
            ['Membaca data, menulis riset', 'membaca-data-menulis-riset', 'finance-research', 'Bedakan korelasi dan sebab akibat. Bikin analisis sederhana yang bisa diulang.', 'free', 0, 70, '#FFE7A0',
                "Korelasi berarti dua variabel bergerak bersama, tetapi tidak membuktikan hubungan sebab akibat. Faktor ketiga dapat memengaruhi keduanya.\n\nRiset yang dapat direproduksi menyimpan data sumber, kode analisis, asumsi, dan versi alat. Hindari mengubah data mentah tanpa catatan.\n\nGunakan statistik deskriptif untuk memahami distribusi, nilai kosong, dan pencilan sebelum memodelkan. Pisahkan hipotesis awal dari temuan eksploratif.\n\nLatihan: catat pengeluaran fiktif tujuh hari dalam CSV. Hitung total dan median, tuliskan satu keterbatasan data. Materi ini adalah pendidikan, bukan rekomendasi investasi.",
                [['prompt' => 'Korelasi saja membuktikan sebab akibat?', 'options' => ['Selalu', 'Tidak', 'Hanya jika grafik naik', 'Hanya jika sampel kecil'], 'correct' => [1], 'explanation' => 'Confounding dan arah hubungan dapat menjelaskan korelasi.'], ['prompt' => 'Agar analisis bisa direproduksi, kita perlu…', 'options' => ['Menyimpan hasil gambar saja', 'Menghapus data mentah', 'Menyimpan data sumber dan kode', 'Mengubah angka tanpa catatan'], 'correct' => [2], 'explanation' => 'Data, kode, dan lingkungan yang terdokumentasi memungkinkan pengulangan analisis.']]],
        ];
        foreach ($specs as [$title,$slug,$academySlug,$summary,$access,$price,$minutes,$color,$body,$questions]) {
            $academy = Academy::where('slug', $academySlug)->firstOrFail();
            $course = Course::updateOrCreate(['slug' => $slug], ['academy_id' => $academy->id, 'title' => $title, 'summary' => $summary, 'description' => $body, 'level' => 'Pemula', 'age_band' => 'umum', 'access' => $access, 'price' => $price, 'status' => 'published', 'duration_minutes' => $minutes, 'color' => $color, 'instructor' => 'Tim Teras Ngulik']);
            Lesson::updateOrCreate(['course_id' => $course->id, 'slug' => 'mulai-di-sini'], ['title' => 'Mulai dari rasa penasaran', 'module_title' => '01 — Kenalan dengan konsep', 'kind' => 'rich_text', 'position' => 1, 'is_preview' => true, 'content' => ['body' => $body, 'transcript' => $body]]);
            if ($slug === 'website-pertamamu') {
                $initial = [['path' => 'index.html', 'content' => '<main>\n  <h1>Halo, dunia!</h1>\n  <p>Aku sedang belajar membuat website.</p>\n</main>'], ['path' => 'style.css', 'content' => 'body { font-family: system-ui; padding: 32px; background: #F6F8FF; }\nh1 { color: #22263A; }'], ['path' => 'script.js', 'content' => 'console.log("Selamat datang di Teras Ngulik!");']];
                $events = [['t' => 1000, 'seq' => 1, 'type' => 'file_open', 'path' => 'index.html'], ['t' => 3500, 'seq' => 2, 'type' => 'replace', 'path' => 'index.html', 'content' => '<main>\n  <h1>Halo, aku seorang pengulik!</h1>\n  <p>Ini website pertamaku.</p>\n  <button onclick="alert(\'Terus mengulik!\')">Sapa aku</button>\n</main>'], ['t' => 6000, 'seq' => 3, 'type' => 'file_open', 'path' => 'style.css'], ['t' => 9000, 'seq' => 4, 'type' => 'replace', 'path' => 'style.css', 'content' => 'body { font-family: system-ui; padding: 32px; background: #DCE8FF; }\nh1 { color: #2F55D4; }\nbutton { background: #FFE7A0; border: 0; border-radius: 12px; padding: 14px 24px; cursor: pointer; }'], ['t' => 12000, 'seq' => 5, 'type' => 'file_open', 'path' => 'index.html']];
                foreach ($initial as &$file) {
                    $file['content'] = str_replace('\n', "\n", $file['content']);
                } unset($file);
                foreach ($events as &$event) {
                    if (isset($event['content'])) {
                        $event['content'] = str_replace('\n', "\n", $event['content']);
                    }
                } unset($event);
                Lesson::updateOrCreate(['course_id' => $course->id, 'slug' => 'ngulik-langsung'], ['title' => 'Jeda, ubah kode, lihat hasilnya', 'module_title' => '02 — Praktik interaktif', 'kind' => 'scrim', 'position' => 2, 'is_preview' => true, 'content' => ['body' => 'Rekaman contoh editor selama 15 detik. Jeda untuk mengedit kode. Versi pengajar kembali ketika diputar.', 'scrim' => ['schema_version' => 1, 'initial_files' => $initial, 'duration_ms' => 15000, 'events' => $events], 'transcript' => 'Kita mulai dari struktur HTML. Tambahkan tombol yang bisa diklik, lalu berikan warna dengan CSS. Jeda kapan pun untuk bereksperimen.']]);
            }
            Lesson::updateOrCreate(['course_id' => $course->id, 'slug' => 'uji-pemahaman'], ['title' => 'Seberapa paham kamu?', 'module_title' => '03 — Uji pemahaman', 'kind' => 'quiz', 'position' => 3, 'is_preview' => false, 'content' => ['questions' => $questions, 'pass_score' => 70]]);
        }
        $web = Academy::where('slug', 'web-development')->first();
        Course::updateOrCreate(['slug' => 'frontend-project-lab'], ['academy_id' => $web->id, 'title' => 'Frontend Project Lab', 'summary' => 'Bangun portofolio antarmuka dan dapatkan review manual dari pengajar. Katalog contoh; pembayaran memerlukan konfigurasi.', 'description' => 'Program praktik HTML, CSS, dan JavaScript. Akses melalui pembelian setelah payment gateway dikonfigurasi.', 'level' => 'Menengah', 'age_band' => 'umum', 'access' => 'purchase', 'price' => 149000, 'status' => 'published', 'duration_minutes' => 360, 'color' => '#FFD2BC']);
        $premium = Course::where('slug', 'frontend-project-lab')->first();
        Lesson::updateOrCreate(['course_id' => $premium->id, 'slug' => 'proyek-portofolio'], ['title' => 'Proyek akhir: halaman portofolio', 'kind' => 'assignment', 'position' => 1, 'content' => ['body' => 'Buat halaman portofolio yang responsif. Sertakan profil, daftar karya, tautan proyek, dan navigasi keyboard. Kirim tautan repositori beserta penjelasan keputusan desain. Rubrik: struktur semantik (25%), responsivitas (25%), aksesibilitas (25%), keterbacaan kode (25%).'], 'is_preview' => false]);
        foreach (Academy::all() as $academy) {
            $ids = $academy->courses()->where('access', 'free')->pluck('id')->all();
            DB::table('learning_paths')->updateOrInsert(['slug' => 'jalur-'.$academy->slug], ['title' => 'Mulai di '.$academy->name, 'description' => 'Jalur pengantar contoh. Selesaikan konsep, praktik, lalu evaluasi untuk membangun fondasi.', 'academy_id' => $academy->id, 'course_ids' => json_encode($ids), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('standalone_quizzes')->updateOrInsert(['slug' => 'logika-sehari-hari'], ['title' => 'Logika sehari-hari', 'description' => 'Lima menit untuk menguji cara berpikirmu. Latihan mandiri tanpa sertifikat.', 'age_band' => 'umum', 'questions' => json_encode($specs[0][9]), 'pass_score' => 70, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('standalone_quizzes')->updateOrInsert(['slug' => 'aman-atau-phishing'], ['title' => 'Aman atau phishing?', 'description' => 'Latihan mengenali keputusan keamanan yang lebih baik.', 'age_band' => 'sma', 'questions' => json_encode($specs[2][9]), 'pass_score' => 70, 'created_at' => now(), 'updated_at' => now()]);
    }
}
