<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_pembangkits', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'unit_pembangkit_nama_unique');
        });

        Schema::create('ubps', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'ubp_nama_unique');
        });

        Schema::create('tipe_ohs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'tipe_oh_nama_unique');
        });

        Schema::create('pekerjaan_ohs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedBigInteger('tipe_oh_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('status')->default('Direncanakan');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('siteco')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Nama constraint dipendekkan supaya aman di Oracle.
            $table->foreign('tipe_oh_id', 'pekerjaan_oh_tipe_fk')
                ->references('id')
                ->on('tipe_ohs')
                ->nullOnDelete();
            $table->foreign('area_id', 'pekerjaan_oh_area_fk')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
            $table->foreign('created_by', 'pekerjaan_oh_creator_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['area_id', 'status'], 'pekerjaan_oh_area_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan_ohs');
        Schema::dropIfExists('tipe_ohs');
        Schema::dropIfExists('ubps');
        Schema::dropIfExists('unit_pembangkits');
    }
};
