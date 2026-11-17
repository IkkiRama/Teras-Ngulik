<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function page(Request $request, string $screen)
    {
        return Inertia::render('App', ['screen' => $screen, 'data' => ['googleConfigured' => (bool) config('services.google.client_id'), 'email' => $request->email, 'token' => $request->route('token'), 'mailMode' => config('mail.default')]]);
    }

    public function register(Request $request)
    {
        $request->merge(['username' => Str::lower(trim((string) $request->username)), 'email' => Str::lower(trim((string) $request->email))]);
        $input = $request->validate(['name' => 'required|string|max:80', 'username' => ['required', 'regex:/^[a-z0-9_]{3,24}$/', 'unique:users,username'], 'email' => 'required|email|max:190|unique:users,email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()], 'age_band' => ['required', Rule::in(['child', 'sd', 'smp', 'sma', 'adult', 'college'])], 'newsletter' => 'sometimes|boolean']);
        $user = User::create($input);
        if ($user->newsletter) {
            DB::table('newsletter_subscriptions')->updateOrInsert(['email' => $user->email], ['user_id' => $user->id, 'status' => 'pending', 'consent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/verify-email')->with('success', 'Akun dibuat. Silakan verifikasi alamat email.');
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt(['email' => Str::lower($request->email), 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        if ($request->user()->newsletter) {
            DB::table('newsletter_subscriptions')->where('user_id', $request->user()->id)->where('status', 'pending')->update(['status' => 'subscribed', 'updated_at' => now()]);
        }

return redirect('/dashboard')->with('success', 'Email berhasil diverifikasi.');
    }

    public function resend(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Tautan verifikasi dikirim. Mode lokal menyimpan email ke storage/logs/laravel.log.');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Jika alamat terdaftar, tautan pemulihan akan dikirim.');
    }

    public function reset(Request $request)
    {
        $input = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()]]);
        $status = Password::reset($input, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect('/login')->with('success', 'Kata sandi diperbarui. Silakan masuk.');
    }

    public function profile(Request $request)
    {
        return Inertia::render('App', ['screen' => 'profile', 'data' => ['user' => $request->user()]]);
    }

    public function updateProfile(Request $request)
    {
        $request->merge(['username' => Str::lower(trim((string) $request->username))]);
        $input = $request->validate(['name' => 'required|string|max:80', 'username' => ['required', 'regex:/^[a-z0-9_]{3,24}$/', Rule::unique('users')->ignore($request->user()->id)], 'bio' => 'nullable|string|max:1200', 'avatar_seed' => 'nullable|string|max:40', 'website' => 'nullable|url:http,https|max:190', 'github' => 'nullable|url:http,https|max:190', 'linkedin' => 'nullable|url:http,https|max:190', 'privacy' => ['required', Rule::in(['private', 'public'])], 'locale' => ['required', Rule::in(['id', 'en'])], 'newsletter' => 'sometimes|boolean']);
        if (in_array($request->user()->age_band, ['child', 'sd', 'smp'])) {
            $input['privacy'] = 'private';
        }
        $request->user()->fill($input)->save();
        if (array_key_exists('newsletter', $input)) {
            DB::table('newsletter_subscriptions')->updateOrInsert(['email' => $request->user()->email], ['user_id' => $request->user()->id, 'status' => $input['newsletter'] ? ($request->user()->hasVerifiedEmail() ? 'subscribed' : 'pending') : 'unsubscribed', 'consent_at' => $input['newsletter'] ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
        }

        return back()->with('success', 'Profil disimpan.');
    }

    public function otp(Request $request)
    {
        $request->validate(['current_password' => 'required|string']);
        if (! Hash::check($request->current_password, $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Kata sandi saat ini tidak sesuai.']);
        }
        $code = (string) random_int(100000, 999999);
        DB::transaction(function () use ($request, $code) {
            DB::table('password_otps')->where('user_id', $request->user()->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            DB::table('password_otps')->insert(['user_id' => $request->user()->id, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(10), 'created_at' => now(), 'updated_at' => now()]);
        });
        Mail::raw("Kode perubahan kata sandi Teras Ngulik: {$code}. Berlaku 10 menit. Jangan bagikan kode ini.", fn ($m) => $m->to($request->user()->email)->subject('Kode keamanan Teras Ngulik'));

        return back()->with('success', 'Kode OTP dikirim ke alamat email akun.');
    }

    public function changePassword(Request $request)
    {
        $input = $request->validate(['otp' => 'required|digits:6', 'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()]]);
        $valid = DB::transaction(function () use ($request, $input) {
            $otp = DB::table('password_otps')->where('user_id', $request->user()->id)->whereNull('consumed_at')->latest('id')->lockForUpdate()->first();
            if (! $otp || $otp->attempts >= 5 || now()->greaterThan($otp->expires_at)) {
                return false;
            }
            DB::table('password_otps')->where('id', $otp->id)->increment('attempts');
            if (! Hash::check($input['otp'], $otp->code_hash)) {
                return false;
            }
            DB::table('password_otps')->where('id', $otp->id)->update(['consumed_at' => now()]);
            $request->user()->forceFill(['password' => $input['password'], 'remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();

            return true;
        });
        if (! $valid) {
            throw ValidationException::withMessages(['otp' => 'Kode tidak valid, kedaluwarsa, atau sudah digunakan.']);
        }
        $request->session()->regenerate();

        return back()->with('success', 'Kata sandi berhasil diperbarui; sesi lain dicabut.');
    }

    public function google()
    {
        abort_unless(config('services.google.client_id'), 503, 'Google login belum dikonfigurasi.');

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        abort_unless(config('services.google.client_id'), 503);
        $identity = Socialite::driver('google')->user();
        abort_unless(($identity->user['email_verified'] ?? false) === true, 403, 'Google email harus terverifikasi.');
        $user = User::where('google_id', $identity->getId())->first() ?? User::where('email', Str::lower($identity->getEmail()))->first();
        if (! $user) {
            $user = User::create(['name' => $identity->getName() ?: 'Teman Ngulik', 'email' => $identity->getEmail(), 'password' => Str::random(64), 'username' => 'ngulik_'.Str::lower(Str::random(10))]);
        }
        $user->forceFill(['google_id' => $identity->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    public function export(Request $request)
    {
        $uid = $request->user()->id;
        $data = ['profile' => $request->user()->toArray(), 'orders' => DB::table('orders')->where('user_id', $uid)->get(['number', 'status', 'total', 'created_at']), 'projects' => DB::table('projects')->where('user_id', $uid)->get(), 'notes' => DB::table('notebook_entries')->where('user_id', $uid)->get()];

        return response()->streamDownload(fn () => print (json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'teras-ngulik-data.json',['Content-Type' => 'application/json']);
    }
}
