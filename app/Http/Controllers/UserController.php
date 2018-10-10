<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Predis\Client;

class UserController extends ResponseController {

    public function updateUserInfo(Request $request) {
        $request->validate([
            'mobile'         => 'required|mobile',
            'alipay_account' => 'required',
            'alipay_name'    => 'required',
        ]);

        $user = User::find(Auth()->user()->id);

        if ($user->status == 2) {
            return $this->responseError('该卡已激活无需重新激活！');
        }

        $user->mobile = $request->get('mobile');
        $user->alipay_account = $request->get('alipay_account');
        $user->alipay_name = $request->get('alipay_name');
        $user->status = 2;
        $user->activation_at = Carbon::now();
        $user->expiration_at = Carbon::now()->addMonth($user->validity_period);
        $user->save();
        if (!$user->save()) {

            return $this->responseError('激活失败');
        }

        return $this->responseSuccess(true);
    }

    public function userInfo() {
        $user = User::find(Auth()->user()->id);

        if (!$user) {
            return $this->responseError('用户信息获取失败');
        }

        if ($user->status != 2) {
            return $this->responseError('该卡尚未激活！');
        }
        $amount = (new RedisController())->getTodayAmount();

        return $this->responseSuccess([
            'username'       => $user->id,
            'mobile'         => $user->mobile,
            'alipay_account' => $user->alipay_account,
            'alipay_name'    => $user->alipay_name,
            'activation_date_at'  => substr($user->activation_at, 0, 10),
            'activation_time_at'  => substr($user->activation_at, 11),
            'expiration_at'  => $user->expiration_at,
            'ad_fee' => $amount['ad_fee'], //总金额
            'amount' => $amount['ad_fee'] - $amount['amount'],    //可分配金额
            'withdraw' => $amount['withdraw'],  //套现总额
        ]);
    }

    /**
     * 分润记录
     */
    public function complex() {
        $complex = Complex::where('user_id', auth()->user()->id)->get();
        $redis = new Client(config('database.redis.default'));
        $today_amount = $redis->get('a_' . auth()->user()->id . '_' . date('Ymd'));
        $visits = $redis->get('v_' . auth()->user()->id . '_' . date('Ymd'));

        return $this->responseSuccess([
            'today_amount'       => $today_amount ?: 0,
            'visits'             => $visits ?: 0,
            'history_read_count' => $complex->sum('history_read_count'),
            'history_amount'     => $complex->sum('history_amount'),
        ]);
    }

    public function getImage() {
        if(Carbon::now() >= Auth()->user()->expiration_at){
            return $this->responseError('该卡已过期');
        }

        if(Auth()->user()->status == 3){
            return $this->responseError('该卡已冻结');
        }

        $redis = new Client(config('database.redis.default'));
        $visit = $redis->get('v_'.Auth()->user()->id.'_' . date('Ymd')) ?:0;

        if($visit >= config('max_visits')){
            return $this->responseError('今日访问已达上限');
        }

        $my_amount = $this->getLast_amount();
        $advertisement = Advertisement::where('status', 1)->get();
        $res = $advertisement->random();

        $redis->incrby('v_'.Auth()->user()->id.'_'.date('Ymd'), 1);
        $redis->incrby('a_'.Auth()->user()->id.'_'.date('Ymd'),$my_amount * 100000);

        return $this->responseSuccess([
            'last_amount' => $my_amount,
            'url' => $res->img_uri ? : url('storage/'.$res->img),
            'time' => config('ad_frequency')
        ]);
    }

    /**
     * 获取分润
     * @return float
     */
    public function getLast_amount() {
        $ad_start_time = Carbon::createFromTimeString(config('ad_start_time'));
        $ad_end_time = Carbon::createFromTimeString(config('ad_end_time'));
        $middle_time = $ad_start_time->gt($ad_end_time) ? 0 :$ad_start_time->diffInHours($ad_end_time); //小时
        $middle_time = ($middle_time * 60 * 60) / config('ad_frequency');  //秒
        $my_amount = round(config('daily_ad_revenue')/$middle_time, 4);

        return round($my_amount + randFloat(0.0001, 0.003), 4);
    }

    public function getWithdraw() {
        $redis = new RedisController();
        $history_amount = Complex::where('user_id', Auth()->user()->id)->sum('history_amount') * 100000;
        $user_today_amount = $redis->userTodayAmount(Auth()->user()->id) * 100000;
        $withdraw_finished = Withdraw::where([
            'user_id' => Auth()->user()->id,
            'status' => 1
        ]);

        return $this->responseSuccess([
            'use_amount' => (Auth()->user()->amount * 100000 +  $user_today_amount) /100000, //可用总金额
            'withdraw_amount' => $this->canWithdrawAmount(Auth()->user()->amount), //可提现金额
            'history_amount' => ($history_amount + $user_today_amount) / 100000,  //广告费总金额
            'withdraw_finished' => $withdraw_finished->sum('price'),  //提现总金额
            'withdraw_finished_count' => $withdraw_finished->count()  //提现总金额
        ]);
    }

    public function storeWithdraw(Request $request) {
        $request->validate([
            'withdraw'         => 'required|integer|min:100',
        ]);

        $can_withdraw_amount = $this->canWithdrawAmount(Auth()->user()->amount);

        if($request->get('withdraw') != $can_withdraw_amount){
            return $this->responseError('提现金额有误');
        }
        $user = User::find(Auth()->user()->id);

        $user->amount -= $can_withdraw_amount;
        if(!$user->save()){
            Log::info('用户'.Auth()->user()->id.'申请提现金额为'.$can_withdraw_amount.'用户表修改失败');
            return $this->responseError('提现申请失败');
        }
        Log::info('用户'.Auth()->user()->id.'申请提现金额为'.$can_withdraw_amount.'用户表修改成功');
        $res = Withdraw::create([
            'user_id' => Auth()->user()->id,
            'price' => $can_withdraw_amount,
        ]);

        if(!$res){
            Log::info('用户'.Auth()->user()->id.'申请提现金额为'.$can_withdraw_amount.'提现表保存失败');
            return $this->responseError('提现保存失败');
        }
        Log::info('用户'.Auth()->user()->id.'申请提现金额为'.$can_withdraw_amount.'提现表保存成功');
        return $this->responseSuccess($res);
    }

    public function canWithdrawAmount($user_amount) {
        $value = (int)((float)$user_amount / 100);

        return $value ? $value * 100 : 0;
    }
}
