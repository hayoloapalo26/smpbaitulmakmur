@php
    $record = $getRecord() ?? $record ?? null;
    $summons = $record ? $record->parentSummons()->orderByDesc('tanggal_pemanggilan')->get() : collect();
@endphp

@if($summons->isEmpty())
    <div class="p-3 text-center text-xs text-gray-500 italic bg-gray-50 dark:bg-gray-800 rounded-lg">
        Belum ada surat panggilan orang tua yang diterbitkan untuk kasus ini.
    </div>
@else
    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-50 dark:bg-gray-800 font-bold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="py-2.5 px-3 text-center">#</th>
                    <th class="py-2.5 px-3">No. Surat</th>
                    <th class="py-2.5 px-3">Tgl Surat</th>
                    <th class="py-2.5 px-3">Jadwal Hadir</th>
                    <th class="py-2.5 px-3 text-center">Status Kehadiran</th>
                    <th class="py-2.5 px-3">Notula / Hasil Pertemuan</th>
                    <th class="py-2.5 px-3 text-center">Dokumen Drive</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @foreach($summons as $idx => $ps)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="py-2 px-3 text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-gray-900 dark:text-white">{{ $ps->nomor_surat ?? '-' }}</td>
                        <td class="py-2 px-3 text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($ps->tanggal_pemanggilan)->format('d M Y') }}</td>
                        <td class="py-2 px-3 font-semibold text-gray-800 dark:text-gray-200">
                            {{ $ps->tanggal_pertemuan ? \Carbon\Carbon::parse($ps->tanggal_pertemuan)->format('d M Y') : '-' }}
                            <span class="block text-[10px] text-gray-500">{{ $ps->tempat_pertemuan }}</span>
                        </td>
                        <td class="py-2 px-3 text-center">
                            @if($ps->status === 'hadir')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-300 border border-green-200 dark:border-green-800">
                                    ✅ Telah Hadir
                                </span>
                            @elseif($ps->status === 'tidak_hadir')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800">
                                    ❌ Mangkir / Tidak Hadir
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    ⏳ Menunggu Hadir
                                </span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-gray-700 dark:text-gray-300 max-w-[220px]">
                            {{ $ps->hasil_pertemuan ?: '-' }}
                        </td>
                        <td class="py-2 px-3 text-center">
                            @if($ps->surat_file)
                                <a href="{{ str_starts_with($ps->surat_file, 'http') ? $ps->surat_file : asset('storage/' . $ps->surat_file) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 font-bold text-[11px] border border-primary-200 dark:border-primary-800 hover:underline">
                                    <span>Buka Link ↗</span>
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
