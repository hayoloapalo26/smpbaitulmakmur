<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\DailyAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Controller Integrasi Mesin Absensi Fingerspot Revo W230N
 * 
 * Mendukung 3 Mode Komunikasi:
 * 1. Webhook Fingerspot.io (Cloud Developer API: developer.fingerspot.io)
 *    Endpoint: POST /api/fingerspot/attendance atau /api/fingerspot/webhook
 *    Format: JSON event dengan type "attlog"
 * 
 * 2. Direct ADMS / Push Server (Protokol IClock langsung dari mesin via WiFi/LAN)
 *    Endpoint: GET/POST /iclock/cdata & /iclock/getrequest
 *    Format: Raw text tab-separated ATTLOG
 * 
 * 3. Direct REST / HTTP Push (Software EasyLink SDK / Script Lokal / POST biasa)
 *    Endpoint: POST /api/fingerspot/attendance
 *    Format: JSON atau Form Data {pin, scan_date, verify, status_scan}
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
        Log::channel('daily')->info('Fingerspot Event Received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $payload = $request->all();

            // Kumpulkan daftar log scan yang akan diproses
            $records = [];

            // Kasus 1: Format Resmi Webhook Fingerspot.io {"type": "attlog", "data": [...]}
            if (isset($payload['type']) && $payload['type'] === 'attlog') {
                $rawRecords = $payload['data'] ?? [];
                // Jika data berupa objek tunggal, bungkus jadi array
                if (is_array($rawRecords) && !isset($rawRecords[0])) {
                    $rawRecords = [$rawRecords];
                }
                foreach ($rawRecords as $item) {
                    $records[] = [
                        'pin'         => $item['pin'] ?? $item['PIN'] ?? $item['user_id'] ?? null,
                        'scan_date'   => $item['scan_date'] ?? $item['date_time'] ?? $item['time'] ?? null,
                        'verify'      => $item['verify'] ?? $item['verif_mode'] ?? 1,
                        'status_scan' => $item['status_scan'] ?? $item['status'] ?? 0,
                    ];
                }
            }
            // Kasus 2: Array langsung dari daftar log scan
            elseif (isset($payload[0]) && is_array($payload[0])) {
                foreach ($payload as $item) {
                    $records[] = [
                        'pin'         => $item['pin'] ?? $item['PIN'] ?? $item['user_id'] ?? $item['employee_no'] ?? null,
                        'scan_date'   => $item['scan_date'] ?? $item['date_time'] ?? $item['time'] ?? null,
                        'verify'      => $item['verify'] ?? $item['verif_mode'] ?? 1,
                        'status_scan' => $item['status_scan'] ?? $item['status'] ?? 0,
                    ];
                }
            }
            // Kasus 3: Single record (JSON datar atau form-urlencoded atau query param)
            else {
                $pin = $payload['pin'] ?? $payload['PIN'] ?? $payload['user_id'] ?? $payload['employee_no'] ?? null;
                if ($pin) {
                    $records[] = [
                        'pin'         => $pin,
                        'scan_date'   => $payload['scan_date'] ?? $payload['date_time'] ?? $payload['time'] ?? null,
                        'verify'      => $payload['verify'] ?? $payload['verif_mode'] ?? 1,
                        'status_scan' => $payload['status_scan'] ?? $payload['status'] ?? 0,
                    ];
                }
            }

            if (empty($records)) {
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

        } catch (\Exception $e) {
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
        return response()->json([
            'status'        => 'online',
            'device_target' => 'Fingerspot Revo W230N',
            'server'        => 'SMPI Baitul Makmur',
            'time'          => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s T'),
            'version'       => '2.0',
            'endpoints'     => [
                'cloud_webhook' => url('/api/fingerspot/attendance'),
                'cloud_alias'   => url('/api/fingerspot/webhook'),
                'adms_push'     => url('/iclock/cdata'),
            ],
        ]);
    }

    /**
     * Memproses satu record absensi siswa
     */
    public function processAttendanceRecord(string $pin, ?string $scanDate = null, $verifyMode = 1, $statusScan = 0): array
    {
        // 1. Cari Siswa berdasarkan PIN Fingerspot / Hikvision Employee No / NIS / No Kartu RFID
        $student = Student::with('classroom')
            ->where(function ($query) use ($pin) {
                if (Schema::hasColumn('students', 'fingerspot_pin')) {
                    $query->where('fingerspot_pin', $pin);
                }
                $query->orWhere('hikvision_employee_no', $pin)
                      ->orWhere('nis', $pin)
                      ->orWhere('nfc_card_no', $pin);
            })
            ->first();

        if (!$student) {
            Log::warning("Fingerspot: PIN/NIS {$pin} tidak ditemukan di database siswa.");
            return [
                'status'  => 'not_found',
                'pin'     => $pin,
                'message' => "Siswa dengan PIN/NIS {$pin} tidak terdaftar",
            ];
        }

        // 2. Parse waktu scan (WIB)
        try {
            $now = $scanDate ? Carbon::parse($scanDate)->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        } catch (\Exception $e) {
            $now = Carbon::now('Asia/Jakarta');
        }
        $today = $now->toDateString();

        // 3. Tentukan metode verifikasi
        $metode = $this->mapVerifyMode($verifyMode);

        // 4. Deteksi tipe absen berdasarkan slot jam sekolah SMPI Baitul Makmur
        $tipe = $this->deteksiTipe($now, $statusScan);

        $institutionId = $student->classroom->institution_id ?? 1;

        $systemUserId = auth()->id() ?? \App\Models\User::value('id') ?? 1;

        // 5. Cek halangan siswi (untuk waktu sholat dhuhur & ashar)
        if ($student->jenis_kelamin === 'P' && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar'])) {
            if (StudentHalangan::isHalangan($student->id, $today)) {
                DailyAttendance::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'tanggal'    => $today,
                        'tipe_absen' => $tipe,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'teacher_id'     => $systemUserId, // System / Mesin Absensi
                        'metode_absen'   => $metode,
                        'status'         => 'halangan',
                        'is_late'        => false,
                        'catatan'        => 'Siswi halangan (Udzur Syar\'i) - via Fingerspot Revo W230N',
                    ]
                );

                Log::info("Fingerspot: Siswi halangan dicatat ({$student->name} - {$tipe})");
                return [
                    'status'  => 'success',
                    'action'  => 'halangan',
                    'student' => $student->name,
                    'tipe'    => $tipe,
                ];
            }
        }

        // 5b. Cek apakah siswa sudah tercatat Sakit, Izin, atau Alpa hari ini oleh Guru Piket
        $excusedToday = DailyAttendance::where('student_id', $student->id)
            ->where('tanggal', $today)
            ->whereIn('status', ['sakit', 'izin', 'alpa'])
            ->first();

        if ($excusedToday && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar', 'pulang'])) {
            Log::info("Fingerspot: Siswa {$student->name} tercatat {$excusedToday->status} hari ini oleh Guru Piket, scan {$tipe} diabaikan agar status tidak tertimpa.");
            return [
                'status'  => 'excused',
                'action'  => 'ignored',
                'student' => $student->name,
                'tipe'    => $tipe,
                'message' => "Siswa {$student->name} sudah tercatat {$excusedToday->status} hari ini oleh Guru Piket",
            ];
        }

        // 6. Cek apakah sudah ada catatan absen pada tipe & tanggal yang sama
        $exists = DailyAttendance::where('student_id', $student->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', $tipe)
            ->exists();

        if ($exists) {
            return [
                'status'  => 'duplicate',
                'student' => $student->name,
                'tipe'    => $tipe,
                'message' => "Siswa {$student->name} sudah tercatat untuk {$tipe} hari ini",
            ];
        }

        // 7. Hitung Keterlambatan (hanya untuk absen masuk)
        $isLate = false;
        $catatan = ucfirst(str_replace('_', ' ', $tipe)) . " via Fingerspot ({$metode})";
        if ($tipe === 'masuk') {
            $batas = $now->copy()->setHour(7)->setMinute(15)->setSecond(0);
            $isLate = $now->greaterThan($batas);
            $catatan = $isLate
                ? "Terlambat ({$now->format('H:i')}) via Fingerspot ({$metode})"
                : "Tepat Waktu via Fingerspot ({$metode})";
        }

        // 8. Simpan presensi harian
        DailyAttendance::create([
            'student_id'       => $student->id,
            'institution_id'   => $institutionId,
            'teacher_id'       => $systemUserId, // System user (Mesin Fingerspot)
            'tanggal'          => $today,
            'tipe_absen'       => $tipe,
            'metode_absen'     => $metode,
            'waktu_kedatangan' => in_array($tipe, ['masuk', 'sholat_dhuhur', 'sholat_ashar']) ? $now->format('H:i:s') : null,
            'waktu_kepulangan' => $tipe === 'pulang' ? $now->format('H:i:s') : null,
            'status'           => 'hadir',
            'is_late'          => $isLate,
            'catatan'          => $catatan,
        ]);

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
            default                             => 'fingerspot_' . $modeStr,
        };
    }

    /**
     * Deteksi Tipe Absen berdasarkan Jam Sekolah SMPI Baitul Makmur:
     * - < 12:00      : Absen Masuk Pagi
     * - 12:00–13:59  : Absensi Sholat Dhuhur Berjamaah
     * - 14:00–15:59  : Absensi Sholat Ashar Berjamaah
     * - >= 16:00     : Absen Kepulangan
     */
    private function deteksiTipe(Carbon $waktu, $statusScan = null): string
    {
        $jam = $waktu->hour * 60 + $waktu->minute;

        if ($jam < 12 * 60) {
            return 'masuk';
        }
        if ($jam < 14 * 60) {
            return 'sholat_dhuhur';
        }
        if ($jam < 16 * 60) {
            return 'sholat_ashar';
        }
        return 'pulang';
    }
}
