<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentHalanganResource\Pages;
use App\Models\StudentHalangan;
use App\Models\Student;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentHalanganResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk', 'wali_kelas'])) {
            return true;
        }

        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = StudentHalangan::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $modelLabel = 'Catatan Halangan Siswi';
    protected static ?string $pluralModelLabel = 'Halangan Siswi';
    protected static ?string $recordTitleAttribute = 'catatan';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user && $user->isPiketToday() && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return 'Tugas Guru Piket';
        }

        return 'Kesiswaan & Karakter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'bk', 'kepala_sekolah', 'superadmin']) || $user->isPiketToday()) {
            if (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
                $query->where('institution_id', $user->institution_id);
            }
            return $query;
        }

        // Wali kelas hanya melihat siswi kelasnya (kelas akademik)
        $classroomIds = Classroom::reguler()->where('wali_kelas_id', $user->id)->pluck('id');
        $studentIds = Student::whereIn('classroom_id', $classroomIds)
            ->where('jenis_kelamin', 'P')
            ->pluck('id');

        return $query->whereIn('student_id', $studentIds);
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();

        // Siswi yang bisa dipilih (hanya perempuan)
        $siswi = function () use ($user) {
            if ($user->hasRole(['waka_kesiswaan', 'bk', 'kepala_sekolah'])) {
                return Student::where('jenis_kelamin', 'P')
                    ->with('classroom')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->name} — {$s->classroom->name}"]);
            }
            $classroomIds = Classroom::reguler()->where('wali_kelas_id', $user->id)->pluck('id');
            return Student::whereIn('classroom_id', $classroomIds)
                ->where('jenis_kelamin', 'P')
                ->with('classroom')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->id => "{$s->name} — {$s->classroom->name}"]);
        };

        return $form->schema([
            Forms\Components\Section::make('Informasi Siswi')
                ->description('Data ini bersifat rahasia dan hanya dapat dilihat oleh wali kelas, BK, dan kesiswaan.')
                ->schema([
                    Forms\Components\Hidden::make('recorded_by')->default($user->id),

                    Forms\Components\Select::make('student_id')
                        ->label('Nama Siswi')
                        ->options($siswi)
                        ->searchable()
                        ->required(),

                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Mulai Tanggal')
                        ->default(now())
                        ->required(),

                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Selesai Tanggal')
                        ->helperText('Kosongkan jika belum tahu kapan selesai')
                        ->nullable(),

                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan (Opsional)')
                        ->helperText('Jika ada catatan khusus dari siswi/orang tua')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->placeholder('Belum selesai'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Sedang Halangan?')
                    ->boolean()
                    ->getStateUsing(function (StudentHalangan $record): bool {
                        $today = Carbon::today();
                        return $record->tanggal_mulai->lte($today)
                            && ($record->tanggal_selesai === null || $record->tanggal_selesai->gte($today));
                    }),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_mulai', 'desc')
            ->filters([
                Tables\Filters\Filter::make('sedang_halangan')
                    ->label('Sedang Halangan Sekarang')
                    ->query(fn (Builder $query) => $query
                        ->where('tanggal_mulai', '<=', today())
                        ->where(fn ($q) => $q
                            ->whereNull('tanggal_selesai')
                            ->orWhere('tanggal_selesai', '>=', today())
                        )
                    )
                    ->default(),
            ])
            ->actions([
                // Tombol "Selesai" untuk menandai halangan sudah berakhir
                Tables\Actions\Action::make('mark_selesai')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StudentHalangan $record) => $record->tanggal_selesai === null)
                    ->action(fn (StudentHalangan $record) => $record->update([
                        'tanggal_selesai' => today(),
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Halangan Selesai')
                    ->modalDescription('Apakah siswi ini sudah selesai masa halangannya?'),
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
            'index' => Pages\ManageStudentHalangans::route('/'),
        ];
    }
}
