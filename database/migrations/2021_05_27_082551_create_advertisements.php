<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('image')->nullable();
            $table->longText('video')->nullable();
            $table->string('position');
            $table->string('banner_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->unsignedBigInteger('sp_id')->nullable();
            $table->foreign('sp_id')->references('id')->on('users');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->foreign('class_id')->references('id')->on('ct_classes');
            $table->timestamps();
            $table->tinyInteger('enable')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('show_on')->default('both')->comment('both,user,sp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('advertisements');
    }
}
