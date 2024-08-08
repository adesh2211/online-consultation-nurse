<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryMaterial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->double('price')->default(0);
            $table->string('image_icon')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('subject_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subject_id')->comment('Subject id is category Id');
            $table->unsignedBigInteger('topic_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('study_materials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('topic_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('type',30)->default('image')->comment('video,image,pdf');
            $table->text('file_name')->nullable();
            $table->unsignedBigInteger('added_by')->nullable()->comment('Added By SP name');
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
        Schema::dropIfExists('topics');
        Schema::dropIfExists('subject_topics');
        Schema::dropIfExists('study_materials');
    }
}
