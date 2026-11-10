<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ExtracurricularResource\Pages;
use App\Models\Extracurricular;
use App\Models\ExtracurricularMember;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExtracurricularResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    protected static ?string $model = Extracurricular::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Data & Jadwal Ekstrakurikuler';
    protected static ?string $modelLabel = 'Data & Jadwal Ekstrakurikuler';
    protected static ?string $pluralModelLabel = 'Data & Jadwal Ekstrakurikuler';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Ekstrakurikuler & Pembina')
                    ->description('Waka Kesiswaan mengatur identitas ekstrakurikuler serta menunjuk Guru Pembina/Pelatih.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Ekstrakurikuler')
                            ->placeholder('Contoh: Pramuka, ECC, Futsal, Bulutangkis, Teater, Pagar Nusa, dll.')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Anda dapat menambahkan ekstrakurikuler baru kapan saja melalui kolom ini.'),

                        Forms\Components\Select::make('pembina_id')
                            ->label('Guru Pembina / Pelatih')
                            ->options(function () {
                                return User::where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($u) {
                                        $roles = $u->roles->pluck('name')->map(fn ($r) => User::getRoleDisplayName($r))->implode(', ');
                                        return [$u->id => "{$u->name}" . ($roles ? " ({$roles})" : '')];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih guru/staf yang bertindak sebagai pembina/pelatih kegiatan ini.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ekstrakurikuler Aktif')
                            ->default(true)
                            ->helperText('Hanya ekstrakurikuler aktif yang muncul pada lembar presensi kehadiran.'),
                    ])->columns(2),

                Forms\Components\Section::make('Jadwal Pelaksanaan & Lokasi Latihan')
                    ->description('Waka Kesiswaan menentukan hari, rentang jam latihan, dan tempat pelaksanaan.')
                    ->schema([
                        Forms\Components\Select::make('hari')
                            ->label('Hari Latihan')
                            ->options([
                                'Senin' => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu' => 'Rabu',
                                'Kamis' => 'Kamis',
                                'Jumat' => 'Jumat',
                                'Sabtu' => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jam_mulai')
                                ->label('Jam Mulai')
                                ->placeholder('Contoh: 15:30')
                                ->default('15:30')
                                ->required(),

                            Forms\Components\TextInput::make('jam_selesai')
                                ->label('Jam Selesai')
                                ->placeholder('Contoh: 17:00')
                                ->default('17:00')
                                ->required(),
                        ]),

                        Forms\Components\TextInput::make('lokasi')
                            ->label('Lokasi / Tempat Latihan')
                            ->placeholder('Contoh: Lapangan Olahraga / Aula / Lab Bahasa / GOR')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi / Program Latihan')
                            ->placeholder('Uraian singkat tujuan atau materi ekstrakurikuler...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ekstrakurikuler')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('pembina.name')
                    ->label('Guru Pembina / Pelatih')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Jumat' => 'success',
                        'Sabtu' => 'info',
                        'Minggu' => 'purple',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('jam')
                    ->label('Waktu Latihan')
                    ->getStateUsing(fn ($record) => ($record->jam_mulai && $record->jam_selesai) ? "{$record->jam_mulai} - {$record->jam_selesai} WIB" : '-')
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Anggota Siswa')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Total Presensi')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                        'Minggu' => 'Minggu',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\Action::make('routing_siswa')
                    ->label('Routing Anggota')
                    ->icon('heroicon-o-user-group')
                    ->color('warning')
                    ->modalHeading(fn (Extracurricular $record) => "Routing Anggota Santri/Siswa: {$record->name}")
                    ->modalDescription('Tentukan santri/siswa yang mengikuti kegiatan ekstrakurikuler ini. Siswa yang dirouting akan otomatis muncul di jurnal mengajar dan presensi.')
                    ->modalWidth('3xl')
                    ->fillForm(fn (Extracurricular $record) => [
                        'student_ids' => $record->members()->where('is_active', true)->pluck('student_id')->toArray(),
                    ])
                    ->form([
                        Forms\Components\Select::make('filter_classroom_id')
                            ->label('Saring Tampilan Berdasarkan Kelas (Opsional)')
                            ->options(fn () => \App\Models\Classroom::reguler()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\CheckboxList::make('student_ids')
                            ->label('Daftar Santri / Siswa Anggota Eskul')
                            ->options(function (Forms\Get $get) {
                                $query = Student::query();
                                $clsId = $get('filter_classroom_id');
                                if ($clsId) {
                                    $query->where('classroom_id', $clsId);
                                }
                                return $query->orderBy('name')->get()->mapWithKeys(function ($s) {
                                    $kelas = $s->classroom ? " [{$s->classroom->name}]" : '';
                                    return [$s->id => "{$s->name}{$kelas}"];
                                });
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->required(),
                    ])
                    ->action(function (Extracurricular $record, array $data) {
                        $selectedStudentIds = $data['student_ids'] ?? [];
                        
                        foreach ($selectedStudentIds as $sid) {
                            ExtracurricularMember::updateOrCreate(
                                [
                                    'extracurricular_id' => $record->id,
                                    'student_id'         => $sid,
                                ],
                                [
                                    'is_active'          => true,
                                    'institution_id'     => $record->institution_id ?? auth()->user()?->institution_id,
                                ]
                            );
                        }

                        if (!empty($data['filter_classroom_id'])) {
                            $classStudentIds = Student::where('classroom_id', $data['filter_classroom_id'])->pluck('id')->toArray();
                            ExtracurricularMember::where('extracurricular_id', $record->id)
                                ->whereIn('student_id', $classStudentIds)
                                ->whereNotIn('student_id', $selectedStudentIds)
                                ->delete();
                        } else {
                            ExtracurricularMember::where('extracurricular_id', $record->id)
                                ->whereNotIn('student_id', $selectedStudentIds)
                                ->delete();
                        }

                        Notification::make()
                            ->title('Routing Siswa Berhasil Disimpan')
                            ->body("Data anggota {$record->name} berhasil diperbarui.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('presensi')
                    ->label('Presensi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->url(fn ($record) => ExtracurricularAttendanceResource::getUrl('index', ['tableFilters' => ['extracurricular_id' => ['value' => $record->id]]])),
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
            'index' => Pages\ManageExtracurriculars::route('/'),
        ];
    }
}
