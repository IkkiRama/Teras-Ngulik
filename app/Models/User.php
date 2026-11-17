<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'age_band', 'bio', 'avatar_seed', 'website', 'github', 'linkedin', 'privacy', 'locale', 'newsletter'];

    protected $hidden = ['password', 'remember_token', 'google_id'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'newsletter' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->email = Str::lower($user->email);
            $user->username = Str::lower($user->username ?: 'ngulik_'.Str::lower(Str::random(10)));
            if (in_array($user->age_band, ['child', 'sd', 'smp'])) {
                $user->privacy = 'private';
            }
        });
        static::saving(function (User $user) {
            $user->username = Str::lower($user->username);
        });
    }

    public function xpBalance(): int
    {
        return (int) DB::table('xp_entries')->where('user_id', $this->id)->sum('amount');
    }

    public function baraBalance(): int
    {
        return (int) DB::table('bara_entries')->where('user_id', $this->id)->sum('amount');
    }
}
