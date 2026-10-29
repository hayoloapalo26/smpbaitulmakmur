<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ProgramKepsekResource;
use App\Models\BkWorkProgram;
use App\Models\ClassProgram;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\OsisProgram;
use App\Models\ProgramHumas;
use App\Models\ProgramKepsek;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\User;
use App\Models\YayasanProgram;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramKepsekSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institutionA;
    protected Institution $institutionB;
    protected User $kepsek;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        $this->institutionA = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->institutionB = Institution::firstOrCreate(
            ['slug' => 'sma-islam-baitul-makmur'],
            ['name' => 'SMA Islam Baitul Makmur']
        );

        $this->kepsek = User::create([
            'name' => 'Kepala Sekolah SMP',
            'username' => 'kepsek_smp',
            'email' => 'kepsek@smp.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => $this->institutionA->id,
        ]);
        $this->kepsek->assignRole('kepala_sekolah');
    }

    public function test_sync_from_all_divisions_and_yayasan_pulls_all_programs_correctly(): void
    {
        // 1. Yayasan program assigned to Institution A
        $yayasanProgramA = YayasanProgram::create([
            'name' => 'Instruksi Yayasan: Standarisasi Mutu 2026',
            'description' => 'Target peningkatan akreditasi dan mutu pembelajaran.',
        ]);
        $yayasanProgramA->institutions()->attach($this->institutionA->id, [
            'is_completed' => true,
            'notes' => 'Telah dipersiapkan',
        ]);

        // 2. Yayasan program assigned ONLY to Institution B (should NOT be pulled for Inst A)
        $yayasanProgramB = YayasanProgram::create([
            'name' => 'Instruksi Yayasan Khusus SMA',
            'description' => 'Target untuk SMA saja.',
        ]);
        $yayasanProgramB->institutions()->attach($this->institutionB->id, [
            'is_completed' => false,
        ]);

        // 3. Program Kurikulum
        $kurikulum = ProgramKurikulum::create([
            'user_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'Supervisi Akademik Semester Ganjil',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Terjadwal',
            'tujuan' => 'Meningkatkan mutu pembelajaran di kelas.',
            'waktu_pelaksanaan' => 'Oktober 2026',
            'status_pelaksanaan' => 'Sedang Berjalan',
        ]);

        // 4. Program Kesiswaan
        $kesiswaan = ProgramKesiswaan::create([
            'user_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'LDKS Pengurus OSIS 2026',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Melatih kepemimpinan siswa.',
            'waktu_pelaksanaan' => 'November 2026',
            'status_pelaksanaan' => 'Rencana',
        ]);

        // 5. Program Sarpras
        $sarpras = ProgramSarpras::create([
            'user_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Peremajaan PC Lab Komputer',
            'kategori' => 'Sarpras',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Terjadwal',
            'tujuan' => 'Menunjang asesmen nasional dan ujian berbasis komputer.',
            'waktu_pelaksanaan' => 'Desember 2026',
        ]);

        // 6. Program Humas
        $humas = ProgramHumas::create([
            'user_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Parenting & Sosialisasi Perangkat Ajar',
            'kategori' => 'Humas',
            'tipe_pelaksanaan' => 'Tahunan',
            'sifat' => 'Wajib',
            'tujuan' => 'Membangun sinergi dengan wali murid.',
            'waktu_pelaksanaan' => 'September 2026',
        ]);

        // 7. BK Work Program
        $bk = BkWorkProgram::create([
            'user_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
            'judul_program' => 'Konseling Karir dan Minat Bakat',
            'bidang_layanan' => 'Karir',
            'sasaran' => 'Siswa Kelas IX',
            'waktu_pelaksanaan' => 'Januari 2027',
            'tujuan' => 'Persiapan penjurusan jenjang SMA/SMK.',
            'status' => 'Rencana',
        ]);

        // 8. Classroom & Class Program
        $classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'wali_kelas_id' => $this->kepsek->id,
            'institution_id' => $this->institutionA->id,
        ]);

        $classProgram = ClassProgram::create([
            'institution_id' => $this->institutionA->id,
            'classroom_id' => $classroom->id,
            'wali_kelas_id' => $this->kepsek->id,
            'nama_program' => 'Pojok Baca & Literasi Digital Kelas VII-A',
            'kategori' => 'kegiatan_kreatif',
            'tujuan' => 'Meningkatkan minat baca siswa di kelas.',
            'waktu_pelaksanaan' => 'Setiap Hari Jumat',
        ]);

        // 9. Osis Program
        $osis = OsisProgram::create([
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'Peringatan Bulan Bahasa 2026',
            'tanggal' => '2026-10-28',
            'status' => 'selesai',
        ]);

        // Run sync method
        $result = ProgramKepsek::syncFromAllDivisionsAndYayasan($this->institutionA->id, $this->kepsek->id);

        $this->assertEquals(8, $result['total']);
        $this->assertEquals(1, $result['details']['yayasan']);
        $this->assertEquals(1, $result['details']['kurikulum']);
        $this->assertEquals(1, $result['details']['kesiswaan']);
        $this->assertEquals(1, $result['details']['sarpras']);
        $this->assertEquals(1, $result['details']['humas']);
        $this->assertEquals(1, $result['details']['bk']);
        $this->assertEquals(1, $result['details']['wali_kelas']);
        $this->assertEquals(1, $result['details']['osis']);

        // Assert records exist in program_kepseks
        $this->assertDatabaseHas('program_kepseks', [
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'Instruksi Yayasan: Standarisasi Mutu 2026',
            'kategori' => 'Program Yayasan',
            'status_pelaksanaan' => 'Selesai', // because pivot is_completed = true
            'source_type' => YayasanProgram::class,
            'source_id' => $yayasanProgramA->id,
        ]);

        $this->assertDatabaseMissing('program_kepseks', [
            'nama_program' => 'Instruksi Yayasan Khusus SMA',
        ]);

        $this->assertDatabaseHas('program_kepseks', [
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'Supervisi Akademik Semester Ganjil',
            'kategori' => 'Kurikulum',
            'source_type' => ProgramKurikulum::class,
            'source_id' => $kurikulum->id,
        ]);

        $this->assertDatabaseHas('program_kepseks', [
            'institution_id' => $this->institutionA->id,
            'nama_program' => 'Konseling Karir dan Minat Bakat',
            'kategori' => 'Bimbingan Konseling',
            'source_type' => BkWorkProgram::class,
            'source_id' => $bk->id,
        ]);

        // Run sync a SECOND time to verify duplication prevention
        $secondResult = ProgramKepsek::syncFromAllDivisionsAndYayasan($this->institutionA->id, $this->kepsek->id);
        $this->assertEquals(0, $secondResult['total'], 'Second sync should not create duplicate programs.');
    }

    public function test_manage_program_kepseks_page_renders_with_sync_action_for_kepala_sekolah(): void
    {
        $this->actingAs($this->kepsek);

        $response = $this->get('/staff/program-kepseks');
        $response->assertSuccessful();

        Livewire::test(ProgramKepsekResource\Pages\ManageProgramKepseks::class)
            ->assertActionExists('syncFromAllDivisions')
            ->assertActionExists('create');
    }
}
