<?php
$exporter = <<<'PHP'
<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('NO'),
            ExportColumn::make('name')->label('NAMA LENGKAP'),
            ExportColumn::make('satminkal')->label('KET SATMINKAL (SATUAN INDUK PANGKAL)'),
            ExportColumn::make('gender')->label('Jenis Kelamin'),
            ExportColumn::make('tanggal_masuk')->label('Awal Masuk'),
            ExportColumn::make('nuptk')->label('NUPTK'),
            ExportColumn::make('jabatan')->label('JABATAN'),
            ExportColumn::make('roles.name')->label('ROLES/MAPEL'),
            ExportColumn::make('alamat')->label('ALAMAT RUMAH / DOMISILI SAAT INI'),
            ExportColumn::make('no_hp')->label('NO HP'),
            ExportColumn::make('tempat_lahir')->label('Tempat Lahir'),
            ExportColumn::make('tanggal_lahir')->label('Tanggal Lahir'),
            ExportColumn::make('jenjang_pendidikan')->label('PENDIDIKAN TERAKHIR'),
            ExportColumn::make('universitas')->label('UNIVERSITAS'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor data PTK telah selesai dan ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';
        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diekspor.';
        }
        return $body;
    }
}
PHP;
file_put_contents('app/Filament/Exports/UserExporter.php', $exporter);

$importer = <<<'PHP'
<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:255'])->guess(['NAMA LENGKAP', 'NAMA', 'name']),
            ImportColumn::make('email')->requiredMapping()->rules(['required', 'email', 'max:255'])->guess(['EMAIL', 'email']),
            ImportColumn::make('satminkal')->rules(['max:255'])->guess(['KET SATMINKAL (SATUAN INDUK PANGKAL)', 'KET SATMINKAL', 'satminkal']),
            ImportColumn::make('gender')->rules(['max:255'])->guess(['Jenis Kelamin', 'gender']),
            ImportColumn::make('tanggal_masuk')->rules(['date'])->guess(['Awal Masuk', 'tanggal_masuk']),
            ImportColumn::make('nuptk')->rules(['max:255'])->guess(['NUPTK', 'nuptk']),
            ImportColumn::make('jabatan')->rules(['max:255'])->guess(['JABATAN', 'jabatan']),
            ImportColumn::make('alamat')->rules(['max:65535'])->guess(['ALAMAT RUMAH / DOMISILI SAAT INI', 'ALAMAT', 'alamat']),
            ImportColumn::make('no_hp')->rules(['max:255'])->guess(['NO HP', 'no_hp']),
            ImportColumn::make('tempat_lahir')->rules(['max:255'])->guess(['Tempat Lahir', 'tempat_lahir']),
            ImportColumn::make('tanggal_lahir')->rules(['date'])->guess(['Tanggal Lahir', 'tanggal_lahir']),
            ImportColumn::make('jenjang_pendidikan')->rules(['max:255'])->guess(['PENDIDIKAN TERAKHIR', 'jenjang_pendidikan']),
            ImportColumn::make('universitas')->rules(['max:255'])->guess(['UNIVERSITAS', 'universitas']),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Try to find existing by email, otherwise create new
        $user = User::firstOrNew(['email' => $this->data['email'] ?? null]);
        if (!$user->exists) {
            $user->password = Hash::make('password'); // Default password for imported users
        }
        return $user;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor data PTK telah selesai dan ' . number_format($import->successful_rows) . ' baris berhasil diimpor.';
        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' Namun, ada ' . number_format($failedRowsCount) . ' baris yang gagal diimpor.';
        }
        return $body;
    }
}
PHP;
file_put_contents('app/Filament/Imports/UserImporter.php', $importer);

$file = 'app/Filament/Staff/Resources/UserResource/Pages/ManageUsers.php';
$content = file_get_contents($file);
$content = str_replace(
    "use Filament\Actions;",
    "use Filament\Actions;\nuse App\Filament\Exports\UserExporter;\nuse App\Filament\Imports\UserImporter;",
    $content
);
$content = str_replace(
    "Actions\CreateAction::make(),",
    "Actions\ImportAction::make()->importer(UserImporter::class)->label('Import Data PTK')->icon('heroicon-o-arrow-down-tray')->color('success'),\n            Actions\ExportAction::make()->exporter(UserExporter::class)->label('Export Data PTK')->icon('heroicon-o-arrow-up-tray')->color('warning'),\n            Actions\CreateAction::make(),",
    $content
);
file_put_contents($file, $content);

$resFile = 'app/Filament/Staff/Resources/UserResource.php';
$resContent = file_get_contents($resFile);
if (strpos($resContent, 'ExportBulkAction') === false) {
    $resContent = str_replace(
        "use Filament\Tables;",
        "use Filament\Tables;\nuse Filament\Tables\Actions\ExportBulkAction;\nuse App\Filament\Exports\UserExporter;",
        $resContent
    );
    $resContent = str_replace(
        "Tables\Actions\DeleteBulkAction::make(),",
        "Tables\Actions\DeleteBulkAction::make(),\n                    ExportBulkAction::make()->exporter(UserExporter::class)->label('Export Terpilih'),",
        $resContent
    );
    file_put_contents($resFile, $resContent);
}

echo "Done\n";
