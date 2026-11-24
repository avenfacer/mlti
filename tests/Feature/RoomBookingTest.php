<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\MaintenanceBlock;
use App\Models\MeetingRoom;
use App\Models\Room;
use App\Models\Team;
use App\Models\User;
use App\Notifications\RoomBookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoomBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    private User $umum;

    private MeetingRoom $aula;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 08:00:00');

        Team::create(['id' => 1, 'fungsi' => 'Umum']);
        Room::create(['id' => 1, 'ruang' => 'Ruang Aula']);
        Room::create(['id' => 15, 'ruang' => 'Gudang']);
        $this->aula = MeetingRoom::create(['room_id' => 1, 'capacity' => 30, 'location' => 'Lantai 1', 'active' => true]);

        $this->employee = User::create(['nip_lama' => 340011001, 'name' => 'Pegawai Satu', 'email' => 'pegawai@example.com', 'password' => bcrypt('password'), 'fungsi' => 1]);
        $this->umum = User::create(['nip_lama' => 340011002, 'name' => 'Petugas Umum', 'email' => 'umum@example.com', 'password' => bcrypt('password'), 'fungsi' => 1, 'is_umum' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Rapat Koordinasi Internal',
            'purpose' => 'Pembahasan rencana kerja.',
            'pic' => 'Pegawai Satu',
            'date' => '2026-11-26',
            'start' => '09:00',
            'end' => '11:00',
            'participants' => 10,
            'room_id' => 1,
        ], $overrides);
    }

    private function approvedBooking(array $attrs = []): Booking
    {
        return Booking::create(array_merge([
            'type' => 'room', 'requested_by' => $this->umum->nip_lama, 'room_id' => 1, 'title' => 'Rapat Pimpinan',
            'purpose' => '-', 'participants' => 10, 'status' => Booking::DISETUJUI,
            'starts_at' => '2026-11-26 10:00:00', 'ends_at' => '2026-11-26 12:00:00',
        ], $attrs));
    }

    public function test_employee_can_open_form_and_only_meeting_rooms_are_listed(): void
    {
        $this->actingAs($this->employee)->get(route('ruang.create'))
            ->assertOk()->assertSee('Pengajuan Ruang Jambi')->assertSee('Ruang Aula')->assertDontSee('Gudang');
    }

    public function test_employee_can_submit_booking_with_facilities_and_reviewers_are_notified(): void
    {
        Notification::fake();
        $projector = Facility::create(['name' => 'Proyektor', 'type' => Facility::TAMBAHAN, 'active' => true]);

        $response = $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['facility_ids' => [$projector->id]]));

        $booking = Booking::where('type', 'room')->firstOrFail();
        $response->assertRedirect(route('ruang.show', $booking));
        $this->assertStringStartsWith('RJB-', $booking->booking_code);
        $this->assertSame(Booking::MENUNGGU, $booking->status);
        $this->assertSame('2026-11-26 09:00:00', $booking->starts_at->toDateTimeString());
        $this->assertTrue($booking->facilities->contains($projector));
        $this->assertDatabaseHas('booking_histories', ['booking_id' => $booking->id, 'action' => 'diajukan']);
        Notification::assertSentTo($this->umum, RoomBookingNotification::class);
        Notification::assertNotSentTo($this->employee, RoomBookingNotification::class);
    }

    public function test_booking_rejected_when_participants_exceed_capacity(): void
    {
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['participants' => 50]))
            ->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejected_outside_operational_hours_or_in_the_past(): void
    {
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['start' => '06:00']))
            ->assertSessionHasErrors('start');
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['date' => '2026-11-24']))
            ->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_rejected_when_overlapping_approved_booking_or_maintenance(): void
    {
        $this->approvedBooking();
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload())
            ->assertSessionHasErrors('room_id');

        MaintenanceBlock::create(['room_id' => 1, 'starts_at' => '2026-11-27 07:00:00', 'ends_at' => '2026-11-27 17:00:00', 'reason' => 'Perbaikan AC']);
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['date' => '2026-11-27']))
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, Booking::count());
    }

    public function test_overlapping_pending_bookings_are_allowed_but_only_one_can_be_approved(): void
    {
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload(['title' => 'Rapat Teknis Lain']))->assertSessionHasNoErrors();
        [$first, $second] = Booking::orderBy('id')->get();

        $this->actingAs($this->umum)->post(route('umum.ruang.approve', $first))->assertSessionHasNoErrors();
        $this->assertSame(Booking::DISETUJUI, $first->fresh()->status);

        $this->actingAs($this->umum)->post(route('umum.ruang.approve', $second))->assertSessionHasErrors('booking');
        $this->assertSame(Booking::MENUNGGU, $second->fresh()->status);
    }

    public function test_revision_flow_returns_booking_to_pending(): void
    {
        Notification::fake();
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload());
        $booking = Booking::firstOrFail();

        $this->actingAs($this->umum)->post(route('umum.ruang.revision', $booking), [])->assertSessionHasErrors('note');
        $this->actingAs($this->umum)->post(route('umum.ruang.revision', $booking), ['note' => 'Mohon kurangi durasi.']);
        $this->assertSame(Booking::PERLU_REVISI, $booking->fresh()->status);
        Notification::assertSentTo($this->employee, RoomBookingNotification::class);

        $this->actingAs($this->employee)->get(route('ruang.edit', $booking))->assertOk()->assertSee('Mohon kurangi durasi.');
        $this->actingAs($this->employee)->put(route('ruang.update', $booking), $this->payload(['end' => '10:00']))
            ->assertRedirect(route('ruang.show', $booking));

        $booking->refresh();
        $this->assertSame(Booking::MENUNGGU, $booking->status);
        $this->assertSame('10:00', $booking->ends_at->format('H:i'));
        $this->assertSame(['diajukan', 'perlu_revisi', 'diubah'], $booking->histories->pluck('action')->all());
    }

    public function test_employee_can_cancel_own_booking_but_not_others(): void
    {
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload());
        $booking = Booking::firstOrFail();
        $other = User::create(['nip_lama' => 340011003, 'name' => 'Pegawai Lain', 'password' => bcrypt('password'), 'fungsi' => 1]);

        $this->actingAs($other)->post(route('ruang.cancel', $booking))->assertForbidden();
        $this->actingAs($other)->get(route('ruang.show', $booking))->assertForbidden();

        $this->actingAs($this->employee)->post(route('ruang.cancel', $booking), ['note' => 'Acara ditunda'])->assertSessionHasNoErrors();
        $this->assertSame(Booking::DIBATALKAN, $booking->fresh()->status);
    }

    public function test_past_approved_bookings_are_marked_completed(): void
    {
        $booking = $this->approvedBooking();
        Carbon::setTestNow('2026-11-26 13:00:00');

        $this->actingAs($this->umum)->get(route('umum.ruang.index'))->assertOk();

        $this->assertSame(Booking::SELESAI, $booking->fresh()->status);
    }

    public function test_all_pages_render_including_notifications_and_zoom(): void
    {
        $this->actingAs($this->employee)->post(route('ruang.store'), $this->payload());
        $booking = Booking::firstOrFail();
        $this->actingAs($this->umum)->post(route('umum.ruang.revision', $booking), ['note' => 'Lengkapi agenda.']);

        $this->actingAs($this->employee)->get(route('ruang.index'))->assertOk()->assertSee($booking->booking_code);
        $this->actingAs($this->employee)->get(route('ruang.show', $booking))->assertOk()->assertSee('Lengkapi agenda.')->assertSee('Revisi Sekarang');
        $this->actingAs($this->employee)->get(route('notifications.index'))->assertOk()->assertSee('Pengajuan Ruang Jambi perlu revisi');
        $this->actingAs($this->umum)->get(route('umum.ruang.show', $booking))->assertOk()->assertSee('Rapat Koordinasi Internal');
        $this->actingAs($this->umum)->get(route('umum.ruang.index', ['status' => 'perlu_revisi']))->assertOk()->assertSee($booking->booking_code);
        $this->actingAs($this->employee)->get(route('general.booking.form', 'zoom'))->assertOk()->assertSee('Pengajuan Zoom');
    }

    public function test_non_umum_users_cannot_access_review_and_manage_pages(): void
    {
        $this->actingAs($this->employee)->get(route('umum.ruang.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($this->employee)->get(route('umum.ruang.manage'))->assertRedirect(route('dashboard'));
    }

    public function test_umum_can_manage_rooms_facilities_and_maintenance(): void
    {
        Room::create(['id' => 7, 'ruang' => 'Ruang Pajale']);
        $ac = Facility::create(['name' => 'AC', 'type' => Facility::TETAP, 'active' => true]);

        $this->actingAs($this->umum)->get(route('umum.ruang.manage'))->assertOk();
        $this->actingAs($this->umum)->post(route('umum.ruang.manage.rooms.store'), ['room_id' => 7, 'capacity' => 20, 'facility_ids' => [$ac->id]])
            ->assertSessionHasNoErrors();
        $this->assertTrue(MeetingRoom::findOrFail(7)->facilities->contains($ac));

        $this->actingAs($this->umum)->post(route('umum.ruang.manage.rooms.toggle', $this->aula));
        $this->assertFalse($this->aula->fresh()->active);

        $this->actingAs($this->umum)->post(route('umum.ruang.manage.facilities.store'), ['name' => 'Mikrofon', 'type' => Facility::TAMBAHAN, 'stock' => 4])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('facilities', ['name' => 'Mikrofon', 'stock' => 4]);

        $this->actingAs($this->umum)->post(route('umum.ruang.manage.blocks.store'), ['room_id' => 7, 'date' => '2026-11-27', 'start' => '08:00', 'end' => '12:00', 'reason' => 'Pengecatan'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('maintenance_blocks', ['room_id' => 7, 'reason' => 'Pengecatan']);
    }
}
