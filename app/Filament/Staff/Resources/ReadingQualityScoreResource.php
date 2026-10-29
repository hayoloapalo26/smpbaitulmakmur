<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReadingQualityScoreResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']));
    }

    protected static ?string $model = ReadingQualityScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Skor Kualitas Bacaan';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('koordinator_madin')) {
            return 'Madrasah Diniyah (Madin)';
        }
        if ($user?->isQuranTeacher()) {
            return 'Fitur Guru Al-Qur\'an';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $studentIds = [];

        if (!$user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            $studentIds = TeacherSubjectClass::where('teacher_id', $user->id)
                ->join('students', 'students.classroom_id', '=', 'teacher_subject_classes.classroom_id')
                ->pluck('students.id')
                ->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default($user->id),
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(function () use ($user, $studentIds) {
                        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
                            return Student::when($user->institution_id && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $user->institution_id))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        }
                        if (!empty($studentIds)) {
                            return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                        }
                        return Student::when($user->institution_id, fn ($q) => $q->where('institution_id', $user->institution_id))
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('program')
                    ->options([
                        'tahfidz' => 'Tahfidz',
                        'tahsin' => 'Tahsin',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('scale_score')
                    ->label('Skor (1-100)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')
                    ->default(Carbon::now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date()->sortable(),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa'),
                Tables\Columns\TextColumn::make('student.classroom.name')->label('Kelas')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('teacher.name')->label('Guru Penginput')->searchable()->badge()->color('info'),
                Tables\Columns\TextColumn::make('program')->label('Program')->badge(),
                Tables\Columns\TextColumn::make('scale_score')->label('Skor')->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 75 ? 'primary' : 'warning')),
                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Diterima' => 'success',
                        'Perlu Perbaikan' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                        'Diterima' => 'Diterima',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                    ]),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru Penginput')
                    ->relationship('teacher', 'name'),
                Tables\Filters\SelectFilter::make('program')
                    ->options([
                        'tahfidz' => 'Tahfidz',
                        'tahsin' => 'Tahsin',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status_verifikasi !== 'Diterima' && (auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Skor Kualitas Bacaan')
                    ->modalDescription('Apakah Anda yakin menerima dan memverifikasi skor kualitas bacaan siswa ini?')
                    ->action(function ($record) {
                        $record->update([
                            'status_verifikasi' => 'Diterima',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Skor Kualitas Berhasil Diterima')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('terima_massal')
                        ->label('Terima Semua Terpilih')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update([
                                'status_verifikasi' => 'Diterima',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Semua skor kualitas terpilih berhasil diterima')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReadingQualityScores::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
