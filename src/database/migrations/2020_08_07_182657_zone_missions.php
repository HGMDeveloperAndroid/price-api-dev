<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ZoneMissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zone_missions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_mission');
            $table->unsignedBigInteger('id_zone');
            $table->timestamps();

            $table->foreign('id_mission')->references('id')->on('missions');
            $table->foreign('id_zone')->references('id')->on('zones');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zone_missions');
    }
}
