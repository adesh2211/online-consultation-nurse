<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RequestHistoryAddColumnAdminPercentage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_history',function(Blueprint $table) {
            $table->double('admin_cut')->nullable()->after('total_charges');
            $table->unsignedBigInteger('admin_cut_percentage')->nullable()->after('total_charges');
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
            $table->dropColumn('admin_cut');
            $table->dropColumn('admin_cut_percentage');
        });
    }
}
