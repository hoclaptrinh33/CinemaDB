<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function store(Request $request, Title $title, Season $season): RedirectResponse
    {
        $data = $request->validate([
            'episode_number' => ['required', 'integer', 'min:1'],
            'episode_id'     => ['required', 'exists:titles,title_id'],
            'air_date'       => ['nullable', 'date'],
        ]);

        $season->episodes()->create($data);

        return back()->with('success', 'Episode đã được liên kết vào season.');
    }

    public function update(Request $request, Title $title, Season $season, Episode $episode): RedirectResponse
    {
        $data = $request->validate([
            'episode_number' => ['required', 'integer', 'min:1'],
            'air_date'       => ['nullable', 'date'],
        ]);

        $episode->update($data);

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Title $title, Season $season, Episode $episode): RedirectResponse
    {
        $episode->delete();

        return back()->with('success', 'Đã xoá episode.');
    }
}
