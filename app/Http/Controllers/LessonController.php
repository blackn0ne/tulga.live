<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $lessons = Lesson::query()
            ->with([
                'subject:id,name',
                'schoolClass:id,name',
                'teacher:id,name',
            ])
            ->latest('starts_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'subject_name' => $lesson->subject?->name,
                'class_name' => $lesson->schoolClass?->name,
                'teacher_name' => $lesson->teacher?->name,
                'starts_at' => $lesson->starts_at->format('d.m.Y H:i'),
                'jitsi_room' => $lesson->jitsi_room,
                'meeting_url' => $this->meetingUrl($lesson->jitsi_room),
                'meeting_provider' => $this->meetingProviderLabel($lesson->meeting_provider),
                'meeting_status' => $this->meetingStatusLabel($lesson->meeting_status),
                'meeting_has_room' => filled($lesson->jitsi_room),
            ]);

        return Inertia::render('lessons/Index', [
            'lessons' => $lessons,
        ]);
    }

    /**
     * Display the specified lesson.
     */
    public function show(Request $request, Lesson $lesson): Response
    {
        /** @var User $user */
        $user = $request->user();
        $viewerRole = $this->resolveViewerRole($lesson, $user);

        abort_if($viewerRole === null, 403);

        $lesson->load([
            'subject:id,name',
            'schoolClass:id,name',
            'teacher:id,name',
        ]);

        return Inertia::render('lessons/Show', [
            'status' => $request->session()->get('status'),
            'viewerRole' => $viewerRole,
            'canStart' => $viewerRole === 'teacher',
            'canEnter' => in_array($viewerRole, ['admin', 'teacher', 'student'], true)
                && $lesson->meeting_status === 'started'
                && filled($lesson->jitsi_room),
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'subject_name' => $lesson->subject?->name,
                'class_name' => $lesson->schoolClass?->name,
                'teacher_name' => $lesson->teacher?->name,
                'starts_at' => $lesson->starts_at->format('d.m.Y H:i'),
                'jitsi_room' => $lesson->jitsi_room,
                'meeting_url' => $this->meetingUrl($lesson->jitsi_room),
                'meeting_provider' => $this->meetingProviderLabel($lesson->meeting_provider),
                'meeting_status' => $this->meetingStatusLabel($lesson->meeting_status),
                'meeting_status_key' => $lesson->meeting_status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $this->ensureAdmin($request);

        return Inertia::render('lessons/Create', [
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'classes' => SchoolClass::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => User::query()
                ->where('role', UserRole::Teacher->value)
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

        $validated = $this->validateLesson($request);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        $lesson = Lesson::query()->create([
            'title' => $subject->name,
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'],
            'teacher_id' => $validated['teacher_id'],
            'starts_at' => $validated['starts_at'],
            'end_at' => $validated['end_at'],
            'meeting_provider' => 'jitsi',
            'meeting_status' => 'scheduled',
        ]);

        $lesson->update([
            'jitsi_room' => $this->defaultJitsiRoom($lesson),
        ]);

        return to_route('lessons.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Lesson $lesson): Response
    {
        $this->ensureAdmin($request);

        $lesson->load(['subject:id,name', 'schoolClass:id,name', 'teacher:id,name']);

        return Inertia::render('lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'subject_id' => $lesson->subject_id,
                'class_id' => $lesson->class_id,
                'teacher_id' => $lesson->teacher_id,
                'starts_at' => $lesson->starts_at->format('Y-m-d\TH:i'),
                'end_at' => $lesson->end_at?->format('Y-m-d\TH:i') ?? '',
                'jitsi_room' => $lesson->jitsi_room,
            ],
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'classes' => SchoolClass::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => User::query()
                ->where('role', UserRole::Teacher->value)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $this->validateLesson($request, $lesson);

        $subject = Subject::query()->findOrFail($validated['subject_id']);

        $lesson->update([
            'title' => $subject->name,
            'subject_id' => $validated['subject_id'],
            'class_id' => $validated['class_id'],
            'teacher_id' => $validated['teacher_id'],
            'starts_at' => $validated['starts_at'],
            'end_at' => $validated['end_at'],
            'meeting_provider' => 'jitsi',
            'jitsi_room' => $lesson->jitsi_room ?: $this->defaultJitsiRoom($lesson),
        ]);

        return to_route('lessons.index');
    }

    /**
     * Start the specified lesson.
     */
    public function start(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->ensureTeacher($request);
        abort_unless($lesson->teacher_id === $request->user()?->id, 403);

        if (! filled($lesson->jitsi_room)) {
            $lesson->update([
                'jitsi_room' => $this->defaultJitsiRoom($lesson),
                'meeting_provider' => 'jitsi',
            ]);
        }

        if ($lesson->meeting_status !== 'started') {
            $lesson->update([
                'meeting_status' => 'started',
            ]);
        }

        return to_route('lessons.show', $lesson)
            ->with('status', 'Сабақ басталды. Jitsi Meet жаңа бетте ашылады.');
    }

    /**
     * Finish the specified lesson.
     */
    public function finish(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->ensureTeacher($request);
        abort_unless($lesson->teacher_id === $request->user()?->id, 403);

        if ($lesson->meeting_status !== 'finished') {
            $lesson->update([
                'meeting_status' => 'finished',
            ]);
        }

        return to_route('lessons.show', $lesson)
            ->with('status', 'Сабақ жабылды. Оқушылар үшін ол өткен сабақ ретінде көрсетіледі.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->ensureAdmin($request);

        $currentPage = max(1, $request->integer('page', 1));

        $lesson->delete();

        $lastPage = max(1, (int) ceil(Lesson::query()->count() / 25));

        return to_route('lessons.index', [
            'page' => min($currentPage, $lastPage),
        ]);
    }

    /**
     * Validate lesson data.
     *
     * @return array<string, mixed>
     */
    private function validateLesson(Request $request, ?Lesson $lesson = null): array
    {
        if (blank($request->input('end_at'))) {
            $request->merge(['end_at' => null]);
        }

        $validated = $request->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')],
            'class_id' => ['required', Rule::exists('classes', 'id')],
            'teacher_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', UserRole::Teacher->value)),
            ],
            'starts_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date'],
        ]);

        $start = Carbon::parse($validated['starts_at']);
        $endInput = $validated['end_at'] ?? null;
        $end = filled($endInput)
            ? Carbon::parse($endInput)
            : $start->copy()->addMinutes(45);

        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'end_at' => 'Аяқталу уақыты басталу уақытынан кейін болуы керек.',
            ]);
        }

        $validated['starts_at'] = $start;
        $validated['end_at'] = $end;

        $this->ensureNoScheduleConflicts($validated, $lesson);

        return $validated;
    }

    /**
     * Ensure teacher and class schedules do not overlap.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureNoScheduleConflicts(array $validated, ?Lesson $lesson = null): void
    {
        $start = Carbon::parse($validated['starts_at']);
        $rangeStart = $start->copy()->addMinute()->subHour();
        $rangeEnd = $start->copy()->subMinute()->addHour();

        $baseQuery = Lesson::query()
            ->when($lesson !== null, fn ($query) => $query->whereKeyNot($lesson->id))
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd]);

        $teacherConflict = (clone $baseQuery)
            ->where('teacher_id', $validated['teacher_id'])
            ->exists();

        if ($teacherConflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Бұл уақытта мұғалімге басқа сабақ қойылып қойған.',
            ]);
        }

        $classConflict = (clone $baseQuery)
            ->where('class_id', $validated['class_id'])
            ->exists();

        if ($classConflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Бұл уақытта осы сыныпқа басқа сабақ жоспарланған.',
            ]);
        }
    }

    /**
     * Get the localized meeting provider label.
     */
    private function meetingProviderLabel(string $provider): string
    {
        return match ($provider) {
            'jitsi' => 'Jitsi Meet',
            default => $provider,
        };
    }

    /**
     * Generate a stable default Jitsi room name.
     */
    private function defaultJitsiRoom(Lesson $lesson): string
    {
        return Str::lower('tulga-live-lesson-'.$lesson->id);
    }

    /**
     * Build the public meeting URL for a lesson room.
     */
    private function meetingUrl(?string $room): ?string
    {
        if (! filled($room)) {
            return null;
        }

        return config('services.jitsi.public_url').'/'.ltrim($room, '/');
    }

    /**
     * Get the localized meeting status label.
     */
    private function meetingStatusLabel(string $status): string
    {
        return match ($status) {
            'scheduled' => 'Жоспарланған',
            'started' => 'Басталған',
            'finished' => 'Өтілді',
            default => $status,
        };
    }

    /**
     * Resolve the current viewer role for the lesson.
     */
    private function resolveViewerRole(Lesson $lesson, User $user): ?string
    {
        if ($user->role === UserRole::Admin) {
            return 'admin';
        }

        if ($user->role === UserRole::Teacher && $lesson->teacher_id === $user->id) {
            return 'teacher';
        }

        if ($user->role === UserRole::Student && $user->class_id !== null && $lesson->class_id === $user->class_id) {
            return 'student';
        }

        return null;
    }
}
