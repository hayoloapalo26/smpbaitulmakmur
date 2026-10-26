<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Staff\Resources\MandatoryTaskResource;
use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
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

class DailyTodoList extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Tugas & Kewajiban';
    protected static ?string $navigationLabel = 'To-Do List Harian (Wajib)';
    protected static ?string $title = 'To-Do List Harian (Wajib)';

    protected static string $view = 'filament.staff.pages.my-todo-list';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        // Kepala Sekolah & Superadmin menggunakan menu To-Do List Harian (Wajib) versi Kelola/Penugasan
        return !$user->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function formatRoleLabel(string $role): string
    {
        return match ($role) {
            'kepala_sekolah' => 'Kepala Sekolah',
            'waka_kurikulum' => 'Waka Kurikulum',
            'waka_kesiswaan' => 'Waka Kesiswaan',
            'guru_mapel' => 'Guru Mapel',
            'wali_kelas', 'guru_kelas' => 'Wali Kelas',
            'guru_alquran' => 'Guru Al-Qur\'an',
            'guru_piket' => 'Guru Piket',
            'bk' => 'Guru BK',
            'tata_usaha' => 'Tata Usaha',
            'sarpras' => 'Sarpras',
            'humas' => 'Humas',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return [];
        }

        return [
            Actions\Action::make('manage_mandatory_tasks')
                ->label('Kelola & Rakit Tugas Wajib')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(MandatoryTaskResource::getUrl('index')),
            Actions\Action::make('create_mandatory_task')
                ->label('Buat Tugas Wajib Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(MandatoryTaskResource::getUrl('create')),
        ];
    }

    protected function buildTaskFormSchema(MandatoryTask $record): array
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
                    ->directory('mandatory_task_files')
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
                    ->label('Saya menyatakan telah melaksanakan tugas wajib harian ini sesuai SOP')
                    ->default(true)
                    ->required(),
                \Filament\Forms\Components\Textarea::make('Catatan Pelaksanaan')
                    ->label('Catatan / Ringkasan Hasil Pelaksanaan')
                    ->placeholder('Tuliskan ringkasan kegiatan atau hasil pelaksanaan hari ini...')
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
        // Sinkronisasi alias wali_kelas <-> guru_kelas
        if (in_array('wali_kelas', $roles) && !in_array('guru_kelas', $roles)) {
            $roles[] = 'guru_kelas';
        }
        if (in_array('guru_kelas', $roles) && !in_array('wali_kelas', $roles)) {
            $roles[] = 'wali_kelas';
        }

        $institutionId = $user->institution_id;

        $candidateTasks = MandatoryTask::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($institutionId && $institutionId != 1, fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $matchingIds = $candidateTasks
            ->filter(function (MandatoryTask $task) use ($roles) {
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
                MandatoryTask::withoutGlobalScopes()->whereIn('id', $matchingIds ?: [0])
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Tugas / Kewajiban Harian')
                    ->weight('bold')
                    ->description(fn (MandatoryTask $record): string => $record->description ?? '')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('peran_tupoksi')
                    ->label('Tupoksi Role')
                    ->getStateUsing(function (MandatoryTask $record) use ($roles) {
                        $targetRoles = is_array($record->target_roles) ? $record->target_roles : [];
                        $matched = array_intersect($targetRoles, $roles);
                        $displayRoles = !empty($matched) ? $matched : $targetRoles;

                        return collect($displayRoles)
                            ->map(fn ($r) => self::formatRoleLabel($r))
                            ->unique()
                            ->values()
                            ->toArray();
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Hari Ini')
                    ->getStateUsing(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();

                        return $submission ? 'Sudah Tuntas' : 'Belum Dikerjakan';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Tuntas' => 'success',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('waktu_lapor')
                    ->label('Waktu Lapor')
                    ->getStateUsing(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();

                        return $submission ? $submission->submitted_at->format('H:i') . ' WIB' : '-';
                    })
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('fill_form')
                    ->label('Isi Laporan')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->button()
                    ->hidden(function (MandatoryTask $record) use ($user) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                    })
                    ->modalHeading(fn (MandatoryTask $record) => 'Laporan Harian: ' . $record->title)
                    ->modalDescription(fn (MandatoryTask $record) => $record->description ?? 'Silakan lengkapi ceklis dan catatan pelaksanaan tugas wajib harian berikut.')
                    ->form(fn (MandatoryTask $record) => $this->buildTaskFormSchema($record))
                    ->action(function (array $data, MandatoryTask $record) use ($user) {
                        MandatoryTaskSubmission::updateOrCreate(
                            [
                                'mandatory_task_id' => $record->id,
                                'user_id' => $user->id,
                                'submitted_at' => MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                                    ->where('user_id', $user->id)
                                    ->whereDate('submitted_at', today())
                                    ->value('submitted_at') ?? now(),
                            ],
                            [
                                'answers' => $data,
                            ]
                        );
                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Harian Berhasil Disimpan')
                            ->body('Terima kasih, tugas harian "' . $record->title . '" telah tercatat tuntas hari ini.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('edit_submission')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->visible(function (MandatoryTask $record) use ($user) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                    })
                    ->modalHeading(fn (MandatoryTask $record) => 'Perbarui Laporan: ' . $record->title)
                    ->fillForm(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();
                        return $submission ? ($submission->answers ?? []) : [];
                    })
                    ->form(fn (MandatoryTask $record) => $this->buildTaskFormSchema($record))
                    ->action(function (array $data, MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();
                        if ($submission) {
                            $submission->update([
                                'answers' => $data,
                                'submitted_at' => now(),
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Laporan Harian Diperbarui')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_submission')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(function (MandatoryTask $record) use ($user) {
                        return MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->exists();
                    })
                    ->modalHeading('Detail Laporan Harian')
                    ->modalContent(function (MandatoryTask $record) use ($user) {
                        $submission = MandatoryTaskSubmission::where('mandatory_task_id', $record->id)
                            ->where('user_id', $user->id)
                            ->whereDate('submitted_at', today())
                            ->first();

                        return view('filament.staff.pages.mandatory-submission-detail', [
                            'record' => $record,
                            'submission' => $submission,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->emptyStateHeading('Tidak Ada Tugas Wajib Harian')
            ->emptyStateDescription('Belum ada daftar tugas wajib harian untuk peran Anda.');
    }
}