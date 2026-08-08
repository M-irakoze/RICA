<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Workers Attendance Report') }}
        </h2>
    </x-slot>

    <div id="attendanceWrapper" class="worker-page-shell py-12 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="rounded-2xl bg-slate-100 p-4 shadow-sm">
                    <nav class="flex flex-wrap items-center justify-between gap-4">
                        @php $workersActive = request()->routeIs('attendance.workers'); @endphp
                        <a href="{{ route('dashboard', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300">Daily report</a>
                        <a href="{{ route('attendance.weekly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300 opacity-80">Weekly report</a>
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-emerald-600 bg-emerald-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-emerald-700 inline-flex items-center justify-center opacity-50 filter blur-sm">Monthly report</a>
                        <a href="{{ route('attendance.quarterly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-amber-600 bg-amber-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-amber-700 inline-flex items-center justify-center">Quartely report</a>
                    </nav>

                    @php
                        $currentDate = request()->query('date') ?? ($reportDate ?? now()->toDateString());
                        $currentScope = request()->query('scope', 'daily');
                        $currentPeriod = request()->query('period', $currentScope);
                        $effectiveScope = $currentScope === 'personal' ? $currentPeriod : $currentScope;
                        $departmentRoute = match ($effectiveScope) {
                            'weekly' => 'attendance.weekly.departments',
                            'monthly' => 'attendance.monthly.departments',
                            'quarterly' => 'attendance.quarterly.departments',
                            default => 'attendance.departments',
                        };
                        $overallRoute = match ($effectiveScope) {
                            'weekly' => 'attendance.weekly',
                            'monthly' => 'attendance.monthly',
                            'quarterly' => 'attendance.quarterly',
                            default => 'dashboard',
                        };
                        $workersScope = $currentScope === 'personal' ? $currentPeriod : $currentScope;
                    @endphp
                    <div class="mt-4 flex flex-wrap gap-3" id="reportTabs">
                        <a href="{{ route($overallRoute, ['date' => $currentDate]) }}" id="overallTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if((request()->routeIs('dashboard') && $overallRoute === 'dashboard') || (request()->routeIs($overallRoute) && $overallRoute !== 'dashboard')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Overall</a>
                        <a href="{{ route($departmentRoute, ['date' => $currentDate]) }}" id="departmentTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs($departmentRoute)) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Departments</a>
                        <a href="{{ route('attendance.workers', ['date' => $currentDate, 'scope' => $workersScope]) }}" id="workersTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.workers') && (request()->query('scope', 'daily') === $workersScope || request()->query('period') === $workersScope)) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Workers</a>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Workers</h3>
                        <p class="text-sm text-slate-500">Search workers by name and view personal attendance.</p>
                    </div>
                    <form method="get" action="{{ route('attendance.workers') }}" class="w-full sm:w-auto">
                        <input type="hidden" name="date" value="{{ request()->query('date') ?? ($reportDate ?? now()->toDateString()) }}">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <input type="hidden" name="period" value="{{ $period ?? $scope }}">
                        <div class="flex items-center gap-2">
                            <label for="search" class="sr-only">Search workers</label>
                            <input id="search" name="search" value="{{ request()->query('search', '') }}" type="search" placeholder="Search workers" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" autocomplete="off">
                            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Search</button>
                        </div>
                    </form>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const searchInput = document.getElementById('search');
                            const workerList = document.getElementById('workerCards');
                            const noResults = document.getElementById('noWorkersMessage');

                            function updateWorkerList() {
                                const filter = searchInput.value.trim().toLowerCase();
                                let visibleCount = 0;

                                workerList.querySelectorAll('[data-worker-name]').forEach((card) => {
                                    const name = card.getAttribute('data-worker-name');
                                    const match = filter === '' || name.includes(filter);
                                    card.classList.toggle('hidden', !match);
                                    if (match) {
                                        visibleCount += 1;
                                    }
                                });

                                noResults.classList.toggle('hidden', visibleCount > 0);
                            }

                            searchInput.addEventListener('input', function () {
                                updateWorkerList();
                            });

                            searchInput.addEventListener('keydown', function (event) {
                                if (event.key === 'Escape') {
                                    searchInput.value = '';
                                    updateWorkerList();
                                }
                            });

                            updateWorkerList();
                        });
                    </script>
                </div>

                <div id="noWorkersMessage" class="text-slate-500 {{ empty($workers) ? '' : 'hidden' }}">No worker attendance records found.</div>
                <div id="workerCards" class="grid gap-3">
                    @foreach($workers as $worker)
                        @php
                            if ($worker['rate'] < 50) {
                                $bgColor = '#fee2e2';
                                $borderColor = '#fca5a5';
                                $textColor = '#991b1b';
                            } elseif ($worker['rate'] < 80) {
                                $bgColor = '#fef3c7';
                                $borderColor = '#fbbf24';
                                $textColor = '#92400e';
                            } else {
                                $bgColor = '#d1fae5';
                                $borderColor = '#34d399';
                                $textColor = '#065f46';
                            }
                        @endphp
                            <a href="{{ route('attendance.workers', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString()), 'scope' => 'personal', 'period' => $period ?? $scope, 'worker' => $worker['name'], 'search' => request()->query('search', '')]) }}" class="rounded-lg border p-4 text-sm font-medium" style="background-color: {{ $bgColor }}; border-color: {{ $borderColor }}; color: {{ $textColor }};" data-worker-name="{{ strtolower($worker['name']) }}">
                            <div class="flex items-center justify-between gap-4">
                                <span>{{ $worker['name'] }}</span>
                                <span class="font-semibold">{{ $worker['rate'] }}% attendance</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            @php $showPersonalOverlay = request()->query('scope') === 'personal' && request()->query('worker'); @endphp
            <style>
                @media print {
                    .no-print {
                        display: none !important;
                    }

                    body {
                        background: white !important;
                        margin: 0 !important;
                    }

                    .worker-page-shell {
                        background: white !important;
                        min-height: auto !important;
                        padding: 0 !important;
                    }

                    .worker-page-shell > .max-w-7xl > :not(#personalAttendanceOverlay) {
                        display: none !important;
                    }

                    #personalAttendanceOverlay {
                        position: static !important;
                        inset: auto !important;
                        display: block !important;
                        padding: 0 !important;
                        background: white !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        overflow: visible !important;
                    }

                    #personalAttendanceOverlay > div.absolute {
                        display: none !important;
                    }

                    #personalAttendanceOverlay .worker-overlay {
                        max-height: none !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        overflow: visible !important;
                        box-shadow: none !important;
                        border: none !important;
                        border-radius: 0 !important;
                    }

                    #personalAttendanceOverlay .worker-overlay .print-section {
                        break-inside: avoid;
                        page-break-inside: avoid;
                    }

                    #personalAttendanceOverlay .worker-overlay table {
                        width: 100% !important;
                        border-collapse: collapse !important;
                    }

                    #personalAttendanceOverlay .worker-overlay canvas {
                        max-width: 100% !important;
                        max-height: 100% !important;
                    }
                }
            </style>
            <div id="personalAttendanceOverlay" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto px-4 py-6 sm:px-6 @if(!$showPersonalOverlay) hidden @endif">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-7xl max-h-[calc(100vh-6rem)] overflow-y-auto overflow-x-hidden rounded-3xl shadow-2xl ring-1 ring-slate-900/10 worker-overlay" @if(!empty($selectedWorkerColor)) style="background-color: {{ $selectedWorkerColor['bgColor'] }}; border-color: {{ $selectedWorkerColor['borderColor'] }}; color: {{ $selectedWorkerColor['textColor'] }};" @else style="background-color: #ffffff;" @endif>
                    <div class="p-6 print-section">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold" @if(!empty($selectedWorkerColor)) style="color: {{ $selectedWorkerColor['textColor'] }};" @endif>{{ ucfirst($period ?? 'daily') }} attendance for {{ request()->query('worker') }}</h3>
                                <p class="text-sm" @if(!empty($selectedWorkerColor)) style="color: {{ $selectedWorkerColor['textColor'] }};" @else class="text-slate-600" @endif>Viewing {{ request()->query('worker') }} for {{ ucfirst($period ?? 'daily') }} period.</p>
                            </div>
                            <div class="flex items-center justify-end gap-2 no-print">
                                <button type="button" id="downloadPersonalAttendancePdf" class="inline-flex h-10 items-center justify-center rounded-full bg-slate-900 px-4 text-sm font-semibold text-white shadow-sm ring-1 ring-slate-900/10 transition hover:bg-slate-700">
                                    Download PDF
                                </button>
                                <button type="button" id="closePersonalAttendance" class="inline-flex h-10 items-center justify-center rounded-full bg-white/95 px-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:bg-white">
                                    Close
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            @if($personalRows->isEmpty())
                                <p class="text-sm text-slate-600">No attendance records found for this worker on {{ $reportDate }}.</p>
                            @else
                                <div class="mb-6 flex flex-wrap items-stretch gap-3">
                                    <div class="flex-1 min-w-[8rem] rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                        <div class="font-semibold">Total records</div>
                                        <div>{{ $personalSummary['total'] }}</div>
                                    </div>
                                    <div class="flex-1 min-w-[8rem] rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                        <div class="font-semibold">Present</div>
                                        <div>{{ $personalSummary['present'] }}</div>
                                    </div>
                                    <div class="flex-1 min-w-[8rem] rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                        <div class="font-semibold">Absent</div>
                                        <div>{{ $personalSummary['absent'] }}</div>
                                    </div>
                                    <div class="flex-1 min-w-[8rem] rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
                                        <div class="font-semibold">Attendance rate</div>
                                        <div>{{ $personalSummary['rate'] }}%</div>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                                        <thead class="bg-slate-50 text-slate-700">
                                            <tr>
                                                <th class="px-4 py-3">Date</th>
                                                <th class="px-4 py-3">Check In</th>
                                                <th class="px-4 py-3">Check Out</th>
                                                <th class="px-4 py-3">Status</th>
                                                <th class="px-4 py-3">Department</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach($personalRows as $attendance)
                                                <tr>
                                                    <td class="px-4 py-3">{{ optional($attendance->attendance_date)->toDateString() ?? 'N/A' }}</td>
                                                    <td class="px-4 py-3">{{ $attendance->check_in ?? '-' }}</td>
                                                    <td class="px-4 py-3">{{ $attendance->check_out ?? '-' }}</td>
                                                    <td class="px-4 py-3 capitalize">{{ $attendance->status ?? 'unknown' }}</td>
                                                    <td class="px-4 py-3">{{ $attendance->department ?? 'Unassigned' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modal = document.getElementById('personalAttendanceOverlay');
                    const closeBtn = document.getElementById('closePersonalAttendance');
                    const downloadBtn = document.getElementById('downloadPersonalAttendancePdf');
                    if (!modal || !closeBtn) {
                        return;
                    }

                    closeBtn.addEventListener('click', function () {
                        modal.classList.add('hidden');
                        const url = new URL(window.location.href);
                        const currentScope = url.searchParams.get('scope');
                        const currentPeriod = url.searchParams.get('period');
                        url.searchParams.delete('worker');

                        if (currentScope === 'personal') {
                            url.searchParams.set('scope', currentPeriod || 'daily');
                        } else if (currentScope) {
                            url.searchParams.set('scope', currentScope);
                        }

                        history.replaceState(null, '', url.toString());
                    });

                    if (downloadBtn) {
                        downloadBtn.addEventListener('click', function () {
                            window.print();
                        });
                    }
                });
            </script>

        </div>
    </div>
</x-app-layout>
