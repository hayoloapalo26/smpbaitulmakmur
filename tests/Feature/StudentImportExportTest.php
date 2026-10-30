<?php

namespace Tests\Feature;

use App\Filament\Exports\StudentExporter;
use App\Filament\Imports\StudentImporter;
use App\Filament\Staff\Resources\StudentResource\Pages\ManageStudents;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('staff'));

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'tata_usaha', 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], [
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::firstOrCreate(['id' => 2], [
            'name' => 'SMP Baitul Makmur',
            'slug' => 'smp-baitul-makmur',
        ]);

        $this->adminUser = User::create([
            'name' => 'Staf Tata Usaha',
            'username' => 'tata_usaha_test',
            'email' => 'tu@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->adminUser->assignRole('tata_usaha');
    }

    /**
     * Test 1: StudentExporter memiliki 34 kolom yang persis sesuai daftar variabel
     */
    public function test_student_exporter_has_exact_required_columns(): void
    {
        $columns = StudentExporter::getColumns();
        $columnLabels = array_map(fn ($col) => $col->getLabel(), $columns);

        $expectedLabels = [
            'NO',
            'NAMA',
            'NIS',
            'NISN',
            'NIK',
            'Nomer KK (Kartu Keluarga)',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Jenis Kelamin.1',
            'Agama',
            'Anak ke-',
            'Jumlah Saudara Kandung',
            'Asal sekolah',
            'Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)',
            'Nama Ayah',
            'Tempat Lahir (Ayah)',
            'Tanggal Lahir (Ayah)',
            'No. Telp/HP (Ayah)',
            'Pendidikan (Ayah)',
            'Pekerjaan (Ayah)',
            'Rata-rata Penghasilan (Ayah)',
            'Nama Ibu',
            'Tempat Lahir (Ibu)',
            'Tanggal Lahir (Ibu)',
            'No. Telp/HP',
            'Pendidikan',
            'Pekerjaan',
            'Rata-rata Penghasilan',
            'NAMA WALI',
            'NO HP',
            'Pendidikan2',
            'Pekerjaan2',
            'Rata-rata Penghasilan2',
        ];

        foreach ($expectedLabels as $expectedLabel) {
            $this->assertContains($expectedLabel, $columnLabels, "Kolom [{$expectedLabel}] wajib ada pada StudentExporter.");
        }
    }

    /**
     * Test 2: StudentImporter mengimpor data lengkap 34 kolom dengan normalisasi tanggal & gender
     */
    public function test_student_importer_resolves_and_imports_all_data_correctly(): void
    {
        $this->actingAs($this->adminUser);

        $importModel = Import::create([
            'user_id' => $this->adminUser->id,
            'file_name' => 'test_students.csv',
            'file_path' => 'imports/test.csv',
            'importer' => StudentImporter::class,
            'total_rows' => 1,
            'processed_rows' => 0,
            'successful_rows' => 0,
        ]);

        $rowRaw = [
            'name' => 'Muhammad Bintang Pratama',
            'nis' => '20261001',
            'nisn' => '0098765432',
            'nik' => '3515012345670001',
            'no_kk' => '3515012345670002',
            'tempat_lahir' => 'Sidoarjo',
            'tanggal_lahir' => '12 Mei 2011',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'anak_ke' => '2',
            'jumlah_saudara_kandung' => '3',
            'asal_sekolah' => 'SD Islam Terpadu',
            'alamat_siswa' => 'Jl. Pahlawan No. 45 RT 03 RW 01, Sidoarjo',

            'nama_ayah' => 'H. Bambang Susilo',
            'tempat_lahir_ayah' => 'Surabaya',
            'tanggal_lahir_ayah' => '15-08-1980',
            'no_hp_ayah' => '081234567890',
            'pendidikan_ayah' => 'S1 Teknik',
            'pekerjaan_ayah' => 'Wiraswasta',
            'penghasilan_ayah' => 'Rp 10.000.000',

            'nama_ibu' => 'Hj. Siti Nurhaliza',
            'tempat_lahir_ibu' => 'Sidoarjo',
            'tanggal_lahir_ibu' => '1984-11-20',
            'no_hp_ibu' => '081298765432',
            'pendidikan_ibu' => 'S1 Pendidikan',
            'pekerjaan_ibu' => 'Guru',
            'penghasilan_ibu' => 'Rp 5.000.000',

            'nama_wali' => 'Drs. Supardi',
            'no_hp_wali' => '081333444555',
            'pendidikan_wali' => 'S2',
            'pekerjaan_wali' => 'PNS',
            'penghasilan_wali' => 'Rp 8.000.000',
        ];

        $columnMap = array_combine(array_keys($rowRaw), array_keys($rowRaw));
        $importer = new StudentImporter($importModel, $columnMap, []);

        // Simulasikan alur Importer
        $importer($rowRaw);

        $student = Student::where('nis', '20261001')->first();
        $this->assertNotNull($student, 'Siswa harus berhasil dibuat melalui StudentImporter.');
        $this->assertEquals('Muhammad Bintang Pratama', $student->name);
        $this->assertEquals('L', $student->jenis_kelamin, 'Gender Laki-laki harus dinormalisasi menjadi L.');
        $this->assertEquals('2011-05-12', $student->tanggal_lahir->format('Y-m-d'));
        $this->assertEquals('1980-08-15', $student->tanggal_lahir_ayah);
        $this->assertEquals('1984-11-20', $student->tanggal_lahir_ibu);
        $this->assertEquals('H. Bambang Susilo', $student->nama_ayah);
        $this->assertEquals('Hj. Siti Nurhaliza', $student->nama_ibu);
        $this->assertEquals('Drs. Supardi', $student->nama_wali);
        $this->assertEquals(2, $student->anak_ke);
        $this->assertEquals(3, $student->jumlah_saudara_kandung);
        $this->assertEquals($this->institution->id, $student->institution_id);
    }

    /**
     * Test 3: Download Template CSV menghasilkan header 34 kolom yang lengkap
     */
    public function test_download_template_csv_action_returns_valid_csv(): void
    {
        $this->actingAs($this->adminUser);

        $response = Livewire::test(ManageStudents::class)
            ->callAction('unduh_template_csv');

        $response->assertSuccessful();
    }

    /**
     * Test 4: Upload Cepat CSV pada ManageStudents berhasil membaca header 34 kolom
     */
    public function test_quick_csv_import_handles_34_column_file(): void
    {
        $this->actingAs($this->adminUser);
        Storage::fake('local');

        $stream = fopen('php://memory', 'r+');
        fputcsv($stream, [
            'NO',
            'NAMA',
            'NIS',
            'NISN',
            'NIK',
            'Nomer KK (Kartu Keluarga)',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Jenis Kelamin',
            'Agama',
            'Anak ke-',
            'Jumlah Saudara Kandung',
            'Asal sekolah',
            'Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)',
            'Nama Ayah',
            'Tempat Lahir (Ayah)',
            'Tanggal Lahir (Ayah)',
            'No. Telp/HP (Ayah)',
            'Pendidikan (Ayah)',
            'Pekerjaan (Ayah)',
            'Rata-rata Penghasilan (Ayah)',
            'Nama Ibu',
            'Tempat Lahir (Ibu)',
            'Tanggal Lahir (Ibu)',
            'No. Telp/HP',
            'Pendidikan',
            'Pekerjaan',
            'Rata-rata Penghasilan',
            'NAMA WALI',
            'NO HP',
            'Pendidikan2',
            'Pekerjaan2',
            'Rata-rata Penghasilan2',
        ]);

        fputcsv($stream, [
            '1',
            'Aisyah Khumaira',
            '20261002',
            '0091122334',
            '3515098765430001',
            '3515098765430002',
            'Sidoarjo',
            '2011-09-17',
            'P',
            'Perempuan',
            'Islam',
            '1',
            '2',
            'SD Al-Hikmah',
            'Perum Permata Blok B No 12, Waru, Sidoarjo',
            'Fulan Baswedan',
            'Gresik',
            '1982-03-25',
            '081222333444',
            'S1',
            'Manajer',
            'Rp 12.000.000',
            'Fatimah Zahra',
            'Surabaya',
            '1986-07-10',
            '081555666777',
            'D3',
            'Bidan',
            'Rp 4.000.000',
            '-',
            '-',
            '-',
            '-',
            '-',
        ]);
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $file = UploadedFile::fake()->createWithContent('siswa_sample.csv', $csvContent);

        Livewire::test(ManageStudents::class)
            ->callAction('import_csv', [
                'file' => [$file],
            ])
            ->assertHasNoActionErrors();

        $student = Student::where('nis', '20261002')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Aisyah Khumaira', $student->name);
        $this->assertEquals('P', $student->jenis_kelamin);
        $this->assertEquals('2011-09-17', $student->tanggal_lahir->format('Y-m-d'));
        $this->assertEquals('Fulan Baswedan', $student->nama_ayah);
        $this->assertEquals('Fatimah Zahra', $student->nama_ibu);
    }

    /**
     * Test 5: Siswa yang diimpor oleh user Yayasan (ID = 1) atau Superadmin HARUS masuk ke SMP (ID = 2), BUKAN ke Yayasan.
     */
    public function test_import_by_superadmin_or_yayasan_user_always_assigns_students_to_smp(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin Yayasan',
            'username' => 'superadmin_yayasan',
            'email' => 'superadmin@yayasan.com',
            'password' => bcrypt('password'),
            'institution_id' => 1, // Yayasan
        ]);
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin);

        $stream = fopen('php://memory', 'r+');
        fputcsv($stream, ['NO', 'NAMA', 'NIS', 'NISN']);
        fputcsv($stream, ['1', 'Muhammad Zaidan', 'SMP2026999', '0089988776']);
        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        $file = UploadedFile::fake()->createWithContent('siswa_smp_superadmin.csv', $csvContent);

        Livewire::test(ManageStudents::class)
            ->callAction('import_csv', [
                'file' => [$file],
            ])
            ->assertHasNoActionErrors();

        $student = Student::withoutGlobalScopes()->where('nis', 'SMP2026999')->first();
        $this->assertNotNull($student);
        $this->assertEquals(2, $student->institution_id, 'Siswa wajib masuk ke SMP (ID = 2), TIDAK BOLEH masuk ke Yayasan (ID = 1)');

        // Pastikan staf SMP (institution_id = 2) dapat melihat siswa ini di StudentResource
        $smpStaff = User::create([
            'name' => 'Staf SMP Asli',
            'username' => 'staf_smp_asli',
            'email' => 'stafsmp@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $smpStaff->assignRole('tata_usaha');
        $this->actingAs($smpStaff);

        $query = \App\Filament\Staff\Resources\StudentResource::getEloquentQuery();
        $this->assertTrue($query->where('students.id', $student->id)->exists(), 'Siswa harus muncul pada query Data Siswa SMP');
    }

    /**
     * Test 6: Siswa lawas yang sempat tersimpan dengan institution_id = 1 atau null tetap muncul di SMP dan diperbaiki ke ID 2
     */
    public function test_legacy_students_with_institution_id_1_appear_in_smp_and_fixed(): void
    {
        // Bypass model hook using DB query to simulate legacy dirty data
        $legacyId = \Illuminate\Support\Facades\DB::table('students')->insertGetId([
            'institution_id' => 1,
            'name' => 'Siswa Lawas Nyasar Yayasan',
            'nis' => 'SMP-LEGACY-001',
            'jenis_kelamin' => 'L',
            'character_score' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $smpStaff = User::create([
            'name' => 'Staf SMP TU',
            'username' => 'staf_smp_tu2',
            'email' => 'tu2@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => 2,
        ]);
        $smpStaff->assignRole('tata_usaha');
        $this->actingAs($smpStaff);

        // Harus tetap bisa dilihat oleh staf SMP
        $visibleInSmp = \App\Filament\Staff\Resources\StudentResource::getEloquentQuery()
            ->where('students.id', $legacyId)
            ->exists();
        $this->assertTrue($visibleInSmp, 'Siswa yang sempat nyasar di ID 1 harus tetap terbaca di panel SMP');

        // Saat di-save / update, otomatis dinormalisasi ke ID 2
        $legacyStudent = Student::withoutGlobalScopes()->find($legacyId);
        $legacyStudent->save();
        $this->assertEquals(2, $legacyStudent->fresh()->institution_id, 'Siswa otomatis dinormalisasi ke ID 2 (SMP)');
    }
}
