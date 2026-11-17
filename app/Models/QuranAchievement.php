<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranAchievement extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => 'array',
        'verified_at' => 'datetime',
        'scale_score' => 'integer',
        'juz' => 'integer',
        'juz_murajaah' => 'integer',
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (QuranAchievement $record) {
            $isTahsin = ($record->program === 'tahsin' || str_contains(strtolower((string)$record->program), 'tahsin'));
            if ($isTahsin) {
                $record->juz = null;
                if (empty($record->jilid_halaman) && (!empty($record->surah) || !empty($record->ayah))) {
                    $record->jilid_halaman = trim("{$record->surah} {$record->ayah}");
                }
            }

            $types = is_array($record->type) ? $record->type : ($record->type ? [$record->type] : []);
            if (!empty($record->juz) || !empty($record->surah)) {
                if (!in_array('ziyadah', $types)) {
                    $types[] = 'ziyadah';
                }
            }
            if (!empty($record->juz_murajaah) || !empty($record->surah_murajaah)) {
                if (!in_array('murajaah', $types)) {
                    $types[] = 'murajaah';
                }
            }
            if (empty($types)) {
                $types = ['ziyadah'];
            }
            $record->type = array_values(array_unique($types));
        });

        static::saved(function (QuranAchievement $record) {
            // Sinkronisasi otomatis ke reading_quality_scores agar satu pintu penilaian
            // langsung terintegrasi dengan rapor, Madin, dan profil kelulusan
            if ($record->scale_score !== null) {
                \App\Models\ReadingQualityScore::updateOrCreate(
                    [
                        'student_id' => $record->student_id,
                        'teacher_id' => $record->teacher_id,
                        'tanggal'    => $record->tanggal,
                        'program'    => $record->program ?? 'tahsin',
                    ],
                    [
                        'scale_score'   => (int) $record->scale_score,
                        'jilid_halaman' => $record->jilid_halaman,
                        'catatan_guru'  => $record->catatan,
                        'predikat'      => $record->predikat ?? Grade::determineScale($record->scale_score)['predikat'],
                    ]
                );
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getPredikatColorAttribute(): string
    {
        return match ($this->predikat) {
            'Mumtaz'        => 'success',
            'Jayyid Jiddan' => 'info',
            'Jayyid'        => 'warning',
            'Maqbul'        => 'danger',
            default         => 'gray',
        };
    }

    public function getProgramLabelAttribute(): string
    {
        return match ($this->program) {
            'yanbua'           => 'Yanbu\'a',
            'tahsin'           => 'Tahsin',
            'tahfidz_dasar'    => 'Tahfidz Dasar',
            'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
            'tahfidz'          => 'Tahfidz',
            default            => ucfirst(str_replace('_', ' ', (string)($this->program ?? 'Tahfidz'))),
        };
    }

    public function getSurahNameAttribute(): ?string
    {
        if (empty($this->surah)) return null;
        $surahs = \App\Filament\Staff\Resources\QuranAchievementResource::getSurahs();
        return $surahs[(string)$this->surah] ?? (string)$this->surah;
    }

    public function getSurahMurajaahNameAttribute(): ?string
    {
        if (empty($this->surah_murajaah)) return null;
        $surahs = \App\Filament\Staff\Resources\QuranAchievementResource::getSurahs();
        return $surahs[(string)$this->surah_murajaah] ?? (string)$this->surah_murajaah;
    }

    public function getZiyadahFormattedAttribute(): string
    {
        $isTahsin = ($this->program === 'tahsin' || str_contains(strtolower((string)$this->program), 'tahsin') || str_contains(strtolower((string)$this->surah), 'jilid'));

        if ($isTahsin) {
            $parts = [];
            if (!empty($this->surah)) {
                $parts[] = str_starts_with((string)$this->surah, 'Jilid') ? $this->surah : "Jilid {$this->surah}";
            }
            if (!empty($this->ayah)) {
                $parts[] = (str_starts_with((string)$this->ayah, 'Hal') || str_starts_with((string)$this->ayah, 'hal')) ? $this->ayah : "Hal. {$this->ayah}";
            }
            if (empty($parts) && !empty($this->jilid_halaman)) {
                $parts[] = $this->jilid_halaman;
            }
            return !empty($parts) ? implode(', ', $parts) : '-';
        }

        $parts = [];
        if ($this->juz) {
            $parts[] = "Juz {$this->juz}";
        }
        if ($this->surah_name) {
            $parts[] = "Surat {$this->surah_name}";
        }
        if ($this->ayah) {
            $parts[] = "Ayat {$this->ayah}";
        }
        if (!empty($this->jilid_halaman)) {
            $parts[] = "({$this->jilid_halaman})";
        }
        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    public function getMurajaahFormattedAttribute(): string
    {
        $isTahsin = ($this->program === 'tahsin' || str_contains(strtolower((string)$this->program), 'tahsin') || str_contains(strtolower((string)$this->surah_murajaah), 'jilid'));

        if ($isTahsin) {
            $parts = [];
            if (!empty($this->surah_murajaah)) {
                $parts[] = str_starts_with((string)$this->surah_murajaah, 'Jilid') ? $this->surah_murajaah : "Jilid {$this->surah_murajaah}";
            }
            if (!empty($this->ayah_murajaah)) {
                $parts[] = (str_starts_with((string)$this->ayah_murajaah, 'Hal') || str_starts_with((string)$this->ayah_murajaah, 'hal')) ? $this->ayah_murajaah : "Hal. {$this->ayah_murajaah}";
            }
            return !empty($parts) ? implode(', ', $parts) : '-';
        }

        $parts = [];
        if ($this->juz_murajaah) {
            $parts[] = "Juz {$this->juz_murajaah}";
        }
        if ($this->surah_murajaah_name) {
            $parts[] = "Surat {$this->surah_murajaah_name}";
        }
        if ($this->ayah_murajaah) {
            $parts[] = "Ayat {$this->ayah_murajaah}";
        }
        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    /**
     * Pilihan Jilid resmi untuk kelas Tahsin (dan Yanbu'a)
     */
    public static function getJilidOptions(): array
    {
        return [
            'Jilid 1'             => '📖 Jilid 1',
            'Jilid 2'             => '📖 Jilid 2',
            'Jilid 3'             => '📖 Jilid 3',
            'Jilid 4'             => '📖 Jilid 4',
            'Jilid 5'             => '📖 Jilid 5',
            'Jilid 6'             => '📖 Jilid 6',
            'Al-Qur\'an / Gharib' => '📖 Al-Qur\'an / Gharib',
            'Tajwid'              => '📖 Tajwid',
        ];
    }

    /**
     * Daftar jumlah total ayat untuk 114 surat dalam Al-Qur'an
     */
    public static function getSurahVerseCounts(): array
    {
        return [
            1 => 7,     2 => 286,   3 => 200,   4 => 176,   5 => 120,
            6 => 165,   7 => 206,   8 => 75,    9 => 129,   10 => 109,
            11 => 123,  12 => 111,  13 => 43,   14 => 52,   15 => 99,
            16 => 128,  17 => 111,  18 => 110,  19 => 98,   20 => 135,
            21 => 112,  22 => 78,   23 => 118,  24 => 64,   25 => 77,
            26 => 227,  27 => 93,   28 => 88,   29 => 69,   30 => 60,
            31 => 34,   32 => 30,   33 => 73,   34 => 54,   35 => 45,
            36 => 83,   37 => 182,  38 => 88,   39 => 75,   40 => 85,
            41 => 54,   42 => 53,   43 => 89,   44 => 59,   45 => 37,
            46 => 35,   47 => 38,   48 => 29,   49 => 18,   50 => 45,
            51 => 60,   52 => 49,   53 => 62,   54 => 55,   55 => 78,
            56 => 96,   57 => 29,   58 => 22,   59 => 24,   60 => 13,
            61 => 14,   62 => 11,   63 => 11,   64 => 18,   65 => 12,
            66 => 12,   67 => 30,   68 => 52,   69 => 52,   70 => 44,
            71 => 28,   72 => 28,   73 => 20,   74 => 56,   75 => 40,
            76 => 31,   77 => 50,   78 => 40,   79 => 46,   80 => 42,
            81 => 29,   82 => 19,   83 => 36,   84 => 25,   85 => 22,
            86 => 17,   87 => 19,   88 => 26,   89 => 30,   90 => 20,
            91 => 15,   92 => 21,   93 => 11,   94 => 8,    95 => 8,
            96 => 19,   97 => 5,    98 => 8,    99 => 8,    100 => 11,
            101 => 11,  102 => 8,   103 => 3,   104 => 9,   105 => 5,
            106 => 4,   107 => 7,   108 => 3,   109 => 6,   110 => 3,
            111 => 5,   112 => 4,   113 => 5,   114 => 6,
        ];
    }

    /**
     * Estimasi Juz berdasarkan nomor surat dan ayat dalam Al-Qur'an
     */
    public static function getJuzForSurah(int $surah, int $ayah = 1, int $fallbackJuz = 30): int
    {
        if ($surah >= 78) return 30;
        if ($surah >= 67) return 29;
        if ($surah >= 58) return 28;
        if ($surah >= 52 || ($surah == 51 && $ayah >= 31)) return 27;
        if ($surah >= 46) return 26;
        if ($surah >= 42 || ($surah == 41 && $ayah >= 47)) return 25;
        if ($surah >= 40 || ($surah == 39 && $ayah >= 32)) return 24;
        if ($surah >= 37 || ($surah == 36 && $ayah >= 28)) return 23;
        if ($surah >= 34 || ($surah == 33 && $ayah >= 31)) return 22;
        if ($surah >= 30 || ($surah == 29 && $ayah >= 46)) return 21;
        if ($surah >= 28 || ($surah == 27 && $ayah >= 56)) return 20;
        if ($surah >= 26 || ($surah == 25 && $ayah >= 21)) return 19;
        if ($surah >= 23) return 18;
        if ($surah >= 21) return 17;
        if ($surah >= 19 || ($surah == 18 && $ayah >= 75)) return 16;
        if ($surah >= 17) return 15;
        if ($surah >= 15) return 14;
        if ($surah >= 13 || ($surah == 12 && $ayah >= 53)) return 13;
        if ($surah >= 11 && ($ayah >= 6 || $surah >= 12)) return 12;
        if ($surah >= 10 || ($surah == 9 && $ayah >= 93)) return 11;
        if ($surah == 9 || ($surah == 8 && $ayah >= 41)) return 10;
        if ($surah == 8 || ($surah == 7 && $ayah >= 88)) return 9;
        if ($surah == 7 || ($surah == 6 && $ayah >= 111)) return 8;
        if ($surah == 6 || ($surah == 5 && $ayah >= 82)) return 7;
        if ($surah == 5 || ($surah == 4 && $ayah >= 148)) return 6;
        if ($surah == 4 && $ayah >= 24) return 5;
        if ($surah == 4 || ($surah == 3 && $ayah >= 93)) return 4;
        if ($surah == 3 || ($surah == 2 && $ayah >= 253)) return 3;
        if ($surah == 2 && $ayah >= 142) return 2;
        if ($surah <= 2) return 1;

        return $fallbackJuz;
    }

    /**
     * Hitung kelanjutan otomatis (next progress) berdasarkan setoran terakhir santri
     */
    public static function calculateNextProgress(?self $last, ?string $program = null): array
    {
        $prog = $last?->program ?: $program;
        $isTahsin = ($prog === 'tahsin' || str_contains(strtolower((string)$prog), 'tahsin'));

        if (!$last) {
            if ($isTahsin) {
                return [
                    'has_previous'         => false,
                    'last_label'           => null,
                    'juz'                  => null,
                    'surah'                => 'Jilid 1',
                    'ayah'                 => 'Hal. 1-5',
                    'juz_murajaah'         => null,
                    'surah_murajaah'       => '',
                    'ayah_murajaah'        => '',
                    'scale_score'          => 85,
                    'predikat'             => 'Jayyid Jiddan',
                    'catatan'              => '',
                ];
            }

            return [
                'has_previous'         => false,
                'last_label'           => null,
                'juz'                  => 30,
                'surah'                => '78',
                'ayah'                 => '1-10',
                'juz_murajaah'         => '',
                'surah_murajaah'       => '',
                'ayah_murajaah'        => '',
                'scale_score'          => 85,
                'predikat'             => 'Jayyid Jiddan',
                'catatan'              => '',
            ];
        }

        // ── KHUSUS TAHSIN: Berbasis Jilid & Halaman ────────────────────────────
        if ($isTahsin || str_contains(strtolower((string)$last->surah), 'jilid') || str_contains(strtolower((string)$last->ayah), 'hal')) {
            $lastSurahStr = trim((string)$last->surah);
            $lastAyahStr = trim((string)$last->ayah);

            // Ekstrak angka nomor jilid
            $jilidNum = 1;
            if (preg_match('/\d+/', $lastSurahStr, $m)) {
                $jilidNum = (int)$m[0];
            } elseif (str_contains(strtolower($lastSurahStr), 'gharib') || str_contains(strtolower($lastSurahStr), 'tajwid')) {
                $jilidNum = 6;
            }

            // Ekstrak rentang halaman
            preg_match_all('/\d+/', $lastAyahStr, $matches);
            $numbers = !empty($matches[0]) ? array_map('intval', $matches[0]) : [];
            $isAkhir = str_contains(strtolower($lastAyahStr), 'akhir') || str_contains(strtolower($lastAyahStr), 'selesai');

            $maxPagePerJilid = 40; // Batas rata-rata halaman buku jilid

            if ($isAkhir) {
                $endPage = $maxPagePerJilid;
                $span = 5;
            } elseif (count($numbers) >= 2) {
                $startPage = $numbers[0];
                $endPage = end($numbers);
                $span = max(1, $endPage - $startPage + 1);
            } elseif (count($numbers) === 1) {
                $endPage = $numbers[0];
                $span = 5;
            } else {
                $endPage = 5;
                $span = 5;
            }

            if ($endPage >= $maxPagePerJilid) {
                // Jilid selesai, naik ke jilid berikutnya
                $nextJilidNum = min(6, $jilidNum + 1);
                $nextJilid = "Jilid {$nextJilidNum}";
                if ($jilidNum >= 6) {
                    $nextJilid = "Al-Qur'an / Gharib";
                }
                $nextHalaman = "Hal. 1-{$span}";
                $lastStatusNote = ' (Khatam Jilid)';
            } else {
                $nextJilid = $lastSurahStr ?: "Jilid {$jilidNum}";
                $nextStart = $endPage + 1;
                $nextEnd = min($maxPagePerJilid, $nextStart + $span - 1);
                $nextHalaman = ($nextStart == $nextEnd) ? "Hal. {$nextStart}" : "Hal. {$nextStart}-{$nextEnd}";
                $lastStatusNote = '';
            }

            $dateStr = $last->tanggal ? \Carbon\Carbon::parse($last->tanggal)->format('d/m') : '';
            $jilidClean = $lastSurahStr ?: "Jilid {$jilidNum}";
            $lastLabel = "{$jilidClean}: {$lastAyahStr}{$lastStatusNote}" . ($dateStr ? " ({$dateStr})" : '');

            // Muraja'ah Tahsin
            $nextSurahMurajaah = $last->surah_murajaah ?: $lastSurahStr;
            $nextAyahMurajaah = $last->ayah_murajaah ?: $lastAyahStr;

            return [
                'has_previous'         => true,
                'last_label'           => $lastLabel,
                'juz'                  => null,
                'surah'                => $nextJilid,
                'ayah'                 => $nextHalaman,
                'juz_murajaah'         => null,
                'surah_murajaah'       => (string)$nextSurahMurajaah,
                'ayah_murajaah'        => (string)$nextAyahMurajaah,
                'scale_score'          => $last->scale_score ?: 85,
                'predikat'             => $last->predikat ?: 'Jayyid Jiddan',
                'catatan'              => '',
            ];
        }

        // ── KHUSUS TAHFIDZ (Surat & Ayat Al-Qur'an) ───────────────────────────
        $verseCounts = self::getSurahVerseCounts();
        $surahNames = \App\Filament\Staff\Resources\QuranAchievementResource::getSurahs();

        $lastJuz = $last->juz ?: 30;
        $lastSurah = (int)($last->surah ?: 78);
        $lastAyahStr = trim((string)$last->ayah);
        $lastSurahName = $surahNames[(string)$lastSurah] ?? "Surat {$lastSurah}";

        // Tentukan jumlah total ayat surat terakhir
        $totalVerses = $verseCounts[$lastSurah] ?? 40;

        // Ekstrak rentang ayat
        $isAkhir = str_contains(strtolower($lastAyahStr), 'akhir') || str_contains(strtolower($lastAyahStr), 'selesai');
        preg_match_all('/\d+/', $lastAyahStr, $matches);
        $numbers = !empty($matches[0]) ? array_map('intval', $matches[0]) : [];

        if ($isAkhir) {
            $endAyah = $totalVerses;
            $span = 10;
        } elseif (count($numbers) >= 2) {
            $startAyah = $numbers[0];
            $endAyah = end($numbers);
            $span = max(1, $endAyah - $startAyah + 1);
        } elseif (count($numbers) === 1) {
            $endAyah = $numbers[0];
            $span = 10;
        } else {
            $endAyah = 10;
            $span = 10;
        }

        // Tentukan Ziyadah berikutnya
        if ($endAyah >= $totalVerses) {
            // Surat terakhir sudah selesai, lanjut ke surat berikutnya
            $nextSurah = $lastSurah + 1;
            if ($nextSurah > 114) {
                $nextSurah = 1; // Kembali ke awal Al-Qur'an jika sudah khatam
            }
            $nextTotalVerses = $verseCounts[$nextSurah] ?? 40;
            $nextStart = 1;
            $nextEnd = min($nextTotalVerses, $span);
            $nextAyah = "{$nextStart}-{$nextEnd}";
            $nextJuz = self::getJuzForSurah($nextSurah, $nextStart, $lastJuz);
            $lastStatusNote = ' (Selesai)';
        } else {
            // Lanjut ayat berikutnya di surat yang sama
            $nextSurah = $lastSurah;
            $nextStart = $endAyah + 1;
            $nextEnd = min($totalVerses, $nextStart + $span - 1);
            $nextAyah = ($nextStart == $nextEnd) ? (string)$nextStart : "{$nextStart}-{$nextEnd}";
            $nextJuz = $lastJuz;
            $lastStatusNote = '';
        }

        // Label riwayat setoran terakhir untuk ditampilkan di UI
        $dateStr = $last->tanggal ? \Carbon\Carbon::parse($last->tanggal)->format('d/m') : '';
        $lastLabel = "{$lastSurahName}: {$lastAyahStr}{$lastStatusNote}" . ($dateStr ? " ({$dateStr})" : '');

        // Muraja'ah:
        // Jika sebelumnya ada muraja'ah, lanjutkan muraja'ah
        // Jika tidak, gunakan ziyadah kemarin sebagai bahan muraja'ah hari ini
        if (!empty($last->surah_murajaah)) {
            $lastMurajaahSurah = (int)$last->surah_murajaah;
            $lastMurajaahAyah = trim((string)$last->ayah_murajaah);
            $nextJuzMurajaah = $last->juz_murajaah ?: $lastJuz;
            $nextSurahMurajaah = (string)$lastMurajaahSurah;
            $nextAyahMurajaah = $lastMurajaahAyah ?: '1-Akhir';
        } else {
            // Ziyadah kemarin otomatis disarankan sebagai muraja'ah hari ini
            $nextJuzMurajaah = $lastJuz;
            $nextSurahMurajaah = (string)$lastSurah;
            $nextAyahMurajaah = $lastAyahStr ?: '1-10';
        }

        return [
            'has_previous'         => true,
            'last_label'           => $lastLabel,
            'juz'                  => $nextJuz,
            'surah'                => (string)$nextSurah,
            'ayah'                 => $nextAyah,
            'juz_murajaah'         => $nextJuzMurajaah,
            'surah_murajaah'       => $nextSurahMurajaah,
            'ayah_murajaah'        => $nextAyahMurajaah,
            'scale_score'          => $last->scale_score ?: 85,
            'predikat'             => $last->predikat ?: 'Jayyid Jiddan',
            'catatan'              => '',
        ];
    }
}
