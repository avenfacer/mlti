<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\MaintenanceBlock;
use App\Models\MeetingRoom;
use App\Models\User;
use App\Notifications\RoomBookingNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Alur pengajuan Ruang Jambi: ajukan → (ubah) → setujui / tolak / minta revisi → batal / selesai.
 *
 * Seluruh perubahan status lewat kelas ini agar riwayat, aturan ketersediaan,
 * dan notifikasi selalu konsisten (padanan MeetingWorkflow + Availability di
 * bps-meeting-management-system).
 */
class RoomBookingService
{
    /**
     * Alasan ruang tidak dapat dipakai; kosong bila tersedia.
     *
     * - Hanya pengajuan "disetujui" / "selesai" yang memblokir ruang.
     * - Blok maintenance dan ruang nonaktif memblokir pemesanan.
     * - Jumlah peserta tidak boleh melebihi kapasitas.
     *
     * @return list<string>
     */
    public function check(?MeetingRoom $room, Carbon $start, Carbon $end, int $participants, ?int $excludeId = null): array
    {
        if (! $room) {
            return ['Ruangan tidak ditemukan.'];
        }

        $reasons = [];
        if (! $room->active) {
            $reasons[] = 'Ruangan sedang nonaktif.';
        }
        if ($participants > $room->capacity) {
            $reasons[] = "Jumlah peserta ({$participants}) melebihi kapasitas ruangan ({$room->capacity}).";
        }

        $conflict = Booking::query()->overlapping($room->room_id, $start, $end)
            ->whereIn('status', Booking::BLOCKING)
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->orderBy('starts_at')->first();
        if ($conflict) {
            $reasons[] = "Bentrok dengan kegiatan terkonfirmasi \"{$conflict->title}\" ({$conflict->starts_at->format('H:i')}–{$conflict->ends_at->format('H:i')}).";
        }

        $block = MaintenanceBlock::query()->overlapping($room->room_id, $start, $end)->orderBy('starts_at')->first();
        if ($block) {
            $reasons[] = "Ruangan diblokir untuk maintenance ({$block->starts_at->format('d/m H:i')}–{$block->ends_at->format('d/m H:i')}): {$block->reason}.";
        }

        return $reasons;
    }

    /** @param array<string, mixed> $input */
    public function submit(User $actor, array $input): Booking
    {
        return DB::transaction(function () use ($actor, $input) {
            $this->assertBookable($input);

            $booking = Booking::create($this->attributes($input) + [
                'type' => 'room',
                'requested_by' => $actor->nip_lama,
                'status' => Booking::MENUNGGU,
            ]);
            $booking->facilities()->sync($input['facility_ids'] ?? []);
            $this->log($booking, $actor, 'diajukan');
            $this->notifyReviewers($booking, $actor, 'Pengajuan Ruang Jambi baru', "{$actor->name} mengajukan {$this->summary($booking)}.");

            return $booking;
        });
    }

    /** @param array<string, mixed> $input */
    public function edit(User $actor, Booking $booking, array $input): Booking
    {
        return DB::transaction(function () use ($actor, $booking, $input) {
            $booking = $this->lock($booking);
            if (! $booking->isEditable()) {
                $this->fail('Pengajuan ini tidak dapat diubah lagi.');
            }
            $this->assertBookable($input, $booking->id);

            $wasRevision = $booking->status === Booking::PERLU_REVISI;
            $booking->fill($this->attributes($input) + ['status' => Booking::MENUNGGU])->save();
            $booking->facilities()->sync($input['facility_ids'] ?? []);
            $this->log($booking, $actor, 'diubah', $wasRevision ? 'Pengajuan direvisi dan diajukan kembali.' : 'Detail pengajuan diperbarui.');
            $this->notifyReviewers($booking, $actor, $wasRevision ? 'Revisi pengajuan Ruang Jambi' : 'Perubahan pengajuan Ruang Jambi', "{$actor->name} memperbarui {$this->summary($booking)}.");

            return $booking;
        });
    }

    public function approve(User $actor, Booking $booking, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($actor, $booking, $note) {
            // Kunci baris ruang agar dua persetujuan bersamaan tidak lolos pengecekan bentrok.
            $room = MeetingRoom::query()->lockForUpdate()->find($booking->room_id);
            $booking = $this->lock($booking);
            if ($booking->status !== Booking::MENUNGGU) {
                $this->fail('Hanya pengajuan berstatus menunggu yang dapat disetujui.');
            }
            if (! $booking->starts_at->isFuture()) {
                $this->fail('Waktu kegiatan sudah lewat sehingga tidak dapat disetujui.');
            }
            $reasons = $this->check($room, $booking->starts_at, $booking->ends_at, (int) $booking->participants, $booking->id);
            if ($reasons !== []) {
                $this->fail(implode(' ', $reasons));
            }

            return $this->transition($booking, $actor, Booking::DISETUJUI, $note, 'Pengajuan Ruang Jambi disetujui');
        });
    }

    public function reject(User $actor, Booking $booking, string $note): Booking
    {
        return DB::transaction(function () use ($actor, $booking, $note) {
            $booking = $this->lock($booking);
            if ($booking->status !== Booking::MENUNGGU) {
                $this->fail('Hanya pengajuan berstatus menunggu yang dapat ditolak.');
            }

            return $this->transition($booking, $actor, Booking::DITOLAK, $note, 'Pengajuan Ruang Jambi ditolak');
        });
    }

    public function requestRevision(User $actor, Booking $booking, string $note): Booking
    {
        return DB::transaction(function () use ($actor, $booking, $note) {
            $booking = $this->lock($booking);
            if ($booking->status !== Booking::MENUNGGU) {
                $this->fail('Hanya pengajuan berstatus menunggu yang dapat diminta revisi.');
            }

            return $this->transition($booking, $actor, Booking::PERLU_REVISI, $note, 'Pengajuan Ruang Jambi perlu revisi');
        });
    }

    public function cancel(User $actor, Booking $booking, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($actor, $booking, $note) {
            $booking = $this->lock($booking);
            if (! $booking->isCancellable()) {
                $this->fail('Pengajuan ini tidak dapat dibatalkan lagi.');
            }

            $booking->update(['status' => Booking::DIBATALKAN] + ($note ? ['admin_notes' => $note] : []));
            $this->log($booking, $actor, Booking::DIBATALKAN, $note);

            $message = "{$this->summary($booking)} dibatalkan oleh {$actor->name}.".($note ? " Catatan: {$note}" : '');
            if ((int) $actor->nip_lama === (int) $booking->requested_by) {
                $this->notifyReviewers($booking, $actor, 'Pengajuan Ruang Jambi dibatalkan', $message);
            } else {
                $this->notifyRequester($booking, $actor, 'Pengajuan Ruang Jambi dibatalkan', $message);
            }

            return $booking;
        });
    }

    /**
     * Tandai pengajuan disetujui yang sudah lewat sebagai "selesai".
     * Murah (satu SELECT + UPDATE terindeks), jadi aman dipanggil saat halaman dibuka.
     */
    public function completePast(): int
    {
        $ids = Booking::where('type', 'room')->where('status', Booking::DISETUJUI)->where('ends_at', '<=', now())->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($ids) {
            Booking::whereIn('id', $ids)->update(['status' => Booking::SELESAI]);
            BookingHistory::insert($ids->map(fn ($id) => [
                'booking_id' => $id, 'actor_nip' => null, 'actor_name' => 'Sistem',
                'action' => Booking::SELESAI, 'note' => null, 'created_at' => now(),
            ])->all());
        });

        return $ids->count();
    }

    // ---------------------------------------------------------------------

    private function transition(Booking $booking, User $actor, string $to, ?string $note, string $title): Booking
    {
        $booking->update(['status' => $to, 'admin_notes' => $note]);
        $this->log($booking, $actor, $to, $note);
        $this->notifyRequester($booking, $actor, $title, $this->summary($booking).'.'.($note ? " Catatan: {$note}" : ''));

        return $booking;
    }

    /** @param array<string, mixed> $input */
    private function assertBookable(array $input, ?int $excludeId = null): void
    {
        $room = MeetingRoom::find($input['room_id']);
        [$start, $end] = $this->times($input);
        $reasons = $this->check($room, $start, $end, (int) $input['participants'], $excludeId);
        if ($reasons !== []) {
            throw ValidationException::withMessages(['room_id' => $reasons[0]]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{Carbon, Carbon}
     */
    public function times(array $input): array
    {
        return [
            Carbon::parse($input['date'].' '.$input['start']),
            Carbon::parse($input['date'].' '.$input['end']),
        ];
    }

    /** @param array<string, mixed> $input */
    private function attributes(array $input): array
    {
        [$start, $end] = $this->times($input);

        return [
            'title' => trim($input['title']),
            'purpose' => $input['purpose'] ?? '',
            'pic' => trim($input['pic']),
            'room_id' => (int) $input['room_id'],
            'participants' => (int) $input['participants'],
            'other_needs' => $input['other_needs'] ?? null,
            'notes' => $input['notes'] ?? null,
            'starts_at' => $start,
            'ends_at' => $end,
        ];
    }

    private function log(Booking $booking, ?User $actor, string $action, ?string $note = null): void
    {
        $note = $note !== null ? trim($note) : null;
        BookingHistory::create([
            'booking_id' => $booking->id,
            'actor_nip' => $actor?->nip_lama,
            'actor_name' => $actor->name ?? 'Sistem',
            'action' => $action,
            'note' => $note !== '' ? $note : null,
            'created_at' => now(),
        ]);
    }

    private function summary(Booking $booking): string
    {
        $booking->loadMissing('room');

        return "\"{$booking->title}\" di ".($booking->room->ruang ?? 'ruang rapat')
            .' pada '.$booking->starts_at->translatedFormat('d M Y, H:i').'–'.$booking->ends_at->format('H:i');
    }

    private function notifyReviewers(Booking $booking, User $actor, string $title, string $message): void
    {
        $reviewers = User::where(fn ($q) => $q->where('is_umum', 1)->orWhere('is_jarkom', 1))
            ->where('nip_lama', '!=', $actor->nip_lama)->get();
        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new RoomBookingNotification($booking, $title, $message, route('umum.ruang.show', $booking)));
        }
    }

    private function notifyRequester(Booking $booking, User $actor, string $title, string $message): void
    {
        $booking->loadMissing('requester');
        if ($booking->requester && (int) $booking->requester->nip_lama !== (int) $actor->nip_lama) {
            $booking->requester->notify(new RoomBookingNotification($booking, $title, $message, route('ruang.show', $booking)));
        }
    }

    private function lock(Booking $booking): Booking
    {
        return Booking::query()->lockForUpdate()->findOrFail($booking->id);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['booking' => $message]);
    }
}
