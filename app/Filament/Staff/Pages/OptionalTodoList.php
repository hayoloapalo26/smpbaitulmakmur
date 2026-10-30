<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OptionalTodoList extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'Tugas Insidentil';
    protected static ?string $title = 'Tugas Insidentil';

    protected static string $view = 'filament.staff.pages.my-todo-list';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        // Kepala Sekolah & Superadmin mengelola & menginput tugas insidentil lewat menu Kelola Tugas Insidentil
        return !$user->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return [];
        }

        return [
            Actions\Action::make('manage_optional_tasks')
                ->label('Kelola & Input Tugas Insidentil')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->url(OptionalTaskResource::getUrl('index')),
            Actions\Action::make('create_optional_task')
                ->label('Buat Tugas Insidentil Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(OptionalTaskResource::getUrl('create')),
        ];
    }

    protected function buildTaskFormSchema(OptionalTask $record): array
    {
        $schema = [];
        foreach ($record->form_schema ?? [] as $field) {
            $name = $field['field_name'] ?? $field['name'] ?? null;
            if (!$name) {
                continue;
            }
            $type = $field['field_type'] ?? $field['type'] ?? 'text';
            $isRequired = $field['is_required'] ?? $field['required'] ?? false;

            if ($type === 'checkbox') {
                $schema[] = \Filament\Forms\Components\Checkbox::make($name)
                    ->label($name)
                    ->required($isRequired);
            } elseif ($type === 'textarea') {
                $schema[] = \Filament\Forms\Components\Textarea::make($name)
                    ->label($name)
                    ->rows(3)
                    ->required($isRequired);
            } elseif (in_array($type, ['file', 'file_upload'])) {
                $schema[] = \Filament\Forms\Components\TextInput::make($name)
                    ->label($name . ' (Link Google Drive Bukti)')
                    ->placeholder('https://drive.google.com/...')
                    ->url()
                    ->prefixIcon('heroicon-o-link')
                    ->helperText('Tautkan link Google Drive dokumen atau foto bukti pelaksanaan tugas insidentil.')
                    ->maxLength(2048)
                    ->required($isRequired);
            } else {
                $schema[] = \Filament\Forms\Components\TextInput::make($name)
                    ->label($name)
                    ->required($isRequired);
            }
        }

        if (empty($schema)) {
            $schema = [
                \Filament\Forms\Components\Checkbox::make('Konfirmasi Pelaksanaan')
                    ->label('Saya telah melaksanakan tugas insidentil ini dengan baik')
                    ->default(true)
                    ->required(),
                \Filament\Forms\Components\Textarea::make('Uraian Kegiatan & Hasil')
                    ->label('Uraian Pelaksanaan & Hasil Kegiatan')
                    ->placeholder('Tuliskan rincian hasil pelaksanaan tugas insidentil yang telah diselesaikan...')
                    ->rows(3)
                    ->required(),
            ];
        }

        return $schema;
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $institutionId = $user->institution_id;

        $candidateTasks = OptionalTask::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($institutionId && $institutionId != 1, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $matchingIds = $candidateTasks
            ->filter(fn (OptionalTask $task) => $task->isAssignedToUser($user))
            ->pluck('id')
            ->toArray();

        return $table
            ->query(
                OptionalTask::withoutGlobalScopes()->whereIn('id', $matchingIds ?: [0])
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tugas Insidentil')
                    ->weight('bold')
                    ->description(fn (OptionalTask $record): string => $record->description ? "Arahan: {$record->description}" : '')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sasaran')
                    ->label('Sasaran Tugas')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        if ($record->target_type === 'all') {
                            return '🌐 Seluruh Lembaga';
                        }
                        if ($record->target_type === 'users') {
                            return '👤 Ditugaskan Khusus Anda';
                        }
                        $roles = is_array($record->target_roles) ? $record->target_roles : [];
                        $userRoles = $user->roles->pluck('name')->toArray();
                        $matched = array_intersect($roles, $userRoles);
                        $displayRoles = !empty($matched) ? $matched : $roles;

                        return collect($displayRoles)
                            ->map(fn ($r) => OptionalTaskResource::getRoleLabel($r))
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

                Tables\Columns\TextColumn::make('status_pengerjaan')
                    ->label('Status Laporan')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        $done = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->exists();
                        return $done ? 'Sudah Dilaporkan' : 'Belum Dilaporkan';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Dilaporkan' => 'success',
                        default            => 'warning',
                    }),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Dilaporkan')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        $count = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->count();
                        return $count . ' Kali';
                    })
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('fill_form')
                    ->label('Laporkan Tugas')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->button()
                    ->modalHeading(fn (OptionalTask $record) => 'Laporan Pelaksanaan Tugas Insidentil: ' . $record->title)
                    ->modalDescription(fn (OptionalTask $record) => $record->description ?? 'Isi detail pelaksanaan tugas insidentil untuk dilaporkan kepada Kepala Sekolah.')
                    ->form(fn (OptionalTask $record) => $this->buildTaskFormSchema($record))
                    ->action(function (array $data, OptionalTask $record) use ($user) {
                        OptionalTaskSubmission::create([
                            'optional_task_id' => $record->id,
                            'user_id'          => $user->id,
                            'answers'          => $data,
                            'submitted_at'     => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Tugas Insidentil Berhasil Dikirim!')
                            ->body('Alhamdulillah, laporan tugas insidentil Anda telah tersimpan dan poin apresiasi (+' . ($record->reward_points ?? 5) . ' poin) telah ditambahkan.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_history')
                    ->label('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Riwayat Laporan Tugas Insidentil')
                    ->modalContent(function (OptionalTask $record) use ($user) {
                        $submissions = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        return view('filament.staff.pages.optional-submission-history', [
                            'record'      => $record,
                            'submissions' => $submissions,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->emptyStateHeading('Belum Ada Tugas Insidentil')
            ->emptyStateDescription('Saat ini belum ada tugas insidentil dari Kepala Sekolah yang ditujukan untuk Anda.');
    }
}
