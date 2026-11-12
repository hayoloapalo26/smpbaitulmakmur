<x-filament-widgets::widget>
    @php
        $webhookHits = (int) ($diag['webhook_hits_count'] ?? 0);
        $cloudWebhookHits = (int) ($diag['webhook_hits_from_cloud_count'] ?? 0);
        $cloudWebhookVerified = (bool) ($diag['cloud_webhook_verified'] ?? false);
        $cloudId = $diag['cloud_id_configured'] ?? null;
        $lastSyncAt = $diag['last_cloud_sync_at'] ?? null;
        $deviceInfo = $diag['last_device_info'] ?? [];
        $cloudWebhookUrl = $deviceInfo['webhook_url'] ?? ($diag['cloud_webhook_url'] ?? null);
        $lastActivity = $deviceInfo['last_activity'] ?? ($diag['machine_last_activity'] ?? null);
        $deviceName = $deviceInfo['device_name'] ?? ($diag['device_name'] ?? null);
        $devicePins = $diag['device_pins'] ?? [];
        $devicePinsSyncedAt = $diag['device_pins_synced_at'] ?? null;
        $lastAsyncCallback = $diag['last_async_callback'] ?? null;

        $studentsCount = (int) ($diag['students_with_finger_count'] ?? 0);
        $sampleStudents = $diag['sample_registered_students'] ?? [];
        $unmatchedPins = $diag['last_unmatched_pins'] ?? [];
        $recentScans = $diag['recent_scans'] ?? [];
        $lastScan = $recentScans[0] ?? null;
        $todayTotal = (int) ($diag['today_attendance_total'] ?? 0);

        $expectedWebhookUrl = url('/api/fingerspot/attendance');
        $machineNeverConnected = empty($lastActivity) || strtolower((string) $lastActivity) === 'n/a';
    @endphp

    <div wire:poll.30s="refreshStatus" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm space-y-3.5">
        {{-- Header Status Bar --}}
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <div class="flex flex-wrap items-center gap-2">
                @if(!$machineNeverConnected)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        <span>● Mesin Terhubung Cloud ({{ $lastActivity }})</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 animate-pulse">
                        <span>⚠️ Mesin Fisik Belum Konek Cloud (Last Activity: N/A)</span>
                    </span>
                @endif

                @if($cloudWebhookVerified || $cloudWebhookHits > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        <span>● Webhook Cloud Aktif ({{ max($cloudWebhookHits, 1) }} cloud / {{ $webhookHits }} hit)</span>
                    </span>
                @elseif($webhookHits > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                        <span>● Endpoint Webhook Siap ({{ $webhookHits }} uji lokal, menunggu Cloud)</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        <span>⚠️ Webhook Menunggu Data (0 Diterima)</span>
                    </span>
                @endif

                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium text-[11px] bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                    🧪 Mode Trial: Lana (ID 78) Bebas Multi-Scan (Dedup per Menit)
                </span>

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

            <button 
                type="button" 
                wire:click="refreshStatus(true)" 
                wire:loading.attr="disabled"
                title="Segarkan panel dan tarik data presensi terbaru dari mesin/cloud"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-gray-700 border border-primary-200 dark:border-primary-800 shadow-sm transition active:scale-95 disabled:opacity-60 cursor-pointer"
            >
                <svg wire:loading.remove wire:target="refreshStatus" class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="refreshStatus" class="w-3.5 h-3.5 animate-spin text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="refreshStatus">🔄 Segarkan Panel &amp; Data</span>
                <span wire:loading wire:target="refreshStatus">Menyegarkan data...</span>
            </button>
        </div>

        {{-- KONDISI 1: JIKA MESIN FISIK BELUM KONEK CLOUD ATAU WEBHOOK BELUM ADA HIT --}}
        @if($machineNeverConnected || $webhookHits === 0)
            <div class="rounded-lg border-2 border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/40 p-3.5 text-xs text-amber-950 dark:text-amber-100 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-bold text-sm text-amber-900 dark:text-amber-200">
                        ⚡ Status Koneksi Mesin Fisik Revo W-230N &harr; Fingerspot Cloud
                    </span>
                    <span class="text-[11px] font-mono bg-amber-200 dark:bg-amber-800 px-2 py-0.5 rounded">
                        Cloud ID: {{ $cloudId ?: 'C260B8851F1C1030' }} &bull; Last Activity: {{ $lastActivity ?: 'N/A' }}
                    </span>
                </div>

                <div class="space-y-1.5 pl-1">
                    <div class="flex items-start gap-2">
                        <span class="font-bold text-primary-600 shrink-0">1. Web developer.fingerspot.io:</span>
                        <span>
                            Webhook URL target pada Cloud ID <code>{{ $cloudId ?: 'C260B8851F1C1030' }}</code>:
                            <code class="font-mono font-bold text-xs bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded border border-amber-400 dark:border-amber-600 select-all">{{ $expectedWebhookUrl }}</code>
                            @if($cloudWebhookUrl)
                                <span class="text-green-700 dark:text-green-400 font-semibold block mt-0.5">
                                    (✅ Terdaftar di akun Cloud: <code>{{ $cloudWebhookUrl }}</code>)
                                </span>
                            @endif
                        </span>
                    </div>

                    <div class="flex items-start gap-2 pt-1 border-t border-amber-200 dark:border-amber-800">
                        <span class="font-bold text-primary-600 shrink-0">2. PENTING &mdash; Pengaturan di Mesin Revo W-230N:</span>
                        <span>
                            @if($machineNeverConnected)
                                <strong class="text-red-700 dark:text-red-400 font-bold block mb-1">
                                    ⚠️ Cloud Fingerspot belum pernah menerima paket koneksi dari mesin fisik (Last Activity: N/A). Selama status masih N/A, Cloud belum memiliki data untuk dikirim ke sistem.
                                </strong>
                            @endif
                            Buka di layar mesin: <strong>Menu &rarr; Komunikasi / Jaringan &rarr; Server Cloud / ADMS</strong>:
                            <ul class="list-disc pl-5 mt-1 space-y-0.5">
                                <li><strong>Mode Koneksi:</strong> pilih <code>Internet</code> / aktifkan <strong>Server Req = Ya</strong></li>
                                <li><strong>IP Server (utama, bebas kendala DNS router):</strong> ketik <code>3.1.174.198</code> (atau domain <code>fdevice.com</code>)</li>
                                <li><strong>Port Server:</strong> coba <code>8014</code>; jika icon awan masih silang setelah restart, ubah ke <code>8013</code> (kedua port resmi <code>fdevice.com</code> aktif)</li>
                                <li><strong>Power-off Restart:</strong> matikan mesin 10 detik, lalu nyalakan kembali dan pastikan WiFi terhubung &amp; icon awan tidak bertanda silang.</li>
                            </ul>
                        </span>
                    </div>

                    <div class="flex items-start gap-2 pt-1 border-t border-amber-200 dark:border-amber-800">
                        <span class="font-bold text-primary-600 shrink-0">3. Uji Koneksi Cloud:</span>
                        <span>
                            Setelah icon awan terhubung, klik <strong>Kontrol Mesin Cloud &rarr; Tarik Daftar PIN Mesin</strong> atau scan jari langsung &mdash; data otomatis masuk real time via Webhook + sinkronisasi cadangan setiap 2 menit.
                        </span>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($devicePins) || $lastAsyncCallback)
            <div class="rounded-lg border border-sky-200 bg-sky-50 dark:border-sky-800 dark:bg-sky-950/30 p-2.5 text-xs text-sky-950 dark:text-sky-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    @if(!empty($devicePins))
                        <strong>👥 Daftar PIN dari Mesin (get_all_pin):</strong>
                        {{ count($devicePins) }} PIN terdeteksi (<code>{{ implode(', ', array_slice($devicePins, 0, 20)) }}{{ count($devicePins) > 20 ? ', ...' : '' }}</code>)
                        @if($devicePinsSyncedAt)
                            <span class="opacity-75">&bull; {{ $devicePinsSyncedAt }}</span>
                        @endif
                    @endif
                    @if($lastAsyncCallback)
                        <span class="block text-[11px] opacity-85 mt-0.5">
                            📡 Callback Cloud Terakhir: <code>{{ $lastAsyncCallback['type'] ?? '-' }}</code>
                            @if(!empty($lastAsyncCallback['trans_id'])) (trans_id: <code>{{ $lastAsyncCallback['trans_id'] }}</code>) @endif
                            &bull; {{ $lastAsyncCallback['time'] ?? '-' }}
                        </span>
                    @endif
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
