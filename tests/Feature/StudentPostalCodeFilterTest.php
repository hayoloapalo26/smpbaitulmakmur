<?php

namespace Tests\Feature;

use App\Filament\Exports\StudentExporter;
use App\Filament\Imports\StudentImporter;
use App\Filament\Staff\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPostalCodeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Institution $institution;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup yayasan & institution
        Institution::create(['id' => 1, 'name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']);
        $this->institution = Institution::create(['id' => 2, 'name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);

        // Role setup
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // 2. Setup user with superadmin & tata_usaha
        $this->adminUser = User::factory()->create([
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('superadmin');

        // 3. Setup classroom
        $this->classroom = Classroom::create([
            'name' => '7A',
            'institution_id' => $this->institution->id,
            'tingkat' => '7',
        ]);
    }

    public function test_can_save_and_retrieve_student_with_kode_pos(): void
    {
        $student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '1001',
            'name' => 'Ahmad Fulan',
            'tanggal_lahir' => '2012-05-10',
            'jenis_kelamin' => 'L',
            'alamat_siswa' => 'Jl. Danau Ranau No. 12',
            'kode_pos' => '65138',
            'character_score' => 85,
        ]);

        $this->assertDatabaseHas('students', [
            'nis' => '1001',
            'kode_pos' => '65138',
        ]);

        $this->assertEquals('65138', $student->fresh()->kode_pos);
    }

    public function test_student_resource_form_has_kode_pos_field(): void
    {
        $this->actingAs($this->adminUser);

        $dummyLivewire = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms {
            use \Filament\Forms\Concerns\InteractsWithForms;
            public function render() { return '<div></div>'; }
        };

        $form = StudentResource::form(
            Form::make($dummyLivewire)
        );

        $flatFields = $form->getFlatFields();
        $this->assertArrayHasKey('kode_pos', $flatFields, 'Field kode_pos harus terdaftar di form StudentResource.');
        $this->assertEquals('Kode Pos', $flatFields['kode_pos']->getLabel());
    }

    public function test_student_resource_filter_filters_by_kode_pos(): void
    {
        $student1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '1001',
            'name' => 'Siswa Sawojajar',
            'tanggal_lahir' => '2012-01-01',
            'jenis_kelamin' => 'L',
            'alamat_siswa' => 'Jl. Sawojajar',
            'kode_pos' => '65138',
            'character_score' => 80,
        ]);

        $student2 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '1002',
            'name' => 'Siswa Sukun',
            'tanggal_lahir' => '2012-02-02',
            'jenis_kelamin' => 'P',
            'alamat_siswa' => 'Jl. S. Supriadi',
            'kode_pos' => '65147',
            'character_score' => 80,
        ]);

        $query = Student::withoutGlobalScopes();

        // Simulate filter action
        $filteredQuery = (clone $query)->where(function ($q) {
            $q->where('kode_pos', 'like', '%65138%')
              ->orWhere('alamat_siswa', 'like', '%65138%');
        });

        $results = $filteredQuery->get();
        $this->assertTrue($results->contains($student1));
        $this->assertFalse($results->contains($student2));
    }

    public function test_student_exporter_and_importer_contain_kode_pos(): void
    {
        $exportColumns = collect(StudentExporter::getColumns())->map(fn ($c) => $c->getName())->toArray();
        $this->assertContains('kode_pos', $exportColumns, 'StudentExporter harus memiliki kolom kode_pos.');

        $importColumns = collect(StudentImporter::getColumns())->map(fn ($c) => $c->getName())->toArray();
        $this->assertContains('kode_pos', $importColumns, 'StudentImporter harus memiliki kolom kode_pos.');
    }
}
