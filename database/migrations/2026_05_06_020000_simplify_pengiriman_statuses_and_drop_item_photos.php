<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Terkirim')
            ->update(['status' => 'Dikirim']);

        DB::table('peminjamans')
            ->where('status', 'Disiapkan')
            ->update(['status' => 'Dipesan']);

        Schema::dropIfExists('peminjaman_item_photos');
    }

    public function down(): void
    {
        Schema::create('peminjaman_item_photos', function ($table) {
            $table->id();
            $table->unsignedBigInteger('peminjaman_item_id');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('peminjaman_item_id')
                ->references('id')
                ->on('peminjaman_items')
                ->onDelete('cascade');
        });

        DB::table('peminjamans')
            ->where('status', 'Dikirim')
            ->update(['status' => 'Terkirim']);
    }
};
