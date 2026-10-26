<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\Student;
use App\Models\DailyAttendance;
use App\Models\StudentHalangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScannerPresensi extends Page
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Scan Presensi';
    protected static ?string $title = 'Scanner Presensi Siswa';
    protected static string $view = 'filament.staff.pages.scanner-presensi';

    public $nis = '';
    public $scannedStudent = null;
    public $scanStatus = null; // 'success', 'warning', 'error'
    public $scanMessage = '';
    public $scanTime = '';
    public $tipeAbsen = 'masuk'; // masuk | sholat_dhuhur | sholat_ashar | pulang

    

    /**
     * Deteksi tipe absen otomatis berdasarkan jam
     */
    private function deteksiTipeAbsen(Carbon $now): string
    {
        $jam = $now->hour * 60 + $now->minute;

        if ($jam < 12 * 60)         return 'masuk';       // < 12:00 → Masuk
        if ($jam < 14 * 60)         return 'sholat_dhuhur'; // 12:00–13:59 → Dhuhur
        if ($jam < 16 * 60)         return 'sholat_ashar';  // 14:00–15:59 → Ashar
        return 'pulang';            // >= 16:00 → Pulang
    }

    private function labelTipe(string $tipe): string
    {
        return match ($tipe) {
            'masuk'         => '🏫 Absen Masuk',
            'sholat_dhuhur' => '🕌 Sholat Dhuhur',
            'sholat_ashar'  => '🕌 Sholat Ashar',
            'pulang'        => '🏠 Absen Pulang',
            default         => ucfirst($tipe),
        };
    }

    public function handleScan()
    {
        if (empty($this->nis)) {
            return;
        }

        $student = Student::with('classroom')->where('nis', $this->nis)->first();

        if (!$student) {
            $this->scanStatus = 'error';
            $this->scanMessage = "NIS {$this->nis} tidak ditemukan!";
            $this->scannedStudent = null;
            $this->nis = '';
            $this->dispatch('scan-result', status: 'error');
            return;
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Auto-detect tipe absen dari jam
        $tipe = $this->deteksiTipeAbsen($now);
        $labelTipe = $this->labelTipe($tipe);

        // ===========================
        // CEK STATUS HALANGAN SISWI
        // ===========================
        $isHalangan = false;
        if ($student->jenis_kelamin === 'P') {
            $isHalangan = StudentHalangan::isHalangan($student->id, $today);
        }

        // Jika siswi sedang halangan dan ini waktu sholat → catat dengan status khusus
        if ($isHalangan && in_array($tipe, ['sholat_dhuhur', 'sholat_ashar'])) {
            // Cek apakah sudah dicatat hari ini untuk tipe ini
            $existing = DailyAttendance::where('student_id', $student->id)
                ->where('tanggal', $today)
                ->where('tipe_absen', $tipe)
                ->first();

            if (!$existing) {
                DailyAttendance::create([
                    'student_id'   => $student->id,
                    'teacher_id'   => Auth::id(),
                    'tanggal'      => $today,
                    'tipe_absen'   => $tipe,
                    'metode_absen' => 'scan_barcode',
                    'status'       => 'halangan',
                    'is_late'      => false,
                    'catatan'      => 'Siswi sedang halangan — tidak wajib sholat',
                ]);
            }

            $this->scannedStudent = [
                'name'      => $student->name,
                'nis'       => $student->nis,
                'classroom' => $student->classroom->name ?? '-',
                'foto'      => $student->foto,
                'tipe'      => $labelTipe,
                'halangan'  => true,
            ];
            $this->scanStatus = 'halangan';
            $this->scanMessage = "🌸 {$labelTipe} — Siswi Halangan";
            $this->scanTime = $now->format('H:i:s');
            $this->dispatch('scan-result', status: 'warning');
            $this->nis = '';
            return;
        }


        // Cek apakah tipe absen ini sudah ada hari ini
        $existingAbsen = DailyAttendance::where('student_id', $student->id)
            ->where('tanggal', $today)
            ->where('tipe_absen', $tipe)
            ->first();

        if ($existingAbsen) {
            $this->scanStatus = 'warning';
            $this->scanMessage = "Sudah Absen {$labelTipe}!";
        } else {
            // Tentukan apakah terlambat (hanya berlaku untuk absen masuk)
            $isLate = false;
            $catatan = $labelTipe;

            if ($tipe === 'masuk') {
                $limitTime = Carbon::today()->setHour(7)->setMinute(15);
                $isLate = $now->greaterThan($limitTime);
                $catatan = $isLate ? "Terlambat ({$now->format('H:i')})" : 'Tepat Waktu';
            }

            DailyAttendance::create([
                'student_id'    => $student->id,
                'teacher_id'    => Auth::id(),
                'tanggal'       => $today,
                'tipe_absen'    => $tipe,
                'metode_absen'  => 'scan_barcode',
                'waktu_kedatangan' => ($tipe === 'masuk') ? $now->format('H:i:s') : null,
                'waktu_kepulangan' => ($tipe === 'pulang') ? $now->format('H:i:s') : null,
                'status'        => 'hadir',
                'is_late'       => $isLate,
                'catatan'       => $catatan,
            ]);

            $this->scanStatus = 'success';
            if ($tipe === 'masuk' && $isLate) {
                $this->scanMessage = "{$labelTipe} — Terlambat ⚠️";
            } else {
                $this->scanMessage = "{$labelTipe} — Berhasil!";
            }
        }

        $this->scannedStudent = [
            'name'      => $student->name,
            'nis'       => $student->nis,
            'classroom' => $student->classroom->name ?? 'Belum ada kelas',
            'foto'      => $student->foto,
            'tipe'      => $labelTipe,
        ];
        $this->scanTime = $now->format('H:i:s');
        $this->tipeAbsen = $tipe;

        // Trigger audio in frontend
        $this->dispatch('scan-result', status: $this->scanStatus);

        // Reset NIS for next scan
        $this->nis = '';
    }
}
