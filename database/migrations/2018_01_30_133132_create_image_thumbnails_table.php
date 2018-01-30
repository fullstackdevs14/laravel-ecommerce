<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageThumbnailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('image_thumbnails'))
        Schema::create('image_thumbnails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('s3_id');
            $table->integer('image_id')->unsigned();
            $table->integer('width')->unsigned();
            $table->integer('height')->unsigned();
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
        Schema::dropIfExists('image_thumbnails');
    }
}
