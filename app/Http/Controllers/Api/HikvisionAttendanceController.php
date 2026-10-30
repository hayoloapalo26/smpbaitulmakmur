<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentHalangan;
use App\Models\DailyAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API Controller untuk menerima push event dari Hikvision DS-K1T343MFX
 * 
 * Hikvision DS-K1T343MFX mendukung:
 *  - Fingerprint recognition
 *  - NFC / RFID Card (Mifare 13.56MHz)
 *  - Face recognition (3D)
 * 
 * Cara setting push di DS-K1T343MFX:
 *  Main Menu → Communication → Network → Ezviz Cloud / HTTP Forwarding
 *  - Enable HTTP Push
 *  - Server URL : http://[IP-SERVER]:8000/api/hikvision/attendance
 *  - Upload Interval : 0 (real-time)
 * 
 * ATAU via iVMS-4200 / Web Interface:
 *  Configuration → Network → Advanced → Integration Protocol
 *  - Enable Isapi Notify
 *  - URL : http://[IP-SERVER]:8000/api/hikvision/attendance
 */
class HikvisionAttendanceController extends Controller
{
    /**
     * Endpoint utama untuk menerima push event dari Hikvision
     * POST /api/hikvision/attendance
     */
    public function receive(Request $request)
    {
        // Log raw payload untuk debugging
        Log::channel('daily')->info('Hikvision Event Received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Hikvision mengirim data dalam format ISAPI Event
            // Field utama yang kita butuhkan:
            $payload = $request->all();

            // Support 2 format Hikvision: langsung atau nested dalam 'AcsEvent'
            $event = $payload['AcsEvent'] ?? $payload['AccessControllerEvent'] ?? $payload;

            $employeeNo  = $event['employeeNoString'] ?? $event['employeeNo'] ?? null;
            $verifyMode  = $event['verifyMode'] ?? 'unknown';
            $happenTime  = $event['time'] ?? $event['dateTime'] ?? now()->toIso8601String();
            $serialNo    = $event['serialNo'] ?? null;

            if (!$employeeNo) {
                return response()->json(['status' => 'ignored', 'reason' => 'No employee number in event'], 200);
            }

            // Cari siswa berdasarkan Fingerspot PIN, Hikvision Employee No, ATAU NIS
            $student = Student::with('classroom')
                ->where(function ($query) use ($employeeNo) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'fingerspot_pin')) {
                        $query->where('fingerspot_pin', $employeeNo);
                    }
                    $query->orWhere('hikvision_employee_no', $employeeNo)
                          ->orWhere('nis', $employeeNo);
                })
                ->first();

            if (!$student) {
                Log::warning("Hikvision: Employee No {$employeeNo} tidak ditemukan di database siswa");
                return response()->json(['status' => 'not_found', 'employee_no' => $employeeNo], 200);
            }

            // Tentukan metode absen dari verifyMode Hikvision
            $metode = $this->mapVerifyMode($verifyMode);

            // Parse waktu dari Hikvision
            try {
                $now = Carbon::parse($happenTime)->setTimezone('Asia/Jakarta');
            } catch (\Exception $e) {
                $now = Carbon::now();
            }

            $today = $now->toDateString();

            // Tentukan tipe absen berdasarkan jam
            $tipe = $this->deteksiTipe($now);

            // Cek halangan siswi
            if ($student->jenis_kelamin === 'P' && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar'])) {
                if (StudentHalangan::isHalangan($student->id, $today)) {
                    // Catat sebagai halangan
                    DailyAttendance::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'tanggal'    => $today,
                            'tipe_absen' => $tipe,
                        ],
                        [
                            'institution_id' => $student->institution_id,
                            'teacher_id'   => 1, // System/Admin user
                            'metode_absen' => $metode,
                            'status'       => 'halangan',
                            'is_late'      => false,
                            'catatan'      => 'Siswi halangan (Udzur Syar\'i) - via Hikvision',
                        ]
                    );

                    return response()->json(['status' => 'success', 'action' => 'halangan', 'student' => $student->name]);
                }
            }

            // Cek apakah siswa sudah tercatat Sakit, Izin, atau Alpa hari ini oleh Guru Piket
            $excusedRecord = DailyAttendance::where('student_id', $student->id)
                ->where('tanggal', $today)
                ->whereIn('status', ['sakit', 'izin', 'alpa'])
                ->first();

            if ($excusedRecord && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar', 'pulang'])) {
                Log::info("Hikvision: Siswa {$student->name} tercatat {$excusedRecord->status} hari ini oleh Guru Piket, scan {$tipe} diabaikan.");
                return response()->json([
                    'status'  => 'excused',
                    'action'  => 'ignored',
                    'student' => $student->name,
                    'tipe'    => $tipe,
                    'message' => "Siswa {$student->name} sudah tercatat {$excusedRecord->status} hari ini oleh Guru Piket",
                ]);
            }

            // Cek apakah sudah absen untuk tipe ini hari ini
            $exists = DailyAttendance::where('student_id', $student->id)
                ->where('tanggal', $today)
                ->where('tipe_absen', $tipe)
                ->exists();

            if ($exists) {
                return response()->json(['status' => 'duplicate', 'student' => $student->name, 'tipe' => $tipe]);
            }

            // Keterlambatan (hanya untuk absen masuk)
            $isLate = false;
            $catatan = ucfirst(str_replace('_', ' ', $tipe)) . " via {$metode}";
            if ($tipe === 'masuk') {
                $batas = $now->copy()->setHour(7)->setMinute(15)->setSecond(0);
                $isLate = $now->greaterThan($batas);
                $catatan = $isLate ? "Terlambat ({$now->format('H:i')}) via {$metode}" : "Tepat Waktu via {$metode}";
            }

            // Simpan kehadiran
            DailyAttendance::create([
                'student_id'      => $student->id,
                'institution_id'  => $student->institution_id,
                'teacher_id'      => 1, // System user (Hikvision device)
                'tanggal'         => $today,
                'tipe_absen'      => $tipe,
                'metode_absen'    => $metode,
                'waktu_kedatangan' => in_array($tipe, ['masuk', 'sholat_dhuhur', 'sholat_ashar']) ? $now->format('H:i:s') : null,
                'waktu_kepulangan' => $tipe === 'pulang' ? $now->format('H:i:s') : null,
                'status'          => 'hadir',
                'is_late'         => $isLate,
                'catatan'         => $catatan,
            ]);

            Log::info("Hikvision: Absen berhasil", [
                'student'  => $student->name,
                'tipe'     => $tipe,
                'metode'   => $metode,
                'jam'      => $now->format('H:i:s'),
            ]);

            return response()->json([
                'status'   => 'success',
                'student'  => $student->name,
                'nis'      => $student->nis,
                'kelas'    => $student->classroom->name ?? '-',
                'tipe'     => $tipe,
                'metode'   => $metode,
                'jam'      => $now->format('H:i'),
                'is_late'  => $isLate,
            ]);

        } catch (\Exception $e) {
            Log::error('Hikvision Event Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Endpoint untuk test koneksi dari Hikvision
     * GET /api/hikvision/ping
     */
    public function ping()
    {
        return response()->json([
            'status'  => 'online',
            'server'  => 'SMPI Baitul Makmur',
            'time'    => now()->format('Y-m-d H:i:s T'),
            'version' => '1.0',
        ]);
    }

    /**
     * Peta verifyMode Hikvision ke nama metode yang readable
     */
    private function mapVerifyMode(string $mode): string
    {
        return match(strtolower($mode)) {
            'face', 'faceid'                    => 'face_recognition',
            'fingerprint', 'fp'                 => 'fingerprint',
            'card', 'nfc', 'rfcard', 'cardonly' => 'nfc_card',
            'cardandface'                       => 'nfc_card+face',
            'cardandfingerprint'                => 'nfc_card+fingerprint',
            default                             => 'hikvision_' . $mode,
        };
    }

    /**
     * Deteksi tipe absen dari jam
     */
    private function deteksiTipe(Carbon $waktu): string
    {
        $jam = $waktu->hour * 60 + $waktu->minute;
        if ($jam < 12 * 60) return 'masuk';
        if ($jam < 14 * 60) return 'sholat_dhuhur';
        if ($jam < 16 * 60) return 'sholat_ashar';
        return 'pulang';
    }
}
