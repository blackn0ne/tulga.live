<?php

use App\Models\Lesson;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lessons:auto-finish', function (): void {
    $count = Lesson::query()
        ->where('meeting_status', 'started')
        ->whereNotNull('end_at')
        ->where('end_at', '<=', now())
        ->update(['meeting_status' => 'finished']);

    if ($count > 0) {
        $this->info("Авто-жабылды: {$count} сабақ.");
    }
})->purpose('Аяқталу уақыты өткен «басталған» сабақтарды автоматты түрде «аяқталды» деп белгілейді.');

Schedule::command('lessons:auto-finish')->everyMinute();
