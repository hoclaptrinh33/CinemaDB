<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'username'          => $user->username,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'reputation'        => $user->reputation ?? 0,
                    'avatar'            => $user->avatar_url,
                    'avatar_url'        => $user->avatar_url,
                    'cover_url'         => $user->cover_url,
                    'has_password'      => !is_null($user->password),
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
                'can' => [
                    'accessAdmin'    => $user?->isAdmin() ?? false,
                    'accessModerate' => $user && in_array($user->role, ['ADMIN', 'MODERATOR']),
                ],
            ],
            'flash' => [
                'success'      => session('success'),
                'error'        => session('error'),
                'pendingEmail' => session('pending_email'),
                'pendingName'  => session('pending_name'),
            ],
        ];
    }
}
