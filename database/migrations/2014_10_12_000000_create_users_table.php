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
            $table->string('firstname');
            $table->string('lastname');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('token')->nullable();
            $table->string('avatar')->nullable();
            $table->Integer('uploads')->unsigned()->default('0');
            $table->Integer('follower_count')->unsigned()->default('0');
            $table->Integer('following_count')->unsigned()->default('0');
            $table->Integer('likes')->unsigned()->default('0');
            $table->Integer('favorites')->unsigned()->default('0');
            $table->Integer('level')->unsigned()->default('1');
            $table->Integer('exp')->unsigned();

            $table->boolean('verified')->default(false);
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
