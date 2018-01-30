<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('groups'))
        Schema::create('groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('status', 255);
            $table->string('avatar', 255)->nullable();
            $table->string('cover_img', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('owner_id')->unsigned();
            $table->integer('primary_image_id')->unsigned();
            $table->integer('image_count')->unsigned();
            $table->timestamps();
            $table->integer('gallery_id')->unsigned();
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
        Schema::dropIfExists('groups');
    }
}
