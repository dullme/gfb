<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use Cache;
use App\Models\CapitalPool;
use App\Models\User;
use App\Models\Withdraw;
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
     * 用户今日总分润
     * @param $user_id
     * @return float|int
     */
    public function userTodayAmount($user_id) {
        $a_amount = $this->redis->get('a_' . $user_id . '_' . date('Ymd'));

        return $a_amount ? $a_amount : 0;
    }

    /**
     * 保存用户当天在 Redis 中的数据到数据库并清除 Redis
     * @param $user_id
     * @return mixed
     */
    public function storeUserTodayAmountAndVisit($user_id) {
        $amount = $this->userTodayAmount($user_id);
        $history_read_count = $this->userTodayVisit($user_id);

        $complex = Complex::create([
            'user_id' => $user_id,
            'history_read_count' => $history_read_count,
            'history_amount' => $amount * (-1),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if(!$complex){
            return false;
        }

        $this->redis->del('a_' . $user_id . '_' . date('Ymd'));
        $this->redis->del('v_' . $user_id . '_' . date('Ymd'));

        return User::where('id', $user_id)->increment([
            'amount' => $amount,
            'history_read_count' => $history_read_count,
        ]);
    }

    /**
     * 各种金额参数
     * @return array
     */
    public function getTodayAmount() {
        $withdraw = Withdraw::where('status', 2)->get();
        $today_amount = $this->todayTotalAmount();
        $users = Cache::remember('users', 60, function (){
            return User::whereIn('status', [2,3])->get();
        });

        return [
            'ad_fee'   => CapitalPool::all()->sum('price'), //广告费总额
            'amount'   => $users->sum('history_amount') / 10000,    //分润总额
            'today_amount'   => $today_amount / 10000,  //今日分润
            'withdraw' => (int)$withdraw->sum('price') / 10000,  //提现总额
            'visits'   => $this->todayTotalVisit(),  //今日访问人数
        ];
    }


}
