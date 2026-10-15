<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_pembangkits', function (Blueprint $table) {
            // Satu UBP menaungi banyak unit pembangkit. Dibuat nullable supaya
            // data lama tetap bisa disimpan sambil dilengkapi UBP-nya.
            $table->unsignedBigInteger('ubp_id')->nullable()->after('id');

            $table->foreign('ubp_id', 'unit_pembangkit_ubp_fk')
                ->references('id')
                ->on('ubps')
                ->nullOnDelete();
            $table->index('ubp_id', 'unit_pembangkit_ubp_idx');
        });
    }

    public function down(): void
    {
        Schema::table('unit_pembangkits', function (Blueprint $table) {
            $table->dropForeign('unit_pembangkit_ubp_fk');
            $table->dropIndex('unit_pembangkit_ubp_idx');
            $table->dropColumn('ubp_id');
        });
    }
};
