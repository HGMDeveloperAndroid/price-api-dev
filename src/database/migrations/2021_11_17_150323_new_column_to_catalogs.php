<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NewColumnToCatalogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
        Schema::table('chains', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
        Schema::table('lines', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
        Schema::table('zones', function (Blueprint $table) {
            $table->unsignedBigInteger('lang_id')->nullable()->default(1);
            $table->foreign('lang_id')->references('id')->on('languages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
        Schema::table('chains', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
        Schema::table('lines', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
    }
}
