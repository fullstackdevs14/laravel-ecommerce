<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('images'))
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('s3_id');
            $table->string('s3_cached', 50);
            $table->string('title', 255);
            $table->mediumText('description')->nullable();
            $table->integer('downloads')->unsigned()->default(0);
            $table->integer('favorites')->unsigned()->default(0);
            $table->integer('upload_by')->unsigned();
            $table->dateTime('upload_date')->useCurrent();
            $table->integer('orig_width')->unsigned();
            $table->integer('orig_height')->unsigned();
            $table->integer('file_size')->unsigned();
            $table->integer('likes')->unsigned()->default(0);
            $table->integer('shares')->unsigned()->default(0);
            $table->timestamps();
            $table->string('group_name', 255)->nullable();
            $table->integer('comments')->unsigned()->default(0);
            $table->string('ip_address', 255)->nullable();
            $table->timestamp('last_action')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('images');
    }
}
