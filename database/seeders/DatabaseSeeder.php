<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'mokhamediyar@gmail.com',
        ], [
            'name' => 'Moka Admin',
            'username' => 'moka',
            'email' => 'mokhamediyar@gmail.com',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'password' => 'index0322',
        ]);

        // $this->call(ScheduleMay142026LessonsSeeder::class);
        // $this->call(ScheduleMay152026LessonsSeeder::class);
    }
}
