<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MonthlyPackagePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_package_plan_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('available_requests');
            $table->dateTime('expired_on');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('user_package_plans', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monthly_requests');
        Schema::table('user_package_plans', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
