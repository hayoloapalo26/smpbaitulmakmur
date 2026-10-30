<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\StudentResource\Pages\ManageStudents;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MultiFormatStudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected Classroom $class7A;
    protected Classroom $class8A;
    protected Classroom $class9A;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('staff'));

        $roles = ['superadmin', 'kepala_sekolah', 'tata_usaha', 'waka_kurikulum'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Institution::firstOrCreate(['id' => 1], [
            'name' => 'Yayasan Baitul Makmur',
            'slug' => 'yayasan',
        ]);

        $this->institution = Institution::firstOrCreate(['id' => 2], [
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp-islam-baitul-makmur',
        ]);

        $this->class7A = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 7A',
            'tingkat' => '7',
        ]);

        $this->class8A = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 8A',
            'tingkat' => '8',
        ]);

        $this->class9A = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kelas 9A',
            'tingkat' => '9',
        ]);

        $this->staffUser = User::create([
            'name' => 'Tata Usaha SMP',
            'email' => 'tu@smpi.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->staffUser->assignRole('tata_usaha');
    }

    /**
     * Test Format 1: 34 Column Template (User's First Data Set)
     */
    public function test_import_format_1_34_columns_with_footer(): void
    {
        $csvContent = <<<'CSV'
NO,NAMA,NIS,NISN,NIK,Nomer KK (Kartu Keluarga),Tempat Lahir,Tanggal Lahir,Jenis Kelamin,Jenis Kelamin,Agama ,Anak ke-,Jumlah Saudara Kandung,Asal sekolah,"Alamat Rumah Siswa (Nama jalan, RT/RW, Desa, Kecamatan dan Kota/Kabupaten)",Nama Ayah,Tempat Lahir (Ayah),Tanggal Lahir (Ayah),No. Telp/HP (Ayah),Pendidikan (Ayah),Pekerjaan (Ayah),Rata-rata Penghasilan (Ayah),Nama Ibu,Tempat Lahir (Ibu),Tanggal Lahir (Ibu),No. Telp/HP,Pendidikan,Pekerjaan,Rata-rata Penghasilan,NAMA WALI,NO HP,Pendidikan2,Pekerjaan2,Rata-rata Penghasilan2
1,Abdurrohman Faqih,0285,0131397722,3573032904130002,3573030107110010,BLITAR,2013-04-29,L,Laki-laki,Islam,1,2,SDN SAWOJAJAR 3,JL. BRATAN TIMUR H5A15 RT 1 RW 14 MADYOPURO KEDUNGKANDANG KOTA MALANG,YONI PRIYANTO,SIDOARJO,1979-06-30,081334654441,D2,Wiraswasta,500.000 - 1.000.000,SITI KHALIMAH,BLITAR,1987-10-23,081574993976,SMA,IRT,-,,,,,
2,Abimanyu Ghanisakha Purnomo,0286,0124951330,3175051810121010,3573010608180013,JAKARTA,2012-10-18,L,Laki-laki,Islam,2,3,SDN KESATRIAN 2,JL. RONGGOLAWE RT 2 RW 4 KESATRIAN KECAMATAN BLIMBING KOTA MALANG,AGUNG CAHYO PURNOMO,MALANG,1980-08-05,081316045309,S2,TNI/POLRI,>5.000.000,NUNING ROCHAENI,MALANG,1980-12-19,082295098598,D3,IRT,-1,,,,,
3,Aidil Adhar Pringadi,0287,3122822726,2171042510120002,3573032806190003,BATAM,2012-10-25,L,Laki-laki,Islam,1,3,SDI BAITUL MAKMUR MALANG,JL/ SAWOJAJAR V/25 RT 7 RW 01 SAWOJAJAR KEDUNGKANDANG KOTA MALANG,NORIS PIRNGADI,PACITAN,1984-11-13,,SMA,Wiraswasta,>5.000.000,ANDI HERNI,TANAH MERAH,1987-09-20,081231509052,S1,IRT,-,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,LAKI LAKI (L),21,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,PEREMPUAN (P),9,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,JUMLAH,30,,,,,,,,,,,,,,,,,,,,,,,,,,,,
CSV;

        $service = new StudentImportService();
        $rows = $service->extractRowsFromString($csvContent);
        $result = $service->parseAndImportRows($rows, $this->class8A->id, $this->institution->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['created']);

        // Check Abdurrohman Faqih
        $s1 = Student::where('nis', '0285')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('Abdurrohman Faqih', $s1->name);
        $this->assertEquals('0131397722', $s1->nisn);
        $this->assertEquals('L', $s1->jenis_kelamin);
        $this->assertEquals('BLITAR', $s1->tempat_lahir);
        $this->assertEquals('2013-04-29', $s1->tanggal_lahir?->format('Y-m-d'));
        $this->assertEquals('YONI PRIYANTO', $s1->nama_ayah);
        $this->assertEquals('SITI KHALIMAH', $s1->nama_ibu);
        $this->assertEquals($this->class8A->id, $s1->classroom_id);

        // Assert summary footer rows were NOT imported as students
        $this->assertNull(Student::where('name', 'like', '%LAKI LAKI%')->first());
        $this->assertNull(Student::where('name', 'like', '%JUMLAH%')->first());
    }

    /**
     * Test Format 2: Kelas 7 Dapodik/PPDB with Title Metadata and Multi-Row Header
     */
    public function test_import_format_2_kelas_7_with_top_titles_and_split_address(): void
    {
        $csvContent = <<<'CSV'
DATA SEMENTARA MURID BARU KELAS 7,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
SMP ISLAM BAITUL MAKMUR,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
TAHUN AJARAN 2026/2027,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
UPDATE 17 JULI 2026,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
KELAS,Nama,NIPD/NIS,JK,NISN,Tempat Lahir,Tanggal Lahir,NIK,ANAK KE-,DARI SAUDARA KANDUNG KE-,Agama,Alamat,RT,RW,Kelurahan,Kecamatan,Kode Pos,Data Ayah,,,,,,NOMOR HP,Data Ibu,,,,,,NOMOR HP,Data WALI (OPSIONAL),,,,,,NO TELEPON,Sekolah Asal,No KK
,,,,,,,,,,,,,,,,,NIK,Nama,Tanggal lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan (Rp),,NIK,Nama,Tanggal lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan (Rp),,NIK,Nama,Tanggal lahir,Jenjang Pendidikan,Pekerjaan,Penghasilan (Rp),,,
7,Adeeva Khanza Almeera,0320,P,3146235462,MALANG,2014-06-19,3573035905140004,3,3,ISLAM,JL. SELAT BRNGKALIS D11/09,5,9,LESANPURO,KEDUNGKANDANG,65138,357302707780006,JEFRI BASTIAN CHOLID,1978-07-27,S1,WIRASWASTA,2.000-3.000.000,085236364011,3573036702780006,EVI ERNAWATI,1978-02-27,S1,KARYAWAN SWASTA,2.000-3.000.000,081235356937,,,,,,,,MI AL HUDA MALANG,3573031408070787
7,Afiqah Inarah Putriana,0321,P,0138068027,MANGUPURA,2013-04-26,5103016604130001,5,5,ISLAM,JL. MERPATI NO. 16 LINGK. PESALAKAN TUBAN,,,TUBAN,KUTA,80361,51030101088820005,MUHAMMAD HALIKS,1982-08-01,SMA,WIRASWASTA,2.000-3.000.000,085792328608,5103015004830001,EVA CICILIA NOVANTI,1983-04-10,SMA,WIRAUSAHA,1.000.000-2.000.000,085792328608,,,,,,,,SD NO.06 TUBAN,5103013101110001
CSV;

        $service = new StudentImportService();
        $rows = $service->extractRowsFromString($csvContent);
        // Do not pass manual classroom_id; let the service auto-detect KELAS = 7
        $result = $service->parseAndImportRows($rows, null, $this->institution->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['created']);

        $s1 = Student::where('nis', '0320')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('Adeeva Khanza Almeera', $s1->name);
        $this->assertEquals('P', $s1->jenis_kelamin);
        $this->assertEquals('3146235462', $s1->nisn);
        $this->assertEquals('3573035905140004', $s1->nik);
        $this->assertEquals('3573031408070787', $s1->no_kk);
        $this->assertEquals('MI AL HUDA MALANG', $s1->asal_sekolah);
        $this->assertEquals($this->class7A->id, $s1->classroom_id); // Auto-assigned to Kelas 7A!

        // Assert Address Synthesis
        $this->assertStringContainsString('JL. SELAT BRNGKALIS D11/09', $s1->alamat_siswa);
        $this->assertStringContainsString('RT 5', $s1->alamat_siswa);
        $this->assertStringContainsString('RW 9', $s1->alamat_siswa);
        $this->assertStringContainsString('LESANPURO', $s1->alamat_siswa);
        $this->assertStringContainsString('KEDUNGKANDANG', $s1->alamat_siswa);

        // Assert Parents data
        $this->assertEquals('JEFRI BASTIAN CHOLID', $s1->nama_ayah);
        $this->assertEquals('085236364011', $s1->no_hp_ayah);
        $this->assertEquals('EVI ERNAWATI', $s1->nama_ibu);
        $this->assertEquals('081235356937', $s1->no_hp_ibu);
    }

    /**
     * Test Format 3: NOMOR, KELAS, Nama, NIPD/NIS, JK, NISN... (User's Third Data Set)
     */
    public function test_import_format_3_kelas_8_with_double_header_and_footer(): void
    {
        $csvContent = <<<'CSV'
NOMOR,KELAS,Nama,NIPD/NIS,JK,NISN,Tempat Lahir,Tanggal Lahir,NIK,Agama,Alamat,RT,RW,Kelurahan,Kecamatan,Kode Pos,Data Ayah,,,,NOMOR HP,Data Ibu,,,,,,Sekolah Asal,No KK
,,,,,,,,,,,,,,,,Nama,Jenjang Pendidikan,Pekerjaan,Penghasilan,,NIK,Nama,Jenjang Pendidikan,Pekerjaan,Penghasilan,NIK,,
1,8,Abdul Hakim Ar Rasyid,0262,L,0116459980,Batu,2011-05-09,3579030905110001,Islam ,Bamban V/300,11,5,Asrikaton,Pakis,65154,Nurdin Muhammad Ridho,SMA,Pegawai Swasta,2.000.000-3.000.000,081358399110,3579032508860001,Asfiyani,S-1,Pegawai Swasta,1.000.000-2.000.000,3507184206860002,SD Islam Baitul Makmur,3579031701120003
2,8,Almira Mozza Kinara,0282,P,0123375581,Malang,2012-02-12,3573035202120002,Islam ,Jl Sawojajar 17,3,3,Sawojajar,Kedungkandang,65139,Agus Pamuji,SMA,Buruh,500.000-1.000.000,085895429703,3573031708770014,Andriani,,,,,SDN Lesanpuro 3,3573032703120014
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,,,,,,,,,,,,,,,,,,,,,,,,
,,,,,LAKI-LAKI (L),12,,,,,,,,,,,,,,,,,,,,,,
,,,,,PEREMPUAN (P),7,,,,,,,,,,,,,,,,,,,,,,
,,,,,JUMLAH,19,,,,,,,,,,,,,,,,,,,,,,
CSV;

        $service = new StudentImportService();
        $rows = $service->extractRowsFromString($csvContent);
        $result = $service->parseAndImportRows($rows, null, $this->institution->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['created']);

        $s1 = Student::where('nis', '0262')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('Abdul Hakim Ar Rasyid', $s1->name);
        $this->assertEquals('L', $s1->jenis_kelamin);
        $this->assertEquals('0116459980', $s1->nisn);
        $this->assertEquals('3579030905110001', $s1->nik);
        $this->assertEquals('Nurdin Muhammad Ridho', $s1->nama_ayah);
        $this->assertEquals('081358399110', $s1->no_hp_ayah);
        $this->assertEquals('Asfiyani', $s1->nama_ibu);
        $this->assertEquals('SD Islam Baitul Makmur', $s1->asal_sekolah);
        $this->assertEquals($this->class8A->id, $s1->classroom_id); // Auto-assigned to Kelas 8A!

        $s2 = Student::where('nis', '0282')->first();
        $this->assertNotNull($s2);
        $this->assertEquals('Almira Mozza Kinara', $s2->name);
        $this->assertEquals('P', $s2->jenis_kelamin);
        $this->assertEquals('SDN Lesanpuro 3', $s2->asal_sekolah);
        $this->assertEquals($this->class8A->id, $s2->classroom_id);

        // Footer lines skipped
        $this->assertNull(Student::where('name', 'like', '%LAKI-LAKI%')->first());
    }

    /**
     * Test Livewire Action import_csv on ManageStudents Page
     */
    public function test_manage_students_page_import_csv_action(): void
    {
        $this->actingAs($this->staffUser);

        $csvData = "NO,NAMA,NIS,NISN,NIK,Tempat Lahir,Tanggal Lahir,Jenis Kelamin\n1,Siswa Unggulan,0999,0099988877,3573010101010001,Malang,2012-05-10,L";
        $file = UploadedFile::fake()->createWithContent('siswa.csv', $csvData);

        Livewire::test(ManageStudents::class)
            ->callAction('import_csv', [
                'file' => [$file],
                'target_classroom_id' => $this->class9A->id,
            ])
            ->assertHasNoActionErrors();

        $student = Student::where('nis', '0999')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Siswa Unggulan', $student->name);
        $this->assertEquals($this->class9A->id, $student->classroom_id);
    }
}
