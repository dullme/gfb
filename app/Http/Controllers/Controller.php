<?php

namespace App\Http\Controllers;

use App\Models\CapitalPool;
use App\Models\Complex;
use App\Models\Withdraw;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Predis\Client;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function getTodayAmount($user_id = null){
        $redis = new Client(config('database.redis.default'));
        $a_amount = $redis->keys('a_*_' . date('Ymd'));
        $today_amount = $a_amount ? collect($redis->mget($a_amount))->sum() : 0;
        $v_amount = $redis->keys('v_*_' . date('Ymd'));
        $visits = $v_amount ? collect($redis->mget($v_amount))->sum() : 0;
        $complex = Complex::all();
        $withdraw = Withdraw::where('status', 1)->get();

        $data = [
            'ad_fee' => round(CapitalPool::all()->sum('price'), 2), //广告费总额
            'amount' => round($complex->sum('history_amount') + $today_amount, 2),    //分润总额
            'withdraw' => round($withdraw->sum('price'), 2),  //提现总额
            'visits' => $visits  //今日访问人数
        ];
        if($user_id){
            $user_a_amount = $redis->get('a_'.$user_id.'_' . date('Ymd')) ?:0;
            $use_amount = round($complex->where('id', $user_id)->sum('history_amount') + $user_a_amount , 2);

            $withdraw_amount = round($withdraw->where('user_id', $user_id)->sum('price'), 2);

            $data['use_amount'] = $use_amount;
            $data['withdraw_amount'] = $withdraw_amount;
        }


        return $data;
    }
}
