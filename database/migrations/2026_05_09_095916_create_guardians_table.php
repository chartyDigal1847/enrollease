<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('relationship', 50);
            $table->string('contact_number', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('occupation', 100)->nullable();
            
            $table->timestamps();
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
