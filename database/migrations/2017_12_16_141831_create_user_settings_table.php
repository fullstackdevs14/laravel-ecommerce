<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_settings'))
         Schema::create('user_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->boolean('email')->default(true);
            $table->boolean('browser')->default(true);
            $table->boolean('private_msg')->default(true);
            $table->boolean('comment_wallpaper')->default(true);
            $table->boolean('comment_profile')->default(true);
            $table->boolean('follow_profile')->default(true);
            $table->boolean('favorite_wallpaper')->default(true);
            $table->boolean('wallpaper_gratuity')->default(true);
            $table->boolean('achievement')->default(true);
            $table->boolean('follower_levelup')->default(true);
            $table->boolean('newsletter')->default(true);
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
        Schema::dropIfExists('user_settings');
    }
}