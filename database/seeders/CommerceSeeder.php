<?php

namespace Database\Seeders;

use App\Models\ContentItem;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Course::where('access', 'purchase')->get() as $course) {
            Product::updateOrCreate(['slug' => $course->slug], ['title' => $course->title, 'kind' => 'course', 'course_id' => $course->id, 'description' => $course->summary, 'price' => $course->price, 'active' => true, 'metadata' => ['demo' => true]]);
        }
        $courses = Course::where('access', 'purchase')->pluck('id')->all();
        Product::updateOrCreate(['slug' => 'paket-karya-pertama'], ['title' => 'Paket Karya Pertama', 'kind' => 'bundle', 'description' => 'Paket kelas premium untuk membangun proyek pertama. Katalog contoh lokal.', 'price' => 199000, 'metadata' => ['course_ids' => $courses, 'demo' => true]]);
        Product::updateOrCreate(['slug' => 'teras-pro-bulanan'], ['title' => 'Teras Pro · 30 hari', 'kind' => 'subscription', 'description' => 'Akses materi berlabel langganan dan kapasitas proyek Pro. Perpanjangan manual; tanpa debit otomatis.', 'price' => 59000, 'metadata' => ['days' => 30, 'demo' => true]]);
        Product::updateOrCreate(['slug' => 'notebook-ngulik'], ['title' => 'Notebook Ngulik', 'kind' => 'merchandise', 'description' => 'Buku catatan untuk merawat ide. Contoh merchandise lokal, belum dijual ke publik.', 'price' => 45000, 'stock' => 20, 'metadata' => ['demo' => true, 'fulfillment' => 'manual']]);
        Product::updateOrCreate(['slug' => 'kaus-ngulik-preorder'], ['title' => 'Kaus Ngulik · Preorder', 'kind' => 'preorder', 'description' => 'Contoh preorder lokal. Produksi dan pengiriman diatur admin setelah pembayaran terverifikasi.', 'price' => 129000, 'stock' => 30, 'metadata' => ['demo' => true, 'fulfillment' => 'manual', 'estimate' => 'Estimasi diinformasikan admin sebelum transaksi produksi.']]);
        foreach (ContentItem::whereIn('kind', ['event', 'bootcamp', 'ebook', 'podcast', 'mentorship'])->where('access', 'purchased')->get() as $item) {
            Product::updateOrCreate(['slug' => 'content-'.$item->slug], ['title' => $item->title, 'kind' => $item->kind, 'subject_id' => $item->id, 'description' => $item->summary ?? 'Konten premium', 'price' => $item->price, 'stock' => $item->capacity, 'metadata' => ['demo' => true]]);
        }
        DB::table('coupons')->updateOrInsert(['code' => 'NGULIKBARENG'], ['percent' => 15, 'max_uses' => 100, 'min_seats' => 2, 'uses' => 0, 'expires_at' => now()->addMonths(3), 'created_at' => now(), 'updated_at' => now()]);
        if ($courses) {
            DB::table('reward_items')->updateOrInsert(['title' => 'Akses kelas premium'], ['description' => 'Tukar Bara untuk satu kelas premium contoh.', 'bara_cost' => 100, 'stock' => 20, 'course_id' => $courses[0], 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('reward_items')->updateOrInsert(['title' => 'Notebook Ngulik'], ['description' => 'Reward merchandise contoh; pengiriman diproses admin.', 'bara_cost' => 250, 'stock' => 10, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('platform_settings')->updateOrInsert(['key' => 'announcement'], ['value' => json_encode(['text' => 'Belajar lebih seru bareng teman. Hemat 15% untuk 2 peserta dengan NGULIKBARENG.', 'href' => '/marketplace/promo']), 'created_at' => now(), 'updated_at' => now()]);
    }
}
