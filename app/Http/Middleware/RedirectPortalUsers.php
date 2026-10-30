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
            $isYayasan = $user->hasAnyRole([
                'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',
                'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'tim_pengembang_yayasan',
                'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan'
            ]);
            $isTeacherOrStaff = $user->isQuranTeacher() || $user->hasAnyRole([
                'guru_mapel', 'guru_kelas', 'guru_piket', 'wali_kelas', 'koordinator_madin', 'guru_alquran'
            ]);
            $isPortalOnly = $user->hasRole(['siswa', 'orang_tua']) && $user->roles->count() == 1;
            $isStaff = !$isYayasan && !$isPortalOnly; // Anyone else is considered staff

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
                    $instMap = [
                        1 => 'yayasan*',
                        2 => 'staff*',
                        3 => 'kbtk*',
                        4 => 'sd*',
                        5 => 'smk*',
                        6 => 'ma*',
                        7 => 'ponpes*',
                    ];

                    $userInstIds = $user->institution_ids;
                    foreach ($userInstIds as $instId) {
                        if (isset($instMap[$instId]) && $request->is($instMap[$instId])) {
                            return $next($request);
                        }
                    }

                    $targetPath = '/staff'; // Default to SMPI if no institution ID
                    
                    if ($user->institution_id == 3) $targetPath = '/kbtk';
                    elseif ($user->institution_id == 4) $targetPath = '/sd';
                    elseif ($user->institution_id == 2) $targetPath = '/staff';
                    elseif ($user->institution_id == 5) $targetPath = '/smk';
                    elseif ($user->institution_id == 6) $targetPath = '/ma';
                    elseif ($user->institution_id == 7) $targetPath = '/ponpes';
                    elseif ($user->institution_id == 1) $targetPath = '/yayasan';
                    else {
                        // If null, allow them to stay on whichever staff panel they accessed, else default to /staff
                        $validStaffPanels = ['kbtk*', 'sd*', 'staff*', 'smk*', 'ma*', 'ponpes*'];
                        $isOnValidPanel = false;
                        foreach ($validStaffPanels as $panelStr) {
                            if ($request->is($panelStr)) {
                                $isOnValidPanel = true;
                                break;
                            }
                        }
                        if ($isOnValidPanel) return $next($request);
                    }
                    
                    // Redirect if they are not on their assigned target path
                    if (!$request->is(ltrim($targetPath, '/') . '*')) {
                        return redirect($targetPath);
                    }
                }
            }
        }

        return $next($request);
    }
}

