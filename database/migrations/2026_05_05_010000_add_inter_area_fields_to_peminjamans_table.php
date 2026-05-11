<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_area_id')->nullable()->after('area_id');
            $table->boolean('is_inter_area')->default(false)->after('requester_area_id');
            $table->index(['requester_area_id', 'status']);
            $table->index(['is_inter_area', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex(['requester_area_id', 'status']);
            $table->dropIndex(['is_inter_area', 'status']);
            $table->dropColumn(['requester_area_id', 'is_inter_area']);
        });
    }
};
