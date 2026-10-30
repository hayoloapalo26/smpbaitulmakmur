<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\OptionalTaskResource\Pages;
use App\Models\Institution;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OptionalTaskResource extends Resource
{
    protected static ?string $model = OptionalTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'Tugas Insidentil';
    protected static ?string $modelLabel = 'Tugas Insidentil';
    protected static ?string $pluralModelLabel = 'Tugas Insidentil';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user?->hasAnyRole(['kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']) ?? false;
    }

    public static function getRoleOptions(): array
    {
        return [
            'humas'                      => '📢 Humas (Hubungan Masyarakat)',
            'sarpras'                    => '🛠️ Sarpras (Sarana & Prasarana)',
            'tata_usaha'                 => '📑 Tata Usaha (TU)',
            'waka_kurikulum'             => '📘 Waka Kurikulum',
            'waka_kesiswaan'             => '🏆 Waka Kesiswaan',
            'wali_kelas'                 => '👨‍🏫 Wali Kelas',
            'guru_mapel'                 => '📖 Guru Mata Pelajaran',
            'guru_alquran'               => '🕋 Guru Al-Qur\'an (Umum)',
            'guru_quran_yanbua'          => '📖 Guru Al-Qur\'an (Yanbu\'a)',
            'guru_quran_tahsin'          => '⭐ Guru Al-Qur\'an (Tahsin)',
            'guru_quran_tahfidz_dasar'   => '🌱 Guru Al-Qur\'an (Tahfidz Dasar)',
            'guru_quran_tahfidz_lanjutan'=> '🌳 Guru Al-Qur\'an (Tahfidz Lanjutan)',
            'koordinator_madin'          => '🕌 Koordinator Madin',
            'bk'                         => '🤝 Guru BK (Bimbingan Konseling)',
            'guru_piket'                 => '⏱️ Guru Piket',
            'guru_ekstrakurikuler'       => '⚽ Pembina Ekstrakurikuler',
            'pegawai_keuangan'           => '💰 Pegawai Keuangan / Bendahara',
        ];
    }

    public static function getRoleLabel(string $role): string
    {
        $options = self::getRoleOptions();
        return $options[$role] ?? ucwords(str_replace('_', ' ', $role));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📌 1. Arahan & Informasi Tugas Insidentil (Oleh Kepala Sekolah)')
                    ->description('Kepala Sekolah menugaskan tugas insidentil untuk didelegasikan kepada Humas, Sarpras, divisi tertentu, personel spesifik, atau seluruh warga lembaga.')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Unit Sekolah')
                            ->options(Institution::where('id', '!=', 1)->pluck('name', 'id'))
                            ->default(fn () => auth()->user()?->institution_id)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas Insidentil')
                            ->placeholder('Contoh: Koordinasi Penyiapan Sarana Ujian / Liputan Dokumentasi Milad')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Uraian Arahan & Instruksi Kepala Sekolah')
                            ->placeholder('Tuliskan rincian instruksi dan petunjuk pelaksanaan tugas insidentil ini...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tenggat Waktu Pelaksanaan (Opsional)')
                            ->helperText('Batas waktu penyelesaian tugas oleh staf/guru.'),

                        Forms\Components\TextInput::make('reward_points')
                            ->label('Poin Apresiasi Kinerja')
                            ->numeric()
                            ->default(5)
                            ->helperText('Poin apresiasi yang diterima staf saat menyelesaikan tugas ini.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->helperText('Tugas insidentil hanya akan muncul pada staf jika berstatus aktif.')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('🎯 2. Sasaran Penerima Tugas (Diarahkan Kepada Siapa)')
                    ->description('Tentukan apakah tugas ini ditujukan untuk seluruh lembaga, divisi/peran tertentu (seperti Humas/Sarpras), atau pegawai tertentu.')
                    ->schema([
                        Forms\Components\Radio::make('target_type')
                            ->label('Tipe Sasaran Tugas')
                            ->options([
                                'all'   => '🌐 Seluruh Warga Lembaga (Semua Guru & Staf)',
                                'roles' => '👥 Divisi / Jabatan Tertentu (contoh: Humas, Sarpras, Guru, TU, dll.)',
                                'users' => '👤 Staf / Pegawai Spesifik (Pilih Orang per Orang)',
                            ])
                            ->default('roles')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('target_roles')
                            ->label('Pilih Divisi / Role Penerima Tugas')
                            ->multiple()
                            ->options(self::getRoleOptions())
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'roles')
                            ->required(fn (Forms\Get $get) => $get('target_type') === 'roles')
                            ->helperText('Pilih satu atau beberapa divisi yang ditugaskan (contoh: Humas, Sarpras).')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('target_user_ids')
                            ->label('Pilih Pegawai / Staf Penerima Tugas')
                            ->multiple()
                            ->searchable()
                            ->options(function () {
                                $instId = auth()->user()?->institution_id;
                                return User::query()
                                    ->when($instId && $instId != 1, fn ($q) => $q->where('institution_id', $instId))
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'users')
                            ->required(fn (Forms\Get $get) => $get('target_type') === 'users')
                            ->helperText('Pilih nama-nama pegawai/guru di lembaga ini yang secara khusus ditugaskan.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('📝 3. Format Laporan & Bukti Pelaksanaan (Builder)')
                    ->description('Rancang pertanyaan atau ceklis yang harus diisi oleh staf saat melaporkan pelaksanaan tugas insidentil ini.')
                    ->schema([
                        Forms\Components\Repeater::make('form_schema')
                            ->label('Daftar Kolom Input Laporan')
                            ->default([
                                [
                                    'field_name' => 'Konfirmasi Pelaksanaan Tugas Insidentil',
                                    'field_type' => 'checkbox',
                                    'is_required' => true,
                                ],
                                [
                                    'field_name' => 'Uraian Hasil Pelaksanaan & Catatan',
                                    'field_type' => 'textarea',
                                    'is_required' => true,
                                ],
                                [
                                    'field_name' => 'Link Google Drive Bukti / Dokumentasi',
                                    'field_type' => 'file',
                                    'is_required' => false,
                                ],
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('field_name')
                                    ->label('Nama Field (Pertanyaan / Input)')
                                    ->required(),
                                Forms\Components\Select::make('field_type')
                                    ->label('Tipe Input')
                                    ->options([
                                        'checkbox' => 'Ceklis (Ya/Sudah)',
                                        'text'     => 'Teks Singkat',
                                        'textarea' => 'Paragraf / Catatan',
                                        'file'     => 'Upload Bukti (Link Google Drive / Berkas)',
                                    ])
                                    ->required()
                                    ->default('text'),
                                Forms\Components\Toggle::make('is_required')
                                    ->label('Wajib Diisi')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['field_name'] ?? null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->hasRole('superadmin')),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Tugas Insidentil')
                    ->weight('bold')
                    ->description(fn (OptionalTask $record): string => $record->description ?? '')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sasaran')
                    ->label('Sasaran Penerima')
                    ->getStateUsing(function (OptionalTask $record) {
                        if ($record->target_type === 'all') {
                            return '🌐 Seluruh Lembaga';
                        }
                        if ($record->target_type === 'users') {
                            $userIds = is_array($record->target_user_ids) ? $record->target_user_ids : [];
                            $count = count($userIds);
                            if ($count === 0) return '👤 Belum Ditentukan';
                            if ($count <= 2) {
                                $names = User::whereIn('id', $userIds)->pluck('name')->toArray();
                                return '👤 ' . implode(', ', $names);
                            }
                            return "👤 {$count} Pegawai Tertentu";
                        }
                        $roles = is_array($record->target_roles) ? $record->target_roles : [];
                        if (empty($roles)) return 'Belum Ditentukan';
                        return collect($roles)
                            ->map(fn ($r) => self::getRoleLabel($r))
                            ->implode(', ');
                    })
                    ->badge()
                    ->color(fn (OptionalTask $record) => match ($record->target_type) {
                        'all'   => 'purple',
                        'users' => 'warning',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tenggat Waktu')
                    ->date('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('reward_points')
                    ->label('Poin Apresiasi')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ? "+{$state} Poin" : '+5 Poin'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Respon')
                    ->counts('submissions')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => "{$state} Laporan"),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('lihat_respon')
                    ->label('Lihat Respon & Laporan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->modalHeading(fn (OptionalTask $record) => 'Laporan Respon Tugas Insidentil: ' . $record->title)
                    ->modalContent(function (OptionalTask $record) {
                        $submissions = OptionalTaskSubmission::with('user')
                            ->where('optional_task_id', $record->id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        return view('filament.staff.pages.task-submissions-modal', [
                            'record' => $record,
                            'submissions' => $submissions,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum Ada Tugas Insidentil')
            ->emptyStateDescription('Kepala Sekolah dapat membuat tugas insidentil baru untuk ditugaskan kepada Humas, Sarpras, divisi lain, atau seluruh warga lembaga.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOptionalTasks::route('/'),
            'create' => Pages\CreateOptionalTask::route('/create'),
            'edit'   => Pages\EditOptionalTask::route('/{record}/edit'),
        ];
    }
}
