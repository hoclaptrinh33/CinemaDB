<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->when(request('search'), fn($q, $v) =>
            $q->where('username', 'LIKE', "%{$v}%")
                ->orWhere('email', 'LIKE', "%{$v}%"))
            ->when(request('role'), fn($q, $v) =>
            $q->where('role', $v))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => request()->only(['search', 'role']),
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load('reviews'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'role'      => ['sometimes', Rule::in(['ADMIN', 'MODERATOR', 'USER'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return back()->with('success', 'Cập nhật user thành công.');
    }

    /**
     * PATCH /admin/users/{user}/reputation
     * Điều chỉnh điểm reputation trực tiếp (admin only).
     */
    public function adjustReputation(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reputation' => ['required', 'integer', 'min:0', 'max:999999'],
            'note'       => ['nullable', 'string', 'max:255'],
        ]);

        $user->update(['reputation' => $data['reputation']]);

        return back()->with('success', "Đã cập nhật reputation của {$user->username} thành {$data['reputation']}.");
    }
}
