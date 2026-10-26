<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class TeacherSupervision extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'jadwal' => 'datetime',
        'komponen_penilaian' => 'array',
        'total_skor' => 'integer',
        'skor_perencanaan' => 'integer',
        'skor_pelaksanaan' => 'integer',
        'skor_evaluasi' => 'integer',
        'nilai_akhir' => 'float',
        'score' => 'float',
    ];

    public static function getUnitOptions(): array
    {
        return [
            'Reguler' => 'Reguler',
            'Bilingual' => 'Bilingual',
            'Takhasus' => 'Takhasus',
            'Tahsin-Tahfidz' => 'Tahsin-Tahfidz',
            'Madin' => 'Madin',
        ];
    }

    public static function getIndicatorsStructure(): array
    {
        return [
            'perencanaan' => [
                'code' => 'A',
                'name' => 'Perencanaan & Desain Pembelajaran (Modul Ajar / RPP)',
                'max_score' => 20,
                'subclusters' => [
                    'A' => [
                        'name' => 'Perencanaan & Desain Pembelajaran',
                        'indicators' => [
                            1 => [
                                'code' => 'A.1',
                                'title' => 'Analisis CP, TP, & KKTP',
                                'description' => 'Keselarasan dan keterukuran TP/KKTP dengan Capaian Pembelajaran.',
                            ],
                            2 => [
                                'code' => 'A.2',
                                'title' => 'Desain Pembelajaran Berdiferensiasi',
                                'description' => 'Variasi konten, proses, dan produk sesuai kesiapan, minat, dan profil murid.',
                            ],
                            3 => [
                                'code' => 'A.3',
                                'title' => 'Desain Pembelajaran Deep Learning',
                                'description' => 'Pemantik bernalar kritis, alur runtut (Memahami > Mengaplikasi > Merefleksi), serta media/sumber digital interaktif.',
                            ],
                            4 => [
                                'code' => 'A.4',
                                'title' => 'Integrasi Bahan Ajar & Karakter',
                                'description' => 'Transformasi nilai moral, etika, dan pembiasaan karakter.',
                            ],
                            5 => [
                                'code' => 'A.5',
                                'title' => 'Perencanaan Bahasa & Al-Qur\'an',
                                'description' => 'Penggunaan Classroom Language (Bahasa Inggris) dan pembiasaan Tahsin & Tahfidz (BMQ) terstruktur.',
                            ],
                        ],
                    ],
                ],
            ],
            'pelaksanaan' => [
                'code' => 'B',
                'name' => 'Pelaksanaan Pembelajaran',
                'max_score' => 60,
                'subclusters' => [
                    'B.1' => [
                        'name' => 'Implementasi Prinsip Deep Learning',
                        'indicators' => [
                            6 => [
                                'code' => 'Indikator 6',
                                'title' => 'Nalar Kritis & Pemecahan Masalah (Mindful)',
                                'description' => 'Pertanyaan pemantik dan keterlibatan aktif peserta didik.',
                            ],
                            7 => [
                                'code' => 'Indikator 7',
                                'title' => 'Konteks Kehidupan Nyata (Meaningful)',
                                'description' => 'Keterkaitan konsep materi dengan kehidupan nyata.',
                            ],
                            8 => [
                                'code' => 'Indikator 8',
                                'title' => 'Tahapan Belajar Runtut',
                                'description' => 'Alur runtut Memahami, Mengaplikasi, hingga Merefleksi.',
                            ],
                            9 => [
                                'code' => 'Indikator 9',
                                'title' => 'Kolaborasi & Diskusi Aktif (Joyful & Collaborative)',
                                'description' => 'Kerja sama dan komunikasi aktif antar peserta didik.',
                            ],
                            10 => [
                                'code' => 'Indikator 10',
                                'title' => 'Media & Sumber Digital Interaktif',
                                'description' => 'Pemanfaatan media/sumber belajar digital secara kreatif dan interaktif.',
                            ],
                        ],
                    ],
                    'B.2' => [
                        'name' => 'Pembelajaran Berdiferensiasi',
                        'indicators' => [
                            11 => [
                                'code' => 'Indikator 11',
                                'title' => 'Diferensiasi Konten, Proses, & Produk',
                                'description' => 'Bahan ajar bertingkat, proses berjenjang (scaffolding), dan fleksibilitas tagihan produk.',
                            ],
                            12 => [
                                'code' => 'Indikator 12',
                                'title' => 'Lingkungan Belajar',
                                'description' => 'Suasana kelas aman, fleksibel, inklusif, dan mendukung kolaborasi.',
                            ],
                        ],
                    ],
                    'B.3' => [
                        'name' => 'Internalisasi Karakter Khas LPI Baitul Makmur',
                        'indicators' => [
                            13 => [
                                'code' => 'Indikator 13',
                                'title' => 'Karakter Personal',
                                'description' => 'Disiplin, ketepatan waktu, atribut belajar, ketuntasan tugas, dan tanggung jawab.',
                            ],
                            14 => [
                                'code' => 'Indikator 14',
                                'title' => 'Karakter Pribadi',
                                'description' => 'Kejujuran, kesederhanaan, kesabaran, semangat belajar, karya nyata.',
                            ],
                            15 => [
                                'code' => 'Indikator 15',
                                'title' => 'Karakter Sosial',
                                'description' => 'Toleransi, empati, peduli, sopan santun, demokratis, kooperatif.',
                            ],
                            16 => [
                                'code' => 'Indikator 16',
                                'title' => 'Karakter Religius',
                                'description' => 'Pembiasaan sholat berjamaah khusyuk, salam/salim, adab islami, anti-bullying.',
                            ],
                        ],
                    ],
                    'B.4' => [
                        'name' => 'Pembiasaan Percakapan Berbahasa Inggris (Bilingual / ESL)',
                        'indicators' => [
                            17 => [
                                'code' => 'Indikator 17',
                                'title' => 'Penggunaan Classroom Language',
                                'description' => 'Konsistensi instruksi dan pengantar bahasa Inggris komunikatif.',
                            ],
                            18 => [
                                'code' => 'Indikator 18',
                                'title' => 'Kepercayaan Diri Berbahasa (English Exploration)',
                                'description' => 'Dorongan murid aktif berbicara dan merespons dalam bahasa Inggris.',
                            ],
                        ],
                    ],
                    'B.5' => [
                        'name' => 'Pembiasaan Mengaji, Tahsin & Tahfidz Al-Qur\'an',
                        'indicators' => [
                            19 => [
                                'code' => 'Indikator 19',
                                'title' => 'Penerapan Standar Tahsin Yanbu\'a',
                                'description' => 'Ketepatan makhraj, sifat huruf, dan tajwid sesuai standar Yanbu\'a sebelum/sesudah belajar.',
                            ],
                            20 => [
                                'code' => 'Indikator 20',
                                'title' => 'Bimbingan Tahfidz (BMQ & Tasmi\')',
                                'description' => 'Murojaah klasikal, setoran hafalan, dan persiapan tasmi\' beradab.',
                            ],
                        ],
                    ],
                ],
            ],
            'evaluasi' => [
                'code' => 'B.6',
                'name' => 'Penutup Pembelajaran / Evaluasi',
                'max_score' => 8,
                'subclusters' => [
                    'B.6' => [
                        'name' => 'Evaluasi & Ketercapaian Hasil Belajar',
                        'indicators' => [
                            21 => [
                                'code' => 'Indikator 21',
                                'title' => 'Asesmen Formatif & Umpan Balik',
                                'description' => 'Pengecekan pemahaman berkelanjutan (real-time feedback).',
                            ],
                            22 => [
                                'code' => 'Indikator 22',
                                'title' => 'Analisis Ketuntasan & Tindak Lanjut',
                                'description' => 'Pengukuran ketercapaian, pengayaan, atau program intervensi/remedial.',
                            ],
                        ],
                    ],
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
        return 'Unclassified / Kurang (D)';
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

    public static function calculateScoresFromComponents(?array $komponen): array
    {
        $komponen = $komponen ?: [];
        $skorPerencanaan = 0;
        $skorPelaksanaan = 0;
        $skorEvaluasi = 0;

        for ($i = 1; $i <= 5; $i++) {
            $val = isset($komponen["i{$i}"]['score']) ? (int) $komponen["i{$i}"]['score'] : (isset($komponen[$i]['score']) ? (int) $komponen[$i]['score'] : 0);
            $skorPerencanaan += max(0, min(4, $val));
        }

        for ($i = 6; $i <= 20; $i++) {
            $val = isset($komponen["i{$i}"]['score']) ? (int) $komponen["i{$i}"]['score'] : (isset($komponen[$i]['score']) ? (int) $komponen[$i]['score'] : 0);
            $skorPelaksanaan += max(0, min(4, $val));
        }

        for ($i = 21; $i <= 22; $i++) {
            $val = isset($komponen["i{$i}"]['score']) ? (int) $komponen["i{$i}"]['score'] : (isset($komponen[$i]['score']) ? (int) $komponen[$i]['score'] : 0);
            $skorEvaluasi += max(0, min(4, $val));
        }

        $totalSkor = $skorPerencanaan + $skorPelaksanaan + $skorEvaluasi;
        $nilaiAkhir = $totalSkor > 0 ? round(($totalSkor / 88) * 100, 2) : 0;
        $predikat = static::determinePredicate($nilaiAkhir);

        return [
            'skor_perencanaan' => $skorPerencanaan,
            'skor_pelaksanaan' => $skorPelaksanaan,
            'skor_evaluasi' => $skorEvaluasi,
            'total_skor' => $totalSkor,
            'nilai_akhir' => $nilaiAkhir,
            'predikat' => $predikat,
        ];
    }

    public function getClusterSummary(string $clusterKey): array
    {
        $structure = static::getIndicatorsStructure();
        if (!isset($structure[$clusterKey])) {
            return ['score' => 0, 'max' => 0, 'percentage' => 0, 'profile' => '-'];
        }

        $cluster = $structure[$clusterKey];
        $max = $cluster['max_score'];
        $score = match ($clusterKey) {
            'perencanaan' => $this->skor_perencanaan ?? 0,
            'pelaksanaan' => $this->skor_pelaksanaan ?? 0,
            'evaluasi' => $this->skor_evaluasi ?? 0,
            default => 0,
        };

        $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
        $profile = match (true) {
            $pct >= 95 => 'Sangat Memuaskan / Unggul',
            $pct >= 85 => 'Sangat Baik / Mandiri',
            $pct >= 75 => 'Baik / Terarah',
            $pct >= 65 => 'Cukup / Berkembang',
            default => 'Perlu Pembinaan Intensif',
        };

        return [
            'score' => $score,
            'max' => $max,
            'percentage' => $pct,
            'profile' => $profile,
            'recommendation' => match ($clusterKey) {
                'perencanaan' => $this->rekomendasi_perencanaan,
                'pelaksanaan' => $this->rekomendasi_pelaksanaan,
                'evaluasi' => $this->rekomendasi_evaluasi,
                default => null,
            },
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TeacherSupervision $supervision) {
            if (empty($supervision->tanggal)) {
                $supervision->tanggal = now()->toDateString();
            }

            if ($supervision->teacher_id) {
                $teacherInstId = User::withoutGlobalScopes()
                    ->where('id', $supervision->teacher_id)
                    ->value('institution_id');

                if ($teacherInstId) {
                    $supervision->institution_id = $teacherInstId;
                }
            }

            if (empty($supervision->jalur_supervisi)) {
                $supervision->jalur_supervisi = 'penjamin_mutu';
            }
        });

        static::saving(function (TeacherSupervision $supervision) {
            if (!empty($supervision->komponen_penilaian) && is_array($supervision->komponen_penilaian)) {
                $calc = static::calculateScoresFromComponents($supervision->komponen_penilaian);
                $supervision->skor_perencanaan = $calc['skor_perencanaan'];
                $supervision->skor_pelaksanaan = $calc['skor_pelaksanaan'];
                $supervision->skor_evaluasi = $calc['skor_evaluasi'];
                $supervision->total_skor = $calc['total_skor'];
                $supervision->nilai_akhir = $calc['nilai_akhir'];
                $supervision->predikat = $calc['predikat'];
                $supervision->score = $calc['nilai_akhir'];

                if ($supervision->total_skor > 0 && in_array($supervision->status, ['menunggu', 'dijadwalkan', null])) {
                    $supervision->status = 'dinilai';
                }
            }
        });

        static::created(function (TeacherSupervision $supervision) {
            $supervision->notifyOnCreated();
        });

        static::updated(function (TeacherSupervision $supervision) {
            $supervision->notifyOnUpdated();
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function notifyOnCreated(): void
    {
        $teacher = User::withoutGlobalScopes()->find($this->teacher_id);
        if (!$teacher) {
            return;
        }

        $instId = $this->institution_id ?: $teacher->institution_id;
        $institutionName = Institution::withoutGlobalScopes()->where('id', $instId)->value('name') ?? 'Lembaga';
        $jadwalFormatted = $this->jadwal ? Carbon::parse($this->jadwal)->translatedFormat('d M Y, H:i') : 'Segera menyusul';
        $catatanInfo = $this->catatan_yayasan ?: ($this->catatan ?: '-');

        // Kepsek & Waka Kurikulum di lembaga guru tersebut
        $schoolLeaders = User::withoutGlobalScopes()
            ->where('institution_id', $instId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['kepala_sekolah', 'waka_kurikulum']))
            ->get();

        if ($this->jalur_supervisi === 'penjamin_mutu') {
            // Jalur 1: Penjamin Mutu menjadwalkan supervisi tanpa permintaan -> Informasikan ke Guru & KS
            Notification::make()
                ->title('Jadwal Supervisi dari Penjamin Mutu')
                ->body("Tim Penjamin Mutu telah menjadwalkan supervisi untuk Anda pada {$jadwalFormatted}. Catatan: {$catatanInfo}")
                ->warning()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Jadwal Supervisi')
                        ->url('/staff/pengajuan-supervisis'),
                ])
                ->sendToDatabase($teacher);

            foreach ($schoolLeaders as $leader) {
                if ($leader->id === $teacher->id) {
                    continue;
                }
                Notification::make()
                    ->title("Informasi Supervisi Guru: {$teacher->name}")
                    ->body("Penjamin Mutu Yayasan menjadwalkan supervisi (tanpa permintaan) untuk guru {$teacher->name} ({$institutionName}) pada {$jadwalFormatted}. Catatan: {$catatanInfo}")
                    ->info()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->label('Lihat Jadwal Supervisi')
                            ->url('/staff/pengajuan-supervisis'),
                    ])
                    ->sendToDatabase($leader);
            }
        } else {
            // Jalur 2: Permintaan sendiri oleh guru -> Informasikan ke Penjamin Mutu, Ketua Yayasan, & KS
            $yayasanReviewers = User::withoutGlobalScopes()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['tim_pengembang_penjamin_mutu_yayasan', 'ketua_yayasan']))
                ->get();

            foreach ($yayasanReviewers as $reviewer) {
                Notification::make()
                    ->title("Permintaan Supervisi Baru: {$teacher->name}")
                    ->body("Guru {$teacher->name} dari {$institutionName} mengajukan permintaan supervisi mandiri. Keterangan: {$catatanInfo}")
                    ->info()
                    ->actions([
                        NotificationAction::make('jadwal')
                            ->label('Tinjau & Jadwalkan')
                            ->url('/yayasan/permintaan-supervisis?activeTab=inst_' . $instId),
                    ])
                    ->sendToDatabase($reviewer);
            }

            foreach ($schoolLeaders as $leader) {
                if ($leader->id === $teacher->id) {
                    continue;
                }
                Notification::make()
                    ->title("Pengajuan Supervisi Mandiri: {$teacher->name}")
                    ->body("Guru {$teacher->name} mengajukan permintaan supervisi mandiri. Keterangan: {$catatanInfo}")
                    ->info()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->label('Lihat Pengajuan')
                            ->url('/staff/pengajuan-supervisis'),
                    ])
                    ->sendToDatabase($leader);
            }
        }
    }

    public function notifyOnUpdated(): void
    {
        $teacher = User::withoutGlobalScopes()->find($this->teacher_id);
        if (!$teacher) {
            return;
        }

        $instId = $this->institution_id ?: $teacher->institution_id;
        $schoolLeaders = User::withoutGlobalScopes()
            ->where('institution_id', $instId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['kepala_sekolah', 'waka_kurikulum']))
            ->get();

        $jadwalFormatted = $this->jadwal ? Carbon::parse($this->jadwal)->translatedFormat('d M Y, H:i') : '-';

        if ($this->wasChanged('status') && $this->status === 'dijadwalkan') {
            Notification::make()
                ->title('Jadwal Supervisi Ditetapkan')
                ->body("Supervisi Anda telah dijadwalkan pada {$jadwalFormatted}. Catatan: " . ($this->catatan_yayasan ?: '-'))
                ->info()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Jadwal')
                        ->url('/staff/pengajuan-supervisis'),
                ])
                ->sendToDatabase($teacher);

            foreach ($schoolLeaders as $leader) {
                if ($leader->id === $teacher->id) {
                    continue;
                }
                Notification::make()
                    ->title("Jadwal Supervisi Guru: {$teacher->name}")
                    ->body("Supervisi untuk guru {$teacher->name} telah dijadwalkan pada {$jadwalFormatted}.")
                    ->info()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->label('Lihat Jadwal')
                            ->url('/staff/pengajuan-supervisis'),
                    ])
                    ->sendToDatabase($leader);
            }
        } elseif (($this->wasChanged('status') && $this->status === 'dinilai') || ($this->wasChanged('score') && $this->score !== null)) {
            Notification::make()
                ->title('Hasil Penilaian Supervisi Keluar')
                ->body("Supervisi Anda telah dinilai dengan skor {$this->score}. Hasil evaluasi: " . ($this->hasil ?: '-'))
                ->success()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Hasil')
                        ->url('/staff/pengajuan-supervisis'),
                ])
                ->sendToDatabase($teacher);

            foreach ($schoolLeaders as $leader) {
                if ($leader->id === $teacher->id) {
                    continue;
                }
                Notification::make()
                    ->title("Hasil Supervisi Guru: {$teacher->name}")
                    ->body("Guru {$teacher->name} memperoleh nilai supervisi {$this->score}. Evaluasi: " . ($this->hasil ?: '-'))
                    ->success()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->label('Lihat Hasil')
                            ->url('/staff/pengajuan-supervisis'),
                    ])
                    ->sendToDatabase($leader);
            }
        } elseif ($this->wasChanged('status') && $this->status === 'ditolak') {
            Notification::make()
                ->title('Pengajuan Supervisi Ditolak')
                ->body('Alasan: ' . ($this->catatan_yayasan ?: '-'))
                ->danger()
                ->sendToDatabase($teacher);
        }
    }
}
