<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CategoryTableUpdateRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
           $table->tinyInteger('enable_percentage')->after('percentage')->default(0);
        });
        Schema::table('request_history', function (Blueprint $table) {
           $table->string('admin_percentage_type',20)->after('admin_cut')->default('admin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('enable_percentage');
        });
        Schema::table('request_history', function (Blueprint $table) {
            $table->dropColumn('admin_percentage_type');
        });
        
    }
}
