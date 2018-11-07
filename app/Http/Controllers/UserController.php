<?php

namespace App\Http\Controllers;

use App\Models\CapitalPool;
use Storage;
use Cache;
use Illuminate\Support\Facades\DB;
use Image;
use Validator;
use App\Models\Advertisement;
use App\Models\Complex;
use App\Models\User;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Predis\Client;

class UserController extends ResponseController {

    protected $client;
    protected $redis;

    /**
     * ProfitController constructor.
     * @param $client
     */
    public function __construct()
    {
        $this->client = new Client(config('database.redis.local'));
        $this->redis = new Client(config('database.redis.default'));
    }

    public function updateUserInfo(Request $request) {
        $user_data = $this->myAuth($request);
        if(!$user_data){
            return $this->responseError('请先登录');
        }

        $request->validate([
            'mobile'         => 'required|mobile',
            'alipay_account' => 'required',
            'alipay_name'    => 'required',
        ]);

        $user = User::find($user_data['id']);

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

        return $this->responseSuccess(true, '激活成功');
    }

    public function userInfo(Request $request) {
        $user_data = $this->myAuth($request);
        if(!$user_data){
            return $this->responseError('请先登录');
        }

        $user = User::find($user_data['id']);

        if (!$user) {
            return $this->responseError('用户信息获取失败');
        }

        if ($user->status != 2) {
            return $this->responseError('该卡尚未激活！');
        }

        $amount = $this->getTodayAmount();

        return $this->responseSuccess(array_merge([
            'username'           => $user->id,
            'mobile'             => $user->mobile,
            'alipay_account'     => $user->alipay_account,
            'alipay_name'        => $user->alipay_name,
            'activation_date_at' => substr($user->activation_at, 0, 10),
            'activation_time_at' => substr($user->activation_at, 11),
            'expiration_at'      => $user->expiration_at,
            'ad_fee'             => $amount['ad_fee'], //总金额
            'amount'             => ($amount['ad_fee'] * 10000 - $amount['amount'] * 10000) / 10000,    //可分配金额
            'withdraw'           => $amount['withdraw'],  //套现总额
        ], $this->withdrawInfo($user)));
    }

    public function getTodayAmount() {
        $info = $this->redis->get('info');
        if($info){
            $info = json_decode($info , true);
        }else{
            $withdraw = Withdraw::where('status', 2)->get();
            $users = User::whereIn('status', [2,3])->get();

            $info = [
                'ad_fee'   => CapitalPool::all()->sum('price'), //广告费总额
                'amount'   => $users->sum('history_amount') / 10000,    //分润总额
                'withdraw' => (int)$withdraw->sum('price') / 10000,  //提现总额
            ];

            $this->redis->set('info', json_encode($info));
        }

        return $info;
    }

    /**
     * 分润记录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function complex(Request $request) {
        $user_data = $this->myAuth($request);
        if(!$user_data){
            return $this->responseError('请先登录');
        }

        $user = User::find($user_data['id']);
        $today_amount = $this->redis->get('a_' . $user->id . '_' . date('Ymd'));
        $visits = $this->redis->get('v_' . $user->id . '_' . date('Ymd'));

        return $this->responseSuccess([
            'today_amount'       => $today_amount ? $today_amount / 10000 : 0,
            'visits'             => $visits ?: 0,
            'history_read_count' => $user->history_read_count,
            'history_amount'     => $user->history_amount / 10000,
        ]);
    }


    public function withdrawInfo($user) {
        $user_today_amount = $this->redis->get('a_' . $user->id . '_' . date('Ymd'));
        $user_today_amount = $user_today_amount ? $user_today_amount : 0;
        $user_today_visit = $this->redis->get('v_' . $user->id . '_' . date('Ymd')) ?: 0;;
        $withdraw_finished = Withdraw::where('user_id', $user->id);

        $amount = ($user->amount + $user_today_amount) / 10000; //可用总金额

        return [
            'use_amount'              => $amount, //可用总金额
            'use_today_amount'        => $user_today_amount / 10000, //当日浏览总金额
            'user_today_visit'        => (int) $user_today_visit, //当日浏览总次数
            'withdraw_amount'         => $this->canWithdrawAmount($amount), //可提现金额
            'history_amount'          => ($user->history_amount + $user_today_amount) / 10000,  //广告费总金额
            'withdraw_finished'       => (int) $withdraw_finished->sum('price') / 10000,  //提现总金额
            'withdraw_finished_count' => $withdraw_finished->count()  //提现总金额
        ];
    }

    public function getWithdraw(Request $request) {

        $user_data = $this->myAuth($request);
        if(!$user_data){
            return $this->responseError('请先登录');
        }

        $user = User::find($user_data['id']);

        return $this->responseSuccess($this->withdrawInfo($user));
    }

    public function storeWithdraw(Request $request) {
        $user_data = $this->myAuth($request);
        if(!$user_data){
            return $this->responseError('请先登录');
        }

        $user = User::find($user_data['id']);

        $request->validate([
            'withdraw' => 'required|integer|min:100',
        ]);

        $user_today_amount = $this->redis->get('a_' . $user->id . '_' . date('Ymd'));
        $user_today_amount = $user_today_amount ? $user_today_amount : 0;

        $amount = ($user->amount + $user_today_amount) / 10000; //可用总金额
        $can_withdraw_amount = $this->canWithdrawAmount($amount);

        if ($request->get('withdraw') != $can_withdraw_amount) {
            return $this->responseError('提现金额不足，请刷新页面后重试！');
        }

        $user_transaction = DB::transaction(function () use ($can_withdraw_amount, $user_today_amount, $user) {
            $user = User::lockForUpdate()->find($user->id);

            if (($user->amount + $user_today_amount) - ($can_withdraw_amount * 10000) < 0){
                return false;
            }
            $user->amount -= $can_withdraw_amount * 10000;

            return $user->save();
        });

        if (!$user_transaction) {
            Log::info('用户' . $user->id . '申请提现金额为' . $can_withdraw_amount . '用户表金额扣除失败');

            return $this->responseError('提现保存失败');
        }
        Log::info('用户' . $user->id . '申请提现金额为' . $can_withdraw_amount . '用户表金额扣除成功');
        $res = DB::transaction(function () use ($can_withdraw_amount, $user) {
            return Withdraw::lockForUpdate()->create([
                'user_id' => $user->id,
                'price'   => $can_withdraw_amount * 10000,
            ]);
        });
        if (!$res) {
            Log::info('用户' . $user->id . '申请提现金额为' . $can_withdraw_amount . '提现表保存失败');

            return $this->responseError('提现保存失败');
        }
        Log::info('用户' . $user->id . '申请提现金额为' . $can_withdraw_amount . '提现表保存成功');
        $withdraw_info = $this->withdrawInfo($user);
        $withdraw_info['withdraw_amount'] = 0;

        return $this->responseSuccess(array_merge([
            "user_id" => $res->user_id,
            "price"   => $res->price / 10000,
        ], $withdraw_info), '提现成功，7个工作日内到账');
    }

    /**
     * 可提现金额
     * @param $user_amount
     * @return float|int
     */
    public function canWithdrawAmount($user_amount) {
        $value = (int) ((float) $user_amount / 100);

        return $value ? $value * 100 : 0;
    }

    /**
     * 是否可以浏览广告
     */
    public function canSeeAd() {
        $config = $this->client->get('config');

        if ($config) {
            $config = json_decode($config, true);
        } else {
            $config = AdminConfig::select('name', 'value')->get()->pluck('value', 'name')->toArray();
            $this->client->set('config', json_encode($config));
        }

        return $this->responseSuccess([
            'status' => true,
            'time'   => $config['ad_frequency'],
            'text'   => $config['announcement'] != 'null' ? $config['announcement'] : null,
        ]);
    }

    public function myAuth($request)
    {
        $res = str_replace('Bearer ', '', $request->header('Authorization'));
        $token = json_decode($res, true);
        if ($token['id']) {
            $user = $this->getUserFromRedis($token['id']);
            if ($user && $user['token'] == $token['token']) {  //认证成功
                return $user;
            } else {
                $auth_user = $this->authUser($token['id'], $token['token']);
                if ($auth_user) { //认证成功
                    return $user;
                }
            }

        }

        return false;
    }

    public function getUserFromRedis($user_id)
    {
        $user = $this->client->get($user_id);
        if ($user) {
            return json_decode($user, true);
        }

        return false;
    }

    public function authUser($user_id, $token)
    {
        $user = User::find($user_id);
        if ($user && $token == $user->wrong_password) {
            $this->client->set($user->id, json_encode([
                'id'                 => $user->id,
                'alipay_name'        => $user->alipay_name,
                'status'             => $user->status,
                'token'              => $user->wrong_password,
                'amount'             => $user->amount,
                'history_amount'     => $user->history_amount,
                'history_read_count' => $user->history_read_count,
                'activation_at'      => $user->activation_at,
                'expiration_at'      => $user->expiration_at,
            ]));


            return $user;
        }

        return false;
    }
}
