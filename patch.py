import re

with open('app/Filament/Staff/Pages/Dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

getPillarsData_replacement = '''
    public function getPillarsDataProperty()
    {
        \ = Auth::user();
        
        \ = [];
        if (\->hasRole('kepala_sekolah') || \->hasRole('superadmin')) \[] = 'KS';
        if (\->hasRole('waka_kurikulum')) \[] = 'WKr';
        if (\->hasRole('waka_kesiswaan')) \[] = 'WKs';
        if (\->hasRole('sarpras')) \[] = 'SP';
        if (\->hasRole('humas')) \[] = 'Hm';
        if (\->hasRole('bk')) \[] = 'BK';
        if (\->hasRole('tata_usaha')) \[] = 'TU';

        \ = in_array('KS', \);

        \ = \App\Models\SchoolPillar::orderBy('order')->with(['tasks' => function(\) {
            \->orderBy('order');
        }])->get();

        if (!\ && count(\) === 0) {
            \ = collect();
        }

        \ = [];
        \ = 0;
        \ = 0;
        
        foreach (\ as \) {
            \ = \->tasks;

            // ---------------------------------------------------------
            // OVERRIDE WITH DYNAMIC MODULE DATA BASED ON PILLAR
            // ---------------------------------------------------------
            if (\->order == 1) { // Manajemen (Kepala Sekolah)
                \ = \App\Models\WorkProgram::all()->map(function(\) {
                    return (object)[
                        'id' => 'dynamic_ks_' . \->id,
                        'name' => \->nama_program,
                        'status' => \->status_pelaksanaan === 'selesai' ? 'selesai' : 'belum',
                        'code' => 'KS',
                        'assigned_role' => 'KS',
                        'is_sop_merah' => false,
                    ];
                });
            } elseif (\->order == 3) { // Kurikulum
                \ = \App\Models\ProgramKurikulum::all()->map(function(\) {
                    return (object)[
                        'id' => 'dynamic_kurikulum_' . \->id,
                        'name' => \->nama_program,
                        'status' => \->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKr',
                        'assigned_role' => 'WKr',
                        'is_sop_merah' => false,
                    ];
                });
            } elseif (\->order == 5) { // Kesiswaan
                \ = \App\Models\ProgramKesiswaan::all()->map(function(\) {
                    return (object)[
                        'id' => 'dynamic_kesiswaan_' . \->id,
                        'name' => \->nama_program,
                        'status' => \->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKs',
                        'assigned_role' => 'WKs',
                        'is_sop_merah' => false,
                    ];
                });
            } elseif (\->order == 8) { // Humas
                \ = \App\Models\HumasProgram::all()->map(function(\) {
                    return (object)[
                        'id' => 'dynamic_humas_' . \->id,
                        'name' => \->nama,
                        'status' => \->status === 'sudah' ? 'selesai' : 'belum',
                        'code' => 'Hm',
                        'assigned_role' => 'Hm',
                        'is_sop_merah' => false,
                    ];
                });
            } elseif (\->order == 9) { // BK
                \ = \App\Models\BkWorkProgram::all()->map(function(\) {
                    return (object)[
                        'id' => 'dynamic_bk_' . \->id,
                        'name' => \->judul_program,
                        'status' => \->status === 'Terlaksana' ? 'selesai' : 'belum',
                        'code' => 'BK',
                        'assigned_role' => 'BK',
                        'is_sop_merah' => false,
                    ];
                });
            }

            // If user is not KS, filter out tasks that are not assigned to them
            if (!\ && count(\) > 0) {
                \ = collect(\)->filter(function(\) use (\) {
                    foreach (\ as \) {
                        if (str_contains(\->assigned_role, \)) {
                            return true;
                        }
                    }
                    return false;
                });
            }
            
            // Skip pillars that have 0 tasks for this user
            if (!\ && collect(\)->count() == 0) {
                continue;
            }

            \ = collect(\)->count();
            \ = collect(\)->where('status', 'selesai')->count();
            \ = \ > 0 ? round((\ / \) * 100) : 0;

            \ += \;
            \ += \;

            \[] = [
                'id' => \->id,
                'order' => \->order,
                'name' => \->name,
                'total' => \,
                'selesai' => \,
                'percentage' => \,
                'tasks' => \,
            ];
        }

        return \;
    }
'''

content = re.sub(r'public function getPillarsDataProperty\(\).*?return \;\n    }', getPillarsData_replacement, content, flags=re.DOTALL)


markTask_replacement = '''
    public function markTask(\, \)
    {
        \ = Auth::user();
        \ = [];
        if (\->hasRole('kepala_sekolah') || \->hasRole('superadmin')) \[] = 'KS';
        if (\->hasRole('waka_kurikulum')) \[] = 'WKr';
        if (\->hasRole('waka_kesiswaan')) \[] = 'WKs';
        if (\->hasRole('sarpras')) \[] = 'SP';
        if (\->hasRole('humas')) \[] = 'Hm';
        if (\->hasRole('bk')) \[] = 'BK';
        if (\->hasRole('tata_usaha')) \[] = 'TU';

        \ = in_array('KS', \);

        // HANDLE DYNAMIC TASKS
        if (is_string(\) && str_starts_with(\, 'dynamic_')) {
            \ = explode('_', \);
            \ = \[1]; // 'kurikulum', 'kesiswaan', 'humas', 'bk', 'ks'
            \ = \[2];
            
            \ = \;
            if (!\) {
                if (\ == 'kurikulum' && in_array('WKr', \)) \ = true;
                if (\ == 'kesiswaan' && in_array('WKs', \)) \ = true;
                if (\ == 'humas' && in_array('Hm', \)) \ = true;
                if (\ == 'bk' && in_array('BK', \)) \ = true;
                if (\ == 'ks' && in_array('KS', \)) \ = true;
            }

            if (\) {
                if (\ == 'kurikulum') {
                    \ = \App\Models\ProgramKurikulum::find(\);
                    if (\) { \->status_pelaksanaan = \ === 'selesai' ? 'Selesai' : 'Sedang Berjalan'; \->save(); }
                } elseif (\ == 'kesiswaan') {
                    \ = \App\Models\ProgramKesiswaan::find(\);
                    if (\) { \->status_pelaksanaan = \ === 'selesai' ? 'Selesai' : 'Sedang Berjalan'; \->save(); }
                } elseif (\ == 'humas') {
                    \ = \App\Models\HumasProgram::find(\);
                    if (\) { \->status = \ === 'selesai' ? 'sudah' : 'belum'; \->save(); }
                } elseif (\ == 'bk') {
                    \ = \App\Models\BkWorkProgram::find(\);
                    if (\) { \->status = \ === 'selesai' ? 'Terlaksana' : 'Sedang Berjalan'; \->save(); }
                } elseif (\ == 'ks') {
                    \ = \App\Models\WorkProgram::find(\);
                    if (\) { \->status_pelaksanaan = \ === 'selesai' ? 'selesai' : 'berjalan'; \->save(); }
                }

                \Filament\Notifications\Notification::make()
                    ->title('Status Program Diperbarui')
                    ->body('Status program terkait telah berhasil disinkronisasi.')
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki wewenang untuk mengubah status program ini.')
                    ->danger()
                    ->send();
            }
            return;
        }

        // HANDLE STATIC SOP TASKS
        \ = \App\Models\SchoolPillarTask::find(\);
        if (\) {
            \ = \;
            
            if (!\) {
                foreach (\ as \) {
                    if (str_contains(\->assigned_role, \)) {
                        \ = true;
                        break;
                    }
                }
            }

            if (\) {
                \->update([
                    'status' => \,
                    'completed_at' => \ === 'selesai' ? now() : null,
                    'completed_by' => \ === 'selesai' ? Auth::id() : null,
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Status SOP Diperbarui')
                    ->body('Progres ketercapaian pilar berhasil disinkronisasi.')
                    ->success()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki wewenang untuk menyelesaikan tugas ini.')
                    ->danger()
                    ->send();
            }
        }
    }
'''

content = re.sub(r'public function markTask\(\, \\)\n    \{.*?\n    \}', markTask_replacement, content, flags=re.DOTALL)

with open('app/Filament/Staff/Pages/Dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Replaced functions.")
