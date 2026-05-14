<?php

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lessons:auto-finish', function (): void {
    $count = Lesson::query()
        ->whereIn('meeting_status', ['started', 'scheduled'])
        ->whereNotNull('end_at')
        ->where('end_at', '<=', now())
        ->update(['meeting_status' => 'finished']);

    if ($count > 0) {
        $this->info("Авто-жабылды: {$count} сабақ.");
    }
})->purpose('Аяқталу уақыты өткен «жоспарланған» немесе «басталған» сабақтарды автоматты түрде «өтілді» деп белгілейді.');

Schedule::command('lessons:auto-finish')->everyMinute();

Artisan::command('users:renumber-teacher-usernames {--force : Растау сұрауын өткізіп жіберу}', function (): int {
    $teachers = User::query()
        ->where('role', UserRole::Teacher)
        ->orderBy('id')
        ->get();

    if ($teachers->isEmpty()) {
        $this->warn('Мұғалім рөліндегі қолданушылар табылмады.');

        return 0;
    }

    $n = $teachers->count();
    $ids = $teachers->pluck('id')->all();

    if (! $this->option('force')) {
        $this->info("Табылды: {$n} мұғалім. Логиндер U-1 … U-{$n} болып өзгереді (id бойынша сұрып).");
        if (! $this->confirm('Жалғастырасыз ба?', false)) {
            $this->comment('Болдырылмады.');

            return 0;
        }
    }

    for ($i = 1; $i <= $n; $i++) {
        $candidate = 'U-'.$i;
        $taken = User::query()
            ->where('username', $candidate)
            ->whereNotIn('id', $ids)
            ->exists();
        if ($taken) {
            $this->error("«{$candidate}» логині мұғалім емес қолданушыға тиесілі. Алдымен қолданыңыз.");

            return 1;
        }
        $emailCandidate = Str::lower($candidate).'@mail.com';
        $emailTaken = User::query()
            ->where('email', $emailCandidate)
            ->whereNotIn('id', $ids)
            ->exists();
        if ($emailTaken) {
            $this->error("«{$emailCandidate}» e-mail басқа қолданушыға тиесілі.");

            return 1;
        }
    }

    DB::transaction(function () use ($teachers): void {
        foreach ($teachers as $user) {
            $user->forceFill([
                'username' => '__renum_teacher_'.$user->id.'__',
                'email' => '__renum_teacher_'.$user->id.'@renumber.local',
            ])->save();
        }

        $i = 1;
        foreach ($teachers as $user) {
            $username = 'U-'.$i;
            $user->forceFill([
                'username' => $username,
                'email' => Str::lower($username).'@mail.com',
            ])->save();
            $i++;
        }
    });

    $this->info("Жаңартылды: {$n} мұғалім (U-1 … U-{$n}). E-mail: u-1@mail.com … u-{$n}@mail.com пішімінде.");

    return 0;
})->purpose('Бар мұғалімдердің логинін U-1, U-2, … етіп қайта нөмірлеу (id бойынша). E-mail логинге байланысты жаңартылады.');
