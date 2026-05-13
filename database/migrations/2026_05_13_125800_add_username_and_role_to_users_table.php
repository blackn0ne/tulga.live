<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default(UserRole::Student->value)->after('email');
        });

        DB::table('users')->orderBy('id')->get(['id', 'name', 'email'])->each(function (object $user): void {
            $baseUsername = Str::slug(Str::before($user->email, '@'), separator: '_');

            if ($baseUsername === '') {
                $baseUsername = Str::slug($user->name, separator: '_');
            }

            if ($baseUsername === '') {
                $baseUsername = 'user';
            }

            $username = $baseUsername;
            $suffix = 1;

            while (DB::table('users')
                ->where('username', $username)
                ->where('id', '!=', $user->id)
                ->exists()) {
                $username = "{$baseUsername}_{$suffix}";
                $suffix++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $username,
                    'role' => UserRole::Student->value,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role']);
        });
    }
};
