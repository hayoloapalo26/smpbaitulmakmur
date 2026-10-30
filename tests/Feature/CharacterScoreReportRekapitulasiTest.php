<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\CharacterScoreReport;
use App\Models\CharacterScoreLog;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CharacterScoreReportRekapitulasiTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $kepsek;
    protected User $wakaKesiswaan;
    protected User $waliKelas7A;
    protected User $waliKelas7B;
    protected User $guruBiasa;
    protected Classroom $class7A;
    protected Classroom $class7B;
    protected Student $student7A;
    protected Student $student7B;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smpi']
        );

        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->kepsek = User::factory()->create(['institution_id' => $this->institution->id]);
        $this->kepsek->assignRole('kepala_sekolah');

        $this->wakaKesiswaan = User::factory()->create(['institution_id' => $this->institution->id]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->waliKelas7A = User::factory()->create(['institution_id' => $this->institution->id]);
        $this->waliKelas7A->assignRole('wali_kelas');

        $this->waliKelas7B = User::factory()->create(['institution_id' => $this->institution->id]);
        $this->waliKelas7B->assignRole('wali_kelas');

        $this->guruBiasa = User::factory()->create(['institution_id' => $this->institution->id]);
        $this->guruBiasa->assignRole('guru_mapel');

        $this->class7A = Classroom::create([
            'name' => 'Kelas 7A',
            'tingkat' => 7,
            'is_active' => true,
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->waliKelas7A->id,
        ]);

        $this->class7B = Classroom::create([
            'name' => 'Kelas 7B',
            'tingkat' => 7,
            'is_active' => true,
            'institution_id' => $this->institution->id,
            'wali_kelas_id' => $this->waliKelas7B->id,
        ]);

        $this->student7A = Student::create([
            'name' => 'Ahmad Santri 7A',
            'nis' => '7001',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->class7A->id,
            'institution_id' => $this->institution->id,
            'character_score' => 95,
            'status' => 'aktif',
        ]);

        $this->student7B = Student::create([
            'name' => 'Budi Santri 7B',
            'nis' => '7002',
            'jenis_kelamin' => 'L',
            'classroom_id' => $this->class7B->id,
            'institution_id' => $this->institution->id,
            'character_score' => 85,
            'status' => 'aktif',
        ]);

        CharacterScoreLog::create([
            'student_id' => $this->student7A->id,
            'source' => 'guru_mapel',
            'category' => 'Kedisiplinan',
            'description' => '[Jurnal #1] [Matematika] Sangat aktif',
            'point_change' => 2,
            'tanggal' => now()->toDateString(),
            'institution_id' => $this->institution->id,
        ]);
    }

    public function test_navigation_label_and_title_are_rekapitulasi_skor_karakter_siswa(): void
    {
        $this->actingAs($this->kepsek);
        $this->assertEquals('Rekapitulasi Skor Karakter Siswa', CharacterScoreReport::getNavigationLabel());

        $component = Livewire::test(CharacterScoreReport::class);
        $component->assertSee('Rekapitulasi Skor Karakter Siswa');
    }

    public function test_navigation_groups_are_mapped_correctly(): void
    {
        // Wali Kelas masuk ke 'Fitur Wali Kelas'
        $this->actingAs($this->waliKelas7A);
        $this->assertEquals('Fitur Wali Kelas', CharacterScoreReport::getNavigationGroup());

        // Waka Kesiswaan & Kepsek masuk ke 'Kesiswaan & Karakter'
        $this->actingAs($this->wakaKesiswaan);
        $this->assertEquals('Kesiswaan & Karakter', CharacterScoreReport::getNavigationGroup());

        $this->actingAs($this->kepsek);
        $this->assertEquals('Kesiswaan & Karakter', CharacterScoreReport::getNavigationGroup());
    }

    public function test_kepala_sekolah_and_waka_kesiswaan_can_view_all_classes(): void
    {
        $this->actingAs($this->kepsek);
        $component = Livewire::test(CharacterScoreReport::class);

        $availableClasses = $component->viewData('availableClasses');
        $this->assertCount(2, $availableClasses);
        $this->assertTrue($availableClasses->contains('id', $this->class7A->id));
        $this->assertTrue($availableClasses->contains('id', $this->class7B->id));

        // Kepsek bisa beralih ke Kelas 7B
        $component->set('selectedClassroomId', $this->class7B->id);
        $component->assertSee('Budi Santri 7B');

        // Beralih ke Kelas 7A
        $component->set('selectedClassroomId', $this->class7A->id);
        $component->assertSee('Ahmad Santri 7A');
    }

    public function test_wali_kelas_can_only_view_their_assigned_classroom(): void
    {
        $this->actingAs($this->waliKelas7A);
        $component = Livewire::test(CharacterScoreReport::class);

        $availableClasses = $component->viewData('availableClasses');
        // Hanya melihat Kelas 7A
        $this->assertCount(1, $availableClasses);
        $this->assertEquals($this->class7A->id, $availableClasses->first()->id);

        // Harus melihat siswa 7A
        $component->assertSee('Ahmad Santri 7A');
        // Tidak boleh melihat siswa 7B
        $component->assertDontSee('Budi Santri 7B');

        // Jika mencoba memanipulasi selectedClassroomId ke 7B
        $component->set('selectedClassroomId', $this->class7B->id);
        // Sistem harus tetap memaksakan hanya kelas 7A
        $component->assertSee('Ahmad Santri 7A');
        $component->assertDontSee('Budi Santri 7B');
    }

    public function test_wali_kelas_cannot_open_detail_modal_for_student_outside_their_class(): void
    {
        $this->actingAs($this->waliKelas7A);
        $component = Livewire::test(CharacterScoreReport::class);

        // Buka detail siswa 7A (berhasil)
        $component->call('openDetailModal', $this->student7A->id);
        $this->assertEquals($this->student7A->id, $component->get('viewStudentId'));

        // Coba buka detail siswa 7B (harus ditolak dan viewStudentId tidak terisi student 7B)
        $component->call('openDetailModal', $this->student7B->id);
        $this->assertNotEquals($this->student7B->id, $component->get('viewStudentId'));
    }

    public function test_manual_input_buttons_and_modal_are_removed(): void
    {
        $this->actingAs($this->waliKelas7A);
        $component = Livewire::test(CharacterScoreReport::class);

        // Tidak boleh ada tombol Input Penilaian Guru Kelas
        $component->assertDontSee('Input Penilaian Guru Kelas');
        // Tidak boleh ada method saveCharacterLog
        $this->assertFalse(method_exists(CharacterScoreReport::class, 'saveCharacterLog'));
        $this->assertFalse(method_exists(CharacterScoreReport::class, 'openAddModal'));
    }
}
