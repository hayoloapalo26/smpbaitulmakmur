<?php
$path = __DIR__ . '/app/Filament/Staff/Resources/StudentResource.php';
$content = file_get_contents($path);

// The new form body
$newForm = <<<'PHP'
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Utama Siswa')
                    ->schema([
                        Forms\Components\TextInput::make('nis')
                            ->label('NIS')
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap Siswa')
                            ->required(),
                        Forms\Components\Select::make('classroom_id')
                            ->label('Kelas')
                            ->relationship('classroom', 'name')
                            ->required(),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Gender')
                            ->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])
                            ->required(),
                        Forms\Components\TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required(),
                        Forms\Components\TextInput::make('no_telepon_siswa')
                            ->label('No Telepon Siswa')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('alamat_siswa')
                            ->label('Alamat Siswa')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Data Akademik & Riwayat')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Siswa')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_ijazah')
                            ->label('No Ijazah')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_skhu')
                            ->label('No SKHU')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tahun_lulus')
                            ->label('Tahun Lulus')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_peserta_un')
                            ->label('No Peserta UN')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('character_score')
                            ->label('Skor Karakter')
                            ->numeric()
                            ->default(80)
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Data Orang Tua / Wali')
                    ->description('Pastikan memasukkan No Telepon Orang Tua yang valid')
                    ->schema([
                        Forms\Components\TextInput::make('no_telepon_orang_tua')
                            ->label('No Telepon Orang Tua')
                            ->tel()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Fieldset::make('Data Ayah')
                            ->schema([
                                Forms\Components\TextInput::make('nama_ayah')
                                    ->label('Nama Ayah')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tempat_lahir_ayah')
                                    ->label('Tempat Lahir Ayah')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('tanggal_lahir_ayah')
                                    ->label('Tanggal Lahir Ayah'),
                                Forms\Components\TextInput::make('pekerjaan_ayah')
                                    ->label('Pekerjaan Ayah')
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Fieldset::make('Data Ibu')
                            ->schema([
                                Forms\Components\TextInput::make('nama_ibu')
                                    ->label('Nama Ibu')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tempat_lahir_ibu')
                                    ->label('Tempat Lahir Ibu')
                                    ->maxLength(255),
                                Forms\Components\DatePicker::make('tanggal_lahir_ibu')
                                    ->label('Tanggal Lahir Ibu'),
                                Forms\Components\TextInput::make('pekerjaan_ibu')
                                    ->label('Pekerjaan Ibu')
                                    ->maxLength(255),
                            ])->columns(2),
                    ]),
            ]);
    }
PHP;

// Replace the form block
$content = preg_replace('/public static function form\(Form \$form\): Form\s*\{.*?(?=public static function table)/s', $newForm . "\n\n    ", $content);

file_put_contents($path, $content);
echo "Updated StudentResource form!\n";
