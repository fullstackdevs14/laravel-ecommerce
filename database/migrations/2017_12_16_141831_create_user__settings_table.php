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
        Schema::create('user__settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->boolean('newsletter')->default(true);
            $table->boolean('announcement')->default(true);
            $table->boolean('private_msg')->default(true);
            $table->boolean('comment_wallpaper')->default(true);
            $table->boolean('post_response')->default(true);
            $table->boolean('comment_profile')->default(true);
            $table->boolean('favorite')->default(true);
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
        Schema::dropIfExists('user__settings');
    }
}
