<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\SchoolWorkPlanResource\Pages;
use App\Models\SchoolWorkPlan;
use App\Models\Scopes\InstitutionScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SchoolWorkPlanResource extends Resource
{
    protected static ?string $model = SchoolWorkPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'RKT & RKS';
    protected static ?string $modelLabel = 'RKT & RKS';
    protected static ?string $pluralModelLabel = 'RKT & RKS';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Gate bypass untuk Yayasan agar bisa melihat semua lembaga
        if (auth()->user()->hasAnyRole(['ketua_yayasan', 'pembina_yayasan', 'superadmin'])) {
            $query->withoutGlobalScope(InstitutionScope::class);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('document_type')
                    ->label('Tipe Dokumen')
                    ->options([
                        'RKT' => 'RKT (Rencana Kerja Tahunan)',
                        'RKS' => 'RKS (Rencana Kerja Sekolah)'
                    ])
                    ->required()
                    ->disabled(fn (?SchoolWorkPlan $record) => auth()->user()->hasRole('kepala_sekolah') && $record && !in_array($record->status, ['draft', 'revision_ketua', 'revision_pembina'])),

                Forms\Components\TextInput::make('drive_link')
                    ->label('Link Google Drive Dokumen')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (?SchoolWorkPlan $record) => auth()->user()->hasRole('kepala_sekolah') && $record && !in_array($record->status, ['draft', 'revision_ketua', 'revision_pembina'])),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasAnyRole(['ketua_yayasan', 'pembina_yayasan', 'superadmin'])),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipe')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('drive_link')
                    ->label('Tautan Dokumen')
                    ->formatStateUsing(fn ($state) => 'Buka Drive')
                    ->url(fn ($record) => $record->drive_link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'reviewing_ketua' => 'info',
                        'revision_ketua', 'revision_pembina' => 'danger',
                        'approved_ketua' => 'success',
                        'approved_final' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'submitted' => 'Menunggu Review',
                        'reviewing_ketua' => 'Sedang Direview (Ketua)',
                        'revision_ketua' => 'Revisi (Ketua)',
                        'approved_ketua' => 'Disetujui Ketua',
                        'revision_pembina' => 'Revisi (Pembina)',
                        'approved_final' => 'ACC Final',
                    }),

                Tables\Columns\TextColumn::make('ketua_note')
                    ->label('Catatan Ketua')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pembina_note')
                    ->label('Catatan Pembina')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->relationship('institution', 'name')
                    ->visible(fn() => auth()->user()->hasAnyRole(['ketua_yayasan', 'pembina_yayasan', 'superadmin'])),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Menunggu Review',
                        'reviewing_ketua' => 'Sedang Direview (Ketua)',
                        'revision_ketua' => 'Revisi (Ketua)',
                        'approved_ketua' => 'Disetujui Ketua',
                        'revision_pembina' => 'Revisi (Pembina)',
                        'approved_final' => 'ACC Final',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // --- AKSI KEPALA SEKOLAH ---
                Tables\Actions\Action::make('tandai_selesai')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('kepala_sekolah') && in_array($record->status, ['draft', 'revision_ketua', 'revision_pembina']))
                    ->action(fn (SchoolWorkPlan $record) => $record->update(['status' => 'submitted'])),

                // --- AKSI KETUA YAYASAN ---
                Tables\Actions\Action::make('proses_review_ketua')
                    ->label('Proses Review')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('ketua_yayasan') && $record->status === 'submitted')
                    ->action(fn (SchoolWorkPlan $record) => $record->update(['status' => 'reviewing_ketua'])),

                Tables\Actions\Action::make('setuju_ketua')
                    ->label('Setuju')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('ketua_yayasan') && in_array($record->status, ['submitted', 'reviewing_ketua', 'revision_ketua']))
                    ->action(fn (SchoolWorkPlan $record) => $record->update(['status' => 'approved_ketua'])),

                Tables\Actions\Action::make('perbaiki_ketua')
                    ->label('Perbaiki')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('ketua_yayasan') && in_array($record->status, ['submitted', 'reviewing_ketua', 'revision_ketua']))
                    ->form([
                        Forms\Components\Textarea::make('ketua_note')
                            ->label('Catatan Perbaikan')
                            ->required(),
                    ])
                    ->action(function (array $data, SchoolWorkPlan $record): void {
                        $record->update([
                            'status' => 'revision_ketua',
                            'ketua_note' => $data['ketua_note'],
                        ]);
                    }),

                // --- AKSI PEMBINA YAYASAN ---
                Tables\Actions\Action::make('acc_final')
                    ->label('ACC Final')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('pembina_yayasan') && in_array($record->status, ['approved_ketua', 'revision_pembina']))
                    ->action(fn (SchoolWorkPlan $record) => $record->update(['status' => 'approved_final'])),

                Tables\Actions\Action::make('perbaiki_pembina')
                    ->label('Perbaiki')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SchoolWorkPlan $record) => auth()->user()->hasRole('pembina_yayasan') && in_array($record->status, ['approved_ketua', 'revision_pembina']))
                    ->form([
                        Forms\Components\Textarea::make('pembina_note')
                            ->label('Catatan Perbaikan')
                            ->required(),
                    ])
                    ->action(function (array $data, SchoolWorkPlan $record): void {
                        $record->update([
                            'status' => 'revision_pembina',
                            'pembina_note' => $data['pembina_note'],
                        ]);
                    }),
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
            'index' => Pages\ListSchoolWorkPlans::route('/'),
            'create' => Pages\CreateSchoolWorkPlan::route('/create'),
            'edit' => Pages\EditSchoolWorkPlan::route('/{record}/edit'),
        ];
    }
}
