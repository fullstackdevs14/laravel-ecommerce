<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('users'))
         Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('username', 255)->unique();
            $table->string('password', 255);
            $table->string('role', 255)->default('user');
            $table->string('token', 255)->nullable();
            $table->boolean('verified')->default(false);
            $table->string('avatar', 255)->nullable();
            $table->string('cover_img', 255)->nullable();
            $table->Integer('uploads')->unsigned()->default('0');
            $table->Integer('exp')->unsigned();
            $table->Integer('level')->unsigned()->default('1');
            $table->Integer('diamond')->unsigned()->default('0');
            $table->Integer('favourites')->unsigned()->default('0');
            $table->Integer('likes')->unsigned()->default('0');
            $table->string('facebook', 150)->nullable();
            $table->string('twitter', 150)->nullable();
            $table->string('google', 150)->nullable();
            $table->string('about', 1000)->nullable();
            $table->string('join_ip', 50)->nullable();
            $table->string('last_login_ip', 50)->nullable();
            $table->timestamp('joined')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->boolean('is_warned')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
