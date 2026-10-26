<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\StudentSppResource\Pages;
use App\Models\StudentSpp;
use App\Models\Institution;
use App\Models\Classroom;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentSppResource extends Resource
{
    protected static ?string $model = StudentSpp::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Rekap & Riwayat SPP';
    protected static ?string $modelLabel = 'Data SPP Siswa';
    protected static ?string $pluralModelLabel = 'Rekap Pembayaran SPP';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasAnyRole([
            'superadmin',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'yayasan',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Siswa & Lembaga')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga')
                            ->options(Institution::where('id', '>', 1)->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('classroom_id', null)),

                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->options(function (Get $get) {
                                $instId = $get('institution_id');
                                if (!$instId) return [];
                                return Classroom::withoutGlobalScopes()
                                    ->where('institution_id', $instId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null)),

                        Forms\Components\Select::make('student_id')
                            ->label('Siswa')
                            ->options(function (Get $get) {
                                $clsId = $get('classroom_id');
                                if (!$clsId) return [];
                                return Student::withoutGlobalScopes()
                                    ->where('classroom_id', $clsId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])->columns(3),

                Forms\Components\Section::make('Detail Pembayaran SPP')
                    ->schema([
                        Forms\Components\Select::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->options([
                                '2026/2027' => '2026/2027',
                                '2025/2026' => '2025/2026',
                                '2027/2028' => '2027/2028',
                            ])
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('bulan')
                            ->label('Bulan')
                            ->options(StudentSpp::BULAN_LIST)
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'lunas' => 'Lunas',
                                'belum_lunas' => 'Belum Lunas',
                            ])
                            ->default('lunas')
                            ->required(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal SPP (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(200000),

                        Forms\Components\DatePicker::make('tanggal_bayar')
                            ->label('Tanggal Bayar')
                            ->default(now()),

                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options(StudentSpp::METODE_PEMBAYARAN)
                            ->default('Tunai'),

                        Forms\Components\TextInput::make('kwitansi_no')
                            ->label('Nomor Bukti / Kwitansi')
                            ->placeholder('SPP/2026/001'),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.nis')
                    ->label('NIS')
                    ->searchable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('tahun_ajaran')
                    ->label('Thn Ajaran')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bulan')
                    ->label('Bulan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lunas' => 'LUNAS',
                        default => 'BELUM LUNAS',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_bayar')
                    ->label('Tgl Bayar')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode'),

                Tables\Columns\TextColumn::make('petugas.name')
                    ->label('Petugas')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->options(Institution::where('id', '>', 1)->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options([
                        '2026/2027' => '2026/2027',
                        '2025/2026' => '2025/2026',
                        '2027/2028' => '2027/2028',
                    ]),

                Tables\Filters\SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(StudentSpp::BULAN_LIST),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status SPP')
                    ->options([
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsLunas')
                        ->label('Tandai LUNAS')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update([
                            'status' => 'lunas',
                            'tanggal_bayar' => now()->toDateString(),
                        ])),
                    Tables\Actions\BulkAction::make('markAsBelumLunas')
                        ->label('Tandai BELUM LUNAS')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->update([
                            'status' => 'belum_lunas',
                            'tanggal_bayar' => null,
                        ])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudentSpps::route('/'),
        ];
    }
}
