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
            if($user->status == 0){
                return $this->responseError('资料有误！');
            }
            if($user->password == md5($request->get('password'))){
                $token = makeInvitationCode(6);
                $user->update(['remember_token' => $token]);
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

                return $this->responseSuccess([
                    'activated' => $user->status == 2 ? true : false,
                    'token' => json_encode([
                        'id' => $user->id,
                        'token' => $token,
                    ])
                ]);
            }else{
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

        return $redis->del($token['id']);
    }

    /**
     * 重置 Token
     * @return mixed
     */
    public function refresh() {

        return $this->proxy->refresh();
    }
}
