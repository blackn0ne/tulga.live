<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ScheduleMay152026LessonsSeeder extends Seeder
{
    private const SCHEDULE_DATE = '2026-05-15';

    /** @var array<string, string> Кестедегі сабақ уақытының аяқталуы (HH:MM) */
    private const SLOT_END = [
        '09:00' => '09:45',
        '09:50' => '10:35',
        '10:40' => '11:30',
        '11:35' => '12:20',
        '12:25' => '13:10',
        '13:15' => '14:00',
    ];

    /**
     * Сабақ кестесі 15.05.2026 (7–9 сынып, Word кестесінен).
     * Қайта орындағанда сол күннің сабақтарын алып тастайды.
     */
    public function run(): void
    {
        $day = CarbonImmutable::parse(self::SCHEDULE_DATE);

        Lesson::query()->whereDate('starts_at', $day)->delete();

        /** @var list<array{class:string,time:string,subject:string,teacher:string}> $rows */
        $rows = require __DIR__.'/data/may152026_schedule_rows.php';

        foreach ($rows as $row) {
            $schoolClass = SchoolClass::query()->firstOrCreate(
                ['name' => $row['class']],
            );

            $subject = Subject::query()->firstOrCreate(
                ['name' => $row['subject']],
            );

            $teacher = $this->resolveTeacherUser($row['teacher']);

            $startsAt = CarbonImmutable::parse(self::SCHEDULE_DATE.' '.$row['time'].':00');
            $endTime = self::SLOT_END[$row['time']] ?? '14:00';
            $endsAt = CarbonImmutable::parse(self::SCHEDULE_DATE.' '.$endTime.':00');

            $lesson = Lesson::query()->create([
                'title' => $row['subject'],
                'subject_id' => $subject->id,
                'class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'starts_at' => $startsAt,
                'end_at' => $endsAt,
                'meeting_provider' => 'jitsi',
                'meeting_status' => 'scheduled',
                'meeting_external_id' => null,
                'meeting_password' => null,
                'meeting_join_url' => null,
                'meeting_start_url' => null,
                'jitsi_room' => null,
            ]);

            $lesson->forceFill([
                'jitsi_room' => 'tulga-live-lesson-'.$lesson->id,
            ])->save();
        }
    }

    private function resolveTeacherUser(string $displayName): User
    {
        $displayName = trim(preg_replace('/\s+/u', ' ', $displayName) ?? $displayName);
        $slug = Str::slug(Str::ascii($displayName));

        if ($slug === '') {
            $slug = 'teacher';
        }

        $email = sprintf(
            'timetable.%s.%s@seed.tulga.local',
            $slug,
            substr(hash('crc32b', $displayName), 0, 8)
        );

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $displayName,
                'username' => 'tt_'.substr(hash('sha256', $email), 0, 20),
                'password' => Hash::make('password'),
                'role' => UserRole::Teacher,
                'email_verified_at' => now(),
            ]
        );
    }
}
