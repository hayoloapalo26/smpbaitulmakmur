<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherObservationResource\Pages;
use App\Models\TeacherObservation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TeacherObservationResource extends Resource
{
    protected static ?string $model = TeacherObservation::class;
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationLabel = 'Observasi Pembelajaran';
    protected static ?string $modelLabel = 'Observasi Pembelajaran Guru';
    protected static ?string $pluralModelLabel = 'Observasi Pembelajaran Guru';
    protected static ?string $slug = 'observasi-pembelajaran';
    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return Auth::user()?->hasRole('kepala_sekolah')
            ? 'Manajemen Sekolah'
            : 'Akademik & Kurikulum';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasAnyRole(array_merge([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
            'guru_kelas',
            'guru_mapel',
            'wali_kelas',
        ], User::QURAN_TEACHER_ROLES));
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasAnyRole([
            'superadmin',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
        ]) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->hasAnyRole([
            'superadmin',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
        ]) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->hasRole('superadmin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->with(['teacher', 'observer', 'institution', 'subject', 'classroom'])
            ->latest('tanggal_observasi');

        $user = Auth::user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Superadmin & Penjamin Mutu see all
        if ($user->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan'])) {
            return $query;
        }

        // Kepala Sekolah & Waka Kurikulum see observations in their school
        if ($user->hasAnyRole(['kepala_sekolah', 'waka_kurikulum'])) {
            return $query->where('institution_id', $user->institution_id);
        }

        // Teachers see their own observations
        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Observasi')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_observasi')
                            ->label('Tanggal Observasi')
                            ->required(),
                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru')
                            ->relationship('teacher', 'name')
                            ->required(),
                        Forms\Components\Select::make('observer_id')
                            ->label('Observer')
                            ->relationship('observer', 'name'),
                        Forms\Components\TextInput::make('materi_pembelajaran')
                            ->label('Materi Pembelajaran'),
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Akhir (0 - 100)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('predikat')
                            ->label('Predikat'),
                    ])->columns(2),

                Forms\Components\Section::make('Umpan Balik & Rekomendasi')
                    ->schema([
                        Forms\Components\Textarea::make('catatan_observasi')
                            ->label('Catatan Observasi')
                            ->rows(3),
                        Forms\Components\Textarea::make('rekomendasi')
                            ->label('Rekomendasi Tindak Lanjut')
                            ->rows(3),
                    ]),
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
                    ->label('Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('materi_pembelajaran')
                    ->label('Materi')
                    ->limit(30)
                    ->default('-'),

                Tables\Columns\TextColumn::make('observer.name')
                    ->label('Observer')
                    ->default('-'),

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
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->catatan_observasi ?? '-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherObservations::route('/'),
        ];
    }
}
