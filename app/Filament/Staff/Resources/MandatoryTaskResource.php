<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Pages\DailyTodoList;
use App\Filament\Staff\Resources\MandatoryTaskResource\Pages;
use App\Models\Institution;
use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MandatoryTaskResource extends Resource
{
    protected static ?string $model = MandatoryTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'To-Do List Harian (Wajib)';
    protected static ?string $modelLabel = 'To-Do List Harian (Wajib)';
    protected static ?string $pluralModelLabel = 'To-Do List Harian (Wajib)';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;

    public static function getRoleOptions(): array
    {
        return [
            'waka_kurikulum' => 'Waka Kurikulum',
            'waka_kesiswaan' => 'Waka Kesiswaan',
            'wali_kelas'     => 'Wali Kelas / Guru Kelas',
            'guru_mapel'     => 'Guru Mata Pelajaran',
            'guru_alquran'   => 'Guru Al-Qur\'an (Tahfidz & Tahsin)',
            'koordinator_madin' => 'Koordinator Madin (Madrasah Diniyah)',
            'guru_piket'     => 'Guru Piket',
            'bk'             => 'Guru BK (Bimbingan Konseling)',
            'tata_usaha'     => 'Tata Usaha (TU)',
            'sarpras'        => 'Sarpras',
            'humas'          => 'Humas',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas Wajib Harian')
                    ->description('Tambahkan tugas harian wajib yang akan otomatis muncul di menu To-Do List Harian (Wajib) sesuai role di lembaga Anda.')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Unit Sekolah')
                            ->options(Institution::where('id', '!=', 1)->pluck('name', 'id'))
                            ->default(fn () => auth()->user()?->institution_id)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul / Nama Tugas')
                            ->placeholder('Contoh: Laporan Jurnal Mengajar Harian')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi / Instruksi Tugas (Opsional)')
                            ->placeholder('Tuliskan instruksi pelaksanaan tugas wajib harian ini...'),
                        Forms\Components\Select::make('target_roles')
                            ->label('Target Peran (Roles)')
                            ->multiple()
                            ->options(self::getRoleOptions())
                            ->required()
                            ->helperText('Pilih role mana saja di lembaga Anda yang wajib mengerjakan tugas harian ini.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Rakit Form Laporan (Builder)')
                    ->description('Rancang pertanyaan atau ceklis yang harus diisi oleh guru/staf saat melaporkan tugas ini.')
                    ->schema([
                        Forms\Components\Repeater::make('form_schema')
                            ->label('Daftar Kolom Input Laporan')
                            ->default([
                                [
                                    'field_name' => 'Konfirmasi Pelaksanaan Tugas',
                                    'field_type' => 'checkbox',
                                    'is_required' => true,
                                ],
                                [
                                    'field_name' => 'Catatan / Ringkasan Hasil Pelaksanaan',
                                    'field_type' => 'textarea',
                                    'is_required' => true,
                                ],
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('field_name')
                                    ->label('Nama Field (Pertanyaan / Ceklis)')
                                    ->required(),
                                Forms\Components\Select::make('field_type')
                                    ->label('Tipe Input')
                                    ->options([
                                        'checkbox' => 'Ceklis (Ya/Sudah)',
                                        'text' => 'Teks Singkat',
                                        'textarea' => 'Paragraf / Catatan',
                                        'file' => 'Upload Bukti (File/Foto)',
                                    ])
                                    ->required()
                                    ->default('checkbox'),
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
                    ->label('Judul Tugas Wajib')
                    ->weight('bold')
                    ->description(fn (MandatoryTask $record): string => $record->description ?? '')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_roles')
                    ->label('Target Role')
                    ->formatStateUsing(fn ($state) => DailyTodoList::formatRoleLabel((string) $state))
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('today_submissions_count')
                    ->label('Lapor Hari Ini')
                    ->getStateUsing(function (MandatoryTask $record) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->whereDate('submitted_at', today())
                            ->count() . ' Orang';
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Respon')
                    ->counts('submissions')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('lihat_respon')
                    ->label('Lihat Respon')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->modalHeading(fn (MandatoryTask $record) => 'Respon Laporan: ' . $record->title)
                    ->modalContent(function (MandatoryTask $record) {
                        $submissions = MandatoryTaskSubmission::with('user')
                            ->where('mandatory_task_id', $record->id)
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
            ->emptyStateHeading('Belum Ada To-Do List Harian (Wajib)')
            ->emptyStateDescription('Silakan klik tombol "Tambah Tugas Wajib" untuk menugaskan To-Do List Harian kepada guru/staf di lembaga Anda.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMandatoryTasks::route('/'),
            'create' => Pages\CreateMandatoryTask::route('/create'),
            'edit' => Pages\EditMandatoryTask::route('/{record}/edit'),
        ];
    }
}