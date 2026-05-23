<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->string('certificate_number', 100)->unique();
            $table->string('verify_token', 32)->unique();
            $table->string('title', 255)->nullable();
            $table->date('issued_at');
            $table->text('notes')->nullable();
            $table->foreignId('pdf_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['person_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
