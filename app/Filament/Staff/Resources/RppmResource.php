<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\RppmResource\Pages;
use App\Models\Rppm;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RppmResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    protected static ?string $model = Rppm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'RPPM & Perangkat Ajar';
    protected static ?string $pluralModelLabel = 'RPPM & Perangkat Ajar';
    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Bab 2: Perencanaan Administrasi Pembelajaran';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return '- Fitur RPPM';
        }
        return 'RPPM & Perangkat Ajar';
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 2;
        }
        return 6;
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar & Input Perangkat Ajar (CP, TP, ATP)')
                    ->description('Input atau pilih Capaian Pembelajaran (CP) dan Tujuan Pembelajaran (TP) yang berlaku 1 semester hingga 1 tahun ajaran.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => auth()->id()),

                        // Tombol Aksi Input & Simpan Langsung (Dengan Tombol Submit & Modal)
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('modal_create_cp')
                                ->label('+ Input & Simpan CP Baru')
                                ->icon('heroicon-o-plus-circle')
                                ->color('primary')
                                ->button()
                                ->modalHeading('Input & Simpan Capaian Pembelajaran (CP) Baru')
                                ->modalDescription('Capaian Pembelajaran (CP) yang disimpan akan berlaku 1 semester hingga 1 tahun dan langsung muncul pada menu dropdown di bawah.')
                                ->modalSubmitActionLabel('Simpan Capaian Pembelajaran (CP)')
                                ->modalWidth('2xl')
                                ->form([
                                    Forms\Components\Select::make('modal_subject_id')
                                        ->label('Mata Pelajaran')
                                        ->options(fn () => \App\Models\Subject::orderBy('name')->pluck('name', 'id'))
                                        ->default(function () {
                                            return \App\Models\TeacherSubjectClass::where('teacher_id', auth()->id())->value('subject_id')
                                                ?? \App\Models\Subject::first()?->id;
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
                                        ->label('Deskripsi / Rumusan Capaian Pembelajaran (CP)')
                                        ->placeholder('Contoh: Peserta didik mampu memahami konsep bilangan bulat dan operasi aljabar...')
                                        ->rows(4)
                                        ->required()
                                        ->helperText('CP berlaku antara 1 semester hingga 1 tahun pembelajaran.'),
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
                                        ->body("CP elemen '{$cp->elemen}' tersimpan dan otomatis terpilih pada menu dropdown.")
                                        ->success()
                                        ->send();
                                }),

                            Forms\Components\Actions\Action::make('modal_create_tp')
                                ->label('+ Input & Simpan TP Baru')
                                ->icon('heroicon-o-plus-circle')
                                ->color('success')
                                ->button()
                                ->modalHeading('Input & Simpan Tujuan Pembelajaran (TP) Baru')
                                ->modalDescription('Pilih Capaian Pembelajaran (CP) dan rumuskan Tujuan Pembelajaran yang ingin dicapai.')
                                ->modalSubmitActionLabel('Simpan Tujuan Pembelajaran (TP)')
                                ->modalWidth('2xl')
                                ->form([
                                    Forms\Components\Select::make('modal_cp_id')
                                        ->label('Rujukan Capaian Pembelajaran (CP)')
                                        ->options(function () {
                                            return \App\Models\CapaianPembelajaran::with('subject')->get()->mapWithKeys(function ($cp) {
                                                $mapel = $cp->subject?->name ?? 'Mapel';
                                                $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                                return [$cp->id => "[{$cpKode}] [{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 55)];
                                            });
                                        })
                                        ->default(fn (Forms\Get $get) => $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id'))
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Pilih Capaian Pembelajaran (CP) yang menjadi dasar pembuatan Tujuan Pembelajaran ini.'),

                                    Forms\Components\TextInput::make('modal_materi')
                                        ->label('Lingkup Materi Pokok (Opsional)')
                                        ->placeholder('Contoh: Operasi Bilangan Bulat, Aljabar, dll.'),

                                    Forms\Components\Textarea::make('modal_deskripsi_tp')
                                        ->label('Rumusan Tujuan Pembelajaran (TP)')
                                        ->placeholder('Contoh: Peserta didik dapat memahami dan menyelesaikan operasi hitung pecahan aljabar...')
                                        ->rows(4)
                                        ->required()
                                        ->helperText('Rumusan TP yang diturunkan langsung dari CP yang dipilih.'),
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
                        ])
                        ->columnSpanFull(),

                        // Menu Dropdown Capaian Pembelajaran & Alur Tujuan Pembelajaran Otomatis
                        Forms\Components\Select::make('capaian_pembelajaran_id')
                            ->label('1. Capaian Pembelajaran (CP) [Menu Dropdown]')
                            ->options(function () {
                                return \App\Models\CapaianPembelajaran::with('subject')->get()->mapWithKeys(function ($cp) {
                                    $mapel = $cp->subject?->name ?? 'Mapel';
                                    $cpKode = $cp->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                    return [$cp->id => "[{$cpKode}] [{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                    if ($cp) {
                                        $set('content.capaian_pembelajaran_id', $cp->id);
                                        $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                        $set('kelas_fase', "Kelas {$cp->tingkat} / Fase D");
                                        if ($cp->subject) {
                                            $set('mata_pelajaran', $cp->subject->name);
                                        }

                                        // Otomatis sinkronisasi ATP sesuai CP & mapel ini
                                        $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject(
                                            auth()->id(),
                                            $cp->subject_id,
                                            $cp->tingkat
                                        );
                                        if ($atp) {
                                            $set('alur_tujuan_pembelajaran_id', $atp->id);
                                        }
                                    }
                                }
                            })
                            ->helperText('Pilih CP yang telah disimpan atau klik tombol "+ Input & Simpan CP Baru" di atas untuk menambah.'),

                        Forms\Components\Select::make('alur_tujuan_pembelajaran_id')
                            ->label('2. Alur Tujuan Pembelajaran (ATP) [Otomatis Terakumulasi]')
                            ->options(function (Forms\Get $get) {
                                $guruId = auth()->id();
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $subjectId = null;
                                $tingkat = '7';
                                if ($cpId) {
                                    $cp = \App\Models\CapaianPembelajaran::find($cpId);
                                    $subjectId = $cp?->subject_id;
                                    $tingkat = $cp?->tingkat ?? '7';
                                }
                                if (!$subjectId) {
                                    $subjectId = \App\Models\TeacherSubjectClass::where('teacher_id', $guruId)->value('subject_id')
                                        ?? \App\Models\Subject::first()?->id;
                                }
                                if ($guruId && $subjectId) {
                                    \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject($guruId, $subjectId, $tingkat);
                                }

                                return \App\Models\AlurTujuanPembelajaran::where('guru_id', $guruId)->get()->mapWithKeys(function ($atp) {
                                    return [$atp->id => "[Otomatis] {$atp->judul_atp}"];
                                });
                            })
                            ->default(function (Forms\Get $get) {
                                $guruId = auth()->id();
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $subjectId = null;
                                $tingkat = '7';
                                if ($cpId) {
                                    $cp = \App\Models\CapaianPembelajaran::find($cpId);
                                    $subjectId = $cp?->subject_id;
                                    $tingkat = $cp?->tingkat ?? '7';
                                }
                                if (!$subjectId) {
                                    $subjectId = \App\Models\TeacherSubjectClass::where('teacher_id', $guruId)->value('subject_id')
                                        ?? \App\Models\Subject::first()?->id;
                                }
                                if ($guruId && $subjectId) {
                                    $atp = \App\Models\AlurTujuanPembelajaran::syncForTeacherAndSubject($guruId, $subjectId, $tingkat);
                                    return $atp?->id;
                                }
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('ATP diakumulasikan secara otomatis dari seluruh inputan TP oleh guru mapel yang bersangkutan.'),

                        Forms\Components\Select::make('selected_tp_ids')
                            ->label('3. Tujuan Pembelajaran (TP) Terkait [Menu Dropdown Multi-Select]')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                $query = \App\Models\TujuanPembelajaran::query();
                                if ($cpId && is_numeric($cpId)) {
                                    $query->where('capaian_pembelajaran_id', $cpId);
                                }
                                $tps = $query->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                });
                                return $tps->mapWithKeys(function ($tp) {
                                    $prefix = $tp->materi ? "({$tp->materi}) " : '';
                                    return [$tp->id => "[TP {$tp->kode_tp}] {$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 65)];
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
                            ->helperText('Pilih satu atau beberapa TP yang telah disimpan atau klik tombol "+ Input & Simpan TP Baru" di atas.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('kelas_fase')
                            ->label('Kelas / Fase')
                            ->placeholder('Contoh: VII / Fase D')
                            ->default('Kelas 7 / Fase D'),

                        Forms\Components\TextInput::make('mata_pelajaran')
                            ->label('Mata Pelajaran')
                            ->placeholder('Contoh: Bahasa Indonesia'),

                        Forms\Components\TextInput::make('topik')
                            ->label('Topik / Materi Pokok')
                            ->placeholder('Contoh: Teks Deskripsi'),

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
                                    ->label('1. Capaian Pembelajaran (CP) [Menu Dropdown]')
                                    ->options(function () {
                                        return \App\Models\CapaianPembelajaran::with('subject')->get()->mapWithKeys(function ($cp) {
                                            $mapel = $cp->subject?->name ?? 'Mapel';
                                            return [$cp->id => "[{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $cp = \App\Models\CapaianPembelajaran::find($state);
                                            if ($cp) {
                                                $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                                $set('capaian_pembelajaran_id', $cp->id);
                                            }
                                        }
                                    })
                                    ->helperText('Menu dropdown dari inputan Capaian Pembelajaran (CP) yang telah disimpan.'),

                                Forms\Components\Textarea::make('content.capaian_pembelajaran')
                                    ->label('Teks Rumusan CP yang Terpilih')
                                    ->rows(2)
                                    ->helperText('Menampilkan rumusan lengkap dari CP yang terpilih pada menu dropdown di atas.'),

                                Forms\Components\Textarea::make('content.lintas_disiplin')
                                    ->label('2. Lintas Disiplin Ilmu')
                                    ->rows(3),

                                Forms\Components\Textarea::make('content.topik_pembelajaran')
                                    ->label('4. Topik Pembelajaran')
                                    ->rows(2),

                                Forms\Components\Select::make('content.selected_tp_dropdown')
                                    ->label('3. Tujuan Pembelajaran (TP) [Menu Dropdown Multi-Select]')
                                    ->multiple()
                                    ->options(function (Forms\Get $get) {
                                        $cpId = $get('content.capaian_pembelajaran_id') ?? $get('capaian_pembelajaran_id');
                                        $query = \App\Models\TujuanPembelajaran::query();
                                        if ($cpId && is_numeric($cpId)) {
                                            $query->where('capaian_pembelajaran_id', $cpId);
                                        }
                                        return $query->get()->mapWithKeys(function ($tp) {
                                            $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                            return [$tp->id => "{$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 65)];
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!empty($state)) {
                                            $tps = \App\Models\TujuanPembelajaran::whereIn('id', $state)->get();
                                            $tpItems = $tps->map(function ($tp) {
                                                $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                                return ['rumusan' => "{$prefix}{$tp->deskripsi_tp}"];
                                            })->toArray();
                                            $set('selected_tp_ids', $state);
                                            $set('content.tujuan_pembelajaran', $tpItems);
                                        }
                                    })
                                    ->helperText('Menu dropdown dari inputan Tujuan Pembelajaran (TP) yang telah disimpan.'),

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
                                                Forms\Components\TextInput::make('kriteria')->label('Kriteria Penilaian')->columnSpanFull(),
                                                Forms\Components\Textarea::make('bimbingan')->label('Perlu Bimbingan (0-40)'),
                                                Forms\Components\Textarea::make('cukup')->label('Cukup (41-65)'),
                                                Forms\Components\Textarea::make('baik')->label('Baik (66-85)'),
                                                Forms\Components\Textarea::make('sangat_baik')->label('Sangat Baik (86-100)'),
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
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'published' => 'Selesai (Publikasi)',
                        'draft' => 'Draft (Simpan Sementara)',
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
                        'published' => 'Selesai (Publikasi)',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('continue')
                    ->label('Lanjutkan Pengisian')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->url(fn (Rppm $record): string => RppmResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (Rppm $record): bool => ($record->status ?? 'draft') === 'draft'),
                Tables\Actions\EditAction::make()
                    ->label(fn (Rppm $record) => ($record->status ?? 'draft') === 'draft' ? 'Edit Draft' : 'Edit'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'edit' => Pages\EditRppm::route('/{record}/edit'),
        ];
    }
}
