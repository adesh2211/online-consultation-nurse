<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBannerTableAddFieldUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners',function(Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('sp_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
          

       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            // 1. Drop foreign key constraints
            $table->dropForeign(['user_id']);
            // 2. Drop the column
            $table->dropColumn('user_id');
        });
    }
}
