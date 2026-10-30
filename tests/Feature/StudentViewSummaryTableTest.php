<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentViewSummaryTableTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $tuUser;
    protected Classroom $classroom;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'wali_kelas', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->tuUser = User::create([
            'name' => 'Staff TU',
            'username' => 'staff_tu_test',
            'email' => 'staff_tu_test@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->tuUser->assignRole('tata_usaha');

        $this->classroom = Classroom::create([
            'name' => 'VIII-B',
            'tingkat' => '8',
            'institution_id' => $this->institution->id,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->classroom->id,
            'nis' => '2026001',
            'nisn' => '0098765432',
            'name' => 'Muhammad Rifqi Pratama',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '2012-05-14',
            'status_siswa' => 'aktif',
            'no_telepon_siswa' => '081234567890',
            'alamat_siswa' => 'Jl. Ketintang Baru No. 12, Surabaya',
            'nama_ayah' => 'Bambang Sutrisno',
            'pekerjaan_ayah' => 'Wiraswasta',
            'nama_ibu' => 'Siti Aminah',
            'pekerjaan_ibu' => 'Guru',
            'no_telepon_orang_tua' => '081298765432',
            'anak_ke' => 2,
            'jumlah_saudara' => 3,
            'character_score' => 95,
            'is_administrasi_lunas' => true,
        ]);
    }

    public function test_student_resource_infolist_is_registered_with_summary_view(): void
    {
        $infolist = StudentResource::infolist(new Infolist());
        $components = $infolist->getComponents();

        $this->assertNotEmpty($components);
        $entry = $components[0];
        $this->assertInstanceOf(\Filament\Infolists\Components\ViewEntry::class, $entry);
        $this->assertEquals('summary', $entry->getName());
        $this->assertEquals('filament.staff.modals.student-detail-summary-modal', $entry->getView());
    }

    public function test_student_detail_summary_modal_blade_renders_as_clean_tables_without_form_inputs(): void
    {
        $rendered = view('filament.staff.modals.student-detail-summary-modal', [
            'record' => $this->student,
        ])->render();

        // Verifikasi data muncul dalam format ringkasan
        $this->assertStringContainsString('Muhammad Rifqi Pratama', $rendered);
        $this->assertStringContainsString('2026001', $rendered);
        $this->assertStringContainsString('0098765432', $rendered);
        $this->assertStringContainsString('VIII-B', $rendered);
        $this->assertStringContainsString('Bambang Sutrisno', $rendered);
        $this->assertStringContainsString('Siti Aminah', $rendered);
        $this->assertStringContainsString('Administrasi Lunas', $rendered);

        // Verifikasi bahwa tampilan menggunakan elemen tabel (bukan disabled inputs)
        $this->assertStringContainsString('<table', $rendered);
        $this->assertStringContainsString('</table>', $rendered);
        $this->assertStringContainsString('1. Data Utama & Identitas Diri Siswa', $rendered);
        $this->assertStringContainsString('4. Data Orang Tua / Wali & Susunan Keluarga', $rendered);

        // Pastikan tidak ada form input tag seperti <input type="text", <select, atau <textarea
        $this->assertStringNotContainsString('<input', $rendered);
        $this->assertStringNotContainsString('<select', $rendered);
        $this->assertStringNotContainsString('<textarea', $rendered);
    }
}
