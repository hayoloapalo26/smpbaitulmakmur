<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnouncementNotificationBellTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;
    protected User $teacher;
    protected User $principal;
    protected User $humas;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'guru_mapel', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kepala_sekolah', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'humas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'yayasan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->institution = Institution::firstOrCreate(
            ['slug' => 'smp-islam-baitul-makmur'],
            ['name' => 'SMP Islam Baitul Makmur']
        );

        $this->teacher = User::create([
            'name' => 'Guru Matematika',
            'username' => 'guru_matematika',
            'email' => 'guru_matematika@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->teacher->assignRole('guru_mapel');

        $this->principal = User::create([
            'name' => 'Kepala Sekolah',
            'username' => 'kepsek_test',
            'email' => 'kepsek@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->principal->assignRole('kepala_sekolah');

        $this->humas = User::create([
            'name' => 'Staf Humas',
            'username' => 'humas_test',
            'email' => 'humas@example.com',
            'password' => bcrypt('password'),
            'institution_id' => $this->institution->id,
            'is_active' => true,
        ]);
        $this->humas->assignRole('humas');
    }

    public function test_published_news_creation_triggers_database_notification_for_target_users(): void
    {
        $this->assertEquals(0, $this->teacher->unreadNotifications()->count());
        $this->assertEquals(0, $this->principal->unreadNotifications()->count());

        $news = News::create([
            'title' => 'Rapat Pleno Awal Semester Genap 2026',
            'content' => '<p>Diberitahukan kepada seluruh dewan guru bahwa rapat pleno akan dilaksanakan besok pagi.</p>',
            'author_id' => $this->humas->id,
            'target_institutions' => [(string) $this->institution->id],
            'target_roles' => ['guru_mapel', 'kepala_sekolah'],
            'is_published' => true,
        ]);

        $this->assertEquals(1, $this->teacher->unreadNotifications()->count(), 'Guru harus menerima 1 notifikasi belum dibaca');
        $this->assertEquals(1, $this->principal->unreadNotifications()->count(), 'Kepsek harus menerima 1 notifikasi belum dibaca');

        $notification = $this->teacher->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Rapat Pleno', $notification->data['title']);
        $this->assertEquals('filament', $notification->data['format']);
        $this->assertEquals($news->id, $notification->data['viewData']['news_id']);
    }

    public function test_marking_notification_as_read_decreases_unread_count_while_retaining_history(): void
    {
        $news = News::create([
            'title' => 'Libur Nasional & Jadwal Pengganti',
            'content' => '<p>Pengumuman terkait libur nasional peringatan hari besar.</p>',
            'author_id' => $this->humas->id,
            'target_institutions' => [],
            'target_roles' => [],
            'is_published' => true,
        ]);

        // Awal: 1 unread notification
        $this->assertEquals(1, $this->teacher->unreadNotifications()->count());
        $this->assertEquals(1, $this->teacher->notifications()->count());

        // Pengguna membaca notifikasi (Tandai Dibaca / Read)
        $notification = $this->teacher->unreadNotifications()->first();
        $notification->markAsRead();

        // Setelah dibaca: unread count berkurang menjadi 0
        $this->assertEquals(0, $this->teacher->unreadNotifications()->count(), 'Jumlah notifikasi belum dibaca harus berkurang menjadi 0');

        // History notifikasi tetap ada di database
        $this->assertEquals(1, $this->teacher->notifications()->count(), 'History notifikasi tetap tersimpan di riwayat pengguna');
        $this->assertNotNull($notification->fresh()->read_at, 'Kolom read_at harus terisi timestamp waktu dibaca');
    }

    public function test_draft_news_does_not_send_notification_until_published(): void
    {
        $news = News::create([
            'title' => 'Draf Pengumuman Kenaikan Kelas',
            'content' => '<p>Masih dalam tahap penyusunan draf pengumuman.</p>',
            'author_id' => $this->humas->id,
            'target_institutions' => [],
            'target_roles' => [],
            'is_published' => false,
        ]);

        // Karena masih draf, tidak ada notifikasi yang dikirim
        $this->assertEquals(0, $this->teacher->notifications()->count());

        // Saat pengumuman diterbitkan (diubah ke published = true)
        $news->update(['is_published' => true]);

        // Pengguna sekarang menerima notifikasi
        $this->assertEquals(1, $this->teacher->unreadNotifications()->count());
    }

    public function test_duplicate_notifications_are_prevented_on_simple_edits(): void
    {
        $news = News::create([
            'title' => 'Upacara Hari Pahlawan',
            'content' => '<p>Harap memakai seragam batik resmi.</p>',
            'author_id' => $this->humas->id,
            'is_published' => true,
        ]);

        $this->assertEquals(1, $this->teacher->notifications()->count());

        // Mengedit isi pengumuman untuk memperbaiki typo
        $news->update([
            'content' => '<p>Harap memakai seragam batik resmi dan peci hitam.</p>',
        ]);

        // Tidak boleh mengirim notifikasi duplikat ke pengguna yang sudah menerima
        $this->assertEquals(1, $this->teacher->notifications()->count());
    }

    public function test_broadcast_news_notifications_artisan_command(): void
    {
        $news = News::create([
            'title' => 'Surat Edaran Akhir Tahun',
            'content' => '<p>Rincian agenda kegiatan libur akhir tahun.</p>',
            'author_id' => $this->humas->id,
            'is_published' => true,
        ]);

        // Hapus notifikasi yang terkirim dari observer untuk mensimulasikan data lama
        $this->teacher->notifications()->delete();
        $this->assertEquals(0, $this->teacher->notifications()->count());

        // Jalankan perintah artisan
        Artisan::call('news:broadcast-notifications');

        // Notifikasi berhasil disiarkan kembali ke pengguna
        $this->assertEquals(1, $this->teacher->unreadNotifications()->count());
    }
}
