<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TeacherAttendance;
use App\Models\TeachingJournal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeachingJournalAttendanceAndDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $admin;
    protected Institution $institution;
    protected Classroom $classroom;
    protected Subject $subject;
    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'guru_mapel']);
        Role::firstOrCreate(['name' => 'waka_kurikulum']);
        Role::firstOrCreate(['name' => 'superadmin']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smpi',
        ]);

        $this->teacher = User::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Ustadz Ahmad',
        ]);
        $this->teacher->assignRole('guru_mapel');

        $this->admin = User::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Waka Kurikulum',
        ]);
        $this->admin->assignRole('waka_kurikulum');

        $this->classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'VII-A',
            'tingkat' => '7',
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->institution->id,
            'name' => 'Matematika',
            'kode' => 'MTK-7',
        ]);

        $this->schedule = Schedule::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
        ]);
    }

    public function test_creating_teaching_journal_automatically_records_teacher_attendance_with_status_masuk(): void
    {
        $this->actingAs($this->teacher);

        $journal = TeachingJournal::create([
            'teacher_id' => $this->teacher->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => now()->toDateString(),
            'rencana_kegiatan_guru' => 'Membahas Aljabar',
            'kegiatan_siswa' => 'Mengerjakan Latihan',
        ]);

        $this->assertDatabaseHas('teacher_attendances', [
            'teacher_id' => $this->teacher->id,
            'teaching_journal_id' => $journal->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'status' => 'masuk',
        ]);

        $attendance = TeacherAttendance::where('teaching_journal_id', $journal->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('masuk', $attendance->status);
    }

    public function test_journal_deadline_calculation(): void
    {
        // Jam Ke-1 berakhir pukul 08:40. Batas 24 jam adalah keesokan harinya pukul 08:40
        $tanggal = '2026-10-27';
        $deadline = TeachingJournal::getJournalDeadline($tanggal, 1);

        $this->assertEquals('2026-10-28 08:40:00', $deadline->format('Y-m-d H:i:s'));

        // Jika jam_ke null, default jam selesai 15:00, deadline esok hari 15:00
        $deadlineDefault = TeachingJournal::getJournalDeadline($tanggal, null);
        $this->assertEquals('2026-10-28 15:00:00', $deadlineDefault->format('Y-m-d H:i:s'));
    }

    public function test_past_deadline_check(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28 09:00:00'));

        // Jadwal kemarin (2026-10-27) jam ke-1 (selesai 08:40) -> batasnya 2026-10-28 08:40:00
        // Karena sekarang 09:00, sudah lewat batas 24 jam
        $this->assertTrue(TeachingJournal::isPastDeadline('2026-10-27', 1));

        // Jadwal kemarin jam ke-8 (selesai 13:30) -> batasnya 2026-10-28 13:30:00
        // Karena sekarang 09:00, belum lewat batas 24 jam
        $this->assertFalse(TeachingJournal::isPastDeadline('2026-10-27', 8));

        // Jadwal hari ini (2026-10-28) jam ke-1 -> batasnya 2026-10-29 08:40:00 -> belum lewat
        $this->assertFalse(TeachingJournal::isPastDeadline('2026-10-28', 1));

        Carbon::setTestNow();
    }

    public function test_teacher_cannot_create_journal_past_24_hours(): void
    {
        $this->actingAs($this->teacher);

        // Simulasi waktu sekarang sudah lebih dari 24 jam dari jadwal mengajar
        Carbon::setTestNow(Carbon::parse('2026-10-28 10:00:00'));

        $this->expectException(ValidationException::class);

        TeachingJournal::create([
            'teacher_id' => $this->teacher->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1, // selesai 08:40 pada tanggal 26 Oktober -> batas 27 Oktober 08:40
            'tanggal' => '2026-10-26',
            'rencana_kegiatan_guru' => 'Membahas Aljabar',
            'kegiatan_siswa' => 'Mengerjakan Latihan',
        ]);

        Carbon::setTestNow();
    }

    public function test_admin_or_waka_can_create_journal_even_past_24_hours(): void
    {
        $this->actingAs($this->admin);

        Carbon::setTestNow(Carbon::parse('2026-10-28 10:00:00'));

        $journal = TeachingJournal::create([
            'teacher_id' => $this->teacher->id,
            'schedule_id' => $this->schedule->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'jam_ke' => 1,
            'tanggal' => '2026-10-25', // Lewat 24 jam, tetapi diinput oleh Waka Kurikulum
            'rencana_kegiatan_guru' => 'Membahas Aljabar Susulan',
            'kegiatan_siswa' => 'Mengerjakan Latihan',
        ]);

        $this->assertNotNull($journal);
        $this->assertDatabaseHas('teacher_attendances', [
            'teaching_journal_id' => $journal->id,
            'status' => 'masuk',
        ]);

        Carbon::setTestNow();
    }
}
