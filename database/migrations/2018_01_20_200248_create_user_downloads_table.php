<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserDownloadsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_downloads', function(Blueprint $table)
		{
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
		    $table->increments('id');
			$table->integer('users_id')->unsigned()->index('userid');
			$table->string('hosthash', 50)->default('');
			$table->dateTime('timestamp')->index('timestamp');
			$table->integer('releases_id')->unsigned()->comment('FK to releases.id');
            $table->foreign('users_id', 'FK_users_ud')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_downloads');
	}

}
