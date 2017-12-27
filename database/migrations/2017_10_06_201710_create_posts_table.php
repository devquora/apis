<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
              $table->increments('id');
			  $table->string('title');
			  $table->text('content');
			  $table->text('excerpt');
			  $table->string('meta_title');
			  $table->text('meta_description');
			  $table->string('image');
			  $table->unsignedTinyInteger('anonymous');
			  $table->enum('type', ['article','question','link']);
			  $table->string('slug')->unique();
			  $table->enum('status', ['draft','trash','published']);
			  $table->integer('user_id');
			  $table->softDeletes();
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
        Schema::table('posts', function (Blueprint $table) {
            //
        });
    }
}
