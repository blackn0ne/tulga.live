<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('jitsi_room')->nullable()->after('meeting_start_url');
        });

        DB::table('lessons')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $lesson): void {
                DB::table('lessons')
                    ->where('id', $lesson->id)
                    ->update([
                        'meeting_provider' => 'jitsi',
                        'meeting_external_id' => null,
                        'meeting_password' => null,
                        'meeting_join_url' => null,
                        'meeting_start_url' => null,
                        'jitsi_room' => 'tulga-live-lesson-'.$lesson->id,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('jitsi_room');
        });
    }
};
