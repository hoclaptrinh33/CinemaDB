<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VerifyPendingRegistrationController extends Controller
{
    public function __invoke(string $token): RedirectResponse
    {
        $userId = Cache::get("reg_verify:{$token}");

        if (! $userId) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Link xác thực không hợp lệ hoặc đã hết hạn. Vui lòng đăng ký lại.']);
        }

        $user = User::find($userId);

        if (! $user) {
            Cache::forget("reg_verify:{$token}");
            return redirect()->route('register')
                ->withErrors(['email' => 'Tài khoản không tồn tại. Vui lòng đăng ký lại.']);
        }

        if ($user->email_verified_at) {
            Cache::forget("reg_verify:{$token}");
            return redirect()->route('home');
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        Cache::forget("reg_verify:{$token}");

        Auth::login($user);

        return redirect()->route('home')->with('success', 'Email đã được xác thực. Chào mừng bạn!');
    }
}
