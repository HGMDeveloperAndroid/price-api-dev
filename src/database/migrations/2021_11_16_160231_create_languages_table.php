<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30);
            $table->string('abbreviation', 5);
            
            $table->timestamps();
            $table->softDeletes();


        });

        DB::table('languages')->insert(
            array(
                'name' => 'spanish',
                'abbreviation' => 'es'
            )
        );

        DB::table('languages')->insert(
            array(
                'name' => 'english',
                'abbreviation' => 'en'
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('languages');
    }
}
