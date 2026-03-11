<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyRegistrationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ResendVerificationEmailController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        $user = User::where('email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        // Silently succeed even if user not found (security: don't reveal if email exists)
        if ($user) {
            $token = Str::random(64);
            Cache::put("reg_verify:{$token}", $user->id, now()->addHours(24));
            $user->notify(new VerifyRegistrationEmail($token));
        }

        return redirect()->route('verification.pending')
            ->with('status', 'verification-link-sent')
            ->with('pending_email', $request->email)
            ->with('pending_name', $user?->name);
    }
}
