<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ExtracurricularJournalResource\Pages;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularJournal;
use App\Models\ExtracurricularMember;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExtracurricularJournalResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = ExtracurricularJournal::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Kesiswaan & Karakter';
        }
        if ($user?->hasRole('guru_ekstrakurikuler') && !$user?->hasAnyRole(['superadmin', 'kepala_sekolah'])) {
            return 'Tugas Guru Ekstrakurikuler';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getNavigationLabel(): string
    {
        $user = auth()->user();
        if ($user?->hasRole('waka_kesiswaan')) {
            return 'Jurnal Mengajar Ekstrakurikuler';
        }
        return 'Jurnal Mengajar Eskul';
    }

    public static function getModelLabel(): string
    {
        return 'Jurnal Mengajar Ekstrakurikuler';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jurnal Mengajar Ekstrakurikuler';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['extracurricular', 'pembina', 'verifier', 'attendances.student']);
        $user = Auth::user();
        if (!$user) return $query;

        // Waka Kesiswaan, Kepala Sekolah, Superadmin melihat seluruh jurnal mengajar eskul
        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        // Guru Ekstrakurikuler hanya melihat jurnal eskul yang dibinanya
        if ($user->hasRole('guru_ekstrakurikuler')) {
            $myEskulIds = Extracurricular::where('pembina_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $myEskulIds) {
                if (!empty($myEskulIds)) {
                    $q->whereIn('extracurricular_id', $myEskulIds);
                }
                $q->orWhere('pembina_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isGuruEskul = $user && $user->hasRole('guru_ekstrakurikuler') && !$user->hasAnyRole(['waka_kesiswaan', 'superadmin', 'kepala_sekolah']);
        $isKesiswaan = $user && $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi & Jadwal Pertemuan Ekstrakurikuler')
                    ->description('Pilih kegiatan ekstrakurikuler dan tanggal pelaksanaan kegiatan.')
                    ->schema([
                        Forms\Components\Select::make('extracurricular_id')
                            ->label('Kegiatan Ekstrakurikuler')
                            ->options(function () use ($user, $isGuruEskul) {
                                return Extracurricular::query()
                                    ->where('is_active', true)
                                    ->when($isGuruEskul, fn ($q) => $q->where('pembina_id', $user->id))
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state) {
                                if ($state) {
                                    $eskul = Extracurricular::find($state);
                                    if ($eskul) {
                                        $set('jam_mulai', $eskul->jam_mulai);
                                        $set('jam_selesai', $eskul->jam_selesai);
                                        $set('lokasi', $eskul->lokasi);
                                    }

                                    // Muat otomatis anggota eskul yang sudah di-routing jika repeater masih kosong
                                    $current = $get('attendances') ?? [];
                                    if (empty($current)) {
                                        $members = ExtracurricularMember::where('extracurricular_id', $state)
                                            ->where('is_active', true)
                                            ->get();
                                        $items = [];
                                        $tgl = $get('tanggal') ?? now()->toDateString();
                                        foreach ($members as $m) {
                                            $items[] = [
                                                'student_id' => $m->student_id,
                                                'status'     => 'hadir',
                                                'catatan'    => null,
                                                'tanggal'    => $tgl,
                                                'extracurricular_id' => $state,
                                            ];
                                        }
                                        if (!empty($items)) {
                                            $set('attendances', $items);
                                        }
                                    }
                                }
                            })
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pertemuan')
                            ->default(now()->toDateString())
                            ->required()
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\TimePicker::make('jam_mulai')
                            ->label('Jam Mulai')
                            ->seconds(false)
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\TimePicker::make('jam_selesai')
                            ->label('Jam Selesai')
                            ->seconds(false)
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\TextInput::make('lokasi')
                            ->label('Lokasi Kegiatan')
                            ->placeholder('Contoh: Lapangan Futsal / Aula / Lab Komputer')
                            ->maxLength(255)
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),
                    ])->columns(2),

                Forms\Components\Section::make('Materi & Kegiatan Pembelajaran Ekstrakurikuler')
                    ->description('Uraikan materi pelatihan dan aktivitas siswa yang diajarkan pada pertemuan ini.')
                    ->schema([
                        Forms\Components\TextInput::make('materi_kegiatan')
                            ->label('Materi / Fokus Pelatihan Hari Ini')
                            ->placeholder('Contoh: Teknik Menendang Bola (Passing & Shooting) serta Penguasaan Ruang')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\Textarea::make('deskripsi_kegiatan')
                            ->label('Uraian Pelaksanaan Kegiatan (Langkah-Langkah Mengajar)')
                            ->placeholder("Contoh:\n1. Pemanasan statis dan dinamis (15 menit)\n2. Latihan inti: variasi menendang sasaran gawang (45 menit)\n3. Mini match evaluasi penerapan teknik (30 menit)\n4. Pendinginan dan evaluasi bersama (15 menit)")
                            ->rows(4)
                            ->required()
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),

                        Forms\Components\Textarea::make('catatan_kegiatan')
                            ->label('Catatan Tambahan Pembina (Opsional)')
                            ->placeholder('Catatan kejadian penting, siswa berprestasi hari ini, atau sarana yang dibutuhkan.')
                            ->rows(2)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin')),
                    ]),

                Forms\Components\Section::make('Daftar Presensi Kehadiran Siswa Pertemuan Ini')
                    ->description('Absensi siswa yang sudah di-routing ikut ekstrakurikuler ini. Anda dapat menandai status hadir, sakit, izin, atau alpa.')
                    ->headerActions([
                        Forms\Components\Actions\Action::make('load_members')
                            ->label('🔄 Muat Siswa Terdaftar (Routing Eskul)')
                            ->color('primary')
                            ->visible(!$isKesiswaan)
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                $eskulId = $get('extracurricular_id');
                                if (!$eskulId) {
                                    Notification::make()
                                        ->title('Pilih ekstrakurikuler terlebih dahulu')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                $members = ExtracurricularMember::where('extracurricular_id', $eskulId)
                                    ->where('is_active', true)
                                    ->with('student.classroom')
                                    ->get();

                                if ($members->isEmpty()) {
                                    Notification::make()
                                        ->title('Belum Ada Siswa Dirouting')
                                        ->body('Belum ada siswa yang dirouting ke ekstrakurikuler ini. Waka Kesiswaan dapat merouting siswa melalui menu Data & Jadwal Ekstrakurikuler.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                $items = [];
                                $tgl = $get('tanggal') ?? now()->toDateString();
                                foreach ($members as $m) {
                                    $items[] = [
                                        'student_id'         => $m->student_id,
                                        'status'             => 'hadir',
                                        'catatan'            => null,
                                        'tanggal'            => $tgl,
                                        'extracurricular_id' => $eskulId,
                                    ];
                                }
                                $set('attendances', $items);

                                Notification::make()
                                    ->title('Siswa Berhasil Dimuat')
                                    ->body(count($items) . ' siswa anggota eskul berhasil dimuat ke daftar presensi.')
                                    ->success()
                                    ->send();
                            }),

                        Forms\Components\Actions\Action::make('set_all_hadir')
                            ->label('✅ Set Semua Hadir')
                            ->color('success')
                            ->visible(!$isKesiswaan)
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                $attendances = $get('attendances') ?? [];
                                foreach ($attendances as $key => $item) {
                                    $attendances[$key]['status'] = 'hadir';
                                }
                                $set('attendances', $attendances);
                            }),
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('attendances')
                            ->relationship('attendances')
                            ->label('Daftar Siswa Peserta Eskul')
                            ->schema([
                                Forms\Components\Select::make('student_id')
                                    ->label('Santri / Siswa')
                                    ->options(function () {
                                        return Student::query()
                                            ->with('classroom')
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($s) {
                                                $cls = $s->classroom ? " [{$s->classroom->name}]" : '';
                                                return [$s->id => "{$s->name}{$cls}"];
                                            });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin'))
                                    ->columnSpan(5),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'hadir' => '✅ Hadir',
                                        'izin'  => '📩 Izin',
                                        'sakit' => '🩺 Sakit',
                                        'alpa'  => '❌ Alpa',
                                    ])
                                    ->default('hadir')
                                    ->required()
                                    ->native(false)
                                    ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin'))
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('catatan')
                                    ->label('Catatan Tambahan')
                                    ->placeholder('Opsional (alasan izin/sakit/evaluasi)')
                                    ->disabled(fn ($record) => $record && $isKesiswaan && !$user->hasRole('superadmin'))
                                    ->columnSpan(4),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addable(fn ($record) => !($record && $isKesiswaan && !$user->hasRole('superadmin')))
                            ->deletable(fn ($record) => !($record && $isKesiswaan && !$user->hasRole('superadmin')))
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (!empty($state['student_id'])) {
                                    $student = Student::with('classroom')->find($state['student_id']);
                                    if ($student) {
                                        $status = strtoupper($state['status'] ?? 'hadir');
                                        $kelas = $student->classroom ? " ({$student->classroom->name})" : '';
                                        return "{$student->name}{$kelas} - [{$status}]";
                                    }
                                }
                                return null;
                            })
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Forms\Get $get): array {
                                $data['extracurricular_id'] = $get('extracurricular_id');
                                $data['tanggal'] = $get('tanggal') ?? now()->toDateString();
                                $data['pembina_id'] = auth()->id();
                                $data['institution_id'] = auth()->user()?->institution_id;
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Forms\Get $get): array {
                                if (!empty($get('extracurricular_id'))) {
                                    $data['extracurricular_id'] = $get('extracurricular_id');
                                }
                                if (!empty($get('tanggal'))) {
                                    $data['tanggal'] = $get('tanggal');
                                }
                                $data['pembina_id'] = auth()->id();
                                $data['institution_id'] = auth()->user()?->institution_id;
                                return $data;
                            }),
                    ]),

                // Bagian Verifikasi Kesiswaan
                Forms\Components\Section::make('Verifikasi & Evaluasi Kesiswaan')
                    ->visible(fn ($record) => $record !== null)
                    ->schema([
                        Forms\Components\Select::make('status_verifikasi')
                            ->label('Status Verifikasi Kesiswaan')
                            ->options([
                                'menunggu_verifikasi' => '⏳ Menunggu Verifikasi',
                                'diterima'            => '✅ Diterima (Disetujui Kesiswaan)',
                                'perlu_revisi'        => '⚠️ Perlu Revisi / Perbaikan',
                            ])
                            ->default('menunggu_verifikasi')
                            ->disabled(!$isKesiswaan),

                        Forms\Components\Textarea::make('catatan_kesiswaan')
                            ->label('Catatan & Feedback Kesiswaan')
                            ->placeholder('Catatan atau evaluasi dari Waka Kesiswaan untuk Guru Pembina Ekstrakurikuler.')
                            ->rows(3)
                            ->disabled(!$isKesiswaan)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isKesiswaan = $user && $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('extracurricular.name')
                    ->label('Ekstrakurikuler')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pembina.name')
                    ->label('Pembina / Pelatih')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('materi_kegiatan')
                    ->label('Materi Pelatihan')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->materi_kegiatan)
                    ->searchable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Peserta Hadir')
                    ->formatStateUsing(function ($state, ExtracurricularJournal $record) {
                        $hadir = $record->attendances()->where('status', 'hadir')->count();
                        $total = $record->attendances()->count();
                        return $total > 0 ? "{$hadir}/{$total} Siswa" : "{$state} Siswa";
                    })
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Verifikasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'diterima'            => 'success',
                        'perlu_revisi'        => 'danger',
                        default               => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'diterima'            => '✅ Diterima',
                        'perlu_revisi'        => '⚠️ Perlu Revisi',
                        default               => '⏳ Menunggu',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan_kesiswaan')
                    ->label('Catatan Kesiswaan')
                    ->placeholder('-')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->catatan_kesiswaan),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('extracurricular_id')
                    ->label('Ekstrakurikuler')
                    ->relationship('extracurricular', 'name'),

                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'menunggu_verifikasi' => 'Menunggu Verifikasi',
                        'diterima'            => 'Diterima',
                        'perlu_revisi'        => 'Perlu Revisi',
                    ]),
            ])
            ->actions([
                // Tombol Terima Laporan khusus Kesiswaan
                Tables\Actions\Action::make('terima_laporan')
                    ->label('Terima Laporan')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn (ExtracurricularJournal $record) => $isKesiswaan && $record->status_verifikasi !== 'diterima')
                    ->modalHeading(fn (ExtracurricularJournal $record) => "Terima & Verifikasi Laporan Jurnal: {$record->extracurricular?->name}")
                    ->modalDescription('Verifikasi kegiatan ekstrakurikuler ini dan berikan catatan atau apresiasi untuk Guru Pembina.')
                    ->form([
                        Forms\Components\Select::make('status_verifikasi')
                            ->label('Pilih Keputusan Verifikasi')
                            ->options([
                                'diterima'     => '✅ Terima Laporan (Disetujui Sesuai Pelaksanaan)',
                                'perlu_revisi' => '⚠️ Minta Perbaikan / Revisi',
                            ])
                            ->default('diterima')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_kesiswaan')
                            ->label('Catatan & Feedback Kesiswaan')
                            ->placeholder('Contoh: Laporan kegiatan diterima dengan sangat baik. Materi teknik dasar sesuai dengan target program kerja.')
                            ->default('Laporan kegiatan ekstrakurikuler telah diverifikasi dan disetujui oleh Waka Kesiswaan.')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ExtracurricularJournal $record, array $data) {
                        $record->update([
                            'status_verifikasi' => $data['status_verifikasi'],
                            'catatan_kesiswaan' => $data['catatan_kesiswaan'],
                            'verified_at'       => now(),
                            'verified_by'       => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Laporan Jurnal Berhasil Diverifikasi')
                            ->body("Status jurnal {$record->extracurricular?->name} tanggal " . $record->tanggal->format('d/m/Y') . " telah diubah menjadi " . ucfirst($data['status_verifikasi']) . ".")
                            ->success()
                            ->send();
                    }),

                // Tombol Beri Catatan Tambahan (bisa kapan saja oleh kesiswaan)
                Tables\Actions\Action::make('beri_catatan')
                    ->label('Beri Catatan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('warning')
                    ->visible(fn () => $isKesiswaan)
                    ->modalHeading(fn (ExtracurricularJournal $record) => "Beri Catatan Kesiswaan: {$record->extracurricular?->name}")
                    ->fillForm(fn (ExtracurricularJournal $record) => [
                        'catatan_kesiswaan' => $record->catatan_kesiswaan,
                    ])
                    ->form([
                        Forms\Components\Textarea::make('catatan_kesiswaan')
                            ->label('Catatan & Evaluasi Kesiswaan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (ExtracurricularJournal $record, array $data) {
                        $record->update([
                            'catatan_kesiswaan' => $data['catatan_kesiswaan'],
                        ]);

                        Notification::make()
                            ->title('Catatan Berhasil Disimpan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (ExtracurricularJournal $record) => $user->hasRole('superadmin') || ($user->hasRole('guru_ekstrakurikuler') && $record->status_verifikasi !== 'diterima')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExtracurricularJournals::route('/'),
            'create' => Pages\CreateExtracurricularJournal::route('/create'),
            'view'   => Pages\ViewExtracurricularJournal::route('/{record}'),
            'edit'   => Pages\EditExtracurricularJournal::route('/{record}/edit'),
        ];
    }
}
