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
        return auth()->user()->hasAnyRole(['guru_mapel', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
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

                        Forms\Components\Radio::make('input_perangkat_mode')
                            ->label('Pilihan Pengisian Perangkat Ajar')
                            ->options([
                                'input_baru' => 'Input CP & TP Baru Langsung di Sini',
                                'pilih_ada'  => 'Pilih dari CP & TP yang Sudah Pernah Diinput',
                            ])
                            ->default('input_baru')
                            ->live()
                            ->inline()
                            ->columnSpanFull()
                            ->helperText('Di awal semester atau topik baru, Anda dapat langsung menginputkan CP & TP baru di sini. Untuk pertemuan berikutnya, Anda bisa memilih dari CP & TP yang sudah ada.'),

                        // 1. Form Input CP & TP Baru Langsung
                        Forms\Components\Group::make([
                            Forms\Components\Fieldset::make('1. Input Capaian Pembelajaran (CP) Baru')
                                ->schema([
                                    Forms\Components\TextInput::make('new_cp_elemen')
                                        ->label('Elemen / Lingkup Materi CP')
                                        ->placeholder('Contoh: Bilangan, Aljabar, Geometri, Pengukuran')
                                        ->live(),

                                    Forms\Components\Select::make('new_cp_masa_berlaku')
                                        ->label('Masa Berlaku CP')
                                        ->options([
                                            '1 Tahun'    => '1 Tahun Ajaran (2 Semester)',
                                            '1 Semester' => '1 Semester',
                                        ])
                                        ->default('1 Tahun'),

                                    Forms\Components\Textarea::make('new_cp_deskripsi')
                                        ->label('Deskripsi / Rumusan Capaian Pembelajaran (CP)')
                                        ->placeholder('Contoh: Peserta didik mampu memahami konsep bilangan bulat dan operasi aljabar...')
                                        ->rows(3)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $set('content.capaian_pembelajaran_id', 'new');
                                                $set('content.capaian_pembelajaran', $state);
                                            }
                                        })
                                        ->columnSpanFull()
                                        ->helperText('CP berlaku antara 1 semester hingga 1 tahun pembelajaran.'),
                                ])->columns(2),

                            Forms\Components\Fieldset::make('2. Input Tujuan Pembelajaran (TP) yang Diturunkan dari CP')
                                ->schema([
                                    Forms\Components\Repeater::make('new_tps')
                                        ->label('Daftar Rumusan TP (Bisa tambah beberapa TP sekaligus)')
                                        ->schema([
                                            Forms\Components\TextInput::make('kode_tp')
                                                ->label('Kode TP')
                                                ->placeholder('Contoh: TP.7.1.1')
                                                ->required(),
                                            Forms\Components\TextInput::make('materi')
                                                ->label('Materi Pokok')
                                                ->placeholder('Contoh: Operasi Bilangan')
                                                ->required(),
                                            Forms\Components\Textarea::make('deskripsi_tp')
                                                ->label('Rumusan Tujuan Pembelajaran (TP)')
                                                ->placeholder('Contoh: Peserta didik dapat menyelesaikan operasi perkalian...')
                                                ->rows(2)
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2)
                                        ->defaultItems(1)
                                        ->addActionLabel('+ Tambah TP Baru')
                                        ->live()
                                        ->columnSpanFull()
                                        ->helperText('TP yang diinputkan di sini otomatis tersimpan dan juga terhubung ke fitur Input Nilai Siswa.'),
                                ]),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('input_perangkat_mode') === 'input_baru')
                        ->columnSpanFull(),

                        // 2. Form Pilih CP & TP yang Sudah Pernah Diinput
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('capaian_pembelajaran_id')
                                ->label('1. Rujukan Capaian Pembelajaran (CP)')
                                ->options(function () {
                                    return \App\Models\CapaianPembelajaran::with('subject')->get()->mapWithKeys(function ($cp) {
                                        $mapel = $cp->subject?->name ?? 'Mapel';
                                        return [$cp->id => "[{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('elemen')->label('Elemen CP')->placeholder('Contoh: Aljabar')->required(),
                                    Forms\Components\Textarea::make('deskripsi_cp')->label('Deskripsi CP')->required()->rows(3),
                                    Forms\Components\Select::make('masa_berlaku')->options(['1 Tahun' => '1 Tahun', '1 Semester' => '1 Semester'])->default('1 Tahun')->required(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    $cp = \App\Models\CapaianPembelajaran::create([
                                        'institution_id' => auth()->user()?->institution_id,
                                        'guru_id' => auth()->id(),
                                        'subject_id' => \App\Models\Subject::first()?->id,
                                        'fase' => 'Fase D (SMP)',
                                        'tingkat' => '7',
                                        'tahun_ajaran' => '2026/2027',
                                        'semester' => '1',
                                        'masa_berlaku' => $data['masa_berlaku'] ?? '1 Tahun',
                                        'elemen' => $data['elemen'],
                                        'deskripsi_cp' => $data['deskripsi_cp'],
                                    ]);
                                    return $cp->id;
                                })
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                        if ($cp) {
                                            $set('content.capaian_pembelajaran_id', $cp->id);
                                            $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                            $set('kelas_fase', "Kelas {$cp->tingkat} / {$cp->fase}");
                                            if ($cp->subject) {
                                                $set('mata_pelajaran', $cp->subject->name);
                                            }
                                        }
                                    }
                                })
                                ->helperText('Pilih Capaian Pembelajaran yang telah dibuat sebelumnya.')
                                ->columnSpan(1),

                            Forms\Components\Select::make('alur_tujuan_pembelajaran_id')
                                ->label('2. Rujukan Alur Tujuan Pembelajaran (ATP)')
                                ->options(function () {
                                    return \App\Models\AlurTujuanPembelajaran::with('subject')->get()->mapWithKeys(function ($atp) {
                                        $mapel = $atp->subject?->name ?? 'Mapel';
                                        return [$atp->id => "[{$mapel} - Kelas {$atp->tingkat}] {$atp->judul_atp}"];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->helperText('Pilih ATP untuk menghubungkan alur tujuan pembelajaran.')
                                ->columnSpan(1),

                            Forms\Components\Select::make('selected_tp_ids')
                                ->label('3. Pilih Tujuan Pembelajaran (TP) Terkait')
                                ->multiple()
                                ->options(function (Forms\Get $get) {
                                    $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                    $query = \App\Models\TujuanPembelajaran::query();
                                    if ($cpId && is_numeric($cpId)) {
                                        $query->where('capaian_pembelajaran_id', $cpId);
                                    }
                                    return $query->orderBy('kode_tp')->get()->mapWithKeys(function ($tp) {
                                        return [$tp->id => "[{$tp->kode_tp}] {$tp->materi} - " . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 50)];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('kode_tp')->label('Kode TP')->required(),
                                    Forms\Components\TextInput::make('materi')->label('Materi Pokok')->required(),
                                    Forms\Components\Textarea::make('deskripsi_tp')->label('Rumusan TP')->required()->rows(2),
                                ])
                                ->createOptionUsing(function (array $data, Forms\Get $get) {
                                    $cpId = $get('capaian_pembelajaran_id') ?? $get('content.capaian_pembelajaran_id');
                                    $tp = \App\Models\TujuanPembelajaran::create([
                                        'institution_id' => auth()->user()?->institution_id,
                                        'guru_id' => auth()->id(),
                                        'capaian_pembelajaran_id' => (is_numeric($cpId) ? $cpId : null) ?? \App\Models\CapaianPembelajaran::first()?->id,
                                        'subject_id' => \App\Models\Subject::first()?->id,
                                        'kode_tp' => $data['kode_tp'],
                                        'deskripsi_tp' => $data['deskripsi_tp'],
                                        'materi' => $data['materi'],
                                        'kelas_tingkat' => '7',
                                        'semester' => 'Ganjil',
                                    ]);
                                    return $tp->id;
                                })
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if (!empty($state)) {
                                        $tps = \App\Models\TujuanPembelajaran::whereIn('id', $state)->get();
                                        $tpItems = $tps->map(fn ($tp) => ['rumusan' => "[{$tp->kode_tp}] {$tp->deskripsi_tp}"])->toArray();
                                        $set('content.tujuan_pembelajaran', $tpItems);
                                        $set('content.selected_tp_dropdown', $state);
                                    }
                                })
                                ->helperText('Pilih satu atau beberapa TP yang akan dicapai pada pertemuan RPPM ini.')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('input_perangkat_mode') === 'pilih_ada')
                        ->columns(2)
                        ->columnSpanFull(),

                        Forms\Components\TextInput::make('kelas_fase')
                            ->label('Kelas / Fase')
                            ->placeholder('Contoh: VII / Fase D')
                            ->required(),

                        Forms\Components\TextInput::make('mata_pelajaran')
                            ->label('Mata Pelajaran')
                            ->required(),

                        Forms\Components\TextInput::make('topik')
                            ->label('Topik / Materi Pokok')
                            ->required(),

                        Forms\Components\TextInput::make('alokasi_waktu')
                            ->label('Alokasi Waktu')
                            ->placeholder('Contoh: 2 x 40 Menit (1 Pertemuan)')
                            ->required(),
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
                                                Forms\Components\TextInput::make('dimensi')->label('Dimensi')->required(),
                                                Forms\Components\Textarea::make('penjelasan')->label('Penjelasan Keterkaitan')->required(),
                                            ])->columns(2)->defaultItems(1)
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('B. Desain Pembelajaran')
                            ->schema([
                                Forms\Components\Select::make('content.capaian_pembelajaran_id')
                                    ->label('1. Capaian Pembelajaran (CP) [Menu Dropdown]')
                                    ->options(function (Forms\Get $get) {
                                        $options = [];
                                        $newCpDeskripsi = $get('new_cp_deskripsi');
                                        $newCpElemen = $get('new_cp_elemen');
                                        if ($newCpDeskripsi) {
                                            $options['new'] = "[CP Baru Diinputkan] {$newCpElemen} - " . \Illuminate\Support\Str::limit($newCpDeskripsi, 60);
                                        }
                                        $existing = \App\Models\CapaianPembelajaran::with('subject')->get()->mapWithKeys(function ($cp) {
                                            $mapel = $cp->subject?->name ?? 'Mapel';
                                            return [$cp->id => "[{$mapel} - Kelas {$cp->tingkat}] {$cp->elemen} - " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                        })->toArray();

                                        return $options + $existing;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state === 'new') {
                                            $set('content.capaian_pembelajaran', $get('new_cp_deskripsi'));
                                        } elseif ($state) {
                                            $cp = \App\Models\CapaianPembelajaran::find($state);
                                            if ($cp) {
                                                $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                                $set('capaian_pembelajaran_id', $cp->id);
                                            }
                                        }
                                    })
                                    ->helperText('Menu dropdown dari inputan Capaian Pembelajaran (CP) yang telah dibuat.'),

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
                                    ->label('3. Tujuan Pembelajaran (TP) [Menu Dropdown]')
                                    ->multiple()
                                    ->options(function (Forms\Get $get) {
                                        $options = [];
                                        $newTps = $get('new_tps');
                                        if (!empty($newTps) && is_array($newTps)) {
                                            foreach ($newTps as $idx => $item) {
                                                if (!empty($item['kode_tp']) && !empty($item['deskripsi_tp'])) {
                                                    $options["new_{$idx}"] = "[TP Baru] [{$item['kode_tp']}] {$item['materi']} - " . \Illuminate\Support\Str::limit($item['deskripsi_tp'], 45);
                                                }
                                            }
                                        }

                                        $cpId = $get('content.capaian_pembelajaran_id') ?? $get('capaian_pembelajaran_id');
                                        $query = \App\Models\TujuanPembelajaran::query();
                                        if ($cpId && is_numeric($cpId)) {
                                            $query->where('capaian_pembelajaran_id', $cpId);
                                        }
                                        $existing = $query->orderBy('kode_tp')->get()->mapWithKeys(function ($tp) {
                                            return [$tp->id => "[{$tp->kode_tp}] {$tp->materi} - " . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 50)];
                                        })->toArray();

                                        return $options + $existing;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if (!empty($state)) {
                                            $items = [];
                                            $numericIds = [];
                                            $newTps = $get('new_tps') ?? [];
                                            foreach ($state as $val) {
                                                if (str_starts_with($val, 'new_')) {
                                                    $idx = (int) str_replace('new_', '', $val);
                                                    if (isset($newTps[$idx])) {
                                                        $items[] = ['rumusan' => "[{$newTps[$idx]['kode_tp']}] {$newTps[$idx]['deskripsi_tp']}"];
                                                    }
                                                } elseif (is_numeric($val)) {
                                                    $numericIds[] = (int) $val;
                                                }
                                            }
                                            if (!empty($numericIds)) {
                                                $tps = \App\Models\TujuanPembelajaran::whereIn('id', $numericIds)->get();
                                                foreach ($tps as $tp) {
                                                    $items[] = ['rumusan' => "[{$tp->kode_tp}] {$tp->deskripsi_tp}"];
                                                }
                                                $set('selected_tp_ids', $numericIds);
                                            }
                                            $set('content.tujuan_pembelajaran', $items);
                                        }
                                    })
                                    ->helperText('Menu dropdown dari inputan Tujuan Pembelajaran (TP) yang telah dibuat.'),

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
                                            ])->required(),
                                        Forms\Components\TextInput::make('waktu')
                                            ->label('Alokasi Waktu')
                                            ->placeholder('Contoh: 15 menit')
                                            ->required(),
                                        Forms\Components\Textarea::make('prinsip')
                                            ->label('Prinsip Pembelajaran Mendalam')
                                            ->columnSpanFull(),
                                        Forms\Components\RichEditor::make('kegiatan')
                                            ->label('Uraian Kegiatan')
                                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                                            ->columnSpanFull(),
                                    ])->columns(2)->defaultItems(1)
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
                                                    ])->required(),
                                                Forms\Components\TextInput::make('waktu')->label('Waktu Pelaksanaan'),
                                                Forms\Components\TextInput::make('teknik')->label('Teknik'),
                                                Forms\Components\TextInput::make('instrumen')->label('Instrumen'),
                                                Forms\Components\Textarea::make('tujuan')->label('Tujuan')->columnSpanFull(),
                                            ])->columns(2)->defaultItems(1)
                                    ]),
                                    
                                Forms\Components\Fieldset::make('Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)')
                                    ->schema([
                                        Forms\Components\Repeater::make('content.kktp')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('kriteria')->label('Kriteria Penilaian')->required()->columnSpanFull(),
                                                Forms\Components\Textarea::make('bimbingan')->label('Perlu Bimbingan (0-40)'),
                                                Forms\Components\Textarea::make('cukup')->label('Cukup (41-65)'),
                                                Forms\Components\Textarea::make('baik')->label('Baik (66-85)'),
                                                Forms\Components\Textarea::make('sangat_baik')->label('Sangat Baik (86-100)'),
                                            ])->columns(2)->defaultItems(1)
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
