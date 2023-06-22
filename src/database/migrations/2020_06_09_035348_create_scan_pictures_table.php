<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScanPicturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scan_pictures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_scan')->nullable();
            $table->string('product_picture');
            $table->string('shelf_picture')->nullable();
            $table->string('promo_picture')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_scan')->references('id')->on('scans');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scan_pictures');
    }
}
