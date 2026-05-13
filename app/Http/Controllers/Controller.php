<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Ensure the current user is an admin.
     */
    protected function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::Admin, 403);
    }

    /**
     * Ensure the current user is a teacher.
     */
    protected function ensureTeacher(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::Teacher, 403);
    }
}
