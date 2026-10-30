<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\CharacterScoreReport;
use App\Models\AcademicYear;
use App\Models\CharacterScoreLog;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use App\Models\TeachingJournal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingJournalIntegrationWithAttendanceAndCharacterTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected User $guruMapel;
    protected User $kepsek;
    protected User $waliKelas;
    protected Classroom $classroom;
    protected Subject $subject;
    protected AcademicYear $academicYear;
    protected Schedule $schedule;
    protected Student $studentA;
    protected Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->yayasan = Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->academicYear = AcademicYear::create([
            'institution_id' => $this->smp->id,
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);

        $this->waliKelas = User::factory()->create([
            'institution_id' => $this->smp->id,
            'name' => 'Ustadz Wali Kelas',
        ]);
        $this->waliKelas->assignRole('wali_kelas');

        $this->guruMapel = User::factory()->create([
            'institution_id' => $this->smp->id,
            'name' => 'Ustadz Ahmad Mapel',
        ]);
        $this->guruMapel->assignRole('guru_mapel');

        $this->kepsek = User::factory()->create([
            'institution_id' => $this->smp->id,
            'name' => 'Kepala Sekolah',
        ]);
        $this->kepsek->assignRole('kepala_sekolah');

        $this->classroom = Classroom::create([
            'institution_id' => $this->smp->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'tingkat' => 7,
            'is_active' => true,
            'wali_kelas_id' => $this->waliKelas->id,
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->smp->id,
            'name' => 'Matematika',
            'kode' => 'MTK',
        ]);

        $this->schedule = Schedule::create([
            'institution_id' => $this->smp->id,
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->guruMapel->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'jam_mulai' => '07:30',
            'jam_selesai' => '08:50',
        ]);

        $this->studentA = Student::create([
            'institution_id' => $this->smp->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Ahmad Bilal',
            'nis' => '2026001',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'character_score' => 90,
            'is_administrasi_lunas' => true,
        ]);

        $this->studentB = Student::create([
            'institution_id' => $this->smp->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Budi Santoso',
            'nis' => '2026002',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-06-15',
            'character_score' => 85,
            'is_administrasi_lunas' => true,
        ]);
    }

    /**
     * Test 1: Jurnal Mengajar otomatis mencatat Presensi Kehadiran Guru Mengajar (TeacherAttendance).
     */
    public function test_teaching_journal_automatically_records_teacher_attendance(): void
    {
        $this->actingAs($this->guruMapel);

        $journal = TeachingJournal::create([
            'institution_id' => $this->smp->id,
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->guruMapel->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'materi' => 'Aljabar Dasar',
            'kegiatan_pembelajaran' => 'Diskusi kelompok dan pemecahan soal aljabar',
        ]);

        $attendance = TeacherAttendance::where('teaching_journal_id', $journal->id)->first();

        $this->assertNotNull($attendance, 'TeacherAttendance harus otomatis tercatat saat Jurnal Mengajar disimpan');
        $this->assertEquals($this->guruMapel->id, $attendance->teacher_id);
        $this->assertEquals($this->schedule->id, $attendance->schedule_id);
        $this->assertEquals($this->classroom->id, $attendance->classroom_id);
        $this->assertEquals($this->subject->id, $attendance->subject_id);
        $this->assertEquals(1, $attendance->jam_ke);
        $this->assertEquals('masuk', $attendance->status);
        $this->assertStringContainsString('Jurnal Mengajar', $attendance->keterangan);
    }

    /**
     * Test 2: Input Karakter Baik (+1) dan Kurang Baik (-1) di Jurnal Mengajar otomatis tersinkron ke CharacterScoreLog & Skor Siswa.
     */
    public function test_teaching_journal_syncs_character_notes_to_character_score_logs_and_student_score(): void
    {
        $this->actingAs($this->guruMapel);

        $journal = TeachingJournal::create([
            'institution_id' => $this->smp->id,
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->guruMapel->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'materi' => 'Aljabar Dasar',
            'catatan_karakter' => [
                [
                    'student_id' => (string) $this->studentA->id,
                    'type' => 'baik',
                    'kategori' => 'Keaktifan & Partisipasi',
                    'catatan' => 'Sangat aktif & antusias dalam pembelajaran',
                ],
                [
                    'student_id' => (string) $this->studentB->id,
                    'type' => 'kurang_baik',
                    'kategori' => 'Kedisiplinan & Ketertiban',
                    'catatan' => 'Ramai dan membuat kegaduhan saat guru menjelaskan',
                ],
            ],
        ]);

        // Periksa CharacterScoreLog untuk Student A (Karakter Baik +1)
        $logA = CharacterScoreLog::where('student_id', $this->studentA->id)->first();
        $this->assertNotNull($logA);
        $this->assertEquals(1, $logA->point_change);
        $this->assertEquals('guru_mapel', $logA->source);
        $this->assertEquals('Keaktifan & Partisipasi', $logA->category);
        $this->assertStringContainsString("[Jurnal #{$journal->id}]", $logA->description);
        $this->assertStringContainsString('[Matematika]', $logA->description);

        // Periksa Skor Siswa A bertambah dari 90 menjadi 91
        $this->studentA->refresh();
        $this->assertEquals(91, $this->studentA->character_score);

        // Periksa CharacterScoreLog untuk Student B (Karakter Kurang Baik -1)
        $logB = CharacterScoreLog::where('student_id', $this->studentB->id)->first();
        $this->assertNotNull($logB);
        $this->assertEquals(-1, $logB->point_change);
        $this->assertEquals('guru_mapel', $logB->source);
        $this->assertEquals('Kedisiplinan & Ketertiban', $logB->category);
        $this->assertStringContainsString("[Jurnal #{$journal->id}]", $logB->description);

        // Periksa Skor Siswa B berkurang dari 85 menjadi 84
        $this->studentB->refresh();
        $this->assertEquals(84, $this->studentB->character_score);
    }

    /**
     * Test 3: Rekapitulasi Skor Karakter Siswa (CharacterScoreReport) langsung menampilkan skor dan log dari Jurnal Mengajar.
     */
    public function test_character_score_report_displays_scores_and_logs_from_teaching_journal(): void
    {
        $this->actingAs($this->guruMapel);

        $journal = TeachingJournal::create([
            'institution_id' => $this->smp->id,
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->guruMapel->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'materi' => 'Aljabar Lanjutan',
            'catatan_karakter' => [
                [
                    'student_id' => (string) $this->studentA->id,
                    'type' => 'baik',
                    'kategori' => 'Keaktifan & Partisipasi',
                    'catatan' => 'Membantu dan membimbing temannya yang kesulitan',
                ],
            ],
        ]);

        // Login sebagai Wali Kelas untuk membuka halaman Rekapitulasi Skor Karakter
        $this->actingAs($this->waliKelas);

        $component = Livewire::test(CharacterScoreReport::class)
            ->set('selectedClassroomId', $this->classroom->id);

        // Harus melihat nama siswa A dan poin guru mapel
        $component->assertSee('Ahmad Bilal');

        // Buka modal detail siswa A
        $component->call('openDetailModal', $this->studentA->id);
        $component->assertSee('Membantu dan membimbing temannya yang kesulitan');
        $component->assertSee('[Matematika]');
    }

    /**
     * Test 4: Menghapus Jurnal Mengajar otomatis menghapus Presensi Guru dan mengembalikan Skor Karakter Siswa.
     */
    public function test_deleting_teaching_journal_cleans_up_attendance_and_reverts_character_scores(): void
    {
        $this->actingAs($this->guruMapel);

        $journal = TeachingJournal::create([
            'institution_id' => $this->smp->id,
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->guruMapel->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'materi' => 'Aljabar Selesai',
            'catatan_karakter' => [
                [
                    'student_id' => (string) $this->studentA->id,
                    'type' => 'baik',
                    'kategori' => 'Keaktifan',
                    'catatan' => 'Sangat aktif',
                ],
            ],
        ]);

        $this->studentA->refresh();
        $this->assertEquals(91, $this->studentA->character_score);
        $this->assertDatabaseHas('teacher_attendances', ['teaching_journal_id' => $journal->id]);
        $this->assertDatabaseHas('character_score_logs', ['student_id' => $this->studentA->id]);

        // Hapus Jurnal Mengajar
        $journal->delete();

        // Presensi Guru harus terhapus
        $this->assertDatabaseMissing('teacher_attendances', ['teaching_journal_id' => $journal->id]);

        // CharacterScoreLog harus terhapus
        $this->assertDatabaseMissing('character_score_logs', ['student_id' => $this->studentA->id]);

        // Skor karakter siswa A harus kembali ke baseline 90
        $this->studentA->refresh();
        $this->assertEquals(90, $this->studentA->character_score);
    }
}
