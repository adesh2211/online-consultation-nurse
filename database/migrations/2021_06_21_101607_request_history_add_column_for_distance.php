<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RequestHistoryAddColumnForDistance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_history',function(Blueprint $table) {
            $table->unsignedBigInteger('total_distance')->default(0)->after('request_id');
            $table->double('total_distance_price')->default(0)->after('request_id');
            $table->double('total_distance_price_per_km')->default(0)->after('request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_history', function (Blueprint $table) {
            $table->dropColumn('total_distance');
            $table->dropColumn('total_distance_price');
            $table->dropColumn('total_distance_price_per_km');
        });
    }
}
