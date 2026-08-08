<?php

namespace App\Imports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Shuchkin\SimpleXLS as SimpleXLSParser;
use Shuchkin\SimpleXLSX as SimpleXLSXParser;

class AttendanceImport
{
    public int $imported = 0;
    protected ?string $uploadedFile = null;

    public function setUploadedFile(string $uploadedFile): void
    {
        $this->uploadedFile = $uploadedFile;
    }

    public function importFile(UploadedFile $file): void
    {
        $extension = strtolower($file->extension() ?? $file->getClientOriginalExtension());

        $rows = match ($extension) {
            'xlsx' => $this->parseXlsx($file),
            'xls' => $this->parseXls($file),
            'csv' => $this->parseCsv($file),
            default => [],
        };

        foreach ($rows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $employeeId = $this->findValue($row, ['person_id', 'employee_id', 'employee id', 'id', 'staff_id', 'staff id', 'staff no', 'employee no']);
            $name = $this->findValue($row, ['name', 'full_name', 'full name', 'employee_name', 'employee name']);
            $department = $this->findValue($row, ['department', 'dept']);
            $position = $this->findValue($row, ['position', 'job_title', 'role']);
            $gender = $this->findValue($row, ['gender', 'sex']);
            $dateValue = $this->findValue($row, ['date', 'attendance_date', 'attendance date', 'day']);
            $week = $this->findValue($row, ['week']);
            $timetable = $this->findValue($row, ['timetable', 'time table']);
            $checkInValue = $this->findValue($row, ['check_in', 'check-in', 'check in']);
            $checkOutValue = $this->findValue($row, ['check_out', 'check-out', 'check out']);
            $workMinutes = $this->findValue($row, ['work', 'work_minutes', 'work minutes']);
            $otMinutes = $this->findValue($row, ['ot', 'overtime', 'ot_minutes', 'ot minutes']);
            $attendedMinutes = $this->findValue($row, ['attended', 'attended_minutes', 'attended minutes']);
            $lateMinutes = $this->findValue($row, ['late', 'late_minutes', 'late minutes']);
            $earlyMinutes = $this->findValue($row, ['early', 'early_minutes', 'early minutes']);
            $absentMinutes = $this->findValue($row, ['absent', 'absent_minutes', 'absent minutes']);
            $leaveMinutes = $this->findValue($row, ['leave', 'leave_minutes', 'leave minutes']);
            $statusValue = $this->findValue($row, ['status', 'attendance_status', 'attendance status', 'remark', 'remarks']);
            $sourceValue = $this->findValue($row, ['source', 'machine', 'device', 'terminal']);
            $records = $this->findValue($row, ['records', 'record']);

            $attendanceDate = $this->parseDate($dateValue);
            $attendanceTime = $this->parseTime($checkInValue ?? $checkOutValue ?? $dateValue);
            $checkIn = $this->parseTime($checkInValue);
            $checkOut = $this->parseTime($checkOutValue);

            if (! $attendanceDate) {
                continue;
            }

            Attendance::create([
                'employee_id' => $employeeId ?: 'unknown',
                'name' => $name ?: 'Unknown',
                'department' => $department,
                'position' => $position,
                'gender' => $gender,
                'attendance_date' => $attendanceDate,
                'week' => $week,
                'timetable' => $timetable,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'work_minutes' => $this->parseInteger($workMinutes),
                'ot_minutes' => $this->parseInteger($otMinutes),
                'attended_minutes' => $this->parseInteger($attendedMinutes),
                'late_minutes' => $this->parseInteger($lateMinutes),
                'early_minutes' => $this->parseInteger($earlyMinutes),
                'absent_minutes' => $this->parseInteger($absentMinutes),
                'leave_minutes' => $this->parseInteger($leaveMinutes),
                'attendance_time' => $attendanceTime,
                'status' => $this->normalizeStatus($statusValue, $checkInValue),
                'source' => $sourceValue,
                'records' => $records,
                'uploaded_file' => $this->uploadedFile,
            ]);

            $this->imported++;
        }
    }

    protected function parseXlsx(UploadedFile $file): array
    {
        $xlsx = SimpleXLSXParser::parse($file->getRealPath());

        if (! $xlsx) {
            return [];
        }

        $sheetRows = $xlsx->rows();
        $rows = [];
        $headers = null;

        foreach ($sheetRows as $row) {
            if ($headers === null) {
                $normalizedRow = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);

                if ($this->isHeaderRow($normalizedRow)) {
                    $headers = $normalizedRow;
                }

                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $this->buildRow(array_pad($row, count($headers), null), $headers);
        }

        return $rows;
    }

    protected function parseXls(UploadedFile $file): array
    {
        $xls = SimpleXLSParser::parse($file->getRealPath());

        if (! $xls) {
            return [];
        }

        $sheetRows = $xls->rows();
        $rows = [];
        $headers = null;

        foreach ($sheetRows as $row) {
            if ($headers === null) {
                $normalizedRow = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);

                if ($this->isHeaderRow($normalizedRow)) {
                    $headers = $normalizedRow;
                }

                continue;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = $this->buildRow(array_pad($row, count($headers), null), $headers);
        }

        return $rows;
    }

    protected function parseCsv(UploadedFile $file): array
    {
        $rows = [];

        if (($handle = fopen($file->getRealPath(), 'r')) === false) {
            return [];
        }

        $headers = null;

        while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
            $cleanRow = array_map(fn ($value) => trim((string) $value), $row);

            if ($headers === null) {
                $normalizedRow = array_map(fn ($value) => $this->normalizeHeader((string) $value), $cleanRow);

                if ($this->isHeaderRow($normalizedRow)) {
                    $headers = $normalizedRow;
                }

                continue;
            }

            if ($this->isEmptyRow($cleanRow)) {
                continue;
            }

            $rows[] = $this->buildRow(array_pad($cleanRow, count($headers), null), $headers);
        }

        fclose($handle);

        return $rows;
    }

    protected function isHeaderRow(array $row): bool
    {
        $required = ['person_id', 'name', 'date'];

        foreach ($required as $column) {
            if (! in_array($column, $row, true)) {
                return false;
            }
        }

        return true;
    }

    protected function buildRow(array $row, array $headers): array
    {
        return array_combine($headers, array_map(fn ($value) => trim((string) $value), $row));
    }

    protected function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    protected function findValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeHeader($key);

            if (array_key_exists($normalizedKey, $row) && trim((string) $row[$normalizedKey]) !== '') {
                return trim((string) $row[$normalizedKey]);
            }
        }

        return null;
    }

    protected function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    protected function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function parseTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            $carbon = Carbon::parse($value);

            if ($carbon->format('H:i:s') === '00:00:00' && preg_match('/\d{1,2}:\d{2}/', $value) === 0) {
                return null;
            }

            return $carbon->toTimeString();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    protected function parseInteger(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/[^0-9\-]+/', '', $value);

        return $normalized === '' ? null : (int) $normalized;
    }

    protected function normalizeStatus(?string $value, ?string $timeValue): string
    {
        $status = strtolower(trim((string) $value));

        if ($status === '') {
            return $timeValue ? 'present' : 'absent';
        }

        if ($status === 'a' || str_contains($status, 'abs')) {
            return 'absent';
        }

        if ($status === 'w' || $status === 'p' || str_contains($status, 'work') || str_contains($status, 'present') || str_contains($status, 'w') ) {
            return 'present';
        }

        if (str_contains($status, 'late') || $status === 'l') {
            return 'late';
        }

        return 'present';
    }
}
