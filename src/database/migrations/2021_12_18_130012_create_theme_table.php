<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThemeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('theme', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->boolean('dark_theme')->default(0);
            $table->string('logo_path')->nullable();
            $table->string('text')->nullable();
            $table->string('wallpaper')->nullable();
            $table->string('primary_button')->nullable();
            $table->string('secondary_button')->nullable();
            $table->string('primary_text')->nullable();
            $table->string('secondary_text')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('theme');
    }
}
