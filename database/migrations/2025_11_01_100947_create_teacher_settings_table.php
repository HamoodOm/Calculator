<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teacher_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->onDelete('cascade');
            $table->enum('gender', ['male', 'female']);
            $table->string('certificate_bg')->comment('Relative path to background image');
            $table->json('positions')->comment('Position map including optional photo');
            $table->json('style')->comment('Font, sizes, colors, weights, alignment per field');
            $table->json('print_defaults')->comment('Print flags: arabic_only, english_only, per-field on/off');
            $table->enum('date_type', ['duration', 'end'])->default('duration');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: one setting per track + gender combination
            $table->unique(['track_id', 'gender']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teacher_settings');
    }
}
