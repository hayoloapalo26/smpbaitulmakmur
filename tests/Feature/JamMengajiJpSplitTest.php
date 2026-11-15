<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\TeacherAttendanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JamMengajiJpSplitTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruMengaji;
    protected Subject $mapelQuran;
    protected Classroom $kelasYanbua;
    protected Classroom $kelasTahsin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        for ($i = 1; $i <= 4; $i++) {
            User::create([
                'name' => "Dummy User {$i}",
                'email' => "dummy{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        $this->guruMengaji = User::create([
            'name'           => 'Ustadz Ahmad Al-Hafidz',
            'username'       => 'ahmad_mengaji',
            'email'          => 'ahmad_mengaji@example.com',
            'password'       => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active'      => true,
        ]);
        $this->guruMengaji->assignRole('guru_quran_yanbua');

        $this->mapelQuran = Subject::create([
            'name'           => 'Al-Qur\'an (Yanbu\'a)',
            'kode'           => 'YNB',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelasYanbua = Classroom::create([
            'name'           => 'Kelompok Yanbu\'a Jilid 4',
            'tingkat'        => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelasTahsin = Classroom::create([
            'name'           => 'Kelompok Tahsin Pemula',
            'tingkat'        => '7',
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_schedule_has_two_separate_mengaji_slots_per_jp(): void
    {
        $slots = Schedule::getJamSlots();

        // 1. Slot Jam Mengaji 1
        $this->assertArrayHasKey(-1, $slots);
        $this->assertEquals('Jam Mengaji 1', $slots[-1]['label']);
        $this->assertEquals('07.00 - 07.35', $slots[-1]['time']);
        $this->assertEquals('Jam Mengaji 1 (07.00 - 07.35)', $slots[-1]['full']);

        // 2. Slot Jam Mengaji 2
        $this->assertArrayHasKey(0, $slots);
        $this->assertEquals('Jam Mengaji 2', $slots[0]['label']);
        $this->assertEquals('07.35 - 08.10', $slots[0]['time']);
        $this->assertEquals('Jam Mengaji 2 (07.35 - 08.10)', $slots[0]['full']);

        // Helper label & time methods
        $this->assertEquals('Jam Mengaji 1', Schedule::getJamLabel(-1));
        $this->assertEquals('Jam Mengaji 2', Schedule::getJamLabel(0));
        $this->assertEquals('Jam Ke-1', Schedule::getJamLabel(1));

        $this->assertEquals('07.00 - 07.35', Schedule::getJamTime(-1));
        $this->assertEquals('07.35 - 08.10', Schedule::getJamTime(0));

        $this->assertEquals('Jam Mengaji 1 (07.00 - 07.35)', Schedule::getJamLabelWithTime(-1));
        $this->assertEquals('Jam Mengaji 2 (07.35 - 08.10)', Schedule::getJamLabelWithTime(0));
    }

    public function test_can_schedule_jam_mengaji_1_and_2_independently_as_two_jp(): void
    {
        // Jadwalkan Jam Mengaji 1 (1 JP)
        $sched1 = Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => 'Senin',
            'jam_ke'         => -1,
            'institution_id' => $this->institution->id,
        ]);

        // Jadwalkan Jam Mengaji 2 (1 JP)
        $sched2 = Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => 'Senin',
            'jam_ke'         => 0,
            'institution_id' => $this->institution->id,
        ]);

        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $this->guruMengaji->id,
            'hari'       => 'Senin',
            'jam_ke'     => -1,
        ]);

        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $this->guruMengaji->id,
            'hari'       => 'Senin',
            'jam_ke'     => 0,
        ]);

        $this->assertEquals(2, Schedule::where('teacher_id', $this->guruMengaji->id)->where('hari', 'Senin')->count());
    }

    public function test_teacher_attendance_report_counts_two_jp_for_mengaji_slots(): void
    {
        // Jadwalkan 2 JP mengaji pada hari Senin
        Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => 'Senin',
            'jam_ke'         => -1,
            'institution_id' => $this->institution->id,
        ]);
        Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => 'Senin',
            'jam_ke'         => 0,
            'institution_id' => $this->institution->id,
        ]);

        $report = TeacherAttendanceReportService::getReportData(
            Carbon::now()->month,
            Carbon::now()->year,
            $this->guruMengaji->id
        );

        $teacherReport = collect($report['reportData'])->firstWhere('teacher_id', $this->guruMengaji->id);
        $this->assertNotNull($teacherReport);
        // Beban mingguan harus terhitung 2 JP (bukan 1 JP lagi)
        $this->assertEquals(2, $teacherReport['weekly_jp']);
    }

    public function test_anti_clash_detection_works_for_both_mengaji_slots(): void
    {
        // Guru Mengaji sudah ada jadwal di Kelas Yanbua pada Jam Mengaji 1 (jam_ke = -1)
        Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => 'Selasa',
            'jam_ke'         => -1,
            'institution_id' => $this->institution->id,
        ]);

        // Cek jika guru yang sama dijadwalkan di kelas lain pada Jam Mengaji 1 -> HARUS BENTROK
        $clashes1 = Schedule::checkClashes($this->kelasTahsin->id, $this->guruMengaji->id, ['Selasa'], [-1]);
        $this->assertNotEmpty($clashes1);
        $this->assertStringContainsString('Jam Mengaji 1', $clashes1[0]);

        // Cek jika guru yang sama dijadwalkan di kelas lain pada Jam Mengaji 2 (jam_ke = 0) -> TIDAK BENTROK
        $clashes2 = Schedule::checkClashes($this->kelasTahsin->id, $this->guruMengaji->id, ['Selasa'], [0]);
        $this->assertEmpty($clashes2);
    }

    public function test_teaching_journal_logs_each_mengaji_slot_per_jp(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $dayName = Schedule::getIndonesianDayName(Carbon::today());

        $schedMengaji1 = Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => $dayName,
            'jam_ke'         => -1,
            'institution_id' => $this->institution->id,
        ]);

        $schedMengaji2 = Schedule::create([
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'teacher_id'     => $this->guruMengaji->id,
            'hari'           => $dayName,
            'jam_ke'         => 0,
            'institution_id' => $this->institution->id,
        ]);

        // 1. Isi Jurnal untuk Jam Mengaji 1
        $journal1 = TeachingJournal::create([
            'teacher_id'     => $this->guruMengaji->id,
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'schedule_id'    => $schedMengaji1->id,
            'jam_ke'         => -1,
            'tanggal'        => $today,
            'materi'         => 'Makharijul Huruf & Kelancaran Jilid 4 Hal. 1-5',
            'institution_id' => $this->institution->id,
        ]);

        // 2. Isi Jurnal untuk Jam Mengaji 2
        $journal2 = TeachingJournal::create([
            'teacher_id'     => $this->guruMengaji->id,
            'classroom_id'   => $this->kelasYanbua->id,
            'subject_id'     => $this->mapelQuran->id,
            'schedule_id'    => $schedMengaji2->id,
            'jam_ke'         => 0,
            'tanggal'        => $today,
            'materi'         => 'Simakan Perorangan Jilid 4 Hal. 6-10',
            'institution_id' => $this->institution->id,
        ]);

        // Verifikasi kedua jurnal tercatat
        $this->assertDatabaseHas('teaching_journals', [
            'id'     => $journal1->id,
            'jam_ke' => -1,
        ]);
        $this->assertDatabaseHas('teaching_journals', [
            'id'     => $journal2->id,
            'jam_ke' => 0,
        ]);

        // Verifikasi otomatis tercatat presensi hadir 1 JP untuk masing-masing slot
        $this->assertDatabaseHas('teacher_attendances', [
            'teaching_journal_id' => $journal1->id,
            'jam_ke'              => -1,
            'status'              => 'masuk',
        ]);

        $this->assertDatabaseHas('teacher_attendances', [
            'teaching_journal_id' => $journal2->id,
            'jam_ke'              => 0,
            'status'              => 'masuk',
        ]);
    }
}
