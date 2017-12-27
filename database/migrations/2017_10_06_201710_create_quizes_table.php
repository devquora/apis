<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuizesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quizes', function (Blueprint $table) {
              $table->increments('id');
			  $table->string('title');
			  $table->string('image');
			  $table->text('description');  
			  $table->string('meta_title');
			  $table->text('meta_description');
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
        Schema::table('quizes', function (Blueprint $table) {
            //
        });
    }
}
