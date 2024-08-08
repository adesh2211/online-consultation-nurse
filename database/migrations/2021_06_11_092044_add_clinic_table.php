<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClinicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sp_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->text('address');
            $table->text('logo')->nullable();
            $table->double('lat')->nullable();
            $table->double('long')->nullable();
            $table->double('price')->nullable();
            $table->double('other_price')->nullable();
            $table->timestamps();
        });

        Schema::table('service_provider_slots',function(Blueprint $table) {
            $table->unsignedBigInteger('office_id')->nullable();
        });
        Schema::table('service_provider_slots_dates',function(Blueprint $table) {
            $table->unsignedBigInteger('office_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offices');

        Schema::table('service_provider_slots', function (Blueprint $table) {
            $table->dropColumn('office_id');
        });
        Schema::table('service_provider_slots_dates', function (Blueprint $table) {
            $table->dropColumn('office_id');
        });
    }
}
