<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Client/Platform name (e.g., "FEP Platform")
            $table->string('slug')->unique();                    // Unique identifier (e.g., "fep")
            $table->text('description')->nullable();             // Description of the platform
            $table->string('api_key', 64)->unique();            // Public API key
            $table->string('api_secret', 128);                   // Secret key (hashed)
            $table->foreignId('institution_id')                  // Link to institution
                  ->constrained()
                  ->onDelete('cascade');
            $table->string('webhook_url')->nullable();           // URL to send certificate back
            $table->string('webhook_secret', 64)->nullable();    // Secret for webhook signature
            $table->json('allowed_ips')->nullable();             // IP whitelist (null = all allowed)
            $table->json('scopes')->nullable();                  // API permissions/scopes
            $table->integer('rate_limit')->default(100);         // Requests per minute
            $table->integer('daily_limit')->default(1000);       // Certificates per day
            $table->boolean('active')->default(true);            // Is client active
            $table->timestamp('last_used_at')->nullable();       // Last API call
            $table->unsignedBigInteger('total_requests')->default(0);
            $table->unsignedBigInteger('total_certificates')->default(0);
            $table->timestamps();

            $table->index(['api_key', 'active']);
            $table->index('institution_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_clients');
    }
}
