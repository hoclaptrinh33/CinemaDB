<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function store(Request $request, Title $title): RedirectResponse
    {
        $data = $request->validate([
            'season_number' => ['required', 'integer', 'min:1'],
        ]);

        $seriesDetail = $title->seriesDetail;

        if (! $seriesDetail) {
            return back()->with('error', 'Title này không phải là series.');
        }

        $seriesDetail->seasons()->create($data);

        return back()->with('success', 'Season đã được tạo thành công.');
    }

    public function update(Request $request, Title $title, Season $season): RedirectResponse
    {
        $data = $request->validate([
            'season_number' => ['required', 'integer', 'min:1'],
        ]);

        $season->update($data);

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Title $title, Season $season): RedirectResponse
    {
        $season->delete();

        return back()->with('success', 'Đã xoá season.');
    }
}
