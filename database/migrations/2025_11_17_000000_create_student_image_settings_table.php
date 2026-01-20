<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_image_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->onDelete('cascade');
            $table->enum('gender', ['male', 'female']);
            $table->string('certificate_bg')->nullable();
            $table->json('positions')->nullable();
            $table->json('style')->nullable();
            $table->json('print_defaults')->nullable();
            $table->string('date_type', 20)->default('duration');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['track_id', 'gender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_image_settings');
    }
};
