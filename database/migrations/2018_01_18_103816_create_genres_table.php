<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGenresTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->increments('id');
            $table->string('title');
            $table->integer('type')->nullable();
            $table->boolean('disabled')->default(0);
        });

        if (env('DB_CONNECTION') !== 'pgsql') {
            DB::statement('ALTER TABLE genres AUTO_INCREMENT = 100000;');
        } else {
            DB::statement('ALTER SEQUENCE genres_id_seq RESTART 1000000;');
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('genres');
    }
}
