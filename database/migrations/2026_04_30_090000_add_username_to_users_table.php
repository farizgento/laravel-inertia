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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $existing = DB::table('users')
            ->select('id', 'name', 'email', 'username')
            ->orderBy('id')
            ->get();

        foreach ($existing as $user) {
            if (! empty($user->username)) {
                continue;
            }

            $base = Str::slug((string) $user->name, '_');
            if ($base === '') {
                $emailName = Str::before((string) $user->email, '@');
                $base = Str::slug($emailName, '_');
            }
            if ($base === '') {
                $base = 'user';
            }

            $username = $base;
            $suffix = 1;

            while (
                DB::table('users')
                    ->where('username', $username)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $suffix += 1;
                $username = $base . '_' . $suffix;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
