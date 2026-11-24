<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\RppmResource\Pages;
use App\Models\Rppm;
use App\Models\CapaianPembelajaran;
use App\Models\TujuanPembelajaran;
use App\Models\AlurTujuanPembelajaran;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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

        // Guru Al-Qur'an & Koordinator Madin: TIDAK mengakses RPPM mata pelajaran umum
        if ($user->isQuranTeacher() || $user->hasRole('koordinator_madin')) {
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
    protected static ?string $modelLabel = 'Perangkat Ajar';
    protected static ?string $pluralModelLabel = 'Perangkat Ajar';
    protected static ?string $navigationLabel = 'Perangkat Ajar';
    protected static ?int $navigationSort = 5;

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
        return 'Perangkat Ajar';
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('rppm_detail')
                    ->view('filament.staff.components.rppm-detail-view')
                    ->columnSpanFull(),
            ]);
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

                Forms\Components\Section::make('📋 Perangkat Ajar & Modul Pembelajaran')
                    ->description('Kelola dan susun perangkat ajar, capaian pembelajaran (CP), serta tujuan pembelajaran (TP) langsung dalam satu formulir ringkas.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => auth()->id()),

                        // Inputan CP, TP, dan Dokumen Pembelajaran disusun terpadu langsung di bawah
                        // 1. Pilihan Mata Pelajaran & Pilihan Fase Kurikulum (Multiple Choice)
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
                                    return Subject::akademik()->orderBy('name')->pluck('name', 'name');
                                }

                                $subjectIds = $user->getTeachedSubjectIds();
                                if (!empty($subjectIds)) {
                                    $assigned = Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'name');
                                    if ($assigned->isNotEmpty()) {
                                        return $assigned;
                                    }
                                }

                                return Subject::akademik()->orderBy('name')->pluck('name', 'name');
                            })
                            ->default(function () {
                                $user = auth()->user();
                                if (!$user) return null;
                                $subjectIds = $user->getTeachedSubjectIds();
                                if (!empty($subjectIds)) {
                                    $first = Subject::whereIn('id', $subjectIds)->first();
                                    if ($first) return $first->name;
                                }
                                $jabatan = strtolower($user->jabatan ?? '');
                                if (str_contains($jabatan, 'pai') || str_contains($jabatan, 'agama')) {
                                    $pai = Subject::where('name', 'like', '%Pendidikan Agama Islam%')->orWhere('name', 'like', '%PAI%')->first();
                                    if ($pai) return $pai->name;
                                }
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('capaian_pembelajaran_id', null);
                                $set('content.capaian_pembelajaran_id', null);
                                $set('content.capaian_pembelajaran', null);
                                $set('selected_tp_ids', []);
                                $set('content.selected_tp_dropdown', []);
                                $set('content.tujuan_pembelajaran', []);
                            })
                            ->helperText('Mata pelajaran yang Anda ampu. Pilihan CP dan TP akan disesuaikan otomatis.'),

                        Forms\Components\CheckboxList::make('fases')
                            ->label('Pilihan Fase Kurikulum (Multiple Choice)')
                            ->options(CapaianPembelajaran::FASE_OPTIONS)
                            ->columns(3)
                            ->default(['D'])
                            ->required()
                            ->live()
                            ->afterStateHydrated(function ($component, ?Rppm $record) {
                                if ($record) {
                                    if (!empty($record->fases)) {
                                        $component->state($record->fases);
                                    } elseif (!empty($record->fase)) {
                                        $detected = [];
                                        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $f) {
                                            if (stripos($record->fase, "fase {$f}") !== false || stripos($record->fase, "fase-{$f}") !== false || $record->fase === $f) {
                                                $detected[] = $f;
                                            }
                                        }
                                        $component->state(!empty($detected) ? $detected : ['D']);
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $fases = is_array($state) ? $state : ['D'];
                                $faseStr = collect($fases)->map(fn ($f) => "Fase {$f}")->implode(', ');
                                $set('kelas_fase', "Kelas 7 / {$faseStr}");
                                $set('capaian_pembelajaran_id', null);
                                $set('selected_tp_ids', []);
                            })
                            ->helperText('Pilih satu atau lebih fase untuk RPPM / Modul Ajar ini (Multiple Choice).')
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('kelas_fase')
                            ->default('Kelas 7 / Fase D'),

                        Forms\Components\TextInput::make('topik')
                            ->label('Topik / Judul Modul Ajar / RPPM')
                            ->placeholder('Contoh: Modul Ajar 1 - Teks Deskripsi / Operasi Aljabar')
                            ->required(),

                        Forms\Components\TextInput::make('alokasi_waktu')
                            ->label('Alokasi Waktu (Opsional)')
                            ->placeholder('Contoh: 2 x 40 Menit (1 Pertemuan)'),

                        // Section Terpadu 1: Capaian Pembelajaran (CP) Langsung di Form
                        Forms\Components\Fieldset::make('1. Capaian Pembelajaran (CP)')
                            ->schema([
                                Forms\Components\Radio::make('cp_mode')
                                    ->label('Metode Pengisian CP')
                                    ->options([
                                        'pilih' => '📋 Pilih CP yang Sudah Tersimpan di Bank CP',
                                        'baru'  => '➕ Tulis / Input Capaian Pembelajaran (CP) Baru Langsung',
                                    ])
                                    ->default('pilih')
                                    ->inline()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state === 'baru') {
                                            $set('capaian_pembelajaran_id', null);
                                        }
                                    })
                                    ->columnSpanFull(),

                                // Mode A: Pilih CP yang sudah ada
                                Forms\Components\Select::make('capaian_pembelajaran_id')
                                    ->label('Pilih Rujukan Capaian Pembelajaran (CP)')
                                    ->placeholder('Pilih salah satu Capaian Pembelajaran...')
                                    ->visible(fn (Forms\Get $get) => $get('cp_mode') !== 'baru')
                                    ->options(function (Forms\Get $get) {
                                        $user = auth()->user();
                                        if (!$user) return [];

                                        $selectedMapel = $get('mata_pelajaran');
                                        $selectedFases = (array) ($get('fases') ?? ['D']);

                                        $query = CapaianPembelajaran::with('subject');
                                        if ($selectedMapel) {
                                            $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                        }

                                        if (!empty($selectedFases)) {
                                            $query->where(function ($q) use ($selectedFases) {
                                                foreach ($selectedFases as $f) {
                                                    $q->orWhereJsonContains('fases', $f)
                                                      ->orWhere('fase', 'like', "%{$f}%");
                                                }
                                                $q->orWhereNull('fases');
                                            });
                                        }

                                        return $query->get()->mapWithKeys(function ($cp) {
                                            $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                            $faseStr = !empty($cp->fases) ? 'Fase ' . implode('&', (array) $cp->fases) : ($cp->fase ?: 'Fase D');
                                            return [$cp->id => "[{$cpKode}] [{$faseStr} - Kelas {$cp->tingkat}] {$cp->elemen} - " . Str::limit($cp->deskripsi_cp, 65)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $cp = CapaianPembelajaran::with('subject')->find($state);
                                            if ($cp) {
                                                $set('content.capaian_pembelajaran_id', $cp->id);
                                                $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                                if (empty($get('topik'))) {
                                                    $set('topik', $cp->elemen);
                                                }
                                            }
                                        }
                                    })
                                    ->helperText('Pilih CP yang menjadi rujukan modul ajar ini (disaring otomatis sesuai Mapel & Fase).')
                                    ->columnSpanFull(),

                                // Mode B: Input CP Baru Langsung di Form
                                Forms\Components\Grid::make(3)
                                    ->visible(fn (Forms\Get $get) => $get('cp_mode') === 'baru')
                                    ->schema([
                                        Forms\Components\TextInput::make('new_cp_elemen')
                                            ->label('Elemen / Lingkup Materi CP')
                                            ->placeholder('Contoh: Bilangan, Aljabar, Fikih, dll.')
                                            ->required(fn (Forms\Get $get) => $get('cp_mode') === 'baru'),

                                        Forms\Components\TextInput::make('new_cp_kode')
                                            ->label('Kode CP (Opsional)')
                                            ->placeholder('Contoh: 1, 2, dst. (Otomatis jika kosong)'),

                                        Forms\Components\Select::make('new_cp_masa_berlaku')
                                            ->label('Masa Berlaku')
                                            ->options([
                                                '1 Tahun'    => '1 Tahun Ajaran (2 Semester)',
                                                '1 Semester' => '1 Semester',
                                            ])
                                            ->default('1 Tahun')
                                            ->required(fn (Forms\Get $get) => $get('cp_mode') === 'baru'),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('new_cp_deskripsi')
                                    ->label('Rumusan Deskripsi Capaian Pembelajaran (CP) Baru')
                                    ->placeholder('Tuliskan rumusan lengkap Capaian Pembelajaran yang harus dicapai peserta didik...')
                                    ->rows(3)
                                    ->visible(fn (Forms\Get $get) => $get('cp_mode') === 'baru')
                                    ->required(fn (Forms\Get $get) => $get('cp_mode') === 'baru')
                                    ->helperText('Klik tombol Simpan CP di bawah untuk menyimpan langsung ke Bank CP dan menjadikannya rujukan pembelajaran.')
                                    ->columnSpanFull(),

                                // TOMBOL SIMPAN CP KE BANK DATA
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('save_cp_to_bank_btn')
                                        ->label('💾 Simpan CP ke Bank Data & Jadikan Rujukan')
                                        ->icon('heroicon-o-check-circle')
                                        ->color('success')
                                        ->button()
                                        ->size('lg')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $mapel = $get('mata_pelajaran');
                                            $fases = (array) ($get('fases') ?? ['D']);
                                            $elemen = trim((string) $get('new_cp_elemen'));
                                            $deskripsi = trim((string) $get('new_cp_deskripsi'));
                                            $masaBerlaku = $get('new_cp_masa_berlaku') ?? '1 Tahun';
                                            $kodeCp = trim((string) $get('new_cp_kode'));

                                            if (empty($mapel)) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Mata Pelajaran Belum Dipilih')
                                                    ->body('Silakan pilih Mata Pelajaran yang Diampu terlebih dahulu di bagian atas.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            if (empty($elemen) || empty($deskripsi)) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Data CP Belum Lengkap')
                                                    ->body('Elemen Materi dan Rumusan Deskripsi CP wajib diisi sebelum menekan tombol simpan.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $subj = \App\Models\Subject::where('name', $mapel)->first();
                                            $subjectId = $subj?->id;
                                            if (!$subjectId) {
                                                $user = auth()->user();
                                                $sIds = $user ? $user->getTeachedSubjectIds() : [];
                                                $subjectId = !empty($sIds) ? $sIds[0] : \App\Models\Subject::first()?->id;
                                            }

                                            $faseStr = collect($fases)->map(fn ($f) => "Fase {$f}")->implode(', ');

                                            $cp = \App\Models\CapaianPembelajaran::create([
                                                'institution_id' => auth()->user()?->institution_id,
                                                'guru_id' => auth()->id(),
                                                'subject_id' => $subjectId,
                                                'kode_cp' => !empty($kodeCp) ? $kodeCp : null,
                                                'fase' => $faseStr ?: 'Fase D',
                                                'fases' => $fases,
                                                'tingkat' => '7',
                                                'tahun_ajaran' => '2026/2027',
                                                'semester' => '1',
                                                'masa_berlaku' => $masaBerlaku,
                                                'elemen' => $elemen,
                                                'deskripsi_cp' => $deskripsi,
                                            ]);

                                            // Otomatis aktif sebagai rujukan terpilih
                                            $set('cp_mode', 'pilih');
                                            $set('capaian_pembelajaran_id', $cp->id);
                                            $set('content.capaian_pembelajaran_id', $cp->id);
                                            $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                            if (empty($get('topik'))) {
                                                $set('topik', $cp->elemen);
                                            }

                                            // Bersihkan field baru
                                            $set('new_cp_elemen', null);
                                            $set('new_cp_kode', null);
                                            $set('new_cp_deskripsi', null);

                                            \Filament\Notifications\Notification::make()
                                                ->title('Capaian Pembelajaran (CP) Berhasil Disimpan!')
                                                ->body("CP elemen '{$cp->elemen}' tersimpan di Bank CP dan otomatis aktif sebagai rujukan pembuatan TP.")
                                                ->success()
                                                ->send();
                                        }),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('cp_mode') === 'baru')
                                ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('alur_tujuan_pembelajaran_id'),

                        // Section Terpadu 2: Tujuan Pembelajaran (TP) Langsung di Form
                        Forms\Components\Fieldset::make('2. Tujuan Pembelajaran (TP) Terkait')
                            ->schema([
                                // Indikator Jelas: Rujukan CP yang Aktif untuk Pembuatan TP
                                Forms\Components\Placeholder::make('info_rujukan_cp')
                                    ->label('📌 Rujukan Capaian Pembelajaran (CP) untuk Pembuatan TP')
                                    ->content(function (Forms\Get $get) {
                                        $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                        if (!$cpId) {
                                            return new \Illuminate\Support\HtmlString('<div class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-lg text-sm text-amber-800 dark:text-amber-200 font-medium">⚠️ <strong>Belum ada CP yang dijadikan rujukan.</strong> Silakan pilih atau input & simpan CP pada bagian 1 di atas agar TP terhubung secara otomatis ke rujukan CP yang tepat.</div>');
                                        }

                                        $cp = \App\Models\CapaianPembelajaran::find($cpId);
                                        if (!$cp) return 'CP tidak ditemukan.';

                                        $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                        $faseStr = !empty($cp->fases) ? 'Fase ' . implode('&', (array) $cp->fases) : ($cp->fase ?: 'Fase D');
                                        return new \Illuminate\Support\HtmlString("<div class='p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-lg text-sm text-blue-900 dark:text-blue-100'><strong>[{$cpKode}] [{$faseStr}] {$cp->elemen}</strong><br><span class='text-xs opacity-90 leading-relaxed block mt-1'>{$cp->deskripsi_cp}</span></div>");
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('selected_tp_ids')
                                    ->label('Pilih TP yang Sudah Ada di Bank TP (Bisa Pilih Lebih Dari Satu)')
                                    ->multiple()
                                    ->placeholder('Pilih satu atau beberapa TP yang sudah ada...')
                                    ->options(function (Forms\Get $get) {
                                        $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                        $selectedMapel = $get('mata_pelajaran');
                                        $selectedFases = (array) ($get('fases') ?? ['D']);

                                        $query = TujuanPembelajaran::with(['capaianPembelajaran', 'subject']);
                                        if ($cpId) {
                                            $query->where('capaian_pembelajaran_id', $cpId);
                                        } elseif ($selectedMapel) {
                                            $query->whereHas('subject', fn ($q) => $q->where('name', $selectedMapel));
                                        }

                                        if (!empty($selectedFases)) {
                                            $query->where(function ($q) use ($selectedFases) {
                                                foreach ($selectedFases as $f) {
                                                    $q->orWhereJsonContains('fases', $f)
                                                      ->orWhereHas('capaianPembelajaran', function ($cpQ) use ($f) {
                                                          $cpQ->whereJsonContains('fases', $f)->orWhere('fase', 'like', "%{$f}%");
                                                      });
                                                }
                                                $q->orWhereNull('fases');
                                            });
                                        }

                                        return $query->get()->sort(function ($a, $b) {
                                            return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                        })->mapWithKeys(function ($tp) {
                                            $faseStr = !empty($tp->fases) ? '[Fase ' . implode('&', (array) $tp->fases) . '] ' : '';
                                            $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                            return [$tp->id => "{$faseStr}[TP {$tp->kode_tp}] {$prefix}" . Str::limit($tp->deskripsi_tp, 70)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!empty($state)) {
                                            $tps = TujuanPembelajaran::whereIn('id', $state)->get()->sort(function ($a, $b) {
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
                                    ->helperText('Pilih TP yang dicapai pada modul ajar ini dari daftar yang sudah tersimpan.')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('enable_new_tps')
                                    ->label('➕ Tulis / Tambah Tujuan Pembelajaran (TP) Baru Langsung di Form Ini')
                                    ->helperText('Input dan simpan rumusan TP baru langsung ke Bank TP merujuk pada CP yang aktif di atas.')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->visible(fn (Forms\Get $get) => (bool) $get('enable_new_tps'))
                                    ->schema([
                                        Forms\Components\TextInput::make('single_new_tp_kode')
                                            ->label('Kode TP (Opsional)')
                                            ->placeholder('Contoh: 1.1 / 1.2 (Otomatis jika kosong)'),

                                        Forms\Components\TextInput::make('single_new_tp_materi')
                                            ->label('Materi Pokok (Opsional)')
                                            ->placeholder('Contoh: Aljabar Dasar'),

                                        Forms\Components\CheckboxList::make('single_new_tp_kelas')
                                            ->label('Rekomendasi Kelas')
                                            ->options([
                                                '7' => 'Kelas 7',
                                                '8' => 'Kelas 8',
                                                '9' => 'Kelas 9',
                                            ])
                                            ->default(['7'])
                                            ->columns(3),
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('single_new_tp_deskripsi')
                                    ->label('Rumusan Deskripsi Tujuan Pembelajaran (TP) Baru')
                                    ->placeholder('Tuliskan rumusan tujuan pembelajaran yang ingin dicapai...')
                                    ->rows(2)
                                    ->visible(fn (Forms\Get $get) => (bool) $get('enable_new_tps'))
                                    ->helperText('TP baru ini akan otomatis merujuk ke Capaian Pembelajaran (CP) yang aktif di atas.')
                                    ->columnSpanFull(),

                                // TOMBOL SIMPAN TP KE BANK DATA
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('save_tp_to_bank_btn')
                                        ->label('💾 Simpan TP ke Bank Data & Pilih untuk Modul Ini')
                                        ->icon('heroicon-o-check-circle')
                                        ->color('success')
                                        ->button()
                                        ->size('lg')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                            $deskripsi = trim((string) $get('single_new_tp_deskripsi'));
                                            $mapel = $get('mata_pelajaran');
                                            $fases = (array) ($get('fases') ?? ['D']);

                                            if (empty($cpId)) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Rujukan CP Belum Terpilih')
                                                    ->body('Silakan pilih atau simpan Capaian Pembelajaran (CP) pada bagian 1 terlebih dahulu sebagai rujukan TP ini.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            if (empty($deskripsi)) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Rumusan TP Belum Diisi')
                                                    ->body('Silakan tulis rumusan deskripsi Tujuan Pembelajaran sebelum menekan tombol simpan.')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }

                                            $cp = \App\Models\CapaianPembelajaran::with('subject')->find($cpId);
                                            $subjectId = $cp?->subject_id;
                                            if (!$subjectId && $mapel) {
                                                $subj = \App\Models\Subject::where('name', $mapel)->first();
                                                $subjectId = $subj?->id;
                                            }

                                            $kodeTp = trim((string) $get('single_new_tp_kode'));
                                            $materi = trim((string) $get('single_new_tp_materi'));
                                            $rekomendasi = (array) ($get('single_new_tp_kelas') ?? ['7']);

                                            $tp = \App\Models\TujuanPembelajaran::create([
                                                'institution_id' => auth()->user()?->institution_id,
                                                'guru_id' => auth()->id(),
                                                'capaian_pembelajaran_id' => $cpId,
                                                'subject_id' => $subjectId,
                                                'kode_tp' => !empty($kodeTp) ? $kodeTp : null,
                                                'fases' => !empty($cp?->fases) ? $cp->fases : $fases,
                                                'rekomendasi_kelas' => $rekomendasi,
                                                'materi' => !empty($materi) ? $materi : null,
                                                'deskripsi_tp' => $deskripsi,
                                                'kelas_tingkat' => (string) ($rekomendasi[0] ?? ($cp?->tingkat ?: '7')),
                                                'semester' => 'Ganjil',
                                            ]);

                                            // Sinkronisasi otomatis ke ATP
                                            if ($subjectId) {
                                                \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                                    auth()->id(),
                                                    $subjectId,
                                                    $tp->kelas_tingkat
                                                );
                                            }

                                            // Tambahkan langsung ke daftar TP terpilih
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

                                            // Bersihkan field inputan agar guru bisa input TP berikutnya jika ada
                                            $set('single_new_tp_kode', null);
                                            $set('single_new_tp_materi', null);
                                            $set('single_new_tp_deskripsi', null);

                                            \Filament\Notifications\Notification::make()
                                                ->title('Tujuan Pembelajaran (TP) Berhasil Disimpan!')
                                                ->body("TP '{$tp->kode_tp}' telah tersimpan ke Bank TP merujuk pada CP '{$cp->elemen}' dan otomatis terpilih untuk modul ini.")
                                                ->success()
                                                ->send();
                                        }),
                                ])
                                ->visible(fn (Forms\Get $get) => (bool) $get('enable_new_tps'))
                                ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('link_drive')
                            ->label('3. Tautan / Link Google Drive Dokumen Perangkat Ajar')
                            ->placeholder('Contoh: https://drive.google.com/file/d/... atau https://docs.google.com/document/d/...')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('content.link_drive', $state);
                            })
                            ->helperText('Masukkan tautan Google Drive dokumen Perangkat Ajar / Modul Ajar lengkap. Pastikan akses disetel "Siapa saja yang memiliki link dapat melihat".')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('📂 Arsip Dokumen RPPM Format Lama (Tersimpan)')
                    ->description('Dokumen identifikasi dan rancangan pembelajaran format lama yang pernah diisi sebelumnya.')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?Rppm $record) => !empty($record?->content) && count(array_filter((array) $record->content)) > 1)
                    ->schema([
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
                                            $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                            $deskripsi = trim((string) ($tp->deskripsi_tp ?: ($tp->materi ?: "Tujuan Pembelajaran #{$tp->id}")));
                                            return [$tp->id => "{$prefix}{$deskripsi}"];
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
            ])
            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('link_drive')
                    ->label('Link Drive RPPM')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? 'Google Drive ↗' : '-')
                    ->url(fn (Rppm $record) => $record->link_drive, true)
                    ->openUrlInNewTab(),
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

                Tables\Actions\Action::make('open_link_drive')
                    ->label('Buka Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Rppm $record) => $record->link_drive, true)
                    ->openUrlInNewTab()
                    ->visible(fn (Rppm $record): bool => !empty($record->link_drive)),

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

                // Tombol Hapus khusus Guru Pemilik RPPM, Waka Kurikulum, & Superadmin (Kepala Sekolah tidak menghapus)
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Hapus Dokumen Perangkat Ajar')
                    ->modalDescription('Apakah Anda yakin ingin menghapus dokumen Perangkat Ajar ini? Data yang telah dihapus tidak dapat dikembalikan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->successNotificationTitle('Dokumen Perangkat Ajar Berhasil Dihapus')
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
