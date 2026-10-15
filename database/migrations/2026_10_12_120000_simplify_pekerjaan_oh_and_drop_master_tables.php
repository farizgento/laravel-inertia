<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pekerjaan overhaul disederhanakan: cukup satu tabel tanpa master
        // UBP, unit pembangkit, maupun tipe OH. Jenis cukup ditulis langsung.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite tidak bisa melepas foreign key, jadi tabelnya dibentuk ulang.
            // Jalur ini hanya dipakai database pengujian yang selalu kosong.
            Schema::dropIfExists('pekerjaan_ohs');
            Schema::create('pekerjaan_ohs', function (Blueprint $table) {
                $table->id();
                $table->string('jenis')->nullable();
                $table->string('nama');
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->string('siteco')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by', 'pekerjaan_oh_creator_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } else {
            Schema::table('pekerjaan_ohs', function (Blueprint $table) {
                $table->dropIndex('pekerjaan_oh_area_status_idx');
                $table->dropForeign('pekerjaan_oh_tipe_fk');
                $table->dropForeign('pekerjaan_oh_area_fk');
                $table->dropColumn(['tipe_oh_id', 'area_id', 'status']);
            });

            Schema::table('pekerjaan_ohs', function (Blueprint $table) {
                $table->string('jenis')->nullable()->after('id');
            });
        }

        Schema::dropIfExists('tipe_ohs');
        Schema::dropIfExists('unit_pembangkits');
        Schema::dropIfExists('ubps');
    }

    public function down(): void
    {
        Schema::create('ubps', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'ubp_nama_unique');
        });

        Schema::create('unit_pembangkits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ubp_id')->nullable();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'unit_pembangkit_nama_unique');
            $table->foreign('ubp_id', 'unit_pembangkit_ubp_fk')
                ->references('id')
                ->on('ubps')
                ->nullOnDelete();
            $table->index('ubp_id', 'unit_pembangkit_ubp_idx');
        });

        Schema::create('tipe_ohs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            $table->unique('nama', 'tipe_oh_nama_unique');
        });

        Schema::table('pekerjaan_ohs', function (Blueprint $table) {
            $table->dropColumn('jenis');
            $table->unsignedBigInteger('tipe_oh_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('status')->default('Direncanakan');

            $table->foreign('tipe_oh_id', 'pekerjaan_oh_tipe_fk')
                ->references('id')
                ->on('tipe_ohs')
                ->nullOnDelete();
            $table->foreign('area_id', 'pekerjaan_oh_area_fk')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
            $table->index(['area_id', 'status'], 'pekerjaan_oh_area_status_idx');
        });
    }
};
