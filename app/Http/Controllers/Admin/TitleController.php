<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTitleRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Models\Language;
use App\Models\Studio;
use App\Models\Title;
use App\Services\TitleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TitleController extends Controller
{
    public function __construct(private TitleService $titleService) {}

    public function index(): Response
    {
        $filterKeys = ['search', 'type', 'year', 'language_id'];

        $titles = Title::with('language')
            ->filter(request()->only($filterKeys))
            ->latest('title_id')
            ->paginate(20)
            ->withQueryString();

        $years = Title::whereNotNull('release_date')
            ->selectRaw('YEAR(release_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return Inertia::render('Admin/Titles/Index', [
            'titles'    => $titles,
            'filters'   => request()->only($filterKeys),
            'languages' => Language::orderBy('language_name')->get(['language_id', 'language_name']),
            'years'     => $years,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Titles/Form', [
            'languages' => Language::orderBy('language_name')->get(['language_id', 'language_name']),
            'studios'   => Studio::orderBy('studio_name')->get(['studio_id', 'studio_name']),
        ]);
    }

    public function store(StoreTitleRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $studios = $data['studio_ids'] ?? [];
        unset($data['studio_ids']);

        $title = $this->titleService->create($data);

        if ($studios) {
            $this->titleService->syncStudios($title, $studios);
        }

        return redirect()->route('admin.titles.index')
            ->with('success', 'Title đã được tạo thành công.');
    }

    public function edit(Title $title): Response
    {
        return Inertia::render('Admin/Titles/Form', [
            'title'     => $title->load(['language', 'studios', 'persons.roles']),
            'languages' => Language::orderBy('language_name')->get(['language_id', 'language_name']),
            'studios'   => Studio::orderBy('studio_name')->get(['studio_id', 'studio_name']),
        ]);
    }

    public function update(UpdateTitleRequest $request, Title $title): RedirectResponse
    {
        $data    = $request->validated();
        $studios = $data['studio_ids'] ?? null;
        unset($data['studio_ids']);

        $this->titleService->update($title, $data);

        if ($studios !== null) {
            $this->titleService->syncStudios($title, $studios);
        }

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Title $title): RedirectResponse
    {
        $this->titleService->delete($title);

        return redirect()->route('admin.titles.index')
            ->with('success', 'Đã xoá title.');
    }

    public function show(Title $title): Response
    {
        return Inertia::render('Admin/Titles/Show', [
            'title' => $title->load(['language', 'studios', 'persons', 'reviews.user']),
        ]);
    }
}
