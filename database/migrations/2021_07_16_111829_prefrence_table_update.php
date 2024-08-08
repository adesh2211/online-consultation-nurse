<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrefrenceTableUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_preferences', function (Blueprint $table) {
           $table->string('data_type',20)->after('type')->nullable();
           $table->string('input_type',20)->after('type')->nullable();
        });

        Schema::table('user_master_preferences', function (Blueprint $table) {
           $table->string('input_value')->nullable();
           $table->unsignedBigInteger('preference_option_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
