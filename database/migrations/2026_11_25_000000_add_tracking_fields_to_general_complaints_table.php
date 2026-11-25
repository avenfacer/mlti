<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_complaints', function (Blueprint $table) {
            $table->string('ticket_id', 10)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('room_id')->nullable()->after('reported_by');
            $table->bigInteger('handled_by')->nullable()->after('status');
            $table->text('admin_notes')->nullable()->after('handled_by');
            $table->timestamp('resolved_at')->nullable()->after('admin_notes');

            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('handled_by')->references('nip_lama')->on('users')->nullOnDelete();
        });

        Schema::table('general_complaints', function (Blueprint $table) {
            $table->string('location')->nullable()->change();
        });

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        DB::table('general_complaints')->whereNull('ticket_id')->orderBy('id')->each(function (object $complaint) use ($letters): void {
            do {
                $code = 'SGP-';
                for ($index = 0; $index < 6; $index++) {
                    $code .= $letters[random_int(0, strlen($letters) - 1)];
                }
            } while (DB::table('general_complaints')->where('ticket_id', $code)->exists());

            DB::table('general_complaints')->where('id', $complaint->id)->update(['ticket_id' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('general_complaints', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['handled_by']);
            $table->dropUnique(['ticket_id']);
            $table->dropColumn(['ticket_id', 'room_id', 'handled_by', 'admin_notes', 'resolved_at']);
        });
    }
};
