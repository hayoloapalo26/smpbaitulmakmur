<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GuardianService
{
    /**
     * Bersihkan dan gabungkan semua data orang tua & akun user yang terduplikasi.
     */
    public static function deduplicateAll(): int
    {
        $mergedCount = 0;

        // 1. Penanganan Khusus untuk Evi Ernawati (dan variasi namanya)
        self::mergeSpecificParent('Evi Ernawati');

        // 2. Loop semua nama di tabel guardians yang duplikat per lembaga
        $duplicateNames = DB::table('guardians')
            ->selectRaw('LOWER(TRIM(name)) as clean_name, institution_id, COUNT(*) as total')
            ->groupBy(DB::raw('LOWER(TRIM(name))'), 'institution_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateNames as $dup) {
            $records = Guardian::where('institution_id', $dup->institution_id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [$dup->clean_name])
                ->orderBy('id')
                ->get();

            if ($records->count() <= 1) {
                continue;
            }

            // Tentukan kanonikal: yang sudah memiliki user, atau paling banyak anak, atau id terkecil
            $canonical = $records->first(fn ($g) => $g->user()->exists() || $g->has_account)
                ?? $records->sortByDesc(fn ($g) => $g->studentGuardians()->count())->first()
                ?? $records->first();

            foreach ($records as $other) {
                if ($other->id === $canonical->id) {
                    continue;
                }

                // Pindahkan student_guardians ke canonical
                DB::table('student_guardians')
                    ->where('guardian_id', $other->id)
                    ->update(['guardian_id' => $canonical->id]);

                // Pindahkan user yang terkait jika ada
                DB::table('users')
                    ->where('guardian_id', $other->id)
                    ->update(['guardian_id' => $canonical->id]);

                $other->delete();
                $mergedCount++;
            }
        }

        // 3. Bersihkan duplikasi di student_guardians
        self::cleanDuplicateStudentGuardians();

        // 4. Bersihkan duplikasi akun User role 'orang_tua'
        self::deduplicateParentUsers();

        // 5. Pastikan semua student_guardians memiliki user_id jika guardian memiliki akun
        $guardiansWithUser = Guardian::whereHas('user')->with('user')->get();
        foreach ($guardiansWithUser as $g) {
            if ($g->user) {
                DB::table('student_guardians')
                    ->where('guardian_id', $g->id)
                    ->update(['user_id' => $g->user->id]);
            }
        }

        return $mergedCount;
    }

    /**
     * Gabungkan spesifik nama orang tua (misal Evi Ernawati)
     */
    public static function mergeSpecificParent(string $parentName): void
    {
        $cleanName = strtolower(trim($parentName));

        // Ambil semua records di guardians yang cocok
        $guardians = Guardian::whereRaw('LOWER(TRIM(name)) LIKE ?', ["%{$cleanName}%"])->get();
        if ($guardians->isNotEmpty()) {
            $canonical = $guardians->first(fn ($g) => $g->user()->exists() || $g->has_account)
                ?? $guardians->first();

            $canonical->update(['name' => $parentName]);

            foreach ($guardians as $other) {
                if ($other->id === $canonical->id) {
                    continue;
                }

                DB::table('student_guardians')
                    ->where('guardian_id', $other->id)
                    ->update(['guardian_id' => $canonical->id]);

                DB::table('users')
                    ->where('guardian_id', $other->id)
                    ->update(['guardian_id' => $canonical->id]);

                $other->delete();
            }
        } else {
            $instId = 2;
            $sampleStudent = DB::table('students')
                ->whereRaw('LOWER(TRIM(nama_ibu)) LIKE ?', ["%{$cleanName}%"])
                ->orWhereRaw('LOWER(TRIM(nama_ayah)) LIKE ?', ["%{$cleanName}%"])
                ->orWhereRaw('LOWER(TRIM(nama_wali)) LIKE ?', ["%{$cleanName}%"])
                ->first();

            if ($sampleStudent) {
                $instId = $sampleStudent->institution_id ?: 2;
            }

            $canonical = Guardian::create([
                'name' => $parentName,
                'institution_id' => $instId,
                'phone' => '-',
                'address' => '-',
            ]);
        }

        // Ambil semua akun User yang cocok dengan nama ini
        $users = User::withoutGlobalScopes()
            ->where(function ($q) use ($cleanName) {
                $q->whereRaw('LOWER(TRIM(name)) LIKE ?', ["%{$cleanName}%"])
                  ->orWhereRaw('LOWER(TRIM(username)) LIKE ?', ["%{$cleanName}%"])
                  ->orWhereRaw('LOWER(TRIM(email)) LIKE ?', ["%evi%"]);
            })
            ->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'))
            ->orderBy('id')
            ->get();

        if ($users->isNotEmpty()) {
            // Ambil 1 user kanonikal
            $canonicalUser = $users->first();
            $canonicalUser->update([
                'name' => $parentName,
                'guardian_id' => $canonical->id,
                'is_active' => true,
                'has_account' => true,
            ]);

            // Hapus akun user duplikat lainnya
            foreach ($users as $otherUser) {
                if ($otherUser->id === $canonicalUser->id) {
                    continue;
                }
                DB::table('student_guardians')->where('user_id', $otherUser->id)->update(['user_id' => $canonicalUser->id]);
                $otherUser->delete();
            }

            $userId = $canonicalUser->id;
        } else {
            $userId = null;
        }

        // Hubungkan semua siswa yang memiliki orang tua ini di tabel students
        $students = DB::table('students')
            ->whereRaw('LOWER(TRIM(nama_ibu)) LIKE ?', ["%{$cleanName}%"])
            ->orWhereRaw('LOWER(TRIM(nama_ayah)) LIKE ?', ["%{$cleanName}%"])
            ->orWhereRaw('LOWER(TRIM(nama_wali)) LIKE ?', ["%{$cleanName}%"])
            ->get();

        foreach ($students as $s) {
            $existingPivot = DB::table('student_guardians')
                ->where('student_id', $s->id)
                ->where('guardian_id', $canonical->id)
                ->first();

            if (!$existingPivot) {
                DB::table('student_guardians')->insert([
                    'student_id'  => $s->id,
                    'guardian_id' => $canonical->id,
                    'user_id'     => $userId,
                    'relation'    => 'Ibu',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } else {
                DB::table('student_guardians')
                    ->where('id', $existingPivot->id)
                    ->update([
                        'user_id'    => $userId,
                        'relation'   => 'Ibu',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Bersihkan duplikasi student_guardians
     */
    public static function cleanDuplicateStudentGuardians(): void
    {
        $duplicates = DB::table('student_guardians')
            ->selectRaw('student_id, guardian_id, COUNT(*) as c, MIN(id) as keep_id')
            ->groupBy('student_id', 'guardian_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $d) {
            DB::table('student_guardians')
                ->where('student_id', $d->student_id)
                ->where('guardian_id', $d->guardian_id)
                ->where('id', '!=', $d->keep_id)
                ->delete();
        }
    }

    /**
     * Gabungkan user role orang_tua yang berduplikasi
     */
    public static function deduplicateParentUsers(): void
    {
        $dupUsers = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                     ->where('model_has_roles.model_type', '=', User::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'orang_tua')
            ->selectRaw('LOWER(TRIM(users.name)) as clean_name, COUNT(*) as c')
            ->groupBy(DB::raw('LOWER(TRIM(users.name))'))
            ->having('c', '>', 1)
            ->get();

        foreach ($dupUsers as $du) {
            $users = User::withoutGlobalScopes()
                ->whereHas('roles', fn ($q) => $q->where('name', 'orang_tua'))
                ->whereRaw('LOWER(TRIM(name)) = ?', [$du->clean_name])
                ->orderBy('id')
                ->get();

            if ($users->count() <= 1) {
                continue;
            }

            $keepUser = $users->first(fn ($u) => !empty($u->guardian_id)) ?? $users->first();

            foreach ($users as $otherUser) {
                if ($otherUser->id === $keepUser->id) {
                    continue;
                }

                DB::table('student_guardians')
                    ->where('user_id', $otherUser->id)
                    ->update(['user_id' => $keepUser->id]);

                $otherUser->delete();
            }
        }
    }
}
