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
        Schema::create('ldap_users', function (Blueprint $table) {
            $table->id();
            $table->string('ldap_dn', 1024)->unique();
            $table->string('name')->index();
            $table->string('username')->index();
            $table->string('email')->index();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ldap_users');
    }
};
