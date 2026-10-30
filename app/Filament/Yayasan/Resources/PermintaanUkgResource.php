<?php

namespace App\Filament\Yayasan\Resources;

use App\Models\Institution;
use App\Models\TeacherCompetencyTest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermintaanUkgResource extends Resource
{
    protected static ?string $model = TeacherCompetencyTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Penilaian Kinerja Guru (PKG)';
    protected static ?string $modelLabel = 'Penilaian Kinerja Guru';
    protected static ?string $pluralModelLabel = 'Penilaian Kinerja Guru (PKG)';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->orderBy('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['superadmin', 'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan']);
    }

    protected static function buildUkgIndicatorField(int $num, string $title, string $desc, string $defaultTeknik = 'D'): Forms\Components\Component
    {
        return Forms\Components\Fieldset::make("Indikator {$num} • {$title}")
            ->schema([
                Forms\Components\Placeholder::make("desc_{$num}")
                    ->label('')
                    ->content($desc)
                    ->columnSpanFull(),

                Forms\Components\Select::make("komponen_penilaian.i{$num}.score")
                    ->label('Skor Penilaian')
                    ->options([
                        6 => '6 - Amat Baik / Istimewa',
                        5 => '5 - Sangat Baik',
                        4 => '4 - Baik (Standar)',
                        3 => '3 - Cukup',
                        2 => '2 - Kurang',
                        1 => '1 - Sangat Kurang',
                    ])
                    ->placeholder('-- Pilih Skor (1-6) --')
                    ->live()
                    ->required()
                    ->columnSpan(1),

                Forms\Components\Select::make("komponen_penilaian.i{$num}.teknik")
                    ->label('Teknik Penilaian')
                    ->options(TeacherCompetencyTest::getTeknikOptions())
                    ->default($defaultTeknik)
                    ->required()
                    ->columnSpan(1),

                Forms\Components\TextInput::make("komponen_penilaian.i{$num}.catatan")
                    ->label('Bukti Fisik / Catatan Pengamatan')
                    ->placeholder('Catatan bukti dokumen atau observasi...')
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function form(Form $form): Form
    {
        $clusters = TeacherCompetencyTest::getClustersStructure();

        return $form
            ->schema([
                // Section 1: Data Identitas Pendidik (Header Form)
                Forms\Components\Section::make('1. Data Identitas Pendidik (Header Form)')
                    ->description('Field profil pendidik yang dinilai dalam Penilaian Kinerja Guru (PKG)')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('institution_id')
                            ->label('Unit Sekolah / Lembaga')
                            ->options(fn () => Institution::where('id', '!=', 1)->pluck('name', 'id'))
                            ->default(function () {
                                if ($teacherId = request()->query('teacher_id')) {
                                    return User::withoutGlobalScopes()->where('id', $teacherId)->value('institution_id');
                                }
                                return null;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('teacher_id', null))
                            ->required(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Nama Guru yang Dinilai')
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
                            ->default(fn () => request()->query('teacher_id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $user = User::withoutGlobalScopes()->find($state);
                                    if ($user && $user->institution_id) {
                                        $set('institution_id', $user->institution_id);
                                    }
                                }
                            })
                            ->required(),

                        Forms\Components\TextInput::make('niy')
                            ->label('Nomor Induk Yayasan (NIY)')
                            ->placeholder('Contoh: NIY. 19850715201901')
                            ->nullable(),

                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->placeholder('Contoh: Surabaya')
                            ->nullable(),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->nullable(),

                        Forms\Components\TextInput::make('mata_pelajaran')
                            ->label('Mata Pelajaran yang Diampu')
                            ->placeholder('Contoh: Matematika, Bahasa Inggris, PAI, dll.')
                            ->required(),

                        Forms\Components\TextInput::make('tanggal_masuk')
                            ->label('Tanggal / Tahun Masuk')
                            ->placeholder('Contoh: 15 Juli 2019 / 2019')
                            ->nullable(),

                        Forms\Components\TextInput::make('lama_mengajar')
                            ->label('Lama Mengajar')
                            ->placeholder('Contoh: 5 Tahun 3 Bulan')
                            ->nullable(),

                        Forms\Components\Select::make('status_kepegawaian')
                            ->label('Status Kepegawaian')
                            ->options(TeacherCompetencyTest::getStatusKepegawaianOptions())
                            ->default('Guru Tetap Yayasan (GTY)')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pelaksanaan PKG')
                            ->default(now())
                            ->required(),

                        Forms\Components\DateTimePicker::make('jadwal')
                            ->label('Waktu / Jadwal Pelaksanaan')
                            ->default(now()->setHour(8)->setMinute(0))
                            ->nullable(),

                        Forms\Components\Select::make('assessor_id')
                            ->label('Asesor (Akun Penguji)')
                            ->options(fn () => User::withoutGlobalScopes()
                                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['tim_pengembang_penjamin_mutu_yayasan', 'ketua_yayasan', 'superadmin']))
                                ->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->nullable(),

                        Forms\Components\TextInput::make('assessor_name')
                            ->label('Nama Lengkap Asesor')
                            ->placeholder('Default: Nama akun yang menilai')
                            ->nullable(),

                        Forms\Components\Select::make('status')
                            ->label('Status PKG')
                            ->options([
                                'dinilai' => 'Selesai Dinilai',
                                'dijadwalkan' => 'Dijadwalkan',
                                'menunggu' => 'Menunggu Jadwal',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('dinilai')
                            ->required(),
                    ]),

                // Section 2: Live Skor & Predikat UKG Dashboard
                Forms\Components\Placeholder::make('live_ukg_dashboard')
                    ->label('')
                    ->columnSpanFull()
                    ->content(function (Forms\Get $get) {
                        $komponen = $get('komponen_penilaian') ?: [];
                        $calc = TeacherCompetencyTest::calculateScoresAndPredicate($komponen);
                        $total = $calc['total_skor'];
                        $na = $calc['nilai_akhir'];
                        $predikat = $calc['predikat'];
                        $badgeColor = TeacherCompetencyTest::getPredicateBadgeColor($na);

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
                                        <span style='font-size: 11px; font-weight: 800; color: #ea580c; text-transform: uppercase; letter-spacing: 0.5px;'>Otomasi Penghitungan Nilai PKG</span>
                                        <h4 style='font-size: 16px; font-weight: 800; color: #111827; margin: 2px 0 0 0;'>Akumulasi Skor & Predikat Kinerja Guru (70 Indikator)</h4>
                                    </div>
                                    <div>
                                        <span style='font-size: 12px; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; {$badgeClass}'>
                                            {$predikat}
                                        </span>
                                    </div>
                                </div>

                                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px; text-align: center;'>
                                    <div style='background-color: #fff7ed; padding: 8px; border-radius: 8px; border: 1px solid #ffedd5;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>Total Skor PKG (Max 420)</div>
                                        <div style='font-size: 20px; font-weight: 900; color: #c2410c;'>{$total} <span style='font-size: 11px; font-weight: normal; color: #9ca3af;'>/ 420</span></div>
                                    </div>
                                    <div style='background-color: #ecfdf5; padding: 8px; border-radius: 8px; border: 1px solid #d1fae5;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>Nilai Akhir PKG (NA)</div>
                                        <div style='font-size: 20px; font-weight: 900; color: #059669;'>{$na} <span style='font-size: 11px; font-weight: normal; color: #9ca3af;'>/ 100</span></div>
                                    </div>
                                    <div style='background-color: #eff6ff; padding: 8px; border-radius: 8px; border: 1px solid #dbeafe;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>A. Manajerial</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #1d4ed8;'>{$calc['manajerial']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 126</span></div>
                                    </div>
                                    <div style='background-color: #eff6ff; padding: 8px; border-radius: 8px; border: 1px solid #dbeafe;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>B. Pedagogik</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #1d4ed8;'>{$calc['pedagogik']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 132</span></div>
                                    </div>
                                    <div style='background-color: #fdf4ff; padding: 8px; border-radius: 8px; border: 1px solid #fae8ff;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>C. Profesional</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #a21caf;'>{$calc['profesional']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 30</span></div>
                                    </div>
                                    <div style='background-color: #fdf4ff; padding: 8px; border-radius: 8px; border: 1px solid #fae8ff;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>D. Sosial</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #a21caf;'>{$calc['sosial']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 18</span></div>
                                    </div>
                                    <div style='background-color: #faf5ff; padding: 8px; border-radius: 8px; border: 1px solid #f3e8ff;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>E. Kepribadian</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #7e22ce;'>{$calc['kepribadian']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 30</span></div>
                                    </div>
                                    <div style='background-color: #faf5ff; padding: 8px; border-radius: 8px; border: 1px solid #f3e8ff;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>F. Komitmen</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #7e22ce;'>{$calc['komitmen']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 42</span></div>
                                    </div>
                                    <div style='background-color: #f0fdf4; padding: 8px; border-radius: 8px; border: 1px solid #dcfce7;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>G. Unggulan</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #15803d;'>{$calc['program_unggulan']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 24</span></div>
                                    </div>
                                    <div style='background-color: #f0fdf4; padding: 8px; border-radius: 8px; border: 1px solid #dcfce7;'>
                                        <div style='font-size: 10px; color: #6b7280; font-weight: 600;'>H. Promosi</div>
                                        <div style='font-size: 16px; font-weight: 800; color: #15803d;'>{$calc['promosi']} <span style='font-size: 10px; font-weight: normal; color: #9ca3af;'>/ 18</span></div>
                                    </div>
                                </div>
                            </div>
                        ");
                    }),

                // Section 3: 8 Klaster Komponen Penilaian (70 Indikator)
                Forms\Components\Tabs::make('8 Klaster Komponen UKG')
                    ->columnSpanFull()
                    ->tabs([
                        // Tab A: Manajerial
                        Forms\Components\Tabs\Tab::make('A. Manajerial')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['manajerial'] . '/126')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('A. Manajerial (Indikator 1 – 21)')
                                        ->description('Administrasi guru, pengelolaan kelas, kepemimpinan & budaya kolaborasi')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['manajerial']['indicators']), $clusters['manajerial']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_manajerial')
                                        ->label('Rekomendasi Perbaikan Klaster Manajerial')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk kompetensi manajerial guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab B: Pedagogik
                        Forms\Components\Tabs\Tab::make('B. Pedagogik')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['pedagogik'] . '/132')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('B. Pedagogik (Indikator 22 – 43)')
                                        ->description('Penyusunan perangkat standar, penerapan pembelajaran di kelas, dan evaluasi hasil belajar')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['pedagogik']['indicators']), $clusters['pedagogik']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_pedagogik')
                                        ->label('Rekomendasi Perbaikan Klaster Pedagogik')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk kompetensi pedagogik guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab C: Profesional
                        Forms\Components\Tabs\Tab::make('C. Profesional')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['profesional'] . '/30')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('C. Profesional (Indikator 44 – 48)')
                                        ->description('Penguasaan materi bidang studi, modul ajar, integrasi TIK, dan pengembangan keprofesian')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['profesional']['indicators']), $clusters['profesional']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_profesional')
                                        ->label('Rekomendasi Perbaikan Klaster Profesional')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk kompetensi profesional guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab D: Sosial
                        Forms\Components\Tabs\Tab::make('D. Sosial')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['sosial'] . '/18')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('D. Sosial (Indikator 49 – 51)')
                                        ->description('Hubungan harmonis dan kerja sama dengan rekan guru, orang tua, dan murid')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['sosial']['indicators']), $clusters['sosial']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_sosial')
                                        ->label('Rekomendasi Perbaikan Klaster Sosial')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk kompetensi sosial guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab E: Kepribadian
                        Forms\Components\Tabs\Tab::make('E. Kepribadian')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['kepribadian'] . '/30')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('E. Kepribadian (Indikator 52 – 56)')
                                        ->description('Penampilan rapi berbusana syar\'i, tutur kata santun, sikap beradab, dan kedisiplinan kerja')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['kepribadian']['indicators']), $clusters['kepribadian']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_kepribadian')
                                        ->label('Rekomendasi Perbaikan Klaster Kepribadian')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk kompetensi kepribadian guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab F: Komitmen
                        Forms\Components\Tabs\Tab::make('F. Komitmen')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['komitmen'] . '/42')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('F. Komitmen (Indikator 57 – 63)')
                                        ->description('Komitmen moral keikhlasan tugas serta komitmen kelembagaan terhadap tata tertib yayasan')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['komitmen']['indicators']), $clusters['komitmen']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_komitmen')
                                        ->label('Rekomendasi Perbaikan Klaster Komitmen')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk komitmen guru...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab G: Program Unggulan
                        Forms\Components\Tabs\Tab::make('G. Program Unggulan')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['program_unggulan'] . '/24')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('G. Keterlibatan Program Unggulan Sekolah (Indikator 64 – 67)')
                                        ->description('Partisipasi aktif dalam sekolah ramah anak, tahfidz, bilingual, dan budaya lingkungan bersih')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['program_unggulan']['indicators']), $clusters['program_unggulan']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_program_unggulan')
                                        ->label('Rekomendasi Perbaikan Klaster Program Unggulan')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk keterlibatan program unggulan...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab H: Promosi & PPDB
                        Forms\Components\Tabs\Tab::make('H. Promosi & PPDB')
                            ->badge(fn (Forms\Get $get) => TeacherCompetencyTest::calculateScoresAndPredicate($get('komponen_penilaian'))['promosi'] . '/18')
                            ->schema(array_merge(
                                [
                                    Forms\Components\Section::make('H. Keterlibatan Peningkatan Murid Baru / Promosi (Indikator 68 – 70)')
                                        ->description('Keaktifan promosi di medsos, kepanitiaan PPDB/SPMB, dan kontribusi membawa siswa baru')
                                        ->schema(
                                            array_map(function ($num, $ind) {
                                                return static::buildUkgIndicatorField($num, $ind['title'], $ind['desc'], $ind['teknik']);
                                            }, array_keys($clusters['promosi']['indicators']), $clusters['promosi']['indicators'])
                                        ),
                                    Forms\Components\Textarea::make('rekomendasi_promosi')
                                        ->label('Rekomendasi Perbaikan Klaster Promosi & PPDB')
                                        ->placeholder('Catatan atau rekomendasi perbaikan untuk keterlibatan promosi...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]
                            )),

                        // Tab Catatan Umum
                        Forms\Components\Tabs\Tab::make('Catatan Umum Asesor')
                            ->icon('heroicon-o-document-check')
                            ->schema([
                                Forms\Components\Section::make('Catatan Umum & Rekomendasi Pengembangan Guru')
                                    ->schema([
                                        Forms\Components\Textarea::make('catatan_umum')
                                            ->label('Catatan Umum Asesor Yayasan')
                                            ->placeholder('Kesimpulan umum hasil penilaian kinerja guru, saran pembinaan, dan rekomendasi tindak lanjut...')
                                            ->rows(4)
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

                Tables\Columns\TextColumn::make('niy')
                    ->label('NIY')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status_kepegawaian')
                    ->label('Status')
                    ->badge()
                    ->color('info')
                    ->placeholder('GTY'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl PKG')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_skor')
                    ->label('Total Skor')
                    ->getStateUsing(fn (TeacherCompetencyTest $record) => ($record->total_skor ?: (
                        $record->komponen_penilaian 
                            ? TeacherCompetencyTest::calculateScoresAndPredicate($record->komponen_penilaian)['total_skor']
                            : null
                    )) ? ($record->total_skor ?: TeacherCompetencyTest::calculateScoresAndPredicate($record->komponen_penilaian)['total_skor']) . ' / 420' : '-')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir (NA)')
                    ->getStateUsing(fn (TeacherCompetencyTest $record) => $record->nilai_akhir !== null ? number_format($record->nilai_akhir, 2) : ($record->score !== null ? number_format($record->score, 2) : '-'))
                    ->badge()
                    ->color(fn ($state) => is_numeric($state) ? ($state >= 86 ? 'success' : ($state >= 76 ? 'warning' : 'danger')) : 'gray')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat Kinerja')
                    ->getStateUsing(fn (TeacherCompetencyTest $record) => $record->predikat ?: TeacherCompetencyTest::determinePredicate($record->nilai_akhir ?? $record->score))
                    ->badge()
                    ->color(fn (TeacherCompetencyTest $record) => TeacherCompetencyTest::getPredicateBadgeColor($record->nilai_akhir ?? $record->score)),

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
                    ->options(\App\Models\Institution::where('id', '!=', 1)->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'dinilai' => 'Selesai Dinilai',
                        'dijadwalkan' => 'Dijadwalkan',
                        'menunggu' => 'Menunggu Jadwal',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('report_card')
                    ->label('Raport PKG')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->url(fn (TeacherCompetencyTest $record) => static::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('cetak_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(fn (TeacherCompetencyTest $record) => route('ukg.report-card.pdf', $record))
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
            'index' => \App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages\ListPermintaanUkgs::route('/'),
            'create' => \App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages\CreatePermintaanUkg::route('/create'),
            'view' => \App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages\ViewUkgReportCard::route('/{record}'),
            'edit' => \App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages\EditPermintaanUkg::route('/{record}/edit'),
        ];
    }
}