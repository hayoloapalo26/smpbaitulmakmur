<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherLeaveRequestResource\Pages;
use App\Models\TeacherLeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeacherLeaveRequestResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_mapel', 'waka_kurikulum', 'kepala_sekolah', 'tata_usaha', 'superadmin']);
    }

    public static function canCreate(): bool
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return true;
    }

    public static function canEdit($record): bool
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return parent::canEdit($record);
    }

    public static function canDelete($record): bool
    {
        if (Auth::user()?->hasRole('kepala_sekolah')) {
            return false;
        }
        return parent::canDelete($record);
    }

    protected static ?string $model = TeacherLeaveRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $modelLabel = 'Perizinan Guru';

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 'Akademik & Kurikulum';
        }

        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 'Manajemen Sekolah';
        }

        if (auth()->user()?->hasRole('superadmin')) {
            return 'Manajemen Administrasi';
        }

        return 'Administrasi & Data Induk';
    }

    public static function getNavigationSort(): ?int
    {
        if (auth()->user()?->hasRole('waka_kurikulum')) {
            return 6;
        }

        if (auth()->user()?->hasRole('kepala_sekolah')) {
            return 9;
        }

        return null;
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            $count = TeacherLeaveRequest::where('status', 'diajukan')->count();
            return $count > 0 ? (string)$count : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'tata_usaha', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', Auth::id());
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengajuan Izin')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->label('Nama Guru')
                            ->relationship('teacher', 'name', modifyQueryUsing: function (Builder $query) {
                                $user = Auth::user();
                                if ($user->institution_id && !$user->hasRole('superadmin')) {
                                    $query->where(function ($q) use ($user) {
                                        $q->where('institution_id', $user->institution_id)
                                          ->orWhere('institution_id', 1)
                                          ->orWhereNull('institution_id');
                                    });
                                }
                                $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum']));
                            })
                            ->default(Auth::id())
                            ->searchable()
                            ->preload()
                            ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']))
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Hidden::make('teacher_id')
                            ->default(Auth::id())
                            ->visible(fn () => !Auth::user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])),

                        Forms\Components\Radio::make('tipe_izin')
                            ->label('Pilihan Waktu / Durasi Izin')
                            ->options([
                                'harian' => 'Izin Seharian Penuh / Rentang Beberapa Hari',
                                'jam_tertentu' => 'Izin Jam Pelajaran Tertentu (Di Jam Keberapa)',
                            ])
                            ->descriptions([
                                'harian' => 'Izin tidak mengajar seharian penuh atau beberapa hari (misal 1 hari, 2 hari, atau lebih).',
                                'jam_tertentu' => 'Izin hanya pada jam pelajaran tertentu (misal jam ke-1 s/d jam ke-3). Jam lainnya tetap wajib mengajar.',
                            ])
                            ->default('harian')
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label(fn (Forms\Get $get) => $get('tipe_izin') === 'jam_tertentu' ? 'Tanggal Izin' : 'Tanggal Mulai Izin')
                            ->default(now()->toDateString())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($get('tipe_izin') === 'jam_tertentu') {
                                    $set('tanggal_selesai', $state);
                                }
                            }),

                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai Izin')
                            ->default(now()->toDateString())
                            ->required(fn (Forms\Get $get) => $get('tipe_izin') !== 'jam_tertentu')
                            ->visible(fn (Forms\Get $get) => $get('tipe_izin') !== 'jam_tertentu')
                            ->minDate(fn (Forms\Get $get) => $get('tanggal_mulai'))
                            ->helperText(function (Forms\Get $get) {
                                if (!$get('tanggal_mulai') || !$get('tanggal_selesai')) return null;
                                $start = \Carbon\Carbon::parse($get('tanggal_mulai'));
                                $end = \Carbon\Carbon::parse($get('tanggal_selesai'));
                                if ($end->lessThan($start)) return '⚠️ Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
                                $diff = $start->diffInDays($end) + 1;
                                return "Durasi Izin: {$diff} hari kalender.";
                            }),

                        Forms\Components\Fieldset::make('Pilihan Jam Pelajaran Yang Izin')
                            ->schema([
                                Forms\Components\CheckboxList::make('jam_ke')
                                    ->label('Centang Jam Pelajaran Yang Izin (Jam Keberapa & Keterangan Waktu):')
                                    ->options(\App\Models\Schedule::getJamOptions())
                                    ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 4])
                                    ->helperText(function (Forms\Get $get) {
                                        $teacherId = $get('teacher_id') ?: Auth::id();
                                        $tanggal = $get('tanggal_mulai') ?: now()->toDateString();
                                        $hari = \App\Models\Schedule::getIndonesianDayName($tanggal);
                                        $schedules = \App\Models\Schedule::where('teacher_id', $teacherId)
                                            ->where('hari', $hari)
                                            ->with(['classroom', 'subject'])
                                            ->orderBy('jam_ke')
                                            ->get();

                                        if ($schedules->isEmpty()) {
                                            return "💡 Anda tidak memiliki jadwal mengajar pada hari {$hari} ({$tanggal}). Namun Anda tetap dapat mencentang jam dinas/tugas yang diizinkan.";
                                        }

                                        $schedText = $schedules->map(fn ($s) => \App\Models\Schedule::getJamLabelWithTime($s->jam_ke) . " [{$s->classroom?->name} - {$s->subject?->name}]")->implode(', ');
                                        return "📅 Jadwal Mengajar Anda pada {$hari}: {$schedText}";
                                    })
                                    ->required(fn (Forms\Get $get) => $get('tipe_izin') === 'jam_tertentu')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('tipe_izin') === 'jam_tertentu')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('substitute_teacher_id')
                            ->label('Guru Pengganti (Inval)')
                            ->options(fn (Forms\Get $get) => \App\Models\User::getSubstituteTeacherOptions($get('teacher_id') ?: Auth::id()))
                            ->searchable()
                            ->placeholder('Pilih Guru Pengganti atau Guru Piket...')
                            ->helperText('Pilih guru pengganti jika ada guru tertentu yang menggantikan (akan mendapatkan jam inval), atau pilih opsi "Guru Piket" jika kelas di-inval oleh guru piket (guru piket tidak mendapatkan jam inval).'),

                        Forms\Components\Textarea::make('alasan')
                            ->label('Alasan Izin')
                            ->placeholder('Jelaskan alasan perizinan (contoh: Sakit, Dinas Luar, Urusan Keluarga, dll)...')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('supporting_letter')
                            ->label('Surat Pendukung / Bukti Izin (Foto/Dokumen)')
                            ->directory('teacher-leave-letters')
                            ->image()
                            ->maxSize(5120)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'diajukan' => 'Diajukan',
                                'disetujui_wakakur' => 'Disetujui Waka Kurikulum (ACC)',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('diajukan')
                            ->disabled(fn () => !Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin']))
                            ->visible(fn () => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),

                        Forms\Components\Hidden::make('status')
                            ->default('diajukan')
                            ->visible(fn () => !Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Status Verifikasi & ACC')
                    ->description('Status persetujuan perizinan guru oleh Waka Kurikulum')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Persetujuan')
                            ->badge()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->color(fn (string $state): string => match ($state) {
                                'diajukan' => 'warning',
                                'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'success',
                                'ditolak' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'diajukan' => '⏳ Belum di-ACC (Menunggu Waka Kurikulum)',
                                'disetujui_wakakur', 'disetujui', 'disetujui_kepsek' => '✅ Sudah di-ACC (Disetujui Waka Kurikulum)',
                                'ditolak' => '❌ Ditolak',
                                default => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('wakakurApprover.name')
                            ->label('Di-ACC Oleh (Waka Kurikulum)')
                            ->placeholder('Belum di-ACC')
                            ->icon('heroicon-o-user-check'),
                        Infolists\Components\TextEntry::make('wakakur_approved_at')
                            ->label('Waktu di-ACC')
                            ->dateTime('d F Y - H:i WIB')
                            ->placeholder('-')
                            ->icon('heroicon-o-clock'),
                        Infolists\Components\TextEntry::make('substituteTeacher.name')
                            ->label('Guru Pengganti / Inval')
                            ->placeholder('Tidak ada guru pengganti')
                            ->icon('heroicon-o-user-plus')
                            ->badge()
                            ->color(fn ($state) => str_contains(strtolower($state ?? ''), 'piket') ? 'warning' : 'info'),
                    ])->columns(2),

                Infolists\Components\Section::make('Rincian Guru & Waktu Izin')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('Nama Guru')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('tipe_izin')
                            ->label('Tipe / Durasi Izin')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state === 'jam_tertentu' ? 'Jam Pelajaran Tertentu' : 'Harian Penuh / Beberapa Hari')
                            ->color(fn ($state) => $state === 'jam_tertentu' ? 'info' : 'primary'),
                        Infolists\Components\TextEntry::make('tanggal_mulai')
                            ->label('Tanggal Izin')
                            ->icon('heroicon-o-calendar')
                            ->formatStateUsing(function ($record) {
                                $start = \Carbon\Carbon::parse($record->tanggal_mulai)->translatedFormat('l, d F Y');
                                if ($record->tipe_izin === 'jam_tertentu' || $record->tanggal_mulai == $record->tanggal_selesai) {
                                    return $start;
                                }
                                $end = \Carbon\Carbon::parse($record->tanggal_selesai)->translatedFormat('l, d F Y');
                                return "{$start} s/d {$end}";
                            }),
                        Infolists\Components\TextEntry::make('waktu_izin_label')
                            ->label('Rincian Jam / Durasi Izin')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('waktu_izin_detail')
                            ->label('Keterangan Jam Keberapa & Waktu Mengajar (WIB)')
                            ->visible(fn ($record) => $record->tipe_izin === 'jam_tertentu' && !empty($record->waktu_izin_detail))
                            ->columnSpanFull()
                            ->badge()
                            ->color('info'),
                    ])->columns(2),

                Infolists\Components\Section::make('Keperluan Izin ("Ngapain") & Berkas Pendukung')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('alasan')
                            ->label('Keperluan / Alasan Perizinan ("Ngapain")')
                            ->columnSpanFull()
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->color('gray')
                            ->weight('medium'),
                        Infolists\Components\ImageEntry::make('supporting_letter')
                            ->label('Surat Keterangan / Foto Bukti Lampiran')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada lampiran surat/foto.')
                            ->disk('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable()
                    ->sortable()
                    ->label('Guru'),
                Tables\Columns\TextColumn::make('tipe_izin')
                    ->label('Tipe Izin')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'jam_tertentu' ? 'Jam Tertentu' : 'Harian')
                    ->color(fn ($state) => $state === 'jam_tertentu' ? 'info' : 'primary'),
                Tables\Columns\TextColumn::make('waktu_izin_label')
                    ->label('Waktu / Jam Izin')
                    ->badge()
                    ->color('primary')
                    ->tooltip(fn ($record) => $record->waktu_izin_detail),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Tanggal')
                    ->formatStateUsing(function ($record) {
                        $start = \Carbon\Carbon::parse($record->tanggal_mulai)->translatedFormat('d M Y');
                        if ($record->tipe_izin === 'jam_tertentu' || $record->tanggal_mulai == $record->tanggal_selesai) {
                            return $start;
                        }
                        $end = \Carbon\Carbon::parse($record->tanggal_selesai)->translatedFormat('d M Y');
                        return "{$start} s/d {$end}";
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('substituteTeacher.name')
                    ->label('Guru Pengganti')
                    ->placeholder('-')
                    ->badge()
                    ->color(fn ($state) => str_contains(strtolower($state ?? ''), 'piket') ? 'warning' : 'gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('alasan')
                    ->label('Alasan')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->alasan),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status ACC')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'diajukan' => 'warning',
                        'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'diajukan' => 'Belum di-ACC',
                        'disetujui_wakakur', 'disetujui_kepsek', 'disetujui' => 'Sudah di-ACC (Wakakur)',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => !Auth::user()->hasRole('kepala_sekolah'))
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['tanggal_selesai']) || ($data['tipe_izin'] ?? 'harian') === 'jam_tertentu') {
                            $data['tanggal_selesai'] = $data['tanggal_mulai'] ?? now()->toDateString();
                        }
                        return $data;
                    }),
                Tables\Actions\Action::make('acc_wakakur')
                    ->label('ACC Wakakur')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi ACC Perizinan Guru')
                    ->modalDescription('Setelah di-ACC oleh Waka Kurikulum, perizinan guru otomatis aktif. Guru yang bersangkutan tidak dapat mengisi jurnal mengajar pada waktu tersebut.')
                    ->visible(fn ($record) => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin']) && $record->status === 'diajukan')
                    ->form([
                        Forms\Components\Select::make('substitute_teacher_id')
                            ->label('Pilih Guru Pengganti (Inval)')
                            ->options(fn ($record) => \App\Models\User::getSubstituteTeacherOptions($record?->teacher_id))
                            ->searchable()
                            ->placeholder('Pilih Guru Pengganti atau Guru Piket...')
                            ->helperText('Pilih guru pengganti yang akan mendapatkan jam inval sesuai jumlah JP, atau pilih opsi "Guru Piket" jika kelas di-inval oleh guru piket (guru piket tidak mendapatkan jam inval).')
                            ->default(fn ($record) => $record?->substitute_teacher_id),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'disetujui_wakakur',
                            'substitute_teacher_id' => $data['substitute_teacher_id'] ?? $record->substitute_teacher_id,
                            'wakakur_approved_by' => Auth::id(),
                            'wakakur_approved_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Perizinan Berhasil di-ACC')
                            ->body('Perizinan telah disetujui (ACC) oleh Waka Kurikulum. Guru yang bersangkutan otomatis tidak dapat mengisi jurnal mengajar pada waktu tersebut.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Tolak Perizinan')
                    ->modalDescription('Apakah Anda yakin ingin menolak perizinan guru ini?')
                    ->visible(fn ($record) => Auth::user()->hasAnyRole(['waka_kurikulum', 'superadmin']) && $record->status === 'diajukan')
                    ->action(function ($record) {
                        $record->update(['status' => 'ditolak']);
                        \Filament\Notifications\Notification::make()
                            ->title('Perizinan Ditolak')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeacherLeaveRequests::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
