<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ruang Jambi: pengajuan ruang rapat lengkap (port dari bps-meeting-management-system).
 *
 * Profil ruang rapat menempel ke tabel `rooms` yang sudah ada (PK = room_id), sehingga
 * `bookings.room_id` dan relasi perangkat (`devices.id_user`) tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->primary();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
        });

        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->index(); // tetap | tambahan
            $table->text('description')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('facility_meeting_room', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id');
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->primary(['room_id', 'facility_id']);
            $table->foreign('room_id')->references('room_id')->on('meeting_rooms')->cascadeOnDelete();
        });

        Schema::create('booking_facility', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->primary(['booking_id', 'facility_id']);
        });

        Schema::create('maintenance_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason');
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('room_id')->references('id')->on('rooms')->cascadeOnDelete();
            $table->foreign('created_by')->references('nip_lama')->on('users')->nullOnDelete();
            $table->index(['room_id', 'starts_at']);
        });

        Schema::create('booking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('actor_nip')->nullable();
            // Nama disalin saat aksi agar riwayat tetap akurat bila data pengguna berubah.
            $table->string('actor_name');
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('pic')->nullable()->after('purpose');
            $table->text('other_needs')->nullable()->after('participants');
            $table->text('notes')->nullable()->after('other_needs');
            $table->index(['type', 'room_id', 'starts_at']);
        });

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        DB::table('bookings')->where('type', 'room')->whereNull('booking_code')->orderBy('id')->each(function (object $booking) use ($letters): void {
            do {
                $code = 'RJB-';
                for ($index = 0; $index < 6; $index++) {
                    $code .= $letters[random_int(0, strlen($letters) - 1)];
                }
            } while (DB::table('bookings')->where('booking_code', $code)->exists());

            DB::table('bookings')->where('id', $booking->id)->update(['booking_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['type', 'room_id', 'starts_at']);
            $table->dropColumn(['pic', 'other_needs', 'notes']);
        });
        Schema::dropIfExists('booking_histories');
        Schema::dropIfExists('maintenance_blocks');
        Schema::dropIfExists('booking_facility');
        Schema::dropIfExists('facility_meeting_room');
        Schema::dropIfExists('facilities');
        Schema::dropIfExists('meeting_rooms');
    }
};
