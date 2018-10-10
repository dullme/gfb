<?php

namespace App\Http\Controllers;

use App\Models\CapitalPool;
use App\Models\Complex;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Predis\Client;

class RedisController extends Controller
{
    protected $redis;

    /**
     * RedisController constructor.
     * @param $redis
     */
    public function __construct() {
        $this->redis = new Client(config('database.redis.default'));
    }

    /**
     * 从获取今日总分润
     * @return float|int
     */
    public function todayTotalAmount() {
        $a_amount = $this->redis->keys('a_*_' . date('Ymd'));

        return $a_amount ? collect($this->redis->mget($a_amount))->sum()/100000 : 0;
    }

    /**
     * 用户今日总分润
     * @param $user_id
     * @return float|int
     */
    public function userTodayAmount($user_id) {
        $a_amount = $this->redis->get('a_'.$user_id.'_' . date('Ymd'));
        return $a_amount ?$a_amount/100000:0;
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
     * @return int|mixed
     */
    public function userTodayVisit($user_id) {

        return $this->redis->get('v_'.$user_id.'_' . date('Ymd')) ?:0;
    }

    /**
     * 各种金额参数
     * @param null $user_id
     * @return array
     */
    public function getTodayAmount($user_id = null){
        $complex = Complex::all();
        $withdraw = Withdraw::where('status', 1)->get();

        $data = [
            'ad_fee' => round(CapitalPool::all()->sum('price'), 4), //广告费总额
            'amount' => round($complex->sum('history_amount')/100000 + $this->todayTotalAmount(), 4),    //分润总额
            'withdraw' => round($withdraw->sum('price'), 4),  //提现总额
            'visits' => $this->todayTotalVisit()  //今日访问人数
        ];
        if($user_id){
            $user_a_amount = $this->userTodayAmount($user_id);
            $use_amount = round($complex->where('id', $user_id)->sum('history_amount') + $user_a_amount , 4);

            $withdraw_amount = round($withdraw->where('user_id', $user_id)->sum('price'), 4);

            $data['use_amount'] = $use_amount;
            $data['withdraw_amount'] = $withdraw_amount;
            $data['visit'] = $this->userTodayVisit($user_id);
        }

        return $data;
    }


}
