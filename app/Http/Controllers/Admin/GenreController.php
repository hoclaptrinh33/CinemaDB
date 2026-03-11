<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGenreRequest;
use App\Http\Requests\Admin\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GenreController extends Controller
{
    public function index(): Response
    {
        $genres = Genre::when(request('search'), fn($q, $v) =>
        $q->where('genre_name', 'LIKE', "%{$v}%")
            ->orWhere('genre_name_vi', 'LIKE', "%{$v}%"))
            ->withCount('titles')
            ->orderBy('genre_name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Genres/Index', [
            'genres'  => $genres,
            'filters' => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Genres/Form');
    }

    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('admin.genres.index')
            ->with('success', 'Thể loại đã được tạo thành công.');
    }

    public function edit(Genre $genre): Response
    {
        return Inertia::render('Admin/Genres/Form', [
            'genre' => $genre,
        ]);
    }

    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')
            ->with('success', 'Đã xoá thể loại.');
    }
}
