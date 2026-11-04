<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\Extracurricular;
use App\Models\User;
use App\Models\Institution;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan Role guru_ekstrakurikuler ada
        Role::firstOrCreate(['name' => 'guru_ekstrakurikuler', 'guard_name' => 'web']);

        // 2. Modifikasi tabel extracurriculars
        if (Schema::hasTable('extracurriculars')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                if (!Schema::hasColumn('extracurriculars', 'hari')) {
                    $table->string('hari')->nullable()->after('name');
                }
                if (!Schema::hasColumn('extracurriculars', 'jam_mulai')) {
                    $table->string('jam_mulai', 10)->nullable()->after('hari');
                }
                if (!Schema::hasColumn('extracurriculars', 'jam_selesai')) {
                    $table->string('jam_selesai', 10)->nullable()->after('jam_mulai');
                }
                if (!Schema::hasColumn('extracurriculars', 'lokasi')) {
                    $table->string('lokasi')->nullable()->after('jam_selesai');
                }
                if (!Schema::hasColumn('extracurriculars', 'deskripsi')) {
                    $table->text('deskripsi')->nullable()->after('lokasi');
                }
                if (!Schema::hasColumn('extracurriculars', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('deskripsi');
                }
                if (!Schema::hasColumn('extracurriculars', 'institution_id')) {
                    $table->foreignId('institution_id')->nullable()->after('pembina_id')->constrained('institutions')->nullOnDelete();
                }
            });
        }

        // 3. Modifikasi tabel extracurricular_attendances
        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('extracurricular_attendances', 'catatan')) {
                    $table->string('catatan')->nullable()->after('status');
                }
                if (!Schema::hasColumn('extracurricular_attendances', 'pembina_id')) {
                    $table->foreignId('pembina_id')->nullable()->after('catatan')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('extracurricular_attendances', 'institution_id')) {
                    $table->foreignId('institution_id')->nullable()->after('pembina_id')->constrained('institutions')->nullOnDelete();
                }
            });
        }

        // 4. Seed 6 Ekstrakurikuler Default SMP (Hanya jika tabel users & institutions sudah memiliki data yang valid)
        $pembina = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['guru_ekstrakurikuler', 'guru_mapel', 'waka_kesiswaan', 'superadmin']))->first() 
            ?? User::first();

        $institution = Institution::where('id', 2)->orWhere('slug', 'like', '%smp%')->first() 
            ?? Institution::first();

        if (!$pembina || !$institution) {
            return;
        }

        $institutionId = $institution->id;
        $pembinaId = $pembina->id;

        $defaultEskuls = [
            [
                'name' => 'Pramuka',
                'hari' => 'Jumat',
                'jam_mulai' => '15:00',
                'jam_selesai' => '17:00',
                'lokasi' => 'Lapangan Utama',
                'deskripsi' => 'Ekstrakurikuler wajib kepanduan untuk melatih kedisiplinan, kemandirian, dan kepemimpinan.',
            ],
            [
                'name' => 'ECC',
                'hari' => 'Rabu',
                'jam_mulai' => '15:30',
                'jam_selesai' => '17:00',
                'lokasi' => 'Lab Bahasa / Ruang Kelas',
                'deskripsi' => 'English Conversation Club untuk melatih kemampuan percakapan dan debat bahasa Inggris.',
            ],
            [
                'name' => 'Futsal',
                'hari' => 'Kamis',
                'jam_mulai' => '15:30',
                'jam_selesai' => '17:00',
                'lokasi' => 'Lapangan Futsal',
                'deskripsi' => 'Ekstrakurikuler olahraga futsal untuk mengembangkan bakat fisik, kerja sama tim, dan sportivitas.',
            ],
            [
                'name' => 'Bulutangkis',
                'hari' => 'Selasa',
                'jam_mulai' => '15:30',
                'jam_selesai' => '17:00',
                'lokasi' => 'GOR / Aula Sekolah',
                'deskripsi' => 'Pelatihan teknik dasar dan lanjutan permainan bulutangkis.',
            ],
            [
                'name' => 'Teater',
                'hari' => 'Sabtu',
                'jam_mulai' => '13:00',
                'jam_selesai' => '15:00',
                'lokasi' => 'Aula Serbaguna',
                'deskripsi' => 'Pengembangan seni peran, olah vokal, percaya diri, dan apresiasi sastra pertunjukan.',
            ],
            [
                'name' => 'Pagar Nusa',
                'hari' => 'Minggu',
                'jam_mulai' => '07:30',
                'jam_selesai' => '09:30',
                'lokasi' => 'Halaman Utama Sekolah',
                'deskripsi' => 'Seni bela diri pencak silat Pagar Nusa untuk kebugaran fisik, bela diri, dan pembentukan akhlak.',
            ],
        ];

        foreach ($defaultEskuls as $eskul) {
            $existing = Extracurricular::where('name', $eskul['name'])->first();
            if ($existing) {
                $existing->update([
                    'hari' => $existing->hari ?: $eskul['hari'],
                    'jam_mulai' => $existing->jam_mulai ?: $eskul['jam_mulai'],
                    'jam_selesai' => $existing->jam_selesai ?: $eskul['jam_selesai'],
                    'lokasi' => $existing->lokasi ?: $eskul['lokasi'],
                    'deskripsi' => $existing->deskripsi ?: $eskul['deskripsi'],
                    'institution_id' => $existing->institution_id ?: $institutionId,
                    'is_active' => true,
                ]);
            } else {
                Extracurricular::create([
                    'name' => $eskul['name'],
                    'hari' => $eskul['hari'],
                    'jam_mulai' => $eskul['jam_mulai'],
                    'jam_selesai' => $eskul['jam_selesai'],
                    'lokasi' => $eskul['lokasi'],
                    'deskripsi' => $eskul['deskripsi'],
                    'pembina_id' => $pembinaId,
                    'institution_id' => $institutionId,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('extracurriculars')) {
            Schema::table('extracurriculars', function (Blueprint $table) {
                $cols = ['hari', 'jam_mulai', 'jam_selesai', 'lokasi', 'deskripsi', 'is_active', 'institution_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('extracurriculars', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                $cols = ['catatan', 'pembina_id', 'institution_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('extracurricular_attendances', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
