<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyColumnAssignValidatorFromScanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE scans CHANGE assign_validator being_validated BOOLEAN  NULL DEFAULT FALSE;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE scans CHANGE being_validated assign_validator BIGINT(1) NULL DEFAULT 0;');
    }
}
