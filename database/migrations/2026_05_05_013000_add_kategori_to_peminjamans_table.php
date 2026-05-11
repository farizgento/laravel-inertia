<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->string('kategori')->default('Intra Area')->after('status');
            $table->index(['kategori', 'status']);
        });

        DB::table('peminjamans')
            ->whereNull('kategori')
            ->orWhere('kategori', '')
            ->update(['kategori' => 'Intra Area']);
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex(['kategori', 'status']);
            $table->dropColumn('kategori');
        });
    }
};
