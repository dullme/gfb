<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCapitalPoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('capital_pools', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('price')->comment('广告费总额');
            $table->integer('Balance')->comment('资金池余额	');
            $table->integer('change_amount')->comment('变动金额');
            $table->string('type')->comment('类型');
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
        Schema::dropIfExists('capital_pools');
    }
}
