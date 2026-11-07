<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower(trim($data['email']));
        $hash = sha1($email);
        $attemptsKey = "login:attempts:{$hash}";
        $lockKey = "login:lock:{$hash}";

        $max = config('auth.login.max_attempts');
        $warnAt = config('auth.login.warn_at');
        $minutes = config('auth.login.lock_minutes');

        $lockedUntil = Cache::get($lockKey);
        if ($lockedUntil && $lockedUntil > now()->timestamp) {
            return $this->locked($lockedUntil - now()->timestamp);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && Hash::check($data['password'], $user->password)) {
            Cache::forget($attemptsKey);

            return response()->json([
                'user' => $user,
                'token' => $user->createToken('auth-token')->plainTextToken,
            ]);
        }

        Cache::add($attemptsKey, 0, now()->addMinutes($minutes));
        $attempts = Cache::increment($attemptsKey) ?: 1;

        if ($attempts >= $max) {
            $until = now()->addMinutes($minutes);
            Cache::put($lockKey, $until->timestamp, $until);
            Cache::forget($attemptsKey);

            return $this->locked($minutes * 60);
        }

        // Ditolak, dengan peringatan mulai percobaan ke-3
        $remaining = $max - $attempts;
        $message = 'Email atau password salah.';

        return response()->json([
            'user' => $user,
            'token' => $user->createToken(
                'auth-token',
                ['*'],
                now()->addMinutes(config('auth.token.lifetime')),
            )->plainTextToken,
        ]);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    private function locked(int $seconds): JsonResponse
    {
        $minutes = (int) ceil($seconds / 60);

        return response()->json([
            'message' => "Akun dibatasi sementara karena terlalu banyak percobaan gagal. Coba lagi dalam {$minutes} menit.",
            'retry_after' => $seconds,
        ], 423)->header('Retry-After', $seconds);
    }
}