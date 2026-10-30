@php
    $tpIds = $record?->tp_ids ?? [];
    if (!empty($tpIds) && is_array($tpIds)) {
        $tps = \App\Models\TujuanPembelajaran::with('capaianPembelajaran')
            ->whereIn('id', $tpIds)
            ->get()
            ->sort(function ($a, $b) {
                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
            })
            ->values();
    } elseif ($record instanceof \App\Models\AlurTujuanPembelajaran) {
        $tps = $record->accumulated_tps;
    } else {
        $tps = collect();
    }
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
    <table class="w-full text-sm text-left">
        <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="px-3 py-2.5 text-center w-12">No</th>
                <th class="px-3 py-2.5 text-center w-20">Kode CP</th>
                <th class="px-3 py-2.5 w-36">Elemen CP</th>
                <th class="px-3 py-2.5 text-center w-24">Kode TP</th>
                <th class="px-3 py-2.5 w-44">Materi Pokok</th>
                <th class="px-3 py-2.5">Rumusan Tujuan Pembelajaran (TP)</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
            @forelse($tps as $index => $tp)
                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition">
                    <td class="px-3 py-2.5 text-center font-medium text-gray-500">{{ $index + 1 }}</td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
                            CP {{ $tp->capaianPembelajaran?->kode_cp ?: '-' }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 font-medium text-xs">{{ $tp->capaianPembelajaran?->elemen ?: '-' }}</td>
                    <td class="px-3 py-2.5 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700">
                            {{ $tp->kode_tp }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 font-semibold text-xs text-gray-900 dark:text-gray-100">{{ $tp->materi ?: '-' }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-700 dark:text-gray-300 leading-relaxed">{{ $tp->deskripsi_tp }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-4 text-center text-gray-500 italic">
                        Belum ada Tujuan Pembelajaran (TP) yang terakumulasi. Pilih TP pada inputan di atas atau input TP melalui menu Tujuan Pembelajaran.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
