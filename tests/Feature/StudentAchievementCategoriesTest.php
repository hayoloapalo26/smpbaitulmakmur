<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\User;
use App\Filament\Staff\Widgets\StudentAchievementStatsOverviewWidget;
use App\Filament\Staff\Pages\KetercapaianProfilLulusan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAchievementCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $wakaKesiswaan;
    protected Classroom $class9A;
    protected Student $student1;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->wakaKesiswaan = User::create([
            'name' => 'Waka Kesiswaan',
            'email' => 'kesiswaan@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
        ]);
        $this->wakaKesiswaan->assignRole('waka_kesiswaan');

        $this->class9A = Classroom::create([
            'institution_id' => $this->institution->id,
            'name' => '9-A',
            'tingkat' => '9',
        ]);

        $this->student1 = Student::create([
            'institution_id' => $this->institution->id,
            'classroom_id' => $this->class9A->id,
            'name' => 'Ahmad Fulan',
            'nis' => '1001',
            'nisn' => '0098765432',
            'tanggal_lahir' => '2010-01-01',
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_can_create_individual_non_akademik_achievement(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        $achievement = StudentAchievement::create([
            'student_id' => $this->student1->id,
            'institution_id' => $this->institution->id,
            'lingkup' => StudentAchievement::LINGKUP_INDIVIDUAL,
            'kategori' => StudentAchievement::KATEGORI_NON_AKADEMIK,
            'tingkat' => 'Kota / Kabupaten',
            'nama_prestasi' => 'Juara 1 Lomba Pidato Bahasa Arab',
            'tanggal' => now(),
            'jenis_reward' => StudentAchievement::REWARD_NONE,
        ]);

        $this->assertDatabaseHas('student_achievements', [
            'id' => $achievement->id,
            'kategori' => 'non_akademik',
            'lingkup' => 'individual',
            'nama_prestasi' => 'Juara 1 Lomba Pidato Bahasa Arab',
        ]);

        $this->assertEquals('Prestasi Non-Akademik', $achievement->kategori_label);
        $this->assertEquals('Individual (Perorangan)', $achievement->lingkup_label);
        $this->assertEquals('Tanpa Reward Khusus', $achievement->reward_label);
    }

    public function test_can_create_prestasi_kelas_with_sub_kategori_and_rewards(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        // 1. Prestasi Kelas - Adab dengan Reward Religi
        $adabAch = StudentAchievement::create([
            'classroom_id' => $this->class9A->id,
            'institution_id' => $this->institution->id,
            'lingkup' => StudentAchievement::LINGKUP_KELAS,
            'kategori' => StudentAchievement::KATEGORI_PRESTASI_KELAS,
            'sub_kategori' => StudentAchievement::SUB_ADAB,
            'tingkat' => 'Sekolah / Gugus',
            'nama_prestasi' => 'Juara 1 Kelas Paling Beradab & Bersih',
            'tanggal' => now(),
            'jenis_reward' => StudentAchievement::REWARD_RELIGI,
            'reward_detail' => 'Mushaf Al-Qur\'an Tajwid untuk Seluruh Siswa Rombel',
        ]);

        // 2. Prestasi Kelas - Karakter dengan Reward Akademik
        $karakterAch = StudentAchievement::create([
            'classroom_id' => $this->class9A->id,
            'institution_id' => $this->institution->id,
            'lingkup' => StudentAchievement::LINGKUP_KELAS,
            'kategori' => StudentAchievement::KATEGORI_PRESTASI_KELAS,
            'sub_kategori' => StudentAchievement::SUB_KARAKTER,
            'tingkat' => 'Sekolah / Gugus',
            'nama_prestasi' => 'Kelas Terbaik Disiplin & Kepemimpinan Upacara',
            'tanggal' => now(),
            'jenis_reward' => StudentAchievement::REWARD_AKADEMIK,
            'reward_detail' => 'Bebas SPP 1 Bulan & Piagam Kehormatan',
        ]);

        // 3. Prestasi Kelas - Religius
        $religiAch = StudentAchievement::create([
            'classroom_id' => $this->class9A->id,
            'institution_id' => $this->institution->id,
            'lingkup' => StudentAchievement::LINGKUP_KELAS,
            'kategori' => StudentAchievement::KATEGORI_PRESTASI_KELAS,
            'sub_kategori' => StudentAchievement::SUB_RELIGIUS,
            'tingkat' => 'Sekolah / Gugus',
            'nama_prestasi' => 'Kelas Paling Khusyuk Sholat Berjamaah & Tadarus',
            'tanggal' => now(),
            'jenis_reward' => StudentAchievement::REWARD_RELIGI,
            'reward_detail' => 'Paket Buku Dzikir & Mushaf Al-Qur\'an',
        ]);

        $this->assertEquals('Prestasi Kelas', $adabAch->kategori_label);
        $this->assertEquals('Prestasi Adab', $adabAch->sub_kategori_label);
        $this->assertEquals('Prestasi Kelas (Kelompok)', $adabAch->lingkup_label);
        $this->assertEquals('Reward Religi', $adabAch->reward_label);

        $this->assertEquals('Prestasi Karakter', $karakterAch->sub_kategori_label);
        $this->assertEquals('Reward Akademik', $karakterAch->reward_label);

        $this->assertEquals('Prestasi Religius', $religiAch->sub_kategori_label);
    }

    public function test_student_achievement_stats_widget_reflects_all_categories_and_rewards(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        // Create varied achievements
        StudentAchievement::create([
            'student_id' => $this->student1->id,
            'institution_id' => $this->institution->id,
            'lingkup' => 'individual',
            'kategori' => 'non_akademik',
            'tingkat' => 'Kota / Kabupaten',
            'nama_prestasi' => 'Juara Futsal',
            'tanggal' => now(),
        ]);

        StudentAchievement::create([
            'classroom_id' => $this->class9A->id,
            'institution_id' => $this->institution->id,
            'lingkup' => 'kelas',
            'kategori' => 'prestasi_kelas',
            'sub_kategori' => 'adab',
            'tingkat' => 'Sekolah / Gugus',
            'nama_prestasi' => 'Kelas Beradab',
            'tanggal' => now(),
            'jenis_reward' => 'reward_religi',
            'reward_detail' => 'Mushaf Al-Qur\'an',
        ]);

        StudentAchievement::create([
            'student_id' => $this->student1->id,
            'institution_id' => $this->institution->id,
            'lingkup' => 'individual',
            'kategori' => 'akademik',
            'tingkat' => 'Provinsi',
            'nama_prestasi' => 'Juara Olimpiade Matematika',
            'tanggal' => now(),
            'jenis_reward' => 'reward_akademik',
            'reward_detail' => 'Beasiswa SPP',
        ]);

        $widget = new class extends StudentAchievementStatsOverviewWidget {
            public function callGetStats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->callGetStats();

        $this->assertCount(5, $stats);
        $this->assertEquals('Total Prestasi', $stats[0]->getLabel());
        $this->assertEquals(3, $stats[0]->getValue());

        $this->assertEquals('Prestasi Non-Akademik', $stats[1]->getLabel());
        $this->assertStringContainsString('1', (string) $stats[1]->getValue());

        $this->assertEquals('Prestasi Kelas', $stats[2]->getLabel());
        $this->assertStringContainsString('1', (string) $stats[2]->getValue());

        $this->assertEquals('Prestasi Akademik', $stats[3]->getLabel());
        $this->assertStringContainsString('1', (string) $stats[3]->getValue());

        $this->assertEquals('Reward Kesiswaan', $stats[4]->getLabel());
        $this->assertStringContainsString('2 Diberikan', (string) $stats[4]->getValue());
    }

    public function test_ketercapaian_profil_lulusan_includes_class_and_individual_achievements(): void
    {
        $this->actingAs($this->wakaKesiswaan);

        // 1. Prestasi Individual Non-Akademik
        StudentAchievement::create([
            'student_id' => $this->student1->id,
            'institution_id' => $this->institution->id,
            'lingkup' => 'individual',
            'kategori' => 'non_akademik',
            'tingkat' => 'Kota / Kabupaten',
            'nama_prestasi' => 'Juara Silat',
            'tanggal' => now(),
        ]);

        // 2. Prestasi Kelas untuk kelas 9A (Ahmad Fulan ada di kelas 9A)
        StudentAchievement::create([
            'classroom_id' => $this->class9A->id,
            'institution_id' => $this->institution->id,
            'lingkup' => 'kelas',
            'kategori' => 'prestasi_kelas',
            'sub_kategori' => 'adab',
            'tingkat' => 'Sekolah / Gugus',
            'nama_prestasi' => 'Juara 1 Kelas Paling Beradab',
            'tanggal' => now(),
            'jenis_reward' => 'reward_religi',
            'reward_detail' => 'Mushaf Al-Qur\'an',
        ]);

        $page = new KetercapaianProfilLulusan();
        $page->selectedClassroomId = $this->class9A->id;
        $page->selectedStudentId = $this->student1->id;

        $studentData = $page->getStudentsData();
        $this->assertNotEmpty($studentData);
        $studentItem = $studentData->firstWhere('id', $this->student1->id);
        $this->assertNotNull($studentItem);
        // Total achievements should be 2 (1 individual + 1 class)
        $this->assertEquals(2, $studentItem->total_achievements);

        $detail = $page->getSelectedStudentDetail();
        $this->assertNotNull($detail);
        $this->assertEquals(2, $detail->prestasi->total_count);
        $this->assertEquals(1, $detail->prestasi->kelas_count);
        $this->assertEquals(1, $detail->prestasi->reward_count);
    }
}
