<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Preview Uploaded File') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">File</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $filename }}</p>
                            <p class="text-sm text-slate-500">Format: {{ strtoupper($extension) }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('dashboard') }}" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Back to dashboard</a>
                            <a href="{{ route('attendance.download', $filename) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Download</a>
                        </div>
                    </div>

                    @if($message)
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800">
                            {{ $message }}
                        </div>
                    @endif

                    @if(! empty($headers))
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-600">
                                    <tr>
                                        @foreach($headers as $header)
                                            <th class="px-4 py-3 font-medium">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    @forelse($rows as $row)
                                        <tr>
                                            @foreach($headers as $index => $header)
                                                <td class="whitespace-nowrap px-4 py-2 text-slate-700">{{ $row[$index] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-slate-600" colspan="{{ count($headers) }}">No preview rows available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-sm text-slate-500">Showing up to 50 rows from the selected file.</p>
                    @elseif(! $message)
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-slate-700">
                            No preview data was found in this file.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
