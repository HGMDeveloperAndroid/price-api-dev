<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThreeBProductsChainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('three_b_products_chains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('three_b_product_id');
            $table->unsignedBigInteger('chain_id');
            $table->unsignedBigInteger('barcode');
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
        Schema::dropIfExists('three_b_products_chains');
    }
}
