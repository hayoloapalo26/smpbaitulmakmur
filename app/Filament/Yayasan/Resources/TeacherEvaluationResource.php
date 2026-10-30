<?php

namespace App\Filament\Yayasan\Resources;

use App\Models\Institution;
use App\Models\TeacherCompetencyTest;
use App\Models\TeacherObservation;
use App\Models\TeacherSupervision;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherEvaluationResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Data Guru Perlembaga';
    protected static ?string $pluralModelLabel = 'Data Guru Perlembaga';
    protected static ?string $slug = 'penilaian-guru';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', array_merge([
                    'guru_kelas', 'guru_mapel', 'wali_kelas',
                    'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
                    'sarpras', 'humas',
                ], \App\Models\User::QURAN_TEACHER_ROLES));
            });
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
        ]) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('kehadiran_score')
                    ->label('Jurnal Mengajar (40%)')
                    ->tooltip('Kewajiban menginput jurnal mengajar setelah pembelajaran')
                    ->getStateUsing(function ($record) {
                        $kpiService = new \App\Services\TeacherKpiService();
                        return round($kpiService->calculateAttendancePercentage($record->id, 'bulanan'));
                    })
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) => $state >= 80 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('supervisi_score')
                    ->label('Supervisi Guru (20%)')
                    ->getStateUsing(function ($record) {
                        return TeacherSupervision::withoutGlobalScopes()
                            ->where('teacher_id', $record->id)
                            ->whereNotNull('score')
                            ->latest()
                            ->value('score') ?? 0;
                    })
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1) . '%'),

                Tables\Columns\TextColumn::make('ukg_score')
                    ->label('UKG / PKG (20%)')
                    ->getStateUsing(function ($record) {
                        return TeacherCompetencyTest::withoutGlobalScopes()
                            ->where('teacher_id', $record->id)
                            ->whereNotNull('score')
                            ->latest()
                            ->value('score') ?? 0;
                    })
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1) . '%'),

                Tables\Columns\TextColumn::make('observasi_score')
                    ->label('Observasi (20%)')
                    ->tooltip('Observasi pembelajaran oleh Tim Pengembang & Penjamin Mutu')
                    ->getStateUsing(function ($record) {
                        return TeacherObservation::withoutGlobalScopes()
                            ->where('teacher_id', $record->id)
                            ->whereNotNull('score')
                            ->latest('tanggal_observasi')
                            ->value('score') ?? 0;
                    })
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1) . '%')
                    ->color(fn ($state) => (float)$state >= 80 ? 'success' : ((float)$state >= 70 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('NILAI AKHIR')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $kpiService = new \App\Services\TeacherKpiService();
                        $evaluation = $kpiService->calculateTeacherPerformanceEvaluation($record->id, 'bulanan');
                        return $evaluation['final_score'];
                    })
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2) . '%')
                    ->color(function ($state) {
                        if ($state >= 85) {
                            return 'success';
                        }
                        if ($state >= 70) {
                            return 'warning';
                        }
                        return 'danger';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Pilih Lembaga')
                    ->options(fn () => Institution::where('id', '!=', 1)->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('jadwalkan_supervisi')
                    ->label('Jadwalkan Supervisi')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->modalHeading(fn (User $record) => 'Jadwalkan Supervisi: ' . $record->name)
                    ->modalDescription('Jalur 1: Penjamin Mutu menjadwalkan supervisi tanpa permintaan. Informasi jadwal akan langsung dikirim ke Kepala Sekolah dan Guru.')
                    ->form([
                        Forms\Components\DateTimePicker::make('jadwal')
                            ->label('Jadwal Pelaksanaan Supervisi')
                            ->default(now()->addDays(1)->setHour(8)->setMinute(0))
                            ->required(),
                        Forms\Components\Textarea::make('catatan_yayasan')
                            ->label('Agenda / Catatan Supervisi (Diinformasikan ke KS & Guru)')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        TeacherSupervision::create([
                            'institution_id' => $record->institution_id,
                            'teacher_id' => $record->id,
                            'supervisor_id' => auth()->id(),
                            'jalur_supervisi' => 'penjamin_mutu',
                            'tanggal' => now()->toDateString(),
                            'jadwal' => $data['jadwal'],
                            'status' => 'dijadwalkan',
                            'catatan' => 'Dijadwalkan oleh Tim Penjamin Mutu Yayasan (Tanpa Permintaan)',
                            'catatan_yayasan' => $data['catatan_yayasan'],
                            'hasil' => '-',
                        ]);

                        Notification::make()
                            ->title('Supervisi Berhasil Dijadwalkan')
                            ->body("Jadwal supervisi untuk {$record->name} telah disimpan dan diinformasikan ke Kepala Sekolah & Guru.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan', 'ketua_yayasan'])),

                Tables\Actions\Action::make('input_supervisi')
                    ->label('Nilai Supervisi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')->required()->default(now()),
                        Forms\Components\TextInput::make('score')->label('Nilai (0-100)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\Textarea::make('hasil')->label('Hasil / Keterangan')->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        TeacherSupervision::create([
                            'institution_id' => $record->institution_id,
                            'teacher_id' => $record->id,
                            'supervisor_id' => auth()->id(),
                            'jalur_supervisi' => 'penjamin_mutu',
                            'tanggal' => $data['tanggal'],
                            'jadwal' => now(),
                            'status' => 'dinilai',
                            'score' => $data['score'],
                            'hasil' => $data['hasil'],
                            'catatan' => 'Dinilai oleh Penjamin Mutu Yayasan',
                        ]);

                        Notification::make()
                            ->title('Nilai Supervisi Disimpan')
                            ->body("Nilai supervisi untuk {$record->name} telah disimpan dan diinformasikan ke Kepala Sekolah & Guru.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan', 'ketua_yayasan'])),

                Tables\Actions\Action::make('input_observasi')
                    ->label('Nilai Observasi')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (User $record) => 'Observasi Pembelajaran Guru: ' . $record->name)
                    ->modalDescription('Input hasil observasi pembelajaran oleh Tim Pengembang & Penjamin Mutu (Bobot 20% pada nilai akhir).')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_observasi')
                            ->label('Tanggal Pelaksanaan Observasi')
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Observasi (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('materi_pembelajaran')
                            ->label('Materi / Pokok Bahasan')
                            ->placeholder('Contoh: Pembelajaran Berdiferensiasi & Deep Learning')
                            ->nullable(),
                        Forms\Components\Textarea::make('catatan_observasi')
                            ->label('Catatan Hasil Observasi')
                            ->placeholder('Catatan temuan observasi di kelas...')
                            ->nullable(),
                        Forms\Components\Textarea::make('rekomendasi')
                            ->label('Rekomendasi Tindak Lanjut')
                            ->nullable(),
                    ])
                    ->action(function (User $record, array $data) {
                        TeacherObservation::create([
                            'institution_id' => $record->institution_id,
                            'teacher_id' => $record->id,
                            'observer_id' => auth()->id(),
                            'tanggal_observasi' => $data['tanggal_observasi'],
                            'score' => $data['score'],
                            'predikat' => TeacherObservation::determinePredicate((float) $data['score']),
                            'materi_pembelajaran' => $data['materi_pembelajaran'] ?? null,
                            'catatan_observasi' => $data['catatan_observasi'] ?? null,
                            'rekomendasi' => $data['rekomendasi'] ?? null,
                            'status' => 'selesai',
                        ]);

                        Notification::make()
                            ->title('Hasil Observasi Disimpan')
                            ->body("Nilai observasi untuk {$record->name} telah berhasil disimpan dan masuk ke evaluasi kinerja guru (bobot 20%).")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan', 'ketua_yayasan'])),

                Tables\Actions\Action::make('instrumen_ukg_70')
                    ->label('Instrumen PKG (70 Indikator)')
                    ->icon('heroicon-o-document-check')
                    ->color('warning')
                    ->url(fn (User $record) => \App\Filament\Yayasan\Resources\PermintaanUkgResource::getUrl('create') . '?teacher_id=' . $record->id)
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan', 'ketua_yayasan'])),

                Tables\Actions\Action::make('input_ukg')
                    ->label('Quick Input PKG')
                    ->icon('heroicon-o-academic-cap')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal')->required()->default(now()),
                        Forms\Components\TextInput::make('score')->label('Nilai PKG (0-100)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\Textarea::make('catatan')->label('Catatan PKG')->nullable(),
                    ])
                    ->action(function (User $record, array $data) {
                        TeacherCompetencyTest::create([
                            'institution_id' => $record->institution_id,
                            'teacher_id' => $record->id,
                            'assessor_id' => auth()->id(),
                            'tanggal' => $data['tanggal'],
                            'score' => $data['score'],
                            'catatan' => $data['catatan'],
                        ]);
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan', 'ketua_yayasan'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Yayasan\Resources\TeacherEvaluationResource\Pages\ListTeacherEvaluations::route('/'),
        ];
    }
}
