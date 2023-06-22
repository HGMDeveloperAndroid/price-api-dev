<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('products');
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('barcode', 60)->unique();
            $table->decimal('price', 8,2);
            $table->decimal('min_price', 8,2)->nullable();
            $table->decimal('max_price', 8,2)->nullable();
            $table->unsignedBigInteger('id_unit')->nullable();
            $table->decimal('unit_quantity', 8,2)->nullable();
            $table->unsignedBigInteger('id_group');
            $table->unsignedBigInteger('id_line')->nullable();
            $table->unsignedBigInteger('id_brand');
            $table->string('picture_path')->nullable();
            $table->enum('type', ['MC', 'MP', 'N/A']);
            $table->boolean('is_enable')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_unit')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('id_group')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('id_line')->references('id')->on('lines')->onDelete('cascade');
            $table->foreign('id_brand')->references('id')->on('brands')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('products');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
