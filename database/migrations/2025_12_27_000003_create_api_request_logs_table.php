<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiRequestLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');
            $table->string('endpoint');                          // API endpoint called
            $table->string('method', 10);                        // HTTP method
            $table->string('ip_address', 45)->nullable();        // Client IP
            $table->json('request_data')->nullable();            // Request payload (sanitized)
            $table->integer('response_code');                    // HTTP response code
            $table->json('response_data')->nullable();           // Response payload
            $table->integer('execution_time')->nullable();       // Time in ms
            $table->string('error_message')->nullable();         // Error if any
            $table->string('certificate_id')->nullable();        // Generated certificate reference
            $table->timestamps();

            $table->index(['api_client_id', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_request_logs');
    }
}
