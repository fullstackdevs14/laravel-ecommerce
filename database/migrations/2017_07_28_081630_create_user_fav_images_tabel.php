<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFavImagesTabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_fav_images'))
            Schema::create('user_fav_images', function(Blueprint $table) {
                $table->increments('id');
                $table->string('folder_id', 255)->nullable();
                $table->Integer('user_id')->unsigned();
                $table->Integer('image_id');
                $table->string('ip_address', 255)->nullable();
                $table->timestamp('last_action')->nullable();
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
        Schema::dropIfExists('user_fav_images');
    }
}
