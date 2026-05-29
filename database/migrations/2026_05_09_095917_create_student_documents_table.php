<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('document_type', ['psa', 'photo', 'report_card', 'other']);
            $table->string('file_path');
            $table->string('file_name')->nullable();
            
            $table->timestamps();
            $table->index(['student_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
