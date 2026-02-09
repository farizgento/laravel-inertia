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
        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->unsignedInteger('approved_qty')->default(0);
            $table->string('review_status')->default('Menunggu Review');
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->dropColumn(['approved_qty', 'review_status', 'rejection_reason']);
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn(['review_note', 'reviewed_at']);
        });
    }
};
