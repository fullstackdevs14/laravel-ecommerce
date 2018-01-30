<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('posts'))
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 255)->nullable();
            $table->string('content', 1000)->nullable();
            $table->integer('image_id')->unsigned();
            $table->integer('author_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->timestamps();
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
        Schema::dropIfExists('posts');
    }
}
