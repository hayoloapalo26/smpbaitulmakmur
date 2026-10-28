<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Subject;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'institution_id')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
            });
        }

        $smpSubjects = [
            ['name' => 'SBDP', 'kode' => 'SBDP'],
            ['name' => 'BK', 'kode' => 'BK'],
            ['name' => 'Fiqih/Tarikh', 'kode' => 'FQH'],
            ['name' => 'Informatika', 'kode' => 'INF'],
            ['name' => 'Bahasa Indonesia', 'kode' => 'BIND'],
            ['name' => 'Matematika', 'kode' => 'MTK'],
            ['name' => 'Nahwu/Shorof', 'kode' => 'NHW'],
            ['name' => 'IPA', 'kode' => 'IPA'],
            ['name' => 'PJOK', 'kode' => 'PJOK'],
            ['name' => 'Tauhid/Akhlak', 'kode' => 'THD'],
            ['name' => 'Hadits/Pegon', 'kode' => 'HDT'],
            ['name' => 'IPS', 'kode' => 'IPS'],
            ['name' => 'Bahasa Jawa', 'kode' => 'BJAW'],
            ['name' => 'Bahasa Inggris', 'kode' => 'BING'],
            ['name' => 'PAI', 'kode' => 'PAI'],
            ['name' => 'PKN', 'kode' => 'PKN'],
            ['name' => 'Al-Qur’an', 'kode' => 'QRN'],
        ];

        foreach ($smpSubjects as $sub) {
            $existing = Subject::withoutGlobalScopes()
                ->where('name', $sub['name'])
                ->orWhere('kode', $sub['kode'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $sub['name'],
                    'kode' => $sub['kode'],
                ]);
            } else {
                Subject::withoutGlobalScopes()->create([
                    'name' => $sub['name'],
                    'kode' => $sub['kode'],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retain data integrity
    }
};
