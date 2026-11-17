<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Data demo tidak boleh dimuat di lingkungan produksi.');
        }
        foreach ([['name' => 'Admin Teras', 'username' => 'admin', 'email' => 'admin@teras.test', 'role' => 'admin'], ['name' => 'Teman Ngulik', 'username' => 'pejuang_kode', 'email' => 'belajar@teras.test', 'role' => 'learner']] as $account) {
            $user = User::firstOrNew(['email' => $account['email']]);
            $user->forceFill([...$account, 'password' => 'NgulikDemo!2026', 'email_verified_at' => now(), 'age_band' => 'adult', 'privacy' => 'private', 'avatar_seed' => $account['username']])->save();
        }
        $this->call([LearningSeeder::class, ContentSeeder::class, CommerceSeeder::class]);
    }
}
