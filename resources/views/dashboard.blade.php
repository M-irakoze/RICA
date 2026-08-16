<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daily Attendance Report') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="rounded-2xl bg-slate-100 p-4 shadow-sm">
                    <nav class="flex flex-wrap items-center justify-between gap-4">
                        @php $dailyActive = request()->routeIs('attendance.daily') || request()->routeIs('daily') || request()->routeIs('dashboard') || request()->routeIs('attendance.departments'); @endphp
                        <a href="{{ route('attendance.daily') }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-indigo-700 bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 {{ $dailyActive ? 'border-4 border-black shadow-xl relative z-10 -translate-y-1' : '' }}" style="background-color:#4f46e5;color:#ffffff;border-color:#4338ca;">Daily report</a>
                        <a href="{{ route('attendance.weekly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-slate-700 bg-slate-700 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-slate-800 inline-flex items-center justify-center {{ request()->routeIs('attendance.weekly') ? 'border-4 border-black shadow-xl relative z-10 -translate-y-1' : '' }}" style="background-color:#334155;color:#ffffff;border-color:#334155;">Weekly report</a>
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-emerald-600 bg-emerald-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-emerald-700 inline-flex items-center justify-center" style="background-color:#10b981;color:#ffffff;border-color:#10b981;">Monthly report</a>
                        <a href="{{ route('attendance.quarterly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-amber-600 bg-amber-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-amber-700 inline-flex items-center justify-center" style="background-color:#f59e0b;color:#ffffff;border-color:#f59e0b;">Quartely report</a>
                    </nav>
                    <div class="mt-4 flex flex-wrap gap-3" id="reportTabs">
                        <a href="{{ route('attendance.daily') }}" id="overallTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.daily') || request()->routeIs('daily') || request()->routeIs('dashboard')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Overall</a>
                        <a href="{{ route('attendance.departments') }}{{ request()->query('date') ? '?date='.e(request()->query('date')) : '' }}" id="departmentTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.departments')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Departments</a>
                        <a href="{{ route('attendance.workers', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString()), 'scope' => 'daily']) }}" id="workersTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.workers') && (request()->query('scope', 'daily') === 'daily')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Workers</a>
                    </div>
                </div>
            </div>
            <div class="mb-4 flex items-center justify-between gap-4">
                <form id="reportDateForm" method="GET" action="{{ route('attendance.daily') }}" class="flex items-center gap-2">
                    <button type="button" id="prevDay" title="Previous day" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">‹</button>
                    <input id="reportDateInput" name="date" type="date" value="{{ $reportDate ?? now()->toDateString() }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    <button type="button" id="nextDay" title="Next day" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50">›</button>
                </form>

                <div class="text-sm text-slate-600">Showing report for <strong class="text-slate-800" id="reportDateLabel">{{ $reportDate ?? now()->toDateString() }}</strong></div>
            </div>

            <script>
                function initDashboardDateForm() {
                    const form = document.getElementById('reportDateForm');
                    const input = document.getElementById('reportDateInput');
                    const prev = document.getElementById('prevDay');
                    const next = document.getElementById('nextDay');
                    const label = document.getElementById('reportDateLabel');

                    if (!form || !input || form.dataset.initialized === 'true') return;
                    form.dataset.initialized = 'true';

                    function formatDateForInput(d) {
                        return d.toISOString().slice(0, 10);
                    }

                    if (prev) {
                        prev.addEventListener('click', function () {
                            const d = new Date(input.value || new Date().toISOString());
                            d.setDate(d.getDate() - 1);
                            input.value = formatDateForInput(d);
                            if (label) label.textContent = input.value;
                            form.submit();
                        });
                    }

                    if (next) {
                        next.addEventListener('click', function () {
                            const d = new Date(input.value || new Date().toISOString());
                            d.setDate(d.getDate() + 1);
                            input.value = formatDateForInput(d);
                            if (label) label.textContent = input.value;
                            form.submit();
                        });
                    }

                    input.addEventListener('change', function () {
                        if (label) label.textContent = input.value;
                        form.submit();
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initDashboardDateForm);
                } else {
                    initDashboardDateForm();
                }
                document.addEventListener('turbo:load', initDashboardDateForm);
            </script>
            @if(session('success'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-6 mb-6 xl:grid-cols-2">
                <div class="xl:col-span-2 rounded-lg bg-slate-100 p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Upload daily report</h3>
                    
                    <form action="{{ route('attendance.import') }}" method="POST" enctype="multipart/form-data" class="mt-6">
                        @csrf
                        <div class="grid gap-4">
                            <div>
                                <p class="block text-sm font-medium text-gray-700">Choose attendance report</p>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <label for="attendance_file" class="inline-flex h-14 cursor-pointer items-center justify-center rounded-md bg-slate-100 border border-black px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-300/70">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mr-2 h-5 w-5 text-slate-700">
                                            <path fill-rule="evenodd" d="M8.25 4.5A4.5 4.5 0 0 0 3.75 9v9.75A2.25 2.25 0 0 0 6 21h12a2.25 2.25 0 0 0 2.25-2.25V9a4.5 4.5 0 0 0-4.5-4.5H8.25zM6 8.25a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 8.25V15h-1.5V8.25a.75.75 0 0 0-.75-.75H8.25a.75.75 0 0 0-.75.75V15H6V8.25zm6.75 2.25a.75.75 0 0 1 .75.75v4.5l1.5-1.5a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 1.06-1.06l1.5 1.5V11.25a.75.75 0 0 1 .75-.75z" clip-rule="evenodd" />
                                        </svg>
                                        Select file
                                    </label>
                                    <span id="selectedFileName" class="text-sm text-slate-500">No file chosen</span>
                                    <button id="uploadButton" type="submit" class="inline-flex h-14 w-32 cursor-pointer items-center justify-center rounded-md border border-blue-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/70" style="background-color:#2563eb;color:#ffffff;">Upload</button>
                                </div>
                                <input id="attendance_file" name="attendance_file" type="file" accept=".xlsx,.xls,.csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required class="sr-only" />
                            </div>
                            
                            
                            @error('attendance_file')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const fileInput = document.getElementById('attendance_file');
                                const fileNameLabel = document.getElementById('selectedFileName');

                                if (fileInput && fileNameLabel) {
                                    fileInput.addEventListener('change', function () {
                                        const fileName = fileInput.files?.length ? fileInput.files[0].name : 'No file chosen';
                                        fileNameLabel.textContent = fileName;
                                    });
                                }
                            });
                        </script>
                    </form>

                    @if(! empty($uploadedFiles))
                        <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
                            <p class="font-semibold text-slate-900">Uploaded files</p>

                            <div class="mt-4 space-y-4">
                                @foreach($uploadedFiles as $file)
                                    <div class="rounded-lg bg-white border border-slate-200 p-4">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="truncate text-slate-800">{{ $file }}</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2 justify-end">
                                                <a href="{{ route('attendance.view', $file) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800">View</a>
                                                <a href="{{ route('attendance.download', $file) }}" class="inline-flex items-center rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800">Download</a>
                                                <form action="{{ route('attendance.delete', $file) }}" method="POST" onsubmit="return confirm('Delete uploaded file?');" class="m-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mb-6 overflow-x-auto">
                <div class="flex min-w-full justify-between gap-4">
                    <div class="min-w-[14rem] rounded-2xl bg-indigo-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-medium text-gray-600">Total records</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $total }}</p>
                    </div>
                    <div class="min-w-[14rem] rounded-2xl bg-slate-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-medium text-gray-600">Attendance rate</p>
                        <p class="mt-2 text-2xl font-semibold text-blue-600">{{ $attendanceRate }}%</p>
                    </div>
                    <div class="min-w-[14rem] rounded-2xl bg-emerald-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-medium text-gray-600">Present</p>
                        <p class="mt-2 text-2xl font-semibold text-green-600">{{ $present }}</p>
                    </div>
                    <div class="min-w-[14rem] rounded-2xl bg-rose-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-medium text-gray-600">Absent</p>
                        <p class="mt-2 text-2xl font-semibold text-red-600">{{ $absent }}</p>
                    </div>
                    <div class="min-w-[14rem] rounded-2xl bg-amber-50 p-5 text-center shadow-sm">
                        <p class="text-sm font-medium text-gray-600">Late</p>
                        <p class="mt-2 text-2xl font-semibold text-yellow-600">{{ $late }}</p>
                    </div>
                </div>
            </div>

            @php
                $safeTotal = max($total, 1);
                $presentPct = round(($present / $safeTotal) * 100, 1);
                $absentPct = round(($absent / $safeTotal) * 100, 1);
                $latePct = round(($late / $safeTotal) * 100, 1);
            @endphp

            <div class="grid gap-6 lg:grid-cols-2 mb-6">
                <div class="rounded-lg bg-slate-100 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Attendance status</h3>
                        <span class="text-sm text-gray-500">Status chart</span>
                    </div>

                    <div class="mt-8 flex flex-col items-center gap-6 sm:flex-row">
                        <div class="w-full max-w-md">
                            <canvas id="attendanceStatusChart" class="w-full h-72 rounded-3xl bg-slate-50 p-4"></canvas>
                        </div>

                        <div class="grid gap-4 w-full sm:w-auto">
                            <div class="rounded-2xl bg-emerald-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-wide text-emerald-700">Present</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ $present }}</p>
                                <p class="text-sm text-slate-500">{{ $presentPct }}%</p>
                            </div>
                            <div class="rounded-2xl bg-rose-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-wide text-rose-700">Absent</p>
                                <p class="mt-2 text-2xl font-semibold text-rose-800">{{ $absent }}</p>
                                <p class="text-sm text-slate-500">{{ $absentPct }}%</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-4 text-center">
                                <p class="text-xs uppercase tracking-wide text-amber-700">Late</p>
                                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $late }}</p>
                                <p class="text-sm text-slate-500">{{ $latePct }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($statusRows->isNotEmpty())
                <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-base font-semibold text-gray-800">Attendance details</h4>
                        <span class="text-sm text-gray-500">{{ $statusRows->count() }} records</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-3 py-2 text-left font-medium">Employee ID</th>
                                    <th class="px-3 py-2 text-left font-medium">Name</th>
                                    <th class="px-3 py-2 text-left font-medium">Department</th>
                                    <th class="px-3 py-2 text-left font-medium">Position</th>
                                    <th class="px-3 py-2 text-left font-medium">Gender</th>
                                    <th class="px-3 py-2 text-left font-medium">Date</th>
                                    <th class="px-3 py-2 text-left font-medium">Week</th>
                                    <th class="px-3 py-2 text-left font-medium">Timetable</th>
                                    <th class="px-3 py-2 text-left font-medium">Check-in</th>
                                    <th class="px-3 py-2 text-left font-medium">Check-out</th>
                                    <th class="px-3 py-2 text-left font-medium">Work minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">OT minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Attended minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Late minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Early minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Absent minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Leave minutes</th>
                                    <th class="px-3 py-2 text-left font-medium">Source</th>
                                    <th class="px-3 py-2 text-left font-medium">Records</th>
                                    <th class="px-3 py-2 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($statusRows as $row)
                                    <tr>
                                        <td class="px-3 py-2">{{ $row->employee_id }}</td>
                                        <td class="px-3 py-2">{{ $row->name }}</td>
                                        <td class="px-3 py-2">{{ $row->department }}</td>
                                        <td class="px-3 py-2">{{ $row->position }}</td>
                                        <td class="px-3 py-2">{{ $row->gender }}</td>
                                        <td class="px-3 py-2">{{ optional($row->attendance_date)->format('Y-m-d') }}</td>
                                        <td class="px-3 py-2">{{ $row->week }}</td>
                                        <td class="px-3 py-2">{{ $row->timetable }}</td>
                                        <td class="px-3 py-2">{{ $row->check_in }}</td>
                                        <td class="px-3 py-2">{{ $row->check_out }}</td>
                                        <td class="px-3 py-2">{{ $row->work_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->ot_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->attended_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->late_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->early_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->absent_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->leave_minutes }}</td>
                                        <td class="px-3 py-2">{{ $row->source }}</td>
                                        <td class="px-3 py-2">{{ $row->records }}</td>
                                        <td class="px-3 py-2">
                                            @php
                                                $rowStatusClass = match (strtolower($row->status ?? '')) {
                                                    'present' => 'bg-green-100 text-green-700',
                                                    'late' => 'bg-amber-100 text-amber-700',
                                                    'absent' => 'bg-red-100 text-red-700',
                                                    default => 'bg-slate-100 text-slate-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $rowStatusClass }}">{{ $row->status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <script>
                function initDashboardStatusChart() {
                    if (typeof Chart === 'undefined') {
                        setTimeout(initDashboardStatusChart, 50);
                        return;
                    }

                    const statusCtx = document.getElementById('attendanceStatusChart');
                    if (statusCtx) {
                        if (Chart.getChart(statusCtx)) {
                            Chart.getChart(statusCtx).destroy();
                        }

                        new Chart(statusCtx, {
                            type: 'pie',
                            data: {
                                labels: ['Present', 'Absent', 'Late'],
                                datasets: [{
                                    data: [{{ $present }}, {{ $absent }}, {{ $late }}],
                                    backgroundColor: ['#16a34a', '#ef4444', '#f59e0b'],
                                    borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                                    borderWidth: 2,
                                    hoverBackgroundColor: ['#15803d', '#dc2626', '#d97706'],
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            pointStyle: 'circle',
                                        },
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                const label = context.label || '';
                                                const value = context.parsed || 0;
                                                const total = context.dataset.data.reduce((sum, item) => sum + item, 0);
                                                const percentage = total ? ((value / total) * 100).toFixed(1) : '0.0';
                                                return `${label}: ${value} (${percentage}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initDashboardStatusChart);
                } else {
                    initDashboardStatusChart();
                }
                document.addEventListener('turbo:load', initDashboardStatusChart);
            </script>

           

        </div>
    </div>
</x-app-layout>
