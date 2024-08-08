<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomInfoTableUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_infos', function (Blueprint $table) {
            $table->double('lat')->nullable();
            $table->double('long')->nullable();
            $table->string('locationName')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_infos', function (Blueprint $table) {
            $table->dropColumn('lat');
            $table->dropColumn('long');
            $table->dropColumn('locationName');
        });
    }
}
