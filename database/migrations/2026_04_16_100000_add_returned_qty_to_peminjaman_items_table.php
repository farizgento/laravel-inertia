<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->unsignedInteger('returned_qty')->default(0);
        });

        DB::table('peminjamans')
            ->where('status', 'Dikembalikan')
            ->update([
                'status' => 'Dikembalikan Semuanya',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Dikembalikan Semuanya')
            ->update([
                'status' => 'Dikembalikan',
            ]);

        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->dropColumn('returned_qty');
        });
    }
};
