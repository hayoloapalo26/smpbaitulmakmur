<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Pages\DailyTodoList;
use App\Filament\Staff\Resources\OptionalTaskResource\Pages;
use App\Models\Institution;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OptionalTaskResource extends Resource
{
    protected static ?string $model = OptionalTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'To-Do List Opsional';
    protected static ?string $modelLabel = 'To-Do List Opsional';
    protected static ?string $pluralModelLabel = 'To-Do List Opsional';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tugas Opsional')
                    ->description('Tambahkan tugas opsional yang akan otomatis muncul di menu To-Do List Opsional sesuai role di lembaga Anda.')
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Lembaga / Unit Sekolah')
                            ->options(Institution::where('id', '!=', 1)->pluck('name', 'id'))
                            ->default(fn () => auth()->user()?->institution_id)
                            ->visible(fn () => auth()->user()?->hasRole('superadmin'))
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Tugas Opsional')
                            ->placeholder('Contoh: Pembinaan Ekstrakurikuler / Klinik Akademik')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi / Instruksi Tugas (Opsional)')
                            ->placeholder('Tuliskan deskripsi tugas opsional ini...'),
                        Forms\Components\Select::make('target_roles')
                            ->label('Target Peran (Roles)')
                            ->multiple()
                            ->options(MandatoryTaskResource::getRoleOptions())
                            ->required()
                            ->helperText('Pilih role mana saja di lembaga Anda yang dapat mengambil tugas opsional ini.'),
                        Forms\Components\TextInput::make('reward_points')
                            ->label('Poin Apresiasi')
                            ->numeric()
                            ->default(5)
                            ->helperText('Poin tambahan/apresiasi kinerja saat tugas ini dikerjakan.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Rakit Form Laporan (Builder)')
                    ->description('Rancang pertanyaan atau inputan yang harus diisi oleh guru/staf saat mengerjakan tugas opsional ini.')
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
                                    'field_name' => 'Uraian Kegiatan & Hasil Pelaksanaan',
                                    'field_type' => 'textarea',
                                    'is_required' => true,
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
                                        'text' => 'Teks Singkat',
                                        'textarea' => 'Paragraf / Catatan',
                                        'file' => 'Upload Bukti (File/Foto)',
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
                    ->label('Judul Tugas Opsional')
                    ->weight('bold')
                    ->description(fn (OptionalTask $record): string => $record->description ?? '')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_roles')
                    ->label('Target Role')
                    ->formatStateUsing(fn ($state) => DailyTodoList::formatRoleLabel((string) $state))
                    ->badge()
                    ->color('info'),
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
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('lihat_respon')
                    ->label('Lihat Respon')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->modalHeading(fn (OptionalTask $record) => 'Respon Tugas Opsional: ' . $record->title)
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
            ->emptyStateHeading('Belum Ada To-Do List Opsional')
            ->emptyStateDescription('Silakan klik tombol "Tambah Tugas Opsional" untuk menugaskan To-Do List Opsional kepada guru/staf di lembaga Anda.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOptionalTasks::route('/'),
            'create' => Pages\CreateOptionalTask::route('/create'),
            'edit' => Pages\EditOptionalTask::route('/{record}/edit'),
        ];
    }
}