<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Menunggu Review Area Peminjam')
            ->update(['status' => 'Perlu Direview']);

        DB::table('peminjamans')
            ->where('status', 'Menunggu Review')
            ->update(['status' => 'Perlu Disetujui']);
    }

    public function down(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Perlu Direview')
            ->update(['status' => 'Menunggu Review Area Peminjam']);

        DB::table('peminjamans')
            ->where('status', 'Perlu Disetujui')
            ->update(['status' => 'Menunggu Review']);
    }
};
