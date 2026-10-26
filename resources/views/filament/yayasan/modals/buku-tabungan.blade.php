<div class="p-2 space-y-4 text-gray-800 dark:text-gray-200">
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700" id="print-area-buku">
        {{-- Header Buku Tabungan --}}
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold">
                    BM
                </div>
                <div>
                    <h3 class="font-bold text-base text-gray-900 dark:text-white uppercase tracking-wider">BUKU TABUNGAN SISWA</h3>
                    <p class="text-xs text-gray-500">{{ $student?->institution?->name ?? 'Yayasan Baitul Makmur' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Total Saldo Terakhir</p>
                <p class="text-xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($student?->saldo_tabungan ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- Info Siswa --}}
        <div class="grid grid-cols-3 gap-3 py-3 text-xs border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 p-3 rounded-lg my-3">
            <div>
                <span class="text-gray-400">Nama Siswa:</span>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $student?->name ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-400">NIS / NISN:</span>
                <p class="font-mono font-semibold text-gray-900 dark:text-white">{{ $student?->nis ?? '-' }} / {{ $student?->nisn ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-400">Kelas / Rombel:</span>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $student?->classroom?->name ?? '-' }}</p>
            </div>
        </div>

        {{-- Tabel Mutasi Tabungan --}}
        <div class="overflow-x-auto mt-3">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700/60 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                        <th class="p-2 w-8 text-center">No</th>
                        <th class="p-2">Tanggal</th>
                        <th class="p-2">No. Bukti</th>
                        <th class="p-2">Keterangan</th>
                        <th class="p-2 text-right">Setor (Menabung)</th>
                        <th class="p-2 text-right">Tarik</th>
                        <th class="p-2 text-right">Saldo Akhir</th>
                        <th class="p-2 text-center">Petugas Keuangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($savings as $idx => $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="p-2 text-center text-gray-400">{{ $loop->iteration }}</td>
                            <td class="p-2 whitespace-nowrap">{{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-' }}</td>
                            <td class="p-2 font-mono text-[11px] text-gray-500">{{ $row->kode_transaksi }}</td>
                            <td class="p-2">{{ $row->catatan ?: ($row->tipe === 'tarik' ? 'Penarikan Tabungan' : 'Setoran Tabungan') }}</td>
                            <td class="p-2 text-right font-mono font-semibold {{ $row->tipe === 'setor' ? 'text-emerald-600' : 'text-gray-300' }}">
                                {{ $row->tipe === 'setor' ? 'Rp ' . number_format($row->nominal, 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-2 text-right font-mono font-semibold {{ $row->tipe === 'tarik' ? 'text-red-600' : 'text-gray-300' }}">
                                {{ $row->tipe === 'tarik' ? 'Rp ' . number_format($row->nominal, 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-2 text-right font-mono font-bold text-gray-900 dark:text-white">
                                Rp {{ number_format($row->saldo_akhir, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300 font-medium">
                                    {{ $row->petugas?->name ?? 'Petugas' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-400 italic">
                                Belum ada riwayat transaksi tabungan untuk siswa ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Buku Tabungan
        </button>
    </div>
</div>
