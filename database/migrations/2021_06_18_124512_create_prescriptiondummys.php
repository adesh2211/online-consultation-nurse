<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrescriptiondummys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prescriptiondummys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('medicine_name');
            $table->string('duration');
            $table->string('dosage_type');
            $table->json('dosage_timimg');
            $table->integer('request_id');
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
        Schema::dropIfExists('prescriptiondummys');
    }
}
