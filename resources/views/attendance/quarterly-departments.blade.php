<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Quartely Department Report') }}

    <div class="department-page-shell py-12 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <style>
                    @media print {
                    .no-print {
                        display: none !important;
                    }

                    body {
                        background: white !important;
                        margin: 0 !important;
                    }

                    .department-page-shell {
                        background: white !important;
                        min-height: auto !important;
                        padding: 0 !important;
                    }

                    .department-page-shell > .max-w-7xl > :not(.department-overlay) {
                        display: none !important;
                    }

                    .department-overlay {
                        position: static !important;
                        inset: auto !important;
                        display: block !important;
                        padding: 0 !important;
                        background: white !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        overflow: visible !important;
                    }

                    .department-overlay .department-modal-panel {
                        max-height: none !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        overflow: visible !important;
                        box-shadow: none !important;
                        border: none !important;
                        border-radius: 0 !important;
                    }

                    .department-overlay .department-modal-panel .print-section {
                        break-inside: avoid;
                        page-break-inside: avoid;
                    }

                    /* keep the summary buttons and chart in a single horizontal row when printing */
                    .department-overlay .department-modal-panel .preserve-print-row {
                        display: flex !important;
                        flex-direction: row !important;
                        align-items: flex-start !important;
                        justify-content: center !important;
                        gap: 1.5rem !important;
                        padding-left: 1rem !important;
                    }

                    .department-overlay .department-modal-panel .flex-shrink-0 {
                        margin-right: 1rem !important;
                    }

                    .department-overlay .department-modal-panel canvas {
                        max-width: 100% !important;
                        max-height: 100% !important;
                    }

                    .print-surface {
                        background: white !important;
                        box-shadow: none !important;
                    }

                    .print-card {
                        break-inside: avoid;
                        page-break-inside: avoid;
                    }
                }
            </style>

            <div class="mb-6">
                <div class="rounded-2xl bg-slate-100 p-4 shadow-sm print-surface">
                    <nav class="flex flex-wrap items-center justify-between gap-4 no-print">
                        <a href="{{ route('dashboard') }}{{ request()->query('date') ? '?date='.e(request()->query('date')) : '' }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-teal-500 text-white shadow-sm hover:bg-teal-600" style="background-color:#14b8a6;color:#ffffff;border-color:#14b8a6;">Daily report</a>
                        <a href="{{ route('attendance.weekly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300 opacity-80">Weekly report</a>
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-violet-600 bg-violet-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-violet-700 inline-flex items-center justify-center opacity-50 filter blur-sm" style="background-color:#7c3aed;color:#ffffff;border-color:#7c3aed;">Monthly report</a>
                        <a href="{{ route('attendance.quarterly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-amber-600 bg-amber-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-amber-700 inline-flex items-center justify-center" style="background-color:#f59e0b;color:#ffffff;border-color:#f59e0b;">Quartely report</a>
                    </nav>
                    @php
                        $baseDate = request()->query('date') ?? ($reportDate ?? now()->toDateString());
                    @endphp
                    <div class="mt-4 flex flex-wrap gap-3" id="reportTabs">
                        <a href="{{ route('attendance.quarterly', ['date' => $baseDate]) }}" id="overallTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(!request()->query('dept')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Overall</a>
                        <a href="{{ route('attendance.quarterly.departments', ['date' => $baseDate]) }}" id="departmentTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5">Departments</a>
                        <a href="{{ route('attendance.workers', ['date' => $baseDate, 'scope' => 'quarterly']) }}" id="workersTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center border border-slate-300 bg-slate-200 font-semibold text-slate-900 shadow-sm hover:bg-slate-300">Workers</a>
                    </div>

                </div>
            </div>

            {{-- Filters removed: show departments summary only --}}

            @if(empty($grouped) || count($grouped) === 0)
                <div class="rounded-lg bg-white p-6 shadow-sm">No attendance records found.</div>
            @else
                <div class="mb-6 overflow-x-auto px-6 print-card">
                    <div class="mb-3 rounded-lg bg-slate-200 px-4 py-2.5 flex items-center justify-between gap-3 text-sm font-semibold text-slate-700">
                        <div class="w-1/3 text-left">Department</div>
                        <div class="w-1/3 text-center">Attendance Percentage</div>
                        <div class="w-1/3 text-right">Recorded Users</div>
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                    @foreach($grouped->keys() as $department)
                        @php
                            $meta = $summaries[$department] ?? ['total' => 0, 'rate' => 0];
                            $isSelected = ($selectedDept ?? '') === $department;
                        @endphp
                        <a href="{{ request()->fullUrlWithQuery(['dept' => $department]) }}" class="rounded-lg p-4 text-sm border flex items-center justify-between gap-3 {{ $isSelected ? 'bg-gray-200 text-slate-900 border-gray-300 font-semibold shadow-md' : 'bg-white border-slate-200 hover:bg-slate-50' }}">
                            <div class="w-1/3 font-semibold uppercase text-left {{ $isSelected ? 'text-slate-900' : 'text-slate-800' }}">{{ $department }}</div>
                            <div class="w-1/3 flex items-center justify-center gap-2 text-center">
                                <div class="text-2xl font-semibold {{ $isSelected ? 'text-slate-900' : '' }}">{{ $meta['rate'] }}%</div>
                                <div class="text-xs {{ $isSelected ? 'text-slate-600' : 'text-slate-500' }}">Attendance</div>
                            </div>
                            <div class="w-1/3 text-right text-xs {{ $isSelected ? 'text-slate-600' : 'text-slate-500' }}">{{ $meta['total'] }} rec</div>
                        </a>
                    @endforeach
                    </div>
                </div>

                @if(!empty($selectedDept))
                    <div class="department-overlay fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-green-300/45 p-4">
                        <div class="department-modal-panel max-h-[90vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-300">
                            <div class="print-section border-b border-slate-300 px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="min-w-0 flex-1 text-left">
                                        <h3 class="text-xl font-semibold text-slate-900">{{ $selectedDept }}</h3>
                                        <p class="mt-1 text-sm font-medium text-slate-700">Quartely Department Report</p>
                                    </div>

                                    <div class="flex flex-shrink-0 items-center justify-end gap-2 no-print">
                                        <button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">Download PDF</button>
                                        <a href="{{ request()->fullUrlWithQuery(['dept' => null]) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Close</a>
                                    </div>
                                </div>
                            </div>

                            <div class="print-section p-6">
                                <div class="flex flex-col gap-6">
                                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-center pl-8 w-full preserve-print-row">
                                        <div class="flex-shrink-0 flex flex-col items-center space-y-6 mr-6">
                                            <div class="w-28 rounded-2xl p-3 bg-slate-50 text-slate-900 flex flex-col items-center shadow-sm">
                                                <div class="text-xs uppercase tracking-wide">Records</div>
                                                <div class="text-2xl font-semibold">{{ $selectedSummary['total'] }}</div>
                                            </div>

                                            <div class="w-28 rounded-2xl p-3 bg-emerald-50 text-emerald-800 flex flex-col items-center shadow-sm">
                                                <div class="text-xs uppercase tracking-wide">Present</div>
                                                <div class="text-2xl font-semibold">{{ $selectedSummary['present'] }}</div>
                                                <div class="text-xs text-emerald-600">{{ ($selectedSummary['total'] ?? 0) ? round(($selectedSummary['present'] / $selectedSummary['total']) * 100, 1) : 0 }}%</div>
                                            </div>

                                            <div class="w-28 rounded-2xl p-3 bg-red-50 text-red-800 flex flex-col items-center shadow-sm">
                                                <div class="text-xs uppercase tracking-wide">Absent</div>
                                                <div class="text-2xl font-semibold">{{ $selectedSummary['absent'] }}</div>
                                                <div class="text-xs text-red-600">{{ ($selectedSummary['total'] ?? 0) ? round(($selectedSummary['absent'] / $selectedSummary['total']) * 100, 1) : 0 }}%</div>
                                            </div>

                                            <div class="w-28 rounded-2xl p-3 bg-amber-50 text-amber-800 flex flex-col items-center shadow-sm">
                                                <div class="text-xs uppercase tracking-wide">Late</div>
                                                <div class="text-2xl font-semibold">{{ $selectedSummary['late'] }}</div>
                                                <div class="text-xs text-amber-600">{{ ($selectedSummary['total'] ?? 0) ? round(($selectedSummary['late'] / $selectedSummary['total']) * 100, 1) : 0 }}%</div>
                                            </div>
                                        </div>

                                        <div class="flex-1 flex items-center justify-center">
                                            <div class="w-40 h-40 mx-auto">
                                                <canvas id="departmentStatusChart" class="w-full h-full"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($deptDailySummaries) && isset($deptDailySummaries[$selectedDept]))
                                    <div class="print-section mt-6 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-300">
                                        <h3 class="text-lg font-semibold mb-3 text-slate-900">Daily breakdown for {{ $selectedDept }}</h3>
                                        <div class="mt-4 overflow-x-auto">
                                            <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-800">
                                                <thead>
                                                    <tr class="bg-gray-50">
                                                        <th class="px-3 py-2 text-left font-medium">Date</th>
                                                        <th class="px-3 py-2 text-left font-medium">Total</th>
                                                        <th class="px-3 py-2 text-left font-medium">Present</th>
                                                        <th class="px-3 py-2 text-left font-medium">Absent</th>
                                                        <th class="px-3 py-2 text-left font-medium">Late</th>
                                                        <th class="px-3 py-2 text-left font-medium">Rate</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($deptDailySummaries[$selectedDept] as $d)
                                                        <tr>
                                                            <td class="px-3 py-2">{{ $d['date'] }}</td>
                                                            <td class="px-3 py-2">{{ $d['total'] }}</td>
                                                            <td class="px-3 py-2 text-green-600">{{ $d['present'] }}</td>
                                                            <td class="px-3 py-2 text-red-600">{{ $d['absent'] }}</td>
                                                            <td class="px-3 py-2 text-amber-600">{{ $d['late'] }}</td>
                                                            <td class="px-3 py-2">{{ $d['rate'] }}%</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <div class="print-section mt-6 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-300">
                                        <div class="mt-4 overflow-x-auto">
                                            <table class="min-w-full divide-y divide-slate-200 text-sm text-slate-800">
                                                <thead>
                                                    <tr class="bg-gray-50">
                                                        <th class="px-3 py-2 text-left font-medium">Employee ID</th>
                                                        <th class="px-3 py-2 text-left font-medium">Name</th>
                                                        <th class="px-3 py-2 text-left font-medium">Position</th>
                                                        <th class="px-3 py-2 text-left font-medium">Date</th>
                                                        <th class="px-3 py-2 text-left font-medium">Check In</th>
                                                        <th class="px-3 py-2 text-left font-medium">Check Out</th>
                                                        <th class="px-3 py-2 text-left font-medium">Status</th>
                                                        <th class="px-3 py-2 text-left font-medium">Work Min</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($selectedRows ?? [] as $row)
                                                        <tr>
                                                            <td class="px-3 py-2">{{ $row->employee_id }}</td>
                                                            <td class="px-3 py-2">{{ $row->name }}</td>
                                                            <td class="px-3 py-2">{{ $row->position }}</td>
                                                            <td class="px-3 py-2">{{ optional($row->attendance_date)->format('Y-m-d') }}</td>
                                                            <td class="px-3 py-2">{{ $row->check_in }}</td>
                                                            <td class="px-3 py-2">{{ $row->check_out }}</td>
                                                            <td class="px-3 py-2">
                                                                @php
                                                                    $statusClass = match (strtolower($row->status ?? '')) {
                                                                        'present' => 'bg-green-100 text-green-700',
                                                                        'late' => 'bg-amber-100 text-amber-700',
                                                                        'absent' => 'bg-red-100 text-red-700',
                                                                        default => 'bg-slate-100 text-slate-700',
                                                                    };
                                                                @endphp
                                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $row->status }}</span>
                                                            </td>
                                                            <td class="px-3 py-2">{{ $row->work_minutes }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const pieCtx = document.getElementById('departmentStatusChart');
                            if (!pieCtx) return;

                            const pieChart = new Chart(pieCtx, {
                                type: 'doughnut',
                                data: {
                                    labels: ['Present', 'Absent', 'Late'],
                                    datasets: [{
                                        data: [
                                            @json($selectedSummary['present'] ?? 0),
                                            @json($selectedSummary['absent'] ?? 0),
                                            @json($selectedSummary['late'] ?? 0)
                                        ],
                                        backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                                        borderColor: '#ffffff',
                                        borderWidth: 2,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: { position: 'bottom' },
                                    }
                                }
                            });
                        });
                    </script>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
