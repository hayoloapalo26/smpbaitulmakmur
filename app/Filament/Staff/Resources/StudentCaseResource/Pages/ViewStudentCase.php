<?php

namespace App\Filament\Staff\Resources\StudentCaseResource\Pages;

use App\Filament\Staff\Resources\StudentCaseResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentCase extends ViewRecord
{
    protected static string $resource = StudentCaseResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        return [
            Actions\EditAction::make()
                ->visible(fn () => StudentCaseResource::canEdit($this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Infolists\Components\Section::make('📋 Informasi Kasus')
                ->schema([
                    Infolists\Components\TextEntry::make('nomor_kasus')
                        ->label('Nomor Kasus')
                        ->badge()
                        ->color('gray'),
                    Infolists\Components\TextEntry::make('tanggal_kejadian')
                        ->label('Tanggal Kejadian')
                        ->date('d M Y'),
                    Infolists\Components\TextEntry::make('student.name')
                        ->label('Siswa'),
                    Infolists\Components\TextEntry::make('student.classroom.name')
                        ->label('Kelas'),
                    Infolists\Components\TextEntry::make('waliKelas.name')
                        ->label('Wali Kelas'),
                    Infolists\Components\TextEntry::make('kategori_kasus')
                        ->label('Kategori Pelanggaran')
                        ->badge()
                        ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('deskripsi')
                        ->label('Kronologi Kasus')
                        ->columnSpanFull(),
                ])->columns(3),

            // Timeline Eskalasi
            Infolists\Components\Section::make('🔄 Alur Penanganan Kasus')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('timeline')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('level')->label('Level'),
                            Infolists\Components\TextEntry::make('waktu')->label('Waktu'),
                            Infolists\Components\TextEntry::make('catatan')->label('Catatan'),
                        ])
                        ->getStateUsing(function ($record) {
                            $timeline = [];
                            $timeline[] = [
                                'level'   => '👩‍🏫 Wali Kelas — ' . ($record->waliKelas?->name ?? '-'),
                                'waktu'   => $record->created_at->format('d M Y H:i'),
                                'catatan' => $record->catatan_wali_kelas ?: '(Belum ada catatan)',
                            ];
                            if ($record->recommended_to_bk) {
                                $timeline[] = [
                                    'level'   => '🧑‍⚕️ Diteruskan ke BK',
                                    'waktu'   => $record->diteruskan_ke_bk_at?->format('d M Y H:i') ?? '-',
                                    'catatan' => $record->catatan_bk ?: '(Belum ada catatan dari BK)',
                                ];
                                // Jika BK mengembalikan ke wali kelas
                                if ($record->dikembalikan_dari_bk_at) {
                                    $timeline[] = [
                                        'level'   => '↩️ BK Kembalikan ke Wali Kelas',
                                        'waktu'   => $record->dikembalikan_dari_bk_at->format('d M Y H:i'),
                                        'catatan' => $record->catatan_pengembalian_bk ?: '-',
                                    ];
                                }
                            }
                            if ($record->recommended_to_kesiswaan) {
                                $timeline[] = [
                                    'level'   => '🏫 Diteruskan ke Kesiswaan',
                                    'waktu'   => $record->diteruskan_ke_kesiswaan_at?->format('d M Y H:i') ?? '-',
                                    'catatan' => $record->catatan_kesiswaan ?: '(Belum ada catatan dari Kesiswaan)',
                                ];
                            }
                            if ($record->recommended_to_kepsek) {
                                $timeline[] = [
                                    'level'   => '👨‍💼 Dilaporkan ke Kepala Sekolah',
                                    'waktu'   => $record->diteruskan_ke_kepsek_at?->format('d M Y H:i') ?? '-',
                                    'catatan' => $record->catatan_kepsek ?: '(Belum ada catatan)',
                                ];
                            }
                            if ($record->status === 'selesai') {
                                $timeline[] = [
                                    'level'   => '✅ KASUS DISELESAIKAN',
                                    'waktu'   => $record->diselesaikan_at?->format('d M Y H:i') ?? '-',
                                    'catatan' => 'Oleh: ' . ($record->diselesaikanOleh?->name ?? '-'),
                                ];
                            }
                            return $timeline;
                        })
                        ->columnSpanFull(),
                ]),

            Infolists\Components\Section::make('📊 Status')
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn ($state) => $state === 'selesai' ? 'success' : 'warning')
                        ->formatStateUsing(fn ($state) => $state === 'selesai' ? '✅ Selesai' : '🔄 Sedang Proses'),
                    Infolists\Components\TextEntry::make('ditangani_oleh')
                        ->label('Saat ini ditangani oleh')
                        ->formatStateUsing(fn ($state) => match($state) {
                            'bk'             => '🧑‍⚕️ Guru BK',
                            'kesiswaan'      => '🏫 Waka Kesiswaan',
                            'kepala_sekolah' => '👨‍💼 Kepala Sekolah',
                            default          => '👩‍🏫 Wali Kelas',
                        }),
                    Infolists\Components\TextEntry::make('diselesaikan_at')
                        ->label('Diselesaikan pada')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Belum selesai'),
                ])->columns(3),
        ]);
    }
}
