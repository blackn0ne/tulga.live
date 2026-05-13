<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudentSpreadsheetService
{
    /**
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     errors: list<string>,
     * }
     */
    public function importFromUpload(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw ValidationException::withMessages([
                'file' => 'Файлды оқу мүмкін емес.',
            ]);
        }

        $matrix = $this->loadMatrix($realPath);
        if ($matrix === []) {
            throw ValidationException::withMessages([
                'file' => 'Кесте бос.',
            ]);
        }

        $columnMap = $this->resolveColumnMap($matrix[0] ?? []);

        foreach (['name', 'class_name', 'username'] as $required) {
            if (! isset($columnMap[$required])) {
                throw ValidationException::withMessages([
                    'file' => 'Тақырып жолында міндетті баған жоқ: '.$this->requiredLabel($required).' (name, classs/class, username).',
                ]);
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        /** @var list<string> $errors */
        $errors = [];

        DB::transaction(function () use ($matrix, $columnMap, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($matrix as $rowIndex => $cells) {
                if ($rowIndex === 0) {
                    continue;
                }

                $line = $rowIndex + 1;
                $name = $this->cell($cells, $columnMap['name'] ?? null);
                $className = $this->cell($cells, $columnMap['class_name'] ?? null);
                $username = $this->cell($cells, $columnMap['username'] ?? null);
                $passwordFromFile = isset($columnMap['password'])
                    ? $this->cell($cells, $columnMap['password'])
                    : '';

                if ($passwordFromFile !== '' && mb_strlen($passwordFromFile) < 6) {
                    $errors[] = "{$line}-қатар: құпиясөз кемінде 6 таңба болуы керек.";
                    $skipped++;

                    continue;
                }

                if ($username === '' && $name === '' && $className === '') {
                    $skipped++;

                    continue;
                }

                if ($username === '') {
                    $errors[] = "{$line}-қатар: логин (username) бос.";
                    $skipped++;

                    continue;
                }

                $validator = Validator::make(
                    [
                        'name' => $name,
                        'username' => $username,
                        'class_name' => $className,
                    ],
                    [
                        'name' => ['required', 'string', 'max:255'],
                        'username' => ['required', 'string', 'max:255', 'alpha_dash'],
                        'class_name' => ['required', 'string', 'max:255'],
                    ]
                );

                if ($validator->fails()) {
                    $errors[] = "{$line}-қатар: ".$validator->errors()->first();
                    $skipped++;

                    continue;
                }

                $existing = User::query()->where('username', $username)->first();

                if ($existing !== null && $existing->role !== UserRole::Student) {
                    $errors[] = "{$line}-қатар: «{$username}» логині оқушы емес қолданушыға тиесілі.";
                    $skipped++;

                    continue;
                }

                $schoolClass = SchoolClass::query()->firstOrCreate(
                    ['name' => $className],
                );

                $plainPassword = $this->resolvePasswordForImport($passwordFromFile, $existing !== null);

                if ($existing === null) {
                    $plainPassword ??= $this->generateNumericPassword();
                    User::query()->create([
                        'name' => $name,
                        'username' => $username,
                        'email' => $this->emailFromUsername($username),
                        'role' => UserRole::Student,
                        'class_id' => $schoolClass->id,
                        'email_verified_at' => now(),
                        'password' => $plainPassword,
                    ]);
                    $created++;
                } else {
                    $existing->name = $name;
                    $existing->class_id = $schoolClass->id;
                    $existing->email = $this->emailFromUsername($username);
                    if ($plainPassword !== null) {
                        $existing->password = $plainPassword;
                    }
                    $existing->save();
                    $updated++;
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function exportStudentsWithNewPasswords(): StreamedResponse
    {
        $students = User::query()
            ->where('role', UserRole::Student)
            ->with('schoolClass:id,name')
            ->orderBy('class_id')
            ->orderBy('name')
            ->get();

        /** @var list<array{user: User, password: string, className: string}> $payload */
        $payload = [];
        foreach ($students as $user) {
            $payload[] = [
                'user' => $user,
                'password' => $this->generateNumericPassword(),
                'className' => $user->schoolClass?->name ?? '',
            ];
        }

        DB::transaction(function () use ($payload): void {
            foreach ($payload as $item) {
                $item['user']->password = $item['password'];
                $item['user']->save();
            }
        });

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['name', 'classs', 'username', 'password'], null, 'A1');
        $r = 2;
        foreach ($payload as $item) {
            $sheet->fromArray([
                $item['user']->name,
                $item['className'],
                $item['user']->username,
                $item['password'],
            ], null, 'A'.$r);
            $r++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'students_'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<list<mixed>>
     */
    private function loadMatrix(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestDataRow();
        if ($highestRow < 1) {
            return [];
        }

        /** @var list<list<mixed>> $matrix */
        $matrix = $sheet->rangeToArray('A1:'.$highestColumn.$highestRow, null, true, false, false);

        return array_map(
            static fn ($row): array => array_values(is_array($row) ? $row : []),
            array_values($matrix)
        );
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int> canonical => 0-based column index
     */
    private function resolveColumnMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $idx => $cell) {
            $canonical = $this->canonicalHeader($cell);
            if ($canonical === null) {
                continue;
            }
            if (! isset($map[$canonical])) {
                $map[$canonical] = (int) $idx;
            }
        }

        return $map;
    }

    private function canonicalHeader(mixed $cell): ?string
    {
        $raw = is_string($cell) ? $cell : (string) $cell;
        $s = mb_strtolower(trim($raw, " \t\n\r\0\x0B\u{FEFF}"));

        return match ($s) {
            'name', 'аты', 'фио' => 'name',
            'classs', 'class', 'сынып' => 'class_name',
            'username', 'login', 'логин' => 'username',
            'password', 'пароль', 'құпиясөз' => 'password',
            default => null,
        };
    }

    private function requiredLabel(string $key): string
    {
        return match ($key) {
            'name' => 'name',
            'class_name' => 'classs / class',
            'username' => 'username',
            default => $key,
        };
    }

    /**
     * @param  list<mixed>  $cells
     */
    private function cell(array $cells, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        $v = $cells[$index] ?? '';

        return trim(is_string($v) ? $v : (string) $v);
    }

    private function resolvePasswordForImport(string $fromFile, bool $isUpdate): ?string
    {
        if ($fromFile !== '') {
            return $fromFile;
        }

        if ($isUpdate) {
            return null;
        }

        return $this->generateNumericPassword();
    }

    private function generateNumericPassword(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    private function emailFromUsername(string $username): string
    {
        return Str::lower($username).'@mail.com';
    }
}
