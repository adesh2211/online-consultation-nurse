<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FilterTypeOptionUpdateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('filter_types', function (Blueprint $table) {
           $table->string('data_type',20)->after('preference_name')->nullable();
           $table->string('input_type',20)->after('preference_name')->nullable();
        });

        Schema::table('service_provider_filter_options', function (Blueprint $table) {
           $table->string('input_value')->nullable();
           $table->unsignedBigInteger('filter_option_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
