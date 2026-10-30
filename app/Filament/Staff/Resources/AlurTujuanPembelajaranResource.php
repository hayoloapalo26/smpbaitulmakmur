<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource\Pages;
use App\Models\AlurTujuanPembelajaran;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AlurTujuanPembelajaranResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Role khusus Guru Al-Qur'an: tidak ada akses dan tidak ada inputan ATP
        if ($user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole([
            'guru_mapel',
            'guru_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]);
    }

    protected static ?string $model = AlurTujuanPembelajaran::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Alur Tujuan Pembelajaran (ATP)';
    protected static ?string $pluralModelLabel = 'Alur Tujuan Pembelajaran (ATP)';
    protected static ?string $navigationLabel = '3. Alur Tujuan Pembelajaran (ATP)';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Bab 2: Perencanaan Administrasi Pembelajaran';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();
        return $query->where(function ($q) use ($user, $subjectIds) {
            $q->where('guru_id', $user->id)
              ->orWhereIn('subject_id', $subjectIds);
        });
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isPrivileged = $user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']);

        return $form
            ->schema([
                Forms\Components\Section::make('🗺️ Identitas Alur Tujuan Pembelajaran (ATP)')
                    ->description('ATP adalah akumulasi dan urutan sistematis dari Tujuan Pembelajaran (TP) untuk memetakan alur belajar siswa.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\TextInput::make('kode_atp')
                            ->label('Kode ATP')
                            ->placeholder('Contoh: 1, 2, dst. (Otomatis)')
                            ->helperText('Kode urut ATP (ATP 1, ATP 2, dst.). Otomatis terisi jika dikosongkan.')
                            ->nullable(),

                        Forms\Components\Select::make('capaian_pembelajaran_id')
                            ->label('Rujukan Capaian Pembelajaran (CP)')
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $tingkat = $get('tingkat');
                                $query = \App\Models\CapaianPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                if ($tingkat) {
                                    $query->where('tingkat', $tingkat);
                                }
                                return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                    $kode = $cp->kode_cp ? "[CP {$cp->kode_cp}] " : '';
                                    return [$cp->id => "{$kode}[Kelas {$cp->tingkat} - {$cp->elemen}] " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $cp = \App\Models\CapaianPembelajaran::with('subject')->find($state);
                                    if ($cp) {
                                        $set('tingkat', $cp->tingkat);
                                        $set('kode_atp', $cp->kode_cp ?: '1');
                                        $subjectName = $cp->subject?->name ?? 'Mapel';
                                        $set('judul_atp', "Alur Tujuan Pembelajaran (ATP {$cp->kode_cp}) - {$subjectName} Kelas {$cp->tingkat} [CP {$cp->kode_cp}]");

                                        $tps = \App\Models\TujuanPembelajaran::where('capaian_pembelajaran_id', $cp->id)->pluck('id')->toArray();
                                        if (!empty($tps)) {
                                            $set('tp_ids', $tps);
                                        }
                                    }
                                }
                            })
                            ->helperText('Pilih rujukan CP untuk ATP ini (Otomatis mengisi Kode ATP dan TP terkait).'),

                        Forms\Components\TextInput::make('judul_atp')
                            ->label('Judul / Dokumen ATP')
                            ->placeholder('Contoh: Alur Tujuan Pembelajaran Matematika Kelas 7 Semester Ganjil')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->options(function () use ($isPrivileged, $user) {
                                if ($isPrivileged) {
                                    return Subject::orderBy('name')->pluck('name', 'id');
                                }
                                $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();
                                return Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('tingkat')
                            ->label('Tingkat Kelas')
                            ->options([
                                '7' => 'Kelas 7',
                                '8' => 'Kelas 8',
                                '9' => 'Kelas 9',
                            ])
                            ->default('7')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil'  => 'Semester Ganjil',
                                'Genap'   => 'Semester Genap',
                                '1 Tahun' => '1 Tahun Penuh',
                            ])
                            ->default('Ganjil')
                            ->required(),

                        Forms\Components\Select::make('tp_ids')
                            ->label('Akumulasi TP yang Tercakup dalam ATP Ini')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $tingkat = $get('tingkat');
                                $query = TujuanPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                if ($tingkat) {
                                    $query->where('kelas_tingkat', $tingkat);
                                }
                                $tps = $query->with('capaianPembelajaran')->get()->sort(function ($a, $b) {
                                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                                });
                                return $tps->mapWithKeys(function ($tp) {
                                    $cp = $tp->capaianPembelajaran;
                                    $cpKode = $cp?->kode_cp ? "CP {$cp->kode_cp}" : "CP";
                                    $materi = $tp->materi ? "({$tp->materi}) " : '';
                                    return [$tp->id => "[TP {$tp->kode_tp}] [{$cpKode}] {$materi}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 60)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Pilih TP-TP yang akan diakumulasikan dan diurutkan dalam alur pembelajaran ini.')
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('📋 Kumpulan Seluruh Tujuan Pembelajaran (TP) Terakumulasi')
                    ->description('Daftar seluruh TP yang diakumulasikan dalam ATP ini sesuai kode urutan CP dan TP.')
                    ->schema([
                        Forms\Components\ViewField::make('accumulated_tp_preview')
                            ->label('')
                            ->view('filament.staff.components.atp-accumulated-tp-table')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('🪜 Penyusunan Alur & Tahapan Pembelajaran')
                    ->description('Susun tahapan alur secara runut, alokasi jam pelajaran (JP), dan materi esensial.')
                    ->schema([
                        Forms\Components\Repeater::make('urutan_alur')
                            ->label('Tahapan Alur Pembelajaran')
                            ->schema([
                                Forms\Components\TextInput::make('kode_tp')
                                    ->label('Kode TP')
                                    ->placeholder('1.1')
                                    ->helperText('Contoh: 1.1, 1.2, 2.1'),

                                Forms\Components\TextInput::make('tahap')
                                    ->label('Tahap / Pekan')
                                    ->placeholder('Contoh: Tahap 1 [TP 1.1]')
                                    ->required(),

                                Forms\Components\TextInput::make('materi')
                                    ->label('Lingkup Materi Pokok')
                                    ->placeholder('Contoh: Operasi Bilangan Bulat')
                                    ->required(),

                                Forms\Components\TextInput::make('alokasi_jp')
                                    ->label('Alokasi Waktu (JP)')
                                    ->numeric()
                                    ->placeholder('Contoh: 2')
                                    ->suffix('JP')
                                    ->required(),

                                Forms\Components\Textarea::make('catatan_alur')
                                    ->label('Deskripsi Alur & Asesmen Pendukung')
                                    ->placeholder('Jelaskan tujuan tahapan ini dan metode asesmen formatif yang digunakan...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('total_alokasi_jp')
                            ->label('Total Alokasi Waktu (JP)')
                            ->numeric()
                            ->suffix('JP')
                            ->placeholder('Contoh: 72'),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan Rasional Alur')
                            ->placeholder('Catatan rasional mengapa urutan TP disusun sedemikian rupa...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_atp')
                    ->label('Kode ATP')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? "ATP {$state}" : '-')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('capaianPembelajaran.kode_cp')
                    ->label('Rujukan CP')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? "CP {$state}" : 'Semua CP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('judul_atp')
                    ->label('Judul ATP')
                    ->weight('bold')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn ($state) => 'Kelas ' . $state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tp_count')
                    ->label('Total TP Terakumulasi')
                    ->state(fn (AlurTujuanPembelajaran $record) => count($record->tp_ids ?? []) . ' TP')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('tp_codes_summary')
                    ->label('Daftar Kode TP')
                    ->badge()
                    ->color('primary')
                    ->limit(35)
                    ->tooltip(fn (AlurTujuanPembelajaran $record) => $record->tp_codes_summary),

                Tables\Columns\TextColumn::make('total_alokasi_jp')
                    ->label('Total JP')
                    ->suffix(' JP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->badge(),

                Tables\Columns\TextColumn::make('guru.name')
                    ->label('Penyusun')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mapel'),

                Tables\Filters\SelectFilter::make('tingkat')
                    ->options([
                        '7' => 'Kelas 7',
                        '8' => 'Kelas 8',
                        '9' => 'Kelas 9',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF ATP')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (AlurTujuanPembelajaran $record): string => route('academic.atp.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAlurTujuanPembelajarans::route('/'),
        ];
    }
}
