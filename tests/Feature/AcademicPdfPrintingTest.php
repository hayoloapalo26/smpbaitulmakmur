<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\MeetingMinute;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentCase;
use App\Models\Subject;
use App\Models\TeacherCertificate;
use App\Models\TeacherLeaveRequest;
use App\Models\TeachingJournal;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicPdfPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guru;
    protected Classroom $classroom;
    protected Subject $subject;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bk', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-islam-baitul-makmur',
        ]);

        $this->academicYear = AcademicYear::create([
            'institution_id' => $this->institution->id,
            'name' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-15',
            'end_date' => '2026-12-20',
        ]);

        $this->guru = User::create([
            'name' => 'Ahmad Dahlan, S.Pd.',
            'username' => 'ahmad_dahlan',
            'email' => 'ahmad@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'academic_year_id' => $this->academicYear->id,
            'name' => 'VII-A',
            'tingkat' => '7',
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'institution_id' => $this->institution->id,
            'name' => 'Informatika',
            'kode' => 'INF-7',
            'is_active' => true,
        ]);

        $this->actingAs($this->guru);
    }

    /**
     * Test 1: Cetak PDF RPPM & Perangkat Pembelajaran (Modul Ajar)
     */
    public function test_rppm_can_be_printed_to_pdf(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Berpikir Komputasional',
            'deskripsi_cp' => 'Peserta didik mampu menerapkan berpikir komputasional.',
        ]);

        $tp = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'capaian_pembelajaran_id' => $cp->id,
            'kelas_tingkat' => '7',
            'materi' => 'Algoritma Pemrograman',
            'deskripsi_tp' => 'Memahami algoritma sekuensial dan percabangan',
        ]);

        $rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'capaian_pembelajaran_id' => $cp->id,
            'selected_tp_ids' => [$tp->id],
            'kelas_fase' => 'Kelas 7 / Fase D',
            'mata_pelajaran' => 'Informatika',
            'topik' => 'Algoritma & Pemrograman Dasar',
            'alokasi_waktu' => '2 x 40 Menit',
            'status' => 'published',
            'content' => [
                'capaian_pembelajaran' => $cp->deskripsi_cp,
                'lintas_disiplin' => 'Matematika & Logika',
                'praktik_pedagogis' => 'Problem-Based Learning',
            ],
        ]);

        $response = $this->get(route('academic.rppm.pdf', $rppm));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 2: Cetak PDF Jurnal Mengajar Sesi Harian
     */
    public function test_teaching_journal_can_be_printed_to_pdf(): void
    {
        $journal = TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => 1,
            'materi' => 'Pengenalan Algoritma Dasar',
            'rencana_kegiatan_guru' => 'Menjelaskan konsep flowchart',
            'kegiatan_siswa' => 'Membuat flowchart sederhana',
            'refleksi_guru' => 'Siswa sangat aktif bertanya',
            'tindak_lanjut' => 'Latihan studi kasus pertemuan depan',
        ]);

        $response = $this->get(route('academic.journal.pdf', $journal));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 3: Cetak PDF Rekapitulasi Jurnal Mengajar
     */
    public function test_teaching_journal_rekap_can_be_printed_to_pdf(): void
    {
        TeachingJournal::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'tanggal' => now()->toDateString(),
            'jam_ke' => 1,
            'materi' => 'Pengenalan Algoritma Dasar',
        ]);

        $response = $this->get(route('academic.journal.rekap.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 4: Cetak PDF Alur Tujuan Pembelajaran (ATP)
     */
    public function test_atp_can_be_printed_to_pdf(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Teknologi Informasi & Komunikasi',
            'deskripsi_cp' => 'Menerapkan pemanfaatan teknologi informasi.',
        ]);

        $atp = AlurTujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'capaian_pembelajaran_id' => $cp->id,
            'kode_atp' => '1',
            'judul_atp' => 'Alur Tujuan Pembelajaran Informatika Kelas 7',
            'tingkat' => '7',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
        ]);

        $response = $this->get(route('academic.atp.pdf', $atp));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 5: Cetak PDF Capaian Pembelajaran (CP) dan Rekap CP
     */
    public function test_cp_and_rekap_can_be_printed_to_pdf(): void
    {
        $cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'tingkat' => '7',
            'elemen' => 'Sistem Komputer',
            'deskripsi_cp' => 'Memahami perangkat keras dan lunak komputer.',
        ]);

        $responseSingle = $this->get(route('academic.cp.pdf', $cp));
        $responseSingle->assertOk();
        $this->assertStringContainsString('application/pdf', $responseSingle->headers->get('Content-Type'));

        $responseRekap = $this->get(route('academic.cp.rekap.pdf'));
        $responseRekap->assertOk();
        $this->assertStringContainsString('application/pdf', $responseRekap->headers->get('Content-Type'));
    }

    /**
     * Test 6: Cetak PDF Rekap Tujuan Pembelajaran (TP)
     */
    public function test_tp_rekap_can_be_printed_to_pdf(): void
    {
        $response = $this->get(route('academic.tp.rekap.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 7: Cetak PDF Biodata Lengkap Siswa
     */
    public function test_student_biodata_can_be_printed_to_pdf(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Fatih Al-Ayyubi',
            'nis' => '2026001',
            'nisn' => '0012345678',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2012-08-17',
            'alamat_siswa' => 'Jl. Ketintang Baru No. 12',
            'no_telepon_siswa' => '081234567890',
            'nama_ayah' => 'Bambang Supriyanto',
            'nama_ibu' => 'Siti Nurhaliza',
            'is_active' => true,
        ]);

        $response = $this->get(route('academic.student.pdf', $student));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 8: Cetak PDF Surat Permohonan Cuti/Izin Guru
     */
    public function test_leave_request_can_be_printed_to_pdf(): void
    {
        $leave = TeacherLeaveRequest::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'jenis_cuti' => 'sakit',
            'tipe_izin' => 'harian',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'alasan' => 'Pemeriksaan kesehatan rawat jalan',
            'status' => 'disetujui_wakakur',
        ]);

        $response = $this->get(route('academic.leave-request.pdf', $leave));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 9: Cetak PDF Notulen Rapat
     */
    public function test_meeting_minute_can_be_printed_to_pdf(): void
    {
        $minute = MeetingMinute::create([
            'institution_id' => $this->institution->id,
            'tanggal' => now()->toDateString(),
            'judul' => 'Rapat Koordinasi Persiapan Penilaian Akhir Semester',
            'isi' => 'Rapat membahas penyusunan jadwal PAS, kisi-kisi soal, dan teknis pengawasan.',
            'visibility' => 'internal',
            'attendees' => ['Ahmad Dahlan', 'Waka Kurikulum', 'Kepala Sekolah'],
        ]);

        $response = $this->get(route('academic.meeting-minute.pdf', $minute));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 10: Cetak PDF Lembar Kasus Siswa
     */
    public function test_student_case_can_be_printed_to_pdf(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Santri Binaan',
            'nis' => '2026002',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'is_active' => true,
        ]);

        $case = StudentCase::create([
            'institution_id' => $this->institution->id,
            'student_id' => $student->id,
            'wali_kelas_id' => $this->guru->id,
            'tanggal_kejadian' => now()->toDateString(),
            'kategori_kasus' => ['Keterlambatan'],
            'poin_pelanggaran' => 5,
            'deskripsi' => 'Terlambat mengikuti apel pagi karena ban bocor',
            'tindak_lanjut' => 'Diberikan pembinaan kedisiplinan dan nasihat',
            'status' => 'selesai',
        ]);

        $response = $this->get(route('academic.student-case.pdf', $case));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 11: Cetak PDF Piagam / Bukti Prestasi Siswa
     */
    public function test_student_achievement_can_be_printed_to_pdf(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'name' => 'Ahmad Juara',
            'nis' => '2026003',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-05-10',
            'is_active' => true,
        ]);

        $achievement = StudentAchievement::create([
            'institution_id' => $this->institution->id,
            'student_id' => $student->id,
            'classroom_id' => $this->classroom->id,
            'nama_prestasi' => 'Olimpiade Sains Nasional Informatika',
            'kategori' => 'akademik',
            'peringkat' => 'Juara 1 Emas',
            'tingkat' => 'Provinsi',
            'tanggal' => now()->toDateString(),
            'poin_reward' => 20,
        ]);

        $response = $this->get(route('academic.student-achievement.pdf', $achievement));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * Test 12: Cetak PDF Sertifikat & Portofolio Guru
     */
    public function test_teacher_certificate_can_be_printed_to_pdf(): void
    {
        $cert = TeacherCertificate::create([
            'institution_id' => $this->institution->id,
            'teacher_id' => $this->guru->id,
            'nama_kegiatan' => 'Pelatihan Implementasi Kurikulum Merdeka Terintegrasi',
            'kategori' => 'Pelatihan / Bimtek',
            'penyelenggara' => 'Balai Besar Guru Penggerak (BBGP)',
            'nomor_sertifikat' => 'CERT-2026-BBGP-0091',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'jumlah_jam' => 32,
            'drive_url' => 'https://drive.google.com/file/d/sample',
        ]);

        $response = $this->get(route('academic.certificate.pdf', $cert));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
