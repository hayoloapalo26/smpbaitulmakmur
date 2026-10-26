<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DcmResponseResource\Pages;
use App\Models\DcmResponse;
use App\Models\Student;
use App\Models\Classroom;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DcmResponseResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['bk', 'superadmin']);
    }

    protected static ?string $model = DcmResponse::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Hasil DCM Siswa';
    protected static ?string $navigationLabel = 'Data DCM Siswa';
    protected static ?int $navigationSort = 1;

    

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('student.classroom');
    }

    public static function table(Table $table): Table
    {
        $categories = DcmResponse::getCategories();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Tanggal Isi')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                // Skor per kategori ringkasan
                Tables\Columns\TextColumn::make('total_score_display')
                    ->label('Total Skor')
                    ->state(fn (DcmResponse $record): string => $record->total_score . ' masalah')
                    ->badge()
                    ->color(fn (DcmResponse $record): string => match(true) {
                        $record->total_score >= 50 => 'danger',
                        $record->total_score >= 20 => 'warning',
                        $record->total_score > 0   => 'info',
                        default                    => 'gray',
                    }),
                // Skor per indikator (ringkasan 3 terbesar)
                Tables\Columns\TextColumn::make('top_categories')
                    ->label('Kategori Dominan')
                    ->state(function (DcmResponse $record) use ($categories): string {
                        $scores = [];
                        foreach ($categories as $field => $cat) {
                            $count = count($record->$field ?? []);
                            if ($count > 0) {
                                $scores[$cat['short']] = $count;
                            }
                        }
                        arsort($scores);
                        $top = array_slice($scores, 0, 3, true);
                        return implode(', ', array_map(fn($k, $v) => "{$k}({$v})", array_keys($top), array_values($top)));
                    })
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom')
                    ->label('Kelas')
                    ->relationship('student.classroom', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Detail'),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->emptyStateHeading('Belum ada data DCM')
            ->emptyStateDescription('Siswa belum mengisi formulir DCM.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDcmResponses::route('/'),
            'view'  => Pages\ViewDcmResponse::route('/{record}'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data DCM Siswa';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
