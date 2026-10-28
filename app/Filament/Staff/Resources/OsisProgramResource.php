<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\OsisProgramResource\Pages;
use App\Models\OsisProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OsisProgramResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    protected static ?string $model = OsisProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Program OSIS';
    protected static ?string $recordTitleAttribute = 'nama_program';


    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_program')->searchable()->required(),
                Forms\Components\DatePicker::make('tanggal')->required(),
                Forms\Components\Select::make('status')
                    ->options(['direncanakan'=>'Direncanakan','berjalan'=>'Berjalan','selesai'=>'Selesai','batal'=>'Batal'])
                    ->required(),
                Forms\Components\FileUpload::make('proposal_file')->label('Proposal'),
                Forms\Components\FileUpload::make('lpj_file')->label('LPJ'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('summary_table')
                    ->view('filament.staff.components.program-kerja-summary-table')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama_program ?? 'Program Kerja OSIS'))
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(route('report.osis'))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageOsisPrograms::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
