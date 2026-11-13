<?php

namespace Tests\Feature;

use App\Filament\Staff\Resources\RppmResource\Pages\ViewRppm;
use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Rppm;
use App\Models\Subject;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RppmDetailViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $kepalaSekolah;
    protected User $guru;
    protected Institution $institution;
    protected Subject $subject;
    protected CapaianPembelajaran $cp;
    protected AlurTujuanPembelajaran $atp;
    protected TujuanPembelajaran $tp1;
    protected TujuanPembelajaran $tp2;
    protected Rppm $rppm;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name' => 'SMP Islam Baitul Makmur',
            'slug' => 'smp',
            'is_active' => true,
        ]);

        $this->kepalaSekolah = User::factory()->create([
            'name' => 'Dr. H. Ahmad Fauzi, M.Pd',
            'email' => 'kepsek@baitulmakmur.sch.id',
            'institution_id' => $this->institution->id,
        ]);
        $this->kepalaSekolah->assignRole('kepala_sekolah');

        $this->guru = User::factory()->create([
            'name' => 'Lya Ratnawati, M.Pd',
            'email' => 'lya@baitulmakmur.sch.id',
            'nuptk' => '1234567890123456',
            'institution_id' => $this->institution->id,
        ]);
        $this->guru->assignRole('guru_mapel');

        $this->subject = Subject::create([
            'name' => 'Ilmu Pengetahuan Alam (IPA)',
            'kode' => 'IPA-7',
            'institution_id' => $this->institution->id,
        ]);

        $this->cp = CapaianPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'mata_pelajaran' => 'Ilmu Pengetahuan Alam (IPA)',
            'tingkat' => 'Kelas 7',
            'kode_cp' => 'CP-IPA-01',
            'elemen' => 'Pemahaman IPA',
            'deskripsi_cp' => 'Peserta didik memahami klasifikasi makhluk hidup dan peranannya dalam ekosistem seimbang.',
            'masa_berlaku' => '2026/2027',
        ]);

        $this->atp = AlurTujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'mata_pelajaran' => 'Ilmu Pengetahuan Alam (IPA)',
            'tingkat' => 'Kelas 7',
            'kode_atp' => 'ATP-IPA-01',
            'judul_atp' => 'Alur Pembelajaran Ekosistem dan Klasifikasi Hayati',
            'deskripsi_atp' => 'Alur bertahap pengamatan struktur biologi kontekstual di lingkungan sekolah.',
        ]);

        $this->tp1 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'alur_tujuan_pembelajaran_id' => $this->atp->id,
            'kode_tp' => 'TP-01',
            'materi' => 'Klasifikasi Makhluk Hidup',
            'deskripsi_tp' => 'Mengidentifikasi ciri-ciri spesifik lima kingdom makhluk hidup melalui observasi terpadu.',
        ]);

        $this->tp2 = TujuanPembelajaran::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'capaian_pembelajaran_id' => $this->cp->id,
            'alur_tujuan_pembelajaran_id' => $this->atp->id,
            'kode_tp' => 'TP-02',
            'materi' => 'Interaksi Biotik & Abiotik',
            'deskripsi_tp' => 'Menganalisis hubungan timbal balik antara komponen biotik dan abiotik dalam rantai makanan.',
        ]);

        $this->rppm = Rppm::create([
            'institution_id' => $this->institution->id,
            'guru_id' => $this->guru->id,
            'mata_pelajaran' => 'Ilmu Pengetahuan Alam (IPA)',
            'kelas_fase' => 'Kelas 7 / Fase D',
            'topik' => 'Ekosistem dan Keanekaragaman Hayati',
            'alokasi_waktu' => '2 x 40 Menit (Pertemuan ke-1)',
            'capaian_pembelajaran_id' => $this->cp->id,
            'alur_tujuan_pembelajaran_id' => $this->atp->id,
            'selected_tp_ids' => [$this->tp1->id, $this->tp2->id],
            'status' => 'approved',
            'catatan_kepala_sekolah' => 'RPPM telah memenuhi standar modul ajar Kurikulum Merdeka. Sangat baik.',
            'reviewed_by' => $this->kepalaSekolah->id,
            'reviewed_at' => now(),
            'content' => [
                'lintas_disiplin' => 'IPA, Geografi, dan Pendidikan Agama Islam (Tadabbur Alam)',
                'profil_lulusan' => [
                    ['dimensi' => 'Beriman & Bertakwa kepada Tuhan YME'],
                    ['dimensi' => 'Bernalar Kritis'],
                    ['dimensi' => 'Gotong Royong'],
                ],
                'pemahaman_bermakna' => 'Keseimbangan alam bergantung pada kesadaran manusia dalam merawat lingkungan hidup.',
                'pertanyaan_pemantik' => 'Mengapa kepunahan satu jenis serangga dapat mengganggu pasokan pangan manusia?',
                'model_pembelajaran' => 'Problem-Based Learning (PBL)',
                'metode_pembelajaran' => 'Eksplorasi Lingkungan, Diskusi Kelompok, dan Presentasi Poster',
                'pengalaman_belajar' => [
                    [
                        'tahap' => 'Pendahuluan',
                        'kegiatan' => 'Guru mengucapkan salam, membaca doa bersama, presensi, serta apersepsi video rantai makanan.',
                        'alokasi_waktu' => '15 Menit',
                    ],
                    [
                        'tahap' => 'Kegiatan Inti',
                        'kegiatan' => 'Murid berkelompok mengamati biotik dan abiotik di kebun sekolah, menyusun jaring-jaring makanan pada LKPD.',
                        'alokasi_waktu' => '55 Menit',
                    ],
                    [
                        'tahap' => 'Penutup',
                        'kegiatan' => 'Refleksi pembelajaran, guru memberikan penguatan, dan menutup dengan hamdalah dan doa.',
                        'alokasi_waktu' => '10 Menit',
                    ],
                ],
                'kktp' => [
                    [
                        'kriteria' => 'Ketepatan mengidentifikasi komponen biotik dan abiotik',
                        'bimbingan' => 'Dapat menyebutkan < 3 komponen',
                        'cukup' => 'Dapat menyebutkan 3 - 4 komponen dengan benar',
                        'baik' => 'Dapat menyebutkan 5 - 6 komponen dengan benar',
                        'sangat_baik' => 'Dapat mengidentifikasi seluruh komponen dan fungsinya secara tepat',
                    ],
                ],
            ],
        ]);
    }

    public function test_kepala_sekolah_views_rppm_as_narrative_description_not_inputs(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $this->actingAs($this->kepalaSekolah);

        Livewire::test(ViewRppm::class, ['record' => $this->rppm->id])
            ->assertSuccessful()
            // 1. Memastikan Kop dan Judul Dokumen Tampil Resmi
            ->assertSee('Rencana Pelaksanaan Pembelajaran Mingguan (RPPM)')
            ->assertSee('SMP ISLAM BAITUL MAKMUR')
            ->assertSee('Ekosistem dan Keanekaragaman Hayati')
            ->assertSee('Lya Ratnawati, M.Pd')
            // 2. Memastikan Banner Supervisi Kepala Sekolah Tampil
            ->assertSee('Dokumen RPPM Telah Disetujui (ACC)')
            ->assertSee('RPPM telah memenuhi standar modul ajar Kurikulum Merdeka. Sangat baik.')
            // 3. Memastikan Deskripsi CP & TP Tampil Utuh dalam Bentuk Teks Naratif
            ->assertSee('Peserta didik memahami klasifikasi makhluk hidup dan peranannya dalam ekosistem seimbang.')
            ->assertSee('Mengidentifikasi ciri-ciri spesifik lima kingdom makhluk hidup melalui observasi terpadu.')
            ->assertSee('Menganalisis hubungan timbal balik antara komponen biotik dan abiotik dalam rantai makanan.')
            // 4. Memastikan Profil Pelajar Pancasila & Pemantik Tampil
            ->assertSee('Beriman & Bertakwa kepada Tuhan YME')
            ->assertSee('Bernalar Kritis')
            ->assertSee('Keseimbangan alam bergantung pada kesadaran manusia dalam merawat lingkungan hidup.')
            ->assertSee('Mengapa kepunahan satu jenis serangga dapat mengganggu pasokan pangan manusia?')
            // 5. Memastikan Alur Kegiatan Pembelajaran & Rubrik KKTP Tampil Sebagai Dokumen
            ->assertSee('Pendahuluan')
            ->assertSee('Kegiatan Inti')
            ->assertSee('Penutup')
            ->assertSee('Matriks Rubrik Penilaian Ketercapaian (KKTP)')
            ->assertSee('Ketepatan mengidentifikasi komponen biotik dan abiotik')
            // 6. Memastikan Pengesahan Tampil
            ->assertSee('Lembar Pengesahan Modul Ajar')
            ->assertSee('Dr. H. Ahmad Fauzi, M.Pd');
    }

    public function test_kepala_sekolah_can_approve_rppm_with_notes(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('staff'));

        $this->rppm->update([
            'status' => 'published',
            'catatan_kepala_sekolah' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $this->actingAs($this->kepalaSekolah);

        Livewire::test(ViewRppm::class, ['record' => $this->rppm->id])
            ->assertActionVisible('approve_rppm')
            ->assertActionVisible('revise_rppm')
            ->callAction('approve_rppm', [
                'catatan_kepala_sekolah' => 'RPPM sangat inovatif dan disetujui tanpa catatan perbaikan.',
            ])
            ->assertHasNoActionErrors();

        $this->rppm->refresh();
        $this->assertEquals('approved', $this->rppm->status);
        $this->assertEquals('RPPM sangat inovatif dan disetujui tanpa catatan perbaikan.', $this->rppm->catatan_kepala_sekolah);
        $this->assertEquals($this->kepalaSekolah->id, $this->rppm->reviewed_by);
        $this->assertNotNull($this->rppm->reviewed_at);
    }
}
