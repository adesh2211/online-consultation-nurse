<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmsats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emsats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->double('price')->nullable();
            $table->text('icon')->nullable();
            $table->unsignedBigInteger('question')->nullable();
            $table->unsignedBigInteger('marks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sp_emsats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('emsat_id');
            $table->double('price')->nullable();
            $table->unsignedBigInteger('sp_id');
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
        Schema::dropIfExists('emsats');
        Schema::dropIfExists('sp_emsats');
    }
}
