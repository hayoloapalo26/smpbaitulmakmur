<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controller Integrasi Mesin Absensi Fingerspot Revo W230N
 *
 * Jadwal & Aturan Keterlambatan Resmi:
 * - Masuk Pagi           : 06.00 - 06.50 (> 06.50 = Terlambat) [Rentang mesin: 00:00 - 11:29]
 * - Sholat Dhuhur        : 12.00 - 12.15 (> 12.15 = Terlambat) [Rentang mesin: 11:30 - 14:29]
 * - Sholat Ashar & Pulang: 15.00 - 15.15 (> 15.15 = Terlambat) [Rentang mesin: 14:30 - 23:59]
 *
 * Penguncian Anti-Dobel:
 * - Siswa hanya bisa absen 1 kali per waktu/sesi.
 * - Scan pertama kali langsung dikunci; scan ke-2 dst di sesi yang sama ditolak/diabaikan.
 *
 * Mendukung 4 Mode Komunikasi:
 * 1. Webhook Fingerspot.io (developer.fingerspot.io -> POST /api/fingerspot/attendance, /webhook, /api/fingerspot, POST /)
 * 2. Cloud API Pull (developer.fingerspot.io/api/get_attlog) via tombol & sinkronisasi otomatis
 * 3. Direct ADMS / Push Server (GET/POST /iclock/cdata & /iclock/getrequest)
 * 4. Direct REST / Test Scan (/api/fingerspot/test-scan)
 */
class FingerspotAttendanceController extends Controller
{
    private const CONFIG_FILE = 'fingerspot_config.json';
    private const WEBHOOK_LOG_FILE = 'fingerspot_webhook_logs.json';

    /**
     * Definisi aturan waktu per tipe absen:
     * [cutoff_hour, cutoff_minute, label, check_tipes, lock_group]
     */
    private const SCHEDULE_RULES = [
        'masuk' => [
            'limit_hour'   => 6,
            'limit_minute' => 50,
            'limit_str'    => '06:50',
            'label'        => 'Masuk Pagi',
            'check_tipes'  => ['masuk'],
            'lock_group'   => 'masuk',
        ],
        'sholat_dhuha' => [
            'limit_hour'   => 9,
            'limit_minute' => 30,
            'limit_str'    => '09:30',
            'label'        => 'Sholat Dhuha',
            'check_tipes'  => ['sholat_dhuha'],
            'lock_group'   => 'dhuha',
        ],
        'sholat_dhuhur' => [
            'limit_hour'   => 12,
            'limit_minute' => 15,
            'limit_str'    => '12:15',
            'label'        => 'Sholat Dhuhur',
            'check_tipes'  => ['sholat_dhuhur'],
            'lock_group'   => 'dhuhur',
        ],
        'sholat_ashar' => [
            'limit_hour'   => 15,
            'limit_minute' => 15,
            'limit_str'    => '15:15',
            'label'        => 'Sholat Ashar & Pulang',
            'check_tipes'  => ['sholat_ashar', 'pulang'],
            'lock_group'   => 'ashar_pulang',
        ],
        'pulang' => [
            'limit_hour'   => 15,
            'limit_minute' => 15,
            'limit_str'    => '15:15',
            'label'        => 'Sholat Ashar & Pulang',
            'check_tipes'  => ['sholat_ashar', 'pulang'],
            'lock_group'   => 'ashar_pulang',
        ],
    ];

    /**
     * Endpoint utama untuk menerima webhook / push log dari Fingerspot
     * POST/GET /api/fingerspot/attendance, /api/fingerspot/webhook, /api/fingerspot, POST /
     */
    public function receive(Request $request)
    {
        // Catat raw request ke log persisten SEBELUM validasi apapun agar audit selalu terlacak
        $this->recordRawWebhookHit($request);

        Log::channel('daily')->info('Fingerspot Event Received', [
            'ip'     => $request->ip(),
            'method' => $request->method(),
            'path'   => $request->path(),
            'query'  => $request->query(),
            'post'   => $request->post(),
        ]);

        // Validasi API Key: tolak dengan 401 hanya jika pengirim menyertakan key yang SALAH
        // (Webhook developer.fingerspot.io umumnya mengirim JSON murni tanpa header X-API-KEY)
        $configuredKey = $this->getConfiguredApiKey();
        $incomingKey = $this->extractIncomingApiKey($request);

        if (!empty($incomingKey) && !empty($configuredKey) && $incomingKey !== $configuredKey) {
            Log::warning('Fingerspot Webhook: Percobaan akses dengan API Key yang tidak valid', [
                'ip'           => $request->ip(),
                'provided_key' => substr($incomingKey, 0, 4) . '****',
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: API Key tidak valid',
            ], 401);
        }

        try {
            $records = $this->extractRecordsFromRequest($request);

            if (empty($records)) {
                return response()->json([
                    'status'  => 'ignored',
                    'message' => 'Tidak ada data log presensi (PIN) dalam request',
                ], 200);
            }

            // Urutkan secara kronologis dari waktu paling awal agar scan PERTAMA selalu terkunci lebih dulu
            $records = $this->sortRecordsChronologically($records);

            $results = [];
            foreach ($records as $rec) {
                if (empty($rec['pin']) && $rec['pin'] !== '0') {
                    continue;
                }

                $results[] = $this->processAttendanceRecord(
                    (string) $rec['pin'],
                    $rec['scan_date'] ?? null,
                    $rec['verify'] ?? 1,
                    $rec['status_scan'] ?? 0
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Proses data Fingerspot selesai',
                'total'   => count($results),
                'results' => $results,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Fingerspot Event Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ekstrak array log scan dari berbagai format payload webhook Fingerspot
     */
    protected function extractRecordsFromRequest(Request $request): array
    {
        $payload = $request->all();
        $rawContent = $request->getContent();

        if (empty($payload) && !empty($rawContent)) {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (isset($payload['data']) && is_string($payload['data'])) {
            $decodedData = json_decode($payload['data'], true);
            if (is_array($decodedData)) {
                $payload['data'] = $decodedData;
            }
        }

        // Jika webhook mengirim cloud_id, simpan otomatis agar Cloud Pull ikut aktif
        if (!empty($payload['cloud_id']) && is_string($payload['cloud_id'])) {
            $this->autoRememberCloudId($payload['cloud_id']);
        }

        $records = [];

        // Format 1: Event Fingerspot.io dengan wrapper 'data' (objek tunggal atau array)
        if (!empty($payload['data']) && is_array($payload['data'])) {
            $rawRecords = isset($payload['data'][0]) ? $payload['data'] : [$payload['data']];
            foreach ($rawRecords as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if ($mapped = $this->normalizeScanItem($item)) {
                    $records[] = $mapped;
                }
            }
        }

        // Format 2: Array langsung dari daftar log scan [{"pin":"1",...}]
        if (empty($records) && isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if ($mapped = $this->normalizeScanItem($item)) {
                    $records[] = $mapped;
                }
            }
        }

        // Format 3: Flat JSON / Form / Query (termasuk type='attlog' dengan pin di root object)
        if (empty($records) && is_array($payload)) {
            if ($mapped = $this->normalizeScanItem($payload)) {
                $records[] = $mapped;
            }
        }

        return $records;
    }

    /**
     * Normalisasi satu item log scan ke struktur standar
     */
    private function normalizeScanItem(array $item): ?array
    {
        $pin = $item['pin']
            ?? $item['PIN']
            ?? $item['user_id']
            ?? $item['employee_no']
            ?? $item['badgenumber']
            ?? $item['enroll_id']
            ?? $item['id_finger']
            ?? null;

        if ($pin === null || $pin === '') {
            return null;
        }

        return [
            'pin'         => (string) $pin,
            'scan_date'   => $item['scan_date'] ?? $item['scan'] ?? $item['date_time'] ?? $item['time'] ?? $item['timestamp'] ?? $item['att_time'] ?? null,
            'verify'      => $item['verify'] ?? $item['verif_mode'] ?? $item['verify_mode'] ?? 1,
            'status_scan' => $item['status_scan'] ?? $item['status'] ?? $item['in_out'] ?? 0,
        ];
    }

    /**
     * Urutkan log scan menaik (terlama ke terbaru) agar scan pertama kali selalu dikunci
     */
    private function sortRecordsChronologically(array $records): array
    {
        usort($records, function ($a, $b) {
            $tA = !empty($a['scan_date']) ? strtotime((string) $a['scan_date']) : 0;
            $tB = !empty($b['scan_date']) ? strtotime((string) $b['scan_date']) : 0;
            return $tA <=> $tB;
        });

        return $records;
    }

    /**
     * Handler Protokol Direct ADMS / Push Server (IClock)
     * GET/POST /iclock/cdata
     */
    public function cdata(Request $request)
    {
        $sn = $request->query('SN') ?? $request->query('sn') ?? 'UNKNOWN';

        if ($request->isMethod('get')) {
            Log::info("Fingerspot ADMS Handshake from SN: {$sn}");
            $responseContent = "GET OPTION FROM: {$sn}\n" .
                "Stamp=9999\n" .
                "OpStamp=0\n" .
                "ErrorDelay=60\n" .
                "Delay=30\n" .
                "TransTimes=00:00;14:05\n" .
                "TransInterval=1\n" .
                "TransFlag=1111000000\n" .
                "TimeZone=7\n" .
                "Realtime=1\n" .
                "Encrypt=0\n";

            return response($responseContent, 200)->header('Content-Type', 'text/plain');
        }

        $this->recordRawWebhookHit($request);
        $rawBody = $request->getContent();
        $records = [];

        if (!empty($rawBody)) {
            $lines = explode("\n", str_replace("\r", "", $rawBody));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $cols = preg_split('/\t+|\s{2,}/', $line);
                if (count($cols) >= 2 && !empty(trim($cols[0]))) {
                    $records[] = [
                        'pin'         => trim($cols[0]),
                        'scan_date'   => trim($cols[1]),
                        'status_scan' => isset($cols[2]) ? (int) $cols[2] : 0,
                        'verify'      => isset($cols[3]) ? (int) $cols[3] : 1,
                    ];
                }
            }
        }

        $records = $this->sortRecordsChronologically($records);
        $processedCount = 0;

        foreach ($records as $rec) {
            $this->processAttendanceRecord($rec['pin'], $rec['scan_date'], $rec['verify'], $rec['status_scan']);
            $processedCount++;
        }

        return response("OK: {$processedCount}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Polling Perintah ADMS (IClock getrequest)
     * GET /iclock/getrequest
     */
    public function getrequest(Request $request)
    {
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Feedback Perintah ADMS (IClock devicecmd)
     * POST /iclock/devicecmd
     */
    public function devicecmd(Request $request)
    {
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Cek Koneksi & Diagnostik Live
     * GET /api/fingerspot/ping
     */
    public function ping()
    {
        return response()->json($this->getDiagnosticSnapshot());
    }

    /**
     * Ringkasan status koneksi, webhook masuk, PIN belum terdaftar, dan statistik Fingerspot
     * Digunakan oleh /api/fingerspot/ping maupun Widget Live di halaman Presensi Siswa
     */
    public function getDiagnosticSnapshot(): array
    {
        $config = $this->getCloudConfig();
        $hasKey = !empty($this->getConfiguredApiKey());

        $studentsWithFinger = Student::withoutGlobalScopes()
            ->where(function ($q) {
                if (Schema::hasColumn('students', 'id_finger')) {
                    $q->whereNotNull('id_finger')->where('id_finger', '!=', '');
                }
                if (Schema::hasColumn('students', 'fingerspot_pin')) {
                    $q->orWhereNotNull('fingerspot_pin')->where('fingerspot_pin', '!=', '');
                }
            })->count();

        $teachersWithFinger = User::withoutGlobalScopes()
            ->where(function ($q) {
                if (Schema::hasColumn('users', 'id_finger')) {
                    $q->whereNotNull('id_finger')->where('id_finger', '!=', '');
                }
                if (Schema::hasColumn('users', 'fingerspot_pin')) {
                    $q->orWhereNotNull('fingerspot_pin')->where('fingerspot_pin', '!=', '');
                }
            })->count();

        $sampleStudents = Student::withoutGlobalScopes()
            ->where(function ($q) {
                if (Schema::hasColumn('students', 'id_finger')) {
                    $q->whereNotNull('id_finger')->where('id_finger', '!=', '');
                }
                if (Schema::hasColumn('students', 'fingerspot_pin')) {
                    $q->orWhereNotNull('fingerspot_pin')->where('fingerspot_pin', '!=', '');
                }
            })
            ->limit(10)
            ->get(['id', 'name', 'nis', 'id_finger', 'fingerspot_pin'])
            ->map(fn ($s) => [
                'nama'      => $s->name,
                'id_finger' => $s->id_finger ?? $s->fingerspot_pin,
                'nis'       => $s->nis,
            ])
            ->toArray();

        $rawWebhooks = $this->readJsonFile(self::WEBHOOK_LOG_FILE, []);
        $recentScans = $this->readJsonFile('fingerspot_recent_scans.json', Cache::get('fingerspot_recent_scans', []));
        $unmatchedPins = $this->readJsonFile('fingerspot_unmatched_pins.json', Cache::get('fingerspot_last_unmatched_pins', []));

        $todayWib = now()->setTimezone('Asia/Jakarta')->toDateString();
        $todayStudentAttendanceCount = DailyAttendance::withoutGlobalScopes()
            ->whereDate('tanggal', $todayWib)
            ->count();

        return [
            'status'                         => 'online',
            'device_target'                  => 'Fingerspot Revo W230N',
            'server'                         => 'SMPI Baitul Makmur',
            'time'                           => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s T'),
            'version'                        => '2.3',
            'api_key_configured'             => $hasKey,
            'cloud_id_configured'            => $config['cloud_id'] ?? null,
            'cloud_webhook_url'              => $config['cloud_webhook_url'] ?? null,
            'device_name'                    => $config['device_name'] ?? null,
            'last_device_info'               => $config['last_device_info'] ?? null,
            'last_attlog_raw'                => $config['last_attlog_raw'] ?? null,
            'last_cloud_sync_at'             => $config['last_sync_at'] ?? null,
            'last_sync_summary'              => $config['last_sync_summary'] ?? null,
            'today_attendance_total'         => $todayStudentAttendanceCount,
            'schedule_rules'                 => [
                'masuk'         => '06:00 - 06:50 (> 06:50 Terlambat, batas scan pagi 11:29)',
                'sholat_dhuhur' => '12:00 - 12:15 (> 12:15 Terlambat, aktif 11:30 - 14:29)',
                'sholat_ashar'  => '15:00 - 15:15 (> 15:15 Terlambat, aktif 14:30 - 23:59, Ashar & Pulang)',
                'anti_dobel'    => 'Siswa hanya bisa absen 1 kali per waktu (scan pertama dikunci)',
            ],
            'students_with_finger_count'     => $studentsWithFinger,
            'teachers_with_finger_count'     => $teachersWithFinger,
            'sample_registered_students'     => $sampleStudents,
            'webhook_hits_count'             => count($rawWebhooks),
            'last_raw_webhooks'              => array_slice($rawWebhooks, 0, 5),
            'recent_scans'                   => array_slice($recentScans, 0, 10),
            'last_unmatched_pins'            => array_slice($unmatchedPins, 0, 10),
            'test_scan_url'                  => url('/api/fingerspot/test-scan?pin=1'),
            'sync_cloud_url'                 => url('/api/fingerspot/sync'),
            'endpoints'                      => [
                'cloud_webhook' => url('/api/fingerspot/attendance'),
                'cloud_alias'   => url('/api/fingerspot/webhook'),
                'api_root'      => url('/api/fingerspot'),
                'adms_push'     => url('/iclock/cdata'),
            ],
        ];
    }

    /**
     * Endpoint Sinkronisasi Langsung ke API Cloud developer.fingerspot.io
     * GET/POST /api/fingerspot/sync
     */
    public function syncEndpoint(Request $request)
    {
        $cloudId = $request->input('cloud_id') ?? $request->query('cloud_id');
        $startDate = $request->input('start_date') ?? $request->query('start_date');
        $endDate = $request->input('end_date') ?? $request->query('end_date');

        $result = $this->pullFromCloudApi($cloudId, $startDate, $endDate);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * Endpoint Cepat untuk Menguji Scan Presensi via Browser
     * GET /api/fingerspot/test-scan?pin=1&tipe=auto&time=06:45
     */
    public function testScan(Request $request)
    {
        $pin = $request->query('pin') ?? $request->input('pin');
        $tipe = $request->query('tipe') ?? $request->input('tipe');
        $time = $request->query('time') ?? $request->input('time');

        if (empty($pin) && $pin !== '0') {
            $firstStudent = Student::withoutGlobalScopes()
                ->where(function ($q) {
                    if (Schema::hasColumn('students', 'id_finger')) {
                        $q->whereNotNull('id_finger')->where('id_finger', '!=', '');
                    }
                    if (Schema::hasColumn('students', 'fingerspot_pin')) {
                        $q->orWhereNotNull('fingerspot_pin')->where('fingerspot_pin', '!=', '');
                    }
                })->first();

            $suggestedPin = $firstStudent?->id_finger ?? $firstStudent?->fingerspot_pin ?? $firstStudent?->nis ?? '1';

            return response()->json([
                'status'        => 'prompt',
                'message'       => 'Masukkan parameter PIN untuk menguji scan mesin Fingerspot.',
                'example_url'   => url("/api/fingerspot/test-scan?pin={$suggestedPin}"),
                'suggested_pin' => $suggestedPin,
                'suggested_for' => $firstStudent?->name ?? 'Belum ada siswa yang memiliki ID Finger',
            ]);
        }

        $res = $this->processAttendanceRecord(
            (string) $pin,
            $time,
            1,
            0,
            $tipe && $tipe !== 'auto' ? $tipe : null
        );

        return response()->json([
            'test_mode' => true,
            'result'    => $res,
        ]);
    }

    /**
     * Tarik log absensi secara langsung dari API Cloud developer.fingerspot.io (/api/get_attlog)
     */
    public function pullFromCloudApi(?string $cloudId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $config = $this->getCloudConfig();
        $apiKey = $this->getConfiguredApiKey();
        $resolvedCloudId = trim((string) ($cloudId ?: ($config['cloud_id'] ?? '')));

        if (!empty($cloudId)) {
            $this->saveCloudConfig(['cloud_id' => $resolvedCloudId]);
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key Fingerspot belum dikonfigurasi.',
            ];
        }

        if (empty($resolvedCloudId)) {
            return [
                'success' => false,
                'message' => 'Cloud ID mesin Fingerspot belum diisi. Silakan salin Cloud ID mesin dari web developer.fingerspot.io.',
            ];
        }

        $todayWib = now()->setTimezone('Asia/Jakarta')->toDateString();
        $start = $startDate ?: $todayWib;
        $end = $endDate ?: $todayWib;
        $transId = (string) time();

        // 1. Cek info perangkat (opsional, untuk memverifikasi Cloud ID & webhook_url)
        $deviceInfo = null;
        try {
            $devResp = Http::withToken($apiKey)
                ->timeout(8)
                ->acceptJson()
                ->post('https://developer.fingerspot.io/api/get_device', [
                    'trans_id' => $transId,
                    'cloud_id' => $resolvedCloudId,
                ]);
            if ($devResp->successful()) {
                $deviceInfo = $devResp->json('data') ?? $devResp->json();
            }
        } catch (\Throwable $e) {
            Log::warning('Fingerspot get_device failed: ' . $e->getMessage());
        }

        // 2. Tarik log absensi (get_attlog)
        try {
            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->acceptJson()
                ->post('https://developer.fingerspot.io/api/get_attlog', [
                    'trans_id'   => $transId,
                    'cloud_id'   => $resolvedCloudId,
                    'start_date' => $start,
                    'end_date'   => $end,
                ]);

            if (!$response->successful()) {
                return [
                    'success'     => false,
                    'status_code' => $response->status(),
                    'message'     => 'Gagal menghubungi API developer.fingerspot.io: HTTP ' . $response->status(),
                    'device_info' => $deviceInfo,
                    'raw'         => substr($response->body(), 0, 400),
                ];
            }

            $json = $response->json();

            if (isset($json['success']) && $json['success'] === false) {
                return [
                    'success'     => false,
                    'message'     => 'Respon API Fingerspot: ' . ($json['message'] ?? json_encode($json)),
                    'device_info' => $deviceInfo,
                ];
            }

            $rawLogs = $json['data'] ?? [];
            if (!is_array($rawLogs)) {
                $rawLogs = [];
            }

            $records = [];
            foreach ($rawLogs as $item) {
                if (!is_array($item)) {
                    continue;
                }
                if ($mapped = $this->normalizeScanItem($item)) {
                    $records[] = $mapped;
                }
            }

            // Urutkan kronologis agar scan pertama tiap sesi dikunci duluan
            $records = $this->sortRecordsChronologically($records);

            $inserted = 0;
            $lockedDuplicates = 0;
            $unmatchedPins = [];
            $details = [];

            foreach ($records as $rec) {
                $res = $this->processAttendanceRecord(
                    $rec['pin'],
                    $rec['scan_date'],
                    $rec['verify'],
                    $rec['status_scan']
                );
                $details[] = $res;

                if (($res['status'] ?? '') === 'success') {
                    $inserted++;
                } elseif (in_array($res['status'] ?? '', ['duplicate', 'excused'])) {
                    $lockedDuplicates++;
                } elseif (($res['status'] ?? '') === 'not_found') {
                    $unmatchedPins[] = $rec['pin'];
                }
            }

            $syncAt = now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s');
            $configToSave = [
                'cloud_id'          => $resolvedCloudId,
                'last_sync_at'      => $syncAt,
                'last_sync_summary' => [
                    'total'             => count($records),
                    'inserted'          => $inserted,
                    'duplicates_locked' => $lockedDuplicates,
                    'unmatched_count'   => count(array_unique($unmatchedPins)),
                    'range'             => "{$start} s/d {$end}",
                ],
                'last_device_info'  => $deviceInfo,
                'last_attlog_raw'   => [
                    'count'  => count($rawLogs),
                    'sample' => array_slice($rawLogs, 0, 3),
                ],
            ];
            if (is_array($deviceInfo)) {
                if (isset($deviceInfo['webhook_url'])) {
                    $configToSave['cloud_webhook_url'] = $deviceInfo['webhook_url'];
                }
                if (isset($deviceInfo['device_name'])) {
                    $configToSave['device_name'] = $deviceInfo['device_name'];
                }
            }
            $this->saveCloudConfig($configToSave);

            return [
                'success'           => true,
                'cloud_id'          => $resolvedCloudId,
                'range'             => "{$start} s/d {$end}",
                'total_logs'        => count($records),
                'inserted'          => $inserted,
                'duplicates_locked' => $lockedDuplicates,
                'unmatched_pins'    => array_values(array_unique($unmatchedPins)),
                'device_info'       => $deviceInfo,
                'message'           => "Sinkronisasi selesai ({$start} s/d {$end}): {$inserted} presensi baru masuk, {$lockedDuplicates} sudah terkunci/tercatat, " . count(array_unique($unmatchedPins)) . ' PIN belum terdaftar.',
                'details'           => $details,
            ];
        } catch (\Throwable $e) {
            Log::error('Fingerspot Cloud Sync Error: ' . $e->getMessage());

            return [
                'success'     => false,
                'device_info' => $deviceInfo,
                'message'     => 'Koneksi ke server Fingerspot gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Memproses satu record absensi siswa atau guru dengan penguncian scan pertama
     */
    public function processAttendanceRecord(
        string $pin,
        ?string $scanDate = null,
        $verifyMode = 1,
        $statusScan = 0,
        ?string $forcedTipe = null
    ): array {
        $pinVariants = $this->buildPinVariants($pin);

        // 1. Cari di data biometrik eksplisit Siswa terlebih dahulu, lalu Guru, lalu NISN/NIS
        $student = $this->findStudentByBiometricPin($pinVariants);

        if (!$student) {
            $teacher = $this->findTeacherByBiometricPin($pinVariants);
            if ($teacher) {
                return $this->processTeacherScan($teacher, $scanDate, $verifyMode, $statusScan);
            }

            // Fallback ke NISN / NIS Siswa
            $student = $this->findStudentByAcademicPin($pinVariants);
        }

        if (!$student) {
            // Fallback ke NIY / NUPTK Guru
            $teacher = $this->findTeacherByEmployeePin($pinVariants);
            if ($teacher) {
                return $this->processTeacherScan($teacher, $scanDate, $verifyMode, $statusScan);
            }

            $this->recordUnmatchedPin($pin, $pinVariants);

            return [
                'status'  => 'not_found',
                'pin'     => $pin,
                'message' => "Pengguna dengan PIN / ID Finger '{$pin}' belum didaftarkan di Data Siswa maupun Data Guru. Silakan isi angka '{$pin}' ke kolom 'ID Finger' di Data Siswa.",
            ];
        }

        $now = $this->parseScanTimestamp($scanDate);
        $today = $now->toDateString();
        $metode = $this->mapVerifyMode($verifyMode);
        $tipe = $forcedTipe ?: $this->deteksiTipe($now, $statusScan);
        $rule = self::SCHEDULE_RULES[$tipe] ?? self::SCHEDULE_RULES['masuk'];

        $institutionId = $student->institution_id ?: ($student->classroom?->institution_id ?: 2);
        if ($institutionId == 1) {
            $institutionId = 2; // SMPI Baitul Makmur
        }

        $systemUserId = auth()->id()
            ?? User::withoutGlobalScopes()->where('institution_id', 2)->value('id')
            ?? User::withoutGlobalScopes()->value('id')
            ?? 1;

        // Eksekusi pengecekan + penyimpanan di dalam lock atomik per (siswa, tanggal, sesi)
        // agar dua scan berdekatan atau webhook paralel tidak pernah menghasilkan data ganda
        $lockKey = "fs_lock_{$student->id}_{$today}_{$rule['lock_group']}";

        return $this->runWithLock($lockKey, function () use ($student, $now, $today, $metode, $tipe, $rule, $institutionId, $systemUserId, $pin) {
            // Cek apakah siswa sudah tercatat Sakit / Izin / Alpa hari ini oleh Guru Piket
            $excusedToday = DailyAttendance::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('tanggal', $today)
                ->whereIn('status', ['sakit', 'izin', 'alpa'])
                ->first();

            if ($excusedToday && $tipe !== 'masuk') {
                Log::info("Fingerspot: Siswa {$student->name} berstatus {$excusedToday->status}, scan {$tipe} diabaikan.");
                return [
                    'status'  => 'excused',
                    'action'  => 'ignored',
                    'student' => $student->name,
                    'tipe'    => $tipe,
                    'message' => "Siswa {$student->name} sudah tercatat {$excusedToday->status} hari ini oleh Guru Piket",
                ];
            }

            // PENGUNCIAN SCAN PERTAMA: Cek apakah sudah ada record di sesi yang sama hari ini
            $existingSession = DailyAttendance::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('tanggal', $today)
                ->whereIn('tipe_absen', $rule['check_tipes'])
                ->first();

            if ($existingSession) {
                // Jika sudah tercatat sakit/izin/alpa di pagi hari, laporkan status excused
                if ($excusedToday && $tipe === 'masuk') {
                    return [
                        'status'  => 'excused',
                        'action'  => 'ignored',
                        'locked'  => true,
                        'student' => $student->name,
                        'tipe'    => $tipe,
                        'message' => "Siswa {$student->name} sudah tercatat {$excusedToday->status} hari ini oleh Guru Piket",
                    ];
                }

                return [
                    'status'          => 'duplicate',
                    'locked'          => true,
                    'student'         => $student->name,
                    'tipe'            => $tipe,
                    'first_scan_time' => $existingSession->waktu_kedatangan,
                    'message'         => "Terkunci: {$student->name} sudah absen {$rule['label']} pada jam " . ($existingSession->waktu_kedatangan ?? '-') . ' (1x per waktu)',
                ];
            }

            // Cek halangan siswi (untuk sholat dhuhur & sholat ashar)
            if ($student->jenis_kelamin === 'P' && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar'])) {
                if (StudentHalangan::isHalangan($student->id, $today)) {
                    DailyAttendance::withoutGlobalScopes()->create([
                        'student_id'       => $student->id,
                        'institution_id'   => $institutionId,
                        'teacher_id'       => $systemUserId,
                        'tanggal'          => $today,
                        'tipe_absen'       => $tipe,
                        'metode_absen'     => $metode,
                        'waktu_kedatangan' => $now->format('H:i:s'),
                        'status'           => 'halangan',
                        'is_late'          => false,
                        'keterangan'       => "Siswi halangan (Udzur Syar'i) - scan {$now->format('H:i')} via Fingerspot Revo W230N",
                    ]);

                    $this->recordRecentScan($student->name, $tipe, $metode, $now->format('H:i:s'), 'halangan', false);

                    return [
                        'status'  => 'success',
                        'action'  => 'halangan',
                        'student' => $student->name,
                        'tipe'    => $tipe,
                        'message' => "Siswi {$student->name} tercatat Halangan untuk {$rule['label']}",
                    ];
                }
            }

            // Evaluasi ketepatan waktu sesuai jadwal resmi (06:50, 12:15, 15:15)
            $timing = $this->evaluateAttendanceTiming($tipe, $now, $metode);

            // Simpan presensi (1 scan = tepat 1 record terkunci untuk sesi ini)
            DailyAttendance::withoutGlobalScopes()->create([
                'student_id'       => $student->id,
                'institution_id'   => $institutionId,
                'teacher_id'       => $systemUserId,
                'tanggal'          => $today,
                'tipe_absen'       => $tipe,
                'metode_absen'     => $metode,
                'waktu_kedatangan' => $now->format('H:i:s'),
                'status'           => 'hadir',
                'is_late'          => $timing['is_late'],
                'keterangan'       => $timing['keterangan'],
            ]);

            $this->recordRecentScan($student->name, $tipe, $metode, $now->format('H:i:s'), 'hadir', $timing['is_late']);

            Log::info('Fingerspot: Presensi siswa tersimpan', [
                'student' => $student->name,
                'pin'     => $pin,
                'tipe'    => $tipe,
                'jam'     => $now->format('H:i:s'),
                'is_late' => $timing['is_late'],
            ]);

            return [
                'status'     => 'success',
                'student'    => $student->name,
                'nis'        => $student->nis,
                'kelas'      => $student->classroom->name ?? '-',
                'tipe'       => $tipe,
                'label'      => $rule['label'],
                'metode'     => $metode,
                'jam'        => $now->format('H:i'),
                'is_late'    => $timing['is_late'],
                'keterangan' => $timing['keterangan'],
            ];
        });
    }

    /**
     * Evaluasi keterlambatan & buat catatan keterangan otomatis untuk semua sesi:
     * - Masuk: batas 06:50
     * - Sholat Dhuhur: batas 12:15
     * - Sholat Ashar & Pulang: batas 15:15
     */
    private function evaluateAttendanceTiming(string $tipe, Carbon $waktu, string $metode): array
    {
        $rule = self::SCHEDULE_RULES[$tipe] ?? self::SCHEDULE_RULES['masuk'];
        $scanMinutes = ($waktu->hour * 60) + $waktu->minute;
        $limitMinutes = ($rule['limit_hour'] * 60) + $rule['limit_minute'];
        $isLate = $scanMinutes > $limitMinutes;
        $jamScan = $waktu->format('H:i');

        if ($isLate) {
            $lateBy = $scanMinutes - $limitMinutes;
            $keterangan = "Terlambat {$rule['label']} (+{$lateBy} menit, scan {$jamScan}, batas {$rule['limit_str']}) via Fingerspot ({$metode})";
        } else {
            $keterangan = "{$rule['label']} - Tepat Waktu ({$jamScan}) via Fingerspot ({$metode})";
        }

        return [
            'is_late'    => $isLate,
            'keterangan' => $keterangan,
        ];
    }

    /**
     * Deteksi Sesi / Tipe Absen berdasarkan waktu scan WIB:
     * - 00:00 - 11:29 : Masuk Pagi (Normal: 06.00 - 06.50, > 06.50 = Terlambat)
     * - 11:30 - 14:29 : Sholat Dhuhur (Normal: 12.00 - 12.15, > 12.15 = Terlambat)
     * - 14:30 - 23:59 : Sholat Ashar & Pulang (Normal: 15.00 - 15.15, > 15.15 = Terlambat)
     */
    private function deteksiTipe(Carbon $waktu, $statusScan = null): string
    {
        $totalMinutes = ($waktu->hour * 60) + $waktu->minute;

        // Sebelum 11:30 WIB -> Sesi Masuk Pagi
        if ($totalMinutes < (11 * 60 + 30)) {
            return 'masuk';
        }

        // 11:30 s/d 14:29 WIB -> Sesi Sholat Dhuhur
        if ($totalMinutes < (14 * 60 + 30)) {
            return 'sholat_dhuhur';
        }

        // 14:30 s/d 23:59 WIB -> Sesi Sholat Ashar & Pulang
        return 'sholat_ashar';
    }

    /**
     * Proses scan guru / pegawai (mengunci scan pertama hari ini)
     */
    protected function processTeacherScan(User $teacher, ?string $scanDate, $verifyMode, $statusScan): array
    {
        $now = $this->parseScanTimestamp($scanDate);
        $metode = $this->mapVerifyMode($verifyMode);
        $today = $now->toDateString();

        $institutionId = $teacher->institution_id ?? 2;
        if ($institutionId == 1) {
            $institutionId = 2;
        }

        $lockKey = "fs_lock_teacher_{$teacher->id}_{$today}";

        return $this->runWithLock($lockKey, function () use ($teacher, $now, $today, $institutionId, $metode) {
            $existing = TeacherAttendance::withoutGlobalScopes()
                ->where('teacher_id', $teacher->id)
                ->where('tanggal', $today)
                ->first();

            if ($existing) {
                return [
                    'status'  => 'duplicate',
                    'locked'  => true,
                    'action'  => 'teacher_attendance',
                    'teacher' => $teacher->name,
                    'message' => "Terkunci: {$teacher->name} sudah absen masuk hari ini",
                ];
            }

            TeacherAttendance::withoutGlobalScopes()->create([
                'teacher_id'     => $teacher->id,
                'institution_id' => $institutionId,
                'tanggal'        => $today,
                'status'         => 'masuk',
                'created_at'     => $now,
            ]);

            $this->recordRecentScan($teacher->name, 'guru_masuk', $metode, $now->format('H:i:s'), 'masuk', false);

            return [
                'status'  => 'success',
                'action'  => 'teacher_attendance',
                'teacher' => $teacher->name,
                'time'    => $now->toDateTimeString(),
                'metode'  => $metode,
            ];
        });
    }

    /**
     * Cari siswa berdasarkan kolom biometrik eksplisit (tanpa menabrak ID database acak)
     */
    private function findStudentByBiometricPin(array $pinVariants): ?Student
    {
        return Student::withoutGlobalScopes()
            ->with(['classroom' => fn ($q) => $q->withoutGlobalScopes()])
            ->where(function ($query) use ($pinVariants) {
                $hasCondition = false;
                foreach (['id_finger', 'fingerspot_pin', 'hikvision_employee_no', 'nfc_card_no'] as $col) {
                    if (Schema::hasColumn('students', $col)) {
                        $hasCondition ? $query->orWhereIn($col, $pinVariants) : $query->whereIn($col, $pinVariants);
                        $hasCondition = true;
                    }
                }
                if (!$hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->first();
    }

    private function findStudentByAcademicPin(array $pinVariants): ?Student
    {
        return Student::withoutGlobalScopes()
            ->with(['classroom' => fn ($q) => $q->withoutGlobalScopes()])
            ->where(function ($query) use ($pinVariants) {
                $query->whereIn('nis', $pinVariants);
                if (Schema::hasColumn('students', 'nisn')) {
                    $query->orWhereIn('nisn', $pinVariants);
                }
            })
            ->first();
    }

    private function findTeacherByBiometricPin(array $pinVariants): ?User
    {
        return User::withoutGlobalScopes()
            ->where(function ($query) use ($pinVariants) {
                $hasCondition = false;
                foreach (['id_finger', 'fingerspot_pin'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $hasCondition ? $query->orWhereIn($col, $pinVariants) : $query->whereIn($col, $pinVariants);
                        $hasCondition = true;
                    }
                }
                if (!$hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->first();
    }

    private function findTeacherByEmployeePin(array $pinVariants): ?User
    {
        return User::withoutGlobalScopes()
            ->where(function ($query) use ($pinVariants) {
                $hasCondition = false;
                foreach (['no_induk_yayasan', 'nuptk'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $hasCondition ? $query->orWhereIn($col, $pinVariants) : $query->whereIn($col, $pinVariants);
                        $hasCondition = true;
                    }
                }
                if (!$hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->first();
    }

    private function buildPinVariants(string $pin): array
    {
        $clean = trim($pin);
        $trimmedZero = ltrim($clean, '0');
        $variants = [
            $clean,
            $trimmedZero === '' ? '0' : $trimmedZero,
            (is_numeric($clean) && strlen($clean) < 10) ? str_pad($clean, 10, '0', STR_PAD_LEFT) : null,
            (is_numeric($clean) && strlen($clean) < 4)  ? str_pad($clean, 4, '0', STR_PAD_LEFT) : null,
        ];

        return array_values(array_unique(array_filter(
            $variants,
            fn ($v) => $v !== null && $v !== ''
        )));
    }

    private function parseScanTimestamp(?string $scanDate): Carbon
    {
        try {
            return $scanDate
                ? Carbon::parse($scanDate, 'Asia/Jakarta')->setTimezone('Asia/Jakarta')
                : Carbon::now('Asia/Jakarta');
        } catch (\Throwable $e) {
            return Carbon::now('Asia/Jakarta');
        }
    }

    /**
     * Jalankan closure di dalam atomic lock dengan fallback aman untuk segala driver cache
     */
    private function runWithLock(string $key, \Closure $callback): array
    {
        try {
            return Cache::lock($key, 5)->block(3, $callback);
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    private function getConfiguredApiKey(): ?string
    {
        $config = $this->getCloudConfig();
        return config('services.fingerspot.api_key')
            ?: env('FINGERSPOT_API_KEY')
            ?: ($config['api_key'] ?? '94PM7O0GL1A4M1MR');
    }

    private function extractIncomingApiKey(Request $request): ?string
    {
        $key = $request->header('X-API-KEY')
            ?? $request->bearerToken()
            ?? $request->header('Authorization')
            ?? $request->header('api-key')
            ?? $request->header('apikey')
            ?? $request->header('key')
            ?? $request->query('api_key')
            ?? $request->query('token')
            ?? $request->query('key')
            ?? $request->input('api_key')
            ?? $request->input('token')
            ?? $request->input('key');

        return $key ? trim(str_ireplace('Bearer ', '', $key)) : null;
    }

    public function getCloudConfig(): array
    {
        $stored = $this->readJsonFile(self::CONFIG_FILE, []);
        return array_merge([
            'api_key'  => config('services.fingerspot.api_key', env('FINGERSPOT_API_KEY')) ?: '94PM7O0GL1A4M1MR',
            'cloud_id' => env('FINGERSPOT_CLOUD_ID') ?: config('services.fingerspot.cloud_id'),
        ], $stored);
    }

    public function saveCloudConfig(array $data): void
    {
        $current = $this->readJsonFile(self::CONFIG_FILE, []);
        $merged = array_merge($current, $data);
        $this->writeJsonFile(self::CONFIG_FILE, $merged);
    }

    private function autoRememberCloudId(string $cloudId): void
    {
        try {
            $config = $this->readJsonFile(self::CONFIG_FILE, []);
            if (($config['cloud_id'] ?? null) !== $cloudId) {
                $this->saveCloudConfig(['cloud_id' => $cloudId]);
            }
        } catch (\Throwable $e) {}
    }

    private function recordRawWebhookHit(Request $request): void
    {
        try {
            $logs = $this->readJsonFile(self::WEBHOOK_LOG_FILE, []);
            array_unshift($logs, [
                'time'   => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                'ip'     => $request->ip(),
                'method' => $request->method(),
                'path'   => '/' . ltrim($request->path(), '/'),
                'keys'   => array_keys($request->all()),
                'raw'    => substr((string) $request->getContent(), 0, 500),
            ]);
            $this->writeJsonFile(self::WEBHOOK_LOG_FILE, array_slice($logs, 0, 25));
        } catch (\Throwable $e) {}
    }

    private function recordUnmatchedPin(string $pin, array $variants): void
    {
        try {
            $unmatched = $this->readJsonFile('fingerspot_unmatched_pins.json', Cache::get('fingerspot_last_unmatched_pins', []));
            array_unshift($unmatched, [
                'pin'              => $pin,
                'variants_checked' => $variants,
                'time'             => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);
            $sliced = array_slice($unmatched, 0, 20);
            Cache::put('fingerspot_last_unmatched_pins', $sliced, now()->addDays(7));
            $this->writeJsonFile('fingerspot_unmatched_pins.json', $sliced);
        } catch (\Throwable $e) {}

        Log::warning("Fingerspot: PIN/ID Finger '{$pin}' tidak ditemukan di database siswa maupun guru.", [
            'variants_checked' => $variants,
        ]);
    }

    private function recordRecentScan(string $name, string $tipe, string $metode, string $time, string $status, bool $isLate): void
    {
        try {
            $scans = $this->readJsonFile('fingerspot_recent_scans.json', Cache::get('fingerspot_recent_scans', []));
            array_unshift($scans, [
                'nama'    => $name,
                'tipe'    => $tipe,
                'metode'  => $metode,
                'jam'     => $time,
                'status'  => $status,
                'is_late' => $isLate,
                'date'    => now()->setTimezone('Asia/Jakarta')->format('Y-m-d'),
            ]);
            $sliced = array_slice($scans, 0, 30);
            Cache::put('fingerspot_recent_scans', $sliced, now()->addDays(7));
            $this->writeJsonFile('fingerspot_recent_scans.json', $sliced);
        } catch (\Throwable $e) {}
    }

    private function readJsonFile(string $filename, array $default = []): array
    {
        try {
            $path = storage_path('app/' . $filename);
            if (!file_exists($path)) {
                return $default;
            }
            $decoded = json_decode((string) file_get_contents($path), true);
            return is_array($decoded) ? $decoded : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function writeJsonFile(string $filename, array $data): void
    {
        try {
            $dir = storage_path('app');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents(storage_path('app/' . $filename), json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {}
    }

    private function mapVerifyMode($mode): string
    {
        $modeStr = strtolower((string) $mode);

        return match ($modeStr) {
            '1', 'fingerprint', 'fp', 'finger' => 'fingerprint',
            '2', 'password', 'pin', 'pwd'      => 'password',
            '3', 'card', 'rfid', 'nfc'         => 'nfc_card',
            '4', 'face', 'faceid'              => 'face_recognition',
            '6', 'vein'                        => 'vein',
            '7', 'qr', 'qrcode'                => 'scan_barcode',
            default                            => 'fingerprint',
        };
    }
}
