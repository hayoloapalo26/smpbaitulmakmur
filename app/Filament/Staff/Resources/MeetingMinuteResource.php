<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\MeetingMinuteResource\Pages;
use App\Models\MeetingMinute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MeetingMinuteResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']);
    }

    protected static ?string $model = MeetingMinute::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?int $navigationSort = 6;
    protected static ?string $modelLabel = 'Notulensi Rapat';
    protected static ?string $recordTitleAttribute = 'judul';


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha'])) {
            return $query;
        }

        // Guru biasa hanya melihat yang umum
        return $query->where('visibility', 'umum');
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')->required(),
                Forms\Components\TextInput::make('judul')->required(),
                Forms\Components\Select::make('visibility')
                    ->options(['umum' => 'Umum', 'internal' => 'Internal'])
                    ->default('umum')->required(),
                Forms\Components\Select::make('attendees')
                    ->label('Peserta Rapat')
                    ->multiple()
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->searchable()
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('isi')->required()->columnSpanFull(),
                Forms\Components\FileUpload::make('file')->label('Lampiran File'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('judul')->searchable(),
                Tables\Columns\TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'success',
                        'internal' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status_persetujuan')
                    ->label('Status KS')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('acc_ks')
                    ->label('ACC KS')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MeetingMinute $record) => auth()->user()->hasRole('kepala_sekolah') && $record->status_persetujuan === 'menunggu')
                    ->form([
                        Forms\Components\Select::make('status_persetujuan')
                            ->label('Keputusan')
                            ->options(['disetujui' => 'Setujui', 'ditolak' => 'Tolak'])
                            ->required(),
                        Forms\Components\Textarea::make('catatan_ks')->label('Catatan'),
                    ])
                    ->action(function (MeetingMinute $record, array $data) {
                        $record->update([
                            'status_persetujuan' => $data['status_persetujuan'],
                            'catatan_ks' => $data['catatan_ks'],
                        ]);
                    })
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
            'index' => Pages\ManageMeetingMinutes::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
