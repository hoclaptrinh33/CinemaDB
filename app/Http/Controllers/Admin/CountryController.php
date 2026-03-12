<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCountryRequest;
use App\Http\Requests\Admin\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CountryController extends Controller
{
    public function index(): Response
    {
        $countries = Country::when(request('search'), fn($q, $v) =>
        $q->where('country_name', 'LIKE', "%{$v}%")
            ->orWhere('iso_code', 'LIKE', "%{$v}%"))
            ->withCount('studios')
            ->orderBy('country_name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Countries/Index', [
            'countries' => $countries,
            'filters'   => request()->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Countries/Form');
    }

    public function store(StoreCountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return redirect()->route('admin.countries.index')
            ->with('success', 'Quốc gia đã được tạo thành công.');
    }

    public function edit(Country $country): Response
    {
        return Inertia::render('Admin/Countries/Form', [
            'country' => $country,
        ]);
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('admin.countries.index')
            ->with('success', 'Đã xoá quốc gia.');
    }
}
