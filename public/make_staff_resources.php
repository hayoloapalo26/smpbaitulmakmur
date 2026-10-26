<?php
$dir = __DIR__.'/../app/Filament/Staff/Resources';

$supervisiContent = <<<'PHP'
<?php
namespace App\Filament\Staff\Resources;

use App\Models\TeacherSupervision;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class PengajuanSupervisiResource extends Resource
{
    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Penilaian & Kompetensi';
    protected static ?string $navigationLabel = 'Pengajuan Supervisi';
    protected static ?string $pluralModelLabel = 'Pengajuan Supervisi Guru';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasRole('kepala_sekolah')) {
            return $query; // KS sees all in their institution (handled by trait)
        }
        return $query->where('teacher_id', Auth::id()); // Teacher sees their own
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('tanggal')->default(now()),
                Forms\Components\Textarea::make('catatan')
                    ->label('Keterangan / Tujuan Supervisi')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->label('Nama Guru')->visible(fn() => Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                Tables\Columns\TextColumn::make('tanggal')->label('Tgl Pengajuan')->date('d M Y'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'menunggu',
                        'primary' => 'dijadwalkan',
                        'danger' => 'ditolak',
                        'success' => 'dinilai',
                    ]),
                Tables\Columns\TextColumn::make('jadwal')->label('Jadwal Supervisi')->dateTime('d M Y, H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('score')->label('Nilai')->badge()->color('success')->placeholder('Belum Dinilai'),
                Tables\Columns\TextColumn::make('hasil')->label('Hasil / Masukan')->limit(30),
                Tables\Columns\TextColumn::make('catatan_yayasan')->label('Catatan Yayasan')->limit(30),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\PengajuanSupervisiResource\Pages\ListPengajuanSupervisis::route('/'),
        ];
    }
}
PHP;

$ukgContent = <<<'PHP'
<?php
namespace App\Filament\Staff\Resources;

use App\Models\TeacherCompetencyTest;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class PengajuanUkgResource extends Resource
{
    protected static ?string $model = TeacherCompetencyTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Penilaian & Kompetensi';
    protected static ?string $navigationLabel = 'Pengajuan UKG';
    protected static ?string $pluralModelLabel = 'Pengajuan Uji Kompetensi Guru';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()->hasRole('kepala_sekolah')) {
            return $query; // KS sees all in their institution
        }
        return $query->where('teacher_id', Auth::id()); // Teacher sees their own
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['superadmin', 'kepala_sekolah', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')->default(fn() => Auth::id()),
                Forms\Components\Hidden::make('tanggal')->default(now()),
                Forms\Components\Textarea::make('catatan')
                    ->label('Alasan / Persiapan UKG')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')->label('Nama Guru')->visible(fn() => Auth::user()->hasRole(['superadmin', 'kepala_sekolah'])),
                Tables\Columns\TextColumn::make('tanggal')->label('Tgl Pengajuan')->date('d M Y'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'menunggu',
                        'primary' => 'dijadwalkan',
                        'danger' => 'ditolak',
                        'success' => 'dinilai',
                    ]),
                Tables\Columns\TextColumn::make('jadwal')->label('Jadwal UKG')->dateTime('d M Y, H:i')->placeholder('-'),
                Tables\Columns\TextColumn::make('score')->label('Nilai UKG')->badge()->color('success')->placeholder('Belum Dinilai'),
                Tables\Columns\TextColumn::make('catatan_yayasan')->label('Catatan Yayasan')->limit(30),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Staff\Resources\PengajuanUkgResource\Pages\ListPengajuanUkgs::route('/'),
        ];
    }
}
PHP;

file_put_contents($dir.'/PengajuanSupervisiResource.php', $supervisiContent);
file_put_contents($dir.'/PengajuanUkgResource.php', $ukgContent);

@mkdir($dir.'/PengajuanSupervisiResource/Pages', 0755, true);
@mkdir($dir.'/PengajuanUkgResource/Pages', 0755, true);

$listSup = <<<'PHP'
<?php
namespace App\Filament\Staff\Resources\PengajuanSupervisiResource\Pages;
use App\Filament\Staff\Resources\PengajuanSupervisiResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPengajuanSupervisis extends ListRecords {
    protected static string $resource = PengajuanSupervisiResource::class;
    protected function getActions(): array {
        if (Auth::user()->hasRole('kepala_sekolah')) return [];
        return [ Actions\CreateAction::make()->label('Ajukan Supervisi Baru')->modalHeading('Ajukan Supervisi') ];
    }
}
PHP;
file_put_contents($dir.'/PengajuanSupervisiResource/Pages/ListPengajuanSupervisis.php', $listSup);

$listUkg = <<<'PHP'
<?php
namespace App\Filament\Staff\Resources\PengajuanUkgResource\Pages;
use App\Filament\Staff\Resources\PengajuanUkgResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPengajuanUkgs extends ListRecords {
    protected static string $resource = PengajuanUkgResource::class;
    protected function getActions(): array {
        if (Auth::user()->hasRole('kepala_sekolah')) return [];
        return [ Actions\CreateAction::make()->label('Ajukan UKG Baru')->modalHeading('Ajukan Uji Kompetensi') ];
    }
}
PHP;
file_put_contents($dir.'/PengajuanUkgResource/Pages/ListPengajuanUkgs.php', $listUkg);

echo 'SUCCESS STAFF';
