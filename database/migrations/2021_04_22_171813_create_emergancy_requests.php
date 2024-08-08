<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmergancyRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emergancy_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('from_user');
            $table->bigInteger('to_user')->nullable();
            $table->bigInteger('service_id');
            $table->string('status')->default('pending');
            $table->string('request_type')->default('emergancy');
            $table->datetime('booking_date');
            $table->timestamps();
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
