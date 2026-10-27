<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AttributeViolationResource\Pages;
use App\Models\AttributeViolation;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttributeViolationResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'guru_piket'])) {
            return true;
        }

        // Untuk guru: HANYA jika guru yang bersangkutan terjadwal piket HARI INI
        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = AttributeViolation::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Pelanggaran Atribut';
    protected static ?string $modelLabel = 'Pelanggaran Atribut';
    protected static ?string $pluralModelLabel = 'Pelanggaran Atribut Siswa';
    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pencatatan Pelanggaran Atribut')
                    ->description('Pencatatan atribut oleh Guru Piket akan otomatis mengurangi poin kedisiplinan karakter siswa.')
                    ->schema([
                        Forms\Components\Hidden::make('teacher_id')
                            ->default(Auth::id()),

                        Forms\Components\Select::make('student_id')
                            ->label('Siswa yang Melanggar')
                            ->options(function () {
                                $user = Auth::user();
                                $q = Student::query();
                                if ($user && $user->institution_id && !$user->hasRole('superadmin') && $user->institution_id != 1) {
                                    $q->where('institution_id', $user->institution_id);
                                }
                                return $q->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pelanggaran')
                            ->default(Carbon::today())
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y'),

                        Forms\Components\CheckboxList::make('attribute_type')
                            ->label('Atribut yang Tidak Dipakai / Dilanggar')
                            ->options([
                                'Sabuk' => 'Sabuk / Ikat Pinggang',
                                'Dasi' => 'Dasi Sekolah',
                                'Topi' => 'Topi Upacara',
                                'Sepatu Hitam' => 'Sepatu Bukan Hitam',
                                'Kaos Kaki Putih' => 'Kaos Kaki Tidak Sesuai',
                                'Badge/Lokasi' => 'Badge / Atribut Lokasi Tidak Lengkap',
                                'Seragam' => 'Seragam Tidak Sesuai Hari',
                                'Lainnya' => 'Pelanggaran Kerapian Lainnya',
                            ])
                            ->columns(2)
                            ->required(),

                        Forms\Components\Textarea::make('jenis_pelanggaran')
                            ->label('Keterangan / Catatan Tambahan')
                            ->placeholder('Contoh: Tidak memakai dasi saat upacara bendera hari Senin')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attribute_type')
                    ->label('Atribut yang Dilanggar')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),

                Tables\Columns\TextColumn::make('jenis_pelanggaran')
                    ->label('Keterangan')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Piket Pencatat')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('⚡ Pelanggaran Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('tanggal', Carbon::today())),

                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('student.classroom', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttributeViolations::route('/'),
        ];
    }
}
