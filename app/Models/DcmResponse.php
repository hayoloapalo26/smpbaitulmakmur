<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DcmResponse extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'submitted_at'              => 'datetime',
            'jawaban_kesehatan'         => 'array',
            'jawaban_ekonomi'           => 'array',
            'jawaban_keluarga'          => 'array',
            'jawaban_agama_moral'       => 'array',
            'jawaban_pribadi'           => 'array',
            'jawaban_sosial_organisasi' => 'array',
            'jawaban_rekreasi_hobi'     => 'array',
            'jawaban_sekolah'           => 'array',
            'jawaban_kurikulum'         => 'array',
            'jawaban_masa_depan'        => 'array',
            'jawaban_kebiasaan_belajar' => 'array',
            'jawaban_asmara'            => 'array',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Hitung total skor keseluruhan (semua kategori)
     */
    public function getTotalScoreAttribute(): int
    {
        $fields = [
            'jawaban_kesehatan', 'jawaban_ekonomi', 'jawaban_keluarga',
            'jawaban_agama_moral', 'jawaban_pribadi', 'jawaban_sosial_organisasi',
            'jawaban_rekreasi_hobi', 'jawaban_sekolah', 'jawaban_kurikulum',
            'jawaban_masa_depan', 'jawaban_kebiasaan_belajar', 'jawaban_asmara',
        ];

        $total = 0;
        foreach ($fields as $field) {
            $total += count($this->$field ?? []);
        }
        return $total;
    }

    /**
     * Skor per kategori
     */
    public function getScoreForCategory(string $field): int
    {
        return count($this->$field ?? []);
    }

    /**
     * Daftar semua kategori beserta konfigurasi pertanyaan
     */
    public static function getCategories(): array
    {
        return [
            'jawaban_kesehatan' => [
                'label' => 'I. Masalah Kesehatan',
                'short' => 'Kesehatan',
                'questions' => [
                    1  => 'Sering sakit ketika SD',
                    2  => 'Sering sakit sekarang',
                    3  => 'Jantung sering berdebar-debar',
                    4  => 'Sering keluar keringat dingin',
                    5  => 'Kesehatan saya sering terganggu',
                    6  => 'Pernah dioperasi',
                    7  => 'Merasa terlalu gemuk',
                    8  => 'Merasa terlalu kurus',
                    9  => 'Saya kurang nafsu makan',
                    10 => 'Saya merasa kurang bahagia karena cacat',
                    11 => 'Saya kurang/tidak dapat tidur',
                    12 => 'Merasa lelah dan tidak bersemangat',
                    13 => 'Makanan kurang memenuhi syarat kesehatan',
                    14 => 'Sering merasa ngantuk',
                    15 => 'Penglihatan saya kurang',
                    16 => 'Saya sering pusing atau pening',
                    17 => 'Pendengaran saya kurang',
                    18 => 'Saya menderita gagap',
                    19 => 'Kurang hawa segar',
                    20 => 'Sering gemetar dan keluar keringat',
                    21 => 'Mudah kaget dan gugup',
                    22 => 'Sering pingsan',
                    23 => 'Tekanan darah terlalu rendah',
                    24 => 'Tekanan darah terlalu tinggi',
                    25 => 'Mempunyai penyakit yang tidak sembuh-sembuh',
                ],
            ],
            'jawaban_ekonomi' => [
                'label' => 'II. Masalah Keadaan Kehidupan Ekonomi',
                'short' => 'Ekonomi',
                'questions' => [
                    1  => 'Uang saku saya tidak mencukupi',
                    2  => 'Kekurangan buku-buku karena tidak mampu membeli',
                    3  => 'Terpaksa saya bekerja karena ekonomi tidak cukup',
                    4  => 'Tidak tahu bagaimana caranya menambah biaya sekolah',
                    5  => 'Saya sering meminjam uang',
                    6  => 'Penerangan lampu di rumah kurang cukup',
                    7  => 'Sering berjalan kaki ke sekolah padahal rumah jauh',
                    8  => 'Orang tua tidak mempunyai pekerjaan tetap',
                    9  => 'Uang sekolah saya terlalu tinggi',
                    10 => 'Terlalu banyak saudara yang harus dibiayai orang tua',
                    11 => 'Saya tidak pernah mendapatkan uang saku',
                    12 => 'Ibu/saudara saya ikut membantu mencari penghasilan',
                    13 => 'Terpaksa menunggak membayar uang sekolah',
                    14 => 'Tamat sekolah terpaksa tidak meneruskan ke sekolah lanjutan',
                    15 => 'Ayah dan ibu tidak hidup bersama',
                    16 => 'Keluarga saya hidup berantakan',
                    17 => 'Saya tidak puas dengan keluarga saya',
                    18 => 'Saya ikut orang lain karena keluarga saya tidak mampu',
                    19 => 'Saya tidak ingin orang tua terlalu membatasi',
                ],
            ],
            'jawaban_keluarga' => [
                'label' => 'III. Masalah Keluarga',
                'short' => 'Keluarga',
                'questions' => [
                    1  => 'Saya adalah anak tunggal',
                    2  => 'Saya adalah anak sulung/pertama',
                    3  => 'Saya adalah anak bungsu/terakhir',
                    4  => 'Ayah saya sudah meninggal',
                    5  => 'Ibu saya sudah meninggal',
                    6  => 'Saya selalu dimanja orang tua/saudara',
                    7  => 'Tidak hidup bersama orang tua',
                    8  => 'Selalu bertengkar dengan kakak/adik',
                    9  => 'Ayah/ibu pulang kerja terlalu petang',
                    10 => 'Di rumah terlalu sibuk dengan tugas-tugas membantu orang tua',
                    11 => 'Pertengkaran ayah dan ibu mengganggu pikiran saya',
                    12 => 'Mata pencaharian orang tua mengganggu orang tua saya',
                    13 => 'Orang tua kurang memperhatikan saya',
                    14 => 'Orang tua mencampuri urusanku',
                    15 => 'Sukar menyesuaikan diri dengan ayah',
                    16 => 'Sukar menyesuaikan diri dengan ibu',
                    17 => 'Di rumah saya merasa kurang senang',
                    18 => 'Kehidupan di rumah kurang teratur',
                    19 => 'Keluarga kami kurang tolong menolong',
                ],
            ],
            'jawaban_agama_moral' => [
                'label' => 'IV. Masalah Agama dan Moral',
                'short' => 'Agama & Moral',
                'questions' => [
                    1  => 'Tidak dapat bersungguh-sungguh menerima pelajaran',
                    2  => 'Masih meragukan adanya Tuhan',
                    3  => 'Sering timbul keinginan berganti agama',
                    4  => 'Malas bersembahyang',
                    5  => 'Tidak bersungguh-sungguh mengerjakan ibadah',
                    6  => 'Kurang merasakan manfaat ibadah',
                    7  => 'Sering berkata dusta',
                    8  => 'Sering mengingkari janji',
                    9  => 'Sering tidak mengakui kesalahan',
                    10 => 'Sering iri hati',
                    11 => 'Ucapan dan perbuatan tidak sesuai',
                    12 => 'Sering mengambil barang orang lain',
                    13 => 'Sering mempermainkan orang lain',
                    14 => 'Pernah melanggar kesusilaan',
                    15 => 'Kurang dapat toleransi dengan pemeluk agama lain',
                    16 => 'Mudah terasa iba terhadap penderitaan orang lain',
                    17 => 'Kurang ada tenggang rasa dengan orang lain',
                    18 => 'Sering tidak mengembalikan barang milik orang lain yang dipinjam',
                    19 => 'Merasa hormat dengan orang yang lebih tua',
                    20 => 'Merasa hormat dengan wanita',
                    21 => 'Membenci teman yang punya kelebihan',
                    22 => 'Ada perasaan senang menceritakan hal-hal yang berbau porno',
                    23 => 'Sangat senang bergaul dengan pria/wanita yang ugal-ugalan',
                    24 => 'Kurang senang terhadap wanita/pria yang pendiam',
                ],
            ],
            'jawaban_pribadi' => [
                'label' => 'V. Masalah Pribadi',
                'short' => 'Pribadi',
                'questions' => [
                    1  => 'Tidak suka bergaul dengan orang yang kedudukannya lebih rendah',
                    2  => 'Tidak suka bergaul dengan orang yang kedudukannya lebih tinggi',
                    3  => 'Sering merasa malu dengan kawan lawan jenis',
                    4  => 'Sering merasa iri hati',
                    5  => 'Sukar mendapat kawan',
                    6  => 'Tidak suka bertamu',
                    7  => 'Enggan menerima tamu',
                    8  => 'Merasa rendah diri',
                    9  => 'Sering merasa curiga dengan orang lain',
                    10 => 'Bersikap ragu dan tidak toleran',
                    11 => 'Bersikap dingin dalam bergaul',
                    12 => 'Sering ingin bunuh diri',
                    13 => 'Merasa pesimis (tidak punya harapan)',
                    14 => 'Saya ingin lebih menarik',
                ],
            ],
            'jawaban_sosial_organisasi' => [
                'label' => 'VI. Masalah Hubungan Sosial dan Berorganisasi',
                'short' => 'Sosial & Organisasi',
                'questions' => [
                    1  => 'Tidak senang bergaul dalam kelompok',
                    2  => 'Sering gagal dalam usaha mencari kawan',
                    3  => 'Sukar bergaul',
                    4  => 'Merasa tidak disenangi kawan-kawan di luar sekolah',
                    5  => 'Senang menjadi pusat perhatian',
                    6  => 'Tidak berminat pada organisasi',
                    7  => 'Terlalu aktif dalam organisasi',
                    8  => 'Sukar menyesuaikan diri',
                    9  => 'Mudah tersinggung',
                    10 => 'Takut bergaul dengan atasan',
                    11 => 'Tidak pernah menjadi pimpinan',
                    12 => 'Tidak pernah mengemukakan suatu pendapat',
                    13 => 'Sering bertentangan pendapat dengan orang lain',
                    14 => 'Sukar menerima kekalahan',
                    15 => 'Selalu ingin berkuasa dalam pergaulan',
                    16 => 'Bingung bila berhadapan dengan orang banyak',
                    17 => 'Mudah merasa malu',
                    18 => 'Mudah marah',
                    19 => 'Sering tidak sabar',
                    20 => 'Sering tidak menepati janji',
                    21 => 'Tidak dapat menerima kritikan',
                    22 => 'Bersifat tertutup',
                    23 => 'Lebih senang menjadi anggota daripada menjadi ketua',
                    24 => 'Jarang diajak bermain-main bersama oleh teman-teman',
                ],
            ],
            'jawaban_rekreasi_hobi' => [
                'label' => 'VII. Masalah Rekreasi, Hobi dan Penggunaan Waktu',
                'short' => 'Rekreasi & Hobi',
                'questions' => [
                    1  => 'Keinginan untuk rekreasi selalu terhalang',
                    2  => 'Gemar melukis tetapi tidak punya alat',
                    3  => 'Waktu libur saya harus belajar',
                    4  => 'Suka berolahraga tapi tidak ada kesempatan',
                    5  => 'Lebih suka buku-buku hiburan daripada buku pelajaran',
                    6  => 'Setiap ada film baru saya nonton',
                    7  => 'Salah satu keluargaku selalu menghalangi hobiku',
                    8  => 'Kesenangan membaca majalah komik sering menghabiskan waktu belajar',
                    9  => 'Habis waktuku untuk nonton TV',
                    10 => 'Orang tuaku tidak pernah mengajak rekreasi',
                    11 => 'Terlalu sering rekreasi ke luar kota',
                    12 => 'Sebagian besar waktu saya pakai belajar',
                    13 => 'Waktu banyak terpakai untuk membantu orang tua',
                    14 => 'Saya tidak dapat menggunakan waktu luang dengan baik',
                    15 => 'Waktu saya banyak terpakai untuk menuruti keinginan/hobi saya',
                    16 => 'Waktu saya habis untuk ngobrol',
                    17 => 'Waktu saya habis untuk latihan seni',
                    18 => 'Saya tidak senang rekreasi',
                    19 => 'Lebih senang di rumah daripada menyalurkan hobi di luar rumah',
                ],
            ],
            'jawaban_sekolah' => [
                'label' => 'VIII. Masalah Penyesuaian Terhadap Sekolah',
                'short' => 'Penyesuaian Sekolah',
                'questions' => [
                    1  => 'Sering malas untuk masuk sekolah',
                    2  => 'Sering meninggalkan pelajaran',
                    3  => 'Sering membolos',
                    4  => 'Ingin pindah kelas lain',
                    5  => 'Ingin pindah sekolah',
                    6  => 'Sering merasa cemas jika ada ulangan',
                    7  => 'Bahan pelajaran sukar dikuasai',
                    8  => 'Ingin menjadi pengurus OSIS tetapi tidak terpilih',
                    9  => 'Ada beberapa pelajaran yang tidak saya senangi',
                    10 => 'Pelajaran disekolah terlalu membosankan',
                    11 => 'Merasa kurang dimengerti oleh guru',
                    12 => 'Peraturan sekolah terlalu menekan',
                    13 => 'Pribadi salah seorang guru menyebabkan pelajarannya tidak kuperhatikan',
                    14 => 'Beberapa mata pelajaran kuanggap tidak perlu',
                    15 => 'Di sekolah tidak dapat memusatkan konsentrasi',
                    16 => 'Di dalam kelas saya sering melamun',
                    17 => 'Saya sering datang terlambat',
                    18 => 'Saya sering dibenci kawan-kawan di sekolah',
                    19 => 'Seorang kawan selalu menjengkelkan saya',
                    20 => 'Tidak ada teman yang saya sukai untuk belajar bersama',
                ],
            ],
            'jawaban_kurikulum' => [
                'label' => 'IX. Masalah Penyesuaian Terhadap Kurikulum',
                'short' => 'Kurikulum',
                'questions' => [
                    1  => 'Pelajaran di sekolah terlalu berat',
                    2  => 'Pelajaran di sekolah terlalu mudah',
                    3  => 'Sukar mendapat buku-buku pelajaran',
                    4  => 'Saya takut terhadap ulangan',
                    5  => 'Saya tidak suka belajar',
                    6  => 'Saya mengerti isi buku pelajaran',
                    7  => 'Saya tidak berminat terhadap buku',
                    8  => 'Saya sering mendapat angka rendah',
                    9  => 'Saya tidak senang belajar bersama',
                    10 => 'Sukar menangkap dan menangkap pelajaran',
                    11 => 'Sering khawatir kalau-kalau mendapat giliran maju ke depan kelas',
                    12 => 'Sering mendapatkan kesukaran dalam menyelesaikan pekerjaan rumah',
                    13 => 'Pelajaran yang bersifat hitungan sukar bagiku',
                    14 => 'Pelajaran yang bersifat hafalan sukar bagiku',
                    15 => 'Merasa malas membaca buku perpustakaan',
                ],
            ],
            'jawaban_masa_depan' => [
                'label' => 'X. Masalah Masa Depan yang Berhubungan dengan Jabatan',
                'short' => 'Masa Depan',
                'questions' => [
                    1  => 'Saya tidak tahu berbuat apa setelah tamat SMU',
                    2  => 'Sukar menetapkan pilihan sekolah setelah tamat',
                    3  => 'Khawatir tidak diterima di sekolah lanjutan',
                    4  => 'Ingin melanjutkan ke sekolah yang lebih tinggi tetapi tidak ada biaya',
                    5  => 'Merasa pesimis terhadap hari depan karena sulitnya mencari pekerjaan',
                    6  => 'Khawatir nantinya tidak dapat mandiri',
                    7  => 'Ingin mengetahui bakat dan kemampuan diri',
                    8  => 'Cita-cita tidak sesuai dengan kemampuan',
                    9  => 'Bingung menetapkan sikap setelah lulus nanti',
                    10 => 'Sering berdebar-debar jika mengingat masa depan',
                    11 => 'Ayah Ibu keras dalam mengarahkan cita-cita',
                ],
            ],
            'jawaban_kebiasaan_belajar' => [
                'label' => 'XI. Masalah Kebiasaan dan Belajar',
                'short' => 'Kebiasaan Belajar',
                'questions' => [
                    1  => 'Belajar kalau ada ulangan',
                    2  => 'Belajar tidak teratur waktunya',
                    3  => 'Belajar hanya waktu malam hari',
                    4  => 'Belajar hanya waktu siang hari',
                    5  => 'Sukar memusatkan perhatian waktu belajar',
                    6  => 'Sukar mengingat pelajaran yang sudah dihafalkan',
                    7  => 'Sulit untuk memulai belajar',
                    8  => 'Kalau belajar sering mengantuk',
                    9  => 'Sering merasa malas belajar',
                    10 => 'Sering terganggu saudara kalau sedang belajar',
                    11 => 'Belajar dengan cara menghafal',
                    12 => 'Belajar dengan cara membayangkan',
                    13 => 'Belajar dengan cara membuat ringkasan singkatan',
                    14 => 'Tidak dapat menerapkan cara belajar yang baik',
                    15 => 'Sering menyalin pekerjaan teman',
                ],
            ],
            'jawaban_asmara' => [
                'label' => 'XII. Masalah Muda-Mudi dan Asmara',
                'short' => 'Asmara',
                'questions' => [
                    1  => 'Memikirkan masalah cinta adalah terlalu awal bagi saya',
                    2  => 'Pacaran adalah bagian hidup saya',
                    3  => 'Merasa malu membicarakan soal cinta',
                    4  => 'Pacaran dalam masa sekolah dapat menjadi dorongan',
                    5  => 'Pacaran dalam masa sekolah adalah dapat menghancurkan semangat sekolah',
                    6  => 'Saya mulai tertarik pada wanita/pria',
                    7  => 'Saya lebih tertarik pada teman sejenis',
                    8  => 'Saya pernah patah hati ditinggal pacar',
                    9  => 'Sering membayangkan adegan cinta',
                    10 => 'Tidak senang melihat film yang bernadakan cinta',
                    11 => 'Terpaksa pacaran dengan sembunyi-sembunyi',
                    12 => 'Merasa tidak senang jika ada orang yang membicarakan masalah cinta',
                    13 => 'Saya tidak dapat belajar jika dia tidak sms',
                    14 => 'Sering melamun memikirkan si dia',
                    15 => 'Saya ragu-ragu terhadap pacar saya',
                    16 => 'Orang tua saya melarang saya pacaran dulu',
                    17 => 'Pacarku selalu mengajak keluar rumah',
                    18 => 'Saya kesepian karena belum mempunyai pacar',
                    19 => 'Iri melihat kawan-kawan berpacaran',
                    20 => 'Memilih calon pacar sukar bagiku',
                    21 => 'Sering bertepuk sebelah tangan',
                    22 => 'Sukar bergaul dengan teman lawan jenis',
                    23 => 'Jodohku ditentukan orang tuaku',
                ],
            ],
        ];
    }
}
