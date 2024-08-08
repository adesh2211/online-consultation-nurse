<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TiersTableAdd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->double('price')->comment('per hour');
            $table->unsignedBigInteger('order_by');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tier_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->unsignedBigInteger('tier_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('request_tier_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('request_id');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('tier_id');
            $table->unsignedBigInteger('tier_option_id');
            $table->unsignedBigInteger('updated_by');
            $table->string('type');
            $table->softDeletes();
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
        Schema::dropIfExists('tiers');
        Schema::dropIfExists('tier_options');
        Schema::dropIfExists('request_tier_plans');
    }
}
