<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function getTodayAmount(){
        $redis = new \Predis\Client();
        $a_amount = $redis->keys('a_*_' . date('Ymd'));
        $today_amount = $a_amount ? collect($redis->mget($a_amount))->sum() : 0;
        $v_amount = $redis->keys('v_*_' . date('Ymd'));
        $visits = $v_amount ? collect($redis->mget($v_amount))->sum() : 0;


        return [
            'ad_fee' => \App\Models\CapitalPool::all()->sum('price'), //广告费总额
            'amount' => \App\Models\Complex::all()->sum('history_amount') + $today_amount,    //分润总额
            'withdraw' => \App\Models\Withdraw::where('status', 1)->get()->sum('price'),  //提现总额
            'visits' => $visits  //今日访问人数
        ];
    }
}
