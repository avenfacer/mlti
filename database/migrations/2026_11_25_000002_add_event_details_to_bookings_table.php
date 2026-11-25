<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isian tambahan Ruang Jambi mengikuti Google Form "Pengajuan Ruangan Meeting":
 * layout ruangan, jumlah pejabat di depan, template kepanitiaan, nota dinas, dan surat pendukung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('layout', 30)->nullable()->after('participants');
            $table->unsignedTinyInteger('officials_count')->nullable()->after('layout');
            $table->string('committee', 30)->nullable()->after('officials_count');
            $table->string('memo_path')->nullable()->after('committee');
            $table->string('memo_name')->nullable()->after('memo_path');
            $table->string('letter_path')->nullable()->after('memo_name');
            $table->string('letter_name')->nullable()->after('letter_path');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['layout', 'officials_count', 'committee', 'memo_path', 'memo_name', 'letter_path', 'letter_name']);
        });
    }
};
