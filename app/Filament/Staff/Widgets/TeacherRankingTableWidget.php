<?php

namespace App\Filament\Staff\Widgets;

use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\TeacherTodoList;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;

class TeacherRankingTableWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Leaderboard Kinerja (Detail)';
    protected static ?string $description = 'Peringkat diurutkan berdasarkan Total KPI Guru (50% Presensi + 50% Tugas).';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('institution_id', auth()->user()->institution_id ?? 0)
                    ->whereHas('roles', function($q) {
                        $q->whereIn('name', ['guru_mapel', 'wali_kelas', 'guru_alquran']);
                    })
            )
            ->filters([
                Filter::make('periode')
                    ->form([
                        Select::make('rentang')
                            ->label('Rentang Waktu')
                            ->options([
                                'harian' => 'Harian',
                                'mingguan' => 'Mingguan',
                                'bulanan' => 'Bulanan',
                            ])
                            ->default('bulanan')
                    ])
                    ->query(function (Builder $query, array $data) {
                        $rentang = $data['rentang'] ?? 'bulanan';
                        $now = Carbon::now();
                        
                        $start = match($rentang) {
                            'harian' => $now->copy()->startOfDay(),
                            'mingguan' => $now->copy()->startOfWeek(),
                            default => $now->copy()->startOfMonth(),
                        };
                        
                        $end = match($rentang) {
                            'harian' => $now->copy()->endOfDay(),
                            'mingguan' => $now->copy()->endOfWeek(),
                            default => $now->copy()->endOfMonth(),
                        };

                        // Injecting calculation Subqueries avoiding division by zero via NULLIF
                        // Nilai Presensi = (Hadir / Total Hari) * 50
                        $query->addSelect([
                            'nilai_presensi' => TeacherAttendance::selectRaw('ROUND(COALESCE((SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) * 50.0 / NULLIF(COUNT(id), 0)), 0), 1)')
                                ->whereColumn('teacher_id', 'users.id')
                                ->whereBetween('tanggal', [$start, $end]),
                                
                            'nilai_tugas' => TeacherTodoList::selectRaw('ROUND(COALESCE((SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) * 50.0 / NULLIF(COUNT(id), 0)), 0), 1)')
                                ->whereColumn('teacher_id', 'users.id')
                                ->whereBetween('tanggal', [$start, $end]),
                        ]);
                        
                        // Calculating total_kpi directly to allow sorting
                        $query->selectRaw('
                            (
                                COALESCE( (SELECT ROUND((SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) * 50.0 / NULLIF(COUNT(id), 0)), 1) FROM teacher_attendances WHERE teacher_id = users.id AND tanggal BETWEEN ? AND ?), 0)
                                +
                                COALESCE( (SELECT ROUND((SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) * 50.0 / NULLIF(COUNT(id), 0)), 1) FROM teacher_todo_lists WHERE teacher_id = users.id AND tanggal BETWEEN ? AND ?), 0)
                            ) as total_kpi
                        ', [$start, $end, $start, $end]);
                    })
            ])
            ->columns([
                Tables\Columns\TextColumn::make('peringkat')
                    ->label('Peringkat')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nilai_presensi')
                    ->label('Skor Presensi Jurnal')
                    ->getStateUsing(function (User $record) {
                        $rentang = $this->tableFilters['periode']['rentang'] ?? 'bulanan';
                        $kpiService = new \App\Services\TeacherKpiService();
                        return round(($kpiService->calculateAttendancePercentage($record->id, $rentang) / 100) * 50, 1);
                    })
                    ->formatStateUsing(fn ($state) => $state . ' / 50%'),

                Tables\Columns\TextColumn::make('nilai_tugas')
                    ->label('Skor To-Do List (Wajib & Opsional)')
                    ->getStateUsing(function (User $record) {
                        $rentang = $this->tableFilters['periode']['rentang'] ?? 'bulanan';
                        $kpiService = new \App\Services\TeacherKpiService();
                        return round(($kpiService->calculateTodoPercentage($record->id, $rentang) / 100) * 50, 1);
                    })
                    ->formatStateUsing(fn ($state) => $state . ' / 50%'),

                Tables\Columns\TextColumn::make('total_kpi')
                    ->label('Total KPI')
                    ->getStateUsing(function (User $record) {
                        $rentang = $this->tableFilters['periode']['rentang'] ?? 'bulanan';
                        $kpiService = new \App\Services\TeacherKpiService();
                        return $kpiService->calculateForTeacher($record->id, $rentang);
                    })
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->weight('black')
                    ->size('lg'),
            ])
            ->striped();
    }
}
