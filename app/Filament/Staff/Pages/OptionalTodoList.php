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
    protected static ?string $navigationLabel = 'To-Do List Opsional';
    protected static ?string $title = 'To-Do List Opsional';

    protected static string $view = 'filament.staff.pages.my-todo-list';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        // Kepala Sekolah & Superadmin menggunakan menu To-Do List Opsional versi Kelola/Penugasan
        return !$user->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return [];
        }

        return [
            Actions\Action::make('manage_optional_tasks')
                ->label('Kelola & Rakit Tugas Opsional')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(OptionalTaskResource::getUrl('index')),
            Actions\Action::make('create_optional_task')
                ->label('Buat Tugas Opsional Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(OptionalTaskResource::getUrl('create')),
        ];
    }

    protected function buildTaskFormSchema(OptionalTask $record): array
    {
        $schema = [];
        foreach ($record->form_schema ?? [] as $field) {
            $name = $field['field_name'] ?? null;
            if (!$name) {
                continue;
            }
            $type = $field['field_type'] ?? 'text';
            $isRequired = $field['is_required'] ?? false;

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
                $schema[] = \Filament\Forms\Components\FileUpload::make($name)
                    ->label($name)
                    ->directory('optional_task_files')
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
                    ->label('Saya telah melaksanakan kegiatan/tugas opsional ini')
                    ->default(true)
                    ->required(),
                \Filament\Forms\Components\Textarea::make('Uraian Kegiatan & Hasil')
                    ->label('Uraian Kegiatan & Hasil Pelaksanaan')
                    ->placeholder('Tuliskan deskripsi singkat pelaksanaan tugas opsional yang Anda kerjakan...')
                    ->rows(3)
                    ->required(),
            ];
        }

        return $schema;
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $roles = $user->roles->pluck('name')->toArray();
        if (in_array('wali_kelas', $roles) && !in_array('guru_kelas', $roles)) {
            $roles[] = 'guru_kelas';
        }
        if (in_array('guru_kelas', $roles) && !in_array('wali_kelas', $roles)) {
            $roles[] = 'wali_kelas';
        }

        $institutionId = $user->institution_id;

        $candidateTasks = OptionalTask::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($institutionId && $institutionId != 1, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $matchingIds = $candidateTasks
            ->filter(function (OptionalTask $task) use ($roles) {
                if (in_array('superadmin', $roles) || in_array('kepala_sekolah', $roles)) {
                    return true;
                }
                $targetRoles = is_array($task->target_roles) ? $task->target_roles : [];
                return !empty(array_intersect($targetRoles, $roles));
            })
            ->unique('title')
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
                    ->label('Tugas / Kegiatan Opsional')
                    ->weight('bold')
                    ->description(fn (OptionalTask $record): string => $record->description ?? '')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('peran_tupoksi')
                    ->label('Tupoksi Role')
                    ->getStateUsing(function (OptionalTask $record) use ($roles) {
                        $targetRoles = is_array($record->target_roles) ? $record->target_roles : [];
                        $matched = array_intersect($targetRoles, $roles);
                        $displayRoles = !empty($matched) ? $matched : $targetRoles;

                        return collect($displayRoles)
                            ->map(fn ($r) => DailyTodoList::formatRoleLabel($r))
                            ->unique()
                            ->values()
                            ->toArray();
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('reward_points')
                    ->label('Poin Apresiasi')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => $state ? "+{$state} Poin" : '+5 Poin'),
                Tables\Columns\TextColumn::make('status_hari_ini')
                    ->label('Status Hari Ini')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        $doneToday = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                        return $doneToday ? 'Sudah Dikerjakan Hari Ini' : 'Tersedia';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Dikerjakan Hari Ini' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Total Dikerjakan')
                    ->getStateUsing(function (OptionalTask $record) use ($user) {
                        $count = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->count();
                        return $count . ' Kali';
                    })
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('fill_form')
                    ->label('Kerjakan Tugas')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->button()
                    ->modalHeading(fn (OptionalTask $record) => 'Laporan Tugas Opsional: ' . $record->title)
                    ->modalDescription(fn (OptionalTask $record) => $record->description ?? 'Isi detail pelaksanaan tugas opsional untuk mendapatkan poin apresiasi kinerja.')
                    ->form(fn (OptionalTask $record) => $this->buildTaskFormSchema($record))
                    ->action(function (array $data, OptionalTask $record) use ($user) {
                        OptionalTaskSubmission::create([
                            'optional_task_id' => $record->id,
                            'user_id' => $user->id,
                            'answers' => $data,
                            'submitted_at' => now(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Tugas Opsional Berhasil Dikirim')
                            ->body('Apresiasi +' . ($record->reward_points ?? 5) . ' poin telah ditambahkan ke rekam kinerja Anda.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_history')
                    ->label('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Riwayat Pengerjaan Tugas Opsional')
                    ->modalContent(function (OptionalTask $record) use ($user) {
                        $submissions = OptionalTaskSubmission::where('optional_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        return view('filament.staff.pages.optional-submission-history', [
                            'record' => $record,
                            'submissions' => $submissions,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->emptyStateHeading('Tidak Ada Tugas Opsional')
            ->emptyStateDescription('Belum ada daftar tugas opsional untuk peran Anda.');
    }
}