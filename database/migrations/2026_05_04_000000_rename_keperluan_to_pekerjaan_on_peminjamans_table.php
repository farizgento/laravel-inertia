<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('peminjamans', 'keperluan') || Schema::hasColumn('peminjamans', 'pekerjaan')) {
            return;
        }

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->renameColumn('keperluan', 'pekerjaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('peminjamans', 'pekerjaan') || Schema::hasColumn('peminjamans', 'keperluan')) {
            return;
        }

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->renameColumn('pekerjaan', 'keperluan');
        });
    }
};
