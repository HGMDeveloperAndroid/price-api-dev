<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeoPlaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geo_places', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('p1_x', 11, 8);
            $table->decimal('p1_y', 11, 8);
            $table->decimal('p2_x', 11, 8);
            $table->decimal('p2_y', 11, 8);
            $table->decimal('p3_x', 11, 8);
            $table->decimal('p3_y', 11, 8);
            $table->decimal('p4_x', 11, 8);
            $table->decimal('p4_y', 11, 8);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('geo_places');
    }
}
