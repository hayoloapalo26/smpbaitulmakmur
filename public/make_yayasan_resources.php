<?php
$dir = __DIR__.'/../app/Filament/Yayasan/Resources';

$supervisiContent = <<<'PHP'
<?php
namespace App\Filament\Yayasan\Resources;

use App\Models\TeacherSupervision;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermintaanSupervisiResource extends Resource
{
    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Permintaan Supervisi';
    protected static ?string $pluralModelLabel = 'Permintaan Supervisi Guru';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->orderBy('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'tim_pengembang_yayasan']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')->label('Asal Lembaga')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')->label('Nama Guru')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->label('Tgl Pengajuan')->date('d M Y'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'menunggu',
                        'primary' => 'dijadwalkan',
                        'danger' => 'ditolak',
                        'success' => 'dinilai',
                    ]),
                Tables\Columns\TextColumn::make('jadwal')->label('Jadwal')->dateTime('d M Y, H:i')->placeholder('Belum Ada'),
                Tables\Columns\TextColumn::make('score')->label('Nilai')->badge()->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu Persetujuan',
                        'dijadwalkan' => 'Dijadwalkan',
                        'ditolak' => 'Ditolak',
                        'dinilai' => 'Selesai Dinilai',
                    ]),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->options(\App\Models\Institution::where('id', '!=', 1)->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('terima')
                    ->label('Terima & Jadwalkan')
                    ->icon('heroicon-o-calendar')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'menunggu')
                    ->form([
                        Forms\Components\DateTimePicker::make('jadwal')->label('Atur Jadwal')->required(),
                        Forms\Components\Textarea::make('catatan')->label('Catatan Yayasan')->nullable(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'dijadwalkan',
                        'jadwal' => $data['jadwal'],
                        'catatan_yayasan' => $data['catatan'],
                    ])),
                    
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'menunggu')
                    ->form([
                        Forms\Components\Textarea::make('catatan')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'ditolak',
                        'catatan_yayasan' => $data['catatan'],
                    ])),
                    
                Tables\Actions\Action::make('nilai')
                    ->label('Beri Nilai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'dijadwalkan')
                    ->form([
                        Forms\Components\TextInput::make('score')->label('Nilai (0-100)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\Textarea::make('hasil')->label('Hasil Evaluasi')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'dinilai',
                        'score' => $data['score'],
                        'hasil' => $data['hasil'],
                        'supervisor_id' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages\ListPermintaanSupervisis::route('/'),
        ];
    }
}
PHP;

$ukgContent = <<<'PHP'
<?php
namespace App\Filament\Yayasan\Resources;

use App\Models\TeacherCompetencyTest;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermintaanUkgResource extends Resource
{
    protected static ?string $model = TeacherCompetencyTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Permintaan UKG';
    protected static ?string $pluralModelLabel = 'Permintaan Uji Kompetensi Guru';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->orderBy('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'tim_pengembang_yayasan']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')->label('Asal Lembaga')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')->label('Nama Guru')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->label('Tgl Pengajuan')->date('d M Y'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'menunggu',
                        'primary' => 'dijadwalkan',
                        'danger' => 'ditolak',
                        'success' => 'dinilai',
                    ]),
                Tables\Columns\TextColumn::make('jadwal')->label('Jadwal')->dateTime('d M Y, H:i')->placeholder('Belum Ada'),
                Tables\Columns\TextColumn::make('score')->label('Nilai')->badge()->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu Persetujuan',
                        'dijadwalkan' => 'Dijadwalkan',
                        'ditolak' => 'Ditolak',
                        'dinilai' => 'Selesai Dinilai',
                    ]),
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->options(\App\Models\Institution::where('id', '!=', 1)->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\Action::make('terima')
                    ->label('Terima & Jadwalkan')
                    ->icon('heroicon-o-calendar')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'menunggu')
                    ->form([
                        Forms\Components\DateTimePicker::make('jadwal')->label('Atur Jadwal')->required(),
                        Forms\Components\Textarea::make('catatan')->label('Catatan Yayasan')->nullable(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'dijadwalkan',
                        'jadwal' => $data['jadwal'],
                        'catatan_yayasan' => $data['catatan'],
                    ])),
                    
                Tables\Actions\Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'menunggu')
                    ->form([
                        Forms\Components\Textarea::make('catatan')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'ditolak',
                        'catatan_yayasan' => $data['catatan'],
                    ])),
                    
                Tables\Actions\Action::make('nilai')
                    ->label('Beri Nilai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'dijadwalkan')
                    ->form([
                        Forms\Components\TextInput::make('score')->label('Nilai (0-100)')->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\Textarea::make('catatan_yayasan')->label('Catatan Evaluasi / Hasil')->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->update([
                        'status' => 'dinilai',
                        'score' => $data['score'],
                        'catatan_yayasan' => $data['catatan_yayasan'],
                        'assessor_id' => auth()->id(),
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages\ListPermintaanUkgs::route('/'),
        ];
    }
}
PHP;

file_put_contents($dir.'/PermintaanSupervisiResource.php', $supervisiContent);
file_put_contents($dir.'/PermintaanUkgResource.php', $ukgContent);

@mkdir($dir.'/PermintaanSupervisiResource/Pages', 0755, true);
@mkdir($dir.'/PermintaanUkgResource/Pages', 0755, true);

$listSup = <<<'PHP'
<?php
namespace App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages;
use App\Filament\Yayasan\Resources\PermintaanSupervisiResource;
use Filament\Resources\Pages\ListRecords;

class ListPermintaanSupervisis extends ListRecords {
    protected static string $resource = PermintaanSupervisiResource::class;
    protected function getActions(): array { return []; }
}
PHP;
file_put_contents($dir.'/PermintaanSupervisiResource/Pages/ListPermintaanSupervisis.php', $listSup);

$listUkg = <<<'PHP'
<?php
namespace App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages;
use App\Filament\Yayasan\Resources\PermintaanUkgResource;
use Filament\Resources\Pages\ListRecords;

class ListPermintaanUkgs extends ListRecords {
    protected static string $resource = PermintaanUkgResource::class;
    protected function getActions(): array { return []; }
}
PHP;
file_put_contents($dir.'/PermintaanUkgResource/Pages/ListPermintaanUkgs.php', $listUkg);

echo 'SUCCESS YAYASAN';
