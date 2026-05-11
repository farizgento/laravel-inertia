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
            $table->text('requester_review_note')->nullable()->after('review_note');
            $table->timestamp('requester_reviewed_at')->nullable()->after('requester_review_note');
            $table->foreignId('requester_reviewed_by')
                ->nullable()
                ->after('requester_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('peminjamans')
            ->where('is_inter_area', true)
            ->whereIn('status', ['Menunggu Review', 'Perlu Disetujui'])
            ->whereNull('reviewed_by')
            ->update(['status' => 'Perlu Direview']);
    }

    public function down(): void
    {
        DB::table('peminjamans')
            ->where('status', 'Perlu Direview')
            ->update(['status' => 'Perlu Disetujui']);

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign(['requester_reviewed_by']);
            $table->dropColumn([
                'requester_review_note',
                'requester_reviewed_at',
                'requester_reviewed_by',
            ]);
        });
    }
};
