use App\Models\User;
use App\Models\TeacherAttendance;
use Carbon\Carbon;

$teachers = User::role(['guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'waka_kesiswaan', 'kepala_sekolah'])->get();
$statuses = ['hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'alpa']; // weighted towards hadir

foreach ($teachers as $teacher) {
    for ($i = 0; $i < 10; $i++) {
        $date = Carbon::today()->subDays($i)->format('Y-m-d');
        // skip weekends if you want, but for dummy data it's fine
        
        TeacherAttendance::updateOrCreate(
            ['teacher_id' => $teacher->id, 'tanggal' => $date],
            ['status' => $statuses[array_rand($statuses)]]
        );
    }
}
echo 'Dummy presensi guru (Teacher Attendance) 10 hari terakhir berhasil dibuat untuk ' . $teachers->count() . ' guru!';
