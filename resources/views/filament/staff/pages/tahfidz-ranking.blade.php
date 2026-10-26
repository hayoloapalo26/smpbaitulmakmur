<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($rankings as $className => $students)
            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                <h3 class="text-lg font-bold mb-4">Kelas {{ $className }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Peringkat</th>
                                <th class="px-4 py-3">NIS</th>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">Capaian Ziyadah (Jumlah)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3">{{ $student->nis }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $student->name }}</td>
                                    <td class="px-4 py-3">{{ $student->ziyadah_count }}</td>
                                </tr>
                            @endforeach
                            @if($students->isEmpty())
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-center">Belum ada data siswa.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
