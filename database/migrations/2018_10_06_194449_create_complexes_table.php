<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComplexesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complexes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->comment('外键：users的主键');
            $table->integer('history_read_count')->default(0)->comment('历史浏览次数');
            $table->integer('history_amount')->default(0)->comment('历史分润金额');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complexes');
    }
}
