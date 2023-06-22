<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 20)->unique();
            $table->string('password', 90);
            $table->string('default_password', 90);
            $table->string('first_name', 20);
            $table->string('last_name', 20);
            $table->string('mother_last_name', 20)->nullable();
            $table->string('employee_number', 200)->nullable();
            $table->string('email', 90)->nullable();
            $table->string('cellphone', 15)->nullable();
            $table->string('picture_path', 125)->nullable();

            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
