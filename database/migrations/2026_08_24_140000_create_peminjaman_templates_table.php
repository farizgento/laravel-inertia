<?php

use App\Models\Peminjaman;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('source_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama');
            $table->string('kategori')->default(Peminjaman::KATEGORI_INTRA_AREA);
            $table->timestamps();

            $table->index(['area_id', 'kategori']);
            $table->index(['source_area_id', 'kategori']);
        });

        Schema::create('peminjaman_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_template_id')
                ->constrained('peminjaman_templates')
                ->cascadeOnDelete();
            $table->foreignId('alat_id')->constrained('alats')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();

            $table->unique(['peminjaman_template_id', 'alat_id'], 'template_items_template_alat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_template_items');
        Schema::dropIfExists('peminjaman_templates');
    }
};
