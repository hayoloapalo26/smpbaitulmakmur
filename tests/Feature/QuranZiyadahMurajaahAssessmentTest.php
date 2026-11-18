<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuranZiyadahMurajaahAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $guruQuran;
    protected Student $santri;
    protected Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_alquran', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => User::ROLE_GURU_QURAN_TAHFIDZ_DASAR, 'guard_name' => 'web']);

        Institution::firstOrCreate(['id' => 1], ['slug' => 'yayasan', 'name' => 'Yayasan Baitul Makmur']);
        Institution::firstOrCreate(['id' => 2], ['slug' => 'smp', 'name' => 'SMP Islam Baitul Makmur']);

        $this->class = Classroom::create([
            'name' => '7B',
            'tingkat' => '7',
            'institution_id' => 2,
        ]);

        $this->guruQuran = User::create([
            'name' => 'Ustadzah Maryam Tahfidz',
            'username' => 'ust_maryam',
            'email' => 'maryam@quran.test',
            'password' => bcrypt('password123'),
            'institution_id' => 2,
            'is_active' => true,
        ]);
        $this->guruQuran->assignRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR);

        $this->santri = Student::create([
            'institution_id' => 2,
            'name' => 'Aisyah Putri',
            'nis' => '0876',
            'jenis_kelamin' => 'P',
            'tanggal_lahir' => '2012-08-15',
            'classroom_id' => $this->class->id,
            'guru_quran_id' => $this->guruQuran->id,
            'kelas_quran' => 'tahfidz_dasar',
        ]);
    }

    /**
     * Test: Menyimpan setoran lengkap Ziyadah (Juz, Surat, Ayat) dan Muraja'ah (Juz, Surat, Ayat)
     * beserta nilai skala kualitas bacaan hafalan (0-100) dan predikat.
     */
    public function test_save_both_ziyadah_and_murajaah_with_scale_score(): void
    {
        $this->actingAs($this->guruQuran);

        $achievement = QuranAchievement::create([
            'institution_id'    => 2,
            'student_id'        => $this->santri->id,
            'teacher_id'        => $this->guruQuran->id,
            'program'           => 'tahfidz_dasar',
            // Ziyadah: Juz 30, Surat An-Naba' (78), Ayat 1-15
            'juz'               => 30,
            'surah'             => '78',
            'ayah'              => '1-15',
            // Muraja'ah: Juz 30, Surat An-Nas (114), Ayat 1-6
            'juz_murajaah'      => 30,
            'surah_murajaah'    => '114',
            'ayah_murajaah'     => '1-6',
            // Nilai Skala Kualitas Bacaan Hafalan (0 - 100)
            'scale_score'       => 95,
            'predikat'          => 'Mumtaz',
            'catatan'           => 'Ziyadah lancar mutqin, murajaah surat An-Nas sangat baik tanpa keraguan.',
            'tanggal'           => '2026-10-31',
        ]);

        $this->assertDatabaseHas('quran_achievements', [
            'id'             => $achievement->id,
            'student_id'     => $this->santri->id,
            'juz'            => 30,
            'surah'          => '78',
            'ayah'           => '1-15',
            'juz_murajaah'   => 30,
            'surah_murajaah' => '114',
            'ayah_murajaah'  => '1-6',
            'scale_score'    => 95,
            'predikat'       => 'Mumtaz',
        ]);

        // Verifikasi otomatis terdeteksi tipe 'ziyadah' dan 'murajaah'
        $fresh = $achievement->fresh();
        $this->assertContains('ziyadah', $fresh->type);
        $this->assertContains('murajaah', $fresh->type);

        // Verifikasi Accessors
        $this->assertEquals('An-Naba', $fresh->surah_name);
        $this->assertEquals('An-Nas', $fresh->surah_murajaah_name);
        $this->assertEquals('Juz 30, Surat An-Naba, Ayat 1-15', $fresh->ziyadah_formatted);
        $this->assertEquals('Juz 30, Surat An-Nas, Ayat 1-6', $fresh->murajaah_formatted);

        // Verifikasi Sinkronisasi otomatis ke reading_quality_scores
        $this->assertDatabaseHas('reading_quality_scores', [
            'student_id'  => $this->santri->id,
            'teacher_id'  => $this->guruQuran->id,
            'scale_score' => 95,
            'predikat'    => 'Mumtaz',
        ]);
    }

    /**
     * Test: Menyimpan setoran santri yang hanya Ziyadah (Muraja'ah kosong / nullable)
     */
    public function test_save_only_ziyadah_when_murajaah_is_empty(): void
    {
        $this->actingAs($this->guruQuran);

        $achievement = QuranAchievement::create([
            'institution_id'    => 2,
            'student_id'        => $this->santri->id,
            'teacher_id'        => $this->guruQuran->id,
            'program'           => 'tahfidz_dasar',
            'juz'               => 30,
            'surah'             => '79',
            'ayah'              => '1-20',
            'juz_murajaah'      => null,
            'surah_murajaah'    => null,
            'ayah_murajaah'     => null,
            'scale_score'       => 85,
            'predikat'          => 'Jayyid Jiddan',
            'tanggal'           => '2026-10-31',
        ]);

        $fresh = $achievement->fresh();
        $this->assertEquals('An-Naziat', $fresh->surah_name);
        $this->assertNull($fresh->surah_murajaah_name);
        $this->assertEquals('Juz 30, Surat An-Naziat, Ayat 1-20', $fresh->ziyadah_formatted);
        $this->assertEquals('-', $fresh->murajaah_formatted);
        $this->assertContains('ziyadah', $fresh->type);
        $this->assertNotContains('murajaah', $fresh->type);
    }

    /**
     * Test: Ketika santri tidak menyetor (tidak_menyetor atau '-'),
     * pilihan/nilai juz, surat, dan ayat otomatis '-' dan hafalan terakhir tetap tersimpan.
     */
    public function test_tidak_menyetor_sets_juz_surat_ayah_to_dash_and_preserves_next_progress(): void
    {
        $this->actingAs($this->guruQuran);

        // 1. Hari 1: Santri menyetor Ziyadah An-Naba ayat 1-10
        QuranAchievement::create([
            'institution_id'    => 2,
            'student_id'        => $this->santri->id,
            'teacher_id'        => $this->guruQuran->id,
            'program'           => 'tahfidz_dasar',
            'juz'               => 30,
            'surah'             => '78',
            'ayah'              => '1-10',
            'scale_score'       => 88,
            'predikat'          => 'Jayyid Jiddan',
            'tanggal'           => '2026-11-01',
        ]);

        // 2. Hari 2: Santri TIDAK menyetor (memilih tidak setor)
        $tidakSetor = QuranAchievement::create([
            'institution_id'    => 2,
            'student_id'        => $this->santri->id,
            'teacher_id'        => $this->guruQuran->id,
            'program'           => 'tahfidz_dasar',
            'juz'               => null,
            'surah'             => 'tidak_menyetor',
            'ayah'              => '-',
            'surah_murajaah'    => 'tidak_menyetor',
            'ayah_murajaah'     => '-',
            'scale_score'       => 85,
            'predikat'          => 'Jayyid Jiddan',
            'tanggal'           => '2026-11-02',
        ]);

        $freshTidakSetor = $tidakSetor->fresh();
        $this->assertNull($freshTidakSetor->juz);
        $this->assertEquals('-', $freshTidakSetor->ayah);
        $this->assertEquals('-', $freshTidakSetor->ayah_murajaah);
        $this->assertEquals('❌ Tidak Setor', $freshTidakSetor->ziyadah_formatted);
        $this->assertEquals('❌ Tidak Setor', $freshTidakSetor->murajaah_formatted);
        $this->assertContains('tidak_menyetor', $freshTidakSetor->type);

        // 3. Hari 3: Cek kelanjutan otomatis (calculateNextProgress),
        // harus tetap mengambil hafalan terakhir yang valid (An-Naba 1-10 -> lanjut 11-20),
        // tidak boleh ter-reset gara-gara hari kemarin tidak menyetor.
        $next = QuranAchievement::calculateNextProgress($tidakSetor, 'tahfidz_dasar', $this->santri->id);
        $this->assertTrue($next['has_previous']);
        $this->assertEquals('78', $next['surah']);
        $this->assertEquals('11-20', $next['ayah']);
    }
}
