<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->unsignedBigInteger('area_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('source_peminjaman_id')->nullable()->after('area_id');
            $table->index(['area_id', 'status']);
            $table->index('source_peminjaman_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->dropIndex(['area_id', 'status']);
            $table->dropIndex(['source_peminjaman_id']);
            $table->dropColumn(['area_id', 'source_peminjaman_id']);
        });
    }
};
