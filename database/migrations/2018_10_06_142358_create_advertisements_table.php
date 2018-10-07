<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->comment('广告标题');
            $table->string('img')->nullable()->comment('广告文件');
            $table->string('img_uri')->nullable()->comment('广告链接');
            $table->decimal('ad_expenses', 10,2)->default(0)->comment('广告经费');
            $table->integer('divided_count')->default(0)->comment('被分润次数');
            $table->decimal('divided_amount', 10,2)->default(0)->comment('被分润金额');
            $table->boolean('status')->comment('状态');
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
        Schema::dropIfExists('advertisements');
    }
}
