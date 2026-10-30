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
            $isPortalOnly = $user->hasRole(['siswa', 'orang_tua']) && $user->roles->count() == 1;
            $isStaff = !$isYayasan && !$isPortalOnly; // Anyone else is considered staff

            // Path checks (excluding login/logout paths to prevent redirect loops)
            $path = $request->path();
            $isAuthRoute = str_ends_with($path, '/logout') || str_ends_with($path, '/login');

            if (!$isAuthRoute) {
                // Yayasan Roles -> /yayasan
                if ($isYayasan && !$request->is('yayasan*')) {
                    return redirect('/yayasan');
                }

                // Portal Only -> /portal
                if ($isPortalOnly && !$request->is('portal*')) {
                    return redirect('/portal');
                }

                // Staff / Teachers
                if ($isStaff) {
                    $targetPath = '/staff'; // Default to SMPI if no institution ID
                    
                    if ($user->institution_id == 3) $targetPath = '/kbtk';
                    elseif ($user->institution_id == 4) $targetPath = '/sd';
                    elseif ($user->institution_id == 2) $targetPath = '/staff';
                    elseif ($user->institution_id == 5) $targetPath = '/smk';
                    elseif ($user->institution_id == 6) $targetPath = '/ma';
                    elseif ($user->institution_id == 7) $targetPath = '/ponpes';
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

