<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCapabilitiesToApiClientsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            // Restrict which tracks/courses this client can use (null = all tracks allowed)
            $table->json('allowed_tracks')->nullable()->after('scopes')
                ->comment('Array of track IDs this client can generate certificates for. Null = all.');

            // Restrict certificate types (student/teacher/both)
            $table->json('allowed_certificate_types')->nullable()->after('allowed_tracks')
                ->comment('Array of certificate types: student, teacher. Null = both.');

            // Max certificates per single API request
            $table->unsignedSmallInteger('max_per_request')->default(1)->after('daily_limit')
                ->comment('Maximum number of certificates per single API request.');

            // Force webhook delivery (if webhook configured, must succeed)
            $table->boolean('require_webhook_success')->default(false)->after('max_per_request')
                ->comment('If true, certificate delivery via webhook is required.');

            // Whether client can view certificate download URLs in API response
            $table->boolean('expose_download_url')->default(true)->after('require_webhook_success')
                ->comment('Include download URL in API response.');

            // Contact info / notes for admin reference
            $table->string('contact_email')->nullable()->after('expose_download_url')
                ->comment('Contact email for this API client owner.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_tracks',
                'allowed_certificate_types',
                'max_per_request',
                'require_webhook_success',
                'expose_download_url',
                'contact_email',
            ]);
        });
    }
}
