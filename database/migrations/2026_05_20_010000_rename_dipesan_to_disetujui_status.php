<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Dipesan')
            ->update(['status' => 'Disetujui']);
    }

    public function down(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Disetujui')
            ->update(['status' => 'Dipesan']);
    }
};
