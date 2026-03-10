<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseMappingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('external_course_id');                // Course ID in external platform
            $table->string('external_course_name');              // Course name for display
            $table->string('external_course_name_en')->nullable(); // English name
            $table->foreignId('track_id')                        // Map to certificate track
                  ->constrained()
                  ->onDelete('cascade');
            $table->enum('certificate_type', ['student', 'teacher'])->default('student');
            $table->enum('default_gender', ['male', 'female'])->nullable(); // Default if not provided
            $table->json('custom_fields')->nullable();           // Additional certificate fields
            $table->json('style_overrides')->nullable();         // Custom styling
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('certificates_generated')->default(0);
            $table->timestamps();

            // Each external course ID must be unique per API client
            $table->unique(['api_client_id', 'external_course_id'], 'unique_client_course');
            $table->index('track_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_mappings');
    }
}
