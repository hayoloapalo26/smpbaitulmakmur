<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
            <h3 class="text-lg font-bold mb-4">Status Pengisian To-Do List Karakter - {{ $today }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Nama Guru</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3 text-center">Status Hari Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report as $row)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">{{ $row['email'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row['status'] === 'Sudah Isi')
                                        <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">Sudah Isi</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">Belum Isi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
