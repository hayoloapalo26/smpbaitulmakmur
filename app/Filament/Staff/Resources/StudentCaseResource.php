<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\StudentCaseResource\Pages;
use App\Models\StudentCase;
use App\Models\Student;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentCaseResource extends Resource
{
    protected static ?string $model = StudentCase::class;
    protected static ?string $navigationIcon  = 'heroicon-o-exclamation-triangle';
    protected static ?string $modelLabel      = 'Pelanggaran Siswa';
    protected static ?string $pluralModelLabel = 'Pelanggaran Siswa';
    protected static ?string $navigationLabel = 'Pelanggaran Siswa';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return Auth::user()?->hasRole('wali_kelas') ? 'Fitur Wali Kelas' : 'Kesiswaan & Karakter';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isQuranTeacher()) return false;
        return static::canAccess();
    }

    // ─── Hak Akses ────────────────────────────────────────────────
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isQuranTeacher()) return false;
        return $user->hasAnyRole([
            'wali_kelas', 'waka_kesiswaan', 'bk', 'kepala_sekolah', 'superadmin',
        ]);
    }

    /** Wali kelas dan superadmin bisa CREATE kasus */
    public static function canCreate(): bool
    {
        return Auth::user()->hasAnyRole(['wali_kelas', 'superadmin']);
    }

    /** BK/Kesiswaan/Kepsek tidak bisa create — mereka hanya menerima eskalasi */
    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if ($user->hasRole('superadmin'))     return true;
        if ($user->hasRole('wali_kelas')) {
            // Hanya bisa edit jika masih di level wali kelas
            return $record->wali_kelas_id === $user->id
                && $record->ditangani_oleh === 'wali_kelas'
                && $record->status !== 'selesai';
        }
        if ($user->hasRole('bk'))             return $record->recommended_to_bk && $record->status !== 'selesai';
        if ($user->hasRole('waka_kesiswaan')) return $record->recommended_to_kesiswaan && $record->status !== 'selesai';
        if ($user->hasRole('kepala_sekolah')) return true;
        return false;
    }

    // ─── Query Filter per Role ─────────────────────────────────────
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Auth::user();

        if ($user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return $query; // lihat semua
        }

        if ($user->hasRole('waka_kesiswaan')) {
            // Hanya kasus yang sudah direkomendasikan ke kesiswaan ATAU masih di wali kelas
            return $query->where(function ($q) {
                $q->where('recommended_to_kesiswaan', true)
                  ->orWhere('recommended_to_kepsek', true);
            });
        }

        if ($user->hasRole('bk')) {
            return $query->where('recommended_to_bk', true);
        }

        if ($user->hasRole('wali_kelas')) {
            $classroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id');
            $studentIds   = Student::whereIn('classroom_id', $classroomIds)->pluck('id');
            return $query->whereIn('student_id', $studentIds);
        }

        return $query->whereRaw('1=0'); // tidak ada akses
    }

    // ─── Form ─────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isWaliKelas = $user->hasRole('wali_kelas');

        $classroomIds = $isWaliKelas
            ? Classroom::where('wali_kelas_id', $user->id)->pluck('id')
            : [];

        return $form->schema([

            // ── Informasi Dasar (diisi Wali Kelas) ──────────────────
            Forms\Components\Section::make('📋 Informasi Kasus')
                ->schema([
                    Forms\Components\Placeholder::make('nomor_kasus_display')
                        ->label('Nomor Kasus')
                        ->content(fn ($record) => $record?->nomor_kasus ?? '(Otomatis dibuat saat simpan)')
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('wali_kelas_id')->default($user->id),

                    Forms\Components\Select::make('student_id')
                        ->label('Nama Siswa')
                        ->options(
                            $isWaliKelas
                                ? Student::whereIn('classroom_id', $classroomIds)
                                    ->orderBy('name')->pluck('name', 'id')
                                : Student::orderBy('name')->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->live()
                        ->disabled(fn ($record) => $record !== null && !$isWaliKelas),

                    Forms\Components\Placeholder::make('student_profile_preview')
                        ->label('Profil Siswa & Rekam Jejak Terkait')
                        ->visible(fn (Forms\Get $get) => filled($get('student_id')))
                        ->content(function (Forms\Get $get, $record) {
                            $student = Student::with(['classroom', 'studentCases', 'attributeViolations'])->find($get('student_id'));
                            if (!$student) return '-';

                            $kelas = $student->classroom->name ?? '-';
                            $ortu = $student->nama_ayah ?: ($student->nama_ibu ?: 'Orang Tua / Wali');
                            $hp = $student->no_telepon_orang_tua ?: 'Belum terdata';
                            $skor = $student->character_score ?? 100;
                            $otherCases = $student->studentCases->where('id', '!=', $record?->id);
                            $otherCasesCount = $otherCases->count();
                            $activeCasesCount = $otherCases->where('status', 'proses')->count();
                            $attrCount = $student->attributeViolations->count();

                            $recentAttrs = $student->attributeViolations()->latest('tanggal')->take(3)->get()
                                ->map(fn ($av) => (is_array($av->attribute_type) ? implode(', ', $av->attribute_type) : $av->attribute_type) . ' (' . \Carbon\Carbon::parse($av->tanggal)->format('d/m') . ')')
                                ->implode('; ');

                            return new \Illuminate\Support\HtmlString("
                                <div class='p-3.5 bg-gradient-to-r from-red-50/60 to-amber-50/60 dark:from-gray-800 dark:to-gray-800/80 border border-red-200 dark:border-red-900/40 rounded-xl space-y-2.5 text-xs shadow-sm'>
                                    <div class='flex flex-wrap items-center justify-between gap-2 border-b border-red-200/60 dark:border-gray-700 pb-2'>
                                        <div>
                                            <span class='font-black text-gray-900 dark:text-white text-sm'>{$student->name}</span>
                                            <span class='ml-2 px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 font-bold'>Kelas: {$kelas}</span>
                                            <span class='ml-1 text-gray-500 font-mono'>NIS: {$student->nis}</span>
                                        </div>
                                        <div class='flex items-center gap-2'>
                                            <span class='px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/50 text-amber-900 dark:text-amber-200 font-bold'>
                                                Skor Karakter: {$skor}
                                            </span>
                                            <span class='px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/50 text-rose-900 dark:text-rose-200 font-bold'>
                                                Kasus Lain: {$otherCasesCount} (Aktif: {$activeCasesCount})
                                            </span>
                                        </div>
                                    </div>
                                    <div class='grid grid-cols-1 sm:grid-cols-2 gap-2 text-gray-700 dark:text-gray-300'>
                                        <div><strong>Wali Murid:</strong> {$ortu} &bull; <span class='font-mono text-emerald-600 dark:text-emerald-400 font-bold'>WA: {$hp}</span></div>
                                        <div>
                                            <strong>Pelanggaran Atribut (Piket):</strong> 
                                            " . ($attrCount > 0 ? "<span class='text-purple-700 dark:text-purple-400 font-semibold'>{$attrCount}x ({$recentAttrs})</span>" : "<span class='text-emerald-600'>Nihil / Tertib</span>") . "
                                        </div>
                                    </div>
                                </div>
                            ");
                        })
                        ->columnSpanFull(),

                    Forms\Components\DatePicker::make('tanggal_kejadian')
                        ->label('Tanggal Kejadian')
                        ->default(now())
                        ->required()
                        ->disabled(fn ($record) => $record !== null && !$isWaliKelas),

                    Forms\Components\CheckboxList::make('kategori_kasus')
                        ->label('Kategori / Jenis Pelanggaran')
                        ->options([
                            'Terlambat'                         => '🕐 Terlambat',
                            'Membolos'                          => '🚷 Membolos',
                            'Berkelahi'                         => '👊 Berkelahi',
                            'Bullying / Perundungan'            => '😰 Bullying / Perundungan',
                            'Membawa Barang Terlarang'          => '🚫 Membawa Barang Terlarang',
                            'Tidak Mengerjakan Tugas'           => '📚 Tidak Mengerjakan Tugas',
                            'Pelanggaran Atribut Seragam'       => '👕 Pelanggaran Atribut Seragam',
                            'Merokok'                           => '🚬 Merokok',
                            'Pencurian'                         => '💰 Pencurian',
                            'Pelanggaran Tata Tertib Ibadah'    => '🕌 Pelanggaran Tata Tertib Ibadah',
                            'Lainnya'                           => '📝 Lainnya',
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record !== null && !$isWaliKelas),

                    Forms\Components\Textarea::make('deskripsi')
                        ->label('Kronologi / Uraian Kasus')
                        ->rows(4)
                        ->required()
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record !== null && !$isWaliKelas),
                ])
                ->columns(2),

            // ── Catatan Wali Kelas ───────────────────────────────────
            Forms\Components\Section::make('📝 Tindak Lanjut Wali Kelas')
                ->schema([
                    Forms\Components\Textarea::make('catatan_wali_kelas')
                        ->label('Catatan & Tindakan yang Sudah Dilakukan')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record !== null && !$isWaliKelas),
                ]),

            // ── Catatan BK (hanya jika sudah diteruskan) ─────────────
            Forms\Components\Section::make('🧑‍⚕️ Catatan Guru BK')
                ->schema([
                    Forms\Components\Placeholder::make('bk_diteruskan_at')
                        ->label('Diterima Sejak')
                        ->content(fn ($record) => $record?->diteruskan_ke_bk_at?->format('d M Y H:i') ?? '-'),
                    Forms\Components\Textarea::make('catatan_bk')
                        ->label('Catatan Penanganan BK')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(!$user->hasRole(['bk', 'kepala_sekolah'])),
                ])
                ->visible(fn ($record) => $record?->recommended_to_bk),

            // ── Catatan Kesiswaan (hanya jika sudah diteruskan) ───────
            Forms\Components\Section::make('🏫 Catatan Waka Kesiswaan')
                ->schema([
                    Forms\Components\Placeholder::make('kesiswaan_diteruskan_at')
                        ->label('Diterima Sejak')
                        ->content(fn ($record) => $record?->diteruskan_ke_kesiswaan_at?->format('d M Y H:i') ?? '-'),
                    Forms\Components\Textarea::make('catatan_kesiswaan')
                        ->label('Catatan Penanganan Kesiswaan')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(!$user->hasRole(['waka_kesiswaan', 'kepala_sekolah'])),
                ])
                ->visible(fn ($record) => $record?->recommended_to_kesiswaan),

            // ── Catatan Kepala Sekolah (hanya jika diteruskan) ────────
            Forms\Components\Section::make('👨‍💼 Catatan Kepala Sekolah')
                ->schema([
                    Forms\Components\Placeholder::make('kepsek_diteruskan_at')
                        ->label('Diterima Sejak')
                        ->content(fn ($record) => $record?->diteruskan_ke_kepsek_at?->format('d M Y H:i') ?? '-'),
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Keputusan / Catatan Kepala Sekolah')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(!$user->hasRole('kepala_sekolah')),
                ])
                ->visible(fn ($record) => $record?->recommended_to_kepsek),

            // ── Riwayat Pemanggilan Orang Tua Terkait Kasus Ini ────────
            Forms\Components\Section::make('📨 Riwayat Pemanggilan Orang Tua Terkait Kasus Ini')
                ->icon('heroicon-o-envelope-open')
                ->schema([
                    Forms\Components\ViewField::make('parent_summons_form_view')
                        ->view('filament.staff.components.case-parent-summons-view')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record?->parentSummons()->exists()),

        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('📋 Status & Nomor Kasus')
                    ->icon('heroicon-o-shield-exclamation')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_kasus')
                            ->label('Nomor Kasus')
                            ->badge()
                            ->color('primary')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Kasus')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'selesai' => 'success',
                                'proses'  => 'warning',
                                default   => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'selesai' => '✅ Selesai / Tuntas',
                                'proses'  => '🔄 Sedang Dalam Penanganan',
                                default   => ucfirst($state),
                            }),
                        Infolists\Components\TextEntry::make('ditangani_oleh')
                            ->label('Tingkat Penanganan Saat Ini')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'kepala_sekolah' => 'danger',
                                'kesiswaan'      => 'warning',
                                'bk'             => 'info',
                                default          => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match($state) {
                                'bk'             => '🧑‍⚕️ Guru BK',
                                'kesiswaan'      => '🏫 Waka Kesiswaan',
                                'kepala_sekolah' => '👨‍💼 Kepala Sekolah',
                                default          => '👩‍🏫 Wali Kelas',
                            }),
                        Infolists\Components\TextEntry::make('tanggal_kejadian')
                            ->label('Tanggal Kejadian')
                            ->date('d F Y'),
                        Infolists\Components\TextEntry::make('waliKelas.name')
                            ->label('Wali Kelas Pelapor')
                            ->placeholder('Wali Kelas'),
                        Infolists\Components\TextEntry::make('diselesaikanOleh.name')
                            ->label('Diselesaikan Oleh')
                            ->placeholder('Masih dalam proses')
                            ->visible(fn ($record) => $record->status === 'selesai'),
                    ])->columns(3),

                Infolists\Components\Section::make('👤 Data Siswa & Wali Murid Terkait')
                    ->icon('heroicon-o-user')
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
                        Infolists\Components\TextEntry::make('student.character_score')
                            ->label('Skor Karakter Saat Ini')
                            ->badge()
                            ->color('warning')
                            ->state(fn ($record) => $record->student?->character_score ?? 100),
                        Infolists\Components\TextEntry::make('student.wali')
                            ->label('Wali Murid (Ayah / Ibu)')
                            ->state(fn ($record) => $record->student?->nama_ayah ?: ($record->student?->nama_ibu ?: '-')),
                        Infolists\Components\TextEntry::make('student.no_telepon_orang_tua')
                            ->label('Kontak WhatsApp Orang Tua')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->color('success')
                            ->placeholder('Belum terdata'),
                    ])->columns(3),

                Infolists\Components\Section::make('⚠️ Uraian & Kategori Pelanggaran')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Infolists\Components\TextEntry::make('kategori_kasus')
                            ->label('Kategori Pelanggaran')
                            ->badge()
                            ->color('danger')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('deskripsi')
                            ->label('Kronologi / Uraian Masalah Kasus')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('📨 Riwayat Pemanggilan Orang Tua Terkait Kasus Ini')
                    ->icon('heroicon-o-envelope-open')
                    ->schema([
                        Infolists\Components\ViewEntry::make('parent_summons_list')
                            ->label('')
                            ->view('filament.staff.components.case-parent-summons-view')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->parentSummons()->exists()),

                Infolists\Components\Section::make('📝 Alur Penanganan & Catatan Bertingkat')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('catatan_wali_kelas')
                            ->label('1. Catatan & Tindakan Wali Kelas')
                            ->placeholder('Belum ada catatan wali kelas.')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('catatan_bk')
                            ->label('2. Catatan Penanganan Guru BK')
                            ->placeholder('Belum ditangani BK.')
                            ->visible(fn ($record) => (bool)$record->recommended_to_bk)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('catatan_kesiswaan')
                            ->label('3. Catatan Penanganan Waka Kesiswaan')
                            ->placeholder('Belum ditangani Kesiswaan.')
                            ->visible(fn ($record) => (bool)$record->recommended_to_kesiswaan)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('catatan_kepsek')
                            ->label('4. Keputusan & Arahan Kepala Sekolah')
                            ->placeholder('Belum dilaporkan ke Kepala Sekolah.')
                            ->visible(fn ($record) => (bool)$record->recommended_to_kepsek)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // ─── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_kasus')
                    ->label('No. Kasus')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('tanggal_kejadian')
                    ->label('Tgl Kejadian')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->weight('bold')
                    ->description(function (StudentCase $record) {
                        $parts = [];
                        if ($record->student?->classroom?->name) {
                            $parts[] = 'Kelas ' . $record->student->classroom->name;
                        }
                        $ortu = $record->student?->nama_ayah ?: ($record->student?->nama_ibu ?: null);
                        if ($ortu) {
                            $parts[] = 'Wali: ' . $ortu;
                        }
                        return implode(' • ', $parts);
                    }),

                Tables\Columns\TextColumn::make('kategori_kasus')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_map(fn($k) => preg_replace('/^[^\s]+\s/', '', $k), $state)) : $state)
                    ->wrap(),

                Tables\Columns\TextColumn::make('ditangani_oleh')
                    ->label('Level Penanganan')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'bk'             => '🧑‍⚕️ BK',
                        'kesiswaan'      => '🏫 Kesiswaan',
                        'kepala_sekolah' => '👨‍💼 Kepala Sekolah',
                        default          => '👩‍🏫 Wali Kelas',
                    })
                    ->color(fn ($state) => match($state) {
                        'kepala_sekolah' => 'danger',
                        'kesiswaan'      => 'warning',
                        'bk'             => 'info',
                        default          => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'selesai' => 'success',
                        'proses'  => 'warning',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'selesai' => '✅ Selesai',
                        'proses'  => '🔄 Proses',
                        default   => $state,
                    }),

                Tables\Columns\TextColumn::make('parent_summons_count')
                    ->counts('parentSummons')
                    ->label('Panggilan Ortu')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "📨 {$state}x" : '-'),

                Tables\Columns\TextColumn::make('waliKelas.name')
                    ->label('Wali Kelas')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['proses' => 'Sedang Proses', 'selesai' => 'Selesai']),
                Tables\Filters\SelectFilter::make('ditangani_oleh')
                    ->label('Level Penanganan')
                    ->options([
                        'wali_kelas'     => 'Wali Kelas',
                        'bk'             => 'BK',
                        'kesiswaan'      => 'Kesiswaan',
                        'kepala_sekolah' => 'Kepala Sekolah',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->url(fn (StudentCase $record): string => route('academic.student-case.pdf', $record))
                    ->openUrlInNewTab(),

                // ── View detail ──────────────────────────────────────
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),

                // ── Edit (jika berhak) ────────────────────────────────
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),

                // ── Teruskan ke BK (oleh Wali Kelas) ─────────────────
                Action::make('teruskan_bk')
                    ->label('Teruskan ke BK')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->visible(fn ($record) =>
                        ($user->hasRole('wali_kelas') || $user->hasRole('superadmin'))
                        && !$record->recommended_to_bk
                        && $record->status === 'proses'
                        && ($record->wali_kelas_id === $user->id || $user->hasRole('superadmin'))
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Teruskan Kasus ke Guru BK?')
                    ->modalDescription('Kasus ini akan dikirimkan ke Guru BK untuk ditangani lebih lanjut. Wali kelas tetap bisa memantau perkembangan.')
                    ->modalSubmitActionLabel('Ya, Teruskan ke BK')
                    ->form([
                        Forms\Components\Textarea::make('catatan_wali_kelas')
                            ->label('Catatan Akhir Wali Kelas (sebelum diteruskan)')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'recommended_to_bk'    => true,
                            'ditangani_oleh'       => 'bk',
                            'diteruskan_ke_bk_at'  => now(),
                            'catatan_wali_kelas'   => $data['catatan_wali_kelas'],
                        ]);
                        Notification::make()->title('✅ Kasus diteruskan ke BK')->success()->send();
                    }),

                // ── Kembalikan ke Wali Kelas (oleh BK jika tidak mampu) ─
                Action::make('kembalikan_ke_wali_kelas')
                    ->label('Kembalikan ke Wali Kelas')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn ($record) =>
                        $user->hasRole('bk')
                        && $record->recommended_to_bk
                        && $record->ditangani_oleh === 'bk'
                        && !$record->dikembalikan_dari_bk_at  // belum pernah dikembalikan
                        && $record->status === 'proses'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan Kasus ke Wali Kelas?')
                    ->modalDescription('BK akan mengembalikan kasus ini ke wali kelas. Wali kelas dapat merekomendasikannya ke Waka Kesiswaan jika diperlukan.')
                    ->modalSubmitActionLabel('Ya, Kembalikan ke Wali Kelas')
                    ->form([
                        Forms\Components\Textarea::make('catatan_pengembalian_bk')
                            ->label('Alasan Pengembalian / Rekomendasi untuk Wali Kelas')
                            ->helperText('Jelaskan mengapa dikembalikan dan apa yang sebaiknya dilakukan selanjutnya')
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'ditangani_oleh'          => 'wali_kelas',
                            'dikembalikan_dari_bk_at' => now(),
                            'catatan_pengembalian_bk' => $data['catatan_pengembalian_bk'],
                            // catatan_bk juga diupdate agar terdokumentasi
                            'catatan_bk'              => ($record->catatan_bk ? $record->catatan_bk . "\n\n" : '')
                                                        . '[DIKEMBALIKAN KE WALI KELAS] ' . $data['catatan_pengembalian_bk'],
                        ]);
                        Notification::make()
                            ->title('↩️ Kasus dikembalikan ke Wali Kelas')
                            ->body('Wali kelas dapat merekomendasikan ke Waka Kesiswaan jika diperlukan.')
                            ->warning()
                            ->send();
                    }),

                // ── Teruskan ke Kesiswaan (oleh Wali Kelas ATAU BK) ───
                Action::make('teruskan_kesiswaan')
                    ->label('Teruskan ke Kesiswaan')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        $record->status === 'proses'
                        && !$record->recommended_to_kesiswaan
                        && (
                            // Wali Kelas / Superadmin bisa teruskan ke Kesiswaan:
                            (($user->hasRole('wali_kelas') || $user->hasRole('superadmin'))
                                && ($record->wali_kelas_id === $user->id || $user->hasRole('superadmin'))
                                && ($record->ditangani_oleh === 'wali_kelas')
                            )
                            ||
                            // BK juga tetap bisa teruskan ke Kesiswaan
                            ($user->hasRole('bk') && $record->recommended_to_bk)
                        )
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Teruskan Kasus ke Waka Kesiswaan?')
                    ->modalDescription('Kasus ini akan dikirimkan ke Waka Kesiswaan untuk penanganan lebih lanjut.')
                    ->modalSubmitActionLabel('Ya, Teruskan ke Kesiswaan')
                    ->form([
                        Forms\Components\Textarea::make('catatan_eskalasi')
                            ->label('Catatan / Alasan Diteruskan ke Kesiswaan')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) use ($user) {
                        $updates = [
                            'recommended_to_kesiswaan'   => true,
                            'ditangani_oleh'             => 'kesiswaan',
                            'diteruskan_ke_kesiswaan_at' => now(),
                        ];
                        if ($user->hasRole('bk')) {
                            $updates['catatan_bk'] = ($record->catatan_bk ? $record->catatan_bk . "\n\n" : '')
                                                    . '[DITERUSKAN KE KESISWAAN] ' . $data['catatan_eskalasi'];
                        } else {
                            $updates['catatan_wali_kelas'] = ($record->catatan_wali_kelas ? $record->catatan_wali_kelas . "\n\n" : '')
                                                            . '[DITERUSKAN KE KESISWAAN] ' . $data['catatan_eskalasi'];
                        }
                        $record->update($updates);
                        Notification::make()->title('✅ Kasus diteruskan ke Kesiswaan')->success()->send();
                    }),

                // ── Catat Pemanggilan Orang Tua untuk Kasus Ini ───────
                Action::make('panggil_ortu')
                    ->label('Panggil Ortu')
                    ->icon('heroicon-o-envelope-open')
                    ->color('danger')
                    ->modalHeading(fn ($record) => "Catat Pemanggilan Orang Tua: {$record->student->name}")
                    ->modalDescription(fn ($record) => "Buat surat panggilan orang tua terkait kasus {$record->nomor_kasus}.")
                    ->modalSubmitActionLabel('Simpan Panggilan Orang Tua')
                    ->form([
                        Forms\Components\TextInput::make('nomor_surat')
                            ->label('Nomor Surat (Opsional)')
                            ->default(fn ($record) => 'SP/' . ($record->nomor_kasus ?? now()->format('Ymd'))),
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
                            ->default('Ruang BK / Wali Kelas')
                            ->required(),
                        Forms\Components\Textarea::make('kasus')
                            ->label('Uraian Kasus / Alasan Pemanggilan')
                            ->default(fn ($record) => "Kasus {$record->nomor_kasus}: " . $record->deskripsi)
                            ->rows(3)
                            ->required(),
                        Forms\Components\TextInput::make('surat_file')
                            ->label('Link Google Drive Surat Bertandatangan (Opsional)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen jika surat fisik sudah dicetak/ditandatangani.')
                            ->maxLength(2048),
                    ])
                    ->action(function ($record, array $data) use ($user) {
                        \App\Models\ParentSummon::create([
                            'student_id'          => $record->student_id,
                            'student_case_id'     => $record->id,
                            'teacher_id'          => $user->id,
                            'nomor_surat'         => $data['nomor_surat'] ?? null,
                            'tanggal_pemanggilan' => $data['tanggal_pemanggilan'],
                            'tanggal_pertemuan'   => $data['tanggal_pertemuan'] ?? null,
                            'tempat_pertemuan'    => $data['tempat_pertemuan'] ?? 'Ruang BK / Wali Kelas',
                            'kasus'               => $data['kasus'],
                            'status'              => 'menunggu',
                            'surat_file'          => $data['surat_file'] ?? null,
                            'institution_id'      => $record->student->institution_id ?? $user->institution_id,
                        ]);

                        Notification::make()
                            ->title('📨 Pemanggilan Orang Tua Berhasil Dicatat')
                            ->body("Surat panggilan orang tua untuk {$record->student->name} telah dibuat.")
                            ->success()
                            ->send();
                    }),

                // ── Teruskan ke Kepala Sekolah (oleh Kesiswaan) ───────
                Action::make('teruskan_kepsek')
                    ->label('Teruskan ke Kepala Sekolah')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('danger')
                    ->visible(fn ($record) =>
                        $user->hasRole('waka_kesiswaan')
                        && $record->recommended_to_kesiswaan
                        && !$record->recommended_to_kepsek
                        && $record->status === 'proses'
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Eskalasikan ke Kepala Sekolah?')
                    ->modalDescription('Kasus ini memerlukan keputusan langsung dari Kepala Sekolah.')
                    ->modalSubmitActionLabel('Ya, Laporkan ke Kepala Sekolah')
                    ->form([
                        Forms\Components\Textarea::make('catatan_kesiswaan')
                            ->label('Catatan Kesiswaan (sebelum dilaporkan)')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'recommended_to_kepsek'      => true,
                            'ditangani_oleh'             => 'kepala_sekolah',
                            'diteruskan_ke_kepsek_at'    => now(),
                            'catatan_kesiswaan'          => $data['catatan_kesiswaan'],
                        ]);
                        Notification::make()->title('✅ Kasus dilaporkan ke Kepala Sekolah')->success()->send();
                    }),

                // ── Tandai Selesai ────────────────────────────────────
                Action::make('tandai_selesai')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) =>
                        $record->status === 'proses'
                        && (
                            ($user->hasRole('wali_kelas') && $record->wali_kelas_id === $user->id && $record->ditangani_oleh === 'wali_kelas')
                            || ($user->hasRole('bk') && $record->ditangani_oleh === 'bk')
                            || ($user->hasRole('waka_kesiswaan') && in_array($record->ditangani_oleh, ['kesiswaan']))
                            || $user->hasRole('kepala_sekolah')
                        )
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Kasus Selesai?')
                    ->modalDescription('Pastikan semua tindak lanjut sudah dicatat sebelum menutup kasus ini.')
                    ->modalSubmitActionLabel('Selesaikan Kasus')
                    ->form([
                        Forms\Components\Textarea::make('catatan_penutupan')
                            ->label('Catatan Penutupan / Kesimpulan')
                            ->rows(3)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) use ($user) {
                        $catatanField = match(true) {
                            $user->hasRole('kepala_sekolah')  => 'catatan_kepsek',
                            $user->hasRole('waka_kesiswaan') => 'catatan_kesiswaan',
                            $user->hasRole('bk')             => 'catatan_bk',
                            default                          => 'catatan_wali_kelas',
                        };

                        $record->update([
                            'status'            => 'selesai',
                            'diselesaikan_at'   => now(),
                            'diselesaikan_oleh' => $user->id,
                            $catatanField       => ($record->$catatanField ? $record->$catatanField . "\n\n[PENUTUPAN] " : '[PENUTUPAN] ')
                                                  . $data['catatan_penutupan'],
                        ]);
                        Notification::make()->title('✅ Kasus berhasil ditutup')->success()->send();
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
            'index'  => Pages\ManageStudentCases::route('/'),
            'create' => Pages\CreateStudentCase::route('/create'),
            'view'   => Pages\ViewStudentCase::route('/{record}'),
            'edit'   => Pages\EditStudentCase::route('/{record}/edit'),
        ];
    }
}
