<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\BkWorkProgramResource;
use App\Filament\Staff\Resources\ClassProgramResource;
use App\Filament\Staff\Resources\OsisProgramResource;
use App\Filament\Staff\Resources\ProgramHumasResource;
use App\Filament\Staff\Resources\ProgramKepsekResource;
use App\Filament\Staff\Resources\ProgramKesiswaanResource;
use App\Filament\Staff\Resources\ProgramKurikulumResource;
use App\Filament\Staff\Resources\ProgramSarprasResource;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\OsisProgram;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KepalaSekolahWorkProgramAccessAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $principal;
    protected User $wakaKurikulum;
    protected User $wakaKesiswaan;
    protected User $bk;
    protected User $waliKelas;
    protected User $sarpras;
    protected User $humas;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            'kepala_sekolah',
            'superadmin',
            'waka_kurikulum',
            'waka_kesiswaan',
            'bk',
            'wali_kelas',
            'sarpras',
            'humas',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->principal = $this->createUserWithRole('kepala_sekolah', 'kepsek_test');
        $this->wakaKurikulum = $this->createUserWithRole('waka_kurikulum', 'wkr_test');
        $this->wakaKesiswaan = $this->createUserWithRole('waka_kesiswaan', 'wks_test');
        $this->bk = $this->createUserWithRole('bk', 'bk_test');
        $this->waliKelas = $this->createUserWithRole('wali_kelas', 'walikelas_test');
        $this->sarpras = $this->createUserWithRole('sarpras', 'sarpras_test');
        $this->humas = $this->createUserWithRole('humas', 'humas_test');
    }

    private function createUserWithRole(string $role, string $username): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'username' => $username,
            'email' => "{$username}@example.com",
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_kepala_sekolah_can_see_all_work_programs_in_navigation_and_has_access(): void
    {
        $this->actingAs($this->principal);

        $this->assertTrue(ProgramKurikulumResource::shouldRegisterNavigation(), 'Kurikulum proker harus muncul di navigasi Kepsek');
        $this->assertTrue(ProgramKurikulumResource::canAccess(), 'Kepsek harus punya akses ke proker Kurikulum');

        $this->assertTrue(ProgramKesiswaanResource::shouldRegisterNavigation(), 'Kesiswaan proker harus muncul di navigasi Kepsek');
        $this->assertTrue(ProgramKesiswaanResource::canAccess(), 'Kepsek harus punya akses ke proker Kesiswaan');

        $this->assertTrue(BkWorkProgramResource::shouldRegisterNavigation(), 'BK proker harus muncul di navigasi Kepsek');
        $this->assertTrue(BkWorkProgramResource::canAccess(), 'Kepsek harus punya akses ke proker BK');

        $this->assertTrue(OsisProgramResource::shouldRegisterNavigation(), 'OSIS proker harus muncul di navigasi Kepsek');
        $this->assertTrue(OsisProgramResource::canAccess(), 'Kepsek harus punya akses ke proker OSIS');

        $this->assertTrue(ClassProgramResource::shouldRegisterNavigation(), 'Class proker harus muncul di navigasi Kepsek');
        $this->assertTrue(ClassProgramResource::canAccess(), 'Kepsek harus punya akses ke proker Kelas');

        $this->assertTrue(ProgramSarprasResource::shouldRegisterNavigation(), 'Sarpras proker harus muncul di navigasi Kepsek');
        $this->assertTrue(ProgramSarprasResource::canAccess(), 'Kepsek harus punya akses ke proker Sarpras');

        $this->assertTrue(ProgramHumasResource::shouldRegisterNavigation(), 'Humas proker harus muncul di navigasi Kepsek');
        $this->assertTrue(ProgramHumasResource::canAccess(), 'Kepsek harus punya akses ke proker Humas');

        $this->assertTrue(ProgramKepsekResource::canAccess(), 'Kepsek harus punya akses ke proker Sekolah/Kepsek');
    }

    public function test_respective_roles_can_access_their_own_work_programs(): void
    {
        $this->actingAs($this->wakaKurikulum);
        $this->assertTrue(ProgramKurikulumResource::shouldRegisterNavigation());
        $this->assertTrue(ProgramKurikulumResource::canAccess());

        $this->actingAs($this->wakaKesiswaan);
        $this->assertTrue(ProgramKesiswaanResource::shouldRegisterNavigation());
        $this->assertTrue(ProgramKesiswaanResource::canAccess());
        $this->assertTrue(OsisProgramResource::shouldRegisterNavigation());
        $this->assertTrue(OsisProgramResource::canAccess());

        $this->actingAs($this->bk);
        $this->assertTrue(BkWorkProgramResource::shouldRegisterNavigation());
        $this->assertTrue(BkWorkProgramResource::canAccess());

        $this->actingAs($this->waliKelas);
        $this->assertTrue(ClassProgramResource::shouldRegisterNavigation());
        $this->assertTrue(ClassProgramResource::canAccess());

        $this->actingAs($this->sarpras);
        $this->assertTrue(ProgramSarprasResource::shouldRegisterNavigation());
        $this->assertTrue(ProgramSarprasResource::canAccess());

        $this->actingAs($this->humas);
        $this->assertTrue(ProgramHumasResource::shouldRegisterNavigation());
        $this->assertTrue(ProgramHumasResource::canAccess());
    }

    public function test_kepala_sekolah_can_approve_and_give_notes_for_all_work_programs(): void
    {
        $this->actingAs($this->principal);

        // 1. Kurikulum
        $prokerKurikulum = ProgramKurikulum::create([
            'user_id' => $this->wakaKurikulum->id,
            'institution_id' => $this->institution->id,
            'nama_program' => 'Penyusunan Silabus Kurikulum 2026',
            'tahun_ajaran' => '2026/2027',
            'kategori' => 'Kurikulum & Pembelajaran',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'status' => 'Rencana',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerKurikulum->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC, lanjutkan koordinasi dewan guru.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerKurikulum->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerKurikulum->fresh()->approved_by);

        // 2. Kesiswaan
        $prokerKesiswaan = ProgramKesiswaan::create([
            'user_id' => $this->wakaKesiswaan->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Latihan Dasar Kepemimpinan Siswa (LDKS)',
            'kategori' => 'Kepemimpinan & Karakter',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Melatih kepemimpinan siswa',
            'status_pelaksanaan' => 'Rencana',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerKesiswaan->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC Kesiswaan mantap.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerKesiswaan->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerKesiswaan->fresh()->approved_by);

        // 3. BK
        $prokerBk = BkWorkProgram::create([
            'user_id' => $this->bk->id,
            'judul_program' => 'Program Bimbingan Karir & Studi Lanjut Siswa',
            'bidang_layanan' => 'Karir',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'sasaran' => 'Siswa Kelas 9',
            'waktu_pelaksanaan' => 'Semester Genap',
            'tahun_ajaran' => '2026/2027',
            'tujuan' => 'Mempersiapkan siswa memilih SMA/SMK yang tepat',
            'status' => 'Rencana',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerBk->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC BK, pastikan melibatkan wali murid.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerBk->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerBk->fresh()->approved_by);

        // 4. OSIS
        $prokerOsis = OsisProgram::create([
            'nama_program' => 'Classmeeting Seni & Olahraga',
            'tanggal' => '2026-12-15',
            'status' => 'direncanakan',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerOsis->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC OSIS, jaga ketertiban.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerOsis->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerOsis->fresh()->approved_by);

        // 5. Kelas
        $classroom = Classroom::create([
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->waliKelas->id,
            'name' => '7A - Abu Bakar',
            'tingkat' => 7,
            'is_active' => true,
        ]);
        $prokerKelas = ClassProgram::create([
            'classroom_id' => $classroom->id,
            'wali_kelas_id' => $this->waliKelas->id,
            'nama_program' => 'Pojok Baca & Literasi Harian',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'kategori' => 'lingkungan_sarpras',
            'tipe_pelaksanaan' => 'harian',
            'sasaran' => 'Siswa 7A',
            'waktu_pelaksanaan' => 'Setiap pagi 15 menit',
            'status' => 'rencana',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerKelas->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC Kelas, program literasi sangat bagus.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerKelas->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerKelas->fresh()->approved_by);

        // 6. Sarpras
        $prokerSarpras = ProgramSarpras::create([
            'user_id' => $this->sarpras->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Pengadaan Proyektor Kelas',
            'kategori' => 'Pengadaan',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Mendukung pembelajaran interaktif',
            'status_pelaksanaan' => 'Belum',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerSarpras->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC Sarpras, pilih spesifikasi hemat energi.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerSarpras->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerSarpras->fresh()->approved_by);

        // 7. Humas
        $prokerHumas = ProgramHumas::create([
            'user_id' => $this->humas->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Kunjungan Edukatif ke Kampus Mitra',
            'kategori' => 'Kemitraan',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Memperluas wawasan karir dan pendidikan lanjutan',
            'status_pelaksanaan' => 'Belum',
            'status_persetujuan' => 'Menunggu Persetujuan',
        ]);
        $prokerHumas->update([
            'status_persetujuan' => 'Disetujui',
            'catatan_kepsek' => 'ACC Humas, konfirmasi pihak kampus terlebih dahulu.',
            'approved_by' => $this->principal->id,
            'tanggal_persetujuan' => now(),
        ]);
        $this->assertEquals('Disetujui', $prokerHumas->fresh()->status_persetujuan);
        $this->assertEquals($this->principal->id, $prokerHumas->fresh()->approved_by);
    }

    public function test_kepala_sekolah_cannot_create_edit_or_delete_other_roles_work_programs(): void
    {
        $this->actingAs($this->principal);

        // Kepala sekolah cannot create, edit, or delete kurikulum proker
        $this->assertFalse(ProgramKurikulumResource::canCreate());
        $this->assertFalse(ProgramKurikulumResource::canEdit(new ProgramKurikulum()));
        $this->assertFalse(ProgramKurikulumResource::canDelete(new ProgramKurikulum()));

        // Kepala sekolah cannot create, edit, or delete kesiswaan proker
        $this->assertFalse(ProgramKesiswaanResource::canCreate());
        $this->assertFalse(ProgramKesiswaanResource::canEdit(new ProgramKesiswaan()));
        $this->assertFalse(ProgramKesiswaanResource::canDelete(new ProgramKesiswaan()));

        // Kepala sekolah cannot create, edit, or delete sarpras proker
        $this->assertFalse(ProgramSarprasResource::canCreate());
        $this->assertFalse(ProgramSarprasResource::canEdit(new ProgramSarpras()));
        $this->assertFalse(ProgramSarprasResource::canDelete(new ProgramSarpras()));

        // Kepala sekolah cannot create, edit, or delete bk proker
        $this->assertFalse(BkWorkProgramResource::canCreate());
        $this->assertFalse(BkWorkProgramResource::canEdit(new BkWorkProgram()));
        $this->assertFalse(BkWorkProgramResource::canDelete(new BkWorkProgram()));

        // Kepala sekolah cannot create, edit, or delete osis proker
        $this->assertFalse(OsisProgramResource::canCreate());
        $this->assertFalse(OsisProgramResource::canEdit(new OsisProgram()));
        $this->assertFalse(OsisProgramResource::canDelete(new OsisProgram()));

        // Kepala sekolah cannot create, edit, or delete class proker
        $this->assertFalse(ClassProgramResource::canCreate());
        $this->assertFalse(ClassProgramResource::canEdit(new ClassProgram()));
        $this->assertFalse(ClassProgramResource::canDelete(new ClassProgram()));

        // Kepala sekolah cannot create, edit, or delete humas proker
        $this->assertFalse(ProgramHumasResource::canCreate());
        $this->assertFalse(ProgramHumasResource::canEdit(new ProgramHumas()));
        $this->assertFalse(ProgramHumasResource::canDelete(new ProgramHumas()));
    }
}
