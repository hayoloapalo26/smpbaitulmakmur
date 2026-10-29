<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Capaian Pembelajaran (CP)
        if (!Schema::hasTable('capaian_pembelajarans')) {
            Schema::create('capaian_pembelajarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->string('fase')->default('Fase D (SMP)');
                $table->string('tingkat')->default('7'); // 7, 8, 9
                $table->string('tahun_ajaran')->default('2026/2027');
                $table->string('semester')->default('1'); // 1 (Ganjil), 2 (Genap), 1 Tahun
                $table->string('masa_berlaku')->default('1 Tahun'); // 1 Semester / 1 Tahun
                $table->string('elemen')->nullable(); // Elemen / Lingkup Materi
                $table->text('deskripsi_cp');
                $table->timestamps();
            });
        }

        // 2. Tabel Tujuan Pembelajaran (TP)
        if (!Schema::hasTable('tujuan_pembelajarans')) {
            Schema::create('tujuan_pembelajarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('capaian_pembelajaran_id')->constrained('capaian_pembelajarans')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->string('kode_tp'); // Contoh: TP.7.1, TP.7.2
                $table->text('deskripsi_tp'); // Rumusan TP
                $table->string('materi')->nullable(); // Materi Pokok
                $table->string('kelas_tingkat')->nullable(); // 7, 8, 9
                $table->string('semester')->default('Ganjil'); // Ganjil, Genap, 1 Tahun
                $table->timestamps();
            });
        }

        // 3. Tabel Alur Tujuan Pembelajaran (ATP) - Akumulasi dari TP
        if (!Schema::hasTable('alur_tujuan_pembelajarans')) {
            Schema::create('alur_tujuan_pembelajarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->string('judul_atp');
                $table->string('fase')->default('Fase D (SMP)');
                $table->string('tingkat')->default('7');
                $table->string('tahun_ajaran')->default('2026/2027');
                $table->string('semester')->default('Ganjil');
                $table->json('tp_ids')->nullable(); // Akumulasi TP yang dipilih berurutan
                $table->json('urutan_alur')->nullable(); // Detail susunan alur (tahapan, alokasi JP, catatan)
                $table->integer('total_alokasi_jp')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 4. Update kolom pada tabel RPPM
        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (!Schema::hasColumn('rppms', 'capaian_pembelajaran_id')) {
                    $table->foreignId('capaian_pembelajaran_id')->nullable()->after('mata_pelajaran')->constrained('capaian_pembelajarans')->nullOnDelete();
                }
                if (!Schema::hasColumn('rppms', 'alur_tujuan_pembelajaran_id')) {
                    $table->foreignId('alur_tujuan_pembelajaran_id')->nullable()->after('capaian_pembelajaran_id')->constrained('alur_tujuan_pembelajarans')->nullOnDelete();
                }
                if (!Schema::hasColumn('rppms', 'selected_tp_ids')) {
                    $table->json('selected_tp_ids')->nullable()->after('alur_tujuan_pembelajaran_id');
                }
            });
        }

        // 5. Update dan penambahan kolom pada tabel Grades (Nilai, Remedial, Pengayaan)
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                // Ubah type menjadi string jika belum
                $table->string('type')->change();

                if (!Schema::hasColumn('grades', 'classroom_id')) {
                    $table->foreignId('classroom_id')->nullable()->after('student_id')->constrained('classrooms')->nullOnDelete();
                }
                if (!Schema::hasColumn('grades', 'teacher_id')) {
                    $table->foreignId('teacher_id')->nullable()->after('subject_id')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('grades', 'tanggal')) {
                    $table->date('tanggal')->nullable()->after('teacher_id');
                }
                if (!Schema::hasColumn('grades', 'custom_type_name')) {
                    $table->string('custom_type_name')->nullable()->after('type');
                }
                if (!Schema::hasColumn('grades', 'bentuk_penilaian')) {
                    $table->string('bentuk_penilaian')->nullable()->after('custom_type_name');
                }
                if (!Schema::hasColumn('grades', 'custom_bentuk_penilaian')) {
                    $table->string('custom_bentuk_penilaian')->nullable()->after('bentuk_penilaian');
                }
                if (!Schema::hasColumn('grades', 'materi')) {
                    $table->text('materi')->nullable()->after('custom_bentuk_penilaian');
                }
                if (!Schema::hasColumn('grades', 'tp_ids')) {
                    $table->json('tp_ids')->nullable()->after('materi');
                }
                if (!Schema::hasColumn('grades', 'catatan_guru')) {
                    $table->text('catatan_guru')->nullable()->after('score');
                }

                // Kolom Fitur Remedial
                if (!Schema::hasColumn('grades', 'is_remedial')) {
                    $table->boolean('is_remedial')->default(false)->after('catatan_guru');
                }
                if (!Schema::hasColumn('grades', 'tanggal_remedial')) {
                    $table->date('tanggal_remedial')->nullable()->after('is_remedial');
                }
                if (!Schema::hasColumn('grades', 'nilai_remedial')) {
                    $table->float('nilai_remedial')->nullable()->after('tanggal_remedial');
                }
                if (!Schema::hasColumn('grades', 'catatan_remedial')) {
                    $table->text('catatan_remedial')->nullable()->after('nilai_remedial');
                }

                // Kolom Fitur Pengayaan (Khusus Siswa Nilai >= 95)
                if (!Schema::hasColumn('grades', 'is_pengayaan')) {
                    $table->boolean('is_pengayaan')->default(false)->after('catatan_remedial');
                }
                if (!Schema::hasColumn('grades', 'tp_pengayaan_id')) {
                    $table->foreignId('tp_pengayaan_id')->nullable()->after('is_pengayaan')->constrained('tujuan_pembelajarans')->nullOnDelete();
                }
                if (!Schema::hasColumn('grades', 'upgraded_tp_description')) {
                    $table->text('upgraded_tp_description')->nullable()->after('tp_pengayaan_id');
                }
                if (!Schema::hasColumn('grades', 'bentuk_pengayaan')) {
                    $table->string('bentuk_pengayaan')->nullable()->after('upgraded_tp_description');
                }
                if (!Schema::hasColumn('grades', 'custom_bentuk_pengayaan')) {
                    $table->string('custom_bentuk_pengayaan')->nullable()->after('bentuk_pengayaan');
                }
                if (!Schema::hasColumn('grades', 'link_drive_pengayaan')) {
                    $table->string('link_drive_pengayaan', 2048)->nullable()->after('custom_bentuk_pengayaan');
                }
                if (!Schema::hasColumn('grades', 'tanggal_pengayaan')) {
                    $table->date('tanggal_pengayaan')->nullable()->after('link_drive_pengayaan');
                }
                if (!Schema::hasColumn('grades', 'nilai_pengayaan')) {
                    $table->float('nilai_pengayaan')->nullable()->after('tanggal_pengayaan');
                }
                if (!Schema::hasColumn('grades', 'catatan_pengayaan')) {
                    $table->text('catatan_pengayaan')->nullable()->after('nilai_pengayaan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                $columns = [
                    'classroom_id', 'teacher_id', 'tanggal', 'custom_type_name',
                    'bentuk_penilaian', 'custom_bentuk_penilaian', 'materi', 'tp_ids', 'catatan_guru',
                    'is_remedial', 'tanggal_remedial', 'nilai_remedial', 'catatan_remedial',
                    'is_pengayaan', 'tp_pengayaan_id', 'upgraded_tp_description', 'bentuk_pengayaan',
                    'custom_bentuk_pengayaan', 'link_drive_pengayaan', 'tanggal_pengayaan',
                    'nilai_pengayaan', 'catatan_pengayaan'
                ];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('grades', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('rppms')) {
            Schema::table('rppms', function (Blueprint $table) {
                if (Schema::hasColumn('rppms', 'selected_tp_ids')) $table->dropColumn('selected_tp_ids');
                if (Schema::hasColumn('rppms', 'alur_tujuan_pembelajaran_id')) $table->dropConstrainedForeignId('alur_tujuan_pembelajaran_id');
                if (Schema::hasColumn('rppms', 'capaian_pembelajaran_id')) $table->dropConstrainedForeignId('capaian_pembelajaran_id');
            });
        }

        Schema::dropIfExists('alur_tujuan_pembelajarans');
        Schema::dropIfExists('tujuan_pembelajarans');
        Schema::dropIfExists('capaian_pembelajarans');
    }
};
