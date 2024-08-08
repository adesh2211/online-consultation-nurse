<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPackageTableAddFieldPackageCreatedFor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('packages',function(Blueprint $table) {

            $table->string('package_created_for',20)->comment('Curenik,Doctor')->nullable();
           // $table->Integer('package_updated_user',20)->nullable();
            //$table->double('price')->nullable();

       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('package_created_for');
           // $table->dropColumn('package_updated_user');
          
        });
    }
}
