<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\TeacherObservationResource\Pages;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Subject;
use App\Models\TeacherObservation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeacherObservationResource extends Resource
{
    protected static ?string $model = TeacherObservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Observasi Pembelajaran';
    protected static ?string $modelLabel = 'Observasi Pembelajaran Guru';
    protected static ?string $pluralModelLabel = 'Observasi Pembelajaran Guru';
    protected static ?string $slug = 'observasi-guru';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
        ]) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
        ]) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
        ]) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ]) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['teacher', 'observer', 'institution', 'subject', 'classroom'])
            ->latest('tanggal_observasi');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Guru & Pelaksanaan Observasi')
                    ->description('Pilih guru yang diobservasi, jadwal pelaksanaan, dan kelas pembelajaran.')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga')
                            ->relationship('institution', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('id', '!=', 1))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('teacher_id', null);
                                $set('mata_pelajaran_id', null);
                                $set('classroom_id', null);
                            }),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru yang Diobservasi')
                            ->options(function (Get $get) {
                                $institutionId = $get('institution_id');
                                return User::withoutGlobalScopes()
                                    ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                                    ->whereHas('roles', function ($q) {
                                        $q->whereIn('name', array_merge([
                                            'guru_kelas', 'guru_mapel', 'wali_kelas',
                                            'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
                                        ], User::QURAN_TEACHER_ROLES));
                                    })
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('observer_id')
                            ->label('Observer (Pengembang & Penjamin Mutu)')
                            ->options(function () {
                                return User::withoutGlobalScopes()
                                    ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                                        'superadmin',
                                        'ketua_yayasan',
                                        'tim_pengembang_penjamin_mutu_yayasan',
                                        'pengembang_penjamin_mutu',
                                        'tim_pengembang_yayasan',
                                    ]))
                                    ->pluck('name', 'id');
                            })
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_observasi')
                            ->label('Tanggal Pelaksanaan Observasi')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('mata_pelajaran_id')
                            ->label('Mata Pelajaran (Opsional)')
                            ->options(function (Get $get) {
                                $institutionId = $get('institution_id');
                                return Subject::withoutGlobalScopes()
                                    ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas (Opsional)')
                            ->options(function (Get $get) {
                                $institutionId = $get('institution_id');
                                return Classroom::withoutGlobalScopes()
                                    ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('materi_pembelajaran')
                            ->label('Materi / Pokok Bahasan Pembelajaran')
                            ->placeholder('Contoh: Persamaan Linear / Tahsin Tajwid Nun Sukun / Ekosistem')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('fokus_observasi')
                            ->label('Fokus Observasi')
                            ->placeholder('Contoh: Diferensiasi pembelajaran, keaktifan siswa, dan implementasi Deep Learning')
                            ->columnSpan(2),
                    ])->columns(2),

                Forms\Components\Section::make('Hasil Penilaian Observasi Pembelajaran')
                    ->description('Masukkan skor hasil observasi guru (bobot penilaian 20% pada evaluasi kinerja guru).')
                    ->schema([
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Akhir Observasi (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state !== null && is_numeric($state)) {
                                    $set('predikat', TeacherObservation::determinePredicate((float) $state));
                                }
                            }),

                        Forms\Components\TextInput::make('predikat')
                            ->label('Predikat Kinerja Observasi')
                            ->readOnly()
                            ->placeholder('Otomatis terisi dari nilai')
                            ->dehydrated(),

                        Forms\Components\Textarea::make('catatan_observasi')
                            ->label('Catatan & Masukan Observasi')
                            ->placeholder('Uraikan kelebihan guru dan aspek-aspek yang perlu ditingkatkan dalam proses pembelajaran...')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('rekomendasi')
                            ->label('Rekomendasi Tindak Lanjut')
                            ->placeholder('Rekomendasi pengembangan kompetensi atau pendampingan berkelanjutan...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_observasi')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru yang Diobservasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materi_pembelajaran')
                    ->label('Materi / Topik')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->materi_pembelajaran ?? '-'),

                Tables\Columns\TextColumn::make('observer.name')
                    ->label('Observer')
                    ->default('-')
                    ->color('secondary'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai (Bobot 20%)')
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1) . '%')
                    ->color(fn ($state) => TeacherObservation::getPredicateColor((float) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->color(fn ($record) => TeacherObservation::getPredicateColor((float) ($record->score ?? 0))),

                Tables\Columns\TextColumn::make('catatan_observasi')
                    ->label('Catatan Observasi')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->catatan_observasi ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Filter Lembaga')
                    ->options(fn () => Institution::where('id', '!=', 1)->pluck('name', 'id')),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherObservations::route('/'),
            'create' => Pages\CreateTeacherObservation::route('/create'),
            'edit' => Pages\EditTeacherObservation::route('/{record}/edit'),
        ];
    }
}
