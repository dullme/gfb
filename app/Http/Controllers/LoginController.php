<?php

namespace App\Http\Controllers;

use App\Http\Proxy\TokenProxy;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Predis\Client;

class LoginController extends ResponseController
{
    protected $proxy;

    /**
     * LoginController constructor.
     * @param $proxy
     */
    public function __construct(TokenProxy $proxy) {
        $this->proxy = $proxy;
    }

    /**
     * 用户登录
     * @param LoginRequest $request
     * @return $this|\Illuminate\Http\JsonResponse
     */
    public function userLogin(LoginRequest $request) {
        $user = User::find($request->get('username'));

        if($user){
            if($user->status == 0 || $user->status == 3){
                return $this->responseError('该卡异常，请联系客服！');
            }

            if($user->wrong_password >= 5 && Carbon::now()->lt($user->updated_at->addMinutes($user->wrong_password))){
                return $this->responseError('连续输入错误过多，请'.$user->wrong_password.'分钟后重试');
            }

            if($user->password == md5($request->get('password'))){
                $token = makeInvitationCode(6);
                $user->update([
                    'remember_token' => $token,
                    'wrong_password' => 0
                ]);
                $redis = new Client(config('database.redis.local'));
                $redis->set($user->id, json_encode([
                    'id' => $user->id,
                    'alipay_name' => $user->alipay_name,
                    'status' => $user->status,
                    'token' => $user->remember_token,
                    'amount' => $user->amount,
                    'history_amount' => $user->history_amount,
                    'history_read_count' => $user->history_read_count,
                    'activation_at' => $user->activation_at,
                    'expiration_at' => $user->expiration_at,
                ]));

                if($user->status == 2 || $user->status == 3){
                    $activated = true;
                }else{
                    $activated = false;
                }
                return $this->responseSuccess([
                    'activated' => $activated,
                    'token' => json_encode([
                        'id' => $user->id,
                        'token' => $token,
                    ])
                ]);
            }else{
                $user->increment('wrong_password');
                return $this->responseError('密码有误！');
            }
        }else{

            return $this->responseError('卡号有误！');
        }
    }

    /**
     * 用户注销
     * @param Request $request
     * @return int
     */
    public function userLogout(Request $request) {
        $res = str_replace('Bearer ', '', $request->header('Authorization'));
        $token = json_decode($res, true);
        $redis = new Client(config('database.redis.local'));
        $res = $redis->del($token['id']);

        if($res){
            User::where('id', $token['id'])->update(['remember_token' => NULL]);
        }

        return $this->responseSuccess(true) ;
    }

    /**
     * 重置 Token
     * @return mixed
     */
    public function refresh() {

        return $this->proxy->refresh();
    }

    /**
     * 初始化接口
     * @return \Illuminate\Http\JsonResponse
     */
    public function systemInfo()
    {
        return $this->responseSuccess([
            'task' => 'https://h5.m.taobao.com', //淘宝任务
            'Coupon' => 'https://www.baidu.com', //优惠券
            'version' => '1.1.1', //版本号
            'download' => 'https://guafen.oss-cn-beijing.aliyuncs.com/guafen.apk', //新版本下载地址
            'agreement' => 'https://www.baidu.com', //使用协议
            'strategy' => 'https://www.baidu.com', //挣钱攻略
            'share' => 'https://www.baidu.com', //分享
        ]);
    }
}
