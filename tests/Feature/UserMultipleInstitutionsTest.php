<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\UserResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingJournal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserMultipleInstitutionsTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $yayasan;
    protected Institution $smp;
    protected Institution $sd;
    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'ketua_yayasan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_quran_tahfidz_lanjutan', 'guard_name' => 'web']);

        $this->yayasan = Institution::create(['id' => 1, 'name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->smp = Institution::create(['id' => 2, 'name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);
        $this->sd = Institution::create(['id' => 4, 'name' => 'SD Islam Baitul Makmur', 'slug' => 'sd']);

        $this->superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'super@yayasan.com',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'institution_id' => 1,
            'is_active' => true,
        ]);
        $this->superadmin->assignRole('superadmin');
    }

    public function test_user_can_have_multiple_institutions_and_query_by_them(): void
    {
        $ustadzRomli = User::create([
            'name' => 'M. Romli, M.Ag',
            'email' => 'romli@baitulmakmur.sch.id',
            'username' => 'ustadzromli',
            'password' => bcrypt('password'),
            'institution_id' => 1,
            'is_active' => true,
        ]);

        $ustadzRomli->assignRole('ketua_yayasan');
        $ustadzRomli->assignRole('guru_quran_tahfidz_lanjutan');

        // Pilih unit secara multiple choice: Yayasan dan SMP
        $ustadzRomli->syncInstitutions([$this->yayasan->id, $this->smp->id]);

        $this->assertTrue($ustadzRomli->belongsToInstitution(1));
        $this->assertTrue($ustadzRomli->belongsToInstitution(2));
        $this->assertFalse($ustadzRomli->belongsToInstitution(4));

        $this->assertContains(1, $ustadzRomli->institution_ids);
        $this->assertContains(2, $ustadzRomli->institution_ids);

        // Cari user yang berada di SMP
        $smpUsers = User::whereHas('institutions', fn ($q) => $q->where('institutions.id', 2))->get();
        $this->assertTrue($smpUsers->contains('id', $ustadzRomli->id));
    }

    public function test_user_resource_form_has_multiple_institutions_field(): void
    {
        $this->actingAs($this->superadmin);

        $livewire = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;
            public function render() { return ''; }
        };
        $form = UserResource::form(new \Filament\Forms\Form($livewire));
        $components = $form->getComponents();

        $foundInstitutions = false;
        foreach ($components as $section) {
            foreach ($section->getChildComponents() as $field) {
                if ($field instanceof \Filament\Forms\Components\Select && $field->getName() === 'institutions') {
                    $foundInstitutions = true;
                    $this->assertTrue($field->isMultiple(), 'Field unit/lembaga harus mendukung pilihan multiple choice');
                }
            }
        }

        $this->assertTrue($foundInstitutions, 'Field institutions harus terdaftar pada form UserResource');
    }

    public function test_quran_input_by_dual_role_user_goes_to_student_home_institution(): void
    {
        $ustadzRomli = User::create([
            'name' => 'M. Romli, M.Ag',
            'email' => 'romli2@baitulmakmur.sch.id',
            'username' => 'ustadzromli2',
            'password' => bcrypt('password'),
            'institution_id' => 1, // User primer di Yayasan
            'is_active' => true,
        ]);
        $ustadzRomli->assignRole(['ketua_yayasan', 'guru_quran_tahfidz_lanjutan']);
        $ustadzRomli->syncInstitutions([$this->yayasan->id, $this->smp->id]);

        // Siswa terdaftar di SMP (ID = 2)
        $studentSmp = Student::create([
            'name' => 'Ahmad Fulan',
            'nis' => '99881',
            'jenis_kelamin' => 'L',
            'institution_id' => 2,
            'kelas_quran' => 'tahfidz_lanjutan',
            'guru_quran_id' => $ustadzRomli->id,
        ]);

        $this->actingAs($ustadzRomli);

        $achievement = QuranAchievement::create([
            'student_id' => $studentSmp->id,
            'teacher_id' => $ustadzRomli->id,
            'tanggal' => now()->toDateString(),
            'program' => 'tahfidz_lanjutan',
            'juz' => 30,
            'surah' => '78',
            'ayah' => '1-40',
            'scale_score' => 90,
            'predikat' => 'Mumtaz',
        ]);

        // Dipastikan masuk ke SMP (ID = 2), bukan Yayasan (ID = 1)
        $this->assertEquals(2, $achievement->institution_id);

        $syncedReadingScore = ReadingQualityScore::where('student_id', $studentSmp->id)->first();
        $this->assertNotNull($syncedReadingScore);
        $this->assertEquals(2, $syncedReadingScore->institution_id);
    }
}
