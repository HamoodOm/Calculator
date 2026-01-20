<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThemeAndLevelToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Level for role hierarchy (lower = higher privilege, allows custom roles to have rankings)
            $table->integer('level')->default(99)->after('is_system');

            // Custom theme colors (hover, text, accent, badge)
            // Note: bg is inherited from user's institution, these are for additional theming
            $table->string('theme_hover', 50)->nullable()->after('level');
            $table->string('theme_text', 50)->nullable()->after('theme_hover');
            $table->string('theme_accent', 50)->nullable()->after('theme_text');
            $table->string('theme_badge', 50)->nullable()->after('theme_accent');
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
            $table->dropColumn(['level', 'theme_hover', 'theme_text', 'theme_accent', 'theme_badge']);
        });
    }
}
