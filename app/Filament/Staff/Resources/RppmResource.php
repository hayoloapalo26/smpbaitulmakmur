<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\RppmResource\Pages;
use App\Models\Rppm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RppmResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Role khusus Guru Al-Qur'an murni (yang tidak mengampu mapel): TIDAK PERNAH melihat RPPM di sidebar
        if ($user->isPureQuranTeacher()) {
            return false;
        }

        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Role khusus Guru Al-Qur'an murni (yang tidak mengampu mapel): TIDAK PERNAH mengakses RPPM
        if ($user->isPureQuranTeacher()) {
            return false;
        }

        // Pimpinan, kurikulum, yayasan, dan superadmin memiliki akses supervisi & monitoring penuh
        if ($user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ])) {
            return true;
        }

        // Seluruh guru pengampu mata pelajaran (guru_mapel, guru_kelas, guru_pai, wali_kelas, maupun yang terdaftar di Setting Guru Mapel / Jadwal)
        return $user->isSubjectTeacher();
    }

    protected static ?string $model = Rppm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'RPPM & Perangkat Ajar';
    protected static ?string $pluralModelLabel = 'RPPM & Perangkat Ajar';
    protected static ?int $navigationSort = 6;

    /**
     * Memastikan guru yang menginput RPPM hanya bisa melihat RPPM miliknya sendiri,
     * sedangkan Waka Kurikulum, Kepala Sekolah, dan Yayasan/Superadmin dapat melihat semua untuk supervisi & ACC.
     * Guru lain sama sekali tidak dapat melihat RPPM milik guru lain.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['guru', 'capaianPembelajaran', 'alurTujuanPembelajaran', 'reviewer']);
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Kepala sekolah, Waka Kurikulum, superadmin, dan yayasan dapat melihat seluruh RPPM di lembaganya untuk supervisi & ACC
        if ($user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ])) {
            return $query;
        }

        // Guru yang bersangkutan HANYA bisa melihat RPPM miliknya sendiri
        return $query->where('guru_id', $user->id);
    }

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Perencanaan Administrasi Pembelajaran';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'RPPM';
        }
        return 'RPPM & Perangkat Ajar';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 2;
        }
        return static::$navigationSort;
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    

    public static function canCreate(): bool
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return parent::canCreate();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return parent::canEdit($record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return parent::canDelete($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\ViewField::make('generator_rppm_banner')
                    ->view('filament.staff.components.rppm-generator-banner')
                    ->columnSpanFull(),

                Forms\Components\Section::make('📌 Catatan Supervisi dari Kepala Sekolah')
                    ->description(function (?Rppm $record) {
                        if (!$record || !$record->reviewed_at) return null;
                        $reviewer = $record->reviewer?->name ?? 'Kepala Sekolah';
                        $statusText = match ($record->status) {
                            'approved' => 'Disetujui (ACC)',
                            'need_revision' => 'Membutuhkan Perbaikan',
                            default => 'Ditinjau',
                        };
                        return "Status: {$statusText} • Ditinjau oleh {$reviewer} pada " . $record->reviewed_at->format('d M Y H:i');
                    })
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Forms\Components\Placeholder::make('catatan_kepala_sekolah_box')
                            ->label('Isi Catatan / Arahan KS:')
                            ->content(fn (?Rppm $record) => $record?->catatan_kepala_sekolah ?? 'Belum ada catatan tertulis.')
                            ->extraAttributes(function (?Rppm $record) {
                                $isRevision = ($record?->status === 'need_revision');
                                $bg = $isRevision ? 'bg-red-50 dark:bg-red-950/40 border-red-300 dark:border-red-700 text-red-900 dark:text-red-100' : 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-100';
                                return ['class' => "p-4 rounded-xl border font-medium text-sm leading-relaxed {$bg}"];
                            }),
                    ])
                    ->visible(fn (?Rppm $record) => !empty($record?->catatan_kepala_sekolah) || $record?->status === 'need_revision' || $record?->status === 'approved')
                    ->collapsible()
                    ->columnSpanFull(),

                Forms\Components\Section::make('Informasi Dasar & Input Perangkat Ajar (CP, TP, ATP)')
                    ->description('Input atau pilih Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) yang berlaku 1 semester hingga 1 tahun ajaran.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => auth()->id()),

                        // Tombol Aksi Input & Simpan Langsung (Dengan Tombol Submit & Modal)
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('open_generator_rppm')
                                ->label('🚀 Buka Generator Pembuatan RPPM')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->color('success')
                                ->button()
                                ->url('https://gemini.google.com/share/d/1e2rmqWovfw8uUldDF8Ql-uBCv6_Z1_Nv?usp=sharing', shouldOpenInNewTab: true),

                            Forms\Components\Actions\Action::make('modal_create_cp')
                                ->label('+ Input CP Baru')
                                ->icon('heroicon-o-plus-circle')
                                ->color('primary')
                                ->button()
                                ->modalHeading('Input Capaian Pembelajaran (CP) Baru')
                                ->modalDescription('Capaian Pembelajaran (CP) yang disimpan akan berlaku 1 semester hingga 1 tahun dan langsung muncul pada pilihan CP.')
                                ->modalSubmitActionLabel('Simpan Capaian Pembelajaran (CP)')
                                ->modalWidth('2xl')
                                ->form([
                                    Forms\Components\Select::make('modal_subject_id')
                                        ->label('Mata Pelajaran')
                                        ->options(function (Forms\Get $get) {
                                            $user = auth()->user();
                                            if (!$user) return [];

                                            $isPrivileged = $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            if ($isPrivileged) {
                                                return \App\Models\Subject::akademik()->orderBy('name')->pluck('name', 'id');
                                            }

                                            $subjectIds = $user->getTeachedSubjectIds();
                                            if (!empty($subjectIds)) {
                                                $assigned = \App\Models\Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                                                if ($assigned->isNotEmpty()) {
                                                    return $assigned;
                                                }
                                            }

                                            return \App\Models\Subject::akademik()->orderBy('name')->pluck('name', 'id');
                                        })
                                        ->default(function (Forms\Get $get) {
                                            $selectedMapel = $get('mata_pelajaran');
                                            if ($selectedMapel) {
                                                $subj = \App\Models\Subject::where('name', $selectedMapel)->first();
                                                if ($subj) return $subj->id;
                                            }
                                            $user = auth()->user();
                                            $subjectIds = $user ? $user->getTeachedSubjectIds() : [];
                                            if (!empty($subjectIds)) {
                                                return $subjectIds[0];
                                            }
                                            return \App\Models\Subject::akademik()->first()?->id;
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('modal_kode_cp')
                                            ->label('Kode CP')
                                            ->placeholder('1, 2, dst. (Otomatis)')
                                            ->helperText('Contoh: 1 untuk CP 1'),
                                        Forms\Components\Select::make('modal_tingkat')
                                            ->label('Tingkat Kelas')
                                            ->options(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'])
                                            ->default('7')
                                            ->required(),
                                        Forms\Components\Select::make('modal_masa_berlaku')
                                            ->label('Masa Berlaku')
                                            ->options([
                                                '1 Tahun'    => '1 Tahun Ajaran (2 Semester)',
                                                '1 Semester' => '1 Semester',
                                            ])
                                            ->default('1 Tahun')
                                            ->required(),
                                    ]),

                                    Forms\Components\TextInput::make('modal_elemen')
                                        ->label('Elemen / Lingkup Materi CP')
                                        ->placeholder('Contoh: Bilangan, Aljabar, Geometri, Pengukuran')
                                        ->required(),

                                    Forms\Components\Textarea::make('modal_deskripsi_cp')
                                        ->label('Capaian Pembelajaran (CP)')
                                        ->placeholder('Contoh: Peserta didik mampu memahami konsep bilangan bulat dan operasi aljabar...')
                                        ->rows(4)
                                        ->required()
                                        ->helperText('Capaian Pembelajaran berlaku antara 1 semester hingga 1 tahun pembelajaran.'),
                                ])
                                ->action(function (array $data, Forms\Set $set) {
                                    $cp = \App\Models\CapaianPembelajaran::create([
                                        'institution_id' => auth()->user()?->institution_id,
                                        'guru_id' => auth()->id(),
                                        'subject_id' => $data['modal_subject_id'],
                                        'kode_cp' => !empty($data['modal_kode_cp']) ? (string) $data['modal_kode_cp'] : null,
                                        'fase' => 'Fase D (SMP)',
                                        'tingkat' => $data['modal_tingkat'],
                                        'tahun_ajaran' => '2026/2027',
                                        'semester' => '1',
                                        'masa_berlaku' => $data['modal_masa_berlaku'],
                                        'elemen' => $data['modal_elemen'],
                                        'deskripsi_cp' => $data['modal_deskripsi_cp'],
                                    ]);

                                    $set('capaian_pembelajaran_id', $cp->id);
                                    $set('content.capaian_pembelajaran_id', $cp->id);
                                    $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                    $set('kelas_fase', "Kelas {$cp->tingkat} / Fase D");
                                    if ($cp->subject) {
                                        $set('mata_pelajaran', $cp->subject->name);
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Capaian Pembelajaran (CP) Berhasil Disimpan!')
                                        ->body("CP elemen '{$cp->elemen}' tersimpan dan otomatis terpilih.")
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('modal_edit_cp')
                                ->label('✏️ Edit CP')
                                ->icon('heroicon-o-pencil-square')
                                ->color('warning')
                                ->button()
                                ->modalHeading('Edit Capaian Pembelajaran (CP)')
                                ->modalDescription('Pilih CP yang ingin diubah, perbarui isinya, lalu simpan perubahan.')
                                ->modalSubmitActionLabel('Simpan Perubahan CP')
                                ->modalWidth('2xl')
                                ->fillForm(function (Forms\Get $get): array {
                                    $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                    $cp = $cpId ? \App\Models\CapaianPembelajaran::find($cpId) : null;
                                    if (!$cp) {
                                        $selectedMapel = $get('mata_pelajaran');
                                        $q = \App\Models\CapaianPembelajaran::query();
                                        if ($selectedMapel) {
                                            $q->whereHas('subject', fn($sq) => $sq->where('name', $selectedMapel));
                                        } else {
                                            $user = auth()->user();
                                            $sIds = $user ? $user->getTeachedSubjectIds() : [];
                                            if (!empty($sIds)) $q->whereIn('subject_id', $sIds);
                                        }
                                        $cp = $q->first();
                                    }
                                    if ($cp) {
                                        return [
                                            'edit_cp_id' => $cp->id,
                                            'edit_kode_cp' => $cp->kode_cp,
                                            'edit_tingkat' => $cp->tingkat,
                                            'edit_masa_berlaku' => $cp->masa_berlaku,
                                            'edit_elemen' => $cp->elemen,
                                            'edit_deskripsi_cp' => $cp->deskripsi_cp,
                                        ];
                                    }
                                    return [];
                                })
                                ->form([
                                    Forms\Components\Select::make('edit_cp_id')
                                        ->label('Pilih CP yang Ingin Diedit')
                                        ->options(function (Forms\Get $get) {
                                            $user = auth()->user();
                                            $isPrivileged = $user && $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            $query = \App\Models\CapaianPembelajaran::with('subject');
                                            $selectedMapel = $get('mata_pelajaran');

                                            if ($selectedMapel) {
                                                $query->whereHas('subject', fn($q) => $q->where('name', $selectedMapel));
                                            } elseif (!$isPrivileged && $user) {
                                                $subjectIds = $user->getTeachedSubjectIds();
                                                if (!empty($subjectIds)) {
                                                    $query->whereIn('subject_id', $subjectIds);
                                                }
                                            }

                                            return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                                $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                                return [$cp->id => "[{$cpKode}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 65)];
                                            });
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $cp = \App\Models\CapaianPembelajaran::find($state);
                                                if ($cp) {
                                                    $set('edit_kode_cp', $cp->kode_cp);
                                                    $set('edit_tingkat', $cp->tingkat);
                                                    $set('edit_masa_berlaku', $cp->masa_berlaku);
                                                    $set('edit_elemen', $cp->elemen);
                                                    $set('edit_deskripsi_cp', $cp->deskripsi_cp);
                                                }
                                            }
                                        }),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\TextInput::make('edit_kode_cp')
                                            ->label('Kode CP')
                                            ->required(),
                                        Forms\Components\Select::make('edit_tingkat')
                                            ->label('Tingkat Kelas')
                                            ->options(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'])
                                            ->required(),
                                        Forms\Components\Select::make('edit_masa_berlaku')
                                            ->label('Masa Berlaku')
                                            ->options([
                                                '1 Tahun'    => '1 Tahun Ajaran (2 Semester)',
                                                '1 Semester' => '1 Semester',
                                            ])
                                            ->required(),
                                    ]),

                                    Forms\Components\TextInput::make('edit_elemen')
                                        ->label('Elemen / Lingkup Materi CP')
                                        ->required(),

                                    Forms\Components\Textarea::make('edit_deskripsi_cp')
                                        ->label('Capaian Pembelajaran (CP)')
                                        ->rows(4)
                                        ->required(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get) {
                                    $cp = \App\Models\CapaianPembelajaran::find($data['edit_cp_id'] ?? null);
                                    if ($cp) {
                                        $cp->update([
                                            'kode_cp' => !empty($data['edit_kode_cp']) ? (string) $data['edit_kode_cp'] : $cp->kode_cp,
                                            'tingkat' => $data['edit_tingkat'] ?? $cp->tingkat,
                                            'masa_berlaku' => $data['edit_masa_berlaku'] ?? $cp->masa_berlaku,
                                            'elemen' => $data['edit_elemen'] ?? $cp->elemen,
                                            'deskripsi_cp' => $data['edit_deskripsi_cp'] ?? $cp->deskripsi_cp,
                                        ]);

                                        $currentCpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                        if ($currentCpId == $cp->id) {
                                            $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                            $set('kelas_fase', "Kelas {$cp->tingkat} / Fase D");
                                        }

                                        \Filament\Notifications\Notification::make()
                                            ->title('Capaian Pembelajaran (CP) Berhasil Diperbarui!')
                                            ->body("Perubahan CP '{$cp->elemen}' tersimpan dengan sukses.")
                                            ->success()
                                            ->send();
                                    }
                                }),

                            Forms\Components\Actions\Action::make('modal_delete_cp')
                                ->label('🗑️ Hapus CP')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->button()
                                ->modalHeading('Hapus Capaian Pembelajaran (CP)')
                                ->modalDescription('Pilih CP yang ingin dihapus. Pastikan tidak ada data pembelajaran aktif yang terganggu.')
                                ->modalSubmitActionLabel('Ya, Hapus CP')
                                ->modalWidth('lg')
                                ->fillForm(function (Forms\Get $get): array {
                                    $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                    return ['delete_cp_id' => $cpId];
                                })
                                ->form([
                                    Forms\Components\Select::make('delete_cp_id')
                                        ->label('Pilih CP yang Ingin Dihapus')
                                        ->options(function (Forms\Get $get) {
                                            $user = auth()->user();
                                            $isPrivileged = $user && $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            $query = \App\Models\CapaianPembelajaran::with('subject');
                                            $selectedMapel = $get('mata_pelajaran');

                                            if ($selectedMapel) {
                                                $query->whereHas('subject', fn($q) => $q->where('name', $selectedMapel));
                                            } elseif (!$isPrivileged && $user) {
                                                $subjectIds = $user->getTeachedSubjectIds();
                                                if (!empty($subjectIds)) {
                                                    $query->whereIn('subject_id', $subjectIds);
                                                }
                                            }

                                            return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                                $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                                return [$cp->id => "[{$cpKode}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 65)];
                                            });
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\Checkbox::make('confirm_delete')
                                        ->label('Saya yakin ingin menghapus Capaian Pembelajaran ini secara permanen.')
                                        ->required()
                                        ->accepted(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get) {
                                    $cp = \App\Models\CapaianPembelajaran::find($data['delete_cp_id'] ?? null);
                                    if ($cp) {
                                        $elemen = $cp->elemen;
                                        $cpId = $cp->id;
                                        $currentCpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                        if ($currentCpId == $cpId) {
                                            $set('capaian_pembelajaran_id', null);
                                            $set('content.capaian_pembelajaran_id', null);
                                            $set('content.capaian_pembelajaran', null);
                                        }
                                        $cp->delete();

                                        \Filament\Notifications\Notification::make()
                                            ->title('Capaian Pembelajaran (CP) Berhasil Dihapus!')
                                            ->body("CP '{$elemen}' telah berhasil dihapus.")
                                            ->success()
                                            ->send();
                                    }
                                }),

                            Forms\Components\Actions\Action::make('modal_create_tp')
                                ->label('+ Input TP Baru')
                                ->icon('heroicon-o-plus-circle')
                                ->color('success')
                                ->button()
                                ->modalHeading('Input Tujuan Pembelajaran (TP) Baru')
                                ->modalDescription('Pilih Capaian Pembelajaran (CP) dan tentukan Tujuan Pembelajaran yang ingin dicapai.')
                                ->modalSubmitActionLabel('Simpan Tujuan Pembelajaran (TP)')
                                ->modalWidth('2xl')
                                ->form([
                                    Forms\Components\Select::make('modal_cp_id')
                                        ->label('Rujukan Capaian Pembelajaran (CP)')
                                        ->options(function (Forms\Get $get) {
                                            $user = auth()->user();
                                            $isPrivileged = $user && $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            $query = \App\Models\CapaianPembelajaran::with('subject');
                                            $selectedMapel = $get('mata_pelajaran');

                                            if ($selectedMapel) {
                                                $query->whereHas('subject', fn($q) => $q->where('name', $selectedMapel));
                                            } elseif (!$isPrivileged && $user) {
                                                $subjectIds = $user->getTeachedSubjectIds();
                                                if (!empty($subjectIds)) {
                                                    $query->whereIn('subject_id', $subjectIds);
                                                }
                                            }

                                            return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                                $mapel = $cp->subject?->name ?? 'Mapel';
                                                $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                                return [$cp->id => "[{$cpKode}] [{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 55)];
                                            });
                                        })
                                        ->default(fn (Forms\Get $get) => $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Pilih Capaian Pembelajaran (CP) dari mata pelajaran yang Anda ampu sebagai rujukan Tujuan Pembelajaran ini.'),

                                    Forms\Components\TextInput::make('modal_materi')
                                        ->label('Lingkup Materi Pokok (Opsional)')
                                        ->placeholder('Contoh: Operasi Bilangan Bulat, Aljabar, dll.'),

                                    Forms\Components\Textarea::make('modal_deskripsi_tp')
                                        ->label('Tujuan Pembelajaran (TP)')
                                        ->placeholder('Contoh: Peserta didik dapat memahami dan menyelesaikan operasi hitung pecahan aljabar...')
                                        ->rows(4)
                                        ->required()
                                        ->helperText('Tujuan Pembelajaran yang diturunkan langsung dari CP yang dipilih.'),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get) {
                                    $cp = \App\Models\CapaianPembelajaran::find($data['modal_cp_id']);
                                    $cpKode = $cp?->kode_cp ?: '1';
                                    $count = \App\Models\TujuanPembelajaran::where('capaian_pembelajaran_id', $data['modal_cp_id'])->count() + 1;
                                    $tingkat = $cp?->tingkat ?? '7';
                                    $kodeTp = "{$cpKode}.{$count}";

                                    $tp = \App\Models\TujuanPembelajaran::create([
                                        'institution_id' => auth()->user()?->institution_id,
                                        'guru_id' => auth()->id(),
                                        'capaian_pembelajaran_id' => $data['modal_cp_id'],
                                        'subject_id' => $cp?->subject_id ?? \App\Models\Subject::first()?->id,
                                        'kode_tp' => $kodeTp,
                                        'deskripsi_tp' => $data['modal_deskripsi_tp'],
                                        'materi' => $data['modal_materi'] ?? null,
                                        'kelas_tingkat' => $tingkat,
                                        'semester' => 'Ganjil',
                                    ]);

                                    $currentSelected = $get('selected_tp_ids') ?? [];
                                    if (!is_array($currentSelected)) {
                                        $currentSelected = [];
                                    }
                                    $currentSelected[] = $tp->id;
                                    $currentSelected = array_values(array_unique($currentSelected));

                                    $set('selected_tp_ids', $currentSelected);
                                    $set('content.selected_tp_dropdown', $currentSelected);

                                    $tps = \App\Models\TujuanPembelajaran::whereIn('id', $currentSelected)->get()->sort(function ($a, $b) {
                                        return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                    });
                                    $tpItems = $tps->map(function ($item) {
                                        $prefix = $item->materi ? "({$item->materi}) " : '';
                                        return ['rumusan' => "[{$item->kode_tp}] {$prefix}{$item->deskripsi_tp}"];
                                    })->values()->toArray();
                                    $set('content.tujuan_pembelajaran', $tpItems);

                                    // Otomatis akumulasikan dan sinkronisasi ATP untuk guru & mapel ini
                                    $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                        auth()->id(),
                                        $tp->subject_id,
                                        $tp->kelas_tingkat ?? '7'
                                    );
                                    if ($atp) {
                                        $set('alur_tujuan_pembelajaran_id', $atp->id);
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Tujuan Pembelajaran (TP) Berhasil Disimpan!')
                                        ->body('Tujuan Pembelajaran telah tersimpan dan Alur Tujuan Pembelajaran (ATP) otomatis terakumulasi.')
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('modal_edit_tp')
                                ->label('✏️ Edit TP')
                                ->icon('heroicon-o-pencil-square')
                                ->color('warning')
                                ->button()
                                ->modalHeading('Edit Tujuan Pembelajaran (TP)')
                                ->modalDescription('Pilih TP yang ingin diedit, perbarui isinya, lalu simpan perubahan.')
                                ->modalSubmitActionLabel('Simpan Perubahan TP')
                                ->modalWidth('2xl')
                                ->fillForm(function (Forms\Get $get): array {
                                    $selectedTpIds = $get('selected_tp_ids') ?? $get('content.selected_tp_dropdown');
                                    $firstTpId = is_array($selectedTpIds) && !empty($selectedTpIds) ? reset($selectedTpIds) : null;
                                    $tp = $firstTpId ? \App\Models\TujuanPembelajaran::find($firstTpId) : null;
                                    if (!$tp) {
                                        $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                        $q = \App\Models\TujuanPembelajaran::query();
                                        if ($cpId) {
                                            $q->where('capaian_pembelajaran_id', $cpId);
                                        } else {
                                            $selectedMapel = $get('mata_pelajaran');
                                            if ($selectedMapel) {
                                                $q->whereHas('subject', fn($sq) => $sq->where('name', $selectedMapel));
                                            }
                                        }
                                        $tp = $q->first();
                                    }
                                    if ($tp) {
                                        return [
                                            'edit_tp_id' => $tp->id,
                                            'edit_kode_tp' => $tp->kode_tp,
                                            'edit_materi' => $tp->materi,
                                            'edit_deskripsi_tp' => $tp->deskripsi_tp,
                                        ];
                                    }
                                    return [];
                                })
                                ->form([
                                    Forms\Components\Select::make('edit_tp_id')
                                        ->label('Pilih TP yang Ingin Diedit')
                                        ->options(function (Forms\Get $get) {
                                            $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                            $selectedMapel = $get('mata_pelajaran');
                                            $user = auth()->user();
                                            $isPrivileged = $user && $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            $query = \App\Models\TujuanPembelajaran::with(['capaianPembelajaran', 'subject']);
                                            if ($cpId && is_numeric($cpId)) {
                                                $query->where('capaian_pembelajaran_id', $cpId);
                                            } elseif ($selectedMapel) {
                                                $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                            } elseif (!$isPrivileged && $user) {
                                                $subjectIds = $user->getTeachedSubjectIds();
                                                if (!empty($subjectIds)) {
                                                    $query->whereIn('subject_id', $subjectIds);
                                                } else {
                                                    return [];
                                                }
                                            }

                                            return $query->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            })->mapWithKeys(function ($tp) {
                                                $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                                return [$tp->id => "[TP {$tp->kode_tp}] {$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 70)];
                                            });
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $tp = \App\Models\TujuanPembelajaran::find($state);
                                                if ($tp) {
                                                    $set('edit_kode_tp', $tp->kode_tp);
                                                    $set('edit_materi', $tp->materi);
                                                    $set('edit_deskripsi_tp', $tp->deskripsi_tp);
                                                }
                                            }
                                        }),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('edit_kode_tp')
                                            ->label('Kode TP')
                                            ->placeholder('Contoh: 1.1, 1.2')
                                            ->required(),

                                        Forms\Components\TextInput::make('edit_materi')
                                            ->label('Materi Pokok / Bahasan')
                                            ->placeholder('Contoh: Aljabar, Bilangan Bulat'),
                                    ]),

                                    Forms\Components\Textarea::make('edit_deskripsi_tp')
                                        ->label('Tujuan Pembelajaran (TP)')
                                        ->rows(4)
                                        ->required(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get) {
                                    $tp = \App\Models\TujuanPembelajaran::find($data['edit_tp_id'] ?? null);
                                    if ($tp) {
                                        $tp->update([
                                            'kode_tp' => !empty($data['edit_kode_tp']) ? (string) $data['edit_kode_tp'] : $tp->kode_tp,
                                            'materi' => $data['edit_materi'] ?? $tp->materi,
                                            'deskripsi_tp' => $data['edit_deskripsi_tp'] ?? $tp->deskripsi_tp,
                                        ]);

                                        $currentSelected = $get('selected_tp_ids') ?? [];
                                        if (!empty($currentSelected)) {
                                            $tps = \App\Models\TujuanPembelajaran::whereIn('id', $currentSelected)->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            });
                                            $tpItems = $tps->map(function ($item) {
                                                $prefix = $item->materi ? "({$item->materi}) " : '';
                                                return ['rumusan' => "[{$item->kode_tp}] {$prefix}{$item->deskripsi_tp}"];
                                            })->values()->toArray();
                                            $set('content.tujuan_pembelajaran', $tpItems);
                                        }

                                        \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                            auth()->id(),
                                            $tp->subject_id,
                                            $tp->kelas_tingkat ?? '7'
                                        );

                                        \Filament\Notifications\Notification::make()
                                            ->title('Tujuan Pembelajaran (TP) Berhasil Diperbarui!')
                                            ->body("Perubahan pada TP '{$tp->kode_tp}' tersimpan dengan sukses.")
                                            ->success()
                                            ->send();
                                    }
                                }),

                            Forms\Components\Actions\Action::make('modal_delete_tp')
                                ->label('🗑️ Hapus TP')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->button()
                                ->modalHeading('Hapus Tujuan Pembelajaran (TP)')
                                ->modalDescription('Pilih TP yang ingin dihapus. TP yang dihapus akan otomatis dikeluarkan dari daftar pilihan RPPM.')
                                ->modalSubmitActionLabel('Ya, Hapus TP')
                                ->modalWidth('lg')
                                ->fillForm(function (Forms\Get $get): array {
                                    $selectedTpIds = $get('selected_tp_ids') ?? $get('content.selected_tp_dropdown');
                                    $firstTpId = is_array($selectedTpIds) && !empty($selectedTpIds) ? reset($selectedTpIds) : null;
                                    return ['delete_tp_id' => $firstTpId];
                                })
                                ->form([
                                    Forms\Components\Select::make('delete_tp_id')
                                        ->label('Pilih TP yang Ingin Dihapus')
                                        ->options(function (Forms\Get $get) {
                                            $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                            $selectedMapel = $get('mata_pelajaran');
                                            $user = auth()->user();
                                            $isPrivileged = $user && $user->hasAnyRole([
                                                'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                                'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                            ]);

                                            $query = \App\Models\TujuanPembelajaran::with(['capaianPembelajaran', 'subject']);
                                            if ($cpId && is_numeric($cpId)) {
                                                $query->where('capaian_pembelajaran_id', $cpId);
                                            } elseif ($selectedMapel) {
                                                $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                            } elseif (!$isPrivileged && $user) {
                                                $subjectIds = $user->getTeachedSubjectIds();
                                                if (!empty($subjectIds)) {
                                                    $query->whereIn('subject_id', $subjectIds);
                                                } else {
                                                    return [];
                                                }
                                            }

                                            return $query->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            })->mapWithKeys(function ($tp) {
                                                $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                                return [$tp->id => "[TP {$tp->kode_tp}] {$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 70)];
                                            });
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\Checkbox::make('confirm_delete')
                                        ->label('Saya yakin ingin menghapus Tujuan Pembelajaran ini secara permanen.')
                                        ->required()
                                        ->accepted(),
                                ])
                                ->action(function (array $data, Forms\Set $set, Forms\Get $get) {
                                    $tp = \App\Models\TujuanPembelajaran::find($data['delete_tp_id'] ?? null);
                                    if ($tp) {
                                        $kodeTp = $tp->kode_tp;
                                        $tpId = $tp->id;
                                        $subjectId = $tp->subject_id;
                                        $tingkat = $tp->kelas_tingkat ?? '7';

                                        $currentSelected = $get('selected_tp_ids') ?? [];
                                        if (is_array($currentSelected)) {
                                            $currentSelected = array_values(array_filter($currentSelected, fn($id) => $id != $tpId));
                                            $set('selected_tp_ids', $currentSelected);
                                            $set('content.selected_tp_dropdown', $currentSelected);

                                            $tps = \App\Models\TujuanPembelajaran::whereIn('id', $currentSelected)->where('id', '!=', $tpId)->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            });
                                            $tpItems = $tps->map(function ($item) {
                                                $prefix = $item->materi ? "({$item->materi}) " : '';
                                                return ['rumusan' => "[{$item->kode_tp}] {$prefix}{$item->deskripsi_tp}"];
                                            })->values()->toArray();
                                            $set('content.tujuan_pembelajaran', $tpItems);
                                        }

                                        $tp->delete();

                                        \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                            auth()->id(),
                                            $subjectId,
                                            $tingkat
                                        );

                                        \Filament\Notifications\Notification::make()
                                            ->title('Tujuan Pembelajaran (TP) Berhasil Dihapus!')
                                            ->body("TP '{$kodeTp}' telah berhasil dihapus.")
                                            ->success()
                                            ->send();
                                    }
                                }),
                        ])
                        ->columnSpanFull(),

                        // 1. Pilihan Mata Pelajaran & Kelas/Fase (Diletakkan di Awal untuk Memfilter CP & TP)
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('mata_pelajaran')
                                ->label('Mata Pelajaran yang Diampu')
                                ->options(function () {
                                    $user = auth()->user();
                                    if (!$user) return [];

                                    $isPrivileged = $user->hasAnyRole([
                                        'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                        'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                        'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                    ]);

                                    if ($isPrivileged) {
                                        return \App\Models\Subject::akademik()->orderBy('name')->pluck('name', 'name');
                                    }

                                    $subjectIds = $user->getTeachedSubjectIds();
                                    if (!empty($subjectIds)) {
                                        $assigned = \App\Models\Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'name');
                                        if ($assigned->isNotEmpty()) {
                                            return $assigned;
                                        }
                                    }

                                    return \App\Models\Subject::akademik()->orderBy('name')->pluck('name', 'name');
                                })
                                ->default(function () {
                                    $user = auth()->user();
                                    if (!$user) return null;
                                    $subjectIds = $user->getTeachedSubjectIds();
                                    if (!empty($subjectIds)) {
                                        $first = \App\Models\Subject::whereIn('id', $subjectIds)->first();
                                        if ($first) {
                                            return $first->name;
                                        }
                                    }
                                    // Cek apakah jabatan mengandung mapel tertentu (misal: PAI / Agama)
                                    $jabatan = strtolower($user->jabatan ?? '');
                                    if (str_contains($jabatan, 'pai') || str_contains($jabatan, 'agama')) {
                                        $pai = \App\Models\Subject::where('name', 'like', '%Pendidikan Agama Islam%')->orWhere('name', 'like', '%PAI%')->first();
                                        if ($pai) return $pai->name;
                                    }
                                    return null;
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Reset pilihan CP, ATP, dan TP saat ganti mapel agar tidak ada kebocoran antar mapel
                                    $set('capaian_pembelajaran_id', null);
                                    $set('content.capaian_pembelajaran_id', null);
                                    $set('content.capaian_pembelajaran', null);
                                    $set('alur_tujuan_pembelajaran_id', null);
                                    $set('content.alur_tujuan_pembelajaran_id', null);
                                    $set('selected_tp_ids', []);
                                    $set('content.selected_tp_dropdown', []);
                                    $set('content.tujuan_pembelajaran', []);
                                })
                                ->helperText('Mata pelajaran yang Anda ampu. Pilihan CP dan TP akan disesuaikan otomatis.'),

                            Forms\Components\TextInput::make('kelas_fase')
                                ->label('Kelas / Fase')
                                ->placeholder('Contoh: VII / Fase D')
                                ->default('Kelas 7 / Fase D')
                                ->required(),
                        ])->columnSpanFull(),

                        // Menu Dropdown Capaian Pembelajaran & Alur Tujuan Pembelajaran Otomatis
                        Forms\Components\Select::make('capaian_pembelajaran_id')
                            ->label('1. Capaian Pembelajaran (CP)')
                            ->options(function (Forms\Get $get) {
                                $user = auth()->user();
                                if (!$user) return [];

                                $isPrivileged = $user->hasAnyRole([
                                    'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                    'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                    'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                ]);

                                $query = \App\Models\CapaianPembelajaran::with('subject');
                                $selectedMapel = $get('mata_pelajaran');

                                if ($selectedMapel) {
                                    $query->whereHas('subject', fn($q) => $q->where('name', $selectedMapel));
                                } elseif (!$isPrivileged) {
                                    $subjectIds = $user->getTeachedSubjectIds();
                                    if (!empty($subjectIds)) {
                                        $query->whereIn('subject_id', $subjectIds);
                                    } else {
                                        return [];
                                    }
                                }

                                return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                    $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                    return [$cp->id => "[{$cpKode}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 75)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                    if ($cp) {
                                        $set('content.capaian_pembelajaran_id', $cp->id);
                                        $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                        $set('kelas_fase', "Kelas {$cp->tingkat} / Fase D");
                                        if ($cp->subject && !$get('mata_pelajaran')) {
                                            $set('mata_pelajaran', $cp->subject->name);
                                        }

                                        // Otomatis sinkronisasi ATP sesuai CP & mapel ini
                                        $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                            auth()->id(),
                                            $cp->subject_id,
                                            $cp->tingkat,
                                            '2026/2027',
                                            'Ganjil',
                                            $cp->id
                                        );
                                        if ($atp && $get('alur_tujuan_pembelajaran_id') != $atp->id) {
                                            $set('alur_tujuan_pembelajaran_id', $atp->id);
                                            $set('content.alur_tujuan_pembelajaran_id', $atp->id);

                                            // Otomatis pre-select TP dari CP dan ATP ini
                                            if (!empty($atp->tp_ids)) {
                                                $set('selected_tp_ids', $atp->tp_ids);
                                                $set('content.selected_tp_dropdown', $atp->tp_ids);
                                                $tps = \App\Models\TujuanPembelajaran::whereIn('id', $atp->tp_ids)->get()->sort(function ($a, $b) {
                                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                                });
                                                $tpItems = $tps->map(function ($tp) {
                                                    $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                                    return ['rumusan' => "[{$tp->kode_tp}] {$prefix}{$tp->deskripsi_tp}"];
                                                })->values()->toArray();
                                                $set('content.tujuan_pembelajaran', $tpItems);
                                            }
                                        }
                                    }
                                }
                            })
                            ->helperText('Pilih CP yang telah disimpan atau gunakan tombol di atas untuk menambah, mengedit, atau menghapus CP.'),

                        Forms\Components\Select::make('alur_tujuan_pembelajaran_id')
                            ->label('2. Alur Tujuan Pembelajaran (ATP) [Otomatis Sesuai CP & ATP Terkait]')
                            ->options(function (Forms\Get $get) {
                                $guruId = auth()->id();
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $selectedMapel = $get('mata_pelajaran');
                                $subjectId = null;
                                $tingkat = '7';
                                if ($cpId) {
                                    $cp = \App\Models\CapaianPembelajaran::find($cpId);
                                    $subjectId = $cp?->subject_id;
                                    $tingkat = $cp?->tingkat ?? '7';
                                } elseif ($selectedMapel) {
                                    $subjectId = \App\Models\Subject::where('name', $selectedMapel)->value('id');
                                }

                                if (!$subjectId && $guruId) {
                                    $user = auth()->user();
                                    $subjectIds = $user ? $user->getTeachedSubjectIds() : [];
                                    $subjectId = !empty($subjectIds) ? $subjectIds[0] : null;
                                }

                                if ($guruId && $subjectId) {
                                    \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject($guruId, $subjectId, $tingkat, '2026/2027', 'Ganjil', $cpId);
                                }

                                $query = \App\Models\AlurTujuanPembelajaran::with(['capaianPembelajaran', 'subject'])
                                    ->where('guru_id', $guruId);
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                if ($tingkat) {
                                    $query->where('tingkat', $tingkat);
                                }

                                return $query->get()->mapWithKeys(function ($atp) {
                                    $atpKode = $atp->kode_atp ? "ATP {$atp->kode_atp}" : "ATP";
                                    $cp = $atp->capaianPembelajaran;
                                    $cpKode = $cp ? ($cp->kode_cp ? "CP {$cp->kode_cp}" : "CP") : "Semua CP";
                                    $tpSummary = $atp->tp_codes_summary !== 'Belum ada TP' ? " [{$atp->tp_codes_summary}]" : "";
                                    return [$atp->id => "[{$atpKode}] [{$cpKode}] {$atp->judul_atp}"];
                                });
                            })
                            ->default(function (Forms\Get $get) {
                                $guruId = auth()->id();
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $selectedMapel = $get('mata_pelajaran');
                                $subjectId = null;
                                $tingkat = '7';
                                if ($cpId) {
                                    $cp = \App\Models\CapaianPembelajaran::find($cpId);
                                    $subjectId = $cp?->subject_id;
                                    $tingkat = $cp?->tingkat ?? '7';
                                } elseif ($selectedMapel) {
                                    $subjectId = \App\Models\Subject::where('name', $selectedMapel)->value('id');
                                }

                                if (!$subjectId && $guruId) {
                                    $user = auth()->user();
                                    $subjectIds = $user ? $user->getTeachedSubjectIds() : [];
                                    $subjectId = !empty($subjectIds) ? $subjectIds[0] : null;
                                }

                                if ($guruId && $subjectId) {
                                    $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject($guruId, $subjectId, $tingkat, '2026/2027', 'Ganjil', $cpId);
                                    return $atp?->id;
                                }
                                return null;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $atp = \App\Models\AlurTujuanPembelajaran::find($state);
                                    if ($atp) {
                                        if ($atp->capaian_pembelajaran_id && $get('capaian_pembelajaran_id') != $atp->capaian_pembelajaran_id) {
                                            $set('capaian_pembelajaran_id', $atp->capaian_pembelajaran_id);
                                        }
                                        if (!empty($atp->tp_ids)) {
                                            $set('selected_tp_ids', $atp->tp_ids);
                                            $set('content.selected_tp_dropdown', $atp->tp_ids);
                                            $tps = \App\Models\TujuanPembelajaran::whereIn('id', $atp->tp_ids)->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            });
                                            $tpItems = $tps->map(function ($tp) {
                                                $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                                return ['rumusan' => "[{$tp->kode_tp}] {$prefix}{$tp->deskripsi_tp}"];
                                            })->values()->toArray();
                                            $set('content.tujuan_pembelajaran', $tpItems);
                                        }
                                    }
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('ATP memetakan alur pembelajaran sesuai rujukan CP dan TP yang bersangkutan.'),

                        Forms\Components\Select::make('selected_tp_ids')
                            ->label('3. Tujuan Pembelajaran (TP) Terkait')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $selectedMapel = $get('mata_pelajaran');
                                $user = auth()->user();
                                $isPrivileged = $user && $user->hasAnyRole([
                                    'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                    'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                    'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                ]);

                                $query = \App\Models\TujuanPembelajaran::with(['capaianPembelajaran', 'subject']);

                                if ($cpId && is_numeric($cpId)) {
                                    $query->where('capaian_pembelajaran_id', $cpId);
                                } elseif ($selectedMapel) {
                                    $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                } elseif (!$isPrivileged && $user) {
                                    $subjectIds = $user->getTeachedSubjectIds();
                                    if (!empty($subjectIds)) {
                                        $query->whereIn('subject_id', $subjectIds);
                                    } else {
                                        return [];
                                    }
                                }

                                $tps = $query->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                });

                                return $tps->mapWithKeys(function ($tp) {
                                    $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                    return [$tp->id => "[TP {$tp->kode_tp}] {$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 75)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!empty($state)) {
                                    $tps = \App\Models\TujuanPembelajaran::whereIn('id', $state)->get()->sort(function ($a, $b) {
                                        return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                    });
                                    $tpItems = $tps->map(function ($tp) {
                                        $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                        return ['rumusan' => "[{$tp->kode_tp}] {$prefix}{$tp->deskripsi_tp}"];
                                    })->values()->toArray();
                                    $set('content.tujuan_pembelajaran', $tpItems);
                                    $set('content.selected_tp_dropdown', $state);
                                }
                            })
                            ->helperText('Pilih satu atau beberapa TP atau gunakan tombol di atas untuk menambah, mengedit, atau menghapus TP.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('topik')
                            ->label('Topik / Materi Pokok')
                            ->placeholder('Contoh: Teks Deskripsi')
                            ->required(),

                        Forms\Components\TextInput::make('alokasi_waktu')
                            ->label('Alokasi Waktu')
                            ->placeholder('Contoh: 2 x 40 Menit (1 Pertemuan)'),
                    ])->columns(2),

                Forms\Components\Tabs::make('RPPM Builder')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('A. Identifikasi')
                            ->schema([
                                Forms\Components\Fieldset::make('1. Identifikasi Murid (Analisis Kesiapan Belajar)')
                                    ->schema([
                                        Forms\Components\Textarea::make('content.pengetahuan_awal')->label('Pengetahuan Awal')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.minat_belajar')->label('Minat Belajar')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.gaya_belajar')->label('Gaya Belajar')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.kondisi_sosial')->label('Kondisi Sosial-Emosional')->columnSpanFull(),
                                    ]),
                                Forms\Components\Fieldset::make('2. Identifikasi Materi Pelajaran')
                                    ->schema([
                                        Forms\Components\Textarea::make('content.jenis_pengetahuan')->label('Jenis Pengetahuan (Konseptual/Prosedural/Metakognitif)')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.relevansi')->label('Relevansi dengan Kehidupan Nyata')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.tingkat_kesulitan')->label('Tingkat Kesulitan')->columnSpanFull(),
                                        Forms\Components\Textarea::make('content.integrasi_karakter')->label('Integrasi Nilai dan Karakter')->columnSpanFull(),
                                    ]),
                                Forms\Components\Fieldset::make('3. Dimensi Profil Lulusan yang Dikembangkan')
                                    ->schema([
                                        Forms\Components\Repeater::make('content.profil_lulusan')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('dimensi')->label('Dimensi'),
                                                Forms\Components\Textarea::make('penjelasan')->label('Penjelasan Keterkaitan'),
                                            ])->columns(2)->defaultItems(0)
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('B. Desain Pembelajaran')
                            ->schema([
                                Forms\Components\Select::make('content.capaian_pembelajaran_id')
                                    ->label('1. Capaian Pembelajaran (CP)')
                                    ->options(function (Forms\Get $get) {
                                        $user = auth()->user();
                                        if (!$user) return [];

                                        $isPrivileged = $user->hasAnyRole([
                                            'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                            'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                            'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                        ]);

                                        $query = \App\Models\CapaianPembelajaran::with('subject');
                                        $selectedMapel = $get('mata_pelajaran');

                                        if ($selectedMapel) {
                                            $query->whereHas('subject', fn($q) => $q->where('name', $selectedMapel));
                                        } elseif (!$isPrivileged) {
                                            $subjectIds = $user->getTeachedSubjectIds();
                                            if (!empty($subjectIds)) {
                                                $query->whereIn('subject_id', $subjectIds);
                                            } else {
                                                return [];
                                            }
                                        }

                                        return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                            $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                            return [$cp->id => "[{$cpKode}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 75)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                            if ($cp) {
                                                $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                                $set('capaian_pembelajaran_id', $cp->id);
                                                if ($cp->subject && !$get('mata_pelajaran')) {
                                                    $set('mata_pelajaran', $cp->subject->name);
                                                }
                                            }
                                        }
                                    })
                                    ->helperText('Pilihan Capaian Pembelajaran (CP) yang menjadi acuan.'),

                                Forms\Components\Textarea::make('content.capaian_pembelajaran')
                                    ->label('Capaian Pembelajaran (CP) Terpilih')
                                    ->rows(3)
                                    ->helperText('Menampilkan isi Capaian Pembelajaran (CP) yang terpilih.'),

                                Forms\Components\Textarea::make('content.lintas_disiplin')
                                    ->label('2. Lintas Disiplin Ilmu')
                                    ->rows(3),

                                Forms\Components\Textarea::make('content.topik_pembelajaran')
                                    ->label('4. Topik Pembelajaran')
                                    ->rows(2),

                                Forms\Components\Select::make('content.selected_tp_dropdown')
                                    ->label('3. Tujuan Pembelajaran (TP)')
                                    ->multiple()
                                    ->options(function (Forms\Get $get) {
                                        $cpId = $get('content.capaian_pembelajaran_id') ?? $get('capaian_pembelajaran_id');
                                        $selectedMapel = $get('mata_pelajaran');
                                        $user = auth()->user();
                                        $isPrivileged = $user && $user->hasAnyRole([
                                            'superadmin', 'kepala_sekolah', 'waka_kurikulum',
                                            'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan',
                                            'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'
                                        ]);

                                        $query = \App\Models\TujuanPembelajaran::with(['capaianPembelajaran', 'subject']);

                                        if ($cpId && is_numeric($cpId)) {
                                            $query->where('capaian_pembelajaran_id', $cpId);
                                        } elseif ($selectedMapel) {
                                            $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                        } elseif (!$isPrivileged && $user) {
                                            $subjectIds = $user->getTeachedSubjectIds();
                                            if (!empty($subjectIds)) {
                                                $query->whereIn('subject_id', $subjectIds);
                                            } else {
                                                return [];
                                            }
                                        }

                                        $tps = $query->get()->sort(function ($a, $b) {
                                            return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                        });

                                        return $tps->mapWithKeys(function ($tp) {
                                            $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                            return [$tp->id => "[TP {$tp->kode_tp}] {$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 75)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!empty($state)) {
                                            $tps = \App\Models\TujuanPembelajaran::whereIn('id', $state)->get()->sort(function ($a, $b) {
                                                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                            });
                                            $tpItems = $tps->map(function ($tp) {
                                                $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                                return ['rumusan' => "[{$tp->kode_tp}] {$prefix}{$tp->deskripsi_tp}"];
                                            })->values()->toArray();
                                            $set('selected_tp_ids', $state);
                                            $set('content.tujuan_pembelajaran', $tpItems);
                                        }
                                    })
                                    ->helperText('Pilihan Tujuan Pembelajaran (TP) yang ingin dicapai.'),

                                Forms\Components\Fieldset::make('5. Kerangka Pembelajaran')
                                    ->schema([
                                        Forms\Components\Textarea::make('content.praktik_pedagogis')->label('Praktik Pedagogis'),
                                        Forms\Components\Textarea::make('content.kemitraan')->label('Kemitraan Pembelajaran'),
                                        Forms\Components\Textarea::make('content.lingkungan')->label('Lingkungan Pembelajaran'),
                                        Forms\Components\Textarea::make('content.pemanfaatan_digital')->label('Pemanfaatan Digital'),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('C. Pengalaman Belajar')
                            ->schema([
                                Forms\Components\Repeater::make('content.pengalaman_belajar')
                                    ->label('Tahapan Kegiatan')
                                    ->schema([
                                        Forms\Components\Select::make('tahap')
                                            ->label('Tahap')
                                            ->options([
                                                'Kegiatan Awal' => 'Kegiatan Awal',
                                                'Kegiatan Inti' => 'Kegiatan Inti',
                                                'Kegiatan Penutup' => 'Kegiatan Penutup',
                                            ]),
                                        Forms\Components\TextInput::make('waktu')
                                            ->label('Alokasi Waktu')
                                            ->placeholder('Contoh: 15 menit'),
                                        Forms\Components\Textarea::make('prinsip')
                                            ->label('Prinsip Pembelajaran Mendalam')
                                            ->columnSpanFull(),
                                        Forms\Components\RichEditor::make('kegiatan')
                                            ->label('Uraian Kegiatan')
                                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                                            ->columnSpanFull(),
                                    ])->columns(2)->defaultItems(0)
                            ]),

                        Forms\Components\Tabs\Tab::make('D. Asesmen')
                            ->schema([
                                Forms\Components\Fieldset::make('Rencana Asesmen')
                                    ->schema([
                                        Forms\Components\Repeater::make('content.asesmen')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Select::make('jenis')
                                                    ->label('Jenis Asesmen')
                                                    ->options([
                                                        'Asesmen Awal (Diagnostik)' => 'Awal (Diagnostik)',
                                                        'Asesmen Proses (Formatif)' => 'Proses (Formatif)',
                                                        'Asesmen Akhir (Sumatif)' => 'Akhir (Sumatif)',
                                                    ]),
                                                Forms\Components\TextInput::make('waktu')->label('Waktu Pelaksanaan'),
                                                Forms\Components\TextInput::make('teknik')->label('Teknik'),
                                                Forms\Components\TextInput::make('instrumen')->label('Instrumen'),
                                                Forms\Components\Textarea::make('tujuan')->label('Tujuan')->columnSpanFull(),
                                            ])->columns(2)->defaultItems(0)
                                    ]),
                                    
                                Forms\Components\Fieldset::make('Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)')
                                    ->schema([
                                        Forms\Components\Repeater::make('content.kktp')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('kriteria')->label('Kriteria Penilaian')->placeholder('Contoh: Ketepatan menyelesaikan masalah kontekstual materi')->columnSpanFull(),
                                                Forms\Components\Textarea::make('bimbingan')->label('Perlu Bimbingan (Di Bawah 70)')->placeholder('Deskripsi capaian siswa jika nilai < 70...'),
                                                Forms\Components\Textarea::make('cukup')->label('Cukup (70 - 79)')->placeholder('Deskripsi capaian siswa jika nilai 70 - 79...'),
                                                Forms\Components\Textarea::make('baik')->label('Baik (80 - 89)')->placeholder('Deskripsi capaian siswa jika nilai 80 - 89...'),
                                                Forms\Components\Textarea::make('sangat_baik')->label('Sangat Baik (90 - 100)')->placeholder('Deskripsi capaian siswa jika nilai 90 - 100...'),
                                            ])->columns(2)->defaultItems(0)
                                    ]),
                            ]),
                            
                        Forms\Components\Tabs\Tab::make('E. Lampiran')
                            ->schema([
                                Forms\Components\RichEditor::make('content.lampiran')
                                    ->label('Ringkasan Bahan Ajar & Lampiran Lainnya')
                                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'attachFiles'])
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'approved' => 'success',
                        'published' => 'info',
                        'need_revision' => 'danger',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'approved' => 'Disetujui (ACC)',
                        'published' => 'Menunggu ACC',
                        'need_revision' => 'Perlu Perbaikan',
                        'draft' => 'Draft',
                        default => ucfirst($state ?? 'Draft'),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas_fase')
                    ->label('Kelas/Fase')
                    ->searchable(),
                Tables\Columns\TextColumn::make('topik')
                    ->label('Topik/Materi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('capaianPembelajaran.elemen')
                    ->label('Elemen CP')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('tp_count')
                    ->label('TP Terpilih')
                    ->state(fn (Rppm $record) => count($record->selected_tp_ids ?? []) . ' TP')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('guru.name')
                    ->label('Penyusun')
                    ->searchable(),
                Tables\Columns\TextColumn::make('catatan_kepala_sekolah')
                    ->label('Catatan KS')
                    ->limit(35)
                    ->tooltip(fn (Rppm $record): ?string => $record->catatan_kepala_sekolah)
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status RPPM')
                    ->options([
                        'draft' => 'Draft (Simpan Sementara)',
                        'published' => 'Menunggu ACC (Publikasi)',
                        'approved' => 'Disetujui (ACC)',
                        'need_revision' => 'Perlu Perbaikan',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (Rppm $record): string => route('academic.rppm.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info'),

                // Lanjutkan pengisian / Edit khusus Guru & Admin kurikulum (Kepala Sekolah tidak bisa edit)
                Tables\Actions\Action::make('continue')
                    ->label('Lanjutkan Pengisian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->url(fn (Rppm $record): string => RppmResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (Rppm $record): bool => !auth()->user()?->hasRole('kepala_sekolah') && in_array($record->status ?? 'draft', ['draft', 'need_revision'])),

                Tables\Actions\EditAction::make()
                    ->label(fn (Rppm $record) => in_array($record->status ?? 'draft', ['draft', 'need_revision']) ? 'Edit / Perbaiki' : 'Edit')
                    ->visible(fn (Rppm $record): bool => !auth()->user()?->hasRole('kepala_sekolah') && (auth()->id() === $record->guru_id || auth()->user()?->hasAnyRole(['superadmin', 'waka_kurikulum']))),

                // Tombol Hapus RPPM untuk guru penyusun atau admin kurikulum
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Hapus Dokumen RPPM & Perangkat Ajar')
                    ->modalDescription('Apakah Anda yakin ingin menghapus inputan RPPM ini? Data yang dihapus tidak dapat dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->visible(fn (Rppm $record): bool => !auth()->user()?->hasRole('kepala_sekolah') && (auth()->id() === $record->guru_id || auth()->user()?->hasAnyRole(['superadmin', 'waka_kurikulum']))),

                // Tombol ACC khusus Kepala Sekolah & Superadmin
                Tables\Actions\Action::make('acc_rppm')
                    ->label('ACC')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (Rppm $record): bool => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->modalHeading('ACC / Setujui RPPM')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui RPPM ini? Anda dapat memberikan catatan supervisi.')
                    ->modalSubmitActionLabel('Setujui (ACC)')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Supervisi / Persetujuan (Opsional)')
                            ->placeholder('Contoh: RPPM telah sesuai kaidah kurikulum dan disetujui.')
                            ->default(fn (Rppm $record) => $record->catatan_kepala_sekolah)
                            ->rows(3),
                    ])
                    ->action(function (Rppm $record, array $data): void {
                        $record->update([
                            'status' => 'approved',
                            'catatan_kepala_sekolah' => $data['catatan_kepala_sekolah'] ?? null,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('RPPM Berhasil Disetujui (ACC)')
                            ->body("RPPM {$record->mata_pelajaran} kelas {$record->kelas_fase} telah disetujui.")
                            ->success()
                            ->send();
                    }),

                // Tombol Perbaikan khusus Kepala Sekolah & Superadmin
                Tables\Actions\Action::make('perbaikan_rppm')
                    ->label('Perbaikan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->button()
                    ->visible(fn (Rppm $record): bool => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                    ->modalHeading('Minta Perbaikan / Revisi RPPM')
                    ->modalDescription('Kembalikan RPPM kepada guru untuk diperbaiki beserta catatan perbaikan yang jelas.')
                    ->modalSubmitActionLabel('Kirim Catatan Perbaikan')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kepala_sekolah')
                            ->label('Catatan Perbaikan dari Kepala Sekolah')
                            ->placeholder('Contoh: Mohon perbaiki bagian kegiatan inti pembelajaran dan lengkapi rubrik asesmen formatif.')
                            ->default(fn (Rppm $record) => $record->catatan_kepala_sekolah)
                            ->required()
                            ->rows(4)
                            ->helperText('Catatan ini akan langsung tampil di akun guru penyusun RPPM.'),
                    ])
                    ->action(function (Rppm $record, array $data): void {
                        $record->update([
                            'status' => 'need_revision',
                            'catatan_kepala_sekolah' => $data['catatan_kepala_sekolah'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status RPPM Diubah Menjadi Perbaikan')
                            ->body("Catatan perbaikan telah dikirim ke guru penyusun.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => !auth()->user()?->hasRole('kepala_sekolah')),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRppms::route('/'),
            'create' => Pages\CreateRppm::route('/create'),
            'view' => Pages\ViewRppm::route('/{record}'),
            'edit' => Pages\EditRppm::route('/{record}/edit'),
        ];
    }
}
