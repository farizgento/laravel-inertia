<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role_key', 100)->nullable();
            $table->unsignedBigInteger('actor_area_id')->nullable();
            $table->string('action', 50);
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_label')->nullable();
            $table->text('description')->nullable();
            $table->string('method', 20)->nullable();
            $table->string('route')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();
            $table->longText('properties')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['area_id', 'created_at']);
            $table->index(['actor_role_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
