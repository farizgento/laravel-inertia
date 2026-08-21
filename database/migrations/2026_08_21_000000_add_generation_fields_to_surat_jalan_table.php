<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_jalan', function (Blueprint $table) {
            $table->string('jenis', 32)->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->string('nomor')->nullable();
            $table->string('disk', 32)->default('public');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->string('template_version', 64)->nullable();
        });

        $sequenceByLoanAndType = [];

        DB::table('surat_jalan')
            ->select(['id', 'peminjaman_id', 'path'])
            ->orderBy('peminjaman_id')
            ->orderBy('id')
            ->get()
            ->each(function ($document) use (&$sequenceByLoanAndType) {
                $type = Str::contains((string) $document->path, '/pengembalian/')
                    ? 'pengembalian'
                    : 'pengiriman';
                $key = $document->peminjaman_id.'|'.$type;
                $sequenceByLoanAndType[$key] = ($sequenceByLoanAndType[$key] ?? 0) + 1;

                DB::table('surat_jalan')
                    ->where('id', $document->id)
                    ->update([
                        'jenis' => $type,
                        'urutan' => $sequenceByLoanAndType[$key],
                        'disk' => 'public',
                    ]);
            });

        Schema::table('surat_jalan', function (Blueprint $table) {
            $table->index(['peminjaman_id', 'jenis'], 'sj_loan_type_index');
            $table->unique(['peminjaman_id', 'jenis', 'urutan'], 'sj_loan_type_seq_unique');
            $table->foreign('generated_by', 'sj_generated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_jalan', function (Blueprint $table) {
            $table->dropForeign('sj_generated_by_fk');
            $table->dropUnique('sj_loan_type_seq_unique');
            $table->dropIndex('sj_loan_type_index');
            $table->dropColumn([
                'jenis',
                'urutan',
                'nomor',
                'disk',
                'generated_by',
                'generated_at',
                'template_version',
            ]);
        });
    }
};
