<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TujuanPembelajaranResource\Pages;
use App\Models\CapaianPembelajaran;
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

class TujuanPembelajaranResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Role khusus Guru Al-Qur'an: tidak ada akses dan tidak ada inputan TP
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

    protected static ?string $model = TujuanPembelajaran::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Tujuan Pembelajaran (TP)';
    protected static ?string $pluralModelLabel = 'Tujuan Pembelajaran (TP)';
    protected static ?string $navigationLabel = '2. Tujuan Pembelajaran (TP)';
    protected static ?int $navigationSort = 2;

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
                Forms\Components\Section::make('🎯 Rujukan Capaian Pembelajaran (CP)')
                    ->description('Tujuan Pembelajaran (TP) diturunkan dari Capaian Pembelajaran (CP) dan berlaku selama 1 semester hingga 1 tahun.')
                    ->schema([
                        Forms\Components\Hidden::make('guru_id')
                            ->default(fn () => Auth::id()),

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
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('capaian_pembelajaran_id', null)),

                        Forms\Components\Select::make('capaian_pembelajaran_id')
                            ->label('Rujukan Capaian Pembelajaran (CP)')
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $query = CapaianPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                return $query->orderBy('tingkat')->orderBy('id')->get()->mapWithKeys(function ($cp) {
                                    $kode = $cp->kode_cp ? "[CP {$cp->kode_cp}] " : '';
                                    $label = "{$kode}[Kelas {$cp->tingkat} - {$cp->elemen}] " . \Illuminate\Support\Str::limit($cp->deskripsi_cp, 60);
                                    return [$cp->id => $label];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->helperText('Pilih CP asal yang menjadi payung dari Tujuan Pembelajaran ini.')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $cp = CapaianPembelajaran::find($state);
                                    if ($cp) {
                                        $set('kelas_tingkat', $cp->tingkat);
                                        $set('materi', $cp->elemen);

                                        $cpKode = $cp->kode_cp ?: '1';
                                        $existingCount = TujuanPembelajaran::where('capaian_pembelajaran_id', $cp->id)->count();
                                        $nextNum = $existingCount + 1;
                                        $set('kode_tp', "{$cpKode}.{$nextNum}");
                                    }
                                }
                            }),

                        Forms\Components\Select::make('kelas_tingkat')
                            ->label('Tingkat Kelas')
                            ->options([
                                '7' => 'Kelas 7',
                                '8' => 'Kelas 8',
                                '9' => 'Kelas 9',
                            ])
                            ->default('7')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester Berlaku')
                            ->options([
                                'Ganjil'  => 'Semester Ganjil',
                                'Genap'   => 'Semester Genap',
                                '1 Tahun' => '1 Tahun Penuh',
                            ])
                            ->default('Ganjil')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('📝 Rumusan Tujuan Pembelajaran (TP)')
                    ->schema([
                        Forms\Components\TextInput::make('kode_tp')
                            ->label('Kode TP')
                            ->placeholder('Contoh: 1.1, 1.2, 1.5, 2.1')
                            ->helperText('Format: {Kode CP}.{Nomor TP}. Contoh: TP ke-1 pada CP 1 adalah 1.1, TP ke-5 adalah 1.5.')
                            ->required(),

                        Forms\Components\TextInput::make('materi')
                            ->label('Materi Pokok / Bahasan')
                            ->placeholder('Contoh: Bilangan Bulat dan Pecahan')
                            ->required(),

                        Forms\Components\Textarea::make('deskripsi_tp')
                            ->label('Rumusan Tujuan Pembelajaran (TP)')
                            ->placeholder("Contoh:\nMurid dapat mengidentifikasi konsep bilangan bulat positif dan negatif serta melakukan operasi penjumlahan dan pengurangan secara benar dalam konteks pemecahan masalah.")
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_tp')
                    ->label('Kode TP')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kelas_tingkat')
                    ->label('Kelas')
                    ->formatStateUsing(fn ($state) => 'Kelas ' . $state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materi')
                    ->label('Materi Pokok')
                    ->weight('bold')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('deskripsi_tp')
                    ->label('Rumusan TP')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->badge(),

                Tables\Columns\TextColumn::make('capaianPembelajaran.elemen')
                    ->label('Elemen CP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mapel'),

                Tables\Filters\SelectFilter::make('kelas_tingkat')
                    ->options([
                        '7' => 'Kelas 7',
                        '8' => 'Kelas 8',
                        '9' => 'Kelas 9',
                    ]),

                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_rekap_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (): string => route('academic.tp.rekap.pdf'))
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
            ->defaultSort('kode_tp', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTujuanPembelajarans::route('/'),
        ];
    }
}
