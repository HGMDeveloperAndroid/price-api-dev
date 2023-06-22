<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreeBProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    
        Schema::create('three_b_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('item');
            $table->unsignedBigInteger('keycode');
            $table->unsignedBigInteger('barcode');
            $table->string('description');
            $table->decimal('unit_quantity');
            $table->unsignedBigInteger('unit_id');
            $table->string('type');
            $table->foreign('unit_id')->references('id')->on('units');
            $table->decimal('price');
    });
    
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('three_b_products');
    }
}
