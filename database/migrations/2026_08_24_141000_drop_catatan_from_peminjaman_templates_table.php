<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('peminjaman_templates') || ! Schema::hasColumn('peminjaman_templates', 'catatan')) {
            return;
        }

        Schema::table('peminjaman_templates', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('peminjaman_templates') || Schema::hasColumn('peminjaman_templates', 'catatan')) {
            return;
        }

        Schema::table('peminjaman_templates', function (Blueprint $table) {
            $table->text('catatan')->nullable();
        });
    }
};
