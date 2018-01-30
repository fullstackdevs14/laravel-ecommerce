<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImageCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('image_comments'))
        Schema::create('image_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('image_id')->unsigned();
            $table->integer('author_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->mediumText('content');
            $table->timestamps();
            $table->string('sticker', 255)->nullable();
            $table->string('author_img', 255)->nullable();
            $table->string('author_name', 255);
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
        Schema::dropIfExists('image_comments');
    }
}
