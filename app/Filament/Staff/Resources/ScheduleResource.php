<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ScheduleResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasRole('kepala_sekolah') ?? false);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge([
            'waka_kurikulum', 'superadmin', 'kepala_sekolah', 'tata_usaha',
            'guru_mapel', 'guru_kelas', 'koordinator_madin', 'wali_kelas'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    protected static ?string $model = Schedule::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $modelLabel = 'Jadwal Pelajaran';
    protected static ?int $navigationSort = 4;

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'tata_usaha', 'superadmin'])) {
            return $query;
        }

        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kelas & Mata Pelajaran')
                    ->description('Tentukan rombongan belajar kelas, mata pelajaran, dan guru pengampu.')
                    ->schema([
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('teacher_id')
                            ->label('Guru Pengajar')
                            ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) {
                                $query->where('is_active', true)
                                    ->whereHas('roles', function ($q) {
                                        $q->whereIn('name', array_merge(['guru_mapel', 'koordinator_madin', 'wali_kelas', 'guru_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
                                    });
                            })
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $user = Auth::user();
                                $tag = '';
                                if ($user && $record->institution_id && $record->institution_id != $user->institution_id) {
                                    $instName = $record->institution?->name ?? 'Lintas Lembaga';
                                    $tag = " [Lintas Lembaga: {$instName}]";
                                }
                                return $record->name . $tag;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Waktu Mengajar (Per 1 JP - Multiple Choice)')
                    ->description('Setiap opsi mewakili alokasi 1 Jam Pelajaran (1 JP). Guru dapat mengajar 1 JP, 2 JP, atau 3 JP dengan mencentang jam ke berapa saja yang diampu.')
                    ->schema([
                        Forms\Components\CheckboxList::make('hari')
                            ->label('Pilih Hari Mengajar (Multiple Choice)')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                            ])
                            ->columns(3)
                            ->required()
                            ->helperText('Centang satu atau beberapa hari mengajar untuk mata pelajaran ini.'),

                        Forms\Components\CheckboxList::make('jam_ke')
                            ->label('Pilih Jam Mengajar (Per 1 JP - Multiple Choice)')
                            ->options(function () {
                                $options = [];
                                foreach (\App\Models\Schedule::getJamSlots() as $jam => $slot) {
                                    $options[$jam] = "{$slot['label']} (1 JP · {$slot['time']})";
                                }
                                return $options;
                            })
                            ->columns(2)
                            ->bulkToggleable()
                            ->live()
                            ->required()
                            ->helperText(function (Forms\Get $get) {
                                $selected = $get('jam_ke');
                                $count = is_array($selected) ? count($selected) : 0;
                                if ($count > 0) {
                                    return "✓ Terpilih: {$count} JP (Jam Pelajaran). Sistem otomatis mencatat {$count} jam mengajar untuk guru ini.";
                                }
                                return 'Setiap opsi bernilai 1 JP. Centang jam ke berapa saja guru tersebut mengajar (misal 1 JP centang 1 pilihan, 2 JP centang 2 pilihan, atau 3 JP centang 3 pilihan).';
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_ke')
                    ->label('Jam Pelajaran & Waktu')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => \App\Models\Schedule::getJamLabelWithTime($state))
                    ->description('Beban: 1 JP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Pengajar')
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('jam_ke', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('jam_ke')
                    ->label('Jam Pelajaran')
                    ->options(\App\Models\Schedule::getJamOptions()),
                Tables\Filters\SelectFilter::make('hari')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ]),
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('classroom', 'name'),
                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru')
                    ->relationship('teacher', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('3xl')
                    ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin']))
                    ->mutateRecordDataUsing(function (array $data): array {
                        if (!empty($data['hari']) && !is_array($data['hari'])) {
                            $data['hari'] = [$data['hari']];
                        }
                        if (isset($data['jam_ke']) && !is_array($data['jam_ke'])) {
                            $data['jam_ke'] = [(int) $data['jam_ke']];
                        }
                        return $data;
                    })
                    ->using(function (\App\Models\Schedule $record, array $data): \App\Models\Schedule {
                        $days = is_array($data['hari']) ? $data['hari'] : [$data['hari']];
                        $jams = is_array($data['jam_ke']) ? $data['jam_ke'] : [$data['jam_ke']];

                        $firstDay = array_shift($days);
                        $firstJam = array_shift($jams);

                        $record->update([
                            'classroom_id' => $data['classroom_id'],
                            'subject_id' => $data['subject_id'],
                            'teacher_id' => $data['teacher_id'],
                            'hari' => $firstDay ?? $record->hari,
                            'jam_ke' => $firstJam !== null ? (int) $firstJam : $record->jam_ke,
                        ]);

                        // Jika memilih hari/jam tambahan saat edit
                        $allDays = array_merge([$firstDay], $days);
                        $allJams = array_merge([$firstJam], $jams);
                        foreach ($allDays as $d) {
                            foreach ($allJams as $j) {
                                if ($d == $firstDay && $j == $firstJam) continue;
                                \App\Models\Schedule::firstOrCreate([
                                    'classroom_id' => $data['classroom_id'],
                                    'subject_id' => $data['subject_id'],
                                    'teacher_id' => $data['teacher_id'],
                                    'hari' => $d,
                                    'jam_ke' => (int) $j,
                                ], [
                                    'institution_id' => $record->institution_id,
                                ]);
                            }
                        }

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSchedules::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
