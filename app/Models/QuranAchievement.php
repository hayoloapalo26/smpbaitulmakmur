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
}
