<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPortalUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Superadmin is completely unrestricted, can visit any panel
            if ($user->hasRole('superadmin')) {
                // If they JUST logged in from a panel that isn't staff, forcefully redirect to staff
                $path = $request->path();
                $referer = request()->headers->get('referer');
                if (in_array($path, ['yayasan', 'kbtk', 'sd', 'smk', 'ma', 'ponpes', 'portal'])) {
                    if ($referer && str_ends_with($referer, '/login')) {
                        return redirect('/staff');
                    }
                }
                return $next($request);
            }

            // Check roles
            $hasYayasanRole = $user->hasAnyRole([
                'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',
                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan',
                'pembina_yayasan', 'pegawai_keuangan_yayasan'
            ]);
            // Hanya user yang memang bertugas di Yayasan (institution_id = 1) yang diarahkan ke /yayasan
            $isYayasan = ($hasYayasanRole && ($user->institution_id == 1 || in_array(1, $user->institution_ids)))
                || ($user->institution_id == 1 && !$user->hasAnyRole(['siswa', 'orang_tua']));

            $isTeacherOrStaff = $user->isQuranTeacher() || $user->hasAnyRole([
                'guru_mapel', 'guru_kelas', 'guru_piket', 'wali_kelas', 'koordinator_madin', 'guru_alquran'
            ]);
            $isPortalOnly = $user->hasAnyRole(['siswa', 'orang_tua']) && $user->roles->count() == 1;
            $isStaff = !$isYayasan && !$isPortalOnly; // Anyone else is considered staff (SMP, SD, KB/TK, dll.)

            // Path checks (excluding login/logout paths to prevent redirect loops)
            $path = $request->path();
            $isAuthRoute = str_ends_with($path, '/logout') || str_ends_with($path, '/login');

            if (!$isAuthRoute) {
                // Dual Role: Pengurus Yayasan yang juga Guru Pengajar (misal: Pak Romli - Ketua Yayasan & Guru Tahfidz Lanjutan)
                if ($isYayasan && $isTeacherOrStaff) {
                    // Berikan akses penuh ke portal yayasan dan portal staff/guru tanpa redirect paksa
                    $validDualPanels = ['yayasan*', 'staff*', 'kbtk*', 'sd*', 'smk*', 'ma*', 'ponpes*'];
                    foreach ($validDualPanels as $panelPattern) {
                        if ($request->is($panelPattern)) {
                            return $next($request);
                        }
                    }

                    // Jika berada di luar panel (misal root / atau /portal), arahkan sesuai referer atau default ke /yayasan
                    $referer = request()->headers->get('referer');
                    if ($referer && str_contains($referer, '/staff')) {
                        return redirect('/staff');
                    }
                    return redirect('/yayasan');
                }

                // Yayasan Roles Murni -> /yayasan
                if ($isYayasan && !$request->is('yayasan*')) {
                    return redirect('/yayasan');
                }

                // Portal Only -> /portal
                if ($isPortalOnly && !$request->is('portal*')) {
                    return redirect('/portal');
                }

                // Staff / Teachers
                if ($isStaff) {
                    // Seluruh staf unit lembaga (SMP ID=2, SD ID=4, KB/TK ID=3, dll.) mengakses portal staf terpadu di /staff
                    // di mana data siswa, rombel, jadwal, nilai, dan pilar otomatis terisolasi 100% berdasarkan institution_id user.
                    if ($request->is('staff*')) {
                        return $next($request);
                    }

                    // Jika staf mengakses panel lain di luar /staff (misal /sd, /kbtk), arahkan ke /staff
                    return redirect('/staff');
                }
            }
        }

        return $next($request);
    }
}

