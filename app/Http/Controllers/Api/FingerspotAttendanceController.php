<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\DailyAttendance;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controller Integrasi Mesin Absensi Fingerspot Revo W230N
 * 
 * Mendukung 3 Mode Komunikasi:
 * 1. Webhook Fingerspot.io (Cloud Developer API: developer.fingerspot.io)
 *    Endpoint: POST /api/fingerspot/attendance atau /api/fingerspot/webhook
 *    Format: JSON event dengan type "attlog", atau stringified JSON di form-data
 * 
 * 2. Direct ADMS / Push Server (Protokol IClock langsung dari mesin via WiFi/LAN)
 *    Endpoint: GET/POST /iclock/cdata & /iclock/getrequest
 *    Format: Raw text tab-separated ATTLOG
 * 
 * 3. Direct REST / HTTP Push / Test Scan (Browser / Script Lokal / POST biasa)
 *    Endpoint: POST/GET /api/fingerspot/attendance
 *    Endpoint: GET      /api/fingerspot/test-scan?pin=1
 */
class FingerspotAttendanceController extends Controller
{
    /**
     * Endpoint utama untuk menerima webhook / push log dari Fingerspot
     * POST/GET /api/fingerspot/attendance
     * POST     /api/fingerspot/webhook
     */
    public function receive(Request $request)
    {
        $rawContent = $request->getContent();
        Log::channel('daily')->info('Fingerspot Event Received', [
            'ip'          => $request->ip(),
            'method'      => $request->method(),
            'headers'     => $request->headers->all(),
            'query'       => $request->query(),
            'post'        => $request->post(),
            'body_length' => strlen($rawContent),
        ]);

        // Validasi API Key jika dikonfigurasi di .env atau config
        $configuredKey = config('services.fingerspot.api_key', env('FINGERSPOT_API_KEY')) ?: '94PM7O0GL1A4M1MR';
        $incomingKey = $request->header('X-API-KEY')
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

        if ($incomingKey) {
            $incomingKey = trim(str_ireplace('Bearer ', '', $incomingKey));
        }

        // Jika webhook menyertakan API Key tapi tidak cocok, tolak dengan 401 Unauthorized
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
                Log::info('Fingerspot Webhook: Tidak ada data log presensi (PIN) dalam request.', [
                    'all'     => $request->all(),
                    'content' => substr($rawContent, 0, 300),
                ]);

                return response()->json([
                    'status'  => 'ignored',
                    'message' => 'Tidak ada data log presensi (PIN) yang valid dalam request',
                ], 200);
            }

            // Proses setiap data log scan
            $results = [];
            foreach ($records as $rec) {
                if (empty($rec['pin'])) {
                    continue;
                }

                $res = $this->processAttendanceRecord(
                    (string) $rec['pin'],
                    $rec['scan_date'] ?? null,
                    $rec['verify'] ?? 1,
                    $rec['status_scan'] ?? 0
                );

                $results[] = $res;
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
     * Ekstrak array log scan dari berbagai format request Fingerspot
     */
    protected function extractRecordsFromRequest(Request $request): array
    {
        $payload = $request->all();
        $rawContent = $request->getContent();

        // Jika $payload kosong tapi rawContent ada, coba decode JSON
        if (empty($payload) && !empty($rawContent)) {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        // Jika 'data' dikirim sebagai JSON string (sering terjadi di webhook fingerspot.io form-data)
        if (isset($payload['data']) && is_string($payload['data'])) {
            $decodedData = json_decode($payload['data'], true);
            if (is_array($decodedData)) {
                $payload['data'] = $decodedData;
            }
        }

        $records = [];

        // Format 1: Format Resmi Webhook Fingerspot.io {"type": "attlog", "data": [...]}
        if (isset($payload['type']) && ($payload['type'] === 'attlog' || $payload['type'] === 'attendance')) {
            $rawRecords = $payload['data'] ?? [];
            if (is_array($rawRecords) && !isset($rawRecords[0])) {
                $rawRecords = [$rawRecords];
            }
            foreach ($rawRecords as $item) {
                if (!is_array($item)) continue;
                $records[] = [
                    'pin'         => $item['pin'] ?? $item['PIN'] ?? $item['user_id'] ?? $item['badgenumber'] ?? $item['enroll_id'] ?? null,
                    'scan_date'   => $item['scan_date'] ?? $item['scan'] ?? $item['date_time'] ?? $item['time'] ?? $item['timestamp'] ?? null,
                    'verify'      => $item['verify'] ?? $item['verif_mode'] ?? $item['verify_mode'] ?? 1,
                    'status_scan' => $item['status_scan'] ?? $item['status'] ?? 0,
                ];
            }
        }
        // Format 2: Array langsung dari daftar log scan [{"pin":"1",...}]
        elseif (isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $item) {
                if (!is_array($item)) continue;
                $records[] = [
                    'pin'         => $item['pin'] ?? $item['PIN'] ?? $item['user_id'] ?? $item['employee_no'] ?? $item['badgenumber'] ?? null,
                    'scan_date'   => $item['scan_date'] ?? $item['scan'] ?? $item['date_time'] ?? $item['time'] ?? $item['timestamp'] ?? null,
                    'verify'      => $item['verify'] ?? $item['verif_mode'] ?? $item['verify_mode'] ?? 1,
                    'status_scan' => $item['status_scan'] ?? $item['status'] ?? 0,
                ];
            }
        }
        // Format 3: Single record (JSON datar atau form-urlencoded atau query param)
        else {
            $pin = $payload['pin'] ?? $payload['PIN'] ?? $payload['user_id'] ?? $payload['employee_no'] ?? $payload['badgenumber'] ?? $payload['id_finger'] ?? null;
            if ($pin) {
                $records[] = [
                    'pin'         => $pin,
                    'scan_date'   => $payload['scan_date'] ?? $payload['scan'] ?? $payload['date_time'] ?? $payload['time'] ?? $payload['timestamp'] ?? null,
                    'verify'      => $payload['verify'] ?? $payload['verif_mode'] ?? $payload['verify_mode'] ?? 1,
                    'status_scan' => $payload['status_scan'] ?? $payload['status'] ?? 0,
                ];
            }
        }

        return $records;
    }

    /**
     * Handler untuk Protokol Direct ADMS / Push Server (IClock)
     * Digunakan ketika Mesin Revo W230N langsung diarahkan ke IP Server kita.
     * GET/POST /iclock/cdata
     */
    public function cdata(Request $request)
    {
        $sn = $request->query('SN') ?? $request->query('sn') ?? 'UNKNOWN';

        // 1. Handshake awal dari mesin (GET /iclock/cdata)
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

            return response($responseContent, 200)
                ->header('Content-Type', 'text/plain');
        }

        // 2. Mesin Push Data Log (POST /iclock/cdata)
        $rawBody = $request->getContent();
        $table = $request->query('table') ?? 'ATTLOG';

        Log::info("Fingerspot ADMS POST Received: SN={$sn}, table={$table}", [
            'body_length' => strlen($rawBody),
        ]);

        $processedCount = 0;

        // Parsing baris per baris log ATTLOG
        // Format umum: PIN \t Date_Time \t Status \t Verify \t WorkCode ...
        if (!empty($rawBody)) {
            $lines = explode("\n", str_replace("\r", "", $rawBody));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $cols = preg_split('/\t+|\s{2,}/', $line);
                if (count($cols) >= 2) {
                    $pin        = trim($cols[0]);
                    $scanDate   = trim($cols[1]);
                    $statusScan = isset($cols[2]) ? (int) $cols[2] : 0;
                    $verify     = isset($cols[3]) ? (int) $cols[3] : 1;

                    if (!empty($pin)) {
                        $this->processAttendanceRecord($pin, $scanDate, $verify, $statusScan);
                        $processedCount++;
                    }
                }
            }
        }

        // Mesin ZKTeco/Fingerspot mewajibkan respon text "OK" atau "OK: count"
        return response("OK: {$processedCount}", 200)
            ->header('Content-Type', 'text/plain');
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
     * Endpoint Cek Koneksi / Ping Diagnostik
     * GET /api/fingerspot/ping
     */
    public function ping()
    {
        $hasKey = !empty(config('services.fingerspot.api_key', env('FINGERSPOT_API_KEY')) ?: '94PM7O0GL1A4M1MR');

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
            ->limit(5)
            ->get(['id', 'name', 'nis', 'id_finger', 'fingerspot_pin'])
            ->map(fn ($s) => [
                'nama'      => $s->name,
                'id_finger' => $s->id_finger ?? $s->fingerspot_pin,
                'nis'       => $s->nis,
            ]);

        $recentScans = Cache::get('fingerspot_recent_scans', []);
        $unmatchedPins = Cache::get('fingerspot_last_unmatched_pins', []);

        return response()->json([
            'status'                      => 'online',
            'device_target'               => 'Fingerspot Revo W230N',
            'server'                      => 'SMPI Baitul Makmur',
            'time'                        => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s T'),
            'version'                     => '2.1',
            'api_key_configured'          => $hasKey,
            'students_with_finger_count'  => $studentsWithFinger,
            'teachers_with_finger_count'  => $teachersWithFinger,
            'sample_registered_students'  => $sampleStudents,
            'recent_scans'                => array_slice($recentScans, 0, 5),
            'last_unmatched_pins'         => array_slice($unmatchedPins, 0, 5),
            'test_scan_url'               => url('/api/fingerspot/test-scan?pin=1'),
            'endpoints'                   => [
                'cloud_webhook' => url('/api/fingerspot/attendance'),
                'cloud_alias'   => url('/api/fingerspot/webhook'),
                'adms_push'     => url('/iclock/cdata'),
            ],
        ]);
    }

    /**
     * Endpoint Cepat untuk Menguji Scan Presensi via Browser
     * GET /api/fingerspot/test-scan?pin=1&tipe=auto
     */
    public function testScan(Request $request)
    {
        $pin = $request->query('pin') ?? $request->input('pin');
        $tipe = $request->query('tipe') ?? $request->input('tipe');
        $time = $request->query('time') ?? $request->input('time');

        if (empty($pin)) {
            // Berikan saran PIN yang sudah terdaftar
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
     * Memproses satu record absensi siswa atau guru
     */
    public function processAttendanceRecord(
        string $pin,
        ?string $scanDate = null,
        $verifyMode = 1,
        $statusScan = 0,
        ?string $forcedTipe = null
    ): array {
        $cleanPin = trim($pin);

        // Varian PIN untuk pencocokan fleksibel (mendukung integer, leading-zeros, NISN, NIS, ID Finger)
        $trimmedPin = ltrim($cleanPin, '0');
        $pinVariants = array_values(array_unique(array_filter([
            $cleanPin,
            $trimmedPin ?: '0',
            (is_numeric($cleanPin) && strlen($cleanPin) < 10) ? str_pad($cleanPin, 10, '0', STR_PAD_LEFT) : null,
            (is_numeric($cleanPin) && strlen($cleanPin) < 4)  ? str_pad($cleanPin, 4, '0', STR_PAD_LEFT) : null,
            (is_numeric($cleanPin) && strlen($cleanPin) < 5)  ? str_pad($cleanPin, 5, '0', STR_PAD_LEFT) : null,
            (is_numeric($cleanPin) && strlen($cleanPin) < 6)  ? str_pad($cleanPin, 6, '0', STR_PAD_LEFT) : null,
        ])));

        // 1. Cari Siswa tanpa terhalang global scope institution
        $student = Student::withoutGlobalScopes()
            ->with(['classroom' => fn ($q) => $q->withoutGlobalScopes()])
            ->where(function ($query) use ($pinVariants) {
                if (Schema::hasColumn('students', 'id_finger')) {
                    $query->whereIn('id_finger', $pinVariants);
                }
                if (Schema::hasColumn('students', 'fingerspot_pin')) {
                    $query->orWhereIn('fingerspot_pin', $pinVariants);
                }
                if (Schema::hasColumn('students', 'nisn')) {
                    $query->orWhereIn('nisn', $pinVariants);
                }
                $query->orWhereIn('nis', $pinVariants);

                if (Schema::hasColumn('students', 'hikvision_employee_no')) {
                    $query->orWhereIn('hikvision_employee_no', $pinVariants);
                }
                if (Schema::hasColumn('students', 'nfc_card_no')) {
                    $query->orWhereIn('nfc_card_no', $pinVariants);
                }
                if (is_numeric($pinVariants[0])) {
                    $query->orWhere('id', (int) $pinVariants[0]);
                }
            })
            ->first();

        // 2. Jika tidak ditemukan pada siswa, cek pada Guru / Pegawai (User)
        if (!$student) {
            $teacher = User::withoutGlobalScopes()
                ->where(function ($query) use ($pinVariants) {
                    if (Schema::hasColumn('users', 'id_finger')) {
                        $query->whereIn('id_finger', $pinVariants);
                    }
                    if (Schema::hasColumn('users', 'fingerspot_pin')) {
                        $query->orWhereIn('fingerspot_pin', $pinVariants);
                    }
                    if (Schema::hasColumn('users', 'no_induk_yayasan')) {
                        $query->orWhereIn('no_induk_yayasan', $pinVariants);
                    }
                    if (Schema::hasColumn('users', 'nuptk')) {
                        $query->orWhereIn('nuptk', $pinVariants);
                    }
                    if (Schema::hasColumn('users', 'nik')) {
                        $query->orWhereIn('nik', $pinVariants);
                    }
                    if (is_numeric($pinVariants[0])) {
                        $query->orWhere('id', (int) $pinVariants[0]);
                    }
                })->first();

            if ($teacher) {
                try {
                    $now = $scanDate ? Carbon::parse($scanDate)->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
                } catch (\Throwable $e) {
                    $now = Carbon::now('Asia/Jakarta');
                }
                $metode = $this->mapVerifyMode($verifyMode);
                return $this->processTeacherAttendance($teacher, $now, $metode, $statusScan);
            }

            // Simpan ke Cache Unmatched PINs untuk memudahkan diagnosa guru / admin
            $unmatched = Cache::get('fingerspot_last_unmatched_pins', []);
            array_unshift($unmatched, [
                'pin'              => $pin,
                'variants_checked' => $pinVariants,
                'time'             => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);
            Cache::put('fingerspot_last_unmatched_pins', array_slice($unmatched, 0, 20), now()->addDays(7));

            Log::warning("Fingerspot: PIN/ID Finger '{$pin}' tidak terdaftar di database siswa maupun guru.", [
                'variants_checked' => $pinVariants,
            ]);

            return [
                'status'  => 'not_found',
                'pin'     => $pin,
                'message' => "Pengguna dengan PIN / ID Finger '{$pin}' belum didaftarkan di Data Siswa maupun Data Guru. Silakan masukkan angka '{$pin}' ke kolom 'ID Finger' di Data Siswa.",
            ];
        }

        // 3. Parse waktu scan (WIB)
        try {
            $now = $scanDate ? Carbon::parse($scanDate)->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        } catch (\Throwable $e) {
            $now = Carbon::now('Asia/Jakarta');
        }
        $today = $now->toDateString();

        // 4. Tentukan metode verifikasi
        $metode = $this->mapVerifyMode($verifyMode);

        // 5. Deteksi tipe absen berdasarkan slot jam sekolah SMPI Baitul Makmur (atau tipe yang dipaksa)
        $tipe = $forcedTipe ?: $this->deteksiTipe($now, $statusScan);

        // 6. Pastikan institution_id selalu mengarah ke unit SMP (ID = 2) dan bukan Yayasan (1)
        $institutionId = $student->institution_id ?: ($student->classroom?->institution_id ?: 2);
        if ($institutionId == 1) {
            $institutionId = 2; // SMPI Baitul Makmur
        }

        $systemUserId = auth()->id() ?? User::withoutGlobalScopes()->where('institution_id', 2)->value('id') ?? 1;

        // 7. Cek halangan siswi (untuk waktu sholat dhuhur & ashar)
        if ($student->jenis_kelamin === 'P' && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar'])) {
            if (StudentHalangan::isHalangan($student->id, $today)) {
                DailyAttendance::withoutGlobalScopes()->firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'tanggal'    => $today,
                        'tipe_absen' => $tipe,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'teacher_id'     => $systemUserId,
                        'metode_absen'   => $metode,
                        'status'         => 'halangan',
                        'is_late'        => false,
                        'keterangan'     => "Siswi halangan (Udzur Syar'i) - via Fingerspot Revo W230N",
                    ]
                );

                $this->recordRecentScan($student->name, $tipe, $metode, $now->format('H:i:s'), 'halangan');

                Log::info("Fingerspot: Siswi halangan dicatat ({$student->name} - {$tipe})");
                return [
                    'status'  => 'success',
                    'action'  => 'halangan',
                    'student' => $student->name,
                    'tipe'    => $tipe,
                    'message' => "Siswi {$student->name} tercatat Halangan untuk {$tipe}",
                ];
            }
        }

        // 8. Cek apakah siswa sudah tercatat Sakit, Izin, atau Alpa hari ini oleh Guru Piket
        $excusedToday = DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('tanggal', $today)
            ->whereIn('status', ['sakit', 'izin', 'alpa'])
            ->first();

        if ($excusedToday && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar', 'pulang'])) {
            Log::info("Fingerspot: Siswa {$student->name} tercatat {$excusedToday->status} hari ini oleh Guru Piket, scan {$tipe} diabaikan agar status izin tidak tertimpa.");
            return [
                'status'  => 'excused',
                'action'  => 'ignored',
                'student' => $student->name,
                'tipe'    => $tipe,
                'message' => "Siswa {$student->name} sudah tercatat {$excusedToday->status} hari ini oleh Guru Piket",
            ];
        }

        // 9. Cek apakah sudah ada catatan absen pada tipe & tanggal yang sama
        $exists = DailyAttendance::withoutGlobalScopes()
            ->where('student_id', $student->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', $tipe)
            ->first();

        if ($exists) {
            return [
                'status'  => 'duplicate',
                'student' => $student->name,
                'tipe'    => $tipe,
                'message' => "Siswa {$student->name} sudah tercatat untuk {$tipe} hari ini pada jam {$exists->waktu_kedatangan}",
            ];
        }

        // 10. Hitung Keterlambatan (hanya untuk absen masuk pagi)
        $isLate = false;
        $keterangan = ucfirst(str_replace('_', ' ', $tipe)) . " via Fingerspot ({$metode})";
        if ($tipe === 'masuk') {
            $batas = $now->copy()->setHour(7)->setMinute(15)->setSecond(0);
            $isLate = $now->greaterThan($batas);
            $keterangan = $isLate
                ? "Terlambat ({$now->format('H:i')}) via Fingerspot ({$metode})"
                : "Tepat Waktu via Fingerspot ({$metode})";
        }

        // 11. Simpan presensi utama
        $attendance = DailyAttendance::withoutGlobalScopes()->create([
            'student_id'       => $student->id,
            'institution_id'   => $institutionId,
            'teacher_id'       => $systemUserId,
            'tanggal'          => $today,
            'tipe_absen'       => $tipe,
            'metode_absen'     => $metode,
            'waktu_kedatangan' => in_array($tipe, ['masuk', 'sholat_dhuha', 'sholat_dhuhur', 'sholat_ashar']) ? $now->format('H:i:s') : null,
            'waktu_kepulangan' => $tipe === 'pulang' ? $now->format('H:i:s') : null,
            'status'           => 'hadir',
            'is_late'          => $isLate,
            'keterangan'       => $keterangan,
        ]);

        // 12. FITUR PINTAR INTEGRASI TOTAL:
        // Jika scan dilakukan siang hari (sholat_dhuhur / sholat_ashar / pulang) dan siswa BELUM punya presensi masuk pagi ini,
        // otomatis buatkan juga presensi masuk agar rekap "Kehadiran Harian" siswa tidak kosong/alpa!
        if (in_array($tipe, ['sholat_dhuhur', 'sholat_ashar', 'pulang'])) {
            $hasMasuk = DailyAttendance::withoutGlobalScopes()
                ->where('student_id', $student->id)
                ->where('tanggal', $today)
                ->where('tipe_absen', 'masuk')
                ->exists();

            if (!$hasMasuk) {
                DailyAttendance::withoutGlobalScopes()->create([
                    'student_id'       => $student->id,
                    'institution_id'   => $institutionId,
                    'teacher_id'       => $systemUserId,
                    'tanggal'          => $today,
                    'tipe_absen'       => 'masuk',
                    'metode_absen'     => $metode,
                    'waktu_kedatangan' => $now->format('H:i:s'),
                    'status'           => 'hadir',
                    'is_late'          => false,
                    'keterangan'       => "Hadir (Tercatat via Scan Siang {$tipe} Fingerspot)",
                ]);
                Log::info("Fingerspot: Otomatis mencatat presensi masuk pagi untuk {$student->name} dari scan siang ({$tipe})");
            }
        }

        $this->recordRecentScan($student->name, $tipe, $metode, $now->format('H:i:s'), 'hadir');

        Log::info("Fingerspot: Presensi berhasil disimpan", [
            'student' => $student->name,
            'pin'     => $pin,
            'tipe'    => $tipe,
            'metode'  => $metode,
            'jam'     => $now->format('H:i:s'),
            'is_late' => $isLate,
        ]);

        return [
            'status'   => 'success',
            'student'  => $student->name,
            'nis'      => $student->nis,
            'kelas'    => $student->classroom->name ?? '-',
            'tipe'     => $tipe,
            'metode'   => $metode,
            'jam'      => $now->format('H:i'),
            'is_late'  => $isLate,
        ];
    }

    /**
     * Catat log scan sukses ke Cache untuk monitoring live
     */
    protected function recordRecentScan(string $name, string $tipe, string $metode, string $time, string $status): void
    {
        try {
            $scans = Cache::get('fingerspot_recent_scans', []);
            array_unshift($scans, [
                'nama'   => $name,
                'tipe'   => $tipe,
                'metode' => $metode,
                'jam'    => $time,
                'status' => $status,
                'date'   => now()->setTimezone('Asia/Jakarta')->format('Y-m-d'),
            ]);
            Cache::put('fingerspot_recent_scans', array_slice($scans, 0, 30), now()->addDays(7));
        } catch (\Throwable $e) {}
    }

    /**
     * Proses pencatatan presensi kehadiran Guru / Pegawai dari mesin Fingerprint
     */
    protected function processTeacherAttendance(User $teacher, Carbon $now, string $metode, $statusScan = 0): array
    {
        $today = $now->toDateString();
        $institutionId = $teacher->institution_id ?? 2;
        if ($institutionId == 1) {
            $institutionId = 2; // SMP
        }

        // Cek atau buat presensi kehadiran guru hari ini
        $attendance = TeacherAttendance::withoutGlobalScopes()->firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'tanggal'    => $today,
            ],
            [
                'institution_id' => $institutionId,
                'status'         => 'hadir',
                'created_at'     => $now,
            ]
        );

        $this->recordRecentScan($teacher->name, 'guru_hadir', $metode, $now->format('H:i:s'), 'hadir');

        Log::info("Fingerspot: Presensi Guru Berhasil ({$teacher->name} - {$today} {$now->format('H:i')}) via {$metode}");

        return [
            'status'  => 'success',
            'action'  => 'teacher_attendance',
            'teacher' => $teacher->name,
            'time'    => $now->toDateTimeString(),
            'metode'  => $metode,
        ];
    }

    /**
     * Pemetaan Kode Verifikasi Fingerspot Revo W230N ke nama metode sistem
     * 1 = Sidik Jari (Fingerprint)
     * 2 = Password / PIN
     * 3 = Kartu RFID / NFC
     * 4 = Wajah (Face)
     * 6 = Vein
     * 7 = QR Code
     */
    private function mapVerifyMode($mode): string
    {
        $modeStr = strtolower((string) $mode);

        return match ($modeStr) {
            '1', 'fingerprint', 'fp', 'finger'  => 'fingerprint',
            '2', 'password', 'pin', 'pwd'       => 'password',
            '3', 'card', 'rfid', 'nfc'          => 'nfc_card',
            '4', 'face', 'faceid'               => 'face_recognition',
            '6', 'vein'                         => 'vein',
            '7', 'qr', 'qrcode'                 => 'scan_barcode',
            default                             => 'fingerprint',
        };
    }

    /**
     * Deteksi Tipe Absen berdasarkan Jam Sekolah SMPI Baitul Makmur:
     * - 05:00 - 11:14 : Absen Masuk Pagi
     * - 11:15 - 13:45 : Absensi Sholat Dhuhur Berjamaah
     * - 13:46 - 15:45 : Absensi Sholat Ashar Berjamaah
     * - >= 15:46      : Absen Kepulangan
     */
    private function deteksiTipe(Carbon $waktu, $statusScan = null): string
    {
        $jam = $waktu->hour * 60 + $waktu->minute;

        // < 11:15 (675 menit) -> Masuk Pagi
        if ($jam < (11 * 60 + 15)) {
            return 'masuk';
        }
        // 11:15 - 13:45 (825 menit) -> Sholat Dhuhur
        if ($jam <= (13 * 60 + 45)) {
            return 'sholat_dhuhur';
        }
        // 13:46 - 15:45 (945 menit) -> Sholat Ashar
        if ($jam <= (15 * 60 + 45)) {
            return 'sholat_ashar';
        }
        // >= 15:46 -> Pulang
        return 'pulang';
    }
}
