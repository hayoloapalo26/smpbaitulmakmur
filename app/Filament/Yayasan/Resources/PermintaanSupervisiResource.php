<?php

namespace App\Filament\Yayasan\Resources;

use App\Models\Institution;
use App\Models\TeacherSupervision;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermintaanSupervisiResource extends Resource
{
    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Supervisi Guru';
    protected static ?string $modelLabel = 'Supervisi Guru';
    protected static ?string $pluralModelLabel = 'Supervisi Guru (Penjamin Mutu)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->orderBy('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']);
    }

    protected static function buildIndicatorField(int $num, string $code, string $title, string $desc): Forms\Components\Component
    {
        return Forms\Components\Fieldset::make("{$code} • {$title}")
            ->schema([
                Forms\Components\Placeholder::make("desc_{$num}")
                    ->label('')
                    ->content($desc)
                    ->columnSpanFull(),

                Forms\Components\Select::make("komponen_penilaian.i{$num}.score")
                    ->label('Skor Penilaian')
                    ->options([
                        4 => '4 - Sangat Baik (Amat Baik / Sangat Selaras)',
                        3 => '3 - Baik (Selaras / Terukur / Konsisten)',
                        2 => '2 - Cukup (Cukup Selaras / Parsial)',
                        1 => '1 - Kurang (Belum Terukur / Perlu Bimbingan)',
                    ])
                    ->placeholder('-- Pilih Skor (1-4) --')
                    ->live()
                    ->required()
                    ->columnSpan(1),

                Forms\Components\TextInput::make("komponen_penilaian.i{$num}.catatan")
                    ->label('Catatan Pengamatan Kualitatif (Opsional)')
                    ->placeholder('Catatan bukti pengamatan spesifik di kelas...')
                    ->columnSpan(2),
            ])
            ->columns(3);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Data Identitas Pembelajaran
                Forms\Components\Section::make('1. Data Identitas Pembelajaran')
                    ->description('Informasi umum pelaksanaan supervisi pembelajaran guru')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Unit Sekolah / Lembaga')
                            ->options(fn () => Institution::where('id', '!=', 1)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('teacher_id', null))
                            ->required(),

                        Forms\Components\Select::make('unit_sekolah_program')
                            ->label('Unit Sekolah / Program')
                            ->options(TeacherSupervision::getUnitOptions())
                            ->default('Reguler')
                            ->required(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Nama Guru yang Disupervisi')
                            ->options(function (Forms\Get $get) {
                                $instId = $get('institution_id');
                                $query = User::withoutGlobalScopes()
                                    ->whereHas('roles', fn ($q) => $q->whereIn('name', array_merge([
                                        'guru_kelas', 'guru_mapel', 'wali_kelas',
                                        'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
                                        'sarpras', 'humas',
                                    ], \App\Models\User::QURAN_TEACHER_ROLES)));
                                if ($instId) {
                                    $query->where('institution_id', $instId);
                                }
                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('mata_pelajaran')
                            ->label('Mata Pelajaran / Program')
                            ->placeholder('Contoh: Matematika, Bahasa Inggris, PAI, dll.')
                            ->required(),

                        Forms\Components\TextInput::make('kelas_semester')
                            ->label('Kelas / Semester')
                            ->placeholder('Contoh: Kelas VII / Semester 1')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Hari / Tanggal Supervisi')
                            ->default(now())
                            ->required(),

                        Forms\Components\DateTimePicker::make('jadwal')
                            ->label('Jadwal Pelaksanaan Observasi')
                            ->default(now()->setHour(8)->setMinute(0))
                            ->nullable(),

                        Forms\Components\Select::make('supervisor_id')
                            ->label('Supervisor (Akun Penguji)')
                            ->options(fn () => User::withoutGlobalScopes()
                                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['tim_pengembang_penjamin_mutu_yayasan', 'ketua_yayasan', 'superadmin']))
                                ->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('supervisor_name')
                            ->label('Nama Lengkap Supervisor')
                            ->placeholder('Default: Nama akun yang menilai')
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status Supervisi')
                            ->options([
                                'dinilai' => 'Selesai Dinilai',
                                'dijadwalkan' => 'Dijadwalkan',
                                'menunggu' => 'Menunggu Jadwal',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('dinilai')
                            ->required(),
                    ]),

                // Section 2: Live Otomasi Skor & Predikat Dashboard
                Forms\Components\Placeholder::make('live_score_dashboard')
                    ->label('')
                    ->columnSpanFull()
                    ->content(function (Forms\Get $get) {
                        $komponen = $get('komponen_penilaian') ?: [];
                        $calc = TeacherSupervision::calculateScoresFromComponents($komponen);
                        $total = $calc['total_skor'];
                        $na = $calc['nilai_akhir'];
                        $predikat = $calc['predikat'];
                        $perencanaan = $calc['skor_perencanaan'];
                        $pelaksanaan = $calc['skor_pelaksanaan'];
                        $evaluasi = $calc['skor_evaluasi'];
                        $badgeColor = TeacherSupervision::getPredicateBadgeColor($na);

                        $badgeClass = match($badgeColor) {
                            'success' => 'background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;',
                            'info' => 'background-color: #dbeafe; color: #1e40af; border: 1px solid #93c5fd;',
                            'warning' => 'background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;',
                            default => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;',
                        };

                        return new \Illuminate\Support\HtmlString("
                            <div style='background-color: #ffffff; border: 1.5px solid #fed7aa; border-radius: 12px; padding: 16px; margin: 4px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>
                                <div style='display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px; margin-bottom: 12px;'>
                                    <div>
                                        <span style='font-size: 11px; font-weight: 800; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px;'>Otomasi Penghitungan Nilai</span>
                                        <h4 style='font-size: 16px; font-weight: 800; color: #111827; margin: 2px 0 0 0;'>Hasil Skor & Predikat Supervisi (Live Calculation)</h4>
                                    </div>
                                    <div>
                                        <span style='font-size: 12px; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; {$badgeClass}'>
                                            {$predikat}
                                        </span>
                                    </div>
                                </div>

                                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; text-align: center;'>
                                    <div style='background-color: #fff7ed; padding: 10px; border-radius: 8px; border: 1px solid #ffedd5;'>
                                        <div style='font-size: 11px; color: #6b7280; font-weight: 600;'>Total Skor (Max 88)</div>
                                        <div style='font-size: 22px; font-weight: 900; color: #c2410c;'>{$total} <span style='font-size: 12px; font-weight: normal; color: #9ca3af;'>/ 88</span></div>
                                    </div>
                                    <div style='background-color: #ecfdf5; padding: 10px; border-radius: 8px; border: 1px solid #d1fae5;'>
                                        <div style='font-size: 11px; color: #6b7280; font-weight: 600;'>Nilai Akhir (NA)</div>
                                        <div style='font-size: 22px; font-weight: 900; color: #059669;'>{$na} <span style='font-size: 12px; font-weight: normal; color: #9ca3af;'>/ 100</span></div>
                                    </div>
                                    <div style='background-color: #eff6ff; padding: 10px; border-radius: 8px; border: 1px solid #dbeafe;'>
                                        <div style='font-size: 11px; color: #6b7280; font-weight: 600;'>A. Perencanaan</div>
                                        <div style='font-size: 18px; font-weight: 800; color: #1d4ed8;'>{$perencanaan} <span style='font-size: 11px; font-weight: normal; color: #9ca3af;'>/ 20</span></div>
                                    </div>
                                    <div style='background-color: #eef2ff; padding: 10px; border-radius: 8px; border: 1px solid #e0e7ff;'>
                                        <div style='font-size: 11px; color: #6b7280; font-weight: 600;'>B. Pelaksanaan</div>
                                        <div style='font-size: 18px; font-weight: 800; color: #4338ca;'>{$pelaksanaan} <span style='font-size: 11px; font-weight: normal; color: #9ca3af;'>/ 60</span></div>
                                    </div>
                                    <div style='background-color: #faf5ff; padding: 10px; border-radius: 8px; border: 1px solid #f3e8ff;'>
                                        <div style='font-size: 11px; color: #6b7280; font-weight: 600;'>B.6 Evaluasi</div>
                                        <div style='font-size: 18px; font-weight: 800; color: #7e22ce;'>{$evaluasi} <span style='font-size: 11px; font-weight: normal; color: #9ca3af;'>/ 8</span></div>
                                    </div>
                                </div>
                            </div>
                        ");
                    }),

                // Section 3: Komponen Penilaian (22 Indikator)
                Forms\Components\Tabs::make('Komponen Penilaian')
                    ->columnSpanFull()
                    ->tabs([
                        // TAB A: Perencanaan & Desain Pembelajaran (Indikator 1-5)
                        Forms\Components\Tabs\Tab::make('A. Perencanaan (RPP / Modul)')
                            ->icon('heroicon-o-document-text')
                            ->badge(fn (Forms\Get $get) => TeacherSupervision::calculateScoresFromComponents($get('komponen_penilaian'))['skor_perencanaan'] . '/20')
                            ->schema([
                                Forms\Components\Section::make('A. Perencanaan & Desain Pembelajaran (Modul Ajar / RPP)')
                                    ->description('Fokus pada keselarasan CP-TP-KKTP, diferensiasi, deep learning, karakter, dan integrasi bahasa & Al-Qur\'an')
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 1,
                                            code: 'A.1',
                                            title: 'Analisis CP, TP, & KKTP',
                                            desc: 'Keselarasan dan keterukuran TP/KKTP dengan Capaian Pembelajaran.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 2,
                                            code: 'A.2',
                                            title: 'Desain Pembelajaran Berdiferensiasi',
                                            desc: 'Variasi konten, proses, dan produk sesuai kesiapan, minat, dan profil murid.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 3,
                                            code: 'A.3',
                                            title: 'Desain Pembelajaran Deep Learning',
                                            desc: 'Pemantik bernalar kritis, alur runtut (Memahami > Mengaplikasi > Merefleksi), serta media/sumber digital interaktif.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 4,
                                            code: 'A.4',
                                            title: 'Integrasi Bahan Ajar & Karakter',
                                            desc: 'Transformasi nilai moral, etika, dan pembiasaan karakter.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 5,
                                            code: 'A.5',
                                            title: 'Perencanaan Bahasa & Al-Qur\'an',
                                            desc: 'Penggunaan Classroom Language (Bahasa Inggris) dan pembiasaan Tahsin & Tahfidz (BMQ) terstruktur.'
                                        ),
                                        Forms\Components\Textarea::make('rekomendasi_perencanaan')
                                            ->label('Rekomendasi Khusus Komponen Perencanaan')
                                            ->placeholder('Catatan atau rekomendasi perbaikan untuk modul ajar / RPP guru...')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB B: Pelaksanaan Pembelajaran (Indikator 6-20)
                        Forms\Components\Tabs\Tab::make('B. Pelaksanaan Pembelajaran')
                            ->icon('heroicon-o-academic-cap')
                            ->badge(fn (Forms\Get $get) => TeacherSupervision::calculateScoresFromComponents($get('komponen_penilaian'))['skor_pelaksanaan'] . '/60')
                            ->schema([
                                // B.1 Deep Learning
                                Forms\Components\Section::make('B.1 Implementasi Prinsip Deep Learning')
                                    ->description('Mindful, Meaningful, Joyful & Collaborative dengan media digital interaktif')
                                    ->collapsible()
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 6,
                                            code: 'Indikator 6',
                                            title: 'Nalar Kritis & Pemecahan Masalah (Mindful)',
                                            desc: 'Pertanyaan pemantik dan keterlibatan aktif peserta didik.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 7,
                                            code: 'Indikator 7',
                                            title: 'Konteks Kehidupan Nyata (Meaningful)',
                                            desc: 'Keterkaitan konsep materi dengan kehidupan nyata.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 8,
                                            code: 'Indikator 8',
                                            title: 'Tahapan Belajar Runtut',
                                            desc: 'Alur runtut Memahami, Mengaplikasi, hingga Merefleksi.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 9,
                                            code: 'Indikator 9',
                                            title: 'Kolaborasi & Diskusi Aktif (Joyful & Collaborative)',
                                            desc: 'Kerja sama dan komunikasi aktif antar peserta didik.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 10,
                                            code: 'Indikator 10',
                                            title: 'Media & Sumber Digital Interaktif',
                                            desc: 'Pemanfaatan media/sumber belajar digital secara kreatif dan interaktif.'
                                        ),
                                    ]),

                                // B.2 Berdiferensiasi
                                Forms\Components\Section::make('B.2 Pembelajaran Berdiferensiasi')
                                    ->description('Diferensiasi konten, proses, produk, dan lingkungan belajar aman & inklusif')
                                    ->collapsible()
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 11,
                                            code: 'Indikator 11',
                                            title: 'Diferensiasi Konten, Proses, & Produk',
                                            desc: 'Bahan ajar bertingkat, proses berjenjang (scaffolding), dan fleksibilitas tagihan produk.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 12,
                                            code: 'Indikator 12',
                                            title: 'Lingkungan Belajar',
                                            desc: 'Suasana kelas aman, fleksibel, inklusif, dan mendukung kolaborasi.'
                                        ),
                                    ]),

                                // B.3 Karakter LPI Baitul Makmur
                                Forms\Components\Section::make('B.3 Internalisasi Karakter Khas LPI Baitul Makmur')
                                    ->description('Karakter personal, pribadi, sosial, dan religius islami')
                                    ->collapsible()
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 13,
                                            code: 'Indikator 13',
                                            title: 'Karakter Personal',
                                            desc: 'Disiplin, ketepatan waktu, atribut belajar, ketuntasan tugas, dan tanggung jawab.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 14,
                                            code: 'Indikator 14',
                                            title: 'Karakter Pribadi',
                                            desc: 'Kejujuran, kesederhanaan, kesabaran, semangat belajar, karya nyata.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 15,
                                            code: 'Indikator 15',
                                            title: 'Karakter Sosial',
                                            desc: 'Toleransi, empati, peduli, sopan santun, demokratis, kooperatif.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 16,
                                            code: 'Indikator 16',
                                            title: 'Karakter Religius',
                                            desc: 'Pembiasaan sholat berjamaah khusyuk, salam/salim, adab islami, anti-bullying.'
                                        ),
                                    ]),

                                // B.4 Bilingual / ESL
                                Forms\Components\Section::make('B.4 Pembiasaan Percakapan Berbahasa Inggris (Bilingual / ESL)')
                                    ->description('Penggunaan Classroom Language dan eksplorasi percaya diri berbahasa Inggris')
                                    ->collapsible()
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 17,
                                            code: 'Indikator 17',
                                            title: 'Penggunaan Classroom Language',
                                            desc: 'Konsistensi instruksi dan pengantar bahasa Inggris komunikatif.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 18,
                                            code: 'Indikator 18',
                                            title: 'Kepercayaan Diri Berbahasa (English Exploration)',
                                            desc: 'Dorongan murid aktif berbicara dan merespons dalam bahasa Inggris.'
                                        ),
                                    ]),

                                // B.5 Mengaji & Tahfidz
                                Forms\Components\Section::make('B.5 Pembiasaan Mengaji, Tahsin & Tahfidz Al-Qur\'an')
                                    ->description('Standar Tahsin Yanbu\'a dan Bimbingan Tahfidz BMQ & Tasmi\'')
                                    ->collapsible()
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 19,
                                            code: 'Indikator 19',
                                            title: 'Penerapan Standar Tahsin Yanbu\'a',
                                            desc: 'Ketepatan makhraj, sifat huruf, dan tajwid sesuai standar Yanbu\'a sebelum/sesudah belajar.'
                                        ),
                                        static::buildIndicatorField(
                                            num: 20,
                                            code: 'Indikator 20',
                                            title: 'Bimbingan Tahfidz (BMQ & Tasmi\')',
                                            desc: 'Murojaah klasikal, setoran hafalan, dan persiapan tasmi\' beradab.'
                                        ),
                                    ]),

                                Forms\Components\Textarea::make('rekomendasi_pelaksanaan')
                                    ->label('Rekomendasi Khusus Komponen Pelaksanaan')
                                    ->placeholder('Catatan atau rekomendasi perbaikan untuk proses pembelajaran di kelas...')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        // TAB B.6: Penutup & Evaluasi (Indikator 21-22)
                        Forms\Components\Tabs\Tab::make('B.6 Evaluasi Belajar')
                            ->icon('heroicon-o-check-circle')
                            ->badge(fn (Forms\Get $get) => TeacherSupervision::calculateScoresFromComponents($get('komponen_penilaian'))['skor_evaluasi'] . '/8')
                            ->schema([
                                Forms\Components\Section::make('B.6 Evaluasi & Ketercapaian Hasil Belajar')
                                    ->description('Asesmen formatif berkala, feedback, analisis ketercapaian, dan tindak lanjut remedial/pengayaan')
                                    ->schema([
                                        static::buildIndicatorField(
                                            num: 21,
                                            code: 'Indikator 21',
                                            title: 'Asesmen Formatif & Umpan Balik',
                                            desc: 'Pengecekan pemahaman berkelanjutan (real-time feedback).'
                                        ),
                                        static::buildIndicatorField(
                                            num: 22,
                                            code: 'Indikator 22',
                                            title: 'Analisis Ketuntasan & Tindak Lanjut',
                                            desc: 'Pengukuran ketercapaian, pengayaan, atau program intervensi/remedial.'
                                        ),
                                        Forms\Components\Textarea::make('rekomendasi_evaluasi')
                                            ->label('Rekomendasi Khusus Komponen Evaluasi')
                                            ->placeholder('Catatan atau rekomendasi perbaikan untuk asesmen dan evaluasi belajar murid...')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB 4: Evaluasi Kualitatif & Action Plan
                        Forms\Components\Tabs\Tab::make('Evaluasi Kualitatif & Action Plan')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Evaluasi Kualitatif & Rencana Tindak Lanjut')
                                    ->description('Catatan kualitatif mendalam dan rencana pendampingan klinis untuk guru')
                                    ->schema([
                                        Forms\Components\Textarea::make('praktik_baik')
                                            ->label('Praktik Baik (Best Practice)')
                                            ->placeholder('Kelebihan / hal positif yang ditunjukkan guru saat supervisi...')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('area_fokus_perbaikan')
                                            ->label('Area Fokus Perbaikan')
                                            ->placeholder('Poin-poin spesifik yang perlu ditingkatkan oleh guru...')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('rencana_coaching')
                                            ->label('Rencana Coaching / Action Plan')
                                            ->placeholder('Rencana aksi, pendampingan lanjutan, atau supervisi klinis berikutnya...')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institution.name')
                    ->label('Lembaga')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Nama Guru')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('unit_sekolah_program')
                    ->label('Program')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Bilingual' => 'info',
                        'Takhasus' => 'warning',
                        'Tahsin-Tahfidz' => 'success',
                        'Madin' => 'purple',
                        default => 'gray',
                    })
                    ->placeholder('Reguler'),

                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('kelas_semester')
                    ->label('Kelas/Smtr')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl Supervisi')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_skor')
                    ->label('Total Skor')
                    ->getStateUsing(fn (TeacherSupervision $record) => ($record->total_skor ?: (
                        $record->komponen_penilaian 
                            ? TeacherSupervision::calculateScoresFromComponents($record->komponen_penilaian)['total_skor']
                            : null
                    )) ? ($record->total_skor ?: TeacherSupervision::calculateScoresFromComponents($record->komponen_penilaian)['total_skor']) . ' / 88' : '-')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir (NA)')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->nilai_akhir !== null ? number_format($record->nilai_akhir, 2) : ($record->score !== null ? number_format($record->score, 2) : '-'))
                    ->badge()
                    ->color(fn ($state) => is_numeric($state) ? ($state >= 86 ? 'success' : ($state >= 76 ? 'warning' : 'danger')) : 'gray')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat')
                    ->getStateUsing(fn (TeacherSupervision $record) => $record->predikat ?: TeacherSupervision::determinePredicate($record->nilai_akhir ?? $record->score))
                    ->badge()
                    ->color(fn (TeacherSupervision $record) => TeacherSupervision::getPredicateBadgeColor($record->nilai_akhir ?? $record->score)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'menunggu' => 'Menunggu Jadwal',
                        'dijadwalkan' => 'Dijadwalkan',
                        'dinilai' => 'Selesai Dinilai',
                        'ditolak' => 'Ditolak',
                        default => ucfirst((string) $state),
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'menunggu' => 'gray',
                        'dijadwalkan' => 'primary',
                        'dinilai' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('institution_id')
                    ->label('Lembaga')
                    ->options(fn () => Institution::where('id', '!=', 1)->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('unit_sekolah_program')
                    ->label('Program Sekolah')
                    ->options(TeacherSupervision::getUnitOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'dinilai' => 'Selesai Dinilai',
                        'dijadwalkan' => 'Dijadwalkan',
                        'menunggu' => 'Menunggu Jadwal',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('report_card')
                    ->label('Report Card')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (TeacherSupervision $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('cetak_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(fn (TeacherSupervision $record) => route('supervisi.report-card.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label('Nilai / Edit')
                    ->color('primary'),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages\ListPermintaanSupervisis::route('/'),
            'create' => \App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages\CreatePermintaanSupervisi::route('/create'),
            'view' => \App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages\ViewReportCardSupervisi::route('/{record}'),
            'edit' => \App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages\EditPermintaanSupervisi::route('/{record}/edit'),
        ];
    }
}