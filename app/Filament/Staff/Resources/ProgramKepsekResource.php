<?php

namespace App\Filament\Staff\Resources;

use App\Models\ProgramKepsek;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramKepsekResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ProgramKepsek::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Proker Sekolah';
    protected static ?string $modelLabel = 'Proker Sekolah';
    protected static ?string $pluralModelLabel = 'Proker Sekolah';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Hidden::make('institution_id')
                    ->default(fn () => auth()->user()?->institution_id),

                Forms\Components\Hidden::make('source_type'),
                Forms\Components\Hidden::make('source_id'),
                
                Forms\Components\Section::make('Informasi Program Kerja')
                    ->schema([
                        Forms\Components\Select::make('source_program_ref')
                            ->label('Tarik dari Program Kerja Bidang / Yayasan (Opsional)')
                            ->placeholder('-- Pilih untuk mengisi otomatis dari database bidang atau yayasan --')
                            ->options(function () {
                                $user = auth()->user();
                                $instId = $user?->institution_id;
                                $opts = [];

                                // 1. Program Yayasan untuk lembaga
                                $yPrograms = \App\Models\YayasanProgram::query();
                                if ($instId && $instId != 1) {
                                    $yPrograms->whereHas('institutions', fn ($q) => $q->where('institutions.id', $instId));
                                }
                                foreach ($yPrograms->get() as $p) {
                                    $opts['Yayasan:' . $p->id] = "[🏛️ Yayasan] {$p->name}";
                                }

                                // 2. Kurikulum
                                $kurikulum = \App\Models\ProgramKurikulum::query();
                                if ($instId && $instId != 1) $kurikulum->where('institution_id', $instId);
                                foreach ($kurikulum->get() as $p) {
                                    $opts['Kurikulum:' . $p->id] = "[📚 Kurikulum] {$p->nama_program}";
                                }

                                // 3. Kesiswaan
                                $kesiswaan = \App\Models\ProgramKesiswaan::query();
                                if ($instId && $instId != 1) $kesiswaan->where('institution_id', $instId);
                                foreach ($kesiswaan->get() as $p) {
                                    $opts['Kesiswaan:' . $p->id] = "[🎯 Kesiswaan] {$p->nama_program}";
                                }

                                // 4. Sarpras
                                $sarpras = \App\Models\ProgramSarpras::query();
                                if ($instId && $instId != 1) $sarpras->where('institution_id', $instId);
                                foreach ($sarpras->get() as $p) {
                                    $opts['Sarpras:' . $p->id] = "[🏢 Sarpras] {$p->nama_program}";
                                }

                                // 5. Humas
                                $humas = \App\Models\ProgramHumas::query();
                                if ($instId && $instId != 1) {
                                    $humas->whereHas('user', fn ($u) => $u->where('institution_id', $instId));
                                }
                                foreach ($humas->get() as $p) {
                                    $opts['Humas:' . $p->id] = "[🤝 Humas] {$p->nama_program}";
                                }

                                // 6. BK
                                $bk = \App\Models\BkWorkProgram::query();
                                if ($instId && $instId != 1) $bk->where('institution_id', $instId);
                                foreach ($bk->get() as $p) {
                                    $label = $p->judul_program ?? $p->nama_program ?? 'Program BK #' . $p->id;
                                    $opts['BK:' . $p->id] = "[🧭 BK] {$label}";
                                }

                                // 7. Wali Kelas
                                $waliKelas = \App\Models\ClassProgram::with('classroom');
                                if ($instId && $instId != 1) {
                                    $waliKelas->whereHas('classroom', fn ($c) => $c->where('institution_id', $instId));
                                }
                                foreach ($waliKelas->get() as $p) {
                                    $opts['WaliKelas:' . $p->id] = "[👥 Wali Kelas] {$p->nama_program}";
                                }

                                // 8. OSIS
                                $osis = \App\Models\OsisProgram::query();
                                if ($instId && $instId != 1) $osis->where('institution_id', $instId);
                                foreach ($osis->get() as $p) {
                                    $label = $p->nama_program ?? $p->name ?? 'Program OSIS #' . $p->id;
                                    $opts['OSIS:' . $p->id] = "[🏅 OSIS] {$label}";
                                }

                                return $opts;
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!empty($state) && str_contains($state, ':')) {
                                    [$type, $id] = explode(':', $state, 2);
                                    switch ($type) {
                                        case 'Yayasan':
                                            $p = \App\Models\YayasanProgram::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->name);
                                                $set('kategori', 'Program Yayasan');
                                                $set('tipe_pelaksanaan', 'Tahunan');
                                                $set('sifat', 'Wajib');
                                                $set('tujuan', $p->description ?? 'Instruksi program kerja Yayasan yang ditujukan ke lembaga.');
                                                $set('sasaran', 'Seluruh Lembaga Sekolah');
                                                $set('status_pelaksanaan', 'Sedang Berjalan');
                                                $set('source_type', \App\Models\YayasanProgram::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'Kurikulum':
                                            $p = \App\Models\ProgramKurikulum::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->nama_program);
                                                $set('kategori', 'Kurikulum');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Semesteran');
                                                $set('sifat', $p->sifat ?? 'Terjadwal');
                                                $set('tujuan', $p->tujuan ?? 'Program kerja bidang akademik dan kurikulum pembelajaran.');
                                                $set('sasaran', $p->target ?? 'Siswa & Guru Mapel');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? null);
                                                $set('estimasi_anggaran', $p->anggaran ?? null);
                                                $set('source_type', \App\Models\ProgramKurikulum::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'Kesiswaan':
                                            $p = \App\Models\ProgramKesiswaan::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->nama_program);
                                                $set('kategori', 'Kesiswaan');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Semesteran');
                                                $set('sifat', $p->sifat ?? 'Terjadwal');
                                                $set('tujuan', $p->tujuan ?? 'Program pembinaan karakter dan kesiswaan.');
                                                $set('sasaran', $p->sasaran ?? 'Seluruh Siswa');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? null);
                                                $set('estimasi_anggaran', $p->anggaran ?? null);
                                                $set('source_type', \App\Models\ProgramKesiswaan::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'Sarpras':
                                            $p = \App\Models\ProgramSarpras::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->nama_program);
                                                $set('kategori', 'Sarpras');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Tahunan');
                                                $set('sifat', $p->sifat ?? 'Terjadwal');
                                                $set('tujuan', $p->tujuan ?? 'Program pengadaan dan pemeliharaan fasilitas sarana prasarana.');
                                                $set('sasaran', $p->sasaran ?? 'Fasilitas & Sarana Sekolah');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? null);
                                                $set('estimasi_anggaran', $p->anggaran ?? null);
                                                $set('source_type', \App\Models\ProgramSarpras::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'Humas':
                                            $p = \App\Models\ProgramHumas::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->nama_program);
                                                $set('kategori', 'Humas');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Semesteran');
                                                $set('sifat', $p->sifat ?? 'Terjadwal');
                                                $set('tujuan', $p->tujuan ?? 'Program hubungan masyarakat dan kemitraan lembaga.');
                                                $set('sasaran', $p->sasaran ?? 'Wali Murid & Masyarakat');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? null);
                                                $set('estimasi_anggaran', $p->anggaran ?? null);
                                                $set('source_type', \App\Models\ProgramHumas::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'BK':
                                            $p = \App\Models\BkWorkProgram::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->judul_program ?? $p->nama_program);
                                                $set('kategori', 'Bimbingan Konseling');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Bulanan');
                                                $set('sifat', $p->sifat ?? 'Kontinu');
                                                $set('tujuan', $p->tujuan ?? 'Layanan bimbingan dan konseling siswa.');
                                                $set('sasaran', $p->sasaran ?? 'Siswa Perlu Pembinaan');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? $p->tanggal ?? null);
                                                $set('estimasi_anggaran', $p->estimasi_anggaran ?? null);
                                                $set('source_type', \App\Models\BkWorkProgram::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'WaliKelas':
                                            $p = \App\Models\ClassProgram::with('classroom')->find($id);
                                            if ($p) {
                                                $name = $p->nama_program . ($p->classroom ? " (Kelas {$p->classroom->name})" : '');
                                                $set('nama_program', $name);
                                                $set('kategori', 'Wali Kelas');
                                                $set('tipe_pelaksanaan', $p->tipe_pelaksanaan ?? 'Bulanan');
                                                $set('sifat', 'Terjadwal');
                                                $set('tujuan', $p->tujuan ?? 'Program pengelolaan dan kekompakan kelas.');
                                                $set('sasaran', $p->classroom ? "Siswa {$p->classroom->name}" : 'Siswa Kelas');
                                                $set('waktu_pelaksanaan', $p->waktu_pelaksanaan ?? null);
                                                $set('estimasi_anggaran', $p->estimasi_anggaran ?? null);
                                                $set('source_type', \App\Models\ClassProgram::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                        case 'OSIS':
                                            $p = \App\Models\OsisProgram::find($id);
                                            if ($p) {
                                                $set('nama_program', $p->nama_program ?? $p->name);
                                                $set('kategori', 'OSIS');
                                                $set('tipe_pelaksanaan', 'Semesteran');
                                                $set('sifat', 'Terjadwal');
                                                $set('tujuan', $p->description ?? 'Program kerja pengembangan potensi siswa melalui OSIS.');
                                                $set('sasaran', 'Pengurus OSIS & Seluruh Siswa');
                                                $set('waktu_pelaksanaan', $p->tanggal ? \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') : null);
                                                $set('source_type', \App\Models\OsisProgram::class);
                                                $set('source_id', $p->id);
                                            }
                                            break;
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program / Kegiatan')
                            ->required()
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('kategori')
                            ->label('Kategori / Asal Bidang')
                            ->options([
                                'Program Yayasan' => '🏛️ Program Yayasan',
                                'Kurikulum' => '📚 Kurikulum',
                                'Kesiswaan' => '🎯 Kesiswaan',
                                'Sarpras' => '🏢 Sarana & Prasarana',
                                'Humas' => '🤝 Humas & Kemitraan',
                                'Bimbingan Konseling' => '🧭 Bimbingan Konseling',
                                'Wali Kelas' => '👥 Program Kelas / Wali Kelas',
                                'OSIS' => '🎖️ OSIS',
                                'Manajemen' => '👔 Manajemen Sekolah',
                                'Evaluasi Kinerja' => '📊 Evaluasi Kinerja',
                                'Pengembangan Sekolah' => '🚀 Pengembangan Sekolah',
                                'Lainnya' => '🌐 Lainnya',
                            ])
                            ->default('Manajemen')
                            ->required(),
                            
                        Forms\Components\Select::make('tipe_pelaksanaan')
                            ->label('Tipe / Frekuensi')
                            ->options([
                                'Harian' => 'Harian',
                                'Mingguan' => 'Mingguan',
                                'Bulanan' => 'Bulanan',
                                'Semesteran' => 'Semesteran',
                                'Tahunan' => 'Tahunan',
                            ])
                            ->default('Semesteran')
                            ->required(),
                            
                        Forms\Components\Select::make('sifat')
                            ->label('Sifat Kegiatan')
                            ->options([
                                'Wajib' => 'Wajib',
                                'Kontinu' => 'Kontinu',
                                'Terjadwal' => 'Terjadwal',
                            ])
                            ->default('Terjadwal')
                            ->required(),
                            
                        Forms\Components\Select::make('status_pelaksanaan')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'Rencana' => 'Rencana',
                                'Sedang Berjalan' => 'Sedang Berjalan',
                                'Selesai' => 'Selesai',
                                'Batal' => 'Batal',
                            ])
                            ->default('Rencana')
                            ->required(),

                        Forms\Components\TextInput::make('sasaran')
                            ->label('Sasaran Peserta')
                            ->placeholder('Contoh: Seluruh Guru / Siswa Kelas VII'),
                            
                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Waktu Pelaksanaan')
                            ->placeholder('Contoh: Setiap Semester / Juli 2026'),
                            
                        Forms\Components\TextInput::make('dokumen_terkait')
                            ->label('Dokumen Terkait')
                            ->placeholder('Contoh: SK, Proposal, LPJ'),
                            
                        Forms\Components\TextInput::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Opsional (Ketik angka saja)'),
                            
                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail & Pelaksanaan')
                    ->schema([
                        Forms\Components\Textarea::make('tujuan')
                            ->label('Tujuan Program')
                            ->rows(3),
                        Forms\Components\RichEditor::make('teknis_pelaksanaan')
                            ->label('Teknis Pelaksanaan')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                    ])->columns(1),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('summary_table')
                    ->view('filament.staff.components.program-kerja-summary-table')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ProgramKepsek $record) => !empty($record->sasaran) ? "Sasaran: {$record->sasaran}" : null),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori / Asal Bidang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Program Yayasan' => 'purple',
                        'Kurikulum' => 'info',
                        'Kesiswaan' => 'success',
                        'Sarpras' => 'warning',
                        'Humas' => 'danger',
                        'Bimbingan Konseling' => 'gray',
                        'Wali Kelas' => 'amber',
                        'OSIS' => 'success',
                        'Manajemen' => 'primary',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('tipe_pelaksanaan')
                    ->label('Tipe')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sifat')
                    ->label('Sifat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Wajib' => 'danger',
                        'Kontinu' => 'info',
                        'Terjadwal' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waktu_pelaksanaan')
                    ->label('Waktu')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_pelaksanaan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rencana' => 'gray',
                        'Sedang Berjalan' => 'warning',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->label('Kategori / Asal Bidang')
                    ->options([
                        'Program Yayasan' => '🏛️ Program Yayasan',
                        'Kurikulum' => '📚 Kurikulum',
                        'Kesiswaan' => '🎯 Kesiswaan',
                        'Sarpras' => '🏢 Sarpras',
                        'Humas' => '🤝 Humas',
                        'Bimbingan Konseling' => '🧭 Bimbingan Konseling',
                        'Wali Kelas' => '👥 Wali Kelas',
                        'OSIS' => '🎖️ OSIS',
                        'Manajemen' => '👔 Manajemen',
                        'Evaluasi Kinerja' => '📊 Evaluasi Kinerja',
                        'Pengembangan Sekolah' => '🚀 Pengembangan Sekolah',
                    ]),
                Tables\Filters\SelectFilter::make('status_pelaksanaan')
                    ->label('Status Pelaksanaan')
                    ->options([
                        'Rencana' => 'Rencana',
                        'Sedang Berjalan' => 'Sedang Berjalan',
                        'Selesai' => 'Selesai',
                        'Batal' => 'Batal',
                    ]),
                Tables\Filters\SelectFilter::make('tipe_pelaksanaan')
                    ->options([
                        'Harian' => 'Harian',
                        'Mingguan' => 'Mingguan',
                        'Bulanan' => 'Bulanan',
                        'Semesteran' => 'Semesteran',
                        'Tahunan' => 'Tahunan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja Kepala Sekolah'))
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\ProgramKepsekResource\Pages\ManageProgramKepseks::route('/'),
        ];
    }
}
