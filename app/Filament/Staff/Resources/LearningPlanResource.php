<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\LearningPlanResource\Pages;
use App\Models\LearningPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LearningPlanResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = LearningPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $modelLabel = 'Telaah Perencanaan Pembelajaran';
    protected static ?string $pluralModelLabel = 'Telaah Perencanaan Pembelajaran';
    protected static ?int $navigationSort = 5;

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas')
                    ->schema([
                        Forms\Components\Select::make('penyusun_id')
                            ->label('Nama Penyusun Perencanaan')
                            ->relationship('penyusun', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('unit_kerja_penyusun')
                            ->label('Unit Kerja Penyusun')
                            ->default('SMPI Baitul Makmur')
                            ->required(),
                        Forms\Components\TextInput::make('mata_pelajaran')
                            ->label('Mata Pelajaran')
                            ->required(),
                        Forms\Components\Select::make('penelaah_id')
                            ->label('Nama Penelaah')
                            ->relationship('penelaah', 'name')
                            ->searchable()
                            ->default(fn () => auth()->id())
                            ->required(),
                        Forms\Components\TextInput::make('unit_kerja_penelaah')
                            ->label('Unit Kerja Penelaah')
                            ->default('SMPI Baitul Makmur')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Instrumen Penelaahan')
                    ->description('Skala: 1 = hampir tidak ada | 2 = sedikit dan lemah | 3 = cukup | 4 = memadai')
                    ->schema([
                        // Kerangka Perencanaan Pembelajaran
                        Forms\Components\Fieldset::make('Kerangka Perencanaan Pembelajaran')
                            ->schema([
                                self::aspectField(1, 'Memuat 4 komponen Perencanaan Pembelajaran: 1.1 Identifikasi, 1.2 Desain pembelajaran, 1.3 Pengalaman belajar, dan 1.4 Asesmen'),
                            ])->columns(1),

                        // Identifikasi
                        Forms\Components\Fieldset::make('Identifikasi')
                            ->schema([
                                self::aspectField(2, 'Memuat: 2.1 Identifikasi kesiapan murid. 2.2 Karakteristik materi pelajaran. 2.3 Dimensi Profil Lulusan.'),
                            ])->columns(1),

                        // Desain Pembelajaran
                        Forms\Components\Fieldset::make('Desain Pembelajaran')
                            ->schema([
                                self::aspectField(3, 'Tujuan Pembelajaran mencakup kompetensi dan konten pada ruang lingkup materi dengan menggunakan kata kerja operasional yang relevan.'),
                                self::aspectField(4, 'Praktik Pedagogis: Model/Strategi/Metode pembelajaran yang dipilih sesuai dengan tujuan pembelajaran.'),
                                self::aspectField(5, 'Mencantumkan Kemitraan Pembelajaran di dalam dan/atau luar lingkup sekolah (optional)'),
                                self::aspectField(6, 'Mencantumkan lingkungan pembelajaran yang dikembangkan dalam budaya belajar, ruang fisik, dan/atau ruang virtual.'),
                                self::aspectField(7, 'Mencantumkan pemanfaatan teknologi digital untuk menciptakan pembelajaran yang interaktif, kolaboratif, dan kontekstual (optional)'),
                            ])->columns(1),

                        // Pengalaman Belajar
                        Forms\Components\Fieldset::make('Pengalaman Belajar')
                            ->schema([
                                self::aspectField(8, 'Memuat kegiatan pembelajaran yang memfasilitasi murid untuk terlibat aktif mengkonstruksi pengetahuan agar dapat memahami secara mendalam konsep atau materi dari berbagai sumber dan konteks (unistruktural dan multistruktural)'),
                                self::aspectField(9, 'Memuat kegiatan yang mengkondisikan pengalaman belajar yang menunjukan aktivitas murid mengaplikasi pemahaman secara kontekstual di kehidupan nyata (relational)'),
                                self::aspectField(10, 'Memuat kegiatan yang mampu memfasilitasi murid: mengevaluasi dan memaknai proses, mengelola proses belajarnya mandiri, mengembangkan strategi belajar. (extended abstract)'),
                                self::aspectField(11, 'Kegiatan pembelajaran memfasilitasi pencapaian Dimensi Profil Lulusan yang ditentukan.'),
                            ])->columns(1),

                        // Asesmen Pembelajaran
                        Forms\Components\Fieldset::make('Asesmen Pembelajaran')
                            ->schema([
                                self::aspectField(12, 'Mencantumkan teknik dan instrumen asesmen sumatif dan formatif selaras dengan tujuan pembelajaran.'),
                            ])->columns(1),
                    ]),

                Forms\Components\Section::make('Kesimpulan dan Rekomendasi')
                    ->schema([
                        Forms\Components\Textarea::make('evaluations.note_13')
                            ->label('13. Tuliskan kelebihan Perencanaan Pembelajaran:')
                            ->rows(3),
                        Forms\Components\Textarea::make('evaluations.note_14')
                            ->label('14. Tuliskan hal yang perlu ditingkatkan dari Perencanaan Pembelajaran:')
                            ->rows(3),
                        Forms\Components\Textarea::make('evaluations.note_15')
                            ->label('15. Tuliskan rekomendasi dan lanjutkan dengan revisi Perencanaan Pembelajaran sesuai prinsip PM:')
                            ->rows(3),
                    ])
            ]);
    }

    /**
     * Helper form field untuk setiap aspek
     */
    private static function aspectField(int $no, string $label): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(3)
            ->schema([
                Forms\Components\Placeholder::make("label_{$no}")
                    ->label("No. {$no}")
                    ->content($label)
                    ->columnSpan(1),
                Forms\Components\Radio::make("evaluations.aspect_{$no}_score")
                    ->label('Skala')
                    ->options([
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                    ])
                    ->inline()
                    ->required()
                    ->columnSpan(1),
                Forms\Components\Textarea::make("evaluations.aspect_{$no}_note")
                    ->label('Saran/Komentar Kritis')
                    ->rows(2)
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('penyusun.name')
                    ->label('Penyusun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mata_pelajaran')
                    ->label('Mata Pelajaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('penelaah.name')
                    ->label('Penelaah')
                    ->searchable(),
                Tables\Columns\TextColumn::make('average_score')
                    ->label('Nilai Akhir')
                    ->numeric(2)
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sangat Baik' => 'success',
                        'Baik' => 'info',
                        'Cukup' => 'warning',
                        'Kurang' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningPlans::route('/'),
            'create' => Pages\CreateLearningPlan::route('/create'),
            'edit' => Pages\EditLearningPlan::route('/{record}/edit'),
        ];
    }
}
