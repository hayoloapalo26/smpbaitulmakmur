<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ClassProgramResource\Pages;
use App\Models\ClassProgram;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClassProgramResource extends Resource
{
    protected static ?string $model = ClassProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Fitur Wali Kelas';
    protected static ?string $navigationLabel = 'Program Kerja Kelas';
    protected static ?string $modelLabel = 'Program Kerja Kelas';
    protected static ?string $pluralModelLabel = 'Program Kerja Kelas';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['wali_kelas', 'guru_kelas', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['wali_kelas', 'guru_kelas', 'kepala_sekolah', 'superadmin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        if ($user->hasRole('wali_kelas')) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $classroomIds) {
                $q->where('wali_kelas_id', $user->id)
                  ->orWhereIn('classroom_id', $classroomIds);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isPrivileged = $user->hasAnyRole(['kepala_sekolah', 'superadmin']);

        return $form
            ->schema([
                Forms\Components\Section::make('📌 Data Kelas & Periode')
                    ->schema([
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->options(function () use ($user, $isPrivileged) {
                                if ($isPrivileged) {
                                    return Classroom::orderBy('name')->pluck('name', 'id');
                                }
                                $myClasses = Classroom::where('wali_kelas_id', $user->id)->pluck('name', 'id');
                                return $myClasses->isNotEmpty() ? $myClasses : Classroom::orderBy('name')->pluck('name', 'id');
                            })
                            ->default(fn () => Classroom::where('wali_kelas_id', $user->id)->value('id'))
                            ->required(),

                        Forms\Components\Hidden::make('wali_kelas_id')
                            ->default($user->id),

                        Forms\Components\TextInput::make('tahun_ajaran')
                            ->label('Tahun Ajaran')
                            ->default('2026/2027')
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('Semester')
                            ->options([
                                'Ganjil' => 'Semester Ganjil',
                                'Genap'  => 'Semester Genap',
                            ])
                            ->default('Ganjil')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📋 Rencana Program Kerja')
                    ->schema([
                        Forms\Components\TextInput::make('nama_program')
                            ->label('Nama Program Kerja')
                            ->placeholder('Contoh: Pembentukan Pengurus & Struktur Kelas / Pojok Baca & Literasi Harian...')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Program')
                            ->options([
                                'administrasi'       => '📋 Administrasi & Tata Kelola Kelas',
                                'akademik'           => '📚 Akademik & Pendampingan Belajar',
                                'karakter_disiplin'  => '⭐ Karakter & Kedisiplinan Siswa',
                                'paguyuban_ortu'     => '👨‍👩‍👧 Paguyuban & Pertemuan Orang Tua',
                                'lingkungan_sarpras' => '🌱 Kebersihan, Pojok Baca & Penataan Kelas',
                                'kegiatan_kreatif'   => '🎨 Kegiatan Kreatif & Kebersamaan Kelas',
                                'lainnya'            => '📝 Lainnya',
                            ])
                            ->default('administrasi')
                            ->required(),

                        Forms\Components\Select::make('tipe_pelaksanaan')
                            ->label('Frekuensi / Tipe Pelaksanaan')
                            ->options([
                                'harian'      => '📅 Harian',
                                'mingguan'    => '📆 Mingguan',
                                'bulanan'     => '🗓️ Bulanan',
                                'semesteran'  => '📊 Semesteran',
                                'tahunan'     => '🎯 Tahunan',
                                'insidental'  => '⚡ Insidental / Situasional',
                            ])
                            ->default('bulanan')
                            ->required(),

                        Forms\Components\TextInput::make('sasaran')
                            ->label('Sasaran Peserta')
                            ->default('Seluruh Siswa Kelas')
                            ->required(),

                        Forms\Components\TextInput::make('waktu_pelaksanaan')
                            ->label('Rencana Waktu / Jadwal')
                            ->placeholder('Contoh: Pekan ke-2 Setiap Bulan / Jumat Pagi')
                            ->required(),

                        Forms\Components\TextInput::make('estimasi_anggaran')
                            ->label('Estimasi Anggaran (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),

                        Forms\Components\Textarea::make('tujuan')
                            ->label('Tujuan Program')
                            ->rows(3)
                            ->placeholder('Tujuan yang ingin dicapai melalui program ini...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📈 Status & Pelaksanaan Program')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Pelaksanaan')
                            ->options([
                                'rencana'    => '📝 Rencana',
                                'berjalan'   => '🔄 Sedang Berjalan',
                                'terlaksana' => '✅ Terlaksana / Selesai',
                                'tertunda'   => '⏸️ Tertunda',
                                'batal'      => '❌ Dibatalkan',
                            ])
                            ->default('rencana')
                            ->required(),

                        Forms\Components\FileUpload::make('dokumen_pendukung')
                            ->label('Dokumen / Foto Kegiatan (Opsional)')
                            ->directory('class_programs/docs'),

                        Forms\Components\Textarea::make('evaluasi_catatan')
                            ->label('Catatan Ketercapaian / Evaluasi Hasil')
                            ->rows(3)
                            ->placeholder('Evaluasi keberhasilan atau catatan tindak lanjut program...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->fontFamily('font-semibold')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'administrasi'       => '📋 Administrasi',
                        'akademik'           => '📚 Akademik',
                        'karakter_disiplin'  => '⭐ Karakter',
                        'paguyuban_ortu'     => '👨‍👩‍👧 Paguyuban',
                        'lingkungan_sarpras' => '🌱 Lingkungan',
                        'kegiatan_kreatif'   => '🎨 Kreatif',
                        default              => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('tipe_pelaksanaan')
                    ->label('Tipe')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('waktu_pelaksanaan')
                    ->label('Jadwal Pelaksanaan')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('estimasi_anggaran')
                    ->label('Anggaran')
                    ->money('IDR', locale: 'id')
                    ->placeholder('Rp 0'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'terlaksana' => 'success',
                        'berjalan'   => 'warning',
                        'rencana'    => 'gray',
                        'tertunda'   => 'orange',
                        'batal'      => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'terlaksana' => '✅ Terlaksana',
                        'berjalan'   => '🔄 Berjalan',
                        'rencana'    => '📝 Rencana',
                        'tertunda'   => '⏸️ Tertunda',
                        'batal'      => '❌ Batal',
                        default      => ucfirst($state),
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'rencana'    => 'Rencana',
                        'berjalan'   => 'Sedang Berjalan',
                        'terlaksana' => 'Terlaksana',
                        'tertunda'   => 'Tertunda',
                    ]),
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'administrasi'       => 'Administrasi',
                        'akademik'           => 'Akademik',
                        'karakter_disiplin'  => 'Karakter & Disiplin',
                        'paguyuban_ortu'     => 'Paguyuban Ortu',
                        'lingkungan_sarpras' => 'Lingkungan & Sarpras',
                        'kegiatan_kreatif'   => 'Kegiatan Kreatif',
                    ]),
                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap'  => 'Genap',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('tandai_berjalan')
                    ->label('Jalankan')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (ClassProgram $record) => $record->status === 'rencana')
                    ->action(fn (ClassProgram $record) => $record->update(['status' => 'berjalan'])),
                Tables\Actions\Action::make('tandai_selesai')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ClassProgram $record) => in_array($record->status, ['rencana', 'berjalan']))
                    ->form([
                        Forms\Components\Textarea::make('evaluasi_catatan')
                            ->label('Catatan Hasil / Evaluasi Ketercapaian')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function (ClassProgram $record, array $data) {
                        $record->update([
                            'status'           => 'terlaksana',
                            'evaluasi_catatan' => $data['evaluasi_catatan'],
                        ]);
                    }),
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
            'index' => Pages\ManageClassPrograms::route('/'),
        ];
    }
}
