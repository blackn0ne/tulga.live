<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $subjects = Subject::query()
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'created_at' => $subject->created_at->format('d.m.Y H:i'),
                'updated_at' => $subject->updated_at->format('d.m.Y H:i'),
            ]);

        return Inertia::render('subjects/Index', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('subjects/Create');
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

        Subject::query()->create($validated);

        return to_route('subjects.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Subject $subject): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('subjects/Edit', [
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $subject->update($validated);

        return to_route('subjects.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        $this->ensureAdmin($request);

        $currentPage = max(1, $request->integer('page', 1));

        $subject->delete();

        $lastPage = max(1, (int) ceil(Subject::query()->count() / 25));

        return to_route('subjects.index', [
            'page' => min($currentPage, $lastPage),
        ]);
    }
}
