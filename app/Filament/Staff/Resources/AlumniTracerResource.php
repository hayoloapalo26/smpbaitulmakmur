<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AlumniTracerResource\Pages;
use App\Filament\Staff\Resources\AlumniTracerResource\RelationManagers;
use App\Models\AlumniTracer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AlumniTracerResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah', 'tata_usaha', 'bk']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    protected static ?string $model = AlumniTracer::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Tracer Study (Alumni)';
    protected static ?string $modelLabel = 'Tracer Study Alumni';
    protected static ?string $pluralModelLabel = 'Tracer Study Alumni';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Isolasi ketat antar lembaga: user non-superadmin & non-yayasan hanya melihat alumni dari lembaganya
        if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
            $query->where('alumni_tracers.institution_id', $user->institution_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();

        // Daftar pilihan tahun lulus (10 tahun terakhir s/d tahun depan)
        $currentYear = (int) date('Y');
        $years = [];
        for ($y = $currentYear + 1; $y >= $currentYear - 10; $y--) {
            $years[(string)$y] = (string)$y;
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Alumni')
                    ->description('Pilih dari data siswa atau input manual data alumni.')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Tautkan dengan Data Siswa (Opsional)')
                            ->relationship(
                                name: 'student',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query) use ($user) {
                                    if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
                                        $query->where('institution_id', $user->institution_id);
                                    }
                                    return $query->with('classroom')->orderBy('name');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Student $record) => "{$record->name} " . ($record->classroom ? "({$record->classroom->name})" : "") . " - NIS: {$record->nis}")
                            ->searchable(['name', 'nis'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $student = \App\Models\Student::withoutGlobalScopes()->find($state);
                                    if ($student) {
                                        $set('nama', $student->name);
                                        $set('nis', $student->nis);
                                        $set('nisn', $student->nisn ?? null);
                                        if (!empty($student->no_telepon_siswa)) {
                                            $set('no_telepon', $student->no_telepon_siswa);
                                        } elseif (!empty($student->no_telepon_orang_tua)) {
                                            $set('no_telepon', $student->no_telepon_orang_tua);
                                        }
                                        if (!empty($student->alamat_siswa)) {
                                            $set('alamat_domisili', $student->alamat_siswa);
                                        }
                                    }
                                }
                            })
                            ->helperText('Memilih siswa akan otomatis mengisi data nama, NIS, dan kontak.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap Alumni')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('tahun_lulus')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->options($years)
                            ->default((string) $currentYear)
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('nis')
                            ->label('NIS Alumni')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nisn')
                            ->label('NISN Alumni')
                            ->maxLength(50),
                    ])->columns(2),

                Forms\Components\Section::make('Penelusuran Aktivitas Pasca-Kelulusan (Tracer Study)')
                    ->description('Informasi kegiatan alumni saat ini (pendidikan lanjutan, kerja, atau wirausaha).')
                    ->schema([
                        Forms\Components\Select::make('status_kegiatan')
                            ->label('Status Aktivitas Saat Ini')
                            ->options([
                                'belum_terlacak' => '📋 Belum Terlacak (Baru Lulus / Menunggu Data Penelusuran)',
                                'melanjutkan_pendidikan' => '🎓 Melanjutkan Pendidikan (SMA / SMK / MA / PTN / PTS / Pondok)',
                                'bekerja' => '💼 Bekerja (Karyawan / Instansi / Swasta)',
                                'wirausaha' => '🚀 Wirausaha / Bisnis Mandiri',
                                'mencari_kerja' => '⏳ Mencari Kerja / Masa Tunggu',
                                'lainnya' => '🌐 Lainnya',
                            ])
                            ->default('melanjutkan_pendidikan')
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_instansi')
                            ->label('Nama Sekolah Lanjutan / Kampus / Tempat Kerja')
                            ->placeholder('Contoh: SMAN 1 Surabaya / ITS Surabaya / PT Telkom')
                            ->required(fn (Forms\Get $get) => $get('status_kegiatan') !== 'belum_terlacak')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('jurusan_bidang')
                            ->label('Jurusan / Program Studi / Jabatan')
                            ->placeholder('Contoh: MIPA / Teknik Informatika / Staf HRD')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('alamat_domisili')
                            ->label('Kota / Alamat Domisili Saat Ini')
                            ->placeholder('Contoh: Surabaya, Jawa Timur')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Kontak & Prestasi Pasca-Lulus')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. WhatsApp / HP Aktif')
                            ->tel()
                            ->placeholder('Contoh: 081234567890')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Aktif')
                            ->email()
                            ->placeholder('Contoh: alumni@gmail.com')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('prestasi_setelah_lulus')
                            ->label('Prestasi Pasca-Lulus (Jika Ada)')
                            ->placeholder('Contoh: Juara 1 Karya Tulis Ilmiah Tingkat Provinsi, Peraih Beasiswa...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('kesan_pesan')
                            ->label('Testimoni / Kesan & Pesan untuk Sekolah')
                            ->placeholder('Pesan dan kesan alumni selama menempuh pendidikan di Baitul Makmur...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Catatan Kesiswaan')
                            ->placeholder('Catatan internal kesiswaan terkait alumni ini...')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('input_by')->default(auth()->id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tahun_lulus')
                    ->label('Angkatan')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Alumni (Data TU)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas Terakhir')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status_kegiatan')
                    ->label('Status Penelusuran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'melanjutkan_pendidikan', 'kuliah' => 'info',
                        'bekerja' => 'success',
                        'wirausaha' => 'warning',
                        'mencari_kerja' => 'danger',
                        'belum_terlacak' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'melanjutkan_pendidikan', 'kuliah' => '🎓 Lanjut Pendidikan',
                        'bekerja' => '💼 Bekerja',
                        'wirausaha' => '🚀 Wirausaha',
                        'mencari_kerja' => '⏳ Mencari Kerja',
                        'belum_terlacak' => '📋 Belum Terlacak (Perlu Diisi)',
                        default => ucwords(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('nama_instansi')
                    ->label('Sekolah Lanjutan / Kampus / Kerja')
                    ->placeholder('Belum diisi — Klik Isi Tracer')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('jurusan_bidang')
                    ->label('Jurusan / Jabatan')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-phone')
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('prestasi_setelah_lulus')
                    ->label('Prestasi Pasca-Lulus')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('alamat_domisili')
                    ->label('Domisili')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status_kegiatan')
                    ->label('Status Penelusuran')
                    ->options([
                        'belum_terlacak' => '📋 Belum Terlacak (Perlu Diisi)',
                        'melanjutkan_pendidikan' => '🎓 Melanjutkan Pendidikan',
                        'bekerja' => '💼 Bekerja',
                        'wirausaha' => '🚀 Wirausaha',
                        'mencari_kerja' => '⏳ Mencari Kerja',
                        'lainnya' => '🌐 Lainnya',
                    ]),
                Tables\Filters\SelectFilter::make('tahun_lulus')
                    ->label('Tahun Lulus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(fn (AlumniTracer $record) => ($record->status_kegiatan === 'belum_terlacak' || empty($record->nama_instansi)) ? 'Isi Tracer' : 'Update Tracer')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->color(fn (AlumniTracer $record) => ($record->status_kegiatan === 'belum_terlacak' || empty($record->nama_instansi)) ? 'warning' : 'info'),
                Tables\Actions\Action::make('chat_wa')
                    ->label('WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->url(function (AlumniTracer $record): ?string {
                        if (empty($record->no_telepon)) return null;
                        $phone = preg_replace('/[^0-9]/', '', $record->no_telepon);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }
                        return "https://wa.me/{$phone}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (AlumniTracer $record): bool => !empty($record->no_telepon)),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus / Batal Alumni'),
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
            'index' => Pages\ManageAlumniTracers::route('/'),
        ];
    }
}
