<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ParentSummonResource;
use App\Filament\Staff\Resources\StudentCaseResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentCase;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCaseNavigationGroupKesiswaanTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKesiswaanOnly;
    protected User $wakaKesiswaanAndWaliKelas;
    protected User $waliKelasOnly;
    protected Classroom $classroom;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        Institution::create([
            'id' => 1,
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
            'type' => 'yayasan',
        ]);

        $this->institution = Institution::create([
            'id' => 2,
            'name' => 'SMP IT Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
            'type' => 'smp',
        ]);

        $this->wakaKesiswaanOnly = User::create([
            'name' => 'Ustadz Kesiswaan',
            'email' => 'kesiswaan@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->wakaKesiswaanOnly->assignRole('waka_kesiswaan');

        $this->wakaKesiswaanAndWaliKelas = User::create([
            'name' => 'Ustadz Kesiswaan Sekaligus Wali Kelas',
            'email' => 'kesiswaan.wali@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->wakaKesiswaanAndWaliKelas->assignRole(['waka_kesiswaan', 'wali_kelas']);

        $this->waliKelasOnly = User::create([
            'name' => 'Guru Wali Kelas Murni',
            'email' => 'walikelas@example.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->waliKelasOnly->assignRole('wali_kelas');

        $this->classroom = Classroom::create([
            'institution_id' => 2,
            'name' => 'Kelas 8A',
            'tingkat' => '8',
            'wali_kelas_id' => $this->wakaKesiswaanAndWaliKelas->id,
            'tipe' => Classroom::TIPE_REGULER,
        ]);

        $this->student = Student::create([
            'institution_id' => 2,
            'classroom_id' => $this->classroom->id,
            'name' => 'Santri Bermasalah',
            'nis' => '8001',
            'status_siswa' => 'aktif',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2011-01-01',
        ]);
    }

    public function test_pelanggaran_siswa_is_in_kesiswaan_dan_karakter_for_waka_kesiswaan(): void
    {
        $this->actingAs($this->wakaKesiswaanOnly);

        // Navigation group must be 'Kesiswaan & Karakter' (Tugas Jabatan Kesiswaan), NOT 'Fitur Wali Kelas'
        $this->assertEquals('Kesiswaan & Karakter', StudentCaseResource::getNavigationGroup());
        $this->assertEquals('Kesiswaan & Karakter', ParentSummonResource::getNavigationGroup());
    }

    public function test_pelanggaran_siswa_stays_in_kesiswaan_dan_karakter_even_if_user_has_dual_role_wali_kelas(): void
    {
        // When logged in as Waka Kesiswaan who also has role wali_kelas
        $this->actingAs($this->wakaKesiswaanAndWaliKelas);

        // MUST be in 'Kesiswaan & Karakter' (Tugas Jabatan Kesiswaan), NOT 'Fitur Wali Kelas'
        $this->assertEquals('Kesiswaan & Karakter', StudentCaseResource::getNavigationGroup());
        $this->assertEquals('Kesiswaan & Karakter', ParentSummonResource::getNavigationGroup());
    }

    public function test_pelanggaran_siswa_is_in_fitur_wali_kelas_only_for_pure_wali_kelas(): void
    {
        $this->actingAs($this->waliKelasOnly);

        // For pure wali kelas without kesiswaan role, it stays in 'Fitur Wali Kelas'
        $this->assertEquals('Fitur Wali Kelas', StudentCaseResource::getNavigationGroup());
        $this->assertEquals('Fitur Wali Kelas', ParentSummonResource::getNavigationGroup());
    }

    public function test_waka_kesiswaan_can_create_and_view_student_case(): void
    {
        $this->actingAs($this->wakaKesiswaanOnly);

        $this->assertTrue(StudentCaseResource::canCreate());
        $this->assertTrue(StudentCaseResource::canAccess());

        // Create a case handled directly by kesiswaan
        $case = StudentCase::create([
            'nomor_kasus' => 'KASUS-2026-001',
            'student_id' => $this->student->id,
            'wali_kelas_id' => $this->classroom->wali_kelas_id,
            'judul_kasus' => 'Terlambat Berulang Kali',
            'deskripsi' => 'Deskripsi pelanggaran siswa terlambat masuk sekolah.',
            'kronologi' => 'Siswa terlambat lebih dari 3 kali berturut-turut.',
            'kategori' => 'sedang',
            'tanggal_kejadian' => '2026-11-03',
            'ditangani_oleh' => 'kesiswaan',
            'recommended_to_kesiswaan' => true,
            'status' => 'proses',
        ]);

        $this->assertTrue(StudentCaseResource::canEdit($case));

        // Ensure case is in query for waka kesiswaan
        $queryCases = StudentCaseResource::getEloquentQuery()->get();
        $this->assertTrue($queryCases->contains('id', $case->id));
    }
}
