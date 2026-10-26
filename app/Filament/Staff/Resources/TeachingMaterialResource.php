<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeachingMaterialResource\Pages;
use App\Models\TeachingMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeachingMaterialResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_mapel', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = TeachingMaterial::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Perangkat Ajar';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah'])) {
            return $query;
        }
        return $query->where('teacher_id', Auth::id());
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(Auth::id()),
                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required(),
                Forms\Components\TextInput::make('google_docs_link')
                    ->url()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'diajukan' => 'Diajukan',
                        'direview_wakakur' => 'Ditinjau Waka Kurikulum',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->default('diajukan')
                    ->disabled(fn () => !Auth::user()->hasRole('waka_kurikulum')),
                Forms\Components\Textarea::make('wakakur_note')
                    ->label('Catatan Waka Kurikulum')
                    ->disabled(fn () => !Auth::user()->hasRole('waka_kurikulum')),
                Forms\Components\Textarea::make('kepsek_note')
                    ->label('Catatan Kepala Sekolah')
                    ->disabled(fn () => !Auth::user()->hasRole('kepala_sekolah')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->searchable()->label('Guru'),
                Tables\Columns\TextColumn::make('subject.name')->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('google_docs_link')->url(fn ($record) => $record->google_docs_link, true)->label('Link'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->colors([
                        'warning' => 'diajukan',
                        'primary' => 'direview_wakakur',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('teruskan')
                    ->label('Setujui Waka Kurikulum')
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->visible(fn ($record) => Auth::user()->hasRole('waka_kurikulum') && $record->status === 'diajukan')
                    ->form([
                        Forms\Components\Textarea::make('wakakur_note')->label('Catatan'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'direview_wakakur',
                            'wakakur_note' => $data['wakakur_note'] ?? null,
                            'wakakur_approved_by' => Auth::id(),
                            'wakakur_approved_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('setujui_kepsek')
                    ->label('Setujui Kepsek')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => Auth::user()->hasRole('kepala_sekolah') && $record->status === 'direview_wakakur')
                    ->form([
                        Forms\Components\Textarea::make('kepsek_note')->label('Catatan Kepsek'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'disetujui',
                            'kepsek_note' => $data['kepsek_note'] ?? null,
                            'kepsek_approved_by' => Auth::id(),
                            'kepsek_approved_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('tolak_kepsek')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => Auth::user()->hasRole('kepala_sekolah') && $record->status === 'direview_wakakur')
                    ->form([
                        Forms\Components\Textarea::make('kepsek_note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'ditolak',
                            'kepsek_note' => $data['kepsek_note'],
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTeachingMaterials::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
