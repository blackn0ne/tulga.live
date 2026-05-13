<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $classes = SchoolClass::query()
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (SchoolClass $schoolClass): array => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'created_at' => $schoolClass->created_at->format('d.m.Y H:i'),
                'updated_at' => $schoolClass->updated_at->format('d.m.Y H:i'),
            ]);

        return Inertia::render('classes/Index', [
            'classes' => $classes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('classes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        SchoolClass::query()->create($validated);

        return to_route('classes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, SchoolClass $schoolClass): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('classes/Edit', [
            'schoolClass' => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $schoolClass->update($validated);

        return to_route('classes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->ensureAdmin($request);

        $currentPage = max(1, $request->integer('page', 1));

        $schoolClass->delete();

        $lastPage = max(1, (int) ceil(SchoolClass::query()->count() / 25));

        return to_route('classes.index', [
            'page' => min($currentPage, $lastPage),
        ]);
    }
}
