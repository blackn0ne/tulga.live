<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('schoolClass:id,name');

        $lessonsQuery = Lesson::query()
            ->with([
                'subject:id,name',
                'schoolClass:id,name',
                'teacher:id,name',
            ])
            ->where('starts_at', '>=', now()->startOfDay())
            ->orderBy('starts_at');

        if ($user->role === UserRole::Teacher) {
            $lessonsQuery->where('teacher_id', $user->id);
        }

        if ($user->role === UserRole::Student) {
            $lessonsQuery->where('class_id', $user->class_id ?? 0);
        }

        return Inertia::render('Dashboard', [
            'mode' => $user->role->value,
            'userClassName' => $user->schoolClass?->name,
            'status' => $request->session()->get('status'),
            'stats' => [
                'classes' => SchoolClass::query()->count(),
                'subjects' => Subject::query()->count(),
                'users' => User::query()->count(),
                'lessons' => Lesson::query()->count(),
            ],
            'lessons' => $lessonsQuery
                ->take(12)
                ->get()
                ->map(fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'subject_name' => $lesson->subject?->name,
                    'class_name' => $lesson->schoolClass?->name,
                    'teacher_name' => $lesson->teacher?->name,
                    'starts_at' => $lesson->starts_at->format('d.m.Y H:i'),
                    'starts_at_date_key' => $lesson->starts_at->toDateString(),
                    'starts_at_date_label' => Str::ucfirst($lesson->starts_at->locale(app()->getLocale())->translatedFormat('d F')),
                    'starts_at_time' => $lesson->starts_at->format('H:i'),
                    'jitsi_room' => $lesson->jitsi_room,
                    'meeting_url' => $this->meetingUrl($lesson->jitsi_room),
                    'meeting_status' => $this->meetingStatusLabel($lesson->meeting_status),
                    'meeting_status_key' => $lesson->meeting_status,
                ])
                ->values(),
        ]);
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
     * Build the public meeting URL for a lesson room.
     */
    private function meetingUrl(?string $room): ?string
    {
        if (! filled($room)) {
            return null;
        }

        return config('services.jitsi.public_url').'/'.ltrim($room, '/');
    }
}
