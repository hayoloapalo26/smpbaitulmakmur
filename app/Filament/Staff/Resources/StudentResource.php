<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use App\Filament\Exports\StudentExporter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use App\Models\User;

class StudentResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isQuranTeacher()) return false;
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isQuranTeacher()) return false;
        return $user->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'superadmin', 'kepala_sekolah', 'wali_kelas', 'guru_kelas']);
    }

    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Data Siswa';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasAnyRole(['tata_usaha', 'kepala_sekolah', 'superadmin'])) {
            return 'Administrasi & Data Induk';
        }
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Kesiswaan & Karakter';
        }
        if ($user?->hasAnyRole(['wali_kelas', 'guru_kelas'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Administrasi & Data Induk';
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user?->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user?->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'kepala_sekolah', 'superadmin'])) {
            return 'Data & Profil Siswa Kelas';
        }
        return 'Data Siswa';
    }


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Isolasi ketat per lembaga: pastikan data siswa hanya dari lembaga masing-masing
        if ($user && ($user->institution_id == 2 || $user->institution_id == 1 || $user->hasRole('superadmin'))) {
            // Pada panel staff (SMP): tampilkan siswa SMP (ID 2), serta toleransi jika ada data lawas yang sempat tersimpan ID 1 (Yayasan)
            $query->where(function ($q) {
                $q->where('students.institution_id', 2)
                  ->orWhere('students.institution_id', 1)
                  ->orWhereNull('students.institution_id');
            });
        } elseif ($user && $user->institution_id) {
            $query->where('students.institution_id', $user->institution_id);
        }

        // Admin roles
        if ($user->hasRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'bk'])) {
            return $query;
        }

        // Wali Kelas classes
        $waliKelasClassroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();

        // Teacher subject classes
        $teacherClassroomIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('classroom_id')->toArray();
        
        $allowedClassrooms = array_unique(array_merge($waliKelasClassroomIds, $teacherClassroomIds));

        return $query->whereIn('classroom_id', $allowedClassrooms);
    }

        

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Utama Siswa')
                    ->schema([
                        Forms\Components\TextInput::make('nis')
                            ->label('NIS')
                            ->required(),
                        Forms\Components\TextInput::make('nisn')
                            ->label('NISN')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap Siswa')
                            ->required(),
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship(
                                name: 'classroom',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => (!auth()->user()->hasRole('superadmin') && auth()->user()->institution_id != 1)
                                    ? $query->where('institution_id', auth()->user()->institution_id)
                                    : $query
                            )
                            ->required(),
                        Forms\Components\Select::make('status_siswa')
                            ->label('Status Siswa')
                            ->options([
                                'aktif' => 'Siswa Aktif',
                                'alumni' => 'Alumni (Sudah Lulus)',
                            ])
                            ->default('aktif')
                            ->required(),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Gender')
                            ->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])
                            ->required(),
                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required(),
                        Forms\Components\TextInput::make('no_telepon_siswa')
                            ->label('No Telepon Siswa')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('alamat_siswa')
                            ->label('Alamat Siswa')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Data Akademik & Riwayat')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Siswa')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_ijazah')
                            ->label('No Ijazah')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_skhu')
                            ->label('No SKHU')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tahun_lulus')
                            ->label('Tahun Lulus')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_peserta_un')
                            ->label('No Peserta UN')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('character_score')
                            ->label('Skor Karakter')
                            ->numeric()
                            ->default(80)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('🔐 Integrasi Alat Absensi (Fingerspot Revo W230N / RFID)')
                    ->description('Isi bagian ini setelah siswa didaftarkan di mesin Fingerspot Revo W230N')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make(
                            \Illuminate\Support\Facades\Schema::hasColumn('students', 'fingerspot_pin')
                                ? 'fingerspot_pin'
                                : 'hikvision_employee_no'
                        )
                            ->label('Fingerspot PIN / ID Mesin')
                            ->helperText('Nomor PIN siswa di mesin Fingerspot Revo W230N (biasanya disamakan dengan NIS)')
                            ->placeholder('Contoh: 100001')
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                        Forms\Components\TextInput::make('nfc_card_no')
                            ->label('No Kartu NFC / RFID')
                            ->helperText('Nomor chip kartu pelajar (tertera di belakang kartu jika memakai kartu)')
                            ->placeholder('Contoh: 1234567890')
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Data Orang Tua / Wali')
                    ->description('Pastikan memasukkan data orang tua / wali yang valid sesuai Kartu Keluarga')
                    ->schema([
                        Forms\Components\Fieldset::make('Data Keluarga')
                            ->schema([
                                Forms\Components\TextInput::make('no_kk')
                                    ->label('Nomer KK (Kartu Keluarga)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('agama')
                                    ->label('Agama')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('anak_ke')
                                    ->label('Anak Ke-')
                                    ->numeric(),
                                Forms\Components\TextInput::make('jumlah_saudara_kandung')
                                    ->label('Jumlah Saudara Kandung')
                                    ->numeric(),
                            ])->columns(2),
                            
                        Forms\Components\Fieldset::make('Data Ayah')
                            ->schema([
                                Forms\Components\TextInput::make('nama_ayah')
                                    ->label('Nama Ayah')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tempat_lahir_ayah')
                                    ->label('Tempat Lahir (Ayah)')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('tanggal_lahir_ayah')
                                    ->label('Tanggal Lahir (Ayah)'),
                                Forms\Components\TextInput::make('no_hp_ayah')
                                    ->label('No. Telp/HP (Ayah)')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pendidikan_ayah')
                                    ->label('Pendidikan (Ayah)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pekerjaan_ayah')
                                    ->label('Pekerjaan (Ayah)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('penghasilan_ayah')
                                    ->label('Rata-rata Penghasilan (Ayah)')
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Fieldset::make('Data Ibu')
                            ->schema([
                                Forms\Components\TextInput::make('nama_ibu')
                                    ->label('Nama Ibu')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tempat_lahir_ibu')
                                    ->label('Tempat Lahir (Ibu)')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('tanggal_lahir_ibu')
                                    ->label('Tanggal Lahir (Ibu)'),
                                Forms\Components\TextInput::make('no_hp_ibu')
                                    ->label('No. Telp/HP (Ibu)')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pendidikan_ibu')
                                    ->label('Pendidikan (Ibu)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pekerjaan_ibu')
                                    ->label('Pekerjaan (Ibu)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('penghasilan_ibu')
                                    ->label('Rata-rata Penghasilan (Ibu)')
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Fieldset::make('Data Wali')
                            ->schema([
                                Forms\Components\TextInput::make('nama_wali')
                                    ->label('Nama Wali')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('no_hp_wali')
                                    ->label('No. Telp/HP (Wali)')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pendidikan_wali')
                                    ->label('Pendidikan (Wali)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('pekerjaan_wali')
                                    ->label('Pekerjaan (Wali)')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('penghasilan_wali')
                                    ->label('Rata-rata Penghasilan (Wali)')
                                    ->maxLength(255),
                            ])->columns(2),
                            
                        Forms\Components\Fieldset::make('Status Keuangan')
                            ->schema([
                                Forms\Components\Toggle::make('is_administrasi_lunas')
                                    ->label('Administrasi Lunas')
                                    ->helperText('Jika diaktifkan, orang tua & siswa dapat mengunduh E-Rapor di Portal.')
                                    ->default(false),
                            ])->columns(1),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ViewEntry::make('summary')
                    ->view('filament.staff.modals.student-detail-summary-modal')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nis')->label('NIS')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nama Siswa')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga / Unit')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole('superadmin')),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->placeholder('Belum Diatur')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_siswa')
                    ->label('Status')
                    ->state(function (Student $record): string {
                        if ($record->status_siswa === 'alumni' || $record->alumniTracer()->exists()) {
                            $thn = $record->tahun_lulus ?: $record->alumniTracer?->tahun_lulus;
                            return $thn ? "🎓 Alumni ({$thn})" : '🎓 Alumni';
                        }
                        return 'Siswa Aktif';
                    })
                    ->badge()
                    ->color(function (Student $record): string {
                        return ($record->status_siswa === 'alumni' || $record->alumniTracer()->exists()) ? 'info' : 'success';
                    }),
                Tables\Columns\TextColumn::make('student_achievements_count')
                    ->counts('studentAchievements')
                    ->label('Prestasi')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "🏆 {$state} Prestasi" : "-")
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_kelamin')->label('L/P'),
                Tables\Columns\TextColumn::make('character_score')->label('Skor Karakter'),
                Tables\Columns\TextColumn::make('user_account_status')
                    ->label('Status Akun')
                    ->state(function (Student $record): string {
                        return $record->user()->exists() ? 'Sudah Dibuat' : 'Belum Dibuat';
                    })
                    ->badge()
                    ->color(function (Student $record): string {
                        return $record->user()->exists() ? 'success' : 'danger';
                    })
                    ->icon(function (Student $record): string {
                        return $record->user()->exists() ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';
                    })
                    ->action(function (Student $record, $livewire) {
                        if (!auth()->user()->hasRole('superadmin')) return;
                        if ($record->user()->exists()) {
                            $livewire->mountTableAction('edit_akun', $record);
                        } else {
                            $livewire->mountTableAction('buat_akun', $record);
                        }
                    })
                    ->tooltip(fn () => auth()->user()->hasRole('superadmin') ? 'Klik untuk membuat / kelola akun' : null)
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Unit / Lembaga')
                    ->relationship('institution', 'name')
                    ->visible(fn () => auth()->user()->hasRole('superadmin')),
                Tables\Filters\SelectFilter::make('status_akun')
                    ->label('Status Akun')
                    ->options([
                        'sudah' => 'Sudah Dibuat',
                        'belum' => 'Belum Dibuat',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'sudah') {
                            $query->whereHas('user');
                        } elseif ($data['value'] === 'belum') {
                            $query->whereDoesntHave('user');
                        }
                    }),
                Tables\Filters\SelectFilter::make('status_siswa')
                    ->label('Status Siswa')
                    ->options([
                        'aktif' => 'Siswa Aktif',
                        'alumni' => '🎓 Alumni (Sudah Lulus)',
                    ]),
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship(
                        name: 'classroom',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => (!auth()->user()->hasRole('superadmin') && auth()->user()->institution_id != 1)
                            ? $query->where('institution_id', auth()->user()->institution_id)
                            : $query
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('jadikan_alumni')
                    ->label('Jadikan Alumni')
                    ->icon('heroicon-o-academic-cap')
                    ->button()
                    ->color('success')
                    ->visible(fn (Student $record) => auth()->user()->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'superadmin', 'kepala_sekolah']) && $record->status_siswa !== 'alumni' && !$record->alumniTracer()->exists())
                    ->modalHeading(fn (Student $record) => "Luluskan Siswa Menjadi Alumni: {$record->name}")
                    ->modalDescription(fn (Student $record) => "Data siswa dari Tata Usaha ({$record->name} - NIS: {$record->nis}) akan diubah menjadi Alumni dan otomatis masuk ke fitur Tracer Study (Alumni) lembaga ini.")
                    ->modalIcon('heroicon-o-academic-cap')
                    ->modalSubmitActionLabel('Ya, Jadikan Alumni & Masukkan ke Tracer Study')
                    ->form([
                        Forms\Components\Select::make('tahun_lulus')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->options(function () {
                                $curr = (int) date('Y');
                                $opts = [];
                                for ($y = $curr + 1; $y >= $curr - 10; $y--) {
                                    $opts[(string)$y] = (string)$y;
                                }
                                return $opts;
                            })
                            ->default(fn (Student $record) => !empty($record->tahun_lulus) ? (string) $record->tahun_lulus : (string) date('Y'))
                            ->required(),

                        Forms\Components\Select::make('status_kegiatan')
                            ->label('Status Aktivitas Pasca-Lulus (Bisa Diisi Sekarang atau Nanti di Tracer Study)')
                            ->options([
                                'belum_terlacak' => '📋 Belum Terlacak (Masuk Daftar Tracer Study untuk Dilengkapi Nanti)',
                                'melanjutkan_pendidikan' => '🎓 Melanjutkan Pendidikan (SMA / SMK / MA / PTN / PTS / Pondok)',
                                'bekerja' => '💼 Bekerja (Karyawan / Instansi / Swasta)',
                                'wirausaha' => '🚀 Wirausaha / Bisnis Mandiri',
                                'mencari_kerja' => '⏳ Mencari Kerja / Masa Tunggu',
                                'lainnya' => '🌐 Lainnya',
                            ])
                            ->default('belum_terlacak')
                            ->required(),

                        Forms\Components\TextInput::make('nama_instansi')
                            ->label('Nama Sekolah Lanjutan / Kampus / Tempat Kerja (Opsional)')
                            ->placeholder('Kosongkan jika belum diketahui (dapat dilengkapi di menu Tracer Study)')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('jurusan_bidang')
                            ->label('Jurusan / Program Studi / Posisi (Opsional)')
                            ->placeholder('Contoh: MIPA / Teknik Informatika')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. WhatsApp / HP Aktif Alumni')
                            ->default(fn (Student $record) => $record->no_telepon_siswa ?? $record->no_telepon_orang_tua ?? $record->no_hp_ayah ?? $record->no_hp_ibu)
                            ->tel(),
                    ])
                    ->action(function (Student $record, array $data) {
                        $record->update([
                            'status_siswa' => 'alumni',
                            'tahun_lulus' => $data['tahun_lulus'],
                        ]);

                        \App\Models\AlumniTracer::updateOrCreate(
                            ['student_id' => $record->id],
                            [
                                'institution_id' => $record->institution_id ?? auth()->user()->institution_id,
                                'nama' => $record->name,
                                'nis' => $record->nis,
                                'nisn' => $record->nisn ?? null,
                                'tahun_lulus' => $data['tahun_lulus'],
                                'status_kegiatan' => $data['status_kegiatan'] ?? 'belum_terlacak',
                                'nama_instansi' => $data['nama_instansi'] ?? null,
                                'jurusan_bidang' => $data['jurusan_bidang'] ?? null,
                                'no_telepon' => $data['no_telepon'] ?? $record->no_telepon_siswa ?? $record->no_telepon_orang_tua,
                                'alamat_domisili' => $record->alamat_siswa ?? null,
                                'input_by' => auth()->id(),
                            ]
                        );

                        Notification::make()
                            ->title('Siswa Berhasil Menjadi Alumni')
                            ->body("{$record->name} telah berstatus Alumni dan datanya otomatis masuk ke menu Tracer Study (Alumni).")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('lihat_tracer_alumni')
                    ->label('Sudah Alumni (Tracer)')
                    ->icon('heroicon-m-check-badge')
                    ->button()
                    ->color('info')
                    ->visible(fn (Student $record) => auth()->user()->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'superadmin', 'kepala_sekolah']) && ($record->status_siswa === 'alumni' || $record->alumniTracer()->exists()))
                    ->modalHeading(fn (Student $record) => "Update Data Tracer Study Alumni: {$record->name}")
                    ->modalDescription(fn (Student $record) => "Siswa ini sudah terdaftar di fitur Tracer Study. Anda dapat memperbarui data kelulusannya di bawah ini atau melalui menu Tracer Study (Alumni).")
                    ->modalIcon('heroicon-o-academic-cap')
                    ->fillForm(function (Student $record): array {
                        $tracer = $record->alumniTracer;
                        return [
                            'tahun_lulus' => $tracer?->tahun_lulus ?? $record->tahun_lulus ?? (string) date('Y'),
                            'status_kegiatan' => $tracer?->status_kegiatan ?? 'melanjutkan_pendidikan',
                            'nama_instansi' => $tracer?->nama_instansi,
                            'jurusan_bidang' => $tracer?->jurusan_bidang,
                            'no_telepon' => $tracer?->no_telepon ?? $record->no_telepon_siswa ?? $record->no_telepon_orang_tua,
                            'prestasi_setelah_lulus' => $tracer?->prestasi_setelah_lulus,
                            'kesan_pesan' => $tracer?->kesan_pesan,
                        ];
                    })
                    ->form([
                        Forms\Components\Select::make('tahun_lulus')
                            ->label('Tahun Kelulusan / Angkatan')
                            ->options(function () {
                                $curr = (int) date('Y');
                                $opts = [];
                                for ($y = $curr + 1; $y >= $curr - 10; $y--) {
                                    $opts[(string)$y] = (string)$y;
                                }
                                return $opts;
                            })
                            ->required(),

                        Forms\Components\Select::make('status_kegiatan')
                            ->label('Status Aktivitas Saat Ini')
                            ->options([
                                'belum_terlacak' => '📋 Belum Terlacak (Menunggu Konfirmasi)',
                                'melanjutkan_pendidikan' => '🎓 Melanjutkan Pendidikan (SMA / SMK / MA / PTN / PTS / Pondok)',
                                'bekerja' => '💼 Bekerja (Karyawan / Instansi / Swasta)',
                                'wirausaha' => '🚀 Wirausaha / Bisnis Mandiri',
                                'mencari_kerja' => '⏳ Mencari Kerja / Masa Tunggu',
                                'lainnya' => '🌐 Lainnya',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('nama_instansi')
                            ->label('Nama Sekolah Lanjutan / Kampus / Kerja')
                            ->placeholder('Contoh: SMAN 1 Surabaya / ITS Surabaya')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('jurusan_bidang')
                            ->label('Jurusan / Posisi Pekerjaan')
                            ->placeholder('Contoh: MIPA / Teknik Informatika')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. WhatsApp / HP Aktif')
                            ->tel(),

                        Forms\Components\Textarea::make('prestasi_setelah_lulus')
                            ->label('Prestasi Pasca-Lulus (Jika Ada)')
                            ->rows(2),

                        Forms\Components\Textarea::make('kesan_pesan')
                            ->label('Kesan & Pesan untuk Sekolah (Opsional)')
                            ->rows(2),
                    ])
                    ->action(function (Student $record, array $data) {
                        $record->update([
                            'status_siswa' => 'alumni',
                            'tahun_lulus' => $data['tahun_lulus'],
                        ]);

                        \App\Models\AlumniTracer::updateOrCreate(
                            ['student_id' => $record->id],
                            [
                                'institution_id' => $record->institution_id ?? auth()->user()->institution_id,
                                'nama' => $record->name,
                                'nis' => $record->nis,
                                'nisn' => $record->nisn ?? null,
                                'tahun_lulus' => $data['tahun_lulus'],
                                'status_kegiatan' => $data['status_kegiatan'],
                                'nama_instansi' => $data['nama_instansi'] ?? null,
                                'jurusan_bidang' => $data['jurusan_bidang'] ?? null,
                                'no_telepon' => $data['no_telepon'] ?? null,
                                'prestasi_setelah_lulus' => $data['prestasi_setelah_lulus'] ?? null,
                                'kesan_pesan' => $data['kesan_pesan'] ?? null,
                                'input_by' => auth()->id(),
                            ]
                        );

                        Notification::make()
                            ->title('Data Tracer Study Alumni Diperbarui')
                            ->body("Data penelusuran alumni untuk {$record->name} berhasil diperbarui.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('buat_akun')
                    ->label('Buat Akun')
                    ->icon('heroicon-m-user-plus')
                    ->button()
                    ->color('warning')
                    ->visible(fn (Student $record) => auth()->user()->hasRole('superadmin') && !$record->user()->exists())
                    ->modalHeading(fn (Student $record) => "Buat Akun Siswa: {$record->name}")
                    ->modalDescription('Lengkapi data akun login (email, username, password, dan foto) untuk siswa ini.')
                    ->form([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->maxSize(2048),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->default(fn (Student $record) => $record->name)
                            ->required(),
                        Forms\Components\Select::make('institution_id')
                            ->label('Unit / Lembaga')
                            ->relationship('institution', 'name')
                            ->default(fn (Student $record) => $record->institution_id)
                            ->required(),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->default(fn (Student $record) => $record->nis ? trim($record->nis) : Str::slug($record->name))
                            ->required()
                            ->unique('users', 'username'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn (Student $record) => ($record->nis ? trim($record->nis) . '@siswa.local' : Str::slug($record->name) . '@siswa.local'))
                            ->required()
                            ->unique('users', 'email'),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->default('siswa123'),
                    ])
                    ->action(function (Student $record, array $data) {
                        $user = User::create([
                            'student_id' => $record->id,
                            'institution_id' => $data['institution_id'] ?? $record->institution_id ?? auth()->user()->institution_id ?? 2,
                            'name' => $data['name'],
                            'username' => $data['username'],
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                            'avatar_url' => $data['avatar_url'] ?? null,
                            'has_account' => true,
                            'is_active' => true,
                        ]);

                        $user->assignRole('siswa');

                        Notification::make()
                            ->title('Akun Siswa Berhasil Dibuat')
                            ->body("Akun untuk {$record->name} berhasil diaktifkan dengan username {$data['username']}.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit_akun')
                    ->label('Kelola Akun')
                    ->icon('heroicon-m-key')
                    ->color('info')
                    ->visible(fn (Student $record) => auth()->user()->hasRole('superadmin') && $record->user()->exists())
                    ->modalHeading(fn (Student $record) => "Kelola / Reset Akun Siswa: {$record->name}")
                    ->modalDescription('Ubah data login siswa atau reset password baru jika siswa lupa password.')
                    ->form([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->image()
                            ->maxSize(2048)
                            ->default(fn (Student $record) => $record->user?->avatar_url),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->default(fn (Student $record) => $record->user?->username)
                            ->required()
                            ->unique('users', 'username', ignorable: fn (Student $record) => $record->user),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn (Student $record) => $record->user?->email)
                            ->required()
                            ->unique('users', 'email', ignorable: fn (Student $record) => $record->user),
                        Forms\Components\TextInput::make('password')
                            ->label('Password Baru (Reset Password)')
                            ->helperText('Isi jika ingin mereset password (misal siswa lupa password). Kosongkan jika password tidak diubah.')
                            ->password()
                            ->revealable()
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Akun Aktif')
                            ->default(fn (Student $record) => $record->user?->is_active ?? true),
                    ])
                    ->action(function (Student $record, array $data) {
                        $user = $record->user;
                        if (!$user) return;
                        
                        $updateData = [
                            'avatar_url' => $data['avatar_url'] ?? $user->avatar_url,
                            'username' => $data['username'],
                            'email' => $data['email'],
                            'is_active' => $data['is_active'] ?? true,
                        ];
                        if (!empty($data['password'])) {
                            $updateData['password'] = Hash::make($data['password']);
                        }
                        $user->update($updateData);

                        Notification::make()
                            ->title('Akun Siswa Berhasil Diperbarui')
                            ->body("Data akun siswa {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('input_prestasi')
                    ->label('Input Prestasi')
                    ->icon('heroicon-o-trophy')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']))
                    ->modalHeading(fn (Student $record) => "Input Prestasi Siswa: {$record->name}")
                    ->modalDescription(fn (Student $record) => "Catat prestasi untuk siswa {$record->name} (" . ($record->classroom?->name ?? 'Tanpa Kelas') . "). Data otomatis tersimpan untuk lembaga ini.")
                    ->modalIcon('heroicon-o-trophy')
                    ->form([
                        Forms\Components\Radio::make('lingkup')
                            ->label('Lingkup Prestasi')
                            ->options([
                                'individual' => 'Individual (Perorangan Siswa)',
                                'kelas' => 'Prestasi Kelas (Satu Rombel / Kelompok)',
                            ])
                            ->default('individual')
                            ->inline()
                            ->live(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Prestasi')
                            ->options([
                                'non_akademik' => 'Prestasi Non-Akademik (Seni, Olahraga, Pramuka, dll)',
                                'prestasi_kelas' => 'Prestasi Kelas (Religius, Karakter, Adab)',
                                'akademik' => 'Prestasi Akademik (Sains, Matematika, Lomba Mapel, KIR, dll)',
                            ])
                            ->default('non_akademik')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('sub_kategori')
                            ->label('Sub-Kategori Prestasi Kelas')
                            ->options([
                                'religius' => 'Prestasi Religius (Tahfidz, Sholat Berjamaah, Doa, dll)',
                                'karakter' => 'Prestasi Karakter (Disiplin, Gotong Royong, Kejujuran)',
                                'adab' => 'Prestasi Adab (Sopan Santun, Adab Guru & Teman, Kebersihan)',
                            ])
                            ->visible(fn (\Filament\Forms\Get $get) => $get('kategori') === 'prestasi_kelas')
                            ->required(fn (\Filament\Forms\Get $get) => $get('kategori') === 'prestasi_kelas'),

                        Forms\Components\Select::make('tingkat')
                            ->label('Tingkat Prestasi')
                            ->options([
                                'Sekolah / Gugus' => 'Sekolah / Gugus',
                                'Kecamatan' => 'Kecamatan',
                                'Kota / Kabupaten' => 'Kota / Kabupaten',
                                'Provinsi' => 'Provinsi',
                                'Nasional' => 'Nasional',
                                'Internasional' => 'Internasional',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('nama_prestasi')
                            ->label('Nama Prestasi / Juara')
                            ->placeholder('Contoh: Juara 1 Lomba MTQ Tingkat Kota')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('jenis_reward')
                            ->label('Reward Kesiswaan')
                            ->options([
                                'reward_religi' => 'Reward Religi (Mushaf Al-Qur\'an, Ziarah/Umroh, dll)',
                                'reward_akademik' => 'Reward Akademik (Beasiswa SPP, Piagam Juara, dll)',
                                'tanpa_reward' => 'Tanpa Reward Khusus',
                            ])
                            ->default('tanpa_reward')
                            ->live(),

                        Forms\Components\TextInput::make('reward_detail')
                            ->label('Bentuk / Rincian Reward')
                            ->placeholder('Contoh: Mushaf Al-Qur\'an + Sertifikat')
                            ->visible(fn (\Filament\Forms\Get $get) => in_array($get('jenis_reward'), ['reward_religi', 'reward_akademik']))
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Perolehan Prestasi')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('sertifikat')
                            ->label('Link Google Drive Sertifikat / Piagam / Bukti (Opsional)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive sertifikat atau bukti foto prestasi untuk menghemat ruang penyimpanan hosting.')
                            ->maxLength(2048)
                            ->nullable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Penyelenggara Lomba (Opsional)')
                            ->placeholder('Contoh: Diselenggarakan oleh Dinas Pendidikan Kota Surabaya')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (Student $record, array $data) {
                        \App\Models\StudentAchievement::create([
                            'student_id' => $record->id,
                            'classroom_id' => $record->classroom_id,
                            'institution_id' => $record->institution_id ?? auth()->user()->institution_id,
                            'lingkup' => $data['lingkup'] ?? 'individual',
                            'kategori' => $data['kategori'],
                            'sub_kategori' => $data['sub_kategori'] ?? null,
                            'jenis_reward' => $data['jenis_reward'] ?? 'tanpa_reward',
                            'reward_detail' => $data['reward_detail'] ?? null,
                            'nama_prestasi' => $data['nama_prestasi'],
                            'tingkat' => $data['tingkat'],
                            'tanggal' => $data['tanggal'],
                            'sertifikat' => $data['sertifikat'] ?? null,
                            'keterangan' => $data['keterangan'] ?? null,
                            'input_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Prestasi Siswa Berhasil Dicatat')
                            ->body("Prestasi '{$data['nama_prestasi']}' untuk {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('profil_lulusan')
                    ->label('Profil Lulusan')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->url(fn (Student $record) => \App\Filament\Staff\Pages\KetercapaianProfilLulusan::getUrl(['student_id' => $record->id, 'class_id' => $record->classroom_id]))
                    ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false),
                Tables\Actions\Action::make('pantau_siswa')
                    ->label('Pantau Prestasi & Profil')
                    ->icon('heroicon-o-identification')
                    ->color('info')
                    ->modalHeading(fn (Student $record) => "Profil & Rekam Jejak Siswa: {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl')
                    ->modalContent(fn (Student $record) => view('filament.staff.modals.student-monitoring-modal', [
                        'record' => $record->loadMissing([
                            'studentAchievements.inputBy',
                            'dailyAttendances',
                            'studentCases.parentSummons',
                            'parentSummons',
                            'attributeViolations.teacher',
                            'characterScoreLogs',
                        ]),
                    ])),
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading(fn (Student $record) => "Detail & Ringkasan Data Siswa: {$record->name}")
                    ->modalWidth('4xl'),
                Tables\Actions\Action::make('cetak_biodata_pdf')
                    ->label('Cetak Biodata (PDF)')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (Student $record) => route('academic.student.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Rapor Karakter')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->url(fn (Student $record) => route('report.character', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->hasRole('wali_kelas') || Auth::user()->hasRole('waka_kesiswaan')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('atur_kelas_massal')
                        ->label('Atur / Pindahkan Kelas Siswa Terpilih')
                        ->icon('heroicon-o-academic-cap')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('classroom_id')
                                ->label('Pilih Kelas Tujuan')
                                ->options(function () {
                                    $instId = auth()->user()?->institution_id;
                                    if (empty($instId) || $instId == 1) $instId = 2;
                                    return \App\Models\Classroom::withoutGlobalScopes()
                                        ->where(function ($q) use ($instId) {
                                            $q->where('institution_id', $instId)
                                              ->orWhere('institution_id', 1)
                                              ->orWhereNull('institution_id');
                                        })
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = 0;
                            $classId = (int) $data['classroom_id'];
                            foreach ($records as $record) {
                                $record->update(['classroom_id' => $classId]);
                                $count++;
                            }
                            Notification::make()
                                ->title("Berhasil Memperbarui Kelas")
                                ->body("{$count} siswa terpilih berhasil dimasukkan ke kelas sasaran.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('jadikan_alumni_massal')
                        ->label('Jadikan Alumni & Masukkan ke Tracer Study')
                        ->icon('heroicon-o-academic-cap')
                        ->color('success')
                        ->visible(fn () => auth()->user()->hasAnyRole(['waka_kesiswaan', 'tata_usaha', 'superadmin', 'kepala_sekolah']))
                        ->requiresConfirmation()
                        ->modalHeading('Luluskan Siswa Terpilih Menjadi Alumni')
                        ->modalDescription('Semua siswa yang dipilih akan diubah statusnya menjadi Alumni dan datanya otomatis dimasukkan ke menu Tracer Study (Alumni) sesuai lembaga masing-masing.')
                        ->form([
                            Forms\Components\Select::make('tahun_lulus')
                                ->label('Tahun Kelulusan / Angkatan')
                                ->options(function () {
                                    $curr = (int) date('Y');
                                    $opts = [];
                                    for ($y = $curr + 1; $y >= $curr - 10; $y--) {
                                        $opts[(string)$y] = (string)$y;
                                    }
                                    return $opts;
                                })
                                ->default((string) date('Y'))
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'status_siswa' => 'alumni',
                                    'tahun_lulus' => $data['tahun_lulus'],
                                ]);

                                \App\Models\AlumniTracer::updateOrCreate(
                                    ['student_id' => $record->id],
                                    [
                                        'institution_id' => $record->institution_id ?? auth()->user()->institution_id,
                                        'nama' => $record->name,
                                        'nis' => $record->nis,
                                        'nisn' => $record->nisn ?? null,
                                        'tahun_lulus' => $data['tahun_lulus'],
                                        'status_kegiatan' => $record->alumniTracer?->status_kegiatan ?? 'belum_terlacak',
                                        'no_telepon' => $record->alumniTracer?->no_telepon ?? $record->no_telepon_siswa ?? $record->no_telepon_orang_tua ?? $record->no_hp_ayah ?? $record->no_hp_ibu,
                                        'alamat_domisili' => $record->alumniTracer?->alamat_domisili ?? $record->alamat_siswa,
                                        'input_by' => auth()->id(),
                                    ]
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("Berhasil Meluluskan {$count} Siswa")
                                ->body("{$count} siswa telah berstatus Alumni dan otomatis masuk ke fitur Tracer Study.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('buat_akun_massal_siswa')
                        ->label('Buat Akun Siswa Massal')
                        ->icon('heroicon-m-user-plus')
                        ->color('warning')
                        ->visible(fn () => auth()->user()->hasRole('superadmin'))
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun Login Massal untuk Siswa Terpilih')
                        ->modalDescription('Sistem akan membuatkan akun login secara otomatis untuk semua siswa terpilih yang belum memiliki akun. Username & Email akan dibuat dari NIS, dan password default adalah "siswa123".')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $created = 0;
                            foreach ($records as $record) {
                                if ($record->user()->exists()) {
                                    continue;
                                }
                                $nis = !empty($record->nis) ? trim($record->nis) : Str::slug($record->name) . rand(100, 999);
                                $username = $nis;
                                $email = $nis . '@siswa.local';
                                
                                if (User::where('username', $username)->exists()) {
                                    $username .= rand(10, 99);
                                }
                                if (User::where('email', $email)->exists()) {
                                    $email = $nis . rand(10, 99) . '@siswa.local';
                                }

                                $user = User::create([
                                    'student_id' => $record->id,
                                    'institution_id' => $record->institution_id ?? auth()->user()->institution_id ?? 2,
                                    'name' => $record->name,
                                    'username' => $username,
                                    'email' => $email,
                                    'password' => Hash::make('siswa123'),
                                    'has_account' => true,
                                    'is_active' => true,
                                ]);

                                $user->assignRole('siswa');
                                $created++;
                            }

                            Notification::make()
                                ->title('Pembuatan Akun Massal Berhasil')
                                ->body("{$created} akun siswa berhasil dibuatkan dengan password default 'siswa123'.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStudents::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
