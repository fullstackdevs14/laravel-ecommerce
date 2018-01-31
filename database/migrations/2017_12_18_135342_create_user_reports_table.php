<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_reports'))
         Schema::create('user_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reporter')->unsigned();
            $table->integer('reported')->unsigned();
            $table->text('content');
            $table->boolean('is_solved')->default(0);
            $table->timestamps();
            $table->string('ip_address', 255)->nullable();
            $table->timestamp('last_action')->nullable();
            $table->string('chat_id', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_reports');
    }
}
