<?php

namespace Tests\Feature;

use App\Models\GeneralComplaint;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use App\Notifications\NewSigapComplaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SigapTest extends TestCase
{
    use RefreshDatabase;

    protected User $pegawai;

    protected User $umum;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Team::create(['id' => 1, 'fungsi' => 'Umum']);
        $this->room = Room::create(['id' => 4, 'ruang' => 'Ruang Rapat']);

        $this->pegawai = User::create([
            'nip_lama' => 340011248, 'name' => 'Pegawai Biasa', 'email' => 'pegawai@example.com',
            'password' => bcrypt('password'), 'fungsi' => 1, 'id_ruang' => 4,
        ]);
        $this->umum = User::create([
            'nip_lama' => 340011249, 'name' => 'Petugas Umum', 'email' => 'umum@example.com',
            'password' => bcrypt('password'), 'fungsi' => 1, 'id_ruang' => 4, 'is_umum' => 1,
        ]);
    }

    private function makeComplaint(array $attributes = []): GeneralComplaint
    {
        return GeneralComplaint::create($attributes + [
            'reported_by' => $this->pegawai->nip_lama,
            'room_id' => $this->room->id,
            'category' => 'AC rusak',
            'description' => 'AC tidak dingin sama sekali',
        ]);
    }

    public function test_pegawai_can_submit_complaint_and_umum_is_notified(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->pegawai)->post(route('general.sigap.store'), [
            'room_id' => $this->room->id,
            'location' => 'Dekat jendela',
            'category' => 'AC rusak',
            'description' => 'AC bocor dan meneteskan air',
        ]);

        $complaint = GeneralComplaint::firstOrFail();
        $response->assertRedirect(route('general.sigap.history.show', $complaint));
        $this->assertMatchesRegularExpression('/^SGP-[A-Z0-9]{6}$/', $complaint->ticket_id);
        $this->assertSame('menunggu', $complaint->status);
        Notification::assertSentTo($this->umum, NewSigapComplaint::class);
        Notification::assertNotSentTo($this->pegawai, NewSigapComplaint::class);
    }

    public function test_complaint_rejects_unknown_category_and_room(): void
    {
        $this->actingAs($this->pegawai)->post(route('general.sigap.store'), [
            'room_id' => 999,
            'category' => 'Bukan kategori',
            'description' => 'Deskripsi yang valid',
        ])->assertSessionHasErrors(['room_id', 'category']);

        $this->assertSame(0, GeneralComplaint::count());
    }

    public function test_reporter_sees_own_history_but_not_others_detail(): void
    {
        $complaint = $this->makeComplaint();

        $this->actingAs($this->pegawai)->get(route('general.sigap.history'))
            ->assertOk()->assertSee($complaint->ticket_id);
        $this->actingAs($this->pegawai)->get(route('general.sigap.history.show', $complaint))
            ->assertOk()->assertSee('Ruang Rapat');

        $this->actingAs($this->umum)->get(route('general.sigap.history.show', $complaint))->assertForbidden();
    }

    public function test_regular_user_cannot_access_admin_pages(): void
    {
        $complaint = $this->makeComplaint();

        $this->actingAs($this->pegawai)->get(route('umum.complaints'))->assertRedirect();
        $this->actingAs($this->pegawai)->patch(route('umum.complaints.update', $complaint), ['status' => 'selesai'])->assertRedirect();
        $this->assertSame('menunggu', $complaint->fresh()->status);
    }

    public function test_umum_can_filter_and_follow_up_complaint(): void
    {
        $open = $this->makeComplaint();
        $done = $this->makeComplaint(['category' => 'Listrik', 'status' => 'selesai']);

        $this->actingAs($this->umum)->get(route('umum.complaints', ['status' => 'menunggu']))
            ->assertOk()->assertSee($open->ticket_id)->assertDontSee($done->ticket_id);

        $this->actingAs($this->umum)->get(route('umum.complaints.show', $open))->assertOk();

        $this->actingAs($this->umum)->patch(route('umum.complaints.update', $open), [
            'status' => 'selesai',
            'admin_notes' => 'Freon AC sudah diisi ulang.',
        ])->assertRedirect(route('umum.complaints.show', $open));

        $open->refresh();
        $this->assertSame('selesai', $open->status);
        $this->assertSame($this->umum->nip_lama, (int) $open->handled_by);
        $this->assertNotNull($open->resolved_at);

        $this->actingAs($this->pegawai)->get(route('general.sigap.history.show', $open))
            ->assertSee('Freon AC sudah diisi ulang.');
    }

    public function test_rejecting_requires_notes(): void
    {
        $complaint = $this->makeComplaint();

        $this->actingAs($this->umum)->patch(route('umum.complaints.update', $complaint), ['status' => 'ditolak'])
            ->assertSessionHasErrors('admin_notes');
        $this->assertSame('menunggu', $complaint->fresh()->status);
    }

    public function test_umum_user_can_open_notifications_page(): void
    {
        $complaint = $this->makeComplaint();
        $this->umum->notify(new NewSigapComplaint($complaint->load(['reporter', 'room'])));

        $this->actingAs($this->umum)->get(route('admin.notifications.index'))
            ->assertOk()->assertSee($complaint->ticket_id);
    }
}
