<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFeedTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('feed', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title');
			$table->integer('category')->unsigned()->index('feed_category_foreign');
			$table->text('short', 65535);
			$table->text('description', 65535);
			$table->string('url', 191)->unique();
			$table->dateTime('date');
			$table->integer('position')->default(0);
			$table->integer('active')->default(1);
			$table->integer('user_id')->unsigned()->index('feed_user_id_foreign');
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
		Schema::drop('feed');
	}

}
