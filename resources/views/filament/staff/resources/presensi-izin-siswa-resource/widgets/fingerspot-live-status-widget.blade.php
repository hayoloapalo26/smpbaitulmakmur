<x-filament-widgets::widget>
    @php
        $webhookHits = (int) ($diag['webhook_hits_count'] ?? 0);
        $cloudId = $diag['cloud_id_configured'] ?? null;
        $lastSyncAt = $diag['last_cloud_sync_at'] ?? null;
        $cloudWebhookUrl = $diag['cloud_webhook_url'] ?? null;
        $studentsCount = (int) ($diag['students_with_finger_count'] ?? 0);
        $sampleStudents = $diag['sample_registered_students'] ?? [];
        $unmatchedPins = $diag['last_unmatched_pins'] ?? [];
        $recentScans = $diag['recent_scans'] ?? [];
        $lastScan = $recentScans[0] ?? null;
        $todayTotal = (int) ($diag['today_attendance_total'] ?? 0);
    @endphp

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm space-y-3">
        {{-- Header Status Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold {{ $webhookHits > 0 || !empty($cloudId) ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                    <span>{{ $webhookHits > 0 || !empty($cloudId) ? '● Mesin Terhubung' : '⚠️ Menunggu Koneksi Mesin' }}</span>
                </span>
                <span class="text-gray-600 dark:text-gray-300">
                    Webhook Masuk: <strong>{{ $webhookHits }}</strong>
                    @if(!empty($cloudId))
                        &nbsp;|&nbsp; Cloud ID: <code class="font-mono text-primary-600 dark:text-primary-400">{{ $cloudId }}</code>
                        @if($lastSyncAt)
                            (Sinkron: {{ $lastSyncAt }})
                        @endif
                    @endif
                    &nbsp;|&nbsp; Siswa Ber-ID Finger: <strong>{{ $studentsCount }}</strong>
                    &nbsp;|&nbsp; Presensi Hari Ini: <strong>{{ $todayTotal }}</strong>
                </span>
            </div>

            <button type="button" wire:click="refreshStatus" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                🔄 Segarkan Status
            </button>
        </div>

        {{-- Alert PIN Belum Terdaftar --}}
        @if(!empty($unmatchedPins))
            @php $latestUnmatched = $unmatchedPins[0]; @endphp
            <div class="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/40 p-3 text-xs text-red-800 dark:text-red-200">
                <div class="font-bold">🚨 Mesin mengirim scan PIN "{{ $latestUnmatched['pin'] ?? '-' }}" ({{ $latestUnmatched['time'] ?? '-' }}), tetapi PIN tersebut belum terpasang di Data Siswa/Guru!</div>
                <div class="mt-1">Buka menu <strong>Data Siswa</strong>, lalu isi kolom <strong>ID Finger</strong> siswa dengan angka <code>{{ $latestUnmatched['pin'] ?? '-' }}</code> agar scan langsung masuk ke rekap presensi.</div>
            </div>
        @elseif($webhookHits === 0 && empty($cloudId))
            <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-3 text-xs text-amber-900 dark:text-amber-200 space-y-1">
                <div class="font-bold">ℹ️ Belum ada data scan dari mesin Fingerspot yang masuk ke server web ({{ $webhookHits }} webhook diterima).</div>
                <div>1. Pastikan di portal <strong>developer.fingerspot.io</strong> &rarr; Device &rarr; <strong>Webhook URL</strong> diarahkan ke: <code class="bg-white/80 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-amber-300 dark:border-amber-700 select-all">https://dev.baitulmakmur.sch.id/api/fingerspot/attendance</code></div>
                <div>2. Atau klik tombol oranye <strong>"🔄 Sinkron Mesin Fingerspot"</strong> di atas, masukkan <strong>Cloud ID</strong> mesin Revo W230N Anda, lalu klik <strong>Tarik Log Sekarang</strong> agar sistem menarik data absensi langsung dari Cloud Fingerspot.</div>
            </div>
        @elseif($lastScan && ($lastScan['tipe'] ?? '') === 'guru_masuk')
            <div class="rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/40 p-3 text-xs text-blue-900 dark:text-blue-200">
                <div class="font-bold">ℹ️ Scan terakhir tercatat atas nama Guru: {{ $lastScan['nama'] ?? '-' }} ({{ $lastScan['date'] ?? '' }} {{ $lastScan['jam'] ?? '' }})</div>
                <div>Kehadiran guru tersimpan di menu <strong>Presensi Guru</strong>, bukan di daftar presensi siswa di bawah. Untuk memunculkan di sini, scan menggunakan sidik jari <strong>Siswa</strong> yang ID Finger-nya sudah terisi.</div>
            </div>
        @elseif($lastScan)
            <div class="rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/40 p-2.5 text-xs text-green-900 dark:text-green-200 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <strong>✅ Scan Terakhir Masuk:</strong> {{ $lastScan['nama'] ?? '-' }}
                    ({{ strtoupper(str_replace('_', ' ', $lastScan['tipe'] ?? '-')) }}, jam {{ $lastScan['jam'] ?? '-' }})
                    @if(!empty($lastScan['is_late']))
                        — <span class="text-red-600 dark:text-red-400 font-bold">Terlambat</span>
                    @else
                        — <span class="font-semibold">Tepat Waktu</span>
                    @endif
                </div>
                @if(!empty($cloudWebhookUrl))
                    <span class="text-[11px] opacity-80">Webhook Cloud Terpasang: <code>{{ $cloudWebhookUrl }}</code></span>
                @endif
            </div>
        @endif

        {{-- Jadwal Resmi & Daftar Siswa Terhubung --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
            <div>
                <strong>Jadwal Resmi:</strong>
                Masuk <code>06.00–06.50</code> &bull;
                Sholat Dhuhur <code>12.00–12.15</code> &bull;
                Ashar &amp; Pulang <code>15.00–15.15</code>
                (Lewat batas = Terlambat &bull; Terkunci 1x scan per sesi)
            </div>
            @if(!empty($sampleStudents))
                <div>
                    <strong>Contoh ID Finger Siswa Aktif:</strong>
                    @foreach($sampleStudents as $idx => $s)
                        {{ $idx > 0 ? ', ' : '' }}{{ $s['nama'] ?? '' }} (<code>{{ $s['id_finger'] ?? '-' }}</code>)
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
