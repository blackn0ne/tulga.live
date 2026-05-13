<?php

namespace App\Http\Controllers;

use App\Services\StudentSpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentSpreadsheetController extends Controller
{
    public function import(Request $request, StudentSpreadsheetService $service): RedirectResponse
    {
        $this->ensureAdmin($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $result = $service->importFromUpload($request->file('file'));

        $parts = [
            'Жаңа: '.$result['created'],
            'Жаңартылды: '.$result['updated'],
            'Өткізілді: '.$result['skipped'],
        ];
        $message = 'Импорт аяқталды. '.implode('. ', $parts).'.';

        return to_route('users.index')
            ->with('status', $message)
            ->with('importResult', $result);
    }

    public function export(Request $request, StudentSpreadsheetService $service): StreamedResponse
    {
        $this->ensureAdmin($request);

        return $service->exportStudentsWithNewPasswords();
    }
}
