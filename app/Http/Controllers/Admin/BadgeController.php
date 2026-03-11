<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    private const TIER_VALUES = 'WOOD,IRON,BRONZE,SILVER,GOLD,PLATINUM,DIAMOND';
    private const CONDITION_VALUES = 'manual,review_count,helpful_votes,collections_count,early_bird,distinct_types,collection_nominations_received,follower_count,following_count,activity_streak,login_streak';

    public function index(): Response
    {
        $badges = Badge::query()
            ->when(request('search'), fn($q, $v) =>
            $q->where('name', 'LIKE', "%{$v}%")
                ->orWhere('slug', 'LIKE', "%{$v}%"))
            ->orderByRaw("FIELD(tier,'DIAMOND','PLATINUM','GOLD','SILVER','BRONZE','IRON','WOOD')")
            ->orderBy('slug')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Badges/Index', [
            'badges'  => $badges,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Badges/Form', [
            'conditionTypes' => Badge::CONDITION_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug'            => ['required', 'string', 'max:100', 'unique:badges,slug'],
            'name'            => ['required', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'icon_path'       => ['nullable', 'string', 'max:500'],
            'tier'            => ['required', 'in:' . self::TIER_VALUES],
            'condition_type'  => ['required', 'in:' . self::CONDITION_VALUES],
            'condition_value' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        Badge::create($data);

        return redirect()->route('admin.badges.index')
            ->with('success', 'Huy hiệu đã được tạo.');
    }

    public function edit(Badge $badge): Response
    {
        return Inertia::render('Admin/Badges/Form', [
            'badge'          => $badge,
            'conditionTypes' => Badge::CONDITION_TYPES,
        ]);
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $data = $request->validate([
            'slug'            => ['required', 'string', 'max:100', 'unique:badges,slug,' . $badge->badge_id . ',badge_id'],
            'name'            => ['required', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'icon_path'       => ['nullable', 'string', 'max:500'],
            'tier'            => ['required', 'in:' . self::TIER_VALUES],
            'condition_type'  => ['required', 'in:' . self::CONDITION_VALUES],
            'condition_value' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $badge->update($data);

        return redirect()->route('admin.badges.index')
            ->with('success', 'Huy hiệu đã được cập nhật.');
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        $badge->delete();

        return redirect()->route('admin.badges.index')
            ->with('success', 'Đã xoá huy hiệu.');
    }
}
