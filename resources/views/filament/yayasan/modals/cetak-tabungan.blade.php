<div class="p-4 space-y-4 text-gray-800 dark:text-gray-200">
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-5 bg-white dark:bg-gray-800" id="print-area-saving">
        {{-- Header Slip --}}
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-lg">
                    BM
                </div>
                <div>
                    <h4 class="font-bold text-sm text-gray-900 dark:text-white uppercase tracking-wider">YAYASAN BAITUL MAKMUR</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $saving->institution?->name ?? 'Unit Sekolah' }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full {{ $saving->tipe === 'tarik' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    BUKTI {{ strtoupper($saving->tipe === 'tarik' ? 'PENARIKAN' : 'SETORAN TABUNGAN') }}
                </span>
                <p class="text-[11px] font-mono text-gray-400 mt-1">{{ $saving->kode_transaksi }}</p>
            </div>
        </div>

        {{-- Detail Transaksi --}}
        <div class="grid grid-cols-2 gap-3 py-4 text-xs border-b border-gray-100 dark:border-gray-700">
            <div>
                <p class="text-gray-400">Nama Siswa:</p>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $saving->student?->name ?? '-' }}</p>
                <p class="text-gray-500 font-mono text-[11px]">NIS: {{ $saving->student?->nis ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Kelas / Rombel:</p>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $saving->classroom?->name ?? '-' }}</p>
                <p class="text-gray-500 text-[11px]">Lembaga: {{ $saving->institution?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Tanggal Transaksi:</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $saving->tanggal ? \Carbon\Carbon::parse($saving->tanggal)->translatedFormat('d F Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Metode Pembayaran:</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $saving->metode_pembayaran ?? 'Tunai' }}</p>
            </div>
        </div>

        {{-- Rincian Nominal --}}
        <div class="py-3 space-y-2 text-xs border-b border-gray-100 dark:border-gray-700">
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Sebelumnya:</span>
                <span class="font-mono">Rp {{ number_format($saving->saldo_awal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm {{ $saving->tipe === 'tarik' ? 'text-red-600' : 'text-emerald-600' }}">
                <span>{{ $saving->tipe === 'tarik' ? 'Jumlah Ditarik' : 'Jumlah yang Ditabung' }}:</span>
                <span class="font-mono">{{ $saving->tipe === 'tarik' ? '-' : '+' }} Rp {{ number_format($saving->nominal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-gray-900 dark:text-white pt-2 border-t border-dashed border-gray-200 dark:border-gray-700">
                <span>SALDO AKHIR:</span>
                <span class="font-mono text-orange-600 dark:text-orange-400 text-base">Rp {{ number_format($saving->saldo_akhir, 0, ',', '.') }}</span>
            </div>
        </div>

        @if ($saving->catatan)
            <div class="py-2 text-xs text-gray-500 italic">
                Catatan: {{ $saving->catatan }}
            </div>
        @endif

        {{-- Tanda Tangan --}}
        <div class="grid grid-cols-2 pt-6 text-center text-xs text-gray-500">
            <div>
                <p>Penyetor / Siswa</p>
                <div class="h-12"></div>
                <p class="font-semibold text-gray-800 dark:text-gray-200">({{ $saving->student?->name ?? '.......................' }})</p>
            </div>
            <div>
                <p>Bagian Keuangan / Petugas</p>
                <div class="h-12"></div>
                <p class="font-semibold text-gray-800 dark:text-gray-200">({{ $saving->petugas?->name ?? 'Petugas Keuangan' }})</p>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Kwitansi
        </button>
    </div>
</div>
