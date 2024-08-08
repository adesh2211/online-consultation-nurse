<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PackageMonthlyPlan extends Migration
{
    /**

     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->double('price');
            $table->text('image_icon')->nullable();
            $table->unsignedBigInteger('total_session');
            $table->string('enable')->degault('1');
            $table->string('type',30)->comment('monthly,yearly,halfyearly');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('user_package_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('available_requests');
            $table->dateTime('expired_on');
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
        Schema::dropIfExists('package_plans');
        Schema::dropIfExists('user_package_plans');
    }
}
