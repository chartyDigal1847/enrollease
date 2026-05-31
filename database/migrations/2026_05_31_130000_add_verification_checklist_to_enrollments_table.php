<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->json('verification_checks')->nullable()->after('remarks');
            $table->timestamp('verified_at')->nullable()->after('verification_checks');
            $table->string('verified_by')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['verification_checks', 'verified_at', 'verified_by']);
        });
    }
};
