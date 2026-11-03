<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\ScheduleResource\Pages\ManageSchedules;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleAntiClashValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $waka;
    protected User $guru1;
    protected User $guru2;
    protected Classroom $kelas7A;
    protected Classroom $kelas7B;
    protected Subject $mapelIPA;
    protected Subject $mapelMatematika;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp-islam-baitul-makmur']
        );

        $this->waka = User::create([
            'name' => 'Waka Kurikulum SMP',
            'email' => 'waka@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->waka->assignRole('waka_kurikulum');

        $this->guru1 = User::create([
            'name' => 'Zaenal Musthofa, S.Pd',
            'email' => 'zaenal@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru1->assignRole('guru_mapel');

        $this->guru2 = User::create([
            'name' => 'Siti Aminah, M.Pd',
            'email' => 'siti@smp.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guru2->assignRole('guru_mapel');

        $this->kelas7A = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => 'VII',
            'institution_id' => $this->institution->id,
        ]);

        $this->kelas7B = Classroom::create([
            'name' => 'VII-B',
            'tingkat' => 'VII',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelIPA = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam',
            'kode' => 'IPA',
            'institution_id' => $this->institution->id,
        ]);

        $this->mapelMatematika = Subject::create([
            'name' => 'Matematika',
            'kode' => 'MTK',
            'institution_id' => $this->institution->id,
        ]);
    }

    /**
     * Test 1: Deteksi bentrok guru ketika guru sudah mengajar di kelas lain pada hari dan jam yang sama.
     */
    public function test_check_clashes_detects_teacher_conflict_in_another_class(): void
    {
        Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Coba jadwalkan Guru 1 di Kelas 7B pada hari Senin Jam ke-1
        $clashes = Schedule::checkClashes($this->kelas7B->id, $this->guru1->id, ['Senin'], [1]);

        $this->assertCount(1, $clashes);
        $this->assertStringContainsString('Zaenal Musthofa, S.Pd', $clashes[0]);
        $this->assertStringContainsString('VII-A', $clashes[0]);
        $this->assertStringContainsString('Senin', $clashes[0]);
    }

    /**
     * Test 2: Deteksi bentrok kelas ketika kelas sudah terisi guru dan mata pelajaran lain.
     */
    public function test_check_clashes_detects_classroom_conflict_with_different_teacher(): void
    {
        Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Coba jadwalkan Guru 2 di Kelas 7A pada hari Senin Jam ke-1
        $clashes = Schedule::checkClashes($this->kelas7A->id, $this->guru2->id, ['Senin'], [1]);

        $this->assertCount(1, $clashes);
        $this->assertStringContainsString('VII-A', $clashes[0]);
        $this->assertStringContainsString('Zaenal Musthofa, S.Pd', $clashes[0]);
        $this->assertStringContainsString('Ilmu Pengetahuan Alam', $clashes[0]);
    }

    /**
     * Test 3: Deteksi bentrok ketika menginput guru yang sama di kelas yang sama pada jam yang sama (duplikat).
     */
    public function test_check_clashes_detects_same_teacher_same_class_conflict(): void
    {
        Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Coba jadwalkan ulang Guru 1 di Kelas 7A pada hari Senin Jam ke-1
        $clashes = Schedule::checkClashes($this->kelas7A->id, $this->guru1->id, ['Senin'], [1]);

        $this->assertCount(1, $clashes);
        $this->assertStringContainsString('Zaenal Musthofa, S.Pd', $clashes[0]);
        $this->assertStringContainsString('VII-A', $clashes[0]);
    }

    /**
     * Test 4: Abaikan record ID saat edit agar tidak terjadi false positive bentrok dengan diri sendiri.
     */
    public function test_check_clashes_ignores_specified_schedule_id_when_editing(): void
    {
        $existing = Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Saat edit record itu sendiri, tidak boleh dianggap bentrok
        $clashes = Schedule::checkClashes($this->kelas7A->id, $this->guru1->id, ['Senin'], [1], $existing->id);

        $this->assertEmpty($clashes);
    }

    /**
     * Test 5: Livewire CreateAction menolak jadwal bentrok guru.
     */
    public function test_create_schedule_fails_when_teacher_clash_exists_via_livewire(): void
    {
        $this->actingAs($this->waka);

        // Jadwal eksisting: Guru 1 di Kelas 7A pada Senin Jam ke-1
        Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Coba input Guru 1 di Kelas 7B pada Senin Jam ke-1
        try {
            Livewire::test(ManageSchedules::class)
                ->callAction('create', [
                    'classroom_id' => $this->kelas7B->id,
                    'subject_id' => $this->mapelMatematika->id,
                    'teacher_id' => $this->guru1->id,
                    'hari' => ['Senin'],
                    'jam_ke' => [1],
                ]);
        } catch (\Throwable $e) {
            // Expected to be halted or throw validation exception
        }

        // Pastikan tidak ada jadwal baru yang tersimpan (tetap 1)
        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseMissing('schedules', [
            'classroom_id' => $this->kelas7B->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
        ]);
    }

    /**
     * Test 6: Livewire CreateAction menolak jadwal bentrok kelas.
     */
    public function test_create_schedule_fails_when_classroom_clash_exists_via_livewire(): void
    {
        $this->actingAs($this->waka);

        // Jadwal eksisting: Guru 1 di Kelas 7A pada Senin Jam ke-1
        Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Coba input Guru 2 di Kelas 7A pada Senin Jam ke-1
        try {
            Livewire::test(ManageSchedules::class)
                ->callAction('create', [
                    'classroom_id' => $this->kelas7A->id,
                    'subject_id' => $this->mapelMatematika->id,
                    'teacher_id' => $this->guru2->id,
                    'hari' => ['Senin'],
                    'jam_ke' => [1],
                ]);
        } catch (\Throwable $e) {
            // Expected
        }

        // Tetap hanya 1 jadwal
        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseMissing('schedules', [
            'classroom_id' => $this->kelas7A->id,
            'teacher_id' => $this->guru2->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
        ]);
    }

    /**
     * Test 7: Livewire CreateAction berhasil menyimpan jika tidak ada bentrok.
     */
    public function test_create_schedule_succeeds_when_no_clash(): void
    {
        $this->actingAs($this->waka);

        Livewire::test(ManageSchedules::class)
            ->callAction('create', [
                'classroom_id' => $this->kelas7A->id,
                'subject_id' => $this->mapelIPA->id,
                'teacher_id' => $this->guru1->id,
                'hari' => ['Senin'],
                'jam_ke' => [1, 2],
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseCount('schedules', 2);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->kelas7A->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
        ]);
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->kelas7A->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 2,
        ]);
    }

    /**
     * Test 8: Livewire EditAction menolak update jika slot baru bentrok dengan jadwal lain.
     */
    public function test_edit_schedule_fails_when_updated_to_clashing_slot_via_livewire(): void
    {
        $this->actingAs($this->waka);

        // Jadwal 1: Guru 1 di Kelas 7A pada Senin Jam 1
        $s1 = Schedule::create([
            'classroom_id' => $this->kelas7A->id,
            'subject_id' => $this->mapelIPA->id,
            'teacher_id' => $this->guru1->id,
            'hari' => 'Senin',
            'jam_ke' => 1,
            'institution_id' => $this->institution->id,
        ]);

        // Jadwal 2: Guru 2 di Kelas 7B pada Senin Jam 2
        $s2 = Schedule::create([
            'classroom_id' => $this->kelas7B->id,
            'subject_id' => $this->mapelMatematika->id,
            'teacher_id' => $this->guru2->id,
            'hari' => 'Senin',
            'jam_ke' => 2,
            'institution_id' => $this->institution->id,
        ]);

        // Coba edit Jadwal 2: ganti guru menjadi Guru 1 dan jam ke-1 (bentrok dengan Jadwal 1 Guru 1)
        try {
            Livewire::test(ManageSchedules::class)
                ->callTableAction('edit', $s2, [
                    'classroom_id' => $this->kelas7B->id,
                    'subject_id' => $this->mapelMatematika->id,
                    'teacher_id' => $this->guru1->id,
                    'hari' => ['Senin'],
                    'jam_ke' => [1],
                ]);
        } catch (\Throwable $e) {
            // Expected
        }

        // Jadwal 2 tetap dengan guru 2 dan jam ke-2
        $s2->refresh();
        $this->assertEquals($this->guru2->id, $s2->teacher_id);
        $this->assertEquals(2, $s2->jam_ke);
    }
}
