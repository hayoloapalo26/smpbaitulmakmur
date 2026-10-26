<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ParentSummonResource\Pages;
use App\Models\ParentSummon;
use App\Models\Student;
use App\Models\StudentCase;
use App\Models\Classroom;
use Filament\Forms;
use Filament\Forms\Form;
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

                        Forms\Components\FileUpload::make('surat_file')
                            ->label('Upload Berita Acara / Surat Bertandatangan (PDF/Foto)')
                            ->directory('parent_summons/documents'),

                        Forms\Components\Textarea::make('hasil_pertemuan')
                            ->label('Hasil Pertemuan / Notula Kesepakatan Bersama Orang Tua')
                            ->placeholder('Catat hasil musyawarah, komitmen orang tua, atau surat pernyataan yang disepakati...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                    ->description(fn (ParentSummon $record) => $record->student->classroom->name ?? ''),

                Tables\Columns\TextColumn::make('studentCase.nomor_kasus')
                    ->label('No. Kasus')
                    ->badge()
                    ->color('info')
                    ->placeholder('Non-Kasus'),

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
