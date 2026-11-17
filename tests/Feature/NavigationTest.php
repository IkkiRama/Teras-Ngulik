<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_targets_render_with_published_cms_pages(): void
    {
        $this->seed(ContentSeeder::class);
        foreach (['/', '/belajar/course', '/belajar/akademi', '/belajar/learning-path', '/belajar/ebook', '/belajar/kuis', '/belajar/podcast', '/belajar/video-pendek', '/labs/playground', '/labs/coding-lab', '/labs/challenge', '/labs/code-review', '/labs/game', '/artikel', '/komunitas/diskusi', '/komunitas/showcase', '/komunitas/mentorship', '/komunitas/galeri', '/program/bootcamp', '/program/event', '/program/kompetisi', '/program/karier', '/program/partner', '/marketplace/kelas', '/marketplace/bundling', '/marketplace/langganan', '/marketplace/merchandise', '/marketplace/preorder', '/marketplace/promo', '/tentang', '/bantuan', '/bantuan/faq', '/kontak', '/dokumentasi', '/syarat-ketentuan', '/kebijakan-privasi', '/kebijakan-refund', '/aturan-komunitas', '/aksesibilitas', '/whistleblowing', '/cari?q=web'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_private_navigation_requires_auth_and_admin_is_restricted(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        foreach (['/dashboard', '/dashboard/kelas', '/dashboard/learning-path', '/dashboard/bootcamp', '/dashboard/proyek', '/dashboard/catatan', '/dashboard/review', '/dashboard/leaderboard', '/dashboard/reward', '/dashboard/sertifikat', '/dashboard/profil', '/dashboard/notifikasi', '/dashboard/pesanan', '/account/security', '/diagnostic'] as $path) {
            $this->get($path)->assertOk();
        }
        foreach (['/admin', '/admin/learning', '/admin/content', '/admin/catalog', '/admin/orders', '/admin/users', '/admin/support', '/admin/whistleblowing', '/admin/moderation', '/admin/newsletter', '/admin/settings'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $user->forceFill(['role' => 'admin'])->save();
        foreach (['/admin', '/admin/learning', '/admin/content', '/admin/catalog', '/admin/orders', '/admin/users', '/admin/support', '/admin/whistleblowing', '/admin/moderation', '/admin/newsletter', '/admin/settings'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
