<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudioRequest;
use App\Http\Requests\Admin\UpdateStudioRequest;
use App\Models\Country;
use App\Models\Studio;
use App\Services\StudioService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudioController extends Controller
{
    public function __construct(private StudioService $studioService) {}

    public function index(): Response
    {
        $studios = Studio::with('country')
            ->when(request('search'), fn($q, $v) =>
            $q->where('studio_name', 'LIKE', "%{$v}%"))
            ->latest('studio_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Studios/Index', [
            'studios' => $studios,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Studios/Form', [
            'countries' => Country::orderBy('country_name')->get(['country_id', 'country_name']),
        ]);
    }

    public function store(StoreStudioRequest $request): RedirectResponse
    {
        $this->studioService->create($request->validated());

        return redirect()->route('admin.studios.index')
            ->with('success', 'Studio đã được tạo thành công.');
    }

    public function show(Studio $studio): Response
    {
        return Inertia::render('Admin/Studios/Show', [
            'studio' => $studio->load(['country', 'titles']),
        ]);
    }

    public function edit(Studio $studio): Response
    {
        return Inertia::render('Admin/Studios/Form', [
            'studio'    => $studio,
            'countries' => Country::orderBy('country_name')->get(['country_id', 'country_name']),
        ]);
    }

    public function update(UpdateStudioRequest $request, Studio $studio): RedirectResponse
    {
        $this->studioService->update($studio, $request->validated());

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Studio $studio): RedirectResponse
    {
        $this->studioService->delete($studio);

        return redirect()->route('admin.studios.index')
            ->with('success', 'Đã xoá studio.');
    }
}
