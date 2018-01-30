<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserFavFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('user_fav_folders'))
            Schema::create('user_fav_folders', function(Blueprint $table) {
                $table->string('id', 255);
                $table->Integer('user_id')->unsigned();
                $table->string('text', 255);
                $table->string('parent', 255)->nullable();
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
        Schema::dropIfExists('user_fav_folders');
    }
}
