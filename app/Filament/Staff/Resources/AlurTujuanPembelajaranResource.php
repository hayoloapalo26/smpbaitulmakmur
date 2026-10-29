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
        return auth()->user()?->hasAnyRole(array_merge([
            'guru_mapel',
            'guru_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ], \App\Models\User::QURAN_TEACHER_ROLES)) ?? false;
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
                                return $query->orderBy('kode_tp')->get()->mapWithKeys(function ($tp) {
                                    return [$tp->id => "[{$tp->kode_tp}] {$tp->materi} - " . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 55)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih TP-TP yang akan diakumulasikan dan diurutkan dalam alur pembelajaran ini.')
                            ->columnSpanFull()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('🪜 Penyusunan Alur & Tahapan Pembelajaran')
                    ->description('Susun tahapan alur secara runut, alokasi jam pelajaran (JP), dan materi esensial.')
                    ->schema([
                        Forms\Components\Repeater::make('urutan_alur')
                            ->label('Tahapan Alur Pembelajaran')
                            ->schema([
                                Forms\Components\TextInput::make('tahap')
                                    ->label('Tahap / Pekan')
                                    ->placeholder('Contoh: Alur 1 / Pekan 1-3')
                                    ->required(),

                                Forms\Components\TextInput::make('materi')
                                    ->label('Lingkup Materi Pokok')
                                    ->placeholder('Contoh: Operasi Bilangan Bulat')
                                    ->required(),

                                Forms\Components\TextInput::make('alokasi_jp')
                                    ->label('Alokasi Waktu (JP)')
                                    ->numeric()
                                    ->placeholder('Contoh: 10')
                                    ->suffix('JP')
                                    ->required(),

                                Forms\Components\Textarea::make('catatan_alur')
                                    ->label('Deskripsi Alur & Asesmen Pendukung')
                                    ->placeholder('Jelaskan tujuan tahapan ini dan metode asesmen formatif yang digunakan...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
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
