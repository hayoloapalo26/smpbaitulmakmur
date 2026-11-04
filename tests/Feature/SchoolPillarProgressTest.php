<?php

namespace Tests\Feature;

use App\Models\BkWorkProgram;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\ProgramHumas;
use App\Models\ProgramKesiswaan;
use App\Models\ProgramKurikulum;
use App\Models\ProgramSarpras;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use App\Models\User;
use App\Services\SchoolPillarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SchoolPillarProgressTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $smp;
    protected User $kepalaSekolah;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->smp = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->kepalaSekolah = User::create([
            'name' => 'Kepala Sekolah',
            'email' => 'kepsek_pilar@baitulmakmur.sch.id',
            'password' => bcrypt('password'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        // Seed 9 pillars
        $pillarNames = [
            1 => 'Bidang Manajemen',
            2 => 'Mutu Lulusan',
            3 => 'Bidang Kurikulum dan Pembelajaran',
            4 => 'PTK (Pendidik dan Tenaga Kependidikan)',
            5 => 'Kesiswaan',
            6 => 'Sarpras (Sarana Prasarana)',
            7 => 'Keuangan',
            8 => 'Kehumasan',
            9 => 'Layanan Khusus Sekolah',
        ];

        foreach ($pillarNames as $order => $name) {
            SchoolPillar::create([
                'institution_id' => $this->smp->id,
                'name' => $name,
                'order' => $order,
            ]);
        }
    }

    /**
     * Test bahwa Pilar 1 memiliki 7 indikator turunan dari 7 pilar konstituen.
     */
    public function test_pilar_1_contains_seven_constituent_indicators(): void
    {
        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        $this->assertNotNull($pilar1);
        $this->assertEquals(1, $pilar1['order']);
        $this->assertEquals('Bidang Manajemen', $pilar1['name']);
        $this->assertEquals(7, $pilar1['total']); // 7 pilar konstituen
        $this->assertCount(7, $pilar1['tasks']);

        $taskCodes = collect($pilar1['tasks'])->pluck('code')->all();
        $this->assertContains('1.1', $taskCodes);
        $this->assertContains('1.2', $taskCodes);
        $this->assertContains('1.3', $taskCodes);
        $this->assertContains('1.4', $taskCodes);
        $this->assertContains('1.5', $taskCodes);
        $this->assertContains('1.6', $taskCodes);
        $this->assertContains('1.7', $taskCodes);
    }

    /**
     * Test jika ke-7 pilar mencapai indikator 100, maka Pilar Bidang Manajemen menjadi 100%.
     */
    public function test_pilar_1_reaches_100_percent_when_all_seven_constituents_reach_100(): void
    {
        // 1. Mutu Lulusan (Pilar 2) -> Set all tasks to 'selesai'
        $p2 = SchoolPillar::where('order', 2)->first();
        SchoolPillarTask::create([
            'school_pillar_id' => $p2->id,
            'name' => 'Evaluasi Profil Lulusan',
            'code' => '2.1',
            'status' => 'selesai',
        ]);

        // 2. Kurikulum (Pilar 3) -> Set ProgramKurikulum all 'Selesai'
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Penyusunan KOSP & Modul',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 3. PTK (Pilar 4) -> Set tasks all 'selesai'
        $p4 = SchoolPillar::where('order', 4)->first();
        SchoolPillarTask::create([
            'school_pillar_id' => $p4->id,
            'name' => 'Supervisi PTK',
            'code' => '4.1',
            'status' => 'selesai',
        ]);

        // 4. Kesiswaan (Pilar 5) -> Set ProgramKesiswaan all 'Selesai'
        ProgramKesiswaan::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'LDKS & Ekstrakurikuler',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 5. Sarpras (Pilar 6) -> Set ProgramSarpras all 'Selesai'
        ProgramSarpras::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Perawatan Lab & Kelas',
            'kategori' => 'Sarpras',
            'tipe_pelaksanaan' => 'Bulanan',
            'sifat' => 'Wajib',
            'tujuan' => 'Menjaga kelayakan sarana belajar',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 6. Humas (Pilar 8) -> Set ProgramHumas all 'Selesai'
        ProgramHumas::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'tahun_ajaran' => '2026/2027',
            'nama_program' => 'Pertemuan Wali Murid',
            'kategori' => 'Humas',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'tujuan' => 'Sosialisasi program sekolah dan evaluasi belajar',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // 7. BK (Pilar 9) -> Set BkWorkProgram all 'Terlaksana'
        BkWorkProgram::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'judul_program' => 'Konseling Peminatan Siswa',
            'bidang_layanan' => 'Karir',
            'sasaran' => 'Kelas 9',
            'waktu_pelaksanaan' => 'Semester 1',
            'tujuan' => 'Mengarahkan studi lanjut dan peminatan',
            'status' => 'Terlaksana',
        ]);

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        // Verifikasi ke-7 pilar masing-masing bernilai 100%
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 2)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 3)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 4)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 5)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 6)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 8)['percentage']);
        $this->assertEquals(100, collect($pillars)->firstWhere('order', 9)['percentage']);

        // Verifikasi Pilar 1 (Bidang Manajemen) menjadi 100% dan 7/7 tugas tuntas
        $this->assertEquals(100, $pilar1['percentage']);
        $this->assertEquals(7, $pilar1['selesai']);
        $this->assertEquals(7, $pilar1['total']);

        // Semua indikator di Pilar 1 berstatus 'selesai'
        foreach ($pilar1['tasks'] as $task) {
            $this->assertEquals('selesai', $task->status);
        }
    }

    /**
     * Test ketercapaian Pilar 1 proporsional dan akurat saat sebagian pilar belum 100%.
     */
    public function test_pilar_1_calculates_proportional_average_accurately(): void
    {
        // 1. Kurikulum (Order 3): 1 selesai, 1 belum = 50%
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program 1',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);
        ProgramKurikulum::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program 2',
            'kategori' => 'Kurikulum',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Belum Selesai',
        ]);

        // 2. Kesiswaan (Order 5): 1 selesai = 100%
        ProgramKesiswaan::create([
            'institution_id' => $this->smp->id,
            'user_id' => $this->kepalaSekolah->id,
            'nama_program' => 'Program OSIS',
            'kategori' => 'Kesiswaan',
            'tipe_pelaksanaan' => 'Semesteran',
            'sifat' => 'Wajib',
            'status_pelaksanaan' => 'Selesai',
        ]);

        // Pilar 2, 4, 6, 8, 9 masih 0%
        // Nilai pilar: 0, 50, 0, 100, 0, 0, 0
        // Rata-rata = (0 + 50 + 0 + 100 + 0 + 0 + 0) / 7 = 150 / 7 = 21.42... -> 21%

        $pillars = SchoolPillarService::getPillarsData($this->smp->id, $this->kepalaSekolah);
        $pilar1 = collect($pillars)->firstWhere('order', 1);

        $this->assertEquals(21, $pilar1['percentage']);
        $this->assertEquals(1, $pilar1['selesai']); // Hanya Pilar Kesiswaan yang sudah 100%
        $this->assertEquals(7, $pilar1['total']);
    }

    /**
     * Test halaman Dashboard Staff dapat memuat dan merender data 9 pilar tanpa error.
     */
    public function test_dashboard_staff_renders_updated_nine_pillars_cleanly(): void
    {
        $this->actingAs($this->kepalaSekolah);
        try {
            \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));
        } catch (\Throwable $e) {}

        \Livewire\Livewire::test(\App\Filament\Staff\Pages\Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Bidang Manajemen')
            ->assertSee('Mutu Lulusan')
            ->assertSee('Bidang Kurikulum');
    }
}
