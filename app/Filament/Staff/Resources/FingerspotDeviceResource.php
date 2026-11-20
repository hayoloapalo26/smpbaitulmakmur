<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\FingerspotDeviceResource\Pages;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FingerspotDeviceResource extends Resource
{
    protected static ?string $model = DailyAttendance::class;

    protected static ?string $slug = 'integrasi-mesin-fingerspot';

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Pengaturan Sistem';

    protected static ?string $navigationLabel = 'Integrasi Mesin Presensi (Fingerspot)';

    protected static ?string $modelLabel = 'Log Mesin Presensi';

    protected static ?string $pluralModelLabel = 'Integrasi Mesin Presensi (Fingerspot)';

    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasRole('superadmin') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('superadmin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Nama Siswa')
                    ->options(fn () => Student::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(Carbon::today())
                    ->required(),

                Forms\Components\Select::make('tipe_absen')
                    ->label('Kategori Absensi')
                    ->options([
                        'masuk'         => '🏫 Kehadiran Harian (Masuk)',
                        'sholat_dhuha'  => '🕌 Sholat Dhuha',
                        'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                        'keputrian'     => '🌸 Kegiatan Keputrian (Jumat Putri)',
                        'sholat_ashar'  => '🕌 Sholat Ashar & Pulang',
                        'pulang'        => '🏠 Absen Pulang',
                    ])
                    ->default('masuk')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'hadir'    => '✅ Hadir',
                        'sakit'    => '🤒 Sakit',
                        'izin'     => '📝 Izin',
                        'alpa'     => '❌ Alpha / Tanpa Keterangan',
                        'halangan' => '🌸 Halangan (Udzur Sholat)',
                    ])
                    ->default('hadir')
                    ->required(),

                Forms\Components\TimePicker::make('waktu_kedatangan')
                    ->label('Jam Scan (WIB)')
                    ->seconds(false)
                    ->required(),

                Forms\Components\Toggle::make('is_late')
                    ->label('Terlambat?')
                    ->default(false),

                Forms\Components\Select::make('metode_absen')
                    ->label('Metode / Sumber Scan')
                    ->options([
                        'fingerprint'      => '🖐️ Fingerspot (Sidik Jari)',
                        'face_recognition' => '😊 Face ID (Wajah)',
                        'nfc_card'         => '💳 Kartu RFID / NFC',
                        'password'         => '🔢 PIN Mesin',
                        'manual'           => '✍️ Manual',
                    ])
                    ->default('fingerprint'),

                Forms\Components\Textarea::make('keterangan')
                    ->label('Log Keterangan Mesin')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('bukti_izin')
                    ->label('Link Google Drive Bukti (Jika Ada)')
                    ->url()
                    ->maxLength(2048)
                    ->columnSpanFull(),
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
                    ->label('Siswa Terkait')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (DailyAttendance $record) => ($record->student?->classroom?->name ?? '-') . ' • PIN: ' . ($record->student?->id_finger ?? $record->student?->fingerspot_pin ?? '-')),

                Tables\Columns\TextColumn::make('tipe_absen')
                    ->label('Sesi Mesin')
                    ->badge()
                    ->formatStateUsing(function (?string $state, DailyAttendance $record): string {
                        $isFriday = Carbon::parse($record->tanggal)->isFriday();
                        return match ($state) {
                            'masuk'         => '🏫 Masuk Pagi',
                            'sholat_dhuha'  => '🕌 Sholat Dhuha',
                            'sholat_dhuhur' => $isFriday ? '🕌 Sholat Jum\'at' : '🕌 Sholat Dhuhur',
                            'keputrian'     => '🌸 Kegiatan Keputrian',
                            'sholat_ashar'  => '🕌 Sholat Ashar & Pulang',
                            'pulang'        => '🏠 Pulang',
                            default         => ucfirst((string) $state),
                        };
                    })
                    ->color(fn (?string $state): string => str_starts_with((string) $state, 'sholat_') ? 'warning' : 'primary'),

                Tables\Columns\TextColumn::make('waktu_kedatangan')
                    ->label('Waktu Scan')
                    ->time('H:i:s')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('is_late')
                    ->label('Ketepatan')
                    ->badge()
                    ->formatStateUsing(fn ($state, DailyAttendance $record): string => $record->status !== 'hadir'
                        ? ucfirst($record->status)
                        : ($state ? '⏰ Terlambat' : '✅ Tepat Waktu'))
                    ->color(fn ($state, DailyAttendance $record): string => $record->status !== 'hadir'
                        ? 'gray'
                        : ($state ? 'danger' : 'success')),

                Tables\Columns\TextColumn::make('metode_absen')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'fingerprint'      => '🖐️ Fingerspot',
                        'face_recognition' => '😊 Face ID',
                        'nfc_card'         => '💳 RFID Kartu',
                        'password'         => '🔢 PIN',
                        default            => '✍️ Manual',
                    })
                    ->color(fn (?string $state): string => $state === 'fingerprint' ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Log Info')
                    ->wrap()
                    ->limit(40)
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipe_absen')
                    ->label('Sesi Absensi')
                    ->options([
                        'masuk'         => 'Masuk Pagi',
                        'sholat_dhuhur' => 'Sholat Dhuhur',
                        'keputrian'     => 'Kegiatan Keputrian',
                        'sholat_ashar'  => 'Sholat Ashar & Pulang',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'hadir'    => 'Hadir',
                        'sakit'    => 'Sakit',
                        'izin'     => 'Izin',
                        'alpa'     => 'Alpha',
                        'halangan' => 'Halangan',
                    ]),
                Tables\Filters\TernaryFilter::make('is_late')
                    ->label('Ketepatan Waktu')
                    ->trueLabel('⏰ Terlambat')
                    ->falseLabel('✅ Tepat Waktu'),
                Tables\Filters\Filter::make('hari_ini')
                    ->label('Hanya Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('tanggal', Carbon::today())),
            ])
            ->actions([
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
            'index' => Pages\ManageFingerspotDevices::route('/'),
        ];
    }
}
