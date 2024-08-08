<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('booking_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('main_status')->default('pending');
            $table->string('module_type')->nullable();
            $table->string('module_table_id')->nullable();
            $table->json('order_data')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_order_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('booking_order_id');
            $table->unsignedBigInteger('sp_id');
            $table->string('status')->default('pending');
            $table->json('order_data')->nullable();
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
        Schema::dropIfExists('booking_orders');
        Schema::dropIfExists('booking_order_requests');
    }
}
