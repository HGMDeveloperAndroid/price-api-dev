<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDarkThemeLangColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('users', 'dark_theme')){
            Schema::table('users', function (Blueprint $table){
                $table->dropColumn('dark_theme');
            });
        }
        if (Schema::hasColumn('users', 'lang')){
            Schema::table('users', function (Blueprint $table){
                $table->dropColumn('lang');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('dark_theme')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dark_theme');
        });
    }
}
