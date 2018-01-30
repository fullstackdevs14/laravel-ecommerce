<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_emails'))
        Schema::create('user_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 255);
            $table->integer('user_id')->unsigned();
            $table->boolean('primary')->default(true);
            $table->string('verification_token', 255);
            $table->integer('verified_at');
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
        Schema::dropIfExists('user_emails');
    }
}
