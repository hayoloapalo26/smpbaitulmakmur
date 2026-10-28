<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\AttributeViolationResource\Pages;
use App\Models\AttributeViolation;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttributeViolationResource extends Resource
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'guru_piket'])) {
            return true;
        }

        // Untuk guru: HANYA jika guru yang bersangkutan terjadwal piket HARI INI
        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $model = AttributeViolation::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Pelanggaran Atribut';
    protected static ?string $modelLabel = 'Pelanggaran Atribut';
    protected static ?string $pluralModelLabel = 'Pelanggaran Atribut Siswa';
    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pencatatan Pelanggaran Atribut')
                    ->description('Pencatatan atribut oleh Guru Piket akan otomatis mengurangi poin kedisiplinan karakter siswa.')
                    ->schema([
                        Forms\Components\Hidden::make('teacher_id')
                            ->default(Auth::id()),

                        Forms\Components\Select::make('student_id')
                            ->label('Siswa yang Melanggar')
                            ->options(function () {
                                $user = Auth::user();
                                $q = Student::query();
                                if ($user && $user->institution_id && !$user->hasRole('superadmin') && $user->institution_id != 1) {
                                    $q->where('institution_id', $user->institution_id);
                                }
                                return $q->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live(),

                        Forms\Components\Placeholder::make('student_attribute_info')
                            ->label('Profil Siswa & Riwayat Pelanggaran')
                            ->visible(fn (Forms\Get $get) => filled($get('student_id')))
                            ->content(function (Forms\Get $get) {
                                $student = Student::with(['classroom.waliKelas', 'attributeViolations'])->find($get('student_id'));
                                if (!$student) return '-';

                                $kelas = $student->classroom->name ?? '-';
                                $waliKelas = $student->classroom?->waliKelas?->name ?? 'Belum ditentukan';
                                $totalViolations = $student->attributeViolations->count();
                                $ortu = $student->nama_ayah ?: ($student->nama_ibu ?: '-');
                                $hp = $student->no_telepon_orang_tua ?: '-';

                                $warningBadge = $totalViolations >= 3
                                    ? "<span class='px-2 py-0.5 rounded bg-red-100 text-red-800 font-bold'>⚠️ Peringatan: Sudah {$totalViolations}x Melanggar Atribut (Perlu Pembinaan Intensif)</span>"
                                    : "<span class='px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold'>Total Pelanggaran Sebelumnya: {$totalViolations}x</span>";

                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-3 bg-purple-50/60 dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl space-y-1.5 text-xs'>
                                        <div class='flex flex-wrap items-center justify-between gap-2 border-b border-purple-200/50 pb-1.5'>
                                            <div>
                                                <span class='font-bold text-gray-900 dark:text-white text-sm'>{$student->name}</span>
                                                <span class='ml-2 px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 font-bold'>Kelas: {$kelas}</span>
                                                <span class='ml-1 text-gray-500'>Wali Kelas: {$waliKelas}</span>
                                            </div>
                                            <div>{$warningBadge}</div>
                                        </div>
                                        <div class='text-gray-600 dark:text-gray-300 flex flex-wrap gap-4'>
                                            <span><strong>Orang Tua:</strong> {$ortu}</span>
                                            <span><strong>No. WhatsApp:</strong> <span class='font-mono font-bold text-emerald-600'>{$hp}</span></span>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Pelanggaran')
                            ->default(Carbon::today())
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y'),

                        Forms\Components\CheckboxList::make('attribute_type')
                            ->label('Atribut yang Tidak Dipakai / Dilanggar')
                            ->options([
                                'Sabuk' => 'Sabuk / Ikat Pinggang',
                                'Dasi' => 'Dasi Sekolah',
                                'Topi' => 'Topi Upacara',
                                'Sepatu Hitam' => 'Sepatu Bukan Hitam',
                                'Kaos Kaki Putih' => 'Kaos Kaki Tidak Sesuai',
                                'Badge/Lokasi' => 'Badge / Atribut Lokasi Tidak Lengkap',
                                'Seragam' => 'Seragam Tidak Sesuai Hari',
                                'Lainnya' => 'Pelanggaran Kerapian Lainnya',
                            ])
                            ->columns(2)
                            ->required(),

                        Forms\Components\Textarea::make('jenis_pelanggaran')
                            ->label('Keterangan / Catatan Tambahan')
                            ->placeholder('Contoh: Tidak memakai dasi saat upacara bendera hari Senin')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(function (AttributeViolation $record) {
                        $parts = [];
                        if ($record->student?->classroom?->name) {
                            $parts[] = 'Kelas ' . $record->student->classroom->name;
                        }
                        $wali = $record->student?->classroom?->waliKelas?->name;
                        if ($wali) {
                            $parts[] = 'Wali: ' . $wali;
                        }
                        return implode(' • ', $parts);
                    }),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('attribute_type')
                    ->label('Atribut yang Dilanggar')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state),

                Tables\Columns\TextColumn::make('jenis_pelanggaran')
                    ->label('Keterangan')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Piket Pencatat')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hari_ini')
                    ->label('⚡ Pelanggaran Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('tanggal', Carbon::today())),

                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Kelas')
                    ->relationship('student.classroom', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('buat_kasus')
                    ->label('Eskalasi Kasus')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn () => Auth::user()?->hasAnyRole(['wali_kelas', 'waka_kesiswaan', 'superadmin', 'bk']))
                    ->modalHeading(fn (AttributeViolation $record) => "Eskalasi ke Kasus Pelanggaran: {$record->student->name}")
                    ->modalDescription('Buat berkas kasus pelanggaran siswa resmi untuk penanganan bertingkat oleh Wali Kelas dan BK.')
                    ->modalSubmitActionLabel('Simpan Berkas Kasus')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_kejadian')
                            ->label('Tanggal Kejadian')
                            ->default(fn (AttributeViolation $record) => $record->tanggal)
                            ->required(),
                        Forms\Components\CheckboxList::make('kategori_kasus')
                            ->label('Kategori Pelanggaran')
                            ->options([
                                'Pelanggaran Atribut Seragam' => '👕 Pelanggaran Atribut Seragam',
                                'Kedisiplinan Berulang'       => '⚠️ Kedisiplinan Berulang',
                                'Lainnya'                     => '📝 Lainnya',
                            ])
                            ->default(['Pelanggaran Atribut Seragam'])
                            ->required(),
                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Kronologi Masalah')
                            ->default(function (AttributeViolation $record) {
                                $attrs = is_array($record->attribute_type) ? implode(', ', $record->attribute_type) : $record->attribute_type;
                                return "Pelanggaran atribut berulang tercatat oleh Guru Piket: {$attrs}. Catatan: {$record->jenis_pelanggaran}";
                            })
                            ->rows(3)
                            ->required(),
                        Forms\Components\Textarea::make('catatan_wali_kelas')
                            ->label('Tindakan / Pembinaan Awal')
                            ->placeholder('Tindakan teguran atau pembinaan yang diberikan wali kelas...')
                            ->rows(2),
                    ])
                    ->action(function (AttributeViolation $record, array $data) {
                        \App\Models\StudentCase::create([
                            'student_id'         => $record->student_id,
                            'wali_kelas_id'      => $record->student->classroom?->wali_kelas_id ?? Auth::id(),
                            'tanggal_kejadian'   => $data['tanggal_kejadian'],
                            'kategori_kasus'     => $data['kategori_kasus'],
                            'deskripsi'          => $data['deskripsi'],
                            'catatan_wali_kelas' => $data['catatan_wali_kelas'] ?? null,
                            'status'             => 'proses',
                            'ditangani_oleh'     => 'wali_kelas',
                            'institution_id'     => $record->student->institution_id ?? Auth::user()->institution_id,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Kasus Pelanggaran Berhasil Dibuat')
                            ->body("Pelanggaran {$record->student->name} telah dieskalasi ke menu Kasus Pelanggaran Siswa.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAttributeViolations::route('/'),
        ];
    }
}
