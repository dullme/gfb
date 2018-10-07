<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->comment('用户名');
            $table->decimal('original_price', 10, 2)->comment('发行价');
            $table->decimal('retail_price', 10, 2)->comment('零售价');
            $table->string('mobile')->nullable()->comment('联系电话');
            $table->string('alipay_account')->nullable()->comment('支付宝账户');
            $table->string('alipay_name')->nullable()->comment('支付宝账户姓名');
            $table->boolean('status')->default(0)->comment('状态');
            $table->string('password');
            $table->string('initial_password')->nullable()->comment('初始密码');
            $table->timestamp('activation_at')->nullable()->comment('激活时间');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
