<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\MeetingRoom;
use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * Ruang rapat Ruang Jambi + fasilitas contoh. Memakai firstOrCreate sehingga
 * perubahan dari halaman "Kelola Ruang Jambi" tidak tertimpa saat seed ulang.
 */
class MeetingRoomSeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['AC', Facility::TETAP, 'Pendingin ruangan terpasang.', null],
            ['Proyektor Tetap', Facility::TETAP, 'Proyektor terpasang beserta layar.', null],
            ['Layar TV', Facility::TETAP, 'Smart TV untuk presentasi.', null],
            ['Sound System', Facility::TETAP, 'Pengeras suara dan mikrofon meja.', null],
            ['Video Conference', Facility::TETAP, 'Kamera & mikrofon konferensi untuk rapat daring.', null],
            ['Wi-Fi', Facility::TETAP, 'Akses internet kantor.', null],
            ['Proyektor', Facility::TAMBAHAN, 'Proyektor portabel beserta layar lipat.', 2],
            ['Mikrofon', Facility::TAMBAHAN, 'Mikrofon nirkabel tambahan.', 4],
            ['Laptop', Facility::TAMBAHAN, 'Laptop operasional untuk presentasi.', 2],
            ['Konsumsi / Snack', Facility::TAMBAHAN, 'Snack rapat dari Bagian Umum (diajukan H-1).', null],
            ['Air Minum', Facility::TAMBAHAN, 'Air mineral kemasan untuk peserta.', null],
        ];
        foreach ($facilities as [$name, $type, $description, $stock]) {
            Facility::firstOrCreate(['name' => $name, 'type' => $type], compact('description', 'stock') + ['active' => true]);
        }

        // [room_id, kapasitas awal, lokasi (isi lewat halaman Kelola), fasilitas tetap] — ubah lewat halaman Kelola Ruang Jambi.
        $rooms = [
            [1, 100, null, ['AC', 'Proyektor Tetap', 'Sound System', 'Wi-Fi']],
            [7, 20, null, ['AC', 'Layar TV', 'Wi-Fi']],
            [11, 25, null, ['AC', 'Proyektor Tetap', 'Video Conference', 'Wi-Fi']],
            [13, 15, null, ['AC', 'Layar TV', 'Wi-Fi']],
        ];
        foreach ($rooms as [$roomId, $capacity, $location, $facilityNames]) {
            if (! Room::whereKey($roomId)->exists()) {
                continue;
            }
            $meetingRoom = MeetingRoom::firstOrCreate(['room_id' => $roomId], ['capacity' => $capacity, 'location' => $location, 'active' => true]);
            if ($meetingRoom->wasRecentlyCreated) {
                $meetingRoom->facilities()->sync(Facility::permanent()->whereIn('name', $facilityNames)->pluck('id'));
            }
        }
    }
}
