<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [...parent::share($request),
            'auth' => fn () => ['user' => $user?->only(['id', 'name', 'username', 'email', 'role', 'email_verified_at', 'age_band', 'bio', 'avatar_seed', 'website', 'github', 'linkedin', 'privacy', 'locale', 'newsletter']), 'xp' => $user?->xpBalance() ?? 0, 'bara' => $user?->baraBalance() ?? 0],
            'flash' => ['success' => fn () => $request->session()->get('success'), 'error' => fn () => $request->session()->get('error')],
            'demo' => (bool) config('teras.demo'),
            'announcement' => fn () => ($value = DB::table('platform_settings')->where('key', 'announcement')->value('value')) ? json_decode($value, true) : null,
        ];
    }
}
