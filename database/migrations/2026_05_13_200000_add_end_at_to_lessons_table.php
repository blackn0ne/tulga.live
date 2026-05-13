<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dateTime('end_at')->nullable()->after('starts_at');
        });

        foreach (DB::table('lessons')->whereNull('end_at')->orderBy('id')->cursor() as $row) {
            DB::table('lessons')
                ->where('id', $row->id)
                ->update([
                    'end_at' => Carbon::parse($row->starts_at)->addMinutes(45),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('end_at');
        });
    }
};
