<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\InputNilaiMapel;
use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\GradeResource;
use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\TeachingJournalResource;
use App\Filament\Staff\Resources\TeachingJournalResource\Pages\ManageTeachingJournals;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuruQuranNoCpTpAtpTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected AcademicYear $academicYear;
    protected Classroom $classroom;
    protected Student $student;
    protected User $guruAlquran;
    protected User $guruMapel;
    protected Subject $mapelQuran;
    protected Subject $mapelMatematika;
    protected Schedule $scheduleQuran;
    protected Schedule $scheduleMatematika;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_yanbua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_dasar', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->student = Student::create([
            'name' => 'Muhammad Zaki',
            'nis' => '1010',
            'classroom_id' => $this->classroom->id,
            'institution_id' => $this->institution->id,
            'tanggal_lahir' => '2012-01-01',
            'tempat_lahir' => 'Malang',
            'jenis_kelamin' => 'L',
        ]);

        $this->guruAlquran = User::create([
            'name' => 'Ustadz Ahmad Al-Qur\'an',
            'username' => 'ahmad_quran',
            'email' => 'ahmad.quran@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Al-Qur\'an',
        ]);
        $this->guruAlquran->assignRole('guru_alquran');

        $this->guruMapel = User::create([
            'name' => 'Budi Santoso, S.Pd',
            'username' => 'budi_mapel',
            'email' => 'budi.mapel@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'jabatan' => 'Guru Matematika',
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->mapelQuran = Subject::create([
            'name' => 'Al-Qur\'an (Tahfidz)',
            'kode' => 'QUR_TFZ',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelMatematika = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);

        $todayDay = Carbon::now()->locale('id')->isoFormat('dddd');

        $this->scheduleQuran = Schedule::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->mapelQuran->id,
            'teacher_id' => $this->guruAlquran->id,
            'academic_year_id' => $this->academicYear->id,
            'hari' => $todayDay,
            'jam_ke' => '1',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '07:40:00',
        ]);

        $this->scheduleMatematika = Schedule::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->mapelMatematika->id,
            'teacher_id' => $this->guruMapel->id,
            'academic_year_id' => $this->academicYear->id,
            'hari' => $todayDay,
            'jam_ke' => '2',
            'jam_mulai' => '07:40:00',
            'jam_selesai' => '08:20:00',
        ]);
    }

    /**
     * Test 1: Guru Al-Qur'an TIDAK dapat mengakses CP, TP, ATP, RPPM, GradeResource, dan InputNilaiMapel
     */
    public function test_guru_alquran_cannot_access_cp_tp_atp_and_rppm(): void
    {
        $this->actingAs($this->guruAlquran);

        $this->assertFalse(CapaianPembelajaranResource::canAccess());
        $this->assertFalse(TujuanPembelajaranResource::canAccess());
        $this->assertFalse(AlurTujuanPembelajaranResource::canAccess());
        $this->assertFalse(RppmResource::canAccess());
        $this->assertFalse(RppmResource::shouldRegisterNavigation());
        $this->assertFalse(GradeResource::canAccess());
        $this->assertFalse(GradeResource::shouldRegisterNavigation());
        $this->assertFalse(InputNilaiMapel::canAccess());
        $this->assertFalse(InputNilaiMapel::shouldRegisterNavigation());

        // Guru Al-Qur'an tetap memiliki akses ke fitur Al-Qur'an & Jurnal Mengajar
        $this->assertTrue(QuranAchievementResource::canAccess());
        $this->assertTrue(ReadingQualityScoreResource::canAccess());
        $this->assertTrue(TeachingJournalResource::canAccess());
        $this->assertTrue(ScheduleResource::canAccess());
    }

    /**
     * Test 2: Guru Mapel umum TETAP dapat mengakses CP, TP, ATP, RPPM, dan Nilai Siswa
     */
    public function test_guru_mapel_retains_access_to_cp_tp_atp_and_rppm(): void
    {
        $this->actingAs($this->guruMapel);

        $this->assertTrue(CapaianPembelajaranResource::canAccess());
        $this->assertTrue(TujuanPembelajaranResource::canAccess());
        $this->assertTrue(AlurTujuanPembelajaranResource::canAccess());
        $this->assertTrue(RppmResource::canAccess());
        $this->assertTrue(RppmResource::shouldRegisterNavigation());
        $this->assertTrue(GradeResource::canAccess());
        $this->assertTrue(GradeResource::shouldRegisterNavigation());
        $this->assertTrue(InputNilaiMapel::canAccess());
        $this->assertTrue(TeachingJournalResource::canAccess());
    }

    /**
     * Test 3: Guru Al-Qur'an dapat mengisi Jurnal Mengajar tanpa inputan CP, TP, dan ATP
     */
    public function test_guru_alquran_can_fill_teaching_journal_without_cp_tp_and_atp(): void
    {
        $this->actingAs($this->guruAlquran);

        $today = Carbon::today()->format('Y-m-d');

        // Buka form Create Jurnal Mengajar via ManageTeachingJournals
        Livewire::test(ManageTeachingJournals::class)
            ->mountAction('create')
            ->setActionData([
                'tanggal' => $today,
                'schedule_id' => $this->scheduleQuran->id,
                'classroom_id' => $this->classroom->id,
                'subject_id' => $this->mapelQuran->id,
                'jam_ke' => '1',
                // Materi Al-Qur'an tanpa inputan CP, TP, atau ATP
                'materi' => 'QS. Al-Baqarah ayat 1-15 (Makharijul Huruf)',
                'rencana_kegiatan_guru' => 'Talaqqi dan Simak Setoran Hafalan',
                'kegiatan_siswa' => 'Siswa membaca bergantian dan menyimak makhraj',
                'student_attendances' => [$this->student->id => 'hadir'],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        // Pastikan tersimpan di database tanpa rppm_id dan tp_ids
        $journal = TeachingJournal::where('teacher_id', $this->guruAlquran->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals('QS. Al-Baqarah ayat 1-15 (Makharijul Huruf)', $journal->materi);
        $this->assertNull($journal->rppm_id);
        $this->assertEmpty($journal->tp_ids);
    }
}
