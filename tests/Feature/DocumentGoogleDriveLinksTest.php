<?php

namespace Tests\Feature;

use App\Filament\Staff\Pages\DailyTodoList;
use App\Filament\Staff\Pages\OptionalTodoList;
use App\Filament\Staff\Resources\ClassProgramResource;
use App\Filament\Staff\Resources\DocumentArchiveResource;
use App\Filament\Staff\Resources\LetterResource;
use App\Filament\Staff\Resources\MeetingMinuteResource;
use App\Filament\Staff\Resources\OsisProgramResource;
use App\Filament\Staff\Resources\ParentSummonResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Filament\Staff\Resources\ProgramEvaluationResource;
use App\Filament\Staff\Resources\StudentAchievementResource;
use App\Filament\Staff\Resources\TeacherAttendanceResource;
use App\Filament\Staff\Resources\TeacherLeaveRequestResource;
use App\Filament\Staff\Resources\WorkProgramResource;
use App\Models\MandatoryTask;
use App\Models\OptionalTask;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentGoogleDriveLinksTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kurikulum', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'email' => 'admin_test@baitulmakmur.sch.id',
            'is_active' => true,
        ]);
        $this->user->assignRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum']);
        $this->actingAs($this->user);
    }

    protected function getFormComponent(string $resourceClass, string $fieldName)
    {
        $livewire = new class extends Component implements HasForms {
            use InteractsWithForms;
            public function render() { return '<div></div>'; }
        };

        $form = $resourceClass::form(Form::make($livewire));
        $schema = $form->getComponents();

        return $this->findComponentByName($schema, $fieldName);
    }

    protected function findComponentByName(array $components, string $name)
    {
        foreach ($components as $component) {
            if (method_exists($component, 'getName') && $component->getName() === $name) {
                return $component;
            }
            if (method_exists($component, 'getChildComponents')) {
                $child = $this->findComponentByName($component->getChildComponents(), $name);
                if ($child) {
                    return $child;
                }
            }
        }
        return null;
    }

    public function test_document_archive_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(DocumentArchiveResource::class, 'file');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_letter_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(LetterResource::class, 'file');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_meeting_minute_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(MeetingMinuteResource::class, 'file');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_class_program_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(ClassProgramResource::class, 'dokumen_pendukung');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_osis_program_resource_uses_google_drive_link_text_inputs(): void
    {
        $proposal = $this->getFormComponent(OsisProgramResource::class, 'proposal_file');
        $lpj = $this->getFormComponent(OsisProgramResource::class, 'lpj_file');

        $this->assertNotNull($proposal);
        $this->assertInstanceOf(TextInput::class, $proposal);
        $this->assertNotInstanceOf(FileUpload::class, $proposal);

        $this->assertNotNull($lpj);
        $this->assertInstanceOf(TextInput::class, $lpj);
        $this->assertNotInstanceOf(FileUpload::class, $lpj);
    }

    public function test_parent_summon_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(ParentSummonResource::class, 'surat_file');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_presensi_izin_siswa_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(PresensiIzinSiswaResource::class, 'bukti_izin');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_program_evaluation_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(ProgramEvaluationResource::class, 'file_evaluasi');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_student_achievement_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(StudentAchievementResource::class, 'sertifikat');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_teacher_attendance_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(TeacherAttendanceResource::class, 'attachment');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_teacher_leave_request_resource_uses_google_drive_link_text_input(): void
    {
        $comp = $this->getFormComponent(TeacherLeaveRequestResource::class, 'supporting_letter');
        $this->assertNotNull($comp);
        $this->assertInstanceOf(TextInput::class, $comp);
        $this->assertNotInstanceOf(FileUpload::class, $comp);
    }

    public function test_work_program_resource_uses_google_drive_link_text_inputs(): void
    {
        $proposal = $this->getFormComponent(WorkProgramResource::class, 'file_proposal');
        $lpj = $this->getFormComponent(WorkProgramResource::class, 'file_lpj');

        $this->assertNotNull($proposal);
        $this->assertInstanceOf(TextInput::class, $proposal);
        $this->assertNotInstanceOf(FileUpload::class, $proposal);

        $this->assertNotNull($lpj);
        $this->assertInstanceOf(TextInput::class, $lpj);
        $this->assertNotInstanceOf(FileUpload::class, $lpj);
    }

    public function test_daily_todo_list_converts_file_upload_type_to_drive_link_input(): void
    {
        $page = new DailyTodoList();
        $task = new MandatoryTask([
            'title' => 'Unggah Laporan Mingguan',
            'form_schema' => [
                [
                    'name' => 'Dokumen Laporan',
                    'type' => 'file_upload',
                    'required' => true,
                ],
            ],
        ]);

        $reflection = new \ReflectionMethod(DailyTodoList::class, 'buildTaskFormSchema');
        $reflection->setAccessible(true);
        $schema = $reflection->invoke($page, $task);

        $this->assertNotEmpty($schema);
        $field = $schema[0];
        $this->assertInstanceOf(TextInput::class, $field);
        $this->assertNotInstanceOf(FileUpload::class, $field);
        $this->assertStringContainsString('Google Drive', $field->getLabel());
    }

    public function test_optional_todo_list_converts_file_upload_type_to_drive_link_input(): void
    {
        $page = new OptionalTodoList();
        $task = new OptionalTask([
            'title' => 'Unggah Bukti Pelatihan',
            'form_schema' => [
                [
                    'name' => 'Sertifikat Pelatihan',
                    'type' => 'file',
                    'required' => false,
                ],
            ],
        ]);

        $reflection = new \ReflectionMethod(OptionalTodoList::class, 'buildTaskFormSchema');
        $reflection->setAccessible(true);
        $schema = $reflection->invoke($page, $task);

        $this->assertNotEmpty($schema);
        $field = $schema[0];
        $this->assertInstanceOf(TextInput::class, $field);
        $this->assertNotInstanceOf(FileUpload::class, $field);
        $this->assertStringContainsString('Google Drive', $field->getLabel());
    }
}
