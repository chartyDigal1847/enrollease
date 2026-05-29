<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            // Personal info
            $table->string('student_name', 200);
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->string('middle_name', 80)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('nationality', 60)->default('Filipino');
            $table->string('email', 150)->unique();
            $table->string('contact_number', 20)->nullable();
            $table->string('address', 300)->nullable();
            
            // Academic info
            $table->string('lrn', 12)->nullable()->unique();
            $table->string('previous_school', 150)->nullable();
            $table->tinyInteger('last_grade_completed')->nullable();
            $table->decimal('average_grade', 5, 2)->nullable();
            
            $table->timestamps();
            $table->index('email');
            $table->index('lrn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
