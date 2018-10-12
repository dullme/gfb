<?php

namespace App\Http\Controllers;

use App\Models\CapitalPool;
use App\Models\Complex;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Predis\Client;

class RedisController extends Controller {

    protected $redis;

    /**
     * RedisController constructor.
     * @param $redis
     */
    public function __construct() {
        $this->redis = new Client(config('database.redis.default'));
    }

    /**
     * 获取今日总分润
     * @return float|int
     */
    public function todayTotalAmount() {
        $a_amount = $this->redis->keys('a_*_' . date('Ymd'));

        return $a_amount ? collect($this->redis->mget($a_amount))->sum() : 0;
    }

    /**
     * 用户今日总分润
     * @param $user_id
     * @return float|int
     */
    public function userTodayAmount($user_id) {
        $a_amount = $this->redis->get('a_' . $user_id . '_' . date('Ymd'));

        return $a_amount ? $a_amount : 0;
    }

    /**
     * 获取今日总访问量
     * @return int|mixed
     */
    public function todayTotalVisit() {
        $v_amount = $this->redis->keys('v_*_' . date('Ymd'));

        return $v_amount ? collect($this->redis->mget($v_amount))->sum() : 0;
    }

    /**
     * 用户今日总访问量
     * @param $user_id
     * @return int
     */
    public function userTodayVisit($user_id) {

        return $this->redis->get('v_' . $user_id . '_' . date('Ymd')) ?: 0;
    }

    /**
     * 用户可用余额
     * @param $user_id
     * @return mixed
     */
    public function userLastAmount($user_id) {
        $complex = Complex::where('user_id', $user_id)->get();
        $withdraw = Withdraw::where('user_id', $user_id)->get();

        return $complex->sum('history_amount') - $withdraw->sum('price');
    }

    /**
     * 各种金额参数
     * @return array
     */
    public function getTodayAmount() {
        $complex = Complex::all();
        $withdraw = Withdraw::where('status', 1)->get();

        return [
            'ad_fee'   => CapitalPool::all()->sum('price'), //广告费总额
            'amount'   => ($complex->sum('history_amount') + $this->todayTotalAmount()) / 10000,    //分润总额
            'withdraw' => (int)$withdraw->sum('price') / 10000,  //提现总额
            'visits'   => $this->todayTotalVisit()  //今日访问人数
        ];
    }


}
