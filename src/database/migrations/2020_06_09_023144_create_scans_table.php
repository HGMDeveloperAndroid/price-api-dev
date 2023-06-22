<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_product')->nullable();
            $table->unsignedBigInteger('id_mission')->nullable();
            $table->unsignedBigInteger('id_scanned_by')->nullable();
            $table->unsignedBigInteger('id_reviewed_by')->nullable();
            $table->boolean('is_enable')->default(1);
            $table->boolean('is_valid')->default(0);
            $table->boolean('is_rejected')->default(0);
            $table->boolean('is_locked')->default(0);
            $table->boolean('special_price')->default(0);
            $table->string('barcode')->index();
            $table->decimal('price');
            $table->decimal('previous_price')->nullable();
            $table->decimal('unit_price')->default(0);
            $table->mediumText('comments')->nullable();
            $table->unsignedBigInteger('id_store')->nullable();
            $table->dateTime('capture_date')->nullable();
            $table->dateTime('reception_date')->nullable();
            $table->dateTime('validation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_product')->references('id')->on('products');
            $table->foreign('id_mission')->references('id')->on('missions');
            $table->foreign('id_scanned_by')->references('id')->on('users');
            $table->foreign('id_reviewed_by')->references('id')->on('users');
            $table->foreign('id_store')->references('id')->on('stores');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scans');
    }
}
