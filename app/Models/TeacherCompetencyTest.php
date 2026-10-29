<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TeacherCompetencyTest extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jadwal' => 'datetime',
        'notif_dismissed_at' => 'datetime',
        'tanggal_lahir' => 'date',
        'komponen_penilaian' => 'array',
        'skor_manajerial' => 'integer',
        'skor_pedagogik' => 'integer',
        'skor_profesional' => 'integer',
        'skor_sosial' => 'integer',
        'skor_kepribadian' => 'integer',
        'skor_komitmen' => 'integer',
        'skor_program_unggulan' => 'integer',
        'skor_promosi' => 'integer',
        'total_skor' => 'integer',
        'nilai_akhir' => 'float',
        'score' => 'float',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public static function getTeknikOptions(): array
    {
        return [
            'D' => 'D (Dokumentasi)',
            'O' => 'O (Observasi)',
            'W' => 'W (Wawancara)',
            'A' => 'A (Angket)',
        ];
    }

    public static function getStatusKepegawaianOptions(): array
    {
        return [
            'Guru Tetap Yayasan (GTY)' => 'Guru Tetap Yayasan (GTY)',
            'Guru Tidak Tetap (GTT)' => 'Guru Tidak Tetap (GTT)',
            'Guru Kontrak / Calon GTY' => 'Guru Kontrak / Calon GTY',
            'Guru DPK / Kemenag' => 'Guru DPK / Kemenag',
            'Pegawai Yayasan' => 'Pegawai Yayasan',
        ];
    }

    public static function getClustersStructure(): array
    {
        return [
            'manajerial' => [
                'code' => 'A',
                'name' => 'Manajerial',
                'description' => 'Pengelolaan administrasi guru, kelas, kepemimpinan & budaya kolaborasi',
                'max_score' => 126, // 21 * 6
                'count' => 21,
                'start_idx' => 1,
                'end_idx' => 21,
                'indicators' => [
                    1 => ['title' => 'Perangkat Perencanaan Pembelajaran', 'desc' => 'Kelengkapan dan keselarasan Modul Ajar / RPP, LKPD, Prota, dan Promes.', 'teknik' => 'D'],
                    2 => ['title' => 'Perangkat Pelaksanaan Pembelajaran', 'desc' => 'Ketertiban buku absensi peserta didik dan jurnal harian mengajar.', 'teknik' => 'D'],
                    3 => ['title' => 'Perangkat Penilaian Hasil Belajar', 'desc' => 'Kisi-kisi soal terukur, naskah asesmen, rubrik penilaian, dan analisis hasil evaluasi.', 'teknik' => 'D'],
                    4 => ['title' => 'Tindak Lanjut Supervisi Pembelajaran', 'desc' => 'Penerapan rekomendasi supervisor yayasan/sekolah dalam perbaikan pengajaran.', 'teknik' => 'D'],
                    5 => ['title' => 'Penilaian Kinerja Guru (PKG)', 'desc' => 'Kelengkapan instrumen evaluasi PKG berkala dan pencapaian target kinerja.', 'teknik' => 'D'],
                    6 => ['title' => 'Sertifikat & Pengembangan Diri', 'desc' => 'Bukti sertifikasi, pelatihan, workshop, dan diklat keprofesian berkelanjutan.', 'teknik' => 'D'],
                    7 => ['title' => 'Pengelolaan Lingkungan Fisik Kelas', 'desc' => 'Kebersihan, kerapian, tata letak fasilitas belajar, pencahayaan, dan estetika kelas.', 'teknik' => 'O'],
                    8 => ['title' => 'Fasilitas Kelas & Sarana P3K', 'desc' => 'Ketersediaan kotak P3K, sarana tanggap darurat, dan perawatan inventaris kelas.', 'teknik' => 'O'],
                    9 => ['title' => 'Iklim Kelas Harmonis & Zero Bullying', 'desc' => 'Mewujudkan suasana kelas aman, kondusif, dan bebas perundungan fisik/verbal/sosial.', 'teknik' => 'O'],
                    10 => ['title' => 'Komunikasi Ramah Anak & Bilingual', 'desc' => 'Tutur kata santun, empati, pendekatan komunikatif ramah anak, dan pembiasaan bilingual.', 'teknik' => 'O'],
                    11 => ['title' => 'Agen Perubahan & Keteladanan Pendidik', 'desc' => 'Menjadi pelopor inovasi dan keteladanan pembelajaran di lingkungan madrasah/sekolah.', 'teknik' => 'W'],
                    12 => ['title' => 'Mentoring & Pendampingan Sejawat', 'desc' => 'Membimbing dan mendampingi rekan guru dalam penyusunan modul atau penerapan TIK.', 'teknik' => 'W'],
                    13 => ['title' => 'Keaktifan Komunitas Belajar / KKG / MGMP', 'desc' => 'Keaktifan hadir dan berkontribusi dalam forum KKG, MGMP, atau kombel sekolah.', 'teknik' => 'D'],
                    14 => ['title' => 'Diseminasi Praktik Baik (Best Practice)', 'desc' => 'Berbagi pengalaman sukses pembelajaran inspiratif kepada guru lain.', 'teknik' => 'D'],
                    15 => ['title' => 'Inovasi Pembelajaran Student-Centered', 'desc' => 'Pengembangan metode mengajar variatif yang memusatkan murid sebagai subjek belajar.', 'teknik' => 'O'],
                    16 => ['title' => 'Pemanfaatan TIK & Deep Learning', 'desc' => 'Integrasi perangkat digital interaktif untuk merangsang nalar kritis murid.', 'teknik' => 'D'],
                    17 => ['title' => 'Dampak Prestasi Akademik & Produk Murid', 'desc' => 'Peningkatan nilai, karya nyata/produk siswa, dan keberhasilan dalam kompetisi.', 'teknik' => 'D'],
                    18 => ['title' => 'Dampak Kemahiran Bahasa & Tahfidz Murid', 'desc' => 'Peningkatan kelancaran percakapan bahasa Inggris, tahsin Yanbu\'a, dan hafalan siswa.', 'teknik' => 'O'],
                    19 => ['title' => 'Pembiasaan Karakter & Sholat Khusyuk Murid', 'desc' => 'Ketertiban ibadah sholat berjamaah, salam/salim, adab islami, dan kedisiplinan murid.', 'teknik' => 'O'],
                    20 => ['title' => 'Pengelolaan Kelas Inklusif & Responsif ABK', 'desc' => 'Mengakomodasi siswa dengan kebutuhan khusus atau gaya belajar beraneka ragam.', 'teknik' => 'O'],
                    21 => ['title' => 'Penanganan Masalah Berbasis Data & Reflektif', 'desc' => 'Kolaborasi dengan BK, wali kelas, orang tua murid, serta budaya refleksi diri.', 'teknik' => 'W'],
                ],
            ],
            'pedagogik' => [
                'code' => 'B',
                'name' => 'Pedagogik',
                'description' => 'Penyusunan perangkat standar, penerapan di kelas, dan evaluasi hasil belajar',
                'max_score' => 132, // 22 * 6
                'count' => 22,
                'start_idx' => 22,
                'end_idx' => 43,
                'indicators' => [
                    22 => ['title' => 'Analisis Keselarasan CP, TP, & KKTP', 'desc' => 'Keterukuran dan kedalaman tujuan pembelajaran yang selaras Capaian Pembelajaran.', 'teknik' => 'D'],
                    23 => ['title' => 'Desain Pembelajaran Berdiferensiasi', 'desc' => 'Perencanaan diferensiasi konten, proses, dan produk sesuai kesiapan dan minat murid.', 'teknik' => 'D'],
                    24 => ['title' => 'Desain Pembelajaran Deep Learning', 'desc' => 'Alur runtut pembelajaran bermakna (Memahami > Mengaplikasi > Merefleksi).', 'teknik' => 'D'],
                    25 => ['title' => 'Integrasi Bahan Ajar & Karakter', 'desc' => 'Transformasi nilai adab islami, etika, dan pembiasaan karakter ke dalam bahan ajar.', 'teknik' => 'D'],
                    26 => ['title' => 'Muatan Classroom Language (Bahasa Inggris)', 'desc' => 'Perencanaan instruksi pengantar bilingual dalam modul ajar/RPP.', 'teknik' => 'D'],
                    27 => ['title' => 'Muatan Tahsin & Tahfidz Al-Qur\'an (BMQ)', 'desc' => 'Perencanaan pembiasaan mengaji dan hafalan Al-Qur\'an terstruktur.', 'teknik' => 'D'],
                    28 => ['title' => 'Nalar Kritis & Pertanyaan Pemantik (Mindful)', 'desc' => 'Membangun pertanyaan pemantik dan keterlibatan aktif berpikir kritis murid.', 'teknik' => 'O'],
                    29 => ['title' => 'Konteks Kehidupan Nyata (Meaningful)', 'desc' => 'Keterkaitan konsep materi pembelajaran dengan kehidupan sehari-hari murid.', 'teknik' => 'O'],
                    30 => ['title' => 'Tahapan Belajar Runtut & Terarah', 'desc' => 'Penguasaan alur Memahami, Mengaplikasikan, hingga Merefleksikan materi.', 'teknik' => 'O'],
                    31 => ['title' => 'Kolaborasi & Diskusi Aktif (Joyful)', 'desc' => 'Memfasilitasi kerja sama tim yang komunikatif, aktif, dan menyenangkan.', 'teknik' => 'O'],
                    32 => ['title' => 'Pemanfaatan Media Digital Interaktif', 'desc' => 'Penggunaan media belajar digital, proyektor, dan sumber internet secara kreatif.', 'teknik' => 'O'],
                    33 => ['title' => 'Penerapan Diferensiasi di Kelas', 'desc' => 'Proses berjenjang (scaffolding) dan fleksibilitas tagihan produk sesuai murid.', 'teknik' => 'O'],
                    34 => ['title' => 'Internalisasi Karakter Personal', 'desc' => 'Disiplin, ketepatan waktu, kerapian busana/atribut, dan ketuntasan tugas murid.', 'teknik' => 'O'],
                    35 => ['title' => 'Internalisasi Karakter Pribadi', 'desc' => 'Menanamkan kejujuran, kesederhanaan, kesabaran, semangat belajar, dan karya nyata.', 'teknik' => 'O'],
                    36 => ['title' => 'Internalisasi Karakter Sosial', 'desc' => 'Menumbuhkan toleransi, empati, peduli sesama, sopan santun, dan kerja sama.', 'teknik' => 'O'],
                    37 => ['title' => 'Internalisasi Karakter Religius', 'desc' => 'Pembiasaan sholat berjamaah khusyuk, salam/salim, dan adab islami sehari-hari.', 'teknik' => 'O'],
                    38 => ['title' => 'Penggunaan Classroom Language Konsisten', 'desc' => 'Konsistensi penggunaan instruksi bahasa Inggris komunikatif di dalam kelas.', 'teknik' => 'O'],
                    39 => ['title' => 'Penerapan Standar Tahsin Yanbu\'a', 'desc' => 'Ketepatan makhraj, sifat huruf, dan kaidah tajwid sesuai standar Yanbu\'a.', 'teknik' => 'O'],
                    40 => ['title' => 'Bimbingan Tahfidz (BMQ & Tasmi\')', 'desc' => 'Murojaah klasikal, setoran hafalan, dan persiapan tasmi\' beradab.', 'teknik' => 'O'],
                    41 => ['title' => 'Asesmen Formatif & Real-Time Feedback', 'desc' => 'Pengecekan pemahaman berkala dan pemberian umpan balik langsung yang membangun.', 'teknik' => 'O'],
                    42 => ['title' => 'Analisis Ketuntasan Belajar Siswa', 'desc' => 'Pengukuran ketercapaian KKTP dan diagnosis kendala belajar siswa.', 'teknik' => 'D'],
                    43 => ['title' => 'Program Intervensi Remedial & Pengayaan', 'desc' => 'Pelaksanaan bimbingan remedial bagi yang belum tuntas dan pengayaan bagi yang unggul.', 'teknik' => 'D'],
                ],
            ],
            'profesional' => [
                'code' => 'C',
                'name' => 'Profesional',
                'description' => 'Penguasaan materi bidang studi, integrasi TIK, dan pengembangan keprofesian',
                'max_score' => 30, // 5 * 6
                'count' => 5,
                'start_idx' => 44,
                'end_idx' => 48,
                'indicators' => [
                    44 => ['title' => 'Penguasaan Materi & Modul Ajar', 'desc' => 'Kedalaman dan keluasan konsep materi pelajaran serta penyusunan modul mandiri.', 'teknik' => 'D'],
                    45 => ['title' => 'Penyusunan Bank Soal & Sumber Belajar', 'desc' => 'Penyusunan bank soal terstandar HOTS dan pemanfaatan referensi mutakhir.', 'teknik' => 'D'],
                    46 => ['title' => 'Produktivitas Karya Tulis / Buku Ajar', 'desc' => 'Karya penulisan buku, modul komprehensif, diktat, atau artikel pembelajaran.', 'teknik' => 'D'],
                    47 => ['title' => 'Integrasi TIK & Platform Pembelajaran', 'desc' => 'Pemanfaatan LMS, Quizizz, Kahoot, Google Classroom, dan media presentasi interaktif.', 'teknik' => 'D'],
                    48 => ['title' => 'Pengembangan Diri Bahasa & Tahsin', 'desc' => 'Keaktifan diklat, peningkatan keterampilan bahasa asing (Inggris/Arab), dan tahsin.', 'teknik' => 'W'],
                ],
            ],
            'sosial' => [
                'code' => 'D',
                'name' => 'Sosial',
                'description' => 'Hubungan harmonis dan kolaboratif dengan rekan guru, orang tua, dan murid',
                'max_score' => 18, // 3 * 6
                'count' => 3,
                'start_idx' => 49,
                'end_idx' => 51,
                'indicators' => [
                    49 => ['title' => 'Hubungan dengan Rekan Guru', 'desc' => 'Sikap rendah hati (humble), aktif bekerja sama, memotivasi rekan sejawat, dan solid.', 'teknik' => 'W'],
                    50 => ['title' => 'Hubungan dengan Wali Murid', 'desc' => 'Cepat tanggap, komunikatif, santun, transparan, solutif, dan nihil keluhan.', 'teknik' => 'W'],
                    51 => ['title' => 'Hubungan dengan Murid', 'desc' => 'Inklusif, empatik, ramah anak, serta bebas kekerasan verbal maupun fisik.', 'teknik' => 'O'],
                ],
            ],
            'kepribadian' => [
                'code' => 'E',
                'name' => 'Kepribadian',
                'description' => 'Penampilan syar\'i rapi, tutur kata santun, sikap beradab, dan kedisiplinan kerja',
                'max_score' => 30, // 5 * 6
                'count' => 5,
                'start_idx' => 52,
                'end_idx' => 56,
                'indicators' => [
                    52 => ['title' => 'Penampilan Bersih, Rapi & Berbusana Syar\'i', 'desc' => 'Mengenakan seragam lengkap dengan atribut sekolah, bersih, rapi, dan wajah ceria.', 'teknik' => 'O'],
                    53 => ['title' => 'Komunikasi & Tutur Kata Santun', 'desc' => 'Santun, tidak menyakiti, membiasakan 3S (senyum, salam, sapa), dan tutur kata mendidik.', 'teknik' => 'O'],
                    54 => ['title' => 'Sikap, Adab & Tindakan Berakhlak', 'desc' => 'Menjaga marwah kehormatan guru dan beradab islami dalam ucapan maupun tindakan.', 'teknik' => 'O'],
                    55 => ['title' => 'Keteladanan Nyata bagi Warga Sekolah', 'desc' => 'Menjadi uswah hasanah (panutan moral) bagi murid, rekan kerja, dan wali murid.', 'teknik' => 'W'],
                    56 => ['title' => 'Kedisiplinan & Ketepatan Waktu Kerja', 'desc' => 'Ketepatan waktu hadir di sekolah, masuk dan keluar kelas tepat waktu, taat aturan.', 'teknik' => 'D'],
                ],
            ],
            'komitmen' => [
                'code' => 'F',
                'name' => 'Komitmen',
                'description' => 'Komitmen moral dan komitmen kelembagaan terhadap yayasan dan sekolah',
                'max_score' => 42, // 7 * 6
                'count' => 7,
                'start_idx' => 57,
                'end_idx' => 63,
                'indicators' => [
                    57 => ['title' => 'Memperlakukan Murid secara Unik', 'desc' => 'Menghargai potensi fitrah dan keunikan masing-masing murid tanpa diskriminasi.', 'teknik' => 'W'],
                    58 => ['title' => 'Keadilan & Tidak Diskriminatif', 'desc' => 'Bersikap adil tanpa membeda-bedakan latar belakang sosial, ekonomi, dan kemampuan.', 'teknik' => 'W'],
                    59 => ['title' => 'Transparansi & Kejujuran Penilaian Murni', 'desc' => 'Objektivitas integritas penilaian murni tanpa manipulasi atau rekayasa data.', 'teknik' => 'D'],
                    60 => ['title' => 'Keikhlasan Tugas & Integritas Akhlak', 'desc' => 'Menjalankan tugas mengajar dan mendidik dengan niat ibadah dan ketulusan hati.', 'teknik' => 'W'],
                    61 => ['title' => 'Integrasi Visi-Misi Sekolah & Yayasan', 'desc' => 'Memahami dan aktif mengejawantahkan visi-misi yayasan dalam kinerja harian.', 'teknik' => 'W'],
                    62 => ['title' => 'Kepatuhan Regulasi & Kode Etik Yayasan', 'desc' => 'Ketaatan terhadap kode etik pendidik, tata tertib, dan arahan pimpinan yayasan.', 'teknik' => 'D'],
                    63 => ['title' => 'Kesiapan Tugas Tambahan (SK & LPJ)', 'desc' => 'Kesiapan menerima tugas tambahan kepanitiaan/struktural disertai laporan tuntas.', 'teknik' => 'D'],
                ],
            ],
            'program_unggulan' => [
                'code' => 'G',
                'name' => 'Keterlibatan Program Unggulan',
                'description' => 'Partisipasi nyata dalam pembelajaran ramah anak, tahfidz, bilingual, dan adiwiyata',
                'max_score' => 24, // 4 * 6
                'count' => 4,
                'start_idx' => 64,
                'end_idx' => 67,
                'indicators' => [
                    64 => ['title' => 'Pembelajaran Bermutu & Ramah Anak', 'desc' => 'Dukungan konkret terhadap program sekolah ramah anak dan pembelajaran unggul.', 'teknik' => 'O'],
                    65 => ['title' => 'Pengembangan Program Tahsin & Tahfidz', 'desc' => 'Keterlibatan aktif menyukseskan target tahsin Yanbu\'a dan hafalan Al-Qur\'an.', 'teknik' => 'O'],
                    66 => ['title' => 'Pembiasaan Bahasa Asing (Inggris & Arab)', 'desc' => 'Mendukung gerakan bilingual dengan aktif berbicara bahasa asing di lingkungan sekolah.', 'teknik' => 'O'],
                    67 => ['title' => 'Budaya Lingkungan Bersih & Sehat', 'desc' => 'Keteladanan menjaga kebersihan, memilah sampah, dan kepedulian lingkungan asri.', 'teknik' => 'O'],
                ],
            ],
            'promosi' => [
                'code' => 'H',
                'name' => 'Peningkatan Murid Baru & Promosi',
                'description' => 'Keaktifan promosi media sosial, kepanitiaan PPDB, dan kontribusi siswa baru',
                'max_score' => 18, // 3 * 6
                'count' => 3,
                'start_idx' => 68,
                'end_idx' => 70,
                'indicators' => [
                    68 => ['title' => 'Keaktifan Promosi Media Sosial', 'desc' => 'Aktif menyukai, mengomentari, dan membagikan konten promosi sekolah di medsos.', 'teknik' => 'D'],
                    69 => ['title' => 'Kepanitiaan Sosialisasi PPDB / SPMB', 'desc' => 'Berpartisipasi aktif dalam kepanitiaan, expo, atau pembicara sosialisasi calon siswa.', 'teknik' => 'D'],
                    70 => ['title' => 'Kontribusi Membawa Calon Siswa Baru', 'desc' => 'Kontribusi nyata merekrut / membawa minimal 1 calon murid baru untuk lembaga.', 'teknik' => 'D'],
                ],
            ],
        ];
    }

    public static function determinePredicate(?float $na): string
    {
        if ($na === null) {
            return '-';
        }
        if ($na >= 96) {
            return 'Outstanding / Excellent (A+)';
        }
        if ($na >= 91) {
            return 'High / Sangat Baik (A)';
        }
        if ($na >= 86) {
            return 'Good / Baik (B+)';
        }
        if ($na >= 81) {
            return 'Aspiring / Cukup Baik (B-)';
        }
        if ($na >= 76) {
            return 'Basic / Cukup (C)';
        }
        if ($na >= 71) {
            return 'Unclassified / Kurang (D)';
        }
        return 'Need Improvement / Kurang (D)';
    }

    public static function getPredicateBadgeColor(?float $na): string
    {
        if ($na === null) {
            return 'gray';
        }
        if ($na >= 96) {
            return 'success';
        }
        if ($na >= 91) {
            return 'success';
        }
        if ($na >= 86) {
            return 'info';
        }
        if ($na >= 81) {
            return 'warning';
        }
        if ($na >= 76) {
            return 'warning';
        }
        return 'danger';
    }

    public static function calculateScoresAndPredicate(?array $komponen): array
    {
        $komponen = $komponen ?: [];
        $scores = [
            'manajerial' => 0,
            'pedagogik' => 0,
            'profesional' => 0,
            'sosial' => 0,
            'kepribadian' => 0,
            'komitmen' => 0,
            'program_unggulan' => 0,
            'promosi' => 0,
        ];

        $clusters = static::getClustersStructure();
        foreach ($clusters as $key => $cluster) {
            for ($i = $cluster['start_idx']; $i <= $cluster['end_idx']; $i++) {
                $val = isset($komponen["i{$i}"]['score'])
                    ? (int) $komponen["i{$i}"]['score']
                    : (isset($komponen[$i]['score']) ? (int) $komponen[$i]['score'] : 0);
                $scores[$key] += max(0, min(6, $val));
            }
        }

        $totalSkor = array_sum($scores);
        // Max 420
        $nilaiAkhir = $totalSkor > 0 ? round(($totalSkor / 420) * 100, 2) : 0;
        $predikat = static::determinePredicate($nilaiAkhir);

        return array_merge($scores, [
            'total_skor' => $totalSkor,
            'nilai_akhir' => $nilaiAkhir,
            'predikat' => $predikat,
        ]);
    }

    public function getClusterSummary(string $clusterKey): array
    {
        $clusters = static::getClustersStructure();
        if (!isset($clusters[$clusterKey])) {
            return ['score' => 0, 'max' => 0, 'percentage' => 0, 'profile' => '-', 'rekomendasi' => null];
        }

        $cluster = $clusters[$clusterKey];
        $max = $cluster['max_score'];
        $score = match ($clusterKey) {
            'manajerial' => $this->skor_manajerial ?? 0,
            'pedagogik' => $this->skor_pedagogik ?? 0,
            'profesional' => $this->skor_profesional ?? 0,
            'sosial' => $this->skor_sosial ?? 0,
            'kepribadian' => $this->skor_kepribadian ?? 0,
            'komitmen' => $this->skor_komitmen ?? 0,
            'program_unggulan' => $this->skor_program_unggulan ?? 0,
            'promosi' => $this->skor_promosi ?? 0,
            default => 0,
        };

        $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
        $profile = match (true) {
            $pct >= 95 => 'Amat Baik / Unggul',
            $pct >= 85 => 'Sangat Baik / Mandiri',
            $pct >= 75 => 'Baik / Sesuai Standar',
            $pct >= 65 => 'Cukup / Berkembang',
            default => 'Perlu Pembinaan & Bimbingan Khusus',
        };

        $rekomendasi = match ($clusterKey) {
            'manajerial' => $this->rekomendasi_manajerial,
            'pedagogik' => $this->rekomendasi_pedagogik,
            'profesional' => $this->rekomendasi_profesional,
            'sosial' => $this->rekomendasi_sosial,
            'kepribadian' => $this->rekomendasi_kepribadian,
            'komitmen' => $this->rekomendasi_komitmen,
            'program_unggulan' => $this->rekomendasi_program_unggulan,
            'promosi' => $this->rekomendasi_promosi,
            default => null,
        };

        return [
            'name' => $cluster['name'],
            'code' => $cluster['code'],
            'score' => $score,
            'max' => $max,
            'percentage' => $pct,
            'profile' => $profile,
            'rekomendasi' => $rekomendasi,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherCompetencyTest $ukg) {
            if (empty($ukg->tanggal)) {
                $ukg->tanggal = now()->toDateString();
            }

            if ($ukg->teacher_id && empty($ukg->institution_id)) {
                $instId = User::withoutGlobalScopes()->where('id', $ukg->teacher_id)->value('institution_id');
                if ($instId) {
                    $ukg->institution_id = $instId;
                }
            }

            if (empty($ukg->assessor_id) && auth()->check()) {
                $ukg->assessor_id = auth()->id();
            }
        });

        static::saving(function (TeacherCompetencyTest $ukg) {
            if (!empty($ukg->komponen_penilaian) && is_array($ukg->komponen_penilaian)) {
                $calc = static::calculateScoresAndPredicate($ukg->komponen_penilaian);
                $ukg->skor_manajerial = $calc['manajerial'];
                $ukg->skor_pedagogik = $calc['pedagogik'];
                $ukg->skor_profesional = $calc['profesional'];
                $ukg->skor_sosial = $calc['sosial'];
                $ukg->skor_kepribadian = $calc['kepribadian'];
                $ukg->skor_komitmen = $calc['komitmen'];
                $ukg->skor_program_unggulan = $calc['program_unggulan'];
                $ukg->skor_promosi = $calc['promosi'];
                $ukg->total_skor = $calc['total_skor'];
                $ukg->nilai_akhir = $calc['nilai_akhir'];
                $ukg->predikat = $calc['predikat'];
                $ukg->score = $calc['nilai_akhir'];

                if ($ukg->total_skor > 0 && in_array($ukg->status, ['menunggu', 'dijadwalkan', null])) {
                    $ukg->status = 'dinilai';
                }
            }
        });

        static::created(function (TeacherCompetencyTest $ukg) {
            $ukg->notifyOnCreated();
        });

        static::updated(function (TeacherCompetencyTest $ukg) {
            $ukg->notifyOnUpdated();
        });
    }

    public function notifyOnCreated(): void
    {
        $teacher = User::withoutGlobalScopes()->find($this->teacher_id);
        if (!$teacher) {
            return;
        }

        // Hanya kirim jika berstatus dijadwalkan atau ada tanggal/jadwal
        if ($this->status === 'dijadwalkan' || !empty($this->jadwal)) {
            $jadwalFormatted = $this->jadwal ? Carbon::parse($this->jadwal)->translatedFormat('d M Y, H:i') : ($this->tanggal ? Carbon::parse($this->tanggal)->translatedFormat('d M Y') : 'Segera menyusul');
            $assessorName = $this->assessor?->name ?? 'Asesor Yayasan';
            $catatanInfo = $this->catatan_yayasan ?: ($this->catatan ?: '-');

            Notification::make()
                ->title('Jadwal Uji Kompetensi Guru (UKG)')
                ->body("Agenda UKG / PKG Anda telah dijadwalkan pada {$jadwalFormatted} bersama {$assessorName}. Catatan: {$catatanInfo}")
                ->warning()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Jadwal')
                        ->url('/staff/pengajuan-ukgs'),
                ])
                ->sendToDatabase($teacher);
        }
    }

    public function notifyOnUpdated(): void
    {
        $teacher = User::withoutGlobalScopes()->find($this->teacher_id);
        if (!$teacher) {
            return;
        }

        $jadwalFormatted = $this->jadwal ? Carbon::parse($this->jadwal)->translatedFormat('d M Y, H:i') : ($this->tanggal ? Carbon::parse($this->tanggal)->translatedFormat('d M Y') : '-');

        if (($this->wasChanged('status') && $this->status === 'dijadwalkan') || ($this->wasChanged('jadwal') && $this->jadwal !== null)) {
            Notification::make()
                ->title('Jadwal UKG Ditetapkan')
                ->body("Agenda UKG Anda dijadwalkan pada {$jadwalFormatted}. Catatan: " . ($this->catatan_yayasan ?: '-'))
                ->info()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Jadwal')
                        ->url('/staff/pengajuan-ukgs'),
                ])
                ->sendToDatabase($teacher);
        } elseif (($this->wasChanged('status') && $this->status === 'dinilai') || ($this->wasChanged('nilai_akhir') && $this->nilai_akhir > 0) || ($this->wasChanged('score') && $this->score > 0)) {
            Notification::make()
                ->title('Hasil Penilaian UKG Keluar')
                ->body("Penilaian UKG Anda telah selesai dengan nilai akhir {$this->nilai_akhir} (Predikat: " . ($this->predikat ?: '-') . ").")
                ->success()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Hasil')
                        ->url('/staff/pengajuan-ukgs'),
                ])
                ->sendToDatabase($teacher);
        }
    }
}
