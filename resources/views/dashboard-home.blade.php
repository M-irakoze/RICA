<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <div class="rounded-2xl bg-slate-100 p-4 shadow-sm">
                    <nav class="flex flex-wrap items-center justify-between gap-4">
                        <a href="{{ route('dashboard', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-indigo-700 bg-indigo-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-indigo-700 inline-flex items-center justify-center">Daily report</a>
                        <a href="{{ route('attendance.weekly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-slate-700 bg-slate-700 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-slate-800 inline-flex items-center justify-center">Weekly report</a>
                        <a href="{{ route('attendance.monthly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-emerald-600 bg-emerald-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-emerald-700 inline-flex items-center justify-center">Monthly report</a>
                        <a href="{{ route('attendance.quarterly', ['date' => request()->query('date') ?? ($reportDate ?? now()->toDateString())]) }}" class="flex-1 min-w-[10rem] rounded-xl border border-amber-600 bg-amber-600 px-6 py-4 text-lg font-semibold text-white shadow-sm hover:bg-amber-700 inline-flex items-center justify-center">Quartely report</a>
                    </nav>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2 mb-6">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Departments overview</h3>
                            <p class="text-sm text-slate-500">Today’s department attendance rates.</p>
                        </div>
                    </div>

                    <div class="mt-6 h-64">
                        <canvas id="departmentRatesChart" class="h-full w-full"></canvas>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                            <p class="text-sm font-semibold text-slate-600">Top departments</p>
                            <div class="mt-3 space-y-3">
                                @forelse($topDepartments as $department)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span>{{ $department['department'] }}</span>
                                            <span class="font-semibold">{{ $department['rate'] }}%</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $department['present'] }}/{{ $department['total'] }} present</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No department data available.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 shadow-sm">
                            <p class="text-sm font-semibold text-slate-600">Lowest departments</p>
                            <div class="mt-3 space-y-3">
                                @forelse($bottomDepartments as $department)
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span>{{ $department['department'] }}</span>
                                            <span class="font-semibold">{{ $department['rate'] }}%</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $department['present'] }}/{{ $department['total'] }} present</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">No department data available.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Report totals</h3>
                            <p class="text-sm text-slate-500">Compare recent daily, weekly, monthly and quarterly attendance totals.</p>
                        </div>
                    </div>

                    <div class="mt-6 h-80">
                        <canvas id="attendancePeriodSummaryChart" class="h-full w-full"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const periodCtx = document.getElementById('attendancePeriodSummaryChart');
            if (periodCtx) {
                new Chart(periodCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($periodSummaryLabels),
                        datasets: [{
                            label: 'Attendance records',
                            data: @json($periodSummaryData),
                            backgroundColor: ['#2563eb', '#0ea5e9', '#14b8a6', '#f59e0b'],
                            borderColor: ['#1d4ed8', '#0284c7', '#0f766e', '#b45309'],
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                }
                            }
                        }
                    }
                });
            }

            const departmentCtx = document.getElementById('departmentRatesChart');
            if (departmentCtx) {
                new Chart(departmentCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($departmentLabels),
                        datasets: [{
                            label: 'Attendance rate',
                            data: @json($departmentRates),
                            backgroundColor: '#3b82f6',
                            borderColor: '#1d4ed8',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return `${context.dataset.label}: ${context.parsed.y}%`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function (value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
