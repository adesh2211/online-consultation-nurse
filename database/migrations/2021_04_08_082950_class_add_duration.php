<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClassAddDuration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ct_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('duration')->after('limit_enroll')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ct_classes', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
}
