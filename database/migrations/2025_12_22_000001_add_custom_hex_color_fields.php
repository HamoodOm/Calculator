<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomHexColorFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add custom_hex_color to roles for badge color
        Schema::table('roles', function (Blueprint $table) {
            $table->string('custom_hex_color', 10)->nullable()->after('theme_badge');
        });

        // Add custom_hex_color to institutions for header color
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('custom_hex_color', 10)->nullable()->after('header_color');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('custom_hex_color');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('custom_hex_color');
        });
    }
}
