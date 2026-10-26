<?php

namespace App\Filament\Staff\Resources\DcmResponseResource\Pages;

use App\Filament\Staff\Resources\DcmResponseResource;
use App\Models\DcmResponse;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewDcmResponse extends ViewRecord
{
    protected static string $resource = DcmResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $categories = DcmResponse::getCategories();

        $sections = [
            Section::make('Identitas Siswa')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('student.name')->label('Nama Siswa'),
                        TextEntry::make('student.classroom.name')->label('Kelas')->badge()->color('info'),
                        TextEntry::make('submitted_at')->label('Tanggal Pengisian')->dateTime('d M Y, H:i'),
                    ]),
                ]),
        ];

        // Satu section per kategori
        foreach ($categories as $field => $category) {
            $sections[] = Section::make($category['label'])
                ->collapsed()
                ->schema([
                    TextEntry::make($field)
                        ->label('Masalah yang Dipilih (Ya)')
                        ->state(function (DcmResponse $record) use ($field, $category): string {
                            $selected = $record->$field ?? [];
                            if (empty($selected)) {
                                return '— Tidak ada masalah dipilih (skor: 0)';
                            }
                            $lines = [];
                            foreach ($selected as $no) {
                                if (isset($category['questions'][$no])) {
                                    $lines[] = "No.{$no}: " . $category['questions'][$no];
                                }
                            }
                            return implode("\n", $lines) . "\n\n📊 Skor kategori ini: " . count($selected);
                        })
                        ->html(false)
                        ->columnSpanFull()
                        ->extraAttributes(['class' => 'whitespace-pre-line text-sm']),
                ]);
        }

        // Uraian terbuka
        $sections[] = Section::make('Uraian Terbuka')
            ->schema([
                TextEntry::make('uraian_1')
                    ->label('1. Masalah yang belum tercantum di atas')
                    ->placeholder('Tidak diisi')
                    ->columnSpanFull(),
                TextEntry::make('uraian_2')
                    ->label('2. Masalah yang sedang dihadapi saat ini')
                    ->placeholder('Tidak diisi')
                    ->columnSpanFull(),
                TextEntry::make('uraian_3')
                    ->label('3. Dengan siapa biasanya membicarakan masalah')
                    ->placeholder('Tidak diisi')
                    ->columnSpanFull(),
            ]);

        return $infolist->schema($sections);
    }
}
