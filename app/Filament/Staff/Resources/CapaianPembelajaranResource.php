<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\CapaianPembelajaranResource\Pages;
use App\Models\CapaianPembelajaran;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CapaianPembelajaranResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Role khusus Guru Al-Qur'an: tidak ada akses dan tidak ada inputan CP
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

    protected static ?string $model = CapaianPembelajaran::class;
    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Capaian Pembelajaran (CP)';
    protected static ?string $pluralModelLabel = 'Capaian Pembelajaran (CP)';
    protected static ?string $navigationLabel = '1. Capaian Pembelajaran (CP)';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Akademik & Kurikulum';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        // Guru Mapel hanya melihat CP miliknya atau mapel yang diajarkannya
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
                Forms\Components\Section::make('📌 Identitas Capaian Pembelajaran (CP)')
                    ->description('Capaian Pembelajaran diinput pada awal tahun/semester dan berlaku selama 1 semester hingga 1 tahun pembelajaran.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\TextInput::make('kode_cp')
                            ->label('Kode CP')
                            ->placeholder('Contoh: 1, 2, dst. (Otomatis)')
                            ->helperText('Nomor urut CP (CP ke-1 berkode 1, CP ke-2 berkode 2, dst.). Otomatis terisi urutan berikutnya jika dikosongkan.')
                            ->nullable(),

                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->options(function () use ($isPrivileged, $user) {
                                if ($isPrivileged) {
                                    return Subject::akademik()->orderBy('name')->pluck('name', 'id');
                                }
                                $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();
                                $assignedAkademik = Subject::akademik()->whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                                if ($assignedAkademik->isNotEmpty()) {
                                    return $assignedAkademik;
                                }
                                return Subject::akademik()->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state && empty($get('kode_cp'))) {
                                    $guruId = $get('guru_id') ?? Auth::id();
                                    $tingkat = $get('tingkat') ?? '7';
                                    $count = CapaianPembelajaran::where('guru_id', $guruId)->where('subject_id', $state)->where('tingkat', $tingkat)->count() + 1;
                                    $set('kode_cp', (string) $count);
                                }
                            }),

                        Forms\Components\Select::make('tingkat')
                            ->label('Tingkat Kelas')
                            ->options([
                                '7' => 'Kelas 7 (Fase D)',
                                '8' => 'Kelas 8 (Fase D)',
                                '9' => 'Kelas 9 (Fase D)',
                            ])
                            ->default('7')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                if ($subjectId && empty($get('kode_cp'))) {
                                    $guruId = $get('guru_id') ?? Auth::id();
                                    $count = CapaianPembelajaran::where('guru_id', $guruId)->where('subject_id', $subjectId)->where('tingkat', $state)->count() + 1;
                                    $set('kode_cp', (string) $count);
                                }
                            }),

                        Forms\Components\TextInput::make('fase')
                            ->label('Fase Kurikulum')
                            ->default('Fase D (SMP)')
                            ->required(),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->placeholder('Contoh: 2026/2027')
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('masa_berlaku')
                            ->label('Masa Berlaku CP')
                            ->options([
                                '1 Tahun'    => '1 Tahun Pembelajaran Penuh',
                                '1 Semester' => '1 Semester Pembelajaran',
                            ])
                            ->default('1 Tahun')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester Efektif')
                            ->options([
                                '1 Tahun' => 'Sepanjang Tahun (Semester 1 & 2)',
                                '1'       => 'Semester 1 (Ganjil)',
                                '2'       => 'Semester 2 (Genap)',
                            ])
                            ->default('1 Tahun')
                            ->required(),

                        Forms\Components\TextInput::make('elemen')
                            ->label('Elemen / Lingkup Materi')
                            ->placeholder('Contoh: Bilangan, Aljabar, Geometri, Pemahaman Konsep, Membaca-Memirsa')
                            ->helperText('Elemen mata pelajaran sesuai Standar Kurikulum Merdeka')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('deskripsi_cp')
                            ->label('Rumusan Capaian Pembelajaran (CP)')
                            ->placeholder("Contoh:\nPada akhir fase D, murid dapat memahami relasi dan fungsi serta menyelesaikannya dalam pemecahan masalah nyata. Murid mampu melakukan operasi hitung aljabar dan mengaplikasikannya pada kehidupan sehari-hari secara kritis dan mandiri.")
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_cp')
                    ->label('Kode CP')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state ? "CP {$state}" : '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn ($state) => 'Kelas ' . $state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('elemen')
                    ->label('Elemen Pembelajaran')
                    ->weight('bold')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('deskripsi_cp')
                    ->label('Rumusan CP')
                    ->limit(90)
                    ->wrap(),

                Tables\Columns\TextColumn::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guru.name')
                    ->label('Guru Pengampu')
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
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (CapaianPembelajaran $record): string => route('academic.cp.pdf', $record))
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
            'index' => Pages\ManageCapaianPembelajarans::route('/'),
        ];
    }
}
