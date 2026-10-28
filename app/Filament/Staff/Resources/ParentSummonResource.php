<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ParentSummonResource\Pages;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\StudentCase;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ParentSummonResource extends Resource
{
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['wali_kelas', 'bk', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = ParentSummon::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $modelLabel = 'Panggilan Orang Tua';
    protected static ?string $pluralModelLabel = 'Panggilan Orang Tua';
    protected static ?string $navigationLabel = 'Panggilan Orang Tua Siswa';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return Auth::user()?->hasRole('wali_kelas') ? 'Fitur Wali Kelas' : 'Kesiswaan & Karakter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['waka_kesiswaan', 'bk', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }

        // Wali Kelas melihat surat panggilan untuk siswa di kelas binaannya
        $waliKelasClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
        $studentIds = Student::whereIn('classroom_id', $waliKelasClassroomIds)->pluck('id')->toArray();

        return $query->where(function ($q) use ($user, $studentIds) {
            $q->whereIn('student_id', $studentIds)
              ->orWhere('teacher_id', $user->id);
        });
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isPrivileged = $user->hasAnyRole(['waka_kesiswaan', 'bk', 'kepala_sekolah', 'superadmin']);

        $studentIds = [];
        if (!$isPrivileged) {
            $waliKelasClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            $studentIds = Student::whereIn('classroom_id', $waliKelasClassroomIds)->pluck('id')->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('✉️ Data Pemanggilan & Kasus Siswa')
                    ->schema([
                        Forms\Components\Hidden::make('teacher_id')
                            ->default($user->id),

                        Forms\Components\Select::make('student_id')
                            ->label('Nama Siswa')
                            ->options(function () use ($isPrivileged, $studentIds) {
                                if ($isPrivileged) return Student::orderBy('name')->pluck('name', 'id');
                                return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live(),

                        Forms\Components\Placeholder::make('student_info_preview')
                            ->label('Informasi Terkoneksi Siswa & Orang Tua')
                            ->visible(fn (Forms\Get $get) => filled($get('student_id')))
                            ->content(function (Forms\Get $get) {
                                $student = Student::with(['classroom', 'studentCases', 'attributeViolations'])->find($get('student_id'));
                                if (!$student) return '-';

                                $activeCasesCount = $student->studentCases->where('status', 'proses')->count();
                                $attrViolationsCount = $student->attributeViolations->count();
                                $ortu = $student->nama_ayah ?: ($student->nama_ibu ?: 'Orang Tua / Wali Murid');
                                $hp = $student->no_telepon_orang_tua ?: 'Belum terdata';
                                $alamat = $student->alamat ?: '-';
                                $kelas = $student->classroom->name ?? '-';

                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3 bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl space-y-2 text-xs'>
                                        <div class='flex flex-wrap items-center justify-between gap-2 border-b border-amber-200/60 dark:border-amber-800/60 pb-2'>
                                            <div>
                                                <span class='font-bold text-gray-900 dark:text-white text-sm'>{$student->name}</span>
                                                <span class='ml-2 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300 font-semibold'>Kelas: {$kelas}</span>
                                                <span class='ml-1 text-gray-500'>NIS: {$student->nis}</span>
                                            </div>
                                            <div class='flex gap-2'>
                                                <span class='px-2 py-0.5 rounded bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 font-bold'>⚠️ Kasus Aktif: {$activeCasesCount}</span>
                                                <span class='px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 font-bold'>👕 Pelanggaran Atribut: {$attrViolationsCount}</span>
                                            </div>
                                        </div>
                                        <div class='grid grid-cols-1 sm:grid-cols-3 gap-2 text-gray-700 dark:text-gray-300'>
                                            <div><strong>Wali Murid:</strong> {$ortu}</div>
                                            <div><strong>No. HP/WA:</strong> <span class='font-mono font-bold text-emerald-600 dark:text-emerald-400'>{$hp}</span></div>
                                            <div><strong>Alamat:</strong> {$alamat}</div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('student_case_id')
                            ->label('Terkait Kasus / Pelanggaran Tertentu (Opsional)')
                            ->options(function (Forms\Get $get) {
                                $studentId = $get('student_id');
                                if (!$studentId) {
                                    return StudentCase::orderByDesc('id')->take(30)->get()
                                        ->mapWithKeys(fn ($c) => [$c->id => ($c->nomor_kasus ?? 'KSS-'.$c->id) . ' - ' . \Illuminate\Support\Str::limit($c->deskripsi, 40)]);
                                }
                                return StudentCase::where('student_id', $studentId)->orderByDesc('id')->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => ($c->nomor_kasus ?? 'KSS-'.$c->id) . ' - ' . \Illuminate\Support\Str::limit($c->deskripsi, 40)]);
                            })
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $case = StudentCase::find($state);
                                    if ($case) {
                                        $set('kasus', "Pemanggilan terkait kasus {$case->nomor_kasus}: {$case->deskripsi}");
                                    }
                                }
                            })
                            ->helperText('Pilih kasus jika pemanggilan ini merupakan tindak lanjut dari kasus siswa di sistem.'),

                        Forms\Components\TextInput::make('nomor_surat')
                            ->label('Nomor Surat Panggilan')
                            ->placeholder('Contoh: 045/SP-BK/SMPI/X/2026')
                            ->default(fn () => 'SP/' . date('Ymd') . '/' . rand(100, 999)),

                        Forms\Components\DatePicker::make('tanggal_pemanggilan')
                            ->label('Tanggal Surat Panggilan')
                            ->default(now())
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_pertemuan')
                            ->label('Tanggal Menghadap / Pertemuan')
                            ->default(now()->addDays(2))
                            ->required(),

                        Forms\Components\TextInput::make('tempat_pertemuan')
                            ->label('Tempat Pertemuan')
                            ->default('Ruang BK / Ruang Wali Kelas')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📝 Uraian Masalah & Hasil Pertemuan')
                    ->schema([
                        Forms\Components\Textarea::make('kasus')
                            ->label('Alasan Pemanggilan Orang Tua / Deskripsi Masalah')
                            ->placeholder('Jelaskan pelanggaran atau permasalahan yang perlu dibicarakan bersama orang tua...')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status Kehadiran Orang Tua')
                            ->options([
                                'menunggu'    => '⏳ Menunggu / Belum Hadir',
                                'hadir'       => '✅ Telah Hadir Memenuhi Panggilan',
                                'tidak_hadir' => '❌ Mangkir / Tidak Hadir',
                            ])
                            ->default('menunggu')
                            ->required(),

                        Forms\Components\TextInput::make('surat_file')
                            ->label('Link Google Drive Berita Acara / Surat Bertandatangan')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen berita acara atau surat untuk menghemat ruang penyimpanan hosting.')
                            ->maxLength(2048),

                        Forms\Components\Textarea::make('hasil_pertemuan')
                            ->label('Hasil Pertemuan / Notula Kesepakatan Bersama Orang Tua')
                            ->placeholder('Catat hasil musyawarah, komitmen orang tua, atau surat pernyataan yang disepakati...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('✉️ Dokumen Panggilan Resmi Orang Tua')
                    ->icon('heroicon-o-envelope-open')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_surat')
                            ->label('Nomor Surat')
                            ->badge()
                            ->color('primary')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Kehadiran')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'menunggu'    => 'warning',
                                'hadir'       => 'success',
                                'tidak_hadir' => 'danger',
                                default       => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'menunggu'    => '⏳ Menunggu / Belum Hadir',
                                'hadir'       => '✅ Telah Hadir Memenuhi Panggilan',
                                'tidak_hadir' => '❌ Mangkir / Tidak Hadir',
                                default       => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('tanggal_pemanggilan')
                            ->label('Tanggal Surat')
                            ->date('d F Y'),
                        Infolists\Components\TextEntry::make('tanggal_pertemuan')
                            ->label('Jadwal Pertemuan / Hadir')
                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->isoFormat('dddd, D MMMM Y') : '-'),
                        Infolists\Components\TextEntry::make('tempat_pertemuan')
                            ->label('Tempat Pertemuan'),
                        Infolists\Components\TextEntry::make('teacher.name')
                            ->label('Guru / Pembuat Surat Panggilan')
                            ->icon('heroicon-o-user-check'),
                    ])->columns(3),

                Infolists\Components\Section::make('👤 Data Siswa & Wali Murid Terkait')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Infolists\Components\TextEntry::make('student.name')
                            ->label('Nama Lengkap Siswa')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('student.classroom.name')
                            ->label('Kelas')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('student.nis')
                            ->label('NIS Siswa'),
                        Infolists\Components\TextEntry::make('student.wali_murid')
                            ->label('Nama Orang Tua / Wali')
                            ->state(fn ($record) => $record->student?->nama_ayah ?: ($record->student?->nama_ibu ?: '-')),
                        Infolists\Components\TextEntry::make('student.no_telepon_orang_tua')
                            ->label('No. WhatsApp Orang Tua')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->color('success')
                            ->placeholder('Belum terdata'),
                        Infolists\Components\TextEntry::make('student.alamat')
                            ->label('Alamat Rumah')
                            ->placeholder('-'),
                    ])->columns(3),

                Infolists\Components\Section::make('⚠️ Keterkaitan Kasus & Alasan Pemanggilan')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Infolists\Components\TextEntry::make('studentCase.nomor_kasus')
                            ->label('Nomor Kasus Siswa')
                            ->badge()
                            ->color('info')
                            ->placeholder('Tidak terhubung ke kasus spesifik'),
                        Infolists\Components\TextEntry::make('studentCase.kategori_kasus')
                            ->label('Kategori Kasus')
                            ->badge()
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),
                        Infolists\Components\TextEntry::make('studentCase.ditangani_oleh')
                            ->label('Ditangani Oleh')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'bk' => 'Guru BK',
                                'kesiswaan' => 'Waka Kesiswaan',
                                'kepala_sekolah' => 'Kepala Sekolah',
                                default => 'Wali Kelas',
                            })
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('kasus')
                            ->label('Uraian Permasalahan / Alasan Pemanggilan')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('📝 Notula Hasil Musyawarah & Kesepakatan Bersama Orang Tua')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('hasil_pertemuan')
                            ->label('Hasil Pertemuan & Komitmen Bersama')
                            ->placeholder('Belum ada notula pertemuan / orang tua belum hadir.')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('surat_file')
                            ->label('Link Google Drive Dokumen / Berita Acara')
                            ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                            ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                            ->badge(fn ($state) => filled($state))
                            ->formatStateUsing(fn ($state) => filled($state) ? 'Buka Dokumen di Google Drive ↗' : 'Tidak ada lampiran dokumen.')
                            ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_surat')
                    ->label('No. Surat')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal_pemanggilan')
                    ->label('Tgl Surat')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->weight('bold')
                    ->description(function (ParentSummon $record) {
                        $parts = [];
                        if ($record->student?->classroom?->name) {
                            $parts[] = 'Kelas ' . $record->student->classroom->name;
                        }
                        $ortu = $record->student?->nama_ayah ?: ($record->student?->nama_ibu ?: null);
                        if ($ortu) {
                            $parts[] = 'Wali: ' . $ortu;
                        }
                        if ($record->student?->no_telepon_orang_tua) {
                            $parts[] = 'WA: ' . $record->student->no_telepon_orang_tua;
                        }
                        return implode(' • ', $parts);
                    }),

                Tables\Columns\TextColumn::make('studentCase.nomor_kasus')
                    ->label('No. Kasus')
                    ->badge()
                    ->color('info')
                    ->placeholder('Non-Kasus')
                    ->tooltip(fn (ParentSummon $record) => $record->studentCase ? "Kasus: " . $record->studentCase->deskripsi : null),

                Tables\Columns\TextColumn::make('kasus')
                    ->label('Alasan Pemanggilan')
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('tanggal_pertemuan')
                    ->label('Jadwal Hadir')
                    ->date('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Kehadiran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu'    => 'warning',
                        'hadir'       => 'success',
                        'tidak_hadir' => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu'    => '⏳ Menunggu',
                        'hadir'       => '✅ Telah Hadir',
                        'tidak_hadir' => '❌ Tidak Hadir',
                        default       => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Pembuat Surat')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('surat_file')
                    ->label('Dokumen')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka Link' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true)
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_pemanggilan', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu'    => 'Menunggu',
                        'hadir'       => 'Telah Hadir',
                        'tidak_hadir' => 'Tidak Hadir',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('catat_hasil')
                    ->label('Catat Hadir / Notula')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->modalHeading(fn (ParentSummon $record) => "Catat Kehadiran Orang Tua: {$record->student->name}")
                    ->modalDescription('Dokumentasikan kehadiran orang tua dan kesepakatan bersama pembinaan siswa.')
                    ->modalSubmitActionLabel('Simpan Hasil Pertemuan')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Kehadiran')
                            ->options([
                                'hadir'       => '✅ Telah Hadir Memenuhi Panggilan',
                                'tidak_hadir' => '❌ Mangkir / Tidak Hadir',
                                'menunggu'    => '⏳ Menunggu / Jadwal Ulang',
                            ])
                            ->default('hadir')
                            ->required(),
                        Forms\Components\Textarea::make('hasil_pertemuan')
                            ->label('Notula Hasil Pertemuan & Komitmen Orang Tua')
                            ->placeholder('Tuliskan poin kesepakatan orang tua, komitmen belajar di rumah, atau kesepakatan sanksi edukatif...')
                            ->rows(4)
                            ->required(),
                        Forms\Components\TextInput::make('surat_file')
                            ->label('Link Google Drive Berita Acara / Surat Bertandatangan (Opsional)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen berita acara atau surat untuk menghemat ruang penyimpanan hosting.')
                            ->maxLength(2048),
                    ])
                    ->fillForm(fn (ParentSummon $record) => [
                        'status' => $record->status === 'menunggu' ? 'hadir' : $record->status,
                        'hasil_pertemuan' => $record->hasil_pertemuan,
                        'surat_file' => $record->surat_file,
                    ])
                    ->action(function (ParentSummon $record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'hasil_pertemuan' => $data['hasil_pertemuan'],
                            'surat_file' => $data['surat_file'] ?? $record->surat_file,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Hasil Pertemuan Disimpan')
                            ->body('Status kehadiran dan notula orang tua berhasil diperbarui.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('kirim_wa_undangan')
                    ->label('WA Undangan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (ParentSummon $record) => !empty($record->student->no_telepon_orang_tua))
                    ->url(function (ParentSummon $record) {
                        $phone = preg_replace('/[^0-9]/', '', $record->student->no_telepon_orang_tua);
                        $tgl = $record->tanggal_pertemuan ? \Carbon\Carbon::parse($record->tanggal_pertemuan)->isoFormat('dddd, D MMMM Y') : '-';
                        $msg = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n"
                             . "Kepada Yth. Bapak/Ibu Wali Murid dari ananda *{$record->student->name}*,\n"
                             . "Berdasarkan surat panggilan resmi No: " . ($record->nomor_surat ?? '-') . ",\n"
                             . "Kami mengundang Bapak/Ibu untuk hadir ke sekolah pada:\n"
                             . "📅 Hari/Tanggal: {$tgl}\n"
                             . "🏛️ Tempat: {$record->tempat_pertemuan}\n"
                             . "📝 Keperluan: {$record->kasus}\n\n"
                             . "Kehadiran Bapak/Ibu sangat penting demi pembinaan ananda ke depan. Terima kasih.\n"
                             . "Hormat kami,\nWali Kelas / Pihak Sekolah.";
                        return "https://wa.me/{$phone}?text=" . urlencode($msg);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListParentSummons::route('/'),
            'create' => Pages\CreateParentSummon::route('/create'),
            'edit'   => Pages\EditParentSummon::route('/{record}/edit'),
        ];
    }
}
