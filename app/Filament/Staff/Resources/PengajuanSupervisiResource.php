<?php

namespace App\Filament\Staff\Resources;

use App\Models\TeacherSupervision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PengajuanSupervisiResource extends Resource
{
    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Penilaian & Kompetensi';
    protected static ?string $navigationLabel = 'Supervisi Guru';
    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('kepala_sekolah')
            ? 'Manajemen Sekolah'
            : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return auth()->user()?->hasRole('kepala_sekolah') ? 7 : static::$navigationSort;
    }
    protected static ?string $modelLabel = 'Supervisi Guru';
    protected static ?string $pluralModelLabel = 'Supervisi Guru';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->orderBy('created_at', 'desc');
        if (Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum'])) {
            return $query; // KS & Waka Kurikulum see all in their institution (handled by BelongsToInstitution trait)
        }
        return $query->where('teacher_id', Auth::id()); // Teacher sees their own (both Jalur 1 & Jalur 2)
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(array_merge([
            'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_kelas', 'guru_mapel', 'wali_kelas'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public static function form(Form $form): Form
    {
        $isLeader = Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum']);

        if ($isLeader) {
            return $form
                ->schema([
                    Forms\Components\Section::make('Penjadwalan Supervisi oleh Kepala Sekolah / Waka')
                        ->description('Form bagi Kepala Sekolah untuk menjadwalkan supervisi pembelajaran guru di lembaga ini')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('teacher_id')
                                ->label('Nama Guru yang Disupervisi')
                                ->options(function () {
                                    $user = Auth::user();
                                    $currentInstId = $user?->institution_id;

                                    // Ambil guru yang lembaga induknya sama, atau guru lintas lembaga yang mengajar di lembaga ini
                                    $crossTeacherIds = \App\Models\TeacherSubjectClass::withoutGlobalScopes()
                                        ->where('institution_id', $currentInstId)
                                        ->pluck('teacher_id')
                                        ->merge(
                                            \App\Models\Schedule::withoutGlobalScopes()
                                                ->where('institution_id', $currentInstId)
                                                ->pluck('teacher_id')
                                        )->unique()->filter()->toArray();

                                    $query = \App\Models\User::withoutGlobalScopes()
                                        ->with('institution')
                                        ->where('is_active', true)
                                        ->where(function ($q) use ($currentInstId, $crossTeacherIds) {
                                            if ($currentInstId) {
                                                $q->where('institution_id', $currentInstId)
                                                  ->orWhereIn('id', $crossTeacherIds);
                                            }
                                        })
                                        ->whereHas('roles', fn ($q) => $q->whereIn('name', array_merge([
                                            'guru_kelas', 'guru_mapel', 'wali_kelas',
                                            'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
                                        ], \App\Models\User::QURAN_TEACHER_ROLES)));

                                    return $query->get()->sortBy(function ($t) use ($currentInstId) {
                                        $isSame = ($t->institution_id == $currentInstId);
                                        return ($isSame ? '0_' : '1_') . $t->name;
                                    })->mapWithKeys(function ($teacher) use ($currentInstId) {
                                        $isSame = ($teacher->institution_id == $currentInstId);
                                        $instName = $teacher->institution?->name ?? 'Yayasan';
                                        $label = $isSame 
                                            ? $teacher->name 
                                            : "{$teacher->name} [Guru Lintas Lembaga: {$instName}]";
                                        return [$teacher->id => $label];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\Select::make('unit_sekolah_program')
                                ->label('Unit Sekolah / Program')
                                ->options(TeacherSupervision::getUnitOptions())
                                ->default('Reguler')
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('mata_pelajaran')
                                ->label('Mata Pelajaran / Bidang Studi')
                                ->placeholder('Contoh: Matematika, Bahasa Arab, Al-Qur\'an, dll.')
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('kelas_semester')
                                ->label('Kelas / Semester')
                                ->placeholder('Contoh: Kelas 7A / Semester Genap')
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Penetapan Supervisi')
                                ->default(now())
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\DateTimePicker::make('jadwal')
                                ->label('Jadwal Pelaksanaan Observasi Kelas')
                                ->default(now()->addDays(1)->setHour(8)->setMinute(0))
                                ->required()
                                ->columnSpan(1),

                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan / Arahan Supervisi dari Kepala Sekolah')
                                ->placeholder('Contoh: Fokus observasi pada diferensiasi pembelajaran dan pengelolaan kelas...')
                                ->rows(3)
                                ->columnSpanFull(),

                            Forms\Components\Hidden::make('jalur_supervisi')
                                ->default('kepala_sekolah'),

                            Forms\Components\Hidden::make('status')
                                ->default('dijadwalkan'),

                            Forms\Components\Hidden::make('supervisor_id')
                                ->default(fn () => Auth::id()),

                            Forms\Components\Hidden::make('supervisor_name')
                                ->default(fn () => Auth::user()?->name ?? 'Kepala Sekolah'),

                            Forms\Components\Hidden::make('institution_id')
                                ->default(fn () => Auth::user()?->institution_id),

                            Forms\Components\Hidden::make('hasil')
                                ->default('-'),
                        ]),
                ]);
        }

        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('jalur_supervisi')
                    ->default('pengajuan_guru'),
                Forms\Components\Hidden::make('status')
                    ->default('menunggu'),
                Forms\Components\Hidden::make('hasil')
                    ->default('-'),
                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal Pengajuan')
                    ->default(now())
                    ->required(),
                Forms\Components\Textarea::make('catatan')
                    ->label('Keterangan / Tujuan Permintaan Supervisi')
                    ->placeholder('Contoh: Mohon supervisi pembelajaran di kelas untuk materi...')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSupervisor = Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum']);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => $isSupervisor),
                Tables\Columns\TextColumn::make('jalur_supervisi')
                    ->label('Jalur Supervisi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'kepala_sekolah' => '1. Kepala Sekolah',
                        'penjamin_mutu' => '2. Penjamin Mutu Yayasan',
                        default => '3. Permintaan Guru',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'kepala_sekolah' => 'success',
                        'penjamin_mutu' => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kelas_semester')
                    ->label('Kelas/Sem')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl Dibuat/Pengajuan')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'menunggu' => 'Menunggu Jadwal',
                        'dijadwalkan' => 'Dijadwalkan',
                        'dinilai' => 'Selesai Dinilai',
                        'ditolak' => 'Ditolak',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'menunggu' => 'gray',
                        'dijadwalkan' => 'primary',
                        'dinilai' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('jadwal')
                    ->label('Jadwal Pelaksanaan')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Belum Dijadwalkan'),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('Supervisor / Penilai')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->supervisor_name ?: ($record->supervisor?->name ?? ($record->jalur_supervisi === 'kepala_sekolah' ? 'Kepala Sekolah' : '-')))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('catatan_yayasan')
                    ->label('Catatan Supervisi')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->catatan_yayasan ?: ($record->catatan ?: '-'))
                    ->limit(35)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->badge()
                    ->color('success')
                    ->placeholder('Belum Dinilai'),
                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->predikat ?: ($record->score ? TeacherSupervision::determinePredicate((float)$record->score) : '-'))
                    ->badge()
                    ->color(fn (TeacherSupervision $record) => TeacherSupervision::getPredicateBadgeColor($record->score)),
                Tables\Columns\TextColumn::make('hasil')
                    ->label('Hasil Evaluasi')
                    ->limit(35)
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jalur_supervisi')
                    ->label('Jalur Supervisi')
                    ->options([
                        'kepala_sekolah' => '1. Kepala Sekolah',
                        'penjamin_mutu' => '2. Penjamin Mutu Yayasan',
                        'pengajuan_guru' => '3. Permintaan Guru',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu Jadwal',
                        'dijadwalkan' => 'Dijadwalkan',
                        'dinilai' => 'Selesai Dinilai',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('nilai_supervisi')
                    ->label('Jadwal & Nilai')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn () => $isSupervisor)
                    ->modalHeading(fn (TeacherSupervision $record) => "Penilaian Supervisi Guru: " . ($record->teacher->name ?? '-'))
                    ->modalWidth('2xl')
                    ->fillForm(fn (TeacherSupervision $record): array => [
                        'status' => $record->status ?? 'dijadwalkan',
                        'jadwal' => $record->jadwal,
                        'score' => $record->score,
                        'praktik_baik' => $record->praktik_baik,
                        'area_fokus_perbaikan' => $record->area_fokus_perbaikan,
                        'rencana_coaching' => $record->rencana_coaching,
                        'hasil' => $record->hasil === '-' ? '' : $record->hasil,
                    ])
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Status Supervisi')
                                ->options([
                                    'dijadwalkan' => 'Dijadwalkan',
                                    'dinilai' => 'Sudah Dinilai (Selesai)',
                                    'menunggu' => 'Menunggu Jadwal',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->required(),
                            Forms\Components\DateTimePicker::make('jadwal')
                                ->label('Jadwal Pelaksanaan Observasi'),
                        ]),
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Supervisi (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Skor 90-100: Sangat Baik, 80-89: Baik, 70-79: Cukup, <70: Perlu Bimbingan'),
                        Forms\Components\Textarea::make('praktik_baik')
                            ->label('1. Praktik Baik (Best Practice)')
                            ->placeholder('Kelebihan / hal positif yang ditemukan saat proses pembelajaran...')
                            ->rows(2),
                        Forms\Components\Textarea::make('area_fokus_perbaikan')
                            ->label('2. Area Fokus Perbaikan')
                            ->placeholder('Aspek yang perlu diperbaiki / ditingkatkan oleh guru...')
                            ->rows(2),
                        Forms\Components\Textarea::make('rencana_coaching')
                            ->label('3. Tindak Lanjut / Rencana Coaching')
                            ->placeholder('Rencana pendampingan klinis / pembinaan lanjutan...')
                            ->rows(2),
                        Forms\Components\Textarea::make('hasil')
                            ->label('Catatan Kesimpulan / Masukan Kepala Sekolah')
                            ->placeholder('Kesimpulan evaluasi umum untuk guru...')
                            ->rows(2),
                    ])
                    ->action(function (TeacherSupervision $record, array $data): void {
                        $score = ($data['score'] !== null && $data['score'] !== '') ? (float) $data['score'] : null;
                        $status = $data['status'];
                        if ($score !== null && $status === 'dijadwalkan') {
                            $status = 'dinilai';
                        }
                        $predikat = $score !== null ? TeacherSupervision::determinePredicate($score) : $record->predikat;

                        $record->update([
                            'supervisor_id' => $record->supervisor_id ?: Auth::id(),
                            'supervisor_name' => $record->supervisor_name ?: (Auth::user()?->name ?? 'Kepala Sekolah'),
                            'status' => $status,
                            'jadwal' => $data['jadwal'] ?? $record->jadwal,
                            'score' => $score,
                            'nilai_akhir' => $score,
                            'predikat' => $predikat,
                            'praktik_baik' => $data['praktik_baik'] ?? $record->praktik_baik,
                            'area_fokus_perbaikan' => $data['area_fokus_perbaikan'] ?? $record->area_fokus_perbaikan,
                            'rencana_coaching' => $data['rencana_coaching'] ?? $record->rencana_coaching,
                            'hasil' => !empty($data['hasil']) ? $data['hasil'] : ($record->hasil ?: '-'),
                        ]);

                        Notification::make()
                            ->title('Supervisi Berhasil Dinilai & Disimpan')
                            ->body('Hasil supervisi telah tercatat dan dapat dicetak sebagai Raport Supervisi.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('report_card')
                    ->label('Report Card')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (TeacherSupervision $record) => route('supervisi.report-card.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherSupervision $record) => $record->status === 'dinilai' || $record->total_skor > 0 || $record->score !== null),
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(fn (TeacherSupervision $record) => route('supervisi.report-card.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (TeacherSupervision $record) => $record->status === 'dinilai' || $record->total_skor > 0 || $record->score !== null),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => $isSupervisor),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $isSupervisor),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\PengajuanSupervisiResource\Pages\ListPengajuanSupervisis::route('/'),
        ];
    }
}