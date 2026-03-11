<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLanguageRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(): Response
    {
        $languages = Language::when(request('search'), fn($q, $v) =>
        $q->where('language_name', 'LIKE', "%{$v}%")
            ->orWhere('iso_code', 'LIKE', "%{$v}%"))
            ->withCount('titles')
            ->orderBy('language_name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Languages/Index', [
            'languages' => $languages,
            'filters'   => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Languages/Form');
    }

    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        Language::create($request->validated());

        return redirect()->route('admin.languages.index')
            ->with('success', 'Ngôn ngữ đã được tạo thành công.');
    }

    public function edit(Language $language): Response
    {
        return Inertia::render('Admin/Languages/Form', [
            'language' => $language,
        ]);
    }

    public function update(UpdateLanguageRequest $request, Language $language): RedirectResponse
    {
        $language->update($request->validated());

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        $language->delete();

        return redirect()->route('admin.languages.index')
            ->with('success', 'Đã xoá ngôn ngữ.');
    }
}
