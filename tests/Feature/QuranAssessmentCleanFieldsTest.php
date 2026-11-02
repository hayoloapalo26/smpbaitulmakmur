<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\QuranAchievementResource\Pages\ManageQuranAchievements;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuranAssessmentCleanFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $guruQuran;
    protected Classroom $classroom;
    protected Student $santri1;
    protected Student $santri2;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahsin', 'guard_name' => 'web']);

        Institution::firstOrCreate(
            ['id' => 1],
            ['slug' => 'yayasan-baitul-makmur', 'name' => 'Yayasan Baitul Makmur']
        );

        $this->institution = Institution::firstOrCreate(
            ['id' => 2],
            ['slug' => 'smp-islam-baitul-makmur', 'name' => 'SMP Islam Baitul Makmur']
        );

        $this->guruQuran = User::create([
            'name' => 'Ustadz Zaenal Musthofa, S.Pd',
            'username' => 'zaenal_quran',
            'email' => 'zaenal@smpit.test',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->guruQuran->assignRole('guru_quran_tahsin');

        $this->classroom = Classroom::create([
            'name' => 'VII-A',
            'tingkat' => '7',
            'institution_id' => $this->institution->id,
        ]);

        $this->santri1 = Student::create([
            'name' => 'Ahmad Fathan',
            'nis' => '1001',
            'classroom_id' => $this->classroom->id,
            'kelas_quran' => 'tahsin',
            'guru_quran_id' => $this->guruQuran->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-01-01',
        ]);

        $this->santri2 = Student::create([
            'name' => 'Bilal Habasyi',
            'nis' => '1002',
            'classroom_id' => $this->classroom->id,
            'kelas_quran' => 'tahsin',
            'guru_quran_id' => $this->guruQuran->id,
            'institution_id' => $this->institution->id,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2012-02-02',
        ]);
    }

    public function test_form_schema_only_keeps_kelas_binaan_and_paten_tanggal(): void
    {
        $this->actingAs($this->guruQuran);

        $test = Livewire::test(ManageQuranAchievements::class)
            ->mountAction('input_setoran_rombel');

        $action = $test->instance()->getMountedAction();
        $this->assertNotNull($action);

        $form = $test->instance()->getMountedActionForm();
        $this->assertNotNull($form);
        $components = $form->getFlatComponents();
        $componentNames = collect($components)
            ->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)
            ->filter()
            ->values()
            ->all();

        // 1. Field info_kelas_otomatis dan tanggal harus ada
        $this->assertContains('info_kelas_otomatis', $componentNames);
        $this->assertContains('tanggal', $componentNames);

        // 2. Tanggal harus paten hari ini dan disabled
        $tanggalComponent = collect($components)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'tanggal');
        $this->assertNotNull($tanggalComponent);
        $this->assertTrue($tanggalComponent->isDisabled(), 'Tanggal harus paten (disabled)');
        $this->assertTrue($tanggalComponent->isDehydrated(), 'Tanggal harus tetap terdehidrasi (dehydrated)');

        // 3. Field duplikasi (kedouble-an) harus sudah tidak ada di schema
        $this->assertNotContains('default_scale_score', $componentNames);
        $this->assertNotContains('default_juz', $componentNames);
        $this->assertNotContains('default_surah', $componentNames);
        $this->assertNotContains('default_ayah', $componentNames);
        $this->assertNotContains('default_juz_murajaah', $componentNames);
        $this->assertNotContains('default_surah_murajaah', $componentNames);
        $this->assertNotContains('default_ayah_murajaah', $componentNames);
    }

    public function test_batch_saving_works_flawlessly_with_clean_schema(): void
    {
        $this->actingAs($this->guruQuran);

        $payload = [
            'tanggal' => Carbon::today()->format('Y-m-d'),
            'student_achievements' => [
                $this->santri1->id => [
                    'active' => true,
                    'juz' => 30,
                    'surah' => '78',
                    'ayah' => '1-10',
                    'scale_score' => 95,
                    'predikat' => 'Mumtaz',
                    'catatan' => 'Makhraj mantap dan tartil.',
                ],
                $this->santri2->id => [
                    'active' => true,
                    'juz' => 30,
                    'surah' => '78',
                    'ayah' => '11-20',
                    'scale_score' => 88,
                    'predikat' => 'Jayyid Jiddan',
                    'catatan' => 'Lancar dan baik.',
                ],
            ],
        ];

        Livewire::test(ManageQuranAchievements::class)
            ->callAction('input_setoran_rombel', $payload)
            ->assertHasNoActionErrors();

        // Verifikasi setoran tersimpan dengan tanggal paten hari ini
        $this->assertDatabaseHas('quran_achievements', [
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruQuran->id,
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-10',
            'scale_score' => 95,
            'predikat' => 'Mumtaz',
            'tanggal' => Carbon::today()->format('Y-m-d 00:00:00'),
        ]);

        $this->assertDatabaseHas('quran_achievements', [
            'student_id' => $this->santri2->id,
            'teacher_id' => $this->guruQuran->id,
            'juz' => 30,
            'surah' => '78',
            'ayah' => '11-20',
            'scale_score' => 88,
            'predikat' => 'Jayyid Jiddan',
            'tanggal' => Carbon::today()->format('Y-m-d 00:00:00'),
        ]);

        // Verifikasi sinkronisasi skor kualitas bacaan otomatis
        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id' => $this->santri1->id,
            'teacher_id' => $this->guruQuran->id,
            'scale_score' => 95,
            'tanggal' => Carbon::today()->format('Y-m-d 00:00:00'),
        ]);
    }
}
