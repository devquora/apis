<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
              $table->increments('id');
			  $table->string('title');
			  $table->string('image');
			  $table->text('description');
			  $table->enum('status', ['draft','trash','published']);
			  $table->integer('user_id');
			  $table->integer('quiz_id');
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
        Schema::table('questions', function (Blueprint $table) {
            //
        });
    }
}
