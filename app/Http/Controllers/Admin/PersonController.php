<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\UpdatePersonRequest;
use App\Models\Country;
use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function __construct(private PersonService $personService) {}

    public function index(): Response
    {
        $filterKeys = ['search', 'country_id', 'gender'];

        $persons = Person::filter(request()->only($filterKeys))
            ->with('country')
            ->latest('person_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Persons/Index', [
            'persons'   => $persons,
            'filters'   => request()->only($filterKeys),
            'countries' => Country::orderBy('country_name')->get(['country_id', 'country_name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Persons/Form');
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $this->personService->create($request->validated());

        return redirect()->route('admin.persons.index')
            ->with('success', 'Person đã được tạo thành công.');
    }

    public function show(Person $person): Response
    {
        return Inertia::render('Admin/Persons/Show', [
            'person' => $person->load('titles'),
        ]);
    }

    public function edit(Person $person): Response
    {
        return Inertia::render('Admin/Persons/Form', [
            'person' => $person,
        ]);
    }

    public function update(UpdatePersonRequest $request, Person $person): RedirectResponse
    {
        $this->personService->update($person, $request->validated());

        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        $this->personService->delete($person);

        return redirect()->route('admin.persons.index')
            ->with('success', 'Đã xoá person.');
    }
}
