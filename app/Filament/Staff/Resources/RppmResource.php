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
                Forms\Components\Section::make('Informasi Dasar & Keterhubungan Perangkat Ajar')
                    ->description('RPPM terhubung dengan Perangkat Ajar awal (CP, TP, dan ATP) yang telah dibuat.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => auth()->id()),

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
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                    if ($cp) {
                                        $set('content.capaian_pembelajaran', $cp->deskripsi_cp);
                                        $set('kelas_fase', "Kelas {$cp->tingkat} / {$cp->fase}");
                                        if ($cp->subject) {
                                            $set('mata_pelajaran', $cp->subject->name);
                                        }
                                    }
                                }
                            })
                            ->helperText('Pilih Capaian Pembelajaran dari Perangkat Ajar. Rumusan CP dan identitas akan terisi otomatis.'),

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
                            ->helperText('Pilih ATP untuk menghubungkan alur capaian pembelajaran yang diakumulasikan.'),

                        Forms\Components\Select::make('selected_tp_ids')
                            ->label('3. Pilih Tujuan Pembelajaran (TP) Terkait')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $cpId = $get('capaian_pembelajaran_id');
                                $query = \App\Models\TujuanPembelajaran::query();
                                if ($cpId) {
                                    $query->where('capaian_pembelajaran_id', $cpId);
                                }
                                return $query->orderBy('kode_tp')->get()->mapWithKeys(function ($tp) {
                                    return [$tp->id => "[{$tp->kode_tp}] {$tp->materi} - " . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 50)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!empty($state)) {
                                    $tps = \App\Models\TujuanPembelajaran::whereIn('id', $state)->get();
                                    $tpItems = $tps->map(fn ($tp) => ['rumusan' => "[{$tp->kode_tp}] {$tp->deskripsi_tp}"])->toArray();
                                    $set('content.tujuan_pembelajaran', $tpItems);
                                }
                            })
                            ->helperText('Pilih satu atau beberapa TP yang akan dicapai pada pertemuan RPPM ini.')
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
                                Forms\Components\Textarea::make('content.capaian_pembelajaran')->label('1. Capaian Pembelajaran (CP)')->rows(3),
                                Forms\Components\Textarea::make('content.lintas_disiplin')->label('2. Lintas Disiplin Ilmu')->rows(3),
                                Forms\Components\Textarea::make('content.topik_pembelajaran')->label('4. Topik Pembelajaran')->rows(2),
                                
                                Forms\Components\Fieldset::make('3. Tujuan Pembelajaran')
                                    ->schema([
                                        Forms\Components\Repeater::make('content.tujuan_pembelajaran')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\TextInput::make('rumusan')->label('Rumusan Tujuan Pembelajaran')->required(),
                                            ])->defaultItems(1)
                                    ]),
                                    
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
