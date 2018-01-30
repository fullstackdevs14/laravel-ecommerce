<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGalleryFollowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('gallery_follows'))
            Schema::create('gallery_follows', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('gallery_id')->unsigned();
                $table->integer('follower_id')->unsigned();
                $table->string('ip_address', 255)->nullable();
                $table->timestamp('last_action', 255)->nullable();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gallery_follows');
    }
}
