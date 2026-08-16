<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceImportRequest;
use App\Imports\AttendanceImport;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Shuchkin\SimpleXLS as SimpleXLSParser;
use Shuchkin\SimpleXLSX as SimpleXLSXParser;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $dateParam = $request->query('date');
        if ($dateParam === 'yesterday') {
            $reportDate = Carbon::yesterday();
        } elseif ($dateParam) {
            try {
                $reportDate = Carbon::parse($dateParam)->startOfDay();
            } catch (\Exception $e) {
                $reportDate = Carbon::today();
            }
        } else {
            $reportDate = Carbon::today();
        }

        // Include only records with an explicit attendance_date matching the report date.
        $dailyQuery = Attendance::whereDate('attendance_date', $reportDate->toDateString());
        $selectedStatus = strtolower($request->query('status', ''));
        if (! in_array($selectedStatus, ['present', 'absent', 'late', 'all'], true)) {
            $selectedStatus = '';
        }

        $filteredQuery = $dailyQuery;
        if ($selectedStatus === 'present' || $selectedStatus === 'absent' || $selectedStatus === 'late') {
            $filteredQuery = (clone $dailyQuery)->where('status', $selectedStatus);
        }

        $statusRows = $filteredQuery->orderBy('name')->get();

        $total = $dailyQuery->count();
        $present = (clone $dailyQuery)->where('status', 'present')->count();
        $absent = (clone $dailyQuery)->where('status', 'absent')->count();
        $late = (clone $dailyQuery)->where('status', 'late')->count();
        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        $latestUploadedFile = $request->session()->get('uploaded_file');

        if ($latestUploadedFile && ! Storage::disk('local')->exists("attendance_uploads/{$latestUploadedFile}")) {
            $latestUploadedFile = null;
            $request->session()->forget('uploaded_file');
        }

        // Prefer uploaded files that are associated with attendance rows for the selected report date.
        // Use only `attendance_date` here so a file appears on the date its rows declare.
        $filesFromRecords = Attendance::whereDate('attendance_date', $reportDate->toDateString())
            ->whereNotNull('uploaded_file')
            ->pluck('uploaded_file')
            ->map(fn ($f) => basename($f))
            ->unique()
            ->values()
            ->all();

        $uploadedFiles = collect($filesFromRecords);

        if ($latestUploadedFile) {
            $uploadedFiles->push($latestUploadedFile);
        }

        $uploadedFiles = $uploadedFiles
            ->filter(fn ($fname) => Storage::disk('local')->exists("attendance_uploads/{$fname}"))
            ->unique()
            ->sortByDesc(fn ($fname) => Storage::disk('local')->lastModified("attendance_uploads/{$fname}"))
            ->values()
            ->all();

        return View::make('dashboard', compact(
            'total',
            'present',
            'absent',
            'late',
            'attendanceRate',
            'latestUploadedFile',
            'uploadedFiles',
            'selectedStatus',
            'statusRows'
        ))->with('reportDate', $reportDate->toDateString());
    }

    public function home(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $dailyQuery = Attendance::whereDate('attendance_date', $reportDate->toDateString());
        $total = $dailyQuery->count();
        $present = (clone $dailyQuery)->where('status', 'present')->count();
        $absent = (clone $dailyQuery)->where('status', 'absent')->count();
        $late = (clone $dailyQuery)->where('status', 'late')->count();
        $attendanceRate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        $startOfMonth = $reportDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $reportDate->copy()->endOfMonth()->toDateString();

        $periodSummaryLabels = ['Daily', 'Weekly', 'Monthly', 'Quarterly'];
        $periodSummaryData = [
            $total,
            Attendance::whereBetween('attendance_date', [$reportDate->copy()->startOfWeek(), $reportDate->copy()->endOfWeek()])->count(),
            Attendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])->count(),
            Attendance::whereBetween('attendance_date', [$reportDate->copy()->startOfQuarter(), $reportDate->copy()->endOfQuarter()])->count(),
        ];

        $departmentSummaries = $dailyQuery->get()
            ->groupBy(fn ($attendance) => trim((string) $attendance->department) ?: 'Unassigned')
            ->map(function ($items, $department) {
                $present = $items->where('status', 'present')->count();
                $total = $items->count();
                return [
                    'department' => $department,
                    'present' => $present,
                    'absent' => $items->where('status', 'absent')->count(),
                    'late' => $items->where('status', 'late')->count(),
                    'total' => $total,
                    'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('rate')
            ->values();

        $departmentLabels = $departmentSummaries->pluck('department')->all();
        $departmentRates = $departmentSummaries->pluck('rate')->all();
        $topDepartments = $departmentSummaries->take(3);
        $bottomDepartments = $departmentSummaries->sortBy('rate')->take(3)->values();

        return View::make('dashboard-home', compact(
            'total',
            'present',
            'absent',
            'late',
            'attendanceRate',
            'periodSummaryLabels',
            'periodSummaryData',
            'departmentLabels',
            'departmentRates',
            'topDepartments',
            'bottomDepartments'
        ))->with('reportDate', $reportDate->toDateString());
    }


    public function departments(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        // Load attendances for the selected report date only by attendance_date.
        $attendances = Attendance::whereDate('attendance_date', $reportDate->toDateString())->get();

        $normalized = $this->normalizeAttendanceCollection($attendances);
        $grouped = $normalized->groupBy(fn ($a) => $a->department ?? 'Unassigned');

        $selectedDept = $request->query('dept');

        $selectedRows = collect();
        $selectedSummary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => 0];

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $total = $selectedRows->count();
            $present = $selectedRows->where('status', 'present')->count();
            $absent = $selectedRows->where('status', 'absent')->count();
            $late = $selectedRows->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $selectedSummary = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $grouped = $grouped->sortKeys();

        return View::make('attendance.departments', compact('grouped', 'summaries', 'selectedDept', 'selectedRows', 'selectedSummary'))->with('reportDate', $reportDate->toDateString());
    }

    public function workers(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $scope = $request->query('scope', 'daily');
        $period = $request->query('period', $scope);
        $workerName = $request->query('worker');
        $search = trim((string) $request->query('search', ''));

        if ($period === 'weekly') {
            $startOfWeek = $reportDate->copy()->startOfWeek();
            $endOfWeek = $reportDate->copy()->endOfWeek();
            $attendances = Attendance::whereBetween('attendance_date', [$startOfWeek, $endOfWeek])->get();
        } elseif ($period === 'monthly') {
            $startOfMonth = $reportDate->copy()->startOfMonth();
            $endOfMonth = $reportDate->copy()->endOfMonth();
            $attendances = Attendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])->get();
        } elseif ($period === 'quarterly') {
            $startOfQuarter = $reportDate->copy()->startOfQuarter();
            $endOfQuarter = $reportDate->copy()->endOfQuarter();
            $attendances = Attendance::whereBetween('attendance_date', [$startOfQuarter, $endOfQuarter])->get();
        } else {
            $attendances = Attendance::whereDate('attendance_date', $reportDate->toDateString())->get();
        }

        $normalizedAttendances = $this->normalizeAttendanceCollection($attendances);

        if ($search !== '') {
            $searchLower = Str::lower($search);
            $normalizedAttendances = $normalizedAttendances->filter(function ($attendance) use ($searchLower) {
                return Str::contains(Str::lower((string) $attendance->name), $searchLower);
            });
        }

        $workers = $normalizedAttendances->groupBy(fn ($attendance) => trim((string) $attendance->name) ?: 'Unassigned')
            ->map(function ($items) {
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

                return [
                    'name' => $items->first()->name ?? 'Unassigned',
                    'total' => $total,
                    'present' => $present,
                    'rate' => $rate,
                ];
            })
            ->values()
            ->all();

        $selectedWorkerColor = null;
        if ($scope === 'personal' && $workerName) {
            $selectedWorker = collect($workers)->first(fn ($worker) => strcasecmp(trim((string) $worker['name']), trim((string) $workerName)) === 0);
            if ($selectedWorker) {
                $selectedWorkerColor = $this->getWorkerBadgeColors((float) $selectedWorker['rate']);
            }
        }

        $personalRows = collect();
        $personalSummary = [
            'total' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'rate' => 0,
        ];

        if ($scope === 'personal' && $workerName) {
            $personalRows = $normalizedAttendances->filter(function ($attendance) use ($workerName) {
                return strcasecmp(trim((string) $attendance->name), trim((string) $workerName)) === 0;
            })->values();

            $total = $personalRows->count();
            $present = $personalRows->where('status', 'present')->count();
            $absent = $personalRows->where('status', 'absent')->count();
            $late = $personalRows->where('status', 'late')->count();
            $personalSummary = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }

        return View::make('attendance.workers', compact('reportDate', 'workers', 'scope', 'workerName', 'period', 'personalRows', 'personalSummary', 'selectedWorkerColor'));
    }

    private function getWorkerBadgeColors(float $rate): array
    {
        if ($rate < 50) {
            return [
                'bgColor' => '#fee2e2',
                'borderColor' => '#fca5a5',
                'textColor' => '#991b1b',
            ];
        } elseif ($rate < 80) {
            return [
                'bgColor' => '#fef3c7',
                'borderColor' => '#fbbf24',
                'textColor' => '#92400e',
            ];
        }

        return [
            'bgColor' => '#d1fae5',
            'borderColor' => '#34d399',
            'textColor' => '#065f46',
        ];
    }

    public function weekly(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfWeek = $reportDate->copy()->startOfWeek();
        $endOfWeek = $reportDate->copy()->endOfWeek();

        $weeklyAttendances = Attendance::whereBetween('attendance_date', [$startOfWeek, $endOfWeek])
            ->orderBy('attendance_date')
            ->get();
        $normalizedAttendances = $this->normalizeAttendanceCollection($weeklyAttendances);
        // prepare department grouping for week
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $selectedDept = $request->query('dept');
        $selectedRows = collect();

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $selectedSummary = [
                'total' => $selectedRows->count(),
                'present' => $selectedRows->where('status', 'present')->count(),
                'absent' => $selectedRows->where('status', 'absent')->count(),
                'late' => $selectedRows->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;

            $dailySummaries = $selectedRows
                ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
                ->map(function ($items, $date) {
                    $total = $items->count();
                    $present = $items->where('status', 'present')->count();
                    $absent = $items->where('status', 'absent')->count();
                    $late = $items->where('status', 'late')->count();

                    return [
                        'date' => $date,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    ];
                });
        } else {
            $selectedSummary = [
                'total' => $normalizedAttendances->count(),
                'present' => $normalizedAttendances->where('status', 'present')->count(),
                'absent' => $normalizedAttendances->where('status', 'absent')->count(),
                'late' => $normalizedAttendances->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;

            $dailySummaries = $normalizedAttendances
                ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
                ->map(function ($items, $date) {
                    $total = $items->count();
                    $present = $items->where('status', 'present')->count();
                    $absent = $items->where('status', 'absent')->count();
                    $late = $items->where('status', 'late')->count();

                    return [
                        'date' => $date,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    ];
                });
        }

        $weeklyLabels = $dailySummaries->keys()->all();
        $weeklyData = $dailySummaries->map(fn ($summary) => $summary['rate'])->values()->all();

        return View::make('attendance.weekly', compact(
            'reportDate',
            'startOfWeek',
            'endOfWeek',
            'dailySummaries',
            'weeklyLabels',
            'weeklyData',
            'selectedSummary',
            'grouped',
            'summaries',
            'selectedDept',
            'selectedRows'
        ));
    }

    public function weeklyDepartments(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfWeek = $reportDate->copy()->startOfWeek();
        $endOfWeek = $reportDate->copy()->endOfWeek();

        $weeklyAttendances = Attendance::whereBetween('attendance_date', [$startOfWeek, $endOfWeek])
            ->orderBy('attendance_date')
            ->get();

        $normalizedAttendances = $this->normalizeAttendanceCollection($weeklyAttendances);
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        // summaries by department (overall for the week)
        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        // daily summaries per department for the week
        $deptDailySummaries = [];
        foreach ($grouped as $dept => $items) {
            $daily = $items->groupBy(fn ($a) => $a->attendance_date->toDateString())
                ->map(function ($rows, $date) {
                    $total = $rows->count();
                    $present = $rows->where('status', 'present')->count();
                    $absent = $rows->where('status', 'absent')->count();
                    $late = $rows->where('status', 'late')->count();
                    return [
                        'date' => $date,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    ];
                })->sortKeys();

            $deptDailySummaries[$dept] = $daily->values()->all();
        }

        $selectedDept = $request->query('dept');
        $selectedDailySummaries = [];
        $selectedSummary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => 0];

        if ($selectedDept && isset($deptDailySummaries[$selectedDept])) {
            $selectedDailySummaries = $deptDailySummaries[$selectedDept];
            $total = $grouped->get($selectedDept)->count();
            $present = $grouped->get($selectedDept)->where('status', 'present')->count();
            $absent = $grouped->get($selectedDept)->where('status', 'absent')->count();
            $late = $grouped->get($selectedDept)->where('status', 'late')->count();
            $selectedSummary = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }

        return View::make('attendance.departments', compact('grouped', 'summaries', 'selectedDept', 'selectedDailySummaries', 'selectedSummary', 'deptDailySummaries'))->with('reportDate', $reportDate->toDateString());
    }

    public function monthly(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfMonth = $reportDate->copy()->startOfMonth();
        $endOfMonth = $reportDate->copy()->endOfMonth();

        $monthlyAttendances = Attendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->orderBy('attendance_date')
            ->get();

        $normalizedAttendances = $this->normalizeAttendanceCollection($monthlyAttendances);
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $selectedDept = $request->query('dept');
        $selectedRows = collect();
        $selectedSummary = [
            'total' => $normalizedAttendances->count(),
            'present' => $normalizedAttendances->where('status', 'present')->count(),
            'absent' => $normalizedAttendances->where('status', 'absent')->count(),
            'late' => $normalizedAttendances->where('status', 'late')->count(),
        ];
        $selectedSummary['rate'] = $selectedSummary['total'] > 0
            ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
            : 0;

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $selectedSummary = [
                'total' => $selectedRows->count(),
                'present' => $selectedRows->where('status', 'present')->count(),
                'absent' => $selectedRows->where('status', 'absent')->count(),
                'late' => $selectedRows->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;
        }

        $dailySummaries = $normalizedAttendances
            ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
            ->map(function ($items, $date) {
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                $absent = $items->where('status', 'absent')->count();
                $late = $items->where('status', 'late')->count();

                return [
                    'date' => $date,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late,
                    'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            })
            ->sortKeys();

        // 4 weekly buckets for the month (days 1-7, 8-14, 15-21, 22-end)
        $weeklyBuckets = [
            1 => ['label' => 'Week 1', 'start' => 1, 'end' => 7],
            2 => ['label' => 'Week 2', 'start' => 8, 'end' => 14],
            3 => ['label' => 'Week 3', 'start' => 15, 'end' => 21],
            4 => ['label' => 'Week 4', 'start' => 22, 'end' => $endOfMonth->day],
        ];

        $monthlyLabels = [];
        $monthlyData = [];
        $weeklyBucketStartDates = [];

        foreach ($weeklyBuckets as $weekNum => $bucket) {
            $bucketRows = $dailySummaries->filter(function ($summary) use ($bucket) {
                $day = Carbon::parse($summary['date'])->day;
                return $day >= $bucket['start'] && $day <= $bucket['end'];
            });

            $bucketTotal = $bucketRows->sum(fn ($summary) => $summary['total']);
            $bucketPresent = $bucketRows->sum(fn ($summary) => $summary['present']);

            $monthlyLabels[] = $bucket['label'];
            $monthlyData[] = $bucketTotal > 0 ? round(($bucketPresent / $bucketTotal) * 100, 1) : 0;
            $weeklyBucketStartDates[] = $reportDate->copy()->day($bucket['start'])->toDateString();
        }

        return View::make('attendance.monthly', compact(
            'reportDate',
            'startOfMonth',
            'endOfMonth',
            'dailySummaries',
            'monthlyLabels',
            'monthlyData',
            'weeklyBucketStartDates',
            'selectedSummary',
            'grouped',
            'summaries',
            'selectedDept',
            'selectedRows'
        ));
    }

    public function monthlyDepartments(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfMonth = $reportDate->copy()->startOfMonth();
        $endOfMonth = $reportDate->copy()->endOfMonth();

        $monthlyAttendances = Attendance::whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->orderBy('attendance_date')
            ->get();

        $normalizedAttendances = $this->normalizeAttendanceCollection($monthlyAttendances);
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $selectedDept = $request->query('dept');
        $selectedRows = collect();
        $selectedSummary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => 0];
        $deptDailySummaries = [];

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $selectedSummary = [
                'total' => $selectedRows->count(),
                'present' => $selectedRows->where('status', 'present')->count(),
                'absent' => $selectedRows->where('status', 'absent')->count(),
                'late' => $selectedRows->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;

            $deptDailySummaries[$selectedDept] = $selectedRows
                ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
                ->map(function ($items, $date) {
                    $total = $items->count();
                    $present = $items->where('status', 'present')->count();
                    $absent = $items->where('status', 'absent')->count();
                    $late = $items->where('status', 'late')->count();

                    return [
                        'date' => $date,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    ];
                })
                ->sortKeys()
                ->values()
                ->all();
        }

        return View::make('attendance.departments', compact('grouped', 'summaries', 'selectedDept', 'selectedRows', 'selectedSummary', 'deptDailySummaries'))->with('reportDate', $reportDate->toDateString());
    }

    public function quarterly(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfQuarter = $reportDate->copy()->startOfQuarter();
        $endOfQuarter = $reportDate->copy()->endOfQuarter();

        $quarterlyAttendances = Attendance::whereBetween('attendance_date', [$startOfQuarter, $endOfQuarter])
            ->orderBy('attendance_date')
            ->get();

        $normalizedAttendances = $this->normalizeAttendanceCollection($quarterlyAttendances);
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $selectedDept = $request->query('dept');
        $selectedRows = collect();
        $selectedSummary = [
            'total' => $normalizedAttendances->count(),
            'present' => $normalizedAttendances->where('status', 'present')->count(),
            'absent' => $normalizedAttendances->where('status', 'absent')->count(),
            'late' => $normalizedAttendances->where('status', 'late')->count(),
        ];
        $selectedSummary['rate'] = $selectedSummary['total'] > 0
            ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
            : 0;

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $selectedSummary = [
                'total' => $selectedRows->count(),
                'present' => $selectedRows->where('status', 'present')->count(),
                'absent' => $selectedRows->where('status', 'absent')->count(),
                'late' => $selectedRows->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;
        }

        $dailySummaries = $normalizedAttendances
            ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
            ->map(function ($items, $date) {
                $total = $items->count();
                $present = $items->where('status', 'present')->count();
                $absent = $items->where('status', 'absent')->count();
                $late = $items->where('status', 'late')->count();

                return [
                    'date' => $date,
                    'total' => $total,
                    'present' => $present,
                    'absent' => $absent,
                    'late' => $late,
                    'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                ];
            })
            ->sortKeys();

        // Monthly buckets within the quarter
        $monthlyLabels = [];
        $monthlyData = [];
        $monthlyStartDates = [];

        for ($i = 0; $i < 3; $i++) {
            $monthStart = $startOfQuarter->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $monthRows = $dailySummaries->filter(function ($summary) use ($monthStart, $monthEnd) {
                $date = Carbon::parse($summary['date']);
                return $date->between($monthStart, $monthEnd);
            });

            $monthTotal = $monthRows->sum(fn ($summary) => $summary['total']);
            $monthPresent = $monthRows->sum(fn ($summary) => $summary['present']);
            
            $monthlyLabels[] = $monthStart->format('M');
            $monthlyData[] = $monthTotal > 0 ? round(($monthPresent / $monthTotal) * 100, 1) : 0;
            $monthlyStartDates[] = $monthStart->toDateString();
        }

        return View::make('attendance.quarterly', compact(
            'reportDate',
            'startOfQuarter',
            'endOfQuarter',
            'dailySummaries',
            'monthlyLabels',
            'monthlyData',
            'monthlyStartDates',
            'selectedSummary',
            'grouped',
            'summaries',
            'selectedDept',
            'selectedRows'
        ));
    }

    public function quarterlyDepartments(Request $request)
    {
        $reportDate = $this->resolveReportDate($request);

        $startOfQuarter = $reportDate->copy()->startOfQuarter();
        $endOfQuarter = $reportDate->copy()->endOfQuarter();

        $quarterlyAttendances = Attendance::whereBetween('attendance_date', [$startOfQuarter, $endOfQuarter])
            ->orderBy('attendance_date')
            ->get();

        $normalizedAttendances = $this->normalizeAttendanceCollection($quarterlyAttendances);
        $grouped = $normalizedAttendances->groupBy(fn ($a) => $a->department ?? 'Unassigned')->sortKeys();

        $summaries = [];
        foreach ($grouped as $dept => $items) {
            $total = $items->count();
            $present = $items->where('status', 'present')->count();
            $absent = $items->where('status', 'absent')->count();
            $late = $items->where('status', 'late')->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $summaries[$dept] = [
                'total' => $total,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'rate' => $rate,
            ];
        }

        $selectedDept = $request->query('dept');
        $selectedRows = collect();
        $selectedSummary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'rate' => 0];
        $deptDailySummaries = [];

        if ($selectedDept && $grouped->has($selectedDept)) {
            $selectedRows = $grouped->get($selectedDept);
            $selectedSummary = [
                'total' => $selectedRows->count(),
                'present' => $selectedRows->where('status', 'present')->count(),
                'absent' => $selectedRows->where('status', 'absent')->count(),
                'late' => $selectedRows->where('status', 'late')->count(),
            ];
            $selectedSummary['rate'] = $selectedSummary['total'] > 0
                ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1)
                : 0;

            $deptDailySummaries[$selectedDept] = $selectedRows
                ->groupBy(fn ($attendance) => $attendance->attendance_date->toDateString())
                ->map(function ($items, $date) {
                    $total = $items->count();
                    $present = $items->where('status', 'present')->count();
                    $absent = $items->where('status', 'absent')->count();
                    $late = $items->where('status', 'late')->count();

                    return [
                        'date' => $date,
                        'total' => $total,
                        'present' => $present,
                        'absent' => $absent,
                        'late' => $late,
                        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                    ];
                })
                ->sortKeys()
                ->values()
                ->all();
        }

        return View::make('attendance.quarterly-departments', compact('grouped', 'summaries', 'selectedDept', 'selectedRows', 'selectedSummary', 'deptDailySummaries'))->with('reportDate', $reportDate->toDateString());
    }

    private function resolveReportDate(Request $request): Carbon
    {
        $dateParam = $request->query('date');
        if ($dateParam === 'yesterday') {
            return Carbon::yesterday();
        } elseif ($dateParam) {
            try {
                return Carbon::parse($dateParam)->startOfDay();
            } catch (\Exception $e) {
                return Carbon::today();
            }
        }

        $latestDate = Attendance::max('attendance_date');
        return $latestDate ? Carbon::parse($latestDate)->startOfDay() : Carbon::today();
    }

    private function normalizeAttendanceCollection($attendances)
    {
        return $attendances->map(function ($a) {
            $dept = $a->department;

            if (! $dept || trim((string) $dept) === '') {
                $a->department = 'Unassigned';
                return $a;
            }

            $clean = preg_replace('/\s+/', ' ', trim((string) $dept));
            $a->department = Str::title(Str::lower($clean));
            return $a;
        });
    }

    public function exportDepartments(Request $request)
    {
        // Export all attendances; filtering removed.
        $attendances = $this->normalizeAttendanceCollection(Attendance::orderBy('department')->orderBy('name')->get());

        $filename = 'department-export-' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['employee_id', 'name', 'department', 'position', 'attendance_date', 'check_in', 'check_out', 'status', 'work_minutes', 'late_minutes', 'absent_minutes', 'leave_minutes', 'uploaded_file'];

        $callback = function () use ($attendances, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($attendances as $attendance) {
                fputcsv($handle, array_map(fn ($column) => $attendance->{$column}, $columns));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(AttendanceImportRequest $request)
    {
        $file = $request->file('attendance_file');
        $originalName = $file->getClientOriginalName();
        $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $storedPath = $file->storeAs('attendance_uploads', $safeName);

        $import = new AttendanceImport();
        $import->setUploadedFile($safeName);
        $import->importFile($file);

        $request->session()->put('uploaded_file', $safeName);

        // Try to redirect back to the date of the imported rows (if any),
        // otherwise preserve any `date` query param from the request.
        $firstDate = Attendance::where('uploaded_file', $safeName)
            ->whereNotNull('attendance_date')
            ->orderBy('attendance_date')
            ->value('attendance_date');

        $redirectParams = [];
        if ($request->query('date')) {
            $redirectParams['date'] = $request->query('date');
        } elseif ($firstDate) {
            $redirectParams['date'] = Carbon::parse($firstDate)->toDateString();
        }

        return Redirect::route('attendance.daily', $redirectParams)
            ->with('success', "Imported {$import->imported} attendance records.");
    }

    public function viewUploadedFile(Request $request, string $filename)
    {
        $filename = basename($filename);
        $path = "attendance_uploads/{$filename}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $headers = [];
        $rows = [];
        $message = null;
        $fullPath = Storage::disk('local')->path($path);

        if ($extension === 'xlsx') {
            $xlsx = SimpleXLSXParser::parse($fullPath);

            if ($xlsx === false) {
                $message = 'Unable to parse the spreadsheet preview. Please download the file to view it locally.';
            } else {
                $sheetRows = $xlsx->rows();

                if (! empty($sheetRows)) {
                    $headers = array_map(fn ($value) => (string) $value, $sheetRows[0]);
                    $rows = array_slice(array_map(fn ($row) => array_map(fn ($value) => (string) $value, $row), array_slice($sheetRows, 1)), 0, 50);
                }
            }
        } elseif ($extension === 'xls') {
            $xls = SimpleXLSParser::parse($fullPath);

            if ($xls === false) {
                $message = 'Unable to parse the spreadsheet preview. Please download the file to view it locally.';
            } else {
                $sheetRows = $xls->rows();

                if (! empty($sheetRows)) {
                    $headers = array_map(fn ($value) => (string) $value, $sheetRows[0]);
                    $rows = array_slice(array_map(fn ($row) => array_map(fn ($value) => (string) $value, $row), array_slice($sheetRows, 1)), 0, 50);
                }
            }
        } elseif ($extension === 'csv') {
            if (($handle = fopen($fullPath, 'r')) !== false) {
                $rawHeaders = fgetcsv($handle);

                if (is_array($rawHeaders)) {
                    $headers = array_map(fn ($value) => trim((string) $value), $rawHeaders);

                    while (count($rows) < 50 && ($row = fgetcsv($handle)) !== false) {
                        $rows[] = array_map(fn ($value) => trim((string) $value), $row);
                    }
                } else {
                    $message = 'Unable to parse the CSV preview. Please download the file to view it locally.';
                }

                fclose($handle);
            } else {
                $message = 'Unable to open the CSV file for preview.';
            }
        } else {
            $message = 'Preview is unavailable for this file type. Please download it to view the contents.';
        }

        return View::make('attendance.file-view', compact('filename', 'extension', 'headers', 'rows', 'message'));
    }

    public function downloadUploadedFile(Request $request, string $filename)
    {
        $filename = basename($filename);
        $path = "attendance_uploads/{$filename}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }

    public function deleteUploadedFile(Request $request, string $filename)
    {
        $filename = basename($filename);
        $path = "attendance_uploads/{$filename}";

        // Determine associated attendance_date for this file (if any) before deleting records.
        $associatedDate = Attendance::where('uploaded_file', $filename)
            ->whereNotNull('attendance_date')
            ->orderBy('attendance_date')
            ->value('attendance_date');

        $fileDeleted = false;
        if (Storage::disk('local')->exists($path)) {
            $fileDeleted = Storage::disk('local')->delete($path);
        }

        $recordsDeleted = Attendance::where('uploaded_file', $filename)->delete();

        $request->session()->forget('uploaded_file');

        // Determine redirect date: prefer request `date` query, then associatedDate.
        $redirectParams = [];
        if ($request->query('date')) {
            $redirectParams['date'] = $request->query('date');
        } elseif ($associatedDate) {
            $redirectParams['date'] = Carbon::parse($associatedDate)->toDateString();
        }

        if ($fileDeleted || $recordsDeleted > 0) {
            return Redirect::route('attendance.daily', $redirectParams)->with('success', "Deleted uploaded file and its associated records: {$filename}");
        }

        return Redirect::route('attendance.daily', $redirectParams)->with('success', "No uploaded file or attendance records were found for {$filename}.");
    }
}
