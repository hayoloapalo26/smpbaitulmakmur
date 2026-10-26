<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\StudentSavingResource\Pages;
use App\Models\StudentSaving;
use App\Models\Institution;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentSavingResource extends Resource
{
    protected static ?string $model = StudentSaving::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Tabungan Siswa';
    protected static ?string $modelLabel = 'Tabungan Siswa';
    protected static ?string $pluralModelLabel = 'Data Tabungan Siswa';
    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Identitas Siswa & Lembaga')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('1. Lembaga')
                            ->options(Institution::where('id', '>', 1)->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('classroom_id', null);
                                $set('student_id', null);
                                $set('saldo_awal', 0);
                                $set('saldo_akhir', 0);
                            }),

                        Forms\Components\Select::make('classroom_id')
                            ->label('2. Kelas / Rombel')
                            ->options(function (Get $get) {
                                $instId = $get('institution_id');
                                if (!$instId) return [];
                                return Classroom::withoutGlobalScopes()
                                    ->where('institution_id', $instId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('student_id', null);
                                $set('saldo_awal', 0);
                                $set('saldo_akhir', 0);
                            }),

                        Forms\Components\Select::make('student_id')
                            ->label('3. Nama Siswa')
                            ->options(function (Get $get) {
                                $clsId = $get('classroom_id');
                                if (!$clsId) return [];
                                return Student::withoutGlobalScopes()
                                    ->where('classroom_id', $clsId)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => "{$s->name} (" . ($s->nis ?? 'No NIS') . ")"]);
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state) {
                                    $currentBalance = StudentSaving::getLatestBalance((int)$state);
                                    $set('saldo_awal', $currentBalance);
                                    
                                    $nominal = (float)($get('nominal') ?? 0);
                                    $tipe = $get('tipe') ?? 'setor';
                                    $newSaldo = $tipe === 'tarik' 
                                        ? max(0, $currentBalance - $nominal) 
                                        : $currentBalance + $nominal;
                                    $set('saldo_akhir', $newSaldo);
                                } else {
                                    $set('saldo_awal', 0);
                                    $set('saldo_akhir', 0);
                                }
                            }),
                    ])->columns(3),

                Forms\Components\Section::make('Detail Transaksi Tabungan')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Menabung')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('tipe')
                            ->label('Jenis Transaksi')
                            ->options([
                                'setor' => 'Setor (Menabung)',
                                'tarik' => 'Tarik (Penarikan)',
                            ])
                            ->default('setor')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $saldoAwal = (float)($get('saldo_awal') ?? 0);
                                $nominal = (float)($get('nominal') ?? 0);
                                $newSaldo = $state === 'tarik'
                                    ? max(0, $saldoAwal - $nominal)
                                    : $saldoAwal + $nominal;
                                $set('saldo_akhir', $newSaldo);
                            }),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Jumlah yang Ditabung (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $nominal = (float)($state ?? 0);
                                $saldoAwal = (float)($get('saldo_awal') ?? 0);
                                $tipe = $get('tipe') ?? 'setor';
                                $newSaldo = $tipe === 'tarik'
                                    ? max(0, $saldoAwal - $nominal)
                                    : $saldoAwal + $nominal;
                                $set('saldo_akhir', $newSaldo);
                            }),

                        Forms\Components\TextInput::make('saldo_awal')
                            ->label('Saldo Sebelumnya (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('saldo_akhir')
                            ->label('Saldo Akhir Setelah Transaksi (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Otomatis dihitung dari saldo sebelumnya +/- jumlah yang ditabung.'),

                        Forms\Components\Select::make('petugas_id')
                            ->label('Bagian Keuangan yang Menginput')
                            ->options(function () {
                                return User::where(function ($q) {
                                    $q->whereHas('roles', function ($sub) {
                                        $sub->whereIn('name', [
                                            'pegawai_keuangan_yayasan',
                                            'pegawai_keuangan',
                                            'superadmin',
                                            'ketua_yayasan',
                                            'pembina_yayasan',
                                            'sekretaris_yayasan',
                                            'tata_usaha',
                                        ]);
                                    })->orWhere('id', auth()->id());
                                })->pluck('name', 'id');
                            })
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required()
                            ->helperText('Pilih nama petugas/staf keuangan yang mencatat setoran ini.'),

                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Penyetoran')
                            ->options([
                                'Tunai' => 'Tunai (Langsung ke Petugas)',
                                'Transfer Bank' => 'Transfer Bank',
                                'Titip Wali Kelas' => 'Titip Wali Kelas',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Tunai'),

                        Forms\Components\TextInput::make('kode_transaksi')
                            ->label('No. Bukti / Kode Transaksi')
                            ->placeholder('Otomatis di-generate jika kosong')
                            ->maxLength(50),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan / Keterangan')
                            ->placeholder('Contoh: Tabungan mingguan, tabungan qurban, dll.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('kode_transaksi')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->fontFamily('mono')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal Menabung')
                    ->date('d M Y')
                    ->sortable(),

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
                    ->sortable()
                    ->description(fn (StudentSaving $record): string => "NIS: " . ($record->student?->nis ?? '-')),

                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tarik' => 'TARIK',
                        default => 'SETOR',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'tarik' => 'danger',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Jumlah yang Ditabung')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn (StudentSaving $record): string => $record->tipe === 'tarik' ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('saldo_akhir')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('petugas.name')
                    ->label('Bagian Keuangan yang Menginput')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->options(Institution::where('id', '>', 1)->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Jenis Transaksi')
                    ->options([
                        'setor' => 'Setor (Menabung)',
                        'tarik' => 'Tarik (Penarikan)',
                    ]),

                Tables\Filters\SelectFilter::make('petugas_id')
                    ->label('Bagian Keuangan')
                    ->options(function () {
                        return User::whereHas('roles', function ($q) {
                            $q->whereIn('name', ['pegawai_keuangan_yayasan', 'pegawai_keuangan', 'superadmin']);
                        })->pluck('name', 'id');
                    }),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'], fn ($q, $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['sampai_tanggal'], fn ($q, $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_bukti')
                    ->label('Bukti')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->modalHeading(fn (StudentSaving $record) => "Kwitansi Tabungan - {$record->kode_transaksi}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (StudentSaving $record) => view('filament.yayasan.modals.cetak-tabungan', ['saving' => $record])),

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
            'index' => Pages\ManageStudentSavings::route('/'),
        ];
    }
}
