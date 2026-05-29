<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'deoris_user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('deoris_user_id')->nullable()->unique()->after('id');
                $table->index('deoris_user_id');
            });
        }
    }

    public function down(): void
    {
        // Local users are not restored — identity is owned by DEORIS.
    }
};
