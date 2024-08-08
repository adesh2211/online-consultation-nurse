<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmergancyRequestsAddFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('emergancy_requests',function(Blueprint $table) {

        $table->string('category_id')->nullable();
        $table->bigInteger('limit')->default('0')->nullable();
        $table->time('request_time')->nullable();
        $table->bigInteger('lastid')->nullable();
        $table->string('rating')->nullable();
          
    });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emergancy_requests');
    }
}
