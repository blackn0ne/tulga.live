<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $users = User::query()
            ->with('schoolClass:id,name')
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $this->roleLabel($user->role),
                'school_class_name' => $user->schoolClass?->name,
                'is_current' => $request->user()?->is($user) ?? false,
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
            'status' => $request->session()->get('status'),
            'generatedCredentials' => $request->session()->get('generatedCredentials'),
            'importResult' => $request->session()->get('importResult'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('users/Create', [
            'roles' => $this->roleOptions(),
            'classes' => SchoolClass::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'class_id' => [
                Rule::requiredIf(fn (): bool => $request->string('role')->toString() === UserRole::Student->value),
                'nullable',
                Rule::exists('classes', 'id'),
            ],
        ]);

        $role = UserRole::from($validated['role']);
        $generatedPassword = $this->generatePassword();

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $this->emailFromUsername($validated['username']),
            'role' => $role,
            'class_id' => $role === UserRole::Student ? $validated['class_id'] : null,
            'email_verified_at' => now(),
            'password' => $generatedPassword,
        ]);

        return to_route('users.index')
            ->with('status', 'Қолданушы сәтті құрылды.')
            ->with('generatedCredentials', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $generatedPassword,
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user): Response
    {
        $this->ensureAdmin($request);

        $user->load('schoolClass:id,name');

        return Inertia::render('users/Edit', [
            'managedUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $this->roleLabel($user->role),
                'school_class_name' => $user->schoolClass?->name,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $this->emailFromUsername($validated['username']);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return to_route('users.index')->with('status', 'Қолданушы деректері жаңартылды.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        if ($request->user()?->is($user)) {
            return back()->with('status', 'Өз аккаунтыңызды бұл бөлімнен жоюға болмайды.');
        }

        $currentPage = max(1, $request->integer('page', 1));

        $user->delete();

        $lastPage = max(1, (int) ceil(User::query()->count() / 25));

        return to_route('users.index', [
            'page' => min($currentPage, $lastPage),
        ])->with('status', 'Қолданушы жойылды.');
    }

    /**
     * Build an email from username.
     */
    private function emailFromUsername(string $username): string
    {
        return Str::lower($username).'@mail.com';
    }

    /**
     * Generate a six-digit password.
     */
    private function generatePassword(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get role options for the front-end.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return [
            ['value' => UserRole::Admin->value, 'label' => $this->roleLabel(UserRole::Admin)],
            ['value' => UserRole::Teacher->value, 'label' => $this->roleLabel(UserRole::Teacher)],
            ['value' => UserRole::Student->value, 'label' => $this->roleLabel(UserRole::Student)],
        ];
    }

    /**
     * Get the localized role label.
     */
    private function roleLabel(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => 'Әкімші',
            UserRole::Teacher => 'Мұғалім',
            UserRole::Student => 'Оқушы',
        };
    }
}
