<x-filament-widgets::widget>
    @php
        $webhookHits = (int) ($diag['webhook_hits_count'] ?? 0);
        $cloudId = $diag['cloud_id_configured'] ?? null;
        $lastSyncAt = $diag['last_cloud_sync_at'] ?? null;
        $deviceInfo = $diag['last_device_info'] ?? [];
        $cloudWebhookUrl = $deviceInfo['webhook_url'] ?? ($diag['cloud_webhook_url'] ?? null);
        $lastActivity = $deviceInfo['last_activity'] ?? null;
        $deviceName = $deviceInfo['device_name'] ?? ($diag['device_name'] ?? null);
        $lastAttlogRaw = $diag['last_attlog_raw'] ?? null;

        $studentsCount = (int) ($diag['students_with_finger_count'] ?? 0);
        $sampleStudents = $diag['sample_registered_students'] ?? [];
        $unmatchedPins = $diag['last_unmatched_pins'] ?? [];
        $recentScans = $diag['recent_scans'] ?? [];
        $lastScan = $recentScans[0] ?? null;
        $todayTotal = (int) ($diag['today_attendance_total'] ?? 0);

        $expectedWebhookUrl = url('/api/fingerspot/attendance');
        $webhookMismatch = !empty($cloudWebhookUrl) && trim($cloudWebhookUrl) !== $expectedWebhookUrl && trim($cloudWebhookUrl) !== url('/api/fingerspot/webhook');
        $machineNeverConnected = empty($lastActivity) || strtolower((string) $lastActivity) === 'n/a';
    @endphp

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm space-y-3.5">
        {{-- Header Status Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                @if($webhookHits > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        <span>● Webhook Aktif ({{ $webhookHits }} diterima)</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 animate-pulse">
                        <span>⚠️ Webhook Belum Terhubung (0 Diterima)</span>
                    </span>
                @endif

                <span class="text-gray-600 dark:text-gray-300">
                    Cloud ID: <code class="font-mono font-bold text-primary-600 dark:text-primary-400">{{ $cloudId ?: 'Belum diisi' }}</code>
                    @if($deviceName)
                        ({{ $deviceName }})
                    @endif
                    @if($lastSyncAt)
                        &nbsp;&bull;&nbsp; Sinkron Cloud: <strong>{{ $lastSyncAt }}</strong>
                    @endif
                    &nbsp;&bull;&nbsp; Siswa Terdaftar ID: <strong>{{ $studentsCount }}</strong>
                    &nbsp;&bull;&nbsp; Presensi Hari Ini: <strong>{{ $todayTotal }}</strong>
                </span>
            </div>

            <button type="button" wire:click="refreshStatus" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                🔄 Segarkan Panel
            </button>
        </div>

        {{-- KONDISI 1: JIKA WEBHOOK MASIH 0 (PANDUAN LANGKAH ATASI SEGERA) --}}
        @if($webhookHits === 0)
            <div class="rounded-lg border-2 border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40 p-3.5 text-xs text-amber-950 dark:text-amber-100 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-sm text-amber-900 dark:text-amber-200">
                        ⚡ Kenapa Webhook Masih 0 &amp; Data Belum Masuk? Lakukan 2 Langkah Ini:
                    </span>
                    <span class="text-[11px] font-mono bg-amber-200 dark:bg-amber-800 px-2 py-0.5 rounded">
                        Target Cloud ID: {{ $cloudId ?: 'C2688851F1C1830' }}
                    </span>
                </div>

                <div class="space-y-1.5 pl-1">
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-primary-600">Langkah 1 (Di web developer.fingerspot.io):</span>
                        <span>
                            Buka tab <strong>developer.fingerspot.io</strong> &rarr; menu <strong>Device</strong> (atau Pengaturan Perangkat) pada baris mesin <code>{{ $cloudId ?: 'C2688851F1C1830' }}</code>.
                            Pastikan kolom <strong>Webhook URL</strong> diisi persis:
                            <code class="block mt-1 font-mono font-bold text-xs bg-white dark:bg-gray-800 p-1.5 rounded border border-amber-400 dark:border-amber-600 select-all">{{ $expectedWebhookUrl }}</code>
                            @if($cloudWebhookUrl)
                                <span class="text-red-600 dark:text-red-400 font-semibold block mt-0.5">
                                    (Saat ini terdeteksi di Cloud: <code>{{ $cloudWebhookUrl ?: 'KOSONG / BELUM TERPASANG' }}</code>)
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="flex items-start gap-2 pt-1 border-t border-amber-200 dark:border-amber-800">
                        <span class="font-bold text-primary-600">Langkah 2 (Di Mesin Fisik Revo W230N):</span>
                        <span>
                            Tekan <strong>Menu &rarr; Setelan &rarr; Jaringan &rarr; Server</strong> pada mesin absensi.
                            Pastikan: <strong>IP/Domain Server = <code>fdevice.com</code></strong> (atau IP <code>3.1.174.198</code>) dan <strong>Port Server = <code>8013</code></strong>, pastikan WiFi/LAN tersambung, lalu <strong>Restart (Matikan &amp; Hidupkan)</strong> mesin agar data scan langsung dikirimkan ke server.
                        </span>
                    </div>

                    <div class="flex items-start gap-2 pt-1 border-t border-amber-200 dark:border-amber-800">
                        <span class="font-bold text-primary-600">Solusi Cepat (Tanpa Tunggu Webhook):</span>
                        <span>
                            Klik tombol oranye <strong>🔄 Sinkron Mesin Fingerspot</strong> di kanan atas, pastikan Cloud ID <code>{{ $cloudId ?: 'C2688851F1C1830' }}</code>, pilih tanggal (misal 2 hari terakhir), lalu klik <strong>Tarik Log Sekarang</strong> untuk menarik paksa log absensi dari Cloud Fingerspot seketika!
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Alert PIN Belum Terdaftar --}}
        @if(!empty($unmatchedPins))
            @php $latestUnmatched = $unmatchedPins[0]; @endphp
            <div class="rounded-lg border-2 border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/40 p-3 text-xs text-red-900 dark:text-red-100">
                <div class="font-bold text-sm">🚨 Mesin mengirim scan PIN "{{ $latestUnmatched['pin'] ?? '-' }}" ({{ $latestUnmatched['time'] ?? '-' }}), tetapi PIN tersebut belum ada di Data Siswa!</div>
                <div class="mt-1">Buka menu <strong>Data Siswa</strong>, cari siswa yang bersangkutan, lalu ketik angka <code>{{ $latestUnmatched['pin'] ?? '-' }}</code> pada kolom <strong>ID Finger</strong> agar rekapan langsung tampil.</div>
            </div>
        @endif

        {{-- Info Scan Terakhir --}}
        @if($lastScan)
            <div class="rounded-lg border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/40 p-2.5 text-xs text-green-950 dark:text-green-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <strong>✅ Scan Terakhir Diterima:</strong> {{ $lastScan['nama'] ?? '-' }}
                    ({{ strtoupper(str_replace('_', ' ', $lastScan['tipe'] ?? '-')) }} &bull; jam {{ $lastScan['jam'] ?? '-' }})
                    @if(!empty($lastScan['is_late']))
                        — <span class="text-red-600 dark:text-red-400 font-bold">Terlambat</span>
                    @else
                        — <span class="font-semibold text-green-700 dark:text-green-300">Tepat Waktu</span>
                    @endif
                    @if(($lastScan['tipe'] ?? '') === 'guru_masuk')
                        <span class="block text-[11px] text-blue-700 dark:text-blue-300 mt-0.5">ℹ️ Catatan: Scan ini atas nama Guru (otomatis masuk ke menu Presensi Guru, bukan daftar Siswa di bawah).</span>
                    @endif
                </div>
                <span class="text-[11px] opacity-80">{{ $lastScan['date'] ?? '' }}</span>
            </div>
        @endif

        {{-- Jadwal Resmi & Daftar Siswa Terhubung --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
            <div>
                <strong>Jadwal Resmi:</strong>
                Masuk <code>06.00–06.50</code> &bull;
                Sholat Dhuhur <code>12.00–12.15</code> &bull;
                Ashar &amp; Pulang <code>15.00–15.15</code>
                (Lewat batas = Terlambat &bull; Kunci 1x scan per sesi)
            </div>
            @if(!empty($sampleStudents))
                <div>
                    <strong>Contoh Siswa Ber-ID Finger:</strong>
                    @foreach(array_slice($sampleStudents, 0, 8) as $idx => $s)
                        {{ $idx > 0 ? ', ' : '' }}{{ $s['nama'] ?? '' }} (<code>{{ $s['id_finger'] ?? '-' }}</code>)
                    @endforeach
                    @if(count($sampleStudents) > 8)
                        dan {{ $studentsCount - 8 }} lainnya
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
