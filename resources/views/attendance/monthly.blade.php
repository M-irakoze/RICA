<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Monthly Attendance Report') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="rounded-2xl bg-slate-100 p-4 shadow-sm">
                    <nav class="flex flex-wrap items-center justify-between gap-4">
                        @php $monthlyActive = request()->routeIs('attendance.monthly'); @endphp
                        <a href="{{ route('dashboard', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300">Daily report</a>
                        <a href="{{ route('attendance.weekly', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300 opacity-80">Weekly report</a>
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg font-semibold inline-flex items-center justify-center {{ $monthlyActive ? 'border-4 border-black bg-emerald-600 text-white shadow-xl relative z-10 -translate-y-1 hover:bg-emerald-700' : 'border border-slate-300 bg-slate-200 text-slate-700 shadow-sm hover:bg-slate-300 opacity-80' }}">Monthly report</a>
                        <a href="{{ route('attendance.quarterly', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-amber-600 bg-amber-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-amber-700 inline-flex items-center justify-center">Quartely report</a>
                    </nav>

                    <div class="mt-4 flex flex-wrap gap-3" id="reportTabs">
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" id="overallTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.monthly') && !request()->query('dept')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Overall</a>
                        <a href="{{ route('attendance.monthly.departments', ['date' => request()->query('date') ?? $reportDate->toDateString()]) }}" id="departmentTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100">Departments</a>
                        <a href="{{ route('attendance.workers', ['date' => request()->query('date') ?? $reportDate->toDateString(), 'scope' => 'monthly']) }}" id="workersTab" class="flex-1 min-w-[10rem] rounded-xl px-6 py-4 text-lg inline-flex items-center justify-center @if(request()->routeIs('attendance.workers') && (request()->query('scope', 'daily') === 'monthly')) border border-black bg-slate-200/50 font-bold text-slate-900 shadow-lg -translate-y-0.5 @else border border-slate-300 bg-transparent font-semibold text-slate-700 shadow-sm hover:bg-slate-100 @endif">Workers</a>
                    </div>
                </div>
            </div>

            <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex justify-center">
                    <h3 class="text-xl font-semibold text-center">Month of {{ $startOfMonth->format('M j, Y') }} &ndash; {{ $endOfMonth->format('M j, Y') }}</h3>
                </div>
            </div>

            <div class="grid gap-6 mb-6 xl:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm order-last xl:order-first">
                    <h3 class="text-lg font-semibold mb-4">Monthly attendance rate by week</h3>
                    <div class="h-72">
                        @if(empty($monthlyData) || count($monthlyData) === 0)
                            <div class="h-72 flex items-center justify-center text-slate-500">No attendance records for this month.</div>
                        @else
                            <canvas id="monthlyReportChart" data-labels='@json($monthlyLabels)' data-data='@json($monthlyData)'></canvas>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm order-first xl:order-last">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="text-sm uppercase tracking-wide text-slate-500">Overall summary</div>
                            <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $selectedSummary['total'] }}</div>
                            <div class="mt-2 text-sm text-slate-600">Total records for the selected month</div>
                        </div>
                        <div class="grid gap-4">
                            <div class="rounded-xl bg-emerald-50 p-4">
                                <div class="text-sm uppercase tracking-wide text-emerald-700">Present</div>
                                <div class="mt-2 text-2xl font-semibold text-emerald-800">{{ $selectedSummary['present'] }}</div>
                            </div>
                            <div class="rounded-xl bg-red-50 p-4">
                                <div class="text-sm uppercase tracking-wide text-red-700">Absent</div>
                                <div class="mt-2 text-2xl font-semibold text-red-800">{{ $selectedSummary['absent'] }}</div>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-4">
                                <div class="text-sm uppercase tracking-wide text-amber-700">Late</div>
                                <div class="mt-2 text-2xl font-semibold text-amber-800">{{ $selectedSummary['late'] }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <div class="text-sm uppercase tracking-wide text-slate-700">Attendance rate</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $selectedSummary['rate'] }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Daily breakdown</h3>
                <p class="mb-3 text-sm text-slate-600">The table stays in daily order, grouped naturally from Week 1 through Week 4 for the selected month.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
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
                            @foreach($dailySummaries as $summary)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-900">{{ $summary['date'] }}</td>
                                    <td class="px-3 py-2">{{ $summary['total'] }}</td>
                                    <td class="px-3 py-2 text-green-600">{{ $summary['present'] }}</td>
                                    <td class="px-3 py-2 text-red-600">{{ $summary['absent'] }}</td>
                                    <td class="px-3 py-2 text-amber-600">{{ $summary['late'] }}</td>
                                    <td class="px-3 py-2">{{ $summary['rate'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('monthlyReportChart');
            if (!ctx) return;

            const weeklyBucketDates = @json($weeklyBucketStartDates ?? []);

            const monthlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($monthlyLabels),
                    datasets: [{
                        label: 'Attendance rate',
                        data: @json($monthlyData),
                        backgroundColor: [
                            '#10b981', '#34d399', '#6ee7b7', '#14b8a6', '#0ea5e9', '#6366f1', '#8b5cf6'
                        ],
                        borderRadius: 8,
                        maxBarThickness: 48,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    onClick: function(_, elements) {
                        if (!elements.length) {
                            return;
                        }

                        const index = elements[0].index;
                        const selectedWeekDate = weeklyBucketDates[index];

                        if (!selectedWeekDate) {
                            return;
                        }

                        window.location.href = '{{ route('attendance.weekly') }}?date=' + encodeURIComponent(selectedWeekDate);
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            grid: { color: '#e2e8f0' }
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
